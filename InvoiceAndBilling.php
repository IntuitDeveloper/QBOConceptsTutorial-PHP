<?php

require_once(__DIR__ . '/vendor/autoload.php');
use QuickBooksOnline\API\DataService\DataService;
//Import Facade classes you are going to use here
//For example, if you need to use Customer, add
use QuickBooksOnline\API\Facades\Customer;
use QuickBooksOnline\API\Facades\Item;
use QuickBooksOnline\API\Facades\Invoice;
use QuickBooksOnline\API\Facades\Payment;
use QuickBooksOnline\API\Facades\Account;

session_start();

function invoiceAndBilling()
{
    /*  This sample performs the folowing functions:
     1.   Add a customer
     2.   Add an item
     3    Create invoice using the information above
     4.   Email invoice to customer
     5.   Receive payments for the invoice created above
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

    $dataService->setLogLocation("/Users/ksubramanian3/Desktop/HackathonLogs");

    /*
     * 1. Get CustomerRef and ItemRef
     */
    $customerRef = getCustomerObj($dataService);
    $itemRef = getItemObj($dataService);

    /*
     * 2. Create Invoice using the CustomerRef and ItemRef
     */
    $invoiceObj = Invoice::create([
        "Line" => [
            "Amount" => 100.00,
            "DetailType" => "SalesItemLineDetail",
            "SalesItemLineDetail" => [
                "Qty" => 2,
                "ItemRef" => [
                    "value" => $itemRef->Id
                ]
            ]
        ],
        "CustomerRef"=> [
            "value"=> $customerRef->Id
        ],
        "BillEmail" => [
            "Address" => "author@intuit.com"
        ]
    ]);
    $resultingInvoiceObj = $dataService->Add($invoiceObj);
    $invoiceId = $resultingInvoiceObj->Id;   // This needs to be passed in the Payment creation later
    echo "Created invoice Id={$invoiceId}. Reconstructed response body below:\n";
    $result = json_encode($resultingInvoiceObj, JSON_PRETTY_PRINT);
    print_r($result . "\n\n\n");

    /*
     * 3. Email Invoice to customer
     */
    $resultingMailObj = $dataService->sendEmail($resultingInvoiceObj,
        $resultingInvoiceObj->BillEmail->Address);
    echo "Sent mail. Reconstructed response body below:\n";
    $result = json_encode($resultingMailObj, JSON_PRETTY_PRINT);
    print_r($result . "\n\n\n");

    /*
     * 4. Receive payments for the invoice created above
     */
    $paymentObj = Payment::create([
        "CustomerRef" => [
            "value" => $customerRef->Id
        ],
        "TotalAmt" => 100.00,
        "Line" => [
            "Amount" => 100.00,
            "LinkedTxn" => [
                "TxnId" => $invoiceId,
                "TxnType" => "Invoice"
            ]
        ]
    ]);
    $resultingPaymentObj = $dataService->Add($paymentObj);
    $paymentId = $resultingPaymentObj->Id;
    echo "Created payment Id={$paymentId}. Reconstructed response body below:\n";
    $result = json_encode($resultingPaymentObj, JSON_PRETTY_PRINT);
    print_r($result . "\n\n\n");

}

/*
   Generate GUID to associate with the sample account names
 */
function getGUID()
{
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
        if (sizeof($customerArray) > 0) {
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
   Find if an Item is present , if not create new Item
 */
function getItemObj($dataService) {

    $itemName = 'Sample-Item';
    $itemArray = $dataService->Query("select * from Item WHERE Name='" . $itemName . "'");
    $error = $dataService->getLastError();
    if ($error) {
        logError($error);
    } else {
        if (sizeof($itemArray) > 0) {
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
  Find if an account of Income type exists, if not, create one
*/
function getIncomeAccountObj($dataService) {

    $accountArray = $dataService->Query("select * from Account where AccountType='" . INCOME_ACCOUNT_TYPE . "' and AccountSubType='" . INCOME_ACCOUNT_SUBTYPE . "'");
    $error = $dataService->getLastError();
    if ($error) {
        logError($error);
    } else {
        if (sizeof($accountArray) > 0) {
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
        if (sizeof($accountArray) > 0) {
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
        if (sizeof($accountArray) > 0) {
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



$result = invoiceAndBilling();
?>
