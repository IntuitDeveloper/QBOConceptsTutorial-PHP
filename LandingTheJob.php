<?php

require_once(__DIR__ . '/vendor/autoload.php');
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Facades\Customer;
use QuickBooksOnline\API\Facades\Item;
use QuickBooksOnline\API\Facades\Estimate;
use QuickBooksOnline\API\Facades\Invoice;
use QuickBooksOnline\API\Facades\Account;
//Import Facade classes you are going to use here
//For example, if you need to use Customer, add
//use QuickBooksOnline\API\Facades\Customer;

const INCOME_ACCOUNT_TYPE = "Income";
const INCOME_ACCOUNT_SUBTYPE = "SalesOfProductIncome";
const EXPENSE_ACCOUNT_TYPE = "Cost of Goods Sold";
const EXPENSE_ACCOUNT_SUBTYPE = "SuppliesMaterialsCogs";
const ASSET_ACCOUNT_TYPE = "Other Current Asset";
const ASSET_ACCOUNT_SUBTYPE = "Inventory";

session_start();

function landingTheJob()

{
    /*  This sample performs the folowing functions:
     1.   Add 1 customer
     2.   Add 1 item
     3    Create Estimate
     4.   Update Amount in the Estimate
     5.   Convert Estimates to Invoices
     6.   Update invoice to add $5 discount
    */


    // Create SDK instance
    $config = include('config.php');
    $dataService = DataService::Configure(array(
        'auth_mode' => 'oauth2',
        'ClientID' => $config['client_id'],
        'ClientSecret' =>  $config['client_secret'],
        'RedirectURI' => $config['oauth_redirect_uri'],
        'scope' => $config['oauth_scope'],
        'baseUrl' => "development"
    ));

    /*
     * Retrieve the accessToken value from session variable
     */
    $accessToken = $_SESSION['sessionAccessToken'];
    $dataService->throwExceptionOnError(true);
    /*
     * Update the OAuth2Token of the dataService object
     */
    $dataService->updateOAuth2Token($accessToken);


    /*
     * Create Estimate Item using a Item Ref and Customer Ref
     */
    $estimateRequestObj = getEstimateCreateRequestObj($dataService);
    $estimateCreateResponseObj = $dataService->Add($estimateRequestObj);
    $error = $dataService->getLastError();
    if ($error) {
        logError($error);
    } else {
        echo "Created Estimate with Id={$estimateCreateResponseObj->Id}. Reconstructed response body:\n\n";
    }
    print_r($estimateCreateResponseObj);

    /*
     * Update Amount in the Estimate
     */
    $estimateId = $estimateCreateResponseObj->Id;
    $estimate = $dataService->FindbyId('estimate', $estimateId);
    $estimateUpdateRequestObj = Estimate::update($estimate , [
        "TotalAmt" => 200
    ]);
    $estimateUpdateResponseObj = $dataService->Update($estimateUpdateRequestObj);
    print_r($estimateUpdateResponseObj);

    /*
     * Convert Estimate to Invoice
     */

    $theInvoiceRequestObj = Invoice::create([
        "CustomerRef"=> $estimateUpdateResponseObj->CustomerRef,
        "LinkedTxn"=> [
            "TxnId"=> $estimateId,
            "TxnType"=> "Estimate"
        ],
        "TotalAmt" => 100.00,
        "Line" => $estimate->Line
    ]);
    $resultingInvoiceResponseObj = $dataService->Add($theInvoiceRequestObj);
    print_r($resultingInvoiceResponseObj);

    /*
     * Update Invoice to add 5$ Discount
     */
    $lines = $resultingInvoiceResponseObj->Line;
    $lines[] = [
        "Amount" => 5,
        "DetailType" => "DiscountLineDetail",
        "DiscountLineDetail" => [
            "PercentBased" => false,
        ],
    ];
    $theInvoiceResourceObj = Invoice::update($resultingInvoiceResponseObj, [
        "CustomerRef"=> $estimateUpdateResponseObj->CustomerRef,
        "Line" => $lines,
    ]);
    $theInvoiceResponseObj = $dataService->Update($theInvoiceResourceObj);
    print_r($theInvoiceResponseObj);

}


/*
Create and return an Estimate object
- The item name must be unique
- The customer name must be unique
*/
function getEstimateCreateRequestObj($dataService) {

    // Fetch Item and Customer Refs needed to create an Estimate
    $itemRef = getItemObj($dataService);
    $customerRef = getCustomerObj($dataService);

    return Estimate::create([
        "Line" => [
            [
                "Description" => "Used Car",
                "Amount" => 100,
                "DetailType" => "SalesItemLineDetail",
                "SalesItemLineDetail" => [
                    "ItemRef" => [
                        "value" => $itemRef->Id,
                        "name" => $itemRef->Name
                    ],
                    "UnitPrice" => 100,
                    "Qty" => 1,
                    "TaxCodeRef" => [
                        "value" => "NON"
                    ]
                ]
            ],
            [
                "Amount" => 100,
                "DetailType" => "SubTotalLineDetail",
                "SubTotalLineDetail" => []
            ]
        ],
        "TxnTaxDetail" => [
            "TotalTax" => 0
        ],
        "CustomerRef" => [
            "value" => $customerRef->Id,
            "name"=> $customerRef->FullyQualifiedName
        ],
        "CustomerMemo" => [
            "value" => "Thank you for your business and have a great day!"
        ],
        "TotalAmt" => 100,
        "ApplyTaxAfterDiscount" => false,
        "PrintStatus" => "NeedToPrint",
        "EmailStatus" => "NotSet",
        "BillEmail" => 'customer@sample.com'
    ]);

}

function getItemObj($dataService) {

    $itemName = 'Sample-Item';
    $itemArray = $dataService->Query("select * from Item WHERE Name='" . $itemName . "'");
    $error = $dataService->getLastError();
    if ($error) {
        logError($error);
    } else {
        if (is_array($itemArray) && sizeof($itemArray) > 0) {
            return current($itemArray);
        }
    }

    // Fetch IncomeAccount, ExoenseAccount and AssetAccount Refs needed to create an Item
    $incomeAccount = getIncomeAccountObj($dataService);
    $expenseAccount = getExpenseAccountObj($dataService);
    $assetAccount = getAssetAccountObj($dataService);

    // Create Item
    $dateTime = new \DateTime('NOW');
    $ItemObj = Item::create([
        "Name" => $itemName,
        "Description" => "This is the sales description.",
        "Active" => true,
        "FullyQualifiedName" => "Office Supplies",
        "Taxable" => true,
        "UnitPrice" => 25,
        "Type" => "Inventory",
        "IncomeAccountRef"=> [
            "value"=>  $incomeAccount->Id
        ],
        "PurchaseDesc"=> "This is the purchasing description.",
        "PurchaseCost"=> 35,
        "ExpenseAccountRef"=> [
            "value"=> $expenseAccount->Id
        ],
        "AssetAccountRef"=> [
            "value"=> $assetAccount->Id
        ],
        "TrackQtyOnHand" => true,
        "QtyOnHand"=> 100,
        "InvStartDate"=> $dateTime
    ]);
    $resultingItemObj = $dataService->Add($ItemObj);
    $itemId = $resultingItemObj->Id;  // This needs to be passed in the Invoice creation later
    echo "Created item Id={$itemId}. Reconstructed response body below:\n";
    $result = json_encode($resultingItemObj, JSON_PRETTY_PRINT);
    print_r($result . "\n\n\n");
    return $resultingItemObj;
}

