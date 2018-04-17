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


session_start();

class LandingTheJob {
    private $dataService;
    private $config;
    private $accessToken;

    public function __construct() {
        // Create SDK instance
        $this->config = include('config.php');
        $this->dataService = DataService::Configure(array(
            'auth_mode' => 'oauth2',
            'ClientID' => $this->config['client_id'],
            'ClientSecret' =>  $this->config['client_secret'],
            'RedirectURI' => $this->config['oauth_redirect_uri'],
            'scope' => $this->config['oauth_scope'],
            'baseUrl' => "development"
        ));
        /*
         * Retrieve the accessToken value from session variable
         */
        $this->accessToken = $_SESSION['sessionAccessToken'];
        $this->dataService->setLogLocation("/Users/afincham/phpLogs");
        $this->dataService->throwExceptionOnError(true);
        /*
         * Update the OAuth2Token of the dataService object
         */
        $this->dataService->updateOAuth2Token($this->accessToken);
    }

    public function addCustomer() {
        $theCustomerObj = Customer::create([
            "BillAddr" => [
                "Line1" => "123 Main Street",
                "City" => "Mountain View",
                "Country" => "USA",
                "CountrySubDivisionCode" => "CA",
                "PostalCode" => "94042"
            ],
            "Notes" => "Some Notes",
            "Title" => "Mr",
            "GivenName" => "John",
            "MiddleName" => "B",
            "FamilyName" => "Doe",
            "Suffix" => "Jr",
            "FullyQualifiedName" => "Kings Groceries",
            "CompanyName" => "Kings Groceries",
            "DisplayName" => "SomeOtherDisplayname" . uniqid(),
            "PrimaryPhone" => [
                "FreeFormNumber" => "(555) 555-5555"
            ],
            "PrimaryEmailAddr" => [
                "Address" => "jdrew@myemail.com"
            ]
        ]);

        $resultingCustObj = $this->dataService->Add($theCustomerObj);
        return $resultingCustObj;
    }

    public function addItem() {
        $incomeAccountInfo = array(
            'Name' => "Test Account" . uniqid(), 
            'AccountType' => "Income",
            'AccountSubType' => "SalesOfProductIncome",
        );
        $expenseAccountInfo = array(
            'Name' => "Cost of Goods Sold" . uniqid(), 
            'AccountType' => "CostOfGoodsSold",
            'AccountSubType' => "SuppliesMaterialsCogs"
        );
        $assetAccountInfo = array(
            'Name' => "Inventory Asset" . uniqid(), 
            'AccountType' => "Other Current Asset",
            'AccountSubType' => "Inventory"
        );
        $incomeAccount = $this->createAccount($incomeAccountInfo);
        $expenseAccount = $this->createAccount($expenseAccountInfo);
        $assetAccount = $this->createAccount($assetAccountInfo);
        $theItemObj = Item::create([
          "Name" => "Used Car Sample 2" . uniqid(),
          "UnitPrice" => 200,
          "IncomeAccountRef" => [
            "value" => $incomeAccount->Id,
            "name" => $incomeAccount->Name
          ],
          "ExpenseAccountRef" => [
            "value" => $expenseAccount->Id,
            "name" => $expenseAccount->Name
          ],
          "AssetAccountRef" => [
            "value" => $assetAccount->Id,
            "name" => $assetAccount->Name
          ],
          "Type" => "Inventory",
          "TrackQtyOnHand" => true,
          "QtyOnHand" => 10,
          "InvStartDate" => "2015-01-01"
        ]);
        $resultingItemObj = $this->dataService->Add($theItemObj);
        return $resultingItemObj;
    }

    public function createAccount($accountInfo){
        $theResourceObj = Account::create($accountInfo);
        $resultingObj = $this->dataService->Add($theResourceObj);
        return $resultingObj;
    }

