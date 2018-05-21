<?php
/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SARL (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SARL (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         2.0.0
 */
namespace App\Middleware;

use Cake\Core\Configure;
use Psr\Http\Message\ResponseInterface;

class GpgAuthSignMiddleware
{
    const HTTP_HEADER_GPG_SIG_BODY = 'X-GPG-Body-Signature';

    /**
     * {@inheritdoc}
     */
    public function __invoke($request, ResponseInterface $response, $next)
    {
        // Calling $next() delegates control to the *next* middleware
        // In your application's queue.
        $response = $next($request, $response);

        // Sign the successfull json responses
        if ($response->statusCode() === 200 && $request->is('json')) {
            $body = (string)$response->getBody();
            $gpg = new \Crypt_GPG(array('debug' => false));
            $gpg->addSignKey(
                Configure::read('passbolt.gpg.serverKey.fingerprint'),
                Configure::read('passbolt.gpg.serverKey.passphrase')
            );
            $signed = $gpg->sign($body, \Crypt_GPG::SIGN_MODE_DETACHED);

            $response = $response->withHeader(self::HTTP_HEADER_GPG_SIG_BODY, quotemeta(urlencode($signed)));
        }

        return $response;
    }
}