/*
  Find if a customer with DisplayName if not, create one and return
*/
function getCustomerObj($dataService) {

    $customerName = 'Bob-Smith';
    $customerArray = $dataService->Query("select * from Customer where DisplayName='" . $customerName . "'");
    $error = $dataService->getLastError();
    if ($error) {
        logError($error);
    } else {
        if (is_array($customerArray) && sizeof($customerArray) > 0) {
            return current($customerArray);
        }
    }

    // Create Customer
    $customerRequestObj = Customer::create([
        "DisplayName" => $customerName . getGUID()
    ]);
    $customerResponseObj = $dataService->Add($customerRequestObj);
    $error = $dataService->getLastError();
    if ($error) {
        logError($error);
    } else {
        echo "Created Customer with Id={$customerResponseObj->Id}.\n\n";
        return $customerResponseObj;
    }
}
/*
  Find if an account of Income type exists, if not, create one
*/
function getIncomeAccountObj($dataService) {

    $accountArray = $dataService->Query("select * from Account where AccountType='" . INCOME_ACCOUNT_TYPE . "' and AccountSubType='" . INCOME_ACCOUNT_SUBTYPE . "'");
    $error = $dataService->getLastError();
    if ($error) {
        logError($error);
    } else {
        if (is_array($accountArray) && sizeof($accountArray) > 0) {
            return current($accountArray);
        }
    }

    // Create Income Account
    $incomeAccountRequestObj = Account::create([
        "AccountType" => INCOME_ACCOUNT_TYPE,
        "AccountSubType" => INCOME_ACCOUNT_SUBTYPE,
        "Name" => "IncomeAccount-" . getGUID()
    ]);
    $incomeAccountObject = $dataService->Add($incomeAccountRequestObj);
    $error = $dataService->getLastError();
    if ($error) {
        logError($error);
    } else {
        echo "Created Income Account with Id={$incomeAccountObject->Id}.\n\n";
        return $incomeAccountObject;
    }

}

/*
  Find if an account of "Cost of Goods Sold" type exists, if not, create one
*/
function getExpenseAccountObj($dataService) {

    $accountArray = $dataService->Query("select * from Account where AccountType='" . EXPENSE_ACCOUNT_TYPE . "' and AccountSubType='" . EXPENSE_ACCOUNT_SUBTYPE . "'");
    $error = $dataService->getLastError();
    if ($error) {
        logError($error);
    } else {
        if (is_array($accountArray) && sizeof($accountArray) > 0) {
            return current($accountArray);
        }
    }

    // Create Expense Account
    $expenseAccountRequestObj = Account::create([
        "AccountType" => EXPENSE_ACCOUNT_TYPE,
        "AccountSubType" => EXPENSE_ACCOUNT_SUBTYPE,
        "Name" => "ExpenseAccount-" . getGUID()
    ]);
    $expenseAccountObj = $dataService->Add($expenseAccountRequestObj);
    $error = $dataService->getLastError();
    if ($error) {
        logError($error);
    } else {
        echo "Created Expense Account with Id={$expenseAccountObj->Id}.\n\n";
        return $expenseAccountObj;
    }

}

/*
  Find if an account of "Other Current Asset" type exists, if not, create one
*/
function getAssetAccountObj($dataService) {

    $accountArray = $dataService->Query("select * from Account where AccountType='" . ASSET_ACCOUNT_TYPE . "' and AccountSubType='" . ASSET_ACCOUNT_SUBTYPE . "'");
    $error = $dataService->getLastError();
    if ($error) {
        logError($error);
    } else {
        if (is_array($accountArray) && sizeof($accountArray) > 0) {
            return current($accountArray);
        }
    }

    // Create Asset Account
    $assetAccountRequestObj = Account::create([
        "AccountType" => ASSET_ACCOUNT_TYPE,
        "AccountSubType" => ASSET_ACCOUNT_SUBTYPE,
        "Name" => "AssetAccount-" . getGUID()
    ]);
    $assetAccountObj = $dataService->Add($assetAccountRequestObj);
    $error = $dataService->getLastError();
    if ($error) {
        logError($error);
    } else {
        echo "Created Asset Account with Id={$assetAccountObj->Id}.\n\n";
        return $assetAccountObj;
    }

}

// Generate GUID to associate with the sample account names
function getGUID(){
    if (function_exists('com_create_guid')){
        return com_create_guid();
    }else{
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = // "{"
            $hyphen.substr($charid, 0, 8);
        return $uuid;
    }
}



$result = landingTheJob();
?>

