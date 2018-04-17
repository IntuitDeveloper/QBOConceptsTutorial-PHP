<?php

require_once(__DIR__ . '/vendor/autoload.php');
use QuickBooksOnline\API\DataService\DataService;
//Import Facade classes you are going to use here
//For example, if you need to use Customer, add
use QuickBooksOnline\API\Facades\Customer;
use QuickBooksOnline\API\Facades\Item;
use QuickBooksOnline\API\Facades\Invoice;
use QuickBooksOnline\API\Facades\Payment;
use QuickBooksOnline\API\Facades\XmlObjectSerializerTest;

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
 
//  The business logic starts here
//   1. Add a customer
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
        "GivenName"=>  "King" . rand(0, 10000), // Append a random number to have a new customer for testing repetitively 
        "MiddleName"=>  "1B",
        "FamilyName"=>  "Emperor",
        "Suffix"=>  "Jr",
        "FullyQualifiedName"=>  "Benevolent King",
        "CompanyName"=>  "King Benevolent",
        "DisplayName"=>  "Benevolent King" . rand(0,10000),
        "PrimaryPhone"=>  [
            "FreeFormNumber"=>  "(555) 555-5555"
        ],
        "PrimaryEmailAddr"=>  [
            "Address" => "krishnamurti_subramanian@intuit.com"
        ]
    ]);
    $resultingCustomerObj = $dataService->Add($customerObj); 
    $customerId = $resultingCustomerObj->Id; // This needs to be passed in the Invoice creation later
    echo "Created customer Id={$customerId}. Reconstructed response body below:\n";
    $result = json_encode($resultingCustomerObj, JSON_PRETTY_PRINT);
    print_r($result . "\n\n\n");

//  2. Add an item
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
            "value"=> 79,
            "name" => "Landscaping Services:Job Materials:Fountains and Garden Lighting"
        ],
        "PurchaseDesc"=> "This is the purchasing description.",
        "PurchaseCost"=> 35,
        "ExpenseAccountRef"=> [
            "value"=> 80,
            "name"=> "Cost of Goods Sold"
        ],
        "AssetAccountRef"=> [
            "value"=> 81,
            "name"=> "Inventory Asset"
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
                            "value" => $itemId,
                            "name" => "Hours"
                        ]
                ]
        ],
        "CustomerRef"=> [
            "value"=> $customerId
        ],
        "BillEmail" => [
            "Address" => "krishnamurti_subramanian@intuit.com"
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
