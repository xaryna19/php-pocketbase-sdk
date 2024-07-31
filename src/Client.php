<?php

namespace Pb;

class Client
{
    private string $url;
    private bool $validator;

    public function __construct(string $url, bool $validator = false)
    {
        $this->url = $url;
        $this->validator = $validator;
    }

    public function collection(string $collection): Collection
    {
        return new Collection( $this->url , $collection , $this->validator );
    }

    public function settings(): Settings
    {
        return new Settings( $this->url );
    }

    public function authEndSession(bool $logout = true): void
    {
        if ( isset( $_SESSION['_pb_tokenize'] ) ) {
            unset( $_SESSION['_pb_tokenize'] );
        }
    }

    public function authValSession()
    {
        if( isset( $_SESSION['_pb_tokenize']['userid'] ) ){
            $response = $this->doRequest($this->url . "/api/collections/users/records/" . $_SESSION['_pb_tokenize']['userid'] , 'GET');
            $result = json_decode($response, JSON_FORCE_OBJECT);
            $return = ( isset( $result['code'] ) AND $result['code'] == 404 ) ? false : true ;
        }else{
            $return = false;
        }
        return $return ;
    }

    public function authUser(string $email, string $password): void
    {
        // Attempt Authenticate as Admin
        $output = $this->doRequest($this->url . "/api/admins/auth-with-password", 'POST', ['identity' => $email, 'password' => $password]);
        $result = json_decode($output, true);
        if (!empty($result['token'])) {
            $_SESSION['_pb_tokenize']['userid'] = $result['admin']['id'];
            $_SESSION['_pb_tokenize']['token'] = $result['token'];
            $_SESSION['_pb_tokenize']['iss'] = time();
        }else{
            // Attempt Authenticate as Regular User
            $response = $this->doRequest($this->url . "/api/collections/users/auth-with-password", 'POST', ['identity' => $email, 'password' => $password]);
            $result = json_decode($response, JSON_FORCE_OBJECT);
            if (!empty($result['token'])) {
                $_SESSION['_pb_tokenize']['userid'] = $result['record']['id'];
                $_SESSION['_pb_tokenize']['token'] = $result['token'];
                $_SESSION['_pb_tokenize']['iss'] = time() ;
            }
        }
    }

    public function doRequest(string $url, string $method, $bodyParams = []): string
    {
        $ch = curl_init();
        if ( isset( $_SESSION['_pb_tokenize'] ) ) {
            $headers = array(
                'Content-Type:application/json',
                'Authorization: ' . $_SESSION['_pb_tokenize']['token']
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if ($bodyParams) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyParams);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

}
