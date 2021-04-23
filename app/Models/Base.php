<?php

namespace App\Models; 
use Illuminate\Database\Eloquent\Model; 

class Base extends Model 
{
    
    public function urlCall(string $url, string $type = 'GET', string $content_type = 'json', array $body = [], array $header = [])
    {
        $curl = curl_init();
        $body_convert = [];

        if($content_type == 'application/x-www-form-urlencoded'){
            $tmp   = '';
            $count = 0;
            
            foreach ($body as $key => $value) {
                $count++;
                $x = ''; 
                if($count < count($body)){
                    $x = '&'; 
                }

                $tmp .= $key . '=' . $value . $x;
                
            }
            $body_convert = $tmp;
            unset($tmp);
        }

        if($content_type == 'json'){
            $body_convert = json_encode($body);
        }

        if(count($header) > 0){
            foreach ($header as $key => $value) {
                $tmph[] = $key . ':' . $value;
            }
            $header = $tmph;
            unset($tmph);
        }

        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $type,
        CURLOPT_POSTFIELDS => $body_convert,
        CURLOPT_HTTPHEADER => $header,
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;

    }
    
}