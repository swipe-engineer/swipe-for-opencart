<?php

class Swipego_API extends Swipego_Client {

    public function sign_in( array $params ) {

        return $this->post( 'auth/sign-in', $params );
    }

    public function get_approved_businesses() {

        if ( !$this->access_token ) {
            throw new Exception( 'Missing access token.' );
        }

        return $this->get( 'approved-businesses' );

    }

    public function get_webhooks( $business_id, $integration_id ) {

        if ( !$this->access_token ) {
            throw new Exception( 'Missing access token.' );
        }

        return $this->get( 'integrations/businesses/' . $business_id . '/integrations/' . $integration_id . '/webhook-events' );

    }

    public function store_webhook( $business_id, $integration_id, array $params ) {

        if ( !$this->access_token ) {
            throw new Exception( 'Missing access token.' );
        }

        return $this->post( 'integrations/businesses/' . $business_id . '/integrations/' . $integration_id . '/webhook-events', $params );

    }

    public function delete_webhook( $business_id, $integration_id, $event_id, array $params ) {

        if ( !$this->access_token ) {
            throw new Exception( 'Missing access token.' );
        }

        return $this->delete( 'integrations/businesses/' . $business_id . '/integrations/' . $integration_id . '/webhook-events/' . $event_id, $params );

    }

    public function create_payment_link( array $params ) {

        if ( !$this->api_key ) {
            throw new Exception( 'Missing API key.' );
        }

        return $this->post( 'payment-links', $params );

    }

    public function get_payment_link( $payment_id ) {

        if ( !$this->api_key ) {
            throw new Exception( 'Missing API key.' );
        }

        return $this->get( 'payment-links/' . $payment_id );

    }

}
