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
        $requestState = null;
        try {
            $requestState = AuthenticateRequest::authenticateRequest(
                $request,
                new AuthenticateRequestOptions(
                    secretKey: Configure::read('Clerk.secret_key'),
                    authorizedParties: Configure::read('Clerk.authorized_parties')
                ),
            );
        } catch (\Exception $e) { 
            error_log('Authentication error: ' . $e->getMessage()); 
        }

        if ($requestState && $requestState->isSignedIn()) { 
            $session = $request->getAttribute('session');
            $session->write('Auth.User', $requestState->getPayload());
        } else { 
            $session = $request->getAttribute('session');
            $session->delete('Auth.User');
        }

        $response = $handler->handle($request);

        return $response;
    }
}
