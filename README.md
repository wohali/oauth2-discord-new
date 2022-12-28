# Discord Provider for OAuth 2.0 Client
[![Source Code](http://img.shields.io/badge/source-wohali/oauth2--discord--new-blue.svg?style=flat-square)](https://github.com/wohali/oauth2-discord-new)
[![Latest Version](https://img.shields.io/github/release/wohali/oauth2-discord-new.svg?style=flat-square)](https://github.com/wohali/oauth2-discord-new/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/wohali/oauth2-discord-new/master.svg?style=flat-square)](https://travis-ci.org/wohali/oauth2-discord-new)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/wohali/oauth2-discord-new/master.svg?style=flat-square)](https://scrutinizer-ci.com/g/wohali/oauth2-discord-new)
[![Coverage Status](https://img.shields.io/coveralls/wohali/oauth2-discord-new/master.svg?style=flat-square)](https://coveralls.io/r/wohali/oauth2-discord-new?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/wohali/oauth2-discord-new.svg?style=flat-square)](https://packagist.org/packages/wohali/oauth2-discord-new)

This package provides Discord OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client), v2.0 and up.

## Requirements

The following versions of PHP are supported.

* PHP 7.2
* PHP 7.3
* PHP 7.4
* PHP 8.0
* PHP 8.1
* PHP 8.2

## Installation

To install, use composer:

```bash
$ composer require wohali/oauth2-discord-new
```

## Usage

Usage is the same as The League's OAuth client, using `\Wohali\OAuth2\Client\Provider\Discord` as the provider.

### Sample Authorization Code Flow

This self-contained example:

1. Gets an authorization code
1. Gets an access token using the provided authorization code
1. Looks up the user's profile with the provided access token

You can try this script by [registering a Discord App](https://discord.com/developers/applications/me/create) with a redirect URI to your server's copy of this sample script. Then, place the Discord app's client id and secret, along with that same URI, into the settings at the top of the script.

```php
<?php

require __DIR__ . '/vendor/autoload.php';

session_start();

echo ('Main screen turn on!<br/><br/>');

$provider = new \Wohali\OAuth2\Client\Provider\Discord([
    'clientId' => '{discord-client-id}',
    'clientSecret' => '{discord-client-secret}',
    'redirectUri' => '{your-server-uri-to-this-script-here}'
]);

if (!isset($_GET['code'])) {

    // Step 1. Get authorization code
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: ' . $authUrl);

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    // Step 2. Get an access token using the provided authorization code
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // Show some token details
    echo '<h2>Token details:</h2>';
    echo 'Token: ' . $token->getToken() . "<br/>";
    echo 'Refresh token: ' . $token->getRefreshToken() . "<br/>";
    echo 'Expires: ' . $token->getExpires() . " - ";
    echo ($token->hasExpired() ? 'expired' : 'not expired') . "<br/>";

    // Step 3. (Optional) Look up the user's profile with the provided token
    try {

        $user = $provider->getResourceOwner($token);

        echo '<h2>Resource owner details:</h2>';
        printf('Hello %s#%s!<br/><br/>', $user->getUsername(), $user->getDiscriminator());
        var_export($user->toArray());

    } catch (Exception $e) {

        // Failed to get user details
        exit('Oh dear...');

    }
}
```

### Managing Scopes

When creating your Discord authorization URL in Step 1, you can specify the state and scopes your application may authorize.

```php
$options = [
    'state' => 'OPTIONAL_CUSTOM_CONFIGURED_STATE',
    'scope' => ['identify', 'email', '...'] // array or string
];

$authorizationUrl = $provider->getAuthorizationUrl($options);
```
If neither are defined, the provider will utilize internal defaults.

At the time of authoring this documentation, the [following scopes are available](https://discord.com/developers/docs/topics/oauth2#shared-resources-oauth2-scopes):

- bot
- connections
- email
- identify
- guilds
- guilds.join
- gdm.join
- messages.read
- rpc
- rpc.api
- rpc.notifications.read
- webhook.incoming

### Refreshing a Token

You can refresh an expired token using a refresh token rather than going through the entire process of obtaining a brand new token. To do so, simply reuse the fresh token from your data store to request a refresh:

```php
// create $provider as in the initial example
$existingAccessToken = getAccessTokenFromYourDataStore();

if ($existingAccessToken->hasExpired()) {
    $newAccessToken = $provider->getAccessToken('refresh_token', [
        'refresh_token' => $existingAccessToken->getRefreshToken()
    ]);

    // Purge old access token and store new access token to your data store.
}
```

### Client Credentials Grant

Discord provides a client credentials flow for bot developers to get their own bearer tokens for testing purposes. This returns an access token for the *bot owner*:

```php
// create $provider as in the initial example
try {

    // Try to get an access token using the client credentials grant.
    $accessToken = $provider->getAccessToken('client_credentials');

} catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

    // Failed to get the access token
    exit($e->getMessage());

}
```

### Bot Authorization

To authorize a bot, specify a scope of `bot` and set [permissions](https://discord.com/developers/docs/topics/permissions#permissions-bitwise-permission-flags) appropriately:

```php
// create $provider as in the initial example

$options = [
    'scope' => ['bot'],
    'permissions' => 1
];

$authorizationUrl = $provider->getAuthorizationUrl($options);

// Redirect user to authorization page
header('Location: ' . $authUrl);
```

## Testing

``` bash
$ ./vendor/bin/parallel-lint src test
$ ./vendor/bin/phpcs src --standard=psr2 -sp
$ ./vendor/bin/phpunit --coverage-text
```

## Contributing

Please see [CONTRIBUTING](https://github.com/wohali/oauth2-discord-new/blob/master/CONTRIBUTING.md) for details.

## Credits

- [Joan Touzet](https://github.com/wohali)
- [All Contributors](https://github.com/wohali/oauth2-discord-new/contributors)

## License

The MIT License (MIT). Please see [License File](https://github.com/wohali/oauth2-discord-new/blob/master/LICENSE) for more information.
