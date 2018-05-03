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


//  The business logic starts here
//   1. Add a customer  ( First let us male sure thay the customer exisits if not we create a new customer )

    $GUID = getGUID();
    $customerName = 'Sample-Customer' . $GUID;
    $customerId = null;


    $i = 1;
    while (1) {
        $allCustomers = $dataService->FindAll('Customer', $i, 500);
        $error = $dataService->getLastError();
        if ($error) {
            echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
            echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
            echo "The Response message is: " . $error->getResponseBody() . "\n";
            exit();
        }
        if (!$allCustomers || (0==count($allCustomers))) {
            break;
        }
        foreach ($allCustomers as $oneCustomer) {
            // Check if the Income Account exists
            if($oneCustomer->GivenName == $customerName && $oneCustomer->DisplayName == $customerName)
            {
                        $customerId = $oneCustomer->Id;
            }
        }
    }

    // Create or Update Customer based on the above result
    if($customerId == null) {
        $customerObj = Customer::create([
            "BillAddr" => [
                "Line1"=>  "123 Main Street",
                "City"=>  "Mountain View",
                "Country"=>  "USA",
                "CountrySubDivisionCode"=>  "CA",
                "PostalCode"=>  "94042"
            ],
            "Notes" =>  "Here are other details.",
            "Title"=>  "Mr",
            "GivenName"=>   $customerName,
            "MiddleName"=>  "1B",
            "FamilyName"=>  "Emperor",
            "Suffix"=>  "Jr",
            "FullyQualifiedName"=>  "Sample Company",
            "CompanyName"=>  "Sample Company",
            "DisplayName"=> $customerName,
            "PrimaryPhone"=>  [
                "FreeFormNumber"=>  "(555) 555-5555"
            ],
            "PrimaryEmailAddr"=>  [
                "Address" => "author@intuit.com"
            ]
        ]);
        $resultingCustomerObj = $dataService->Add($customerObj);
        $customerId = $resultingCustomerObj->Id; // This needs to be passed in the Invoice creation later
        echo "Created customer Id={$customerId}. Reconstructed response body below:\n";
        $result = json_encode($resultingCustomerObj, JSON_PRETTY_PRINT);
        print_r($result . "\n\n\n");
    }


//  2. Add an item
    // First, let us make sure these acounts exist: Income, Expense, Asset


    $GUID = getGUID();
    $incomeAccountName = 'Sample-Income-Account' . $GUID;
    $expenseAccountName = 'Sample-Expense-Account' . $GUID;
    $assetAccountName = 'Sample-Asset-Account' . $GUID;

    $incomeAccountId = null;
    $expenseAccountId = null;
    $assetAccountId = null;

    $i = 1;
    while (1) {
        $allAccounts = $dataService->FindAll('Account', $i, 500);
        $error = $dataService->getLastError();
        if ($error) {
            echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
            echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
            echo "The Response message is: " . $error->getResponseBody() . "\n";
            exit();
        }
        if (!$allAccounts || (0==count($allAccounts))) {
            break;
        }
        foreach ($allAccounts as $oneAccount) {
            // Check if the Income Account exists
            if($oneAccount->AccountType == "Income" && $oneAccount->AccountSubType == "SalesOfProductIncome")
            {
                if($oneAccount->Name == $incomeAccountName) {
                    $incomeAccountId = $oneAccount->Id;
                }
            }
            // Check if the Expense Account exists
            if($oneAccount->AccountType == "CostOfGoodsSold" && $oneAccount->AccountSubType == "SuppliesMaterialsCogs")
            {
                if($oneAccount->Name == $expenseAccountName)
                {
                    $expenseAccountId = $oneAccount->Id;
                }
            }
            // Check if the Asset Account exists
            if($oneAccount->AccountType == "Other Current Asset" && $oneAccount->AccountSubType == "Inventory")
            {
                if($oneAccount->Name == $assetAccountName)
                {
                    $assetAccountId = $oneAccount->Id;
                }
            }
        }
    }


    // Create or Update Income Account based on the above result
    if($incomeAccountId == null) {
        $incomeAccountObj = Account::create([
            "AccountType" => "Income",
            "AccountSubType" => "SalesOfProductIncome",
            "Name" => $incomeAccountName
        ]);
        $resultingIncomeAccountObj = $dataService->Add($incomeAccountObj);
        $incomeAccountId = $resultingIncomeAccountObj->Id;
    }

    // Create or Update Expense Account based on the above result
    if($expenseAccountId == null) {
        $expenseAccountObj = Account::create([
            "AccountType" => "CostOfGoodsSold",
            "AccountSubType" => "SuppliesMaterialsCogs",
            "Name" => $expenseAccountName
        ]);
        $resultingExpenseAccountObj = $dataService->Add($expenseAccountObj);
        $expenseAccountId = $resultingExpenseAccountObj->Id;
    }

    // Create or Update Asset Account based on the above result
    if($assetAccountId == null) {
        $assetAccountObj = Account::create([
            "AccountType" => "Other Current Asset",
            "AccountSubType" => "Inventory",
            "Name" => $assetAccountName
        ]);
        $resultingAssetAccountObj = $dataService->Add($assetAccountObj);
        $assetAccountId = $resultingAssetAccountObj->Id;
    }


    $dateTime = new \DateTime('NOW');
    $ItemObj = Item::create([
        "Name" => "Office Supplies3" . rand(0, 10000),
        "Description" => "This is the sales description.",
        "Active" => true,
        "FullyQualifiedName" => "Office Supplies",
        "Taxable" => true,
        "UnitPrice" => 25,
        "Type" => "Inventory",
        "IncomeAccountRef"=> [
            "value"=>  $incomeAccountId
       ],
        "PurchaseDesc"=> "This is the purchasing description.",
        "PurchaseCost"=> 35,
        "ExpenseAccountRef"=> [
            "value"=> $expenseAccountId
        ],
        "AssetAccountRef"=> [
            "value"=> $assetAccountId
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

//  3. Create invoice using the information above
    $invoiceObj = Invoice::create([
        "Line" => [ 
                "Amount" => 100.00,
                "DetailType" => "SalesItemLineDetail",
                "SalesItemLineDetail" => [
                        "Qty" => 2,
                        "ItemRef" => [
                            "value" => $itemId
                         ]
                ]
        ],
        "CustomerRef"=> [
            "value"=> $customerId
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

//  4. send a mail
    $resultingMailObj = $dataService->sendEmail($resultingInvoiceObj, 
                                                $resultingInvoiceObj->BillEmail->Address);
    echo "Sent mail. Reconstructed response body below:\n";
    $result = json_encode($resultingMailObj, JSON_PRETTY_PRINT);
    print_r($result . "\n\n\n");

//  5.  Receive payments for the invoice created above
    $paymentObj = Payment::create([
        "CustomerRef" => [
            "value" => $customerId
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
    
    return $result;
}

$result = invoiceAndBilling();
?>