    public function addEstimate($lineItem, $customer) {
        $theEstimateObj = Estimate::create([
          "Line" => [
             [
               "Description" => "Used Car",
               "Amount" => $lineItem->UnitPrice,
               "DetailType" => "SalesItemLineDetail",
               "SalesItemLineDetail" => [
                 "ItemRef" => [
                   "value" => $lineItem->Id,
                   "name" => $lineItem->Name
                 ],
                 "UnitPrice" => $lineItem->UnitPrice,
                 "Qty" => 1,
                 "TaxCodeRef" => [
                   "value" => "NON"
                 ]
               ]
             ],
             [
               "Amount" => $lineItem->UnitPrice,
               "DetailType" => "SubTotalLineDetail",
               "SubTotalLineDetail" => []
             ]
           ],
           "TxnTaxDetail" => [
             "TotalTax" => 0
           ],
           "CustomerRef" => [
             "value" => $customer->Id,
             "name"=> $customer->DisplayName
           ],
           "CustomerMemo" => [
             "value" => "Thank you for your business and have a great day!"
           ],
           "TotalAmt" => $lineItem->UnitPrice,
           "ApplyTaxAfterDiscount" => false,
           "PrintStatus" => "NeedToPrint",
           "EmailStatus" => "NotSet",
           "BillEmail" => $customer->PrimaryEmailAddr
        ]);
        $resultingEstimateObj = $this->dataService->Add($theEstimateObj);
        return $resultingEstimateObj;
    }

    public function updateEstimate($id, $amt) {
        $estimate = $this->dataService->FindbyId('estimate', $id);
        $theResourceObj = Estimate::update($estimate , [
            "TotalAmt" => $amt
        ]);
        $resultingObj = $this->dataService->Update($theResourceObj);
        return $resultingObj;
    }

    public function createInvoice($estimate, $customer) {
        $theResourceObj = Invoice::create([
            "Line" => $estimate->Line,
            "CustomerRef"=> [
                  "value"=> $customer->Id
            ]
        ]);
        $resultingObj = $this->dataService->Add($theResourceObj);
        return $resultingObj;
    }

    public function updateInvoice($id, $discount) {
        $invoice = $this->dataService->FindbyId('invoice', $id);
        $discountAccountInfo = array(
            'Name' => "$5 discount" . uniqid(), 
            'AccountType' => "Income",
            'AccountSubType' => "DiscountsRefundsGiven"
        );
        $discountAccount = $this->createAccount($discountAccountInfo);
        $saleItem = array(
                     "Amount" => $discount,
                     "DetailType" => "DiscountLineDetail",
                     "DiscountLineDetail" => [
                        "PercentBased" => false,
                       "DiscountAccountRef" => [
                         "value" => $discountAccount->Id,
                         "name" => $discountAccount->Name
                       ]
                     ]
                );
        array_push($invoice->Line, $saleItem);
        $theResourceObj = Invoice::update($invoice, [
            "Line" => $invoice->Line
        ]);
        $resultingObj = $this->dataService->Update($theResourceObj);
        return $resultingObj;
    }
}


$LandingJob = new LandingTheJob();

// Add Customer use case
$customerArr = $LandingJob->addCustomer();
$customerId = $customerArr->Id;
$resultingJson = json_encode($customerArr, JSON_PRETTY_PRINT);
$printedResult = "Customer: " . $customerId . " has been created.\n" . $resultingJson;

// Add Item use case
$itemArr = $LandingJob->addItem();
$itemId = $itemArr->Id;
$resultingJson = json_encode($itemArr, JSON_PRETTY_PRINT);
$printedResult .= "\n\nItem: " . $itemId . " has been created.\n" . $resultingJson;

// Create Estimate use case
$estimateArr = $LandingJob->addEstimate($itemArr, $customerArr);
$estimateId = $estimateArr->Id;
$resultingJson = json_encode($estimateArr, JSON_PRETTY_PRINT);
$printedResult .= "\n\nEstimate: " . $estimateId . " has been created.\n" . $resultingJson;

// Update Estimate amount use case
$estimateArr = $LandingJob->updateEstimate($estimateId, 195);
$resultingJson = json_encode($estimateArr, JSON_PRETTY_PRINT);
$printedResult .= "\n\nEstimate: " . $estimateId . " has been updated.\n" . $resultingJson;

// Create Invoice use case
$invoiceArr = $LandingJob->createInvoice($estimateArr, $customerArr);
$invoiceId = $invoiceArr->Id;
$resultingJson = json_encode($invoiceArr, JSON_PRETTY_PRINT);
$printedResult .= "\n\nInvoice: " . $invoiceId . " has been created.\n" . $resultingJson;

// Update Invoice to add $5 discount use case
$resultingArr = $LandingJob->updateInvoice($invoiceId, 5.00);
$resultingJson = json_encode($resultingArr, JSON_PRETTY_PRINT);
$printedResult .= "\n\nInvoice: " . $invoiceId . " has been updated with discount.\n" . $resultingJson;

return print_r($printedResult);

//$result = landingJob();

?>
