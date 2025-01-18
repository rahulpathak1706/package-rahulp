<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class DBTransaction
{
    /**
     * Begin a database transaction before handling the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     * @throws \Exception If an exception occurs during the transaction
     */
    public function handle($request, Closure $next)
    {
        // Begin a database transaction
        DB::beginTransaction();

        try {
            // Handle the incoming request
            $response = $next($request);
        } catch (\Exception $e) {
            // Roll back the transaction if an exception occurs and rethrow the exception
            DB::rollBack();
            throw $e;
        }

        // Check the HTTP status code of the response
        if ($response->getStatusCode() > 399) {
            // Roll back the transaction if response status code indicates an error
            DB::rollBack();
        } else {
            // Commit the transaction if the response status code indicates success
            DB::commit();
        }

        // Return the response
        return $response;
    }
}
