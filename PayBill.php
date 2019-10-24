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

const EXPENSE_ACCOUNT_TYPE = "Cost of Goods Sold";
const EXPENSE_ACCOUNT_SUBTYPE = "SuppliesMaterialsCogs";

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
    $accountExpenseRef = getExpenseAccountObj($dataService);
    $bankaccountRef = getBankAccountObj($dataService);
    $customerRef = getCustomerObj($dataService);
    $vendorRef = getVendorObj($dataService);

    /*
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
                                    "value" => $accountExpenseRef->Id
                                ]
                        ]
                ]
            ],
        "VendorRef" =>
            [
                "value" =>$vendorRef->Id
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
            "value" => $vendorRef->Id
        ],
        "PayType" => "Check",
        "CheckPayment" => [
            "BankAccountRef" => [
                "value" => $bankaccountRef->Id
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
                                "value" => $customerRef->Id
                            ],
                        "AccountRef" =>
                            [
                                "value" => $accountExpenseRef->Id
                            ],
                        "BillableStatus" => "Billable",
                        "TaxCodeRef" =>
                            [
                                "value" => "TAX"
                            ]
                    ]
            ]
        ],
        "VendorRef" =>
            [
                "value" => $vendorRef->Id
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
   Find if an account of "Bank" type exists, if not, create one
 */
function getBankAccountObj($dataService) {

    $accountArray = $dataService->Query("select * from Account where AccountType='" . 'Bank' . "' and AccountSubType='" . 'Checking' . "'");
    $error = $dataService->getLastError();
    if ($error) {
        logError($error);
    } else {
        if (sizeof($accountArray) > 0) {
            return current($accountArray);
        }
    }


    // Create Expense Account
    $bankAccountRequestObj = Account::create([
        "AccountType" => 'Bank',
        "AccountSubType" => 'Checking',
        "Name" => "BankAccount-" . getGUID()
    ]);
    $bankAccountObj = $dataService->Add($bankAccountRequestObj);
    $error = $dataService->getLastError();
    if ($error) {
        logError($error);
    } else {
        echo "Created Expense Account with Id={$bankAccountObj->Id}.\n\n";
        return $bankAccountObj;
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
        "DisplayName" => $customerName
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
  Find if a Vendor exists
*/
function getVendorObj($dataService) {

    $vendorName = 'Joes-Vendor';
    $vendorArray = $dataService->Query("select * from Vendor where DisplayName='" . $vendorName . "'");
    $error = $dataService->getLastError();
    if ($error) {
        logError($error);
    } else {
        if (sizeof($vendorArray) > 0) {
            return current($vendorArray);
        }
    }

    // Create Customer
    $vendorRequestObj = Vendor::create([
        "DisplayName" => $vendorName
    ]);
    $vendorResponseObj = $dataService->Add($vendorRequestObj);
    $error = $dataService->getLastError();
    if ($error) {
        logError($error);
    } else {
        echo "Created Vendor with Id={$vendorResponseObj->Id}.\n\n";
        return $vendorResponseObj;
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


$result = payBill();

?>

