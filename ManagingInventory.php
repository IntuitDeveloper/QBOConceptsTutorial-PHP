<?php

require_once(__DIR__ . '/vendor/autoload.php');
use QuickBooksOnline\API\DataService\DataService;

//Import Facade classes you are going to use here
use QuickBooksOnline\API\Facades\Item;
use QuickBooksOnline\API\Facades\Invoice;


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
    $itemCreateRequestObj = getItemCreateRequestObj();
    $itemCreateResponseObj = $dataService->Add($itemCreateRequestObj);
    $error = $dataService->getLastError();
    if ($error) {
      logError();
    } else {
      echo "Created Item with Id={$itemCreateResponseObj->Id}. Reconstructed response body:\n\n";
    }
    //print result
    print_r($itemCreateResponseObj);

    // Create Invoice using above item and set the quantity of items to be used as numItems
    $numItems = 2;
    $invoiceCreateRequestObj = getInvoiceCreateRequestObj($itemCreateResponseObj, $numItems);
    $invoiceCreateResponseObj = $dataService->Add($invoiceCreateRequestObj);
    $error = $dataService->getLastError();
    if ($error) {
      logError();
    } else {
      echo "Created Invoice with Id={$invoiceCreateResponseObj->Id}. Reconstructed response body:\n\n";
    }
    //print result
    print_r($invoiceCreateResponseObj);

    // Read the above created Item again and validate that the "Quantity on hand" is reduced by $numItems
    $itemReadResponseObj = $dataService->FindbyId('item', $itemCreateResponseObj->Id);
    $error = $dataService->getLastError();
    if ($error) {
      logError();
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
function getItemCreateRequestObj() {
  return Item::create([
    "Name" => "Inventory Supplier Sample - " . uniqid(),
    "UnitPrice" => 10,
    "IncomeAccountRef" => [
      "value" => "79",
      "name" => "Sales of Product Income"
    ],
    "ExpenseAccountRef" => [
      "value" => "80",
      "name" => "Cost of Goods Sold"
    ],
    "AssetAccountRef" => [
      "value" => "81",
      "name" => "Inventory Asset"
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
function getInvoiceCreateRequestObj($itemRef, $numItems) {
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
          "value"=> 1
    ]
  ]);
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
