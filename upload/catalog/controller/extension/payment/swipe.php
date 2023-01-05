<?php
class ControllerExtensionPaymentSwipe extends Controller {
    public function index() {
        $this->load->language('extension/payment/swipe');
        $this->load->model('checkout/order');
        if(!isset($this->session->data['order_id'])) {
            return false;
        }



        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);





        $payment_issue = $this->process_payment($order_info['order_id'],$order_info);


        $data =array();

        return $this->load->view('extension/payment/swipe', $data);
    }


    public function send()
    {
        $this->load->model('checkout/order');

        if(!isset($this->session->data['order_id'])) {
            return false;
        }

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $payment_issue = $this->process_payment($order_info['order_id'],$order_info);


        $this->response->setOutput(json_encode($payment_issue));

    }

    

    // Process the payment
    protected function process_payment( $order_id,$order_info = array() ) {


        try {
          $returnMessage =  $this->get_payment_link( $order_id );



            if ( $returnMessage['status'] == 100 ) {
                return array(
                    'result'   => 'success',
                    'redirect' => $returnMessage['redirect'],
                );
            }else{


                $this->load->library('swipe');
                $swipego = new Swipego_API();
                $swipego->set_api_key($this->config->get('payment_swipe_api_key'));
                $swipego->set_business_id($this->config->get('payment_swipe_business_id'));
                $swipego->set_signature_key($this->config->get('payment_swipe_signature_key'));
                $swipego->set_environment($this->config->get('payment_swipe_environment'));
                $swipego->set_debug(true);




                $params = array(
                    'email'        => $order_info['email'],
                    'currency'     =>  'MYR',
                 'amount'     =>  $this->currency->convert($order_info['total'], $this->session->data['currency'],'MYR'),
                    'title'        => $this->config->get('payment_swipe_title'),
                    'phone_no'     => preg_replace('/[^0-9]/', '', $order_info['telephone'] ),
                    'description'  => sprintf( 'Payment for Order #%d', $order_id ),
                    'redirect_url' => HTTPS_SERVER . 'index.php?route=extension/payment/swipe/notify',
                    'reference'    => $order_id,
                    'reference_2'  => 'opencart',
                    'send_email'   => true,
                );

                list( $code, $response ) = $swipego->create_payment_link( $params );

                $errors = isset( $response['errors'] ) ? $response['errors'] : false;
                if ( $errors ) {
                    foreach ( $errors as $error ) {
                        throw new Exception( $error[0] );
                    }
                }




            }

            if ( isset( $response['data']['payment_url'] ) ) {
                $this->swipego_wc_logger( 'Payment created for order #' . $order_id );
                return array(
                    'result'   => '100',
                    'redirect' => $response['data']['payment_url'],
                );

            }

        } catch ( Exception $e ) {
            return array(
                'result'   => '99',
                'error' => $e->getMessage(),
            );




        }



    }

    public function  swipego_wc_logger($string){
        $this->log->write($string);
    }
    // Get payment link based on bill ID saved in the WooCommerce
    private function get_payment_link( $transaction_id ) {

        try {
            $this->load->library('swipe');
            $swipego = new Swipego_API();
            $swipego->set_api_key($this->config->get('payment_swipe_api_key'));
            $swipego->set_business_id($this->config->get('payment_swipe_business_id'));
            $swipego->set_signature_key($this->config->get('payment_swipe_signature_key'));
            $swipego->set_environment($this->config->get('payment_swipe_environment'));
            $swipego->set_debug(true);
         /*   $swipego->DisplayAccessToken();

            exit;*/
            list( $code, $response ) = $swipego->get_payment_link( $transaction_id );

            $errors = isset( $response['errors'] ) ? $response['errors'] : false;
            if ( $errors ) {
                foreach ( $errors as $error ) {
                    throw new Exception( $error[0] );
                }
            }
            if ( isset( $response['data']['payment_url'] ) ) {
                return array(
                    'status' => 100,
                    'url'=> $response['data']['payment_url']);
            }

        } catch ( Exception $e ) {
            return   array(
                'status' => 99,
                'message'=> $e->getMessage());

        }

        return false;

    }



    public function get_ipn_response() {


        if ( !in_array( $_SERVER['REQUEST_METHOD'], array( 'GET', 'POST' ) ) ) {
            return false;
        }




        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            $data = file_get_contents( 'php://input' );
            $data = $this->request->get;
        } else {
            $data = $_REQUEST;
        }


        $data = is_array( $data ) ? $data : null;

        if ( !$data ) {
            return false;
        }

        if ( !$response = $this->get_valid_ipn_response( $data ) ) {

            return false;
        }

        return $response;

    }

    private function get_valid_ipn_response( array $data ) {

        // If request is not POST, we return empty array since Swipe does not return any extra parameter to the redirect URL
        if ( $_SERVER['REQUEST_METHOD'] !== 'GET' ) {
            return array();
        }


        $params = $this->get_callback_params();

        // Return false if required parameters is not passed to the URL
        foreach ( $params as $param ) {
            if ( !isset( $data[ $param ] ) ) {

                return false;
            }

        }

        if ( isset( $data ) ) {
            return $data;
        }

        return false;

    }

    private function get_callback_params() {

        return array(
            'attempt_id',/*
            'payment_id',*/
            'payment_time',
            'payment_amount',
            'payment_status',
            'payment_link_id',
            'payment_link_reference',
            'payment_link_reference_2',
            'payment_message',
            'payment_currency'
        );

    }
    public function notify() {


        $response = $this->get_ipn_response();
    
        if ( !$response ) {
            $this->swipego_wc_logger( 'IPN webhook failed' );
            exit;
        }


        if ( $response['payment_link_reference_2'] !== 'opencart' ) {


            return false;
        }




        $order_id = (int) $response['payment_link_reference'];

        $this->load->model('checkout/order');
        $order = $this->model_checkout_order->getOrder($order_id);

        if ( !$order ) {
           $this->swipego_wc_logger( 'Order #' . $order_id . ' not found' );
            return false;
        }



        switch ( $response['payment_status'] ) {
            case 1:

                $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_swipe_order_status_success'), '', false);
                $this->response->redirect($this->url->link('checkout/success'));
                break;

            case 2:
                $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_swipe_order_status_pending'), '', false);
                $this->response->redirect($this->url->link('checkout/success'));
                break;

            default:
                $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_swipe_order_status_failed'), '', false);
                $this->response->redirect($this->url->link('error/payment_failed'));
                break;
        }


    }


    protected function errors($heading_title){


        $url = '';
$data['heading_title'] = $heading_title;

        if (isset($this->request->get['path'])) {
            $url .= '&path=' . $this->request->get['path'];
        }

        if (isset($this->request->get['filter'])) {
            $url .= '&filter=' . $this->request->get['filter'];
        }

        if (isset($this->request->get['manufacturer_id'])) {
            $url .= '&manufacturer_id=' . $this->request->get['manufacturer_id'];
        }

        if (isset($this->request->get['search'])) {
            $url .= '&search=' . $this->request->get['search'];
        }

        if (isset($this->request->get['tag'])) {
            $url .= '&tag=' . $this->request->get['tag'];
        }

        if (isset($this->request->get['description'])) {
            $url .= '&description=' . $this->request->get['description'];
        }

        if (isset($this->request->get['category_id'])) {
            $url .= '&category_id=' . $this->request->get['category_id'];
        }

        if (isset($this->request->get['sub_category'])) {
            $url .= '&sub_category=' . $this->request->get['sub_category'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        if (isset($this->request->get['limit'])) {
            $url .= '&limit=' . $this->request->get['limit'];
        }
        $this->document->setTitle($heading_title);
        $data['continue'] = $this->url->link('common/home');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('error/not_found', $data));
    }
}