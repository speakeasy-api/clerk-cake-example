<?php
declare(strict_types=1);

namespace App\Auth;

use Authentication\Authenticator\AbstractAuthenticator;
use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Psr\Http\Message\ServerRequestInterface;
use Cake\Core\Configure;
use Clerk\Backend\Helpers\Jwks\AuthenticateRequest;
use Clerk\Backend\Helpers\Jwks\AuthenticateRequestOptions;

/**
 * Clerk Authenticator - Authenticates users using Clerk JWT tokens
 */
class ClerkAuthenticator extends AbstractAuthenticator
{
    /**
     * Authenticate a user using a Clerk JWT token
     */
    public function authenticate(ServerRequestInterface $request): ResultInterface
    {
        // Skip OPTIONS requests
        if ($request->getMethod() === 'OPTIONS') {
            return new Result(null, ResultInterface::FAILURE_OTHER, ['OPTIONS request']);
        }

        try {
            // Verify JWT token using Clerk SDK
            $requestState = AuthenticateRequest::authenticateRequest(
                $request,
                new AuthenticateRequestOptions(
                    secretKey: Configure::read('Clerk.secret_key'),
                    authorizedParties: Configure::read('Clerk.authorized_parties')
                )
            );
            
            // If authenticated, create minimal identity data
            if ($requestState && $requestState->isSignedIn()) {
                $payload = $requestState->getPayload();
                
                // Only include essential user data
                $userData = ['id' => $payload->sub];
                
                return new Result($userData, ResultInterface::SUCCESS);
            }
            
            // Authentication failed
            return new Result(null, ResultInterface::FAILURE_CREDENTIALS_INVALID, 
                ['Invalid credentials']);
                
        } catch (\Exception $e) {
            // Log error if in debug mode
            if (Configure::read('debug')) {
                error_log('ClerkAuthenticator: ' . $e->getMessage());
            }
            
            return new Result(null, ResultInterface::FAILURE_OTHER, 
                ['Authentication error']);
        }
    }
} 