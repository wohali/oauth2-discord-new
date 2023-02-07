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

use UnexpectedValueException;

trait TokenRevocationProviderTrait
{
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
}
