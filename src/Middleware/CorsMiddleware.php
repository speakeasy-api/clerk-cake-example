<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response;
use Cake\Core\Configure;

class CorsMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $debug = Configure::read('debug');
        
        // For OPTIONS (preflight) requests, return immediately with appropriate headers
        if ($request->getMethod() === 'OPTIONS') {
            // Create a direct response
            $response = new Response();
            
            // Set CORS headers
            $response = $response
                ->withHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-CSRF-Token, Accept')
                ->withHeader('Access-Control-Allow-Credentials', 'true')
                ->withHeader('Access-Control-Max-Age', '3600')
                ->withStatus(200);
            
            if ($debug) {
                error_log('CorsMiddleware: Sending OPTIONS response');
            }
            
            return $response;
        }
        
        // For non-OPTIONS requests, process normally
        $response = $handler->handle($request);
        
        // Add CORS headers to the response
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-CSRF-Token, Accept')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
        
        return $response;
    }
} 