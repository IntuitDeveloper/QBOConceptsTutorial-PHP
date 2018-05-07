<?php

require_once(__DIR__ . '/vendor/autoload.php');
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Core\Http\Serialization\XmlObjectSerializer;
use QuickBooksOnline\API\Facades\Account;
use QuickBooksOnline\API\Facades\JournalEntry;
// Import Facade classes you are going to use here
// For example, if you need to use Customer, add
// use QuickBooksOnline\API\Facades\Customer;

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


    // Retrieve the accessToken value from session variable
    $accessToken = $_SESSION['sessionAccessToken'];
    $dataService->throwExceptionOnError(true);

    // Update the OAuth2Token of the dataService object
    $dataService->updateOAuth2Token($accessToken);


    /*
     * Usecase 1
     * Generate names for the Bank Account and Credit Card Account
     */
    $bankAccountRef = getBankAccountObj($dataService);
    $creditCardAccountRef = getCreditCardAccountObj($dataService);

    /*
     * Usecase 2
     * Make Journal Entry using the above two accounts created / Updated
     */
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
                        "value" => $bankAccountRef->Id               ]
                ]
            ],
            [
                "Description" => "nov portion of rider insurance",
                "Amount" => 100.0,
                "DetailType" => "JournalEntryLineDetail",
                "JournalEntryLineDetail" => [
                    "PostingType" => "Credit",
                    "AccountRef" => [
                        "value" => $creditCardAccountRef->Id                    ]
                ]
            ]
        ]
    ]);
    $resultingObj = $dataService->Add($theResourceObj);
    $result = json_encode($resultingObj, JSON_PRETTY_PRINT);
    print "Created Id={$resultingObj->Id}. Reconstructed response body:\n\n";
    print_r($result);

}


/*
   Find if an account of "Bank" type exists, if not, create one
 */
function getBankAccountObj($dataService) {

    $accountArray = $dataService->Query("select * from Account where AccountType='" . 'Bank' . "' and AccountSubType='" . 'Bank' . "'");
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
        "AccountSubType" => 'Bank',
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
   Find if an account of "Bank" type exists, if not, create one
 */
function getCreditCardAccountObj($dataService) {

    $accountArray = $dataService->Query("select * from Account where AccountType='" . 'Credit Card' . "' and AccountSubType='" . 'CreditCard' . "'");
    $error = $dataService->getLastError();
    if ($error) {
        logError($error);
    } else {
        if (sizeof($accountArray) > 0) {
            return current($accountArray);
        }
    }


    // Create Expense Account
    $creditCardAccountRequestObj = Account::create([
        "AccountType" => 'Bank',
        "AccountSubType" => 'Bank',
        "Name" => "CreditCardAccount-" . getGUID()
    ]);
    $creditCardAccountResponseObj = $dataService->Add($creditCardAccountRequestObj);
    $error = $dataService->getLastError();
    if ($error) {
        logError($error);
    } else {
        echo "Created Expense Account with Id={$creditCardAccountResponseObj->Id}.\n\n";
        return $creditCardAccountResponseObj;
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


$result = accounting();

?>
