# Clerk Cake Example

A demonstration of using Clerk JWT authentication with CakePHP 5. This project uses a custom authenticator to verify Clerk JWT tokens from frontend requests.

## Installation

```bash
# Clone the repository
git clone https://github.com/yourusername/clerk-cake-example.git
cd clerk-cake-example

# Install dependencies
composer update
```

## Configuration & Running

1. Set the Clerk Secret Key environment variable:

```bash
export CLERK_SECRET_KEY=my_secret_key
```

2. Add Clerk configuration to `config/app_local.php`:

```php
'Clerk' => [
    'secret_key' => env("SECRET_KEY"),
    'authorized_parties' => ['http://localhost:5173'] # default location for clerk react app
]
```

3. Start the CakePHP development server:

```bash
bin/cake server
```

The server will be available at http://localhost:8765

## Authentication Flow

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
    CORS->>+Auth: Pass request to authentication middleware
    
    Auth->>Auth: Create AuthenticationService
    Auth->>+Clerk: authenticate(request)
    
    Clerk->>Clerk: Skip if OPTIONS request
    Clerk->>Clerk: Extract JWT from Authorization header
    Clerk->>Clerk: Verify JWT using Clerk SDK
    
    alt JWT Valid
        Clerk-->>Auth: Return SUCCESS with identity
        Auth->>Auth: Set identity in request
        Auth->>+Cont: Pass authenticated request
        
        Cont->>Cont: Access identity data
        Cont-->>Client: Return protected data
        Cont-->>Auth: Return response to Auth Middleware
        Auth-->>CORS: Return successful response to CORS Middleware
        CORS-->>Client: Return final response to Client
    else JWT Invalid or Missing
        Clerk-->>Auth: Return FAILURE result
        Auth-->>CORS: Authentication error
        CORS-->>Client: Return 401 Unauthorized
    end
```

## Usage

From a React application:

```javascript
import { useAuth } from '@clerk/clerk-react';

function ApiExample() {
  const { getToken } = useAuth();
  
  const fetchData = async () => {
    if (getToken) {
      // Get the userId or null if the token is invalid
      let res = await fetch("http://localhost:8000/api/clerk-jwt", {
          headers: {
              "Authorization": `Bearer ${await getToken()}`
          }
      });
      console.log(await res.json()); // {userId: 'the_user_id_or_null'}

      // Get gated data or a 401 Unauthorized if the token is not valid
      res = await fetch("http://localhost:8000/api/get-gated", {
          headers: {
              "Authorization": `Bearer ${await getToken()}`
          }
      });
      if (res.ok) {
          console.log(await res.json()); // {foo: "bar"}
      } else {
          // Token was invalid
      }
    }
  };
  
  return <button onClick={fetchData}>Fetch Data</button>;
}
```

## Available API Endpoints

- `GET /clerk-jwt` - Returns user ID from the JWT token
- `GET /get-gated` - Returns protected data, requires authentication

## Key Files

- `src/Auth/ClerkAuthenticator.php` - JWT token verification
- `src/Controller/ProtectedController.php` - Protected endpoints
- `src/Middleware/CorsMiddleware.php` - CORS configuration

## ⚠️ Production Warning

This example is not optimized for production use and contains practices that should not be used in a production environment:

- CORS is configured to allow all headers
- No HTTPS enforcement
- Minimal error handling
- Development server configuration

Before using in production, ensure you implement proper security practices, including:

1. Restrict CORS to specific origins
2. Enforce HTTPS
3. Implement proper error handling and logging
4. Use a production-grade web server
