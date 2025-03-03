<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Core\Configure;
use Cake\Http\Cookie\Cookie;
use Cake\I18n\Time;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Clerk\Backend\Helpers\Jwks\AuthenticateRequest;
use Clerk\Backend\Helpers\Jwks\AuthenticateRequestOptions;
use Clerk\Backend\Helpers\Jwks\AuthStatus;

class ClerkAuthMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {

        $requestState = AuthenticateRequest::authenticateRequest(
            $request,
            new AuthenticateRequestOptions(
                secretKey: Configure::read('Clerk.secret_key'),
                authorizedParties: Configure::read('Clerk.authorized_parties')
            ),
        );
        if ($requestState->isSignedIn()){
            $request = $request->withAttribute('verified_clerk_payload', $requestState->getPayload());
        } else {
            $request = $request->withAttribute('verified_clerk_payload', null);
        }

        $response = $handler->handle($request);

        return $response;
    }
}
