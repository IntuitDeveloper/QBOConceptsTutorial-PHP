<?php

require_once(__DIR__ . '/vendor/autoload.php');
use QuickBooksOnline\API\DataService\DataService;

$config = include('config.php');

session_start();

$dataService = DataService::Configure(array(
    'auth_mode' => 'oauth2',
    'ClientID' => $config['client_id'],
    'ClientSecret' =>  $config['client_secret'],
    'RedirectURI' => $config['oauth_redirect_uri'],
    'scope' => $config['oauth_scope'],
    'baseUrl' => "development"
));

$OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
$authUrl = $OAuth2LoginHelper->getAuthorizationCodeURL();


// Store the url in PHP Session Object;
$_SESSION['authUrl'] = $authUrl;

//set the access token using the auth object
if (isset($_SESSION['sessionAccessToken'])) {

    $accessToken = $_SESSION['sessionAccessToken'];
    $accessTokenJson = array('token_type' => 'bearer',
        'access_token' => $accessToken->getAccessToken(),
        'refresh_token' => $accessToken->getRefreshToken(),
        'x_refresh_token_expires_in' => $accessToken->getRefreshTokenExpiresAt(),
        'expires_in' => $accessToken->getAccessTokenExpiresAt()
    );
    $dataService->updateOAuth2Token($accessToken);
    $oauthLoginHelper = $dataService -> getOAuth2LoginHelper();
    $CompanyInfo = $dataService->getCompanyInfo();
}

?>

<!DOCTYPE html>
<html>
<head>
    <link rel="apple-touch-icon icon shortcut" type="image/png" href="https://plugin.intuitcdn.net/sbg-web-shell-ui/6.3.0/shell/harmony/images/QBOlogo.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
    <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="views/common.css">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <script>

        var url = '<?php echo $authUrl; ?>';

        var OAuthCode = function(url) {

            this.loginPopup = function (parameter) {
                this.loginPopupUri(parameter);
            }

            this.loginPopupUri = function (parameter) {

                // Launch Popup
                var parameters = "location=1,width=800,height=650";
                parameters += ",left=" + (screen.width - 800) / 2 + ",top=" + (screen.height - 650) / 2;

                var win = window.open(url, 'connectPopup', parameters);
                var pollOAuth = window.setInterval(function () {
                    try {

                        if (win.document.URL.indexOf("code") != -1) {
                            window.clearInterval(pollOAuth);
                            // win.close();
                            location.reload();
                        }
                    } catch (e) {
                        console.log(e)
                    }
                }, 100);
            }
        }


        var apiCall = function() {
            this.getCompanyInfo = function() {
                /*
                AJAX Request to retrieve getCompanyInfo
                 */
                $.ajax({
                    type: "GET",
                    url: "apiCall.php",
                }).done(function( msg ) {
                    $( '#apiCall' ).html( msg );
                });
            }

            this.AddCustomer = function() {
                /*
                AJAX Request to Add Customer
                 */
                $.ajax({
                    type: "GET",
                    url: "CustomerAdd.php",
                }).done(function( msg ) {
                    $( '#apiCall' ).html( msg);
                });
            }

            this.refreshToken = function() {
                $.ajax({
                    type: "POST",
                    url: "refreshToken.php",
                }).done(function( msg ) {

                });
            }
        }

        var oauth = new OAuthCode(url);
        var apiCall = new apiCall();
    </script>
</head>
<body>

<div class="container">

    <h1>
        <a href="http://developer.intuit.com">
            <img src="views/quickbooks_logo_horz.png" id="headerLogo">
        </a>

    </h1>

    <hr>

    <div class="well text-center">

        <h1>Intuit OAuth2.0 & OpenID Connect Demo in PHP</h1>

        <br>

    </div>

    <h2>OAuth2.0</h2><h4>( Please refer to the <a target="_balnk" href="https://developer.intuit.com/docs/00_quickbooks_online/2_build/10_authentication_and_authorization/10_oauth_2.0">OAuth2.0 Documentation</a> )</h4>
    <p>If there is no access token or the access token is invalid, click the <b>Connect to QuickBooks</b> button below.</p>
    <pre id="accessToken">
        <style="background-color:#efefef;overflow-x:scroll"><?php echo json_encode($accessTokenJson, JSON_PRETTY_PRINT); ?>
    </pre>
    <a class="imgLink" href="#" onclick="oauth.loginPopup()"><img src="views/C2QB_green_btn_lg_default.png" width="178" /></a>
    <button  type="button" class="btn btn-success" onclick="apiCall.refreshToken()">Refresh Token</button>
    <hr />


    <h2>Make an API call</h2><h4>( Please refer to our <a target="_balnk" href="https://developer.intuit.com/v2/apiexplorer?apiname=V3QBO#?id=Account">API Explorer</a> )</h4>
    <p>If there is no access token or the access token is invalid, click either the <b>Connect to QucikBooks</b> or <b>Sign with Intuit</b> button above.</p>
    <pre id="apiCall"></pre>
    <button  type="button" class="btn btn-success" onclick="apiCall.getCompanyInfo()">Get Company Info</button>
    <button  type="button" class="btn btn-success" onclick="apiCall.AddCustomer()">Add a customer</button>

    <hr />

    <p>More info:</p>
    <ul>
        <li><a href="https://developer.intuit.com/docs">Intuit API Developer Guide</a></li>
        <li><a href="https://developer.intuit.com/docs/00_quickbooks_online/2_build/50_sample_apps_and_code">Intuit Sample Apps and Code</a></li>
        <li><a href="https://developer.intuit.com/docs/00_quickbooks_online/2_build/40_sdks">Intuit Official SDK's</a></li>
        <li><a href="https://github.com/anilkumarbp/intuit-demos-webhooks">Github Repo</a></li>
        <li><a href="https://github.com/anilkumarbp/intuit-demos-webhooks/issues">Report Issues</a></li>
    </ul>
    <hr>
    <p class="text-center text-muted">
        &copy; 2018 Intuit&trade;, Inc. All rights reserved. Intuit and QuickBooks are registered trademarks of Intuit Inc.
    </p>

</div>
</body>
</html>
