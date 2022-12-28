<?php
/**
 * This file is part of the wohali/oauth2-discord-new library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Joan Touzet <code@atypical.net>
 * @license http://opensource.org/licenses/MIT MIT
 * @link https://packagist.org/packages/wohali/oauth2-discord-new Packagist
 * @link https://github.com/wohali/oauth2-discord-new GitHub
 */

namespace Wohali\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;
use Wohali\OAuth2\Client\Provider\Exception\DiscordIdentityProviderException;

class Discord extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * API Domain
     *
     * @var string
     */
    public $apiDomain = 'https://discord.com/api/v9';

    /**
     * Get authorization URL to begin OAuth flow
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->apiDomain.'/oauth2/authorize';
    }

    /**
     * Get access token URL to retrieve token
     *
     * @param  array $params
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->apiDomain.'/oauth2/token';
    }

    /**
     * Get revoke token URL to revoke an access/refresh token
     *
     * @return string
     */
    public function getBaseRevokeTokenUrl()
    {
        return $this->apiDomain.'/oauth2/token/revoke';
    }

    /**
     * Get provider URL to retrieve user details
     *
     * @param  AccessToken $token
     *
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->apiDomain.'/users/@me';
    }

    /**
     * Returns the string that should be used to separate scopes when building
     * the URL for requesting an access token.
     *
     * Discord's scope separator is space (%20)
     *
     * @return string Scope separator
     */
    protected function getScopeSeparator()
    {
        return ' ';
    }

    /**
     * Get the default scopes used by this provider.
     *
     * This should not be a complete list of all scopes, but the minimum
     * required for the provider user interface!
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        return [
            'identify',
            'email',
            'connections',
            'guilds',
            'guilds.join'
        ];
    }

    /**
     * Check a provider response for errors.
     *
     * @throws IdentityProviderException
     * @param  ResponseInterface @response
     * @param  array $data Parsed response data
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            throw DiscordIdentityProviderException::clientException($response, $data);
        }
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param array $response
     * @param AccessToken $token
     * @return ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new DiscordResourceOwner($response);
    }

    /**
     * Return the method to use when revoking the access/refresh token
     *
     * @return string
     */
    protected function getRevokeTokenMethod()
    {
        return self::METHOD_POST;
    }

    /**
     * Build request options used for revoking an access/refresh token
     *
     * @param  string $method
     * @param  array $params
     * @return array
     */
    protected function getRevokeTokenOptions(string $method, array $params)
    {
        return $this->optionProvider->getAccessTokenOptions($method, $params);
    }

    /**
     * Return a prepared request for revoking an access/refresh token
     *
     * @param  array $params
     * @return RequestInterface
     */
    protected function getRevokeTokenRequest(array $params)
    {
        $method = $this->getRevokeTokenMethod();
        $url = $this->getBaseRevokeTokenUrl();

        $options = $this->getRevokeTokenOptions($method, $params);

        return $this->getRequest($method, $url, $options);
    }

    /**
     * Retrieve the OAuth Token Type Hint registry
     *
     * @return array
     */
    protected function getRevokeTokenTypes()
    {
        return ['access_token', 'refresh_token'];
    }

    /**
     * Prepare request parameters for the token revocation request
     *
     * This makes sure that fields contain the correct value
     *
     * @param  array $defaults
     * @param  array $options
     * @return array
     *
     * @throws UnexpectedValueException
     */
    protected function prepareRevokeTokenParameters(array $defaults, array $options)
    {
        $provided = array_merge($defaults, $options);

        // List of all known token types that can be revoked
        $tokenTypes = $this->getRevokeTokenTypes();

        if (isset($provided['token_type_hint']) && !in_array($provided['token_type_hint'], $tokenTypes)) {
            throw new UnexpectedValueException(
                sprintf(
                    'Invalid token type hint "%s". The possible options are: %s',
                    $provided['token_type_hint'],
                    implode(', ', $tokenTypes)
                )
            );
        }

        return $provided;
    }

    /**
     * Request a token revocation
     *
     * @param  array $options
     * @return void
     *
     * @throws IdentityProviderException
     * @throws UnexpectedValueException
     */
    public function revokeToken(array $options)
    {
        $params = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];

        $params = $this->prepareRevokeTokenParameters($params, $options);
        $request = $this->getRevokeTokenRequest($params);

        // The name of the method is misleading, however, this also sends the request
        $this->getParsedResponse($request);
    }

    /**
     * Revoke an access token
     *
     * This MAY revoke the refresh token based on the same authorization grant,
     * too. _Currently, it actually is the behavior of the Discord OAuth2 server._
     *
     * However, as this is only an optional behavior (according to the section 2.1,
     * RFC7009) and it is not properly documented by Discord, it is recommended
     * to either revoke all tokens manually, or revoke the refresh token only.
     *
     * @param  string $accessToken
     * @return void
     *
     * @throws IdentityProviderException
     * @throws UnexpectedValueException
     */
    public function revokeAccessToken(string $accessToken)
    {
        $this->revokeToken([
            'token' => $accessToken,
            'token_type_hint' => 'access_token',
        ]);
    }

    /**
     * Revoke all tokens based on the same authorization grant by a refresh token
     *
     * @param  string $refreshToken
     * @return void
     *
     * @throws IdentityProviderException
     * @throws UnexpectedValueException
     */
    public function revokeRefreshToken(string $refreshToken)
    {
        $this->revokeToken([
            'token' => $refreshToken,
            'token_type_hint' => 'refresh_token',
        ]);
    }
}
