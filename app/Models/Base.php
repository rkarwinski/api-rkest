<?php

namespace App\Models; 
use Illuminate\Database\Eloquent\Model; 

class Base extends Model 
{
    
    public function urlCall()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://accounts.spotify.com/api/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Basic MGQ1ZDllOGQxYmE3NGJlMjkwYTA2YjdhZTRlMjFiZmM6YTk0MTdlZGQ5MjUwNGI0YWFjNjI0ODAyNDMwMjdiMWM=',
            'Content-Type: application/x-www-form-urlencoded',
            'Cookie: __Host-device_id=AQBjqQpKr_9Y9MDHt2on3dQxtu7yFWjYK04mrZKeD_I6kpeexVQMb_Wn9_84cYdATXjJCzgrSnR7jLyppD0aaWcUR_RMtWUNdeU'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
    
}