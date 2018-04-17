<?php
/**
 * <README:>
 * Here is a quick tutorial to create a vendor, bill for a vendor, a billPayment for a bill and a vendorCredit for a vendor.
* 0. create necessay dependency entities: an expense account (for creating bill), a bank account (for creating billPayment) and a customer account (for creating vendorCredit)
* 1. Add a new vendor 
* 2. Add a new bill item and set the value of vendorRef to be the Id of the vendor created in 1
* 3. Create a billPayment  for the bill created in 2. We need to set value of vendorRef to be the Id of the vendor created in 1 and Line.LinkedTxn.TxnId to be the Id of the bill created in 2
* 4. Create a vendorCredit item for the vendor created in 1. We need to set value of vendorRef to be the Id of the vendor created in 1
 * </README:>
 */


require_once(__DIR__ . '/vendor/autoload.php');
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Core\Http\Serialization\XmlObjectSerializer;
use QuickBooksOnline\API\Facades\Vendor;
use QuickBooksOnline\API\Facades\Bill;
use QuickBooksOnline\API\Facades\BillPayment;
use QuickBooksOnline\API\Facades\VendorCredit;
use QuickBooksOnline\API\Facades\Account;
use QuickBooksOnline\API\Facades\Customer;



//Import Facade classes you are going to use here
//For example, if you need to use Customer, add
//use QuickBooksOnline\API\Facades\Customer;


session_start();

function payBill()
{

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
    /**
     * create necessary entities for paying bills. We are ceate an expense account (for creating bill), a bank account (for creating billPayment) and a customer account (for creating vendorCredit).
     */
    $accountExpenseCreate = Account::create([
        "AccountType" => "Expense",
        "Name" => uniqid()
    ]);
    $accountExpense = $dataService->Add($accountExpenseCreate);

    $accountBankCreate = Account::create([
        "AccountType" => "Bank",
        "Name" => uniqid()
    ]);
    $accountBank = $dataService->Add($accountBankCreate);

    $customerCreate = Customer::create([
        "BillAddr" => [
            "Line1" => "123 Main Street",
            "City" => "Mountain View",
            "Country" => "USA",
            "CountrySubDivisionCode" => "CA",
            "PostalCode" => "94042"
        ],
        "GivenName" => uniqid(),
        "FamilyName" => "King",
        "FullyQualifiedName" => uniqid(),
        "CompanyName" => uniqid(),
        "DisplayName" => uniqid()
    ]);
    $customer = $dataService->Add($customerCreate);

    /*
    * build a vendor object for creation. The vendor object represents the seller from whom your company purchases any service or product. 
    * To create a vendor object, we need to provide at least one of the following fields: Title, GivenName, MiddleName, FamilyName, DisplayName, Suffix. Additionally, it is also suggested to provide TaxIdentifier and contact information such as BillAddr, WebAddr, PrimaryEmailAddr, Mobile, PrimaryPhone.(Refer to: https://developer.intuit.com/docs/api/accounting/vendor)
    */
    $vendorCreate = Vendor::create([
        "BillAddr" => [
            "Line1" => "Dianne's Auto Shop",
            "Line2" => "Dianne Bradley",
            "Line3" => "29834 Mustang Ave.",
            "City" => "Millbrae",
            "Country" => "U.S.A",
            "CountrySubDivisionCode" => "CA",
            "PostalCode" => "94030"
        ],
        "DisplayName" => uniqid(),
    ]);
    /*
     * create a vendor object via dataService.
     */
    $vendor = $dataService->Add($vendorCreate);
    print_r("***************************************************\n");
    $error = $dataService->getLastError();
    if ($error) {
        echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
        echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
        echo "The Response message is: " . $error->getResponseBody() . "\n";
    }
    else {
        echo "Created Vendor=";
        print_r($vendor);
    }
    
   /*
    * build a bill object for creation. A bill object is an AP transaction representing a request-for-payment from a third party for goods/services rendered, received, or both. 
    * To create a bill object, we need to provide at least one line(Individual line items of a transaction. Required fields include: Id, Amount and DetailType) and VendorRef (with id from the created vendor object above).(Refer to: https://developer.intuit.com/docs/api/accounting/vendor)
    */
    $billCreate = Bill::create([
        "Line" =>
        [
            [
                "Id" => "1",
                "Amount" => 200.00,
                "DetailType" => "AccountBasedExpenseLineDetail",
                "AccountBasedExpenseLineDetail" =>
                [
                    "AccountRef" =>
                    [
                        "value" => $accountExpense->Id
                    ]
                ]
            ]
        ],
        "VendorRef" =>
        [
            "value" =>$vendor->Id
        ]
    ]);
    /*
     * create a bill object via dataService.
     */
    $bill = $dataService->Add($billCreate);
    print_r("***************************************************\n");
    $error = $dataService->getLastError();
    if ($error) {
        echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
        echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
        echo "The Response message is: " . $error->getResponseBody() . "\n";
    }
    else {
        echo "Created Bill=";
        print_r($bill);
    }

    /*
    * build a billPayment object for creation. A billPayment object represents the payment transaction for a bill that the business owner receives from a vendor for goods or services purchased from the vendor.  
    * To create a billPayment, we need to provide at least one line(Individual line items of a transaction. Required fields include: Id, Amount and DetailType), VendorRef (with id from the created vendor object above), PayType(Check, CreditCard), CheckPayment(if PayType is Check)/CreditCardPayment(if PayType is CreditCard), TotalAmt.(Refer to: https://developer.intuit.com/docs/api/accounting/billpayment)
    */
    $billPayMentCreate = BillPayment::create([
        "VendorRef" => [
          "value" => $vendor->Id,
          "name" => "Bob's Burger Joint"
        ],
        "PayType" => "Check",
        "CheckPayment" => [
          "BankAccountRef" => [
            "value" => $accountBank->Id,
            "name" => "Checking"
          ]
        ],
        "TotalAmt" => 200.00,
        "PrivateNote" => "Acct. 1JK90",
        "Line" => [
          [
            "Amount" => 200.00,
            "LinkedTxn" => [
              [
                "TxnId" => $bill->Id,
                "TxnType" => "Bill"
              ]
            ]
          ]
        ]
      ]);

    /*
     * create a billPayment object via dataService. 
     */
    $billPayment = $dataService->Add($billPayMentCreate);
    print_r("***************************************************\n");
    $error = $dataService->getLastError();
    if ($error) {
        echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
        echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
        echo "The Response message is: " . $error->getResponseBody() . "\n";
    }
    else {
        echo "Created BillPayment=";
        print_r($billPayment);
    }

    /**
     * Build a vendorCredit object. The vendorCredit object is an accounts payable transaction that represents a refund or credit of payment for goods or services. It is a credit that a vendor owes you for various reasons such as overpaid bill, returned merchandise, or other reasons.
     * To create a vendorCredit, we need to provide at least one line(Individual line items of a transaction. Required fields include: Id, Amount and DetailType), VendorRef (with id from the created vendor object above).(Refer to: https://developer.intuit.com/docs/api/accounting/vendorcredit)
     */
    $vendorCreditCreate= VendorCredit::create([
        "TxnDate" => "2018-04-01",
        "Line" => [
        [
            "Id" =>"1",
            "Amount" => 90.00,
            "DetailType" => "AccountBasedExpenseLineDetail",
            "AccountBasedExpenseLineDetail" =>
            [
                "CustomerRef" =>
                [
                    "value" =>$customer->Id,
                    "name" =>"Amy's Bird Sanctuary"
                ],
                "AccountRef" =>
                [
                    "value" =>$accountExpense->Id,
                    "name" => "Bank Charges"
                ],
                "BillableStatus" => "Billable",
                "TaxCodeRef" =>
                [
                    "value" =>"TAX"
                ]
            ]
        ]
        ],
        "VendorRef" =>
        [
            "value" => $vendor->Id,
            "name" =>"Books by Bessie"
        ],
        
        "TotalAmt" => 90.00
    ]);    
    /*
     * create a vendorCredit object via dataService. 
     */
    $vendorCredit = $dataService->Add($vendorCreditCreate);
    print_r("***************************************************\n");
    $error = $dataService->getLastError();
    if ($error) {
        echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
        echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
        echo "The Response message is: " . $error->getResponseBody() . "\n";
    }
    else {
        echo "Created VendorCredit=";
        print_r($vendorCredit);
    }
    return $result;
}

$result = payBill();

?>

