<?php
return array(
    'authorizationRequestUrl' => 'https://appcenter.intuit.com/connect/oauth2',
    'tokenEndPointUrl' => 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer',
    'client_id' => 'place your clientID',
    'client_secret' => 'place your clientSecret',
    'oauth_scope' => 'com.intuit.quickbooks.accounting openid profile email phone address',
    'oauth_redirect_uri' => 'http://localhost:3000/callback.php',
)
?>
