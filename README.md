# Clerk Cake Example

This project is an example of how to use the Clerk PHP SDK with CakePHP 5 to authenticate requests using JWT tokens from a Clerk frontend. The authentication is handled by a custom authenticator (`src/Auth/ClerkAuthenticator.php`) and demonstrated in the `src/Controller/ProtectedController.php` controller.

This project is not optimized for production and contains a number of practices that should not be used in a production app (allow all CORS headers, no HTTPS, etc).

## Installation

Install dependencies:

```bash
$ composer update
```

## Configuration

Make sure the CLERK_SECRET_KEY [environment variable](https://clerk.com/docs/deployments/clerk-environment-variables#clerk-publishable-and-secret-keys) is set:

```bash
$ export CLERK_SECRET_KEY=my_secret_key
```

Set `clerk.authorized_parties` in config/app_local.php:
```php
'Clerk' => [
    'secret_key' => env("SECRET_KEY"),
    'authorized_parties' => ['http://localhost:5173'] # default location for clerk react app
]
```

## Running the Server

Start the CakePHP development server:

```bash
$ bin/cake server
```

## How It Works

The application uses the CakePHP Authentication plugin with a custom Clerk JWT authenticator. The authentication flow is:

1. Requests pass through the CORS middleware to handle cross-origin requests
2. The Authentication middleware processes the request using the ClerkAuthenticator
3. ClerkAuthenticator verifies the JWT token using the Clerk SDK
4. If valid, the user identity is set and accessible in the controllers
5. Protected endpoints automatically check for valid authentication

### Authentication Flow Diagram

```mermaid
sequenceDiagram
    autonumber
    participant Client as Client Browser
    participant CORS as CorsMiddleware
    participant Auth as AuthenticationMiddleware
    participant Clerk as ClerkAuthenticator
    participant Cont as ProtectedController
    
    Client->>+CORS: HTTP Request with JWT token
    CORS->>CORS: Set CORS headers
    CORS->>+Auth: Pass request
    
    Auth->>Auth: Create AuthenticationService
    Auth->>+Clerk: authenticate(request)
    
    Clerk->>Clerk: Skip if OPTIONS request
    Clerk->>Clerk: Extract JWT from header
    Clerk->>Clerk: Verify with Clerk SDK
    
    alt JWT Valid
        Clerk-->>-Auth: Return SUCCESS with identity
        Auth->>Auth: Set identity in request
        Auth->>+Cont: Pass authenticated request
        
        Cont->>Cont: Access identity data
        Cont-->>-Client: Return protected data
    else JWT Invalid or Missing
        Clerk-->>-Auth: Return FAILURE result
        Auth-->>-CORS: Authentication error
        CORS-->>Client: Return 401 Unauthorized
    end
```

## Using from a Clerk Frontend

From a Clerk frontend, use the `useSession` hook to retrieve the getToken() function:

```js
const session = useSession();
const getToken = session?.session?.getToken
```

Then, request the CakePHP server:

```js
if (getToken) {
    // get the userId or null if the token is invalid
    let res = await fetch("http://localhost:8765/clerk-jwt", {
        headers: {
            "Authorization": `Bearer ${await getToken()}`
        }
    })
    console.log(await res.json()) // {userId: 'the_user_id_or_null'}

    // get gated data or a 401 Unauthorized if the token is not valid
    res = await fetch("http://localhost:8765/get-gated", {
        headers: {
            "Authorization": `Bearer ${await getToken()}`
        }
    })
    if (res.ok) {
        console.log(await res.json()) // {foo: "bar"}
    } else {
        // token was invalid
    }
}
```

## Key Files

- `src/Auth/ClerkAuthenticator.php` - Custom authenticator that verifies Clerk JWT tokens
- `src/Controller/ProtectedController.php` - Example controller with protected endpoints
- `src/Application.php` - Configures middleware and authentication service
