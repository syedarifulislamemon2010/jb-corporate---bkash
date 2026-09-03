<?php
/**
 * Created by PhpStorm.
 * User: Kibria
 * Date: 05/04/2022
 * Time: 10:44 PM
 */

namespace App\Helper;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

class SMSGenerateHelper extends Controller
{
    /**
     * Send structured SMS based on predefined bank template types (1 to 17).
     *
     * Types:
     * 1:  User Creation / Temporary Password
     * 2:  Password Reset
     * 3:  Account Update
     * 4:  Password Reset OTP
     * 5:  RTGS Credit Confirmation
     * 6:  EFT Return Reason
     * 7:  RTGS Return Reason
     * 8-13: Debit Card & Green PIN Notifications
     * 14: bKash Stage 1: SFTP File Ingested (Pending Checker)
     * 15: bKash Stage 2: Checked by Checker (Pending Authorizer)
     * 16: bKash Stage 3: 1st Authorized (Pending Final Authorizer)
     * 17: bKash Stage 4: 2nd Authorized (Final Settle Complete)
     */
    public static function generate($mobile, $password = "", $type = 1, $account = "", $bankbic = "", $amount = "", $date = "", $time = "", $reason = "")
    {
        try {
            $client = new Client();
            $authHeader = config('bkash.sms_auth_header') ?: 'Basic YmFjaCZydGdzOjMhYiRjJWgmTSZSc0d0MlNxciop';
            $apiUrl = config('bkash.sms_api_url') ?: 'http://172.17.20.17/JBSmsApi/Send';

            $headers = [
                'Authorization' => $authHeader,
                'Content-Type'  => 'application/json',
            ];

            if ($type == 1) { // User Account Create
                $message = "Dear User, your account has been created in JB Nikash Solution and your temporary password is " . $password;
            } else if ($type == 2) { // Password reset
                $message = "Dear User, your account password has been reset in JB Nikash Solution and your temporary password is " . $password;
            } else if ($type == 3) { // Account Update
                $message = "Dear User, Your account has been updated in JB Nikash Solution.";
            } else if ($type == 4) { // Password Reset OTP
                $message = "Dear User, Your JB Nikash Solution OTP is " . $password . " for password reset. OTP will be timed out after 5 minutes.";
            } else if ($type == 5) { // RTGS Credit Confirmation
                $message = "Dear Sir, Your beneficiary account no. " . $account . " of " . $bankbic . " has been credited with " . $amount . " through RTGS on " . $date . " " . $time . ".";
            } else if ($type == 6) { // EFT Return Reason
                $message = substr("Your EFT to " . $bankbic . " on " . $date . " account " . $account . " BDT " . $amount . " returned for " . $reason, 0, 160);
            } else if ($type == 7) { // RTGS Return Reason
                $message = substr("Your RTGS to " . $bankbic . " on " . $date . " account " . $account . " BDT " . $amount . " returned for " . $reason, 0, 160);
            } else if ($type == 8) {
                $message = "Dear customer, your Debit card is ready. Please collect from your respective branch within 180 days; otherwise, Debit card will be destroyed as per bank policy.";
            } else if ($type == 9) {
                $message = "Dear Customer, your PIN is ready. Please collect from your respective branch within 180 days; otherwise, PIN will be destroyed as per bank policy.";
            } else if ($type == 10) {
                $message = "Dear Customer, as per you request your Card has been closed.";
            } else if ($type == 11) {
                $message = "Dear Sir, please ensure sufficient balance in your account to pay maintenance fee for your debit card.";
            } else if ($type == 12) {
                $message = "Dear Customer, your card has been delivered. Use the following link to activate your card and set your PIN: https://www.jb.com.bd/services/greenPin";
            } else if ($type == 13) {
                $message = "Dear Sir, sufficient balance is not available in your account to deduct card maintenance fee. Please deposit card maintenance fee or your card will be closed.";
            } else if ($type == 14) { // bKash Stage 1: SFTP File Upload Ingested
                $message = "Dear Sir/Madam, File Name: \"" . $account . "\" Total Trn: \"" . $bankbic . "\", Total Amount: \"" . $amount . "\". File is pending for Checker. Please Check this file. Thank you. Best Regards, JANATA BANK";
            } else if ($type == 15) { // bKash Stage 2: Checked by Checker
                $message = "Dear Sir/Madam, File Name: \"" . $account . "\" Total Trn: \"" . $bankbic . "\", Total Amount: \"" . $amount . "\" is checked by \"" . $password . "\" & is pending for further Authorization/Approval. Thank you. JANATA BANK";
            } else if ($type == 16) { // bKash Stage 3: 1st Authorize Approved
                $message = "Dear Sir/Madam, File Name: \"" . $account . "\" Total Trn: \"" . $bankbic . "\", Total Amount: \"" . $amount . "\" is Authorized by \"" . $password . "\" & is pending for further Authorization/Approval or final authorization. Thank you. JANATA BANK";
            } else if ($type == 17) { // bKash Stage 4: 2nd Authorize Approved (Finalized)
                $message = "Dear Sir/Madam, File Name: \"" . $account . "\" Total Trn: \"" . $bankbic . "\", Total Amount: \"" . $amount . "\" is Authorized by \"" . $password . "\" & is finally authorized. Thank you. JANATA BANK";
            } else {
                $message = (string) $password;
            }

            $body = json_encode([
                'Recipient' => (string) $mobile,
                'Message'   => (string) $message,
            ]);

            $request = new Request('POST', $apiUrl, $headers, $body);
            $res = $client->sendAsync($request)->wait();
            $out = json_decode($res->getBody());

            Log::info("SMS Gateway: Dispatched Template [Type {$type}] to {$mobile}");

            return $out;
        } catch (\Exception $ex) {
            static::logSmsError($ex);
            $result1 = (object)[];
            $result1->responseCode = 400;
            $result1->message = $ex->getMessage();
            return $result1;
        }
    }

    /**
     * Send direct message text via Janata Bank SMS API Gateway.
     */
    public static function sendDirectSms(string $mobile, string $message)
    {
        try {
            $client = new Client();
            $authHeader = config('bkash.sms_auth_header') ?: 'Basic YmFjaCZydGdzOjMhYiRjJWgmTSZSc0d0MlNxciop';
            $apiUrl = config('bkash.sms_api_url') ?: 'http://172.17.20.17/JBSmsApi/Send';

            $headers = [
                'Authorization' => $authHeader,
                'Content-Type'  => 'application/json',
            ];

            $body = json_encode([
                'Recipient' => (string) $mobile,
                'Message'   => (string) $message,
            ]);

            $request = new Request('POST', $apiUrl, $headers, $body);
            $res = $client->sendAsync($request)->wait();
            $out = json_decode($res->getBody());

            Log::info("SMS Gateway: Successfully sent SMS to {$mobile}");

            return $out;
        } catch (\Exception $ex) {
            static::logSmsError($ex);
            $result1 = (object)[];
            $result1->responseCode = 400;
            $result1->message = $ex->getMessage();
            return $result1;
        }
    }

    /**
     * Safe logging helper
     */
    private static function logSmsError(\Exception $ex): void
    {
        try {
            Log::error('SMS Gateway Error: ' . $ex->getMessage());
        } catch (\Throwable $t) {
            Log::error("SMS Gateway Error: " . $ex->getMessage());
        }
    }
}
