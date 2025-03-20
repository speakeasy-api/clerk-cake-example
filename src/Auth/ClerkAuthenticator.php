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
            // Get configuration
            $secretKey = Configure::read('Clerk.secret_key');
            $authorizedParties = Configure::read('Clerk.authorized_parties');
            
            // Verify JWT token using Clerk SDK
            $requestState = AuthenticateRequest::authenticateRequest(
                $request,
                new AuthenticateRequestOptions(
                    secretKey: $secretKey,
                    authorizedParties: $authorizedParties
                )
            );
            
            // Check if requestState was created successfully
            if ($requestState) {
                // Check if user is signed in
                if ($requestState->isSignedIn()) {
                    $payload = $requestState->getPayload();
                    
                    // Only include essential user data
                    $userData = ['id' => $payload->sub];
                    
                    return new Result($userData, ResultInterface::SUCCESS);
                } else {
                    // User is not signed in - get detailed error reason if available
                    $errorMessage = 'Authentication failed: User is not signed in';
                    if (method_exists($requestState, 'getErrorReason') && $requestState->getErrorReason()) {
                        $errorMessage = 'Authentication failed: ' . $requestState->getErrorReason();
                    }
                    
                    return new Result(
                        null, 
                        ResultInterface::FAILURE_IDENTITY_NOT_FOUND,
                        [$errorMessage]
                    );
                }
            }
            
            // Request state could not be created
            return new Result(
                null, 
                ResultInterface::FAILURE_CREDENTIALS_INVALID,
                ['Authentication failed: Invalid or missing JWT token']
            );
                
        } catch (\Exception $e) {
            return new Result(
                null, 
                ResultInterface::FAILURE_OTHER,
                ['Authentication error: ' . $e->getMessage()]
            );
        }
    }
} 