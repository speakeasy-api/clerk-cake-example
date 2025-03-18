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
            
            // Get authorized parties for "azp" claim validation
            $authorizedParties = Configure::read('Clerk.authorized_parties');
            
            // Verify JWT token using Clerk SDK
            $requestState = AuthenticateRequest::authenticateRequest(
                $request,
                new AuthenticateRequestOptions(
                    secretKey: $secretKey,
                    authorizedParties: $authorizedParties
                )
            );
            
            // Check if authentication succeeded AND user is signed in
            if ($requestState && $requestState->isSignedIn()) {
                $payload = $requestState->getPayload();
                
                // Verify "azp" claim matches authorized parties (if present in payload)
                if (isset($payload->azp) && !$this->isAuthorizedParty($payload->azp, $authorizedParties)) {
                    return new Result(
                        null, 
                        ResultInterface::FAILURE_CREDENTIALS_INVALID,
                        ['Invalid authorized party: Token not issued for this application']
                    );
                }
                
                // Only include essential user data
                $userData = ['id' => $payload->sub];
                
                return new Result($userData, ResultInterface::SUCCESS);
            }
            
            // Get detailed error reason if available
            $errorMessage = 'Authentication failed: Invalid credentials';
            if ($requestState && method_exists($requestState, 'getErrorReason') && $requestState->getErrorReason()) {
                $errorMessage = 'Authentication failed: ' . $requestState->getErrorReason();
            }
            
            // Return failure result with detailed error message
            return new Result(
                null, 
                ResultInterface::FAILURE_CREDENTIALS_INVALID,
                [$errorMessage]
            );
                
        } catch (\Exception $e) {
            return new Result(
                null, 
                ResultInterface::FAILURE_OTHER,
                ['Authentication error: ' . $e->getMessage()]
            );
        }
    }
    
    /**
     * Check if the azp claim matches one of the authorized parties
     */
    private function isAuthorizedParty(string $azp, array $authorizedParties): bool
    {
        if (empty($authorizedParties)) {
            return false;
        }
        
        foreach ($authorizedParties as $party) {
            if ($azp === $party) {
                return true;
            }
        }
        
        return false;
    }
} 