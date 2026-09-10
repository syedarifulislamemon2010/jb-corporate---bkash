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

class PasswordGenerateHelper extends Controller
{
    public static function generate()
    {
        $letter = 'ABCDEFGHIJKLMNPQRSTUVWXYZ';
        $number = '123456789';
        $special = '@$%*&';
        $str = '';
        $str .= substr(str_shuffle($letter),0,5);
        $str .= substr(str_shuffle($special),0,1);
        $str .= substr(str_shuffle($number),0,2);
        $str = str_shuffle($str);

        return $str;
    }
}
