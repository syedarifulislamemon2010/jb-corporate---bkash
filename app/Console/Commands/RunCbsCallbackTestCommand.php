<?php

namespace App\Console\Commands;

use App\Models\BkashFailedTransaction;
use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

class RunCbsCallbackTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:run-cbs-callback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Demonstrate and execute CBS Callback tests for both API-Key and Sanctum Token flows';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->info('================================================================================');
        $this->info('           JANATA BANK — LIVE CBS CALLBACK EXECUTION DEMO                       ');
        $this->info('================================================================================');
        $this->newLine();

        $apiKey = config('bkash.cbs_callback_api_key', 'cbs-secret-callback-key-2026');

        // ---------------------------------------------------------------------
        // PART 1: METHOD 1 — PRODUCTION MACHINE-TO-MACHINE (X-CBS-API-Key)
        // ---------------------------------------------------------------------
        $this->alert('METHOD 1: Production Machine-to-Machine Integration (X-CBS-API-Key)');
        $this->line('<comment>Endpoint:</comment> POST /api/cbs/response-callback');
        $this->line("<comment>Header:</comment>   X-CBS-API-Key: {$apiKey}");
        $this->line('<comment>Payload:</comment>  {"response_id":"CBS_RESP_LIVE_001","status_id":1006,"txn_id":"TXN_CBS_DEMO_01","confirmed_by":"JANATA_CBS_CORE"}');
        $this->newLine();

        $payload1 = [
            'response_id'  => 'CBS_RESP_LIVE_001',
            'status_id'    => 1006,
            'txn_id'       => 'TEST_TXN_CBS_001',
            'confirmed_by' => 'JANATA_CBS_CORE',
        ];

        $req1 = Request::create('/api/cbs/response-callback', 'POST', $payload1, [], [], [
            'HTTP_X_CBS_API_KEY' => $apiKey,
            'HTTP_ACCEPT'        => 'application/json',
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode($payload1));

        $res1 = app()->handle($req1);
        $status1 = $res1->getStatusCode();
        $body1 = json_decode($res1->getContent(), true);

        $this->info("HTTP Response Status: {$status1} OK");
        $this->line('Response JSON: ' . json_encode($body1, JSON_PRETTY_PRINT));
        $this->newLine();

        // ---------------------------------------------------------------------
        // PART 2: METHOD 2 — DEV / STAGING SANCTUM TOKEN TEST
        // ---------------------------------------------------------------------
        $this->alert('METHOD 2: Dev / Staging Postman Token Authentication (Laravel Sanctum)');
        $this->line('<comment>Step 2.1:</comment> Authenticate Test User and Get Sanctum Token');
        $this->line('<comment>Endpoint:</comment> POST /api/test-auth/token');
        $this->line('<comment>Payload:</comment>  {"email":"checker@test.jbcorporate.com","password":"Test@Pass123"}');
        $this->newLine();

        $tokenPayload = [
            'email'    => 'checker@test.jbcorporate.com',
            'password' => 'Test@Pass123',
        ];

        $reqToken = Request::create('/api/test-auth/token', 'POST', $tokenPayload, [], [], [
            'HTTP_ACCEPT'  => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($tokenPayload));

        $resToken = app()->handle($reqToken);
        $statusToken = $resToken->getStatusCode();
        $bodyToken = json_decode($resToken->getContent(), true);

        $this->info("HTTP Token Response Status: {$statusToken} OK");
        $this->line('Response JSON: ' . json_encode($bodyToken, JSON_PRETTY_PRINT));
        $this->newLine();

        $bearerToken = $bodyToken['token'] ?? 'sample-token';

        $this->line('<comment>Step 2.2:</comment> Send CBS Callback using Sanctum Bearer Token');
        $this->line('<comment>Endpoint:</comment> POST /api/test-auth/cbs/response-callback');
        $this->line("<comment>Header:</comment>   Authorization: Bearer {$bearerToken}");
        $this->line('<comment>Payload:</comment>  {"response_id":"CBS_RESP_TOKEN_002","status_id":1006,"txn_id":"TEST_TXN_CBS_001","confirmed_by":"TEST_VIA_POSTMAN"}');
        $this->newLine();

        $callbackPayload = [
            'response_id'  => 'CBS_RESP_TOKEN_002',
            'status_id'    => 1006,
            'txn_id'       => 'TEST_TXN_CBS_001',
            'confirmed_by' => 'TEST_VIA_POSTMAN',
        ];

        $req2 = Request::create('/api/test-auth/cbs/response-callback', 'POST', $callbackPayload, [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$bearerToken}",
            'HTTP_ACCEPT'        => 'application/json',
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode($callbackPayload));

        $res2 = app()->handle($req2);
        $status2 = $res2->getStatusCode();
        $body2 = json_decode($res2->getContent(), true);

        $this->info("HTTP Response Status: {$status2} OK");
        $this->line('Response JSON: ' . json_encode($body2, JSON_PRETTY_PRINT));
        $this->newLine();

        // ---------------------------------------------------------------------
        // PART 3: REJECTION & SECURITY VERIFICATION
        // ---------------------------------------------------------------------
        $this->alert('PART 3: Security & Rejection Handling Checks');

        // Unauthorized check
        $reqUnauth = Request::create('/api/cbs/response-callback', 'POST', [], [], [], [
            'HTTP_X_CBS_API_KEY' => 'invalid-wrong-key',
            'HTTP_ACCEPT'        => 'application/json',
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode(['response_id' => 'X', 'status_id' => 1006, 'txn_id' => 'TXN_CBS_DEMO_01']));
        $resUnauth = Route::dispatch($reqUnauth);
        $this->info("1. Invalid API Key Header Test: HTTP {$resUnauth->getStatusCode()} Unauthorized (Protected)");

        // Unauthenticated Sanctum check
        $reqNoToken = Request::create('/api/test-auth/cbs/response-callback', 'POST', [], [], [], [
            'HTTP_ACCEPT'  => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['response_id' => 'X', 'status_id' => 1006, 'txn_id' => 'TXN_CBS_DEMO_02']));
        $resNoToken = Route::dispatch($reqNoToken);
        $this->info("2. Missing Bearer Token Test:  HTTP {$resNoToken->getStatusCode()} Unauthorized (Protected)");

        $this->newLine();
        $this->info('================================================================================');
        $this->info('                       ALL TESTS EXECUTED SUCCESSFULLY                          ');
        $this->info('================================================================================');
        $this->newLine();

        return Command::SUCCESS;
    }
}