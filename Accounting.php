<?php

require_once(__DIR__ . '/vendor/autoload.php');
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Core\Http\Serialization\XmlObjectSerializer;
use QuickBooksOnline\API\Facades\Account;
use QuickBooksOnline\API\Facades\JournalEntry;

session_start();

function accounting()
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

    //Start write your business logic here, and store the final result to $result object
    //Create  bank account
    $theResourceObj = Account::create([
      "AccountType" => "Bank",
      "Name" => "Milin Bank account7"
    ]);
    $resultingObj = $dataService->Add($theResourceObj);
    $error = $dataService->getLastError();
    if ($error) {
        echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
        echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
        echo "The Response message is: " . $error->getResponseBody() . "\n";
    }
    else {
        print "Created Id={$resultingObj->Id}. Reconstructed response body:\n\n";
        $xmlBody = XmlObjectSerializer::getPostXmlFromArbitraryEntity($resultingObj, $urlResource);
        print $xmlBody . "\n";
    }
    //Create credit card account
    $theResourceObj = Account::create([
        "AccountType" => "Credit Card",
        "Name" => "Milin Credit card account7"
    ]);
    $resultingObj = $dataService->Add($theResourceObj);
    $error = $dataService->getLastError();
    if ($error) {
        print "The Status code is: " . $error->getHttpStatusCode() . "\n";
        print "The Helper message is: " . $error->getOAuthHelperError() . "\n";
        print "The Response message is: " . $error->getResponseBody() . "\n";
    }
    else {
        print "Created Id={$resultingObj->Id}. Reconstructed response body:\n\n";
        $xmlBody = XmlObjectSerializer::getPostXmlFromArbitraryEntity($resultingObj, $urlResource);
        print $xmlBody . "\n";
    }
    //Make jornal using the two accounts created
    $theResourceObj = JournalEntry::create([
        "Line" => [
            [
                "Id" => "0",
                "Description" => "nov portion of rider insurance",
                "Amount" => 100.0,
                "DetailType" => "JournalEntryLineDetail",
                "JournalEntryLineDetail" => [
                "PostingType" => "Debit",
                "AccountRef" => [
                    "value" => "96",
                    "name" => "Opening Bal Equity"
                ]
             ]
         ],
         [
          "Description" => "nov portion of rider insurance",
          "Amount" => 100.0,
          "DetailType" => "JournalEntryLineDetail",
              "JournalEntryLineDetail" => [
                "PostingType" => "Credit",
                  "AccountRef" => [
                    "value" => "97",
                    "name" => "Notes Payable"
                  ]
              ]
          ]
        ]
    ]);
    $resultingObj = $dataService->Add($theResourceObj);

    print_r($resultingObj);
        return $resultingObj;
    }

$result = accounting();

?>
