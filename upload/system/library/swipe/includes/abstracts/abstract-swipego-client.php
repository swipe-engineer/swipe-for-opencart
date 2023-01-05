<?php
abstract class Swipego_Client {

    const PRODUCTION_URL = 'https://api.swipego.io/api/';
    const SANDBOX_URL    = 'https://test-api.swipego.io/api/';

    protected $access_token = null;
    protected $api_key = null;
    protected $signature_key = null;
    protected $business_id = null;
    protected $environment = true;
    protected $debug = false;

    public function get_access_token() {
        return $this->access_token;
    }

    public function  DisplayAccessToken(){
        echo  $this->access_token;echo  '<br>';
        echo 'this is the api key'. $this->api_key;echo  '<br>';
        echo 'this is the signature_key'.  $this->signature_key;echo  '<br>';
        echo 'this is the business_id'.   $this->business_id;echo  '<br>';
        echo  'this is the environment'.   $this->environment;echo  '<br>';
    }
    public function get_api_key() {
        return $this->api_key;
    }

    public function get_signature_key() {
        return $this->signature_key;
    }

    public function get_business_id() {
        return $this->business_id;
    }

    public function get_environment() {
        return $this->environment;
    }

    public function get_debug() {
        return $this->debug;
    }

    public function set_access_token( $access_token ) {
        $this->access_token = $access_token;
    }

    public function set_api_key( $api_key ) {
        $this->api_key = $api_key;
    }

    public function set_signature_key( $signature_key ) {
        $this->signature_key = $signature_key;
    }

    public function set_business_id( $business_id ) {
        $this->business_id = $business_id;
    }

    public function set_environment( $environment ) {
        $this->environment = $environment;
    }

    public function set_debug( $debug ) {
        $this->debug = $debug;
    }

    // HTTP request URL
    private function get_url( $route = null ) {

        if ( $this->environment == 'production' ) {
            return self::PRODUCTION_URL . $route;
        } else {
            return self::SANDBOX_URL . $route;
        }

    }

    // HTTP request headers
    private function get_headers() {

        $headers = array(
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        );

        // Internal API use access token while public API use API key
        if ( $this->access_token ) {
            $headers['Authorization'] = 'Bearer ' . $this->access_token;
        } elseif ( $this->api_key ) {
            $headers['Authorization'] = 'Bearer ' . $this->api_key;
        }

        return $headers;

    }

    // HTTP GET request
    protected function get( $route, $params = array() ) {
        return $this->request( $route, $params, 'GET' );
    }

    // HTTP POST request
    protected function post( $route, $params = array() ) {
        return $this->request( $route, $params );
    }

    // HTTP DELETE request
    protected function delete( $route, $params = array() ) {
        return $this->request( $route, $params, 'DELETE' );
    }

    // HTTP request
    protected function request( $route, $params = array(), $method = 'POST' ) {

        $url = $this->get_url( $route );

        $args['headers'] = $this->get_headers();

        $this->log( 'URL: ' . $url );
        $this->log( 'Headers: ' . json_encode( $args['headers'] ) );

        if ( $params ) {
            if ( $method == 'GET' ) {
                $args['query'] = $params;
            } else {
                $args['body'] = json_encode( $params );
            }

            $this->log( 'Body: ' . json_encode( $params ) );
        }

        $args['http_errors'] = false;

        try {

            $client = new \GuzzleHttp\Client();
            $response = $client->request( $method, $url, $args );

        } catch ( Exception $e ) {

            $this->log( 'Response Error: ' . $e->getMessage() );
            throw new Exception( $e->getMessage() );

        }

        $code = $response->getStatusCode();
        $body = json_decode( $response->getBody(), true );

        $this->log( 'Response: ' . json_encode( $body ) );

        return array( $code, $body );

    }

    // Get IPN response data
    public function get_ipn_response() {

        if ( !in_array( $_SERVER['REQUEST_METHOD'], array( 'GET', 'POST' ) ) ) {
            return false;
        }

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            $data = file_get_contents( 'php://input' );
            $data = json_decode( $data, true );
        } else {
            $data = $_REQUEST;
        }

        $data = is_array( $data ) ? array_map( 'sanitize_text_field', $data ) : null;

        if ( !$data ) {
            return false;
        }

        if ( !$response = $this->get_valid_ipn_response( $data ) ) {
            return false;
        }

        return $response;

    }

    // Check for valid IPN response data
    private function get_valid_ipn_response( array $data ) {

        // If request is not POST, we return empty array since Swipe does not return any extra parameter to the redirect URL
        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
            return array();
        }

        $params = $this->get_callback_params();

        // Return false if required parameters is not passed to the URL
        foreach ( $params as $param ) {
            if ( !isset( $data['data'][ $param ] ) ) {
                return false;
            }
        }

        if ( isset( $data['data'] ) ) {
            return $data['data'];
        }

        return false;

    }

    // Get list of parameters that will be passed in callback URL
    private function get_callback_params() {

        return array(
            'attempt_id',
            'payment_id',
            'payment_time',
            'payment_amount',
            'payment_status',
            'payment_link_id',
            'payment_link_reference',
            'payment_link_reference_2',
            'payment_message',
            'payment_currency',
            'hash',
        );

    }

    // Validate IPN response data
    public function validate_ipn_response( $data ) {

        if ( !$this->verify_hash( $data ) ) {
            throw new Exception( 'Hash mismatch.' );
        }

        return true;

    }

    // Verify hash parameter value received from IPN response data
    private function verify_hash( $data ) {

        if ( !$this->signature_key ) {
            throw new Exception( 'Missing API signature key.' );
        }

        if ( !isset( $data['hash'] ) || empty( $data['hash'] ) ) {
            return false;
        }

        $hash = $data['hash'];

        // Exclude hash from the data
        unset( $data['hash'] );

        $encoded_hash = implode( '', array_values( $data ) );
        $generated_hash = hash_hmac( 'SHA256', $encoded_hash, $this->signature_key );

        return $hash == $generated_hash;

    }

    // Debug logging
    protected function log( $message ) {
        return;
    }

}
