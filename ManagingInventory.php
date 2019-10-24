<?php

require_once(__DIR__ . '/vendor/autoload.php');
use QuickBooksOnline\API\DataService\DataService;

//Import Facade classes you are going to use here
use QuickBooksOnline\API\Facades\Item;
use QuickBooksOnline\API\Facades\Invoice;
use QuickBooksOnline\API\Facades\Account;
use QuickBooksOnline\API\Facades\Customer;

const INCOME_ACCOUNT_TYPE = "Income";
const INCOME_ACCOUNT_SUBTYPE = "SalesOfProductIncome";
const EXPENSE_ACCOUNT_TYPE = "Cost of Goods Sold";
const EXPENSE_ACCOUNT_SUBTYPE = "SuppliesMaterialsCogs";
const ASSET_ACCOUNT_TYPE = "Other Current Asset";
const ASSET_ACCOUNT_SUBTYPE = "Inventory";
const CUSTOMER_NAME = "Bob Smith";

session_start();

/*
This function provides a runnable sample to understand
how QuickBooks Online APIs can be used to manage Inventory items
*/
function manageInventory()
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

    //Start write your business logic here
    $dataService->throwExceptionOnError(true);

    // Create Inventory Item with initial quantity on hand of 10
    $itemCreateRequestObj = getItemCreateRequestObj($dataService);
    $itemCreateResponseObj = $dataService->Add($itemCreateRequestObj);
    $error = $dataService->getLastError();
    if ($error) {
      logError($error);
    } else {
      echo "Created Item with Id={$itemCreateResponseObj->Id}. Reconstructed response body:\n\n";
    }
    //print result
    print_r($itemCreateResponseObj);

    // Create Invoice using above item and set the quantity of items to be used as numItems
    $numItems = 2;
    $invoiceCreateRequestObj = getInvoiceCreateRequestObj($dataService, $itemCreateResponseObj, $numItems);
    $invoiceCreateResponseObj = $dataService->Add($invoiceCreateRequestObj);
    $error = $dataService->getLastError();
    if ($error) {
      logError($error);
    } else {
      echo "Created Invoice with Id={$invoiceCreateResponseObj->Id}. Reconstructed response body:\n\n";
    }
    //print result
    print_r($invoiceCreateResponseObj);

    // Read the above created Item again and validate that the "Quantity on hand" is reduced by $numItems
    $itemReadResponseObj = $dataService->FindbyId('item', $itemCreateResponseObj->Id);
    $error = $dataService->getLastError();
    if ($error) {
      logError($error);
    } else {
      echo "Read Item with Id={$itemReadResponseObj->Id}. Reconstructed response body:\n\n";
    }
    //print result
    print_r($itemReadResponseObj);
    print_r("Item Quantity on Hand = " . $itemReadResponseObj->QtyOnHand);
}

/*
Create and return an Item object of Type = Inventory
- The item name must be unique
- Sales items must have IncomeAccountRef
- Purchase items must have ExpenseAccountRef
- Reference to the Inventory Asset account that tracks the current value of the inventory (AssetAccountRef)
- Set TrackQtyOnHand to true to track quantity on hand
- Set QtyOnHand as current quantity of the Inventory items available for sale
- Set UnitPrioce as the monetary value of the service or product, as expressed in the home currency
- Set InvStartDate as the date of opening balance for the inventory transaction
*/
function getItemCreateRequestObj($dataService) {

  // Fetch account Refs needed to create an Inventory Item
  $incomeAccount = getIncomeAccountObj($dataService);
  $expenseAccount = getExpenseAccountObj($dataService);
  $assetAccount = getAssetAccountObj($dataService);

  return Item::create([
    "Name" => "Inventory Supplier Sample - " . uniqid(),
    "UnitPrice" => 10,
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
    "InvStartDate" => "2018-04-01"
  ]);
}

/*
Create and return an Invoice object
- Must have at least one SalesItemLineDetail
- Must have a populated CustomerRef element referring to the customer who owes money
- Specify the ItemRef in SalesItemLineDetail to be the item that you just created above
- Specify the Qty (quantity) of items that are used as part of this Invoice creation
*/
function getInvoiceCreateRequestObj($dataService, $itemRef, $numItems) {

  // Fetch Customer Ref needed to create this Invoice
  $customerObj = getCustomerObj($dataService);

  return Invoice::create([
    "Line" => [
    [
         "Amount" => 20.00,
         "DetailType" => "SalesItemLineDetail",
         "SalesItemLineDetail" => [
           "ItemRef" => [
             "value" => $itemRef->Id,
             "name" => $itemRef->Name
           ],
           "UnitPrice" => 10,
           "Qty" => $numItems,
         ]
       ]
    ],
    "CustomerRef"=> [
          "value"=> $customerObj->Id
    ]
  ]);
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
      "Name" => "IncomeAccount-" . uniqid()
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
      "Name" => "ExpenseAccount-" . uniqid()
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
      "Name" => "AssetAccount-" . uniqid()
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

/*
  Find if a customer with DisplayName of "Bob Smith" exists, if not, create one and return
*/
function getCustomerObj($dataService) {
  $customerArray = $dataService->Query("select * from Customer where DisplayName='" . CUSTOMER_NAME . "'");
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
      "DisplayName" => CUSTOMER_NAME
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

function logError($error) {
  if ($error) {
      echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
      echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
      echo "The Response message is: " . $error->getResponseBody() . "\n";
  }
}

manageInventory();

?>
