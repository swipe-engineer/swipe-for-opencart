<?php
class ControllerExtensionPaymentSwipe extends Controller {
    private $error = array();
    private $swipego;
    private $api_key;
    private $signature_key;
    private $business_id;
    private $environment;
    private $debug;
    private $code= 'payment_swipe';


    public function CheckSettingInfo()
    {
        $sql = "SELECT * FROM `".DB_PREFIX."setting` WHERE `code` = '".$this->db->escape($this->code)."' ";
        $rows  = $this->db->query($sql)->rows;
        $getSetting = array();
        foreach ($rows as $row){
            $getSetting[$row['key']] = $row['value'];
        }
        return $getSetting;
    }
    public function index() {
        $this->load->library('swipe');
        $this->load->language('extension/payment/swipe');
        $this->document->setTitle($this->language->get('heading_title'));
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->editSetting($this->code, $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['app_id'])) {
            $data['error_app_id'] = $this->error['app_id'];
        } else {
            $data['error_app_id'] = '';
        }


        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/swipe', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/payment/swipe', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);



        $data['key_login'] =  $this->url->link('extension/payment/swipe/login', 'user_token=' . $this->session->data['user_token'], true);
        $data['key_logout'] =  $this->url->link('extension/payment/swipe/logout', 'user_token=' . $this->session->data['user_token'], true);
        $data['user_token'] =  $this->session->data['user_token'];

        $data['entry_total']=$this->language->get('entry_total');
        $data['entry_order_status']=$this->language->get('entry_order_status');
        $data['entry_geo_zone']=$this->language->get('entry_geo_zone');
        $data['entry_sort_order']=$this->language->get('entry_sort_order');
        $data['entry_checkout_label']=$this->language->get('entry_checkout_label');
        $data['entry_description']=$this->language->get('entry_description');
        $data['entry_business_selection']=$this->language->get('entry_business_selection');
        $data['entry_environment']=$this->language->get('entry_environment');
        $data['entry_api_access_key']=$this->language->get('entry_api_access_key');
        $data['entry_api_signature_key']=$this->language->get('entry_api_signature_key');
        $data['entry_api_retrieve_key']=$this->language->get('entry_api_retrieve_key');
        $data['entry_api_setwebhook_key']=$this->language->get('entry_api_setwebhook_key');
        $data['entry_accepting_payment']=$this->language->get('entry_accepting_payment');
        $data['entry_login_account']=$this->language->get('entry_login_account');
        $data['entry_login_swipe_account']=$this->language->get('entry_login_swipe_account');
        $data['entry_password']=$this->language->get('entry_password');
        $data['entry_email']=$this->language->get('entry_email');
        $data['entry_stay_signed']=$this->language->get('entry_stay_signed');
        $data['button_sign_in']=$this->language->get('button_sign_in');
        $data['entry_forget_password']=$this->language->get('entry_forget_password');
        $data['entry_environment_production']=$this->language->get('entry_environment_production');
        $data['entry_environment_sandbox']=$this->language->get('entry_environment_sandbox');
        $data['entry_refetch_api_key']=$this->language->get('entry_refetch_api_key');
        $data['entry_save_webhook']=$this->language->get('entry_save_webhook');
        $data['entry_webhook_label']=$this->language->get('entry_webhook_label');
        $data['entry_logout_config']=$this->language->get('entry_logout_config');
        $data['entry_payment_swipe_description']=$this->language->get('entry_payment_swipe_description');
        $data['entry_payment_swipe_title']=$this->language->get('entry_payment_swipe_title');
        $data['entry_payment_checkout_total']=$this->language->get('entry_payment_checkout_total');
        $data['entry_button_submit']=$this->language->get('entry_button_submit');
        $data['entry_button_cancel']=$this->language->get('entry_button_cancel');
        $data['entry_enable_swipe']=$this->language->get('entry_enable_swipe');
        $data['entry_disable_swipe']=$this->language->get('entry_disable_swipe');
        $data['text_opencart_settings']=$this->language->get('text_opencart_settings');
        $data['entry_pending_order_status']=$this->language->get('entry_pending_order_status');
        $data['entry_complete_order_status']=$this->language->get('entry_complete_order_status');
        $data['entry_failed_order_status']=$this->language->get('entry_failed_order_status');
        $data['text_opencart_settings']=$this->language->get('text_opencart_settings');
        $data['text_opencart_settings']=$this->language->get('text_opencart_settings');
        $data['entry_button_logout']=$this->language->get('entry_button_logout');
        $data['text_info']=$this->language->get('text_info');

        $this->load->model('tool/image');
        $data['swipe_favicon']  = $this->model_tool_image->resize('assets/Swipe_Favicon_32px.png', 32, 32);
        $data['error_image']  = $this->model_tool_image->resize('assets/warning.png', 80, 80);
        $data['image_success']  = $this->model_tool_image->resize('assets/success.png', 80, 80);
        if($this->swipego_is_logged_in()){


            $setting_row =$this->CheckSettingInfo();



            $data['business'] = $businesses =  $this->get_businesses();




            $data['swipe_current_business'] =  'Select Current Business';





            /*  $defaults = array(
            'enabled'       => 'no',
            'title'         => __( 'Pay Using', 'swipego-wc' ),
            'description'   => __( 'Pay with Maybank2u, CIMB Clicks, Bank Islam, RHB, Hong Leong Bank, Bank Muamalat, Public Bank, Alliance Bank, Affin Bank, AmBank, Bank Rakyat, UOB, Standard Chartered, Boost, e-Wallet.' ),
            'api_key'       => '',
            'signature_key' => '',
            'environment'   => 'sandbox',
            'business_id'   => '',
        );*/

            if (isset($this->request->post['payment_swipe_status'])) {
                $data['payment_swipe_status'] = $this->request->post['payment_swipe_status'];
            }elseif(!isset($setting_row['payment_swipe_status'])){
                $data['payment_swipe_status'] = 0;
            } else {
                $data['payment_swipe_status'] = $this->config->get('payment_swipe_status');
            }


            if (isset($this->request->post['payment_swipe_business_id'])) {
                $data['payment_swipe_business_id'] = $this->request->post['payment_swipe_business_id'];
            }elseif(!isset($setting_row['payment_swipe_business_id'])){
                $data['payment_swipe_business_id'] = '';
            }else {
                $data['payment_swipe_business_id'] = $this->config->get('payment_swipe_business_id');
            }
            foreach($businesses as $business){
                if($business['id'] == $data['payment_swipe_business_id']){
                    $data['swipe_current_business'] = $business['name'];
                }
            }

            if(isset($data['business'][$data['payment_swipe_business_id']]['integration_id'])){
                $data['integrated_id']=$data['business'][$data['payment_swipe_business_id']]['integration_id'];
            }else{
                $data['integrated_id']=0;
            }

            $data['save_webhook_url']=HTTPS_CATALOG;

            if($data['integrated_id'] && $data['payment_swipe_business_id']) {
                $swipego = new Swipego_API();
                $swipego->set_access_token($this->swipego_get_access_token());
                list( $code, $response ) = $swipego->get_webhooks($data['payment_swipe_business_id'], $data['integrated_id']);
                $webhooks = isset( $response['data']['data'] ) ? $response['data']['data'] : array();
                if ($webhooks) {
                    foreach ($webhooks as $webhook ) {
                        if ( !isset( $webhook['_id'] ) ) {
                            continue;
                        }
                        $data['save_webhook_url']=$webhook['url'];
                    }
                }

            }



            if (isset($this->request->post['payment_swipe_enviroment'])) {
                $data['payment_swipe_environment'] = $this->request->post['payment_swipe_enviroment'];
            } elseif(!isset($setting_row['payment_swipe_environment'])){
                $data['payment_swipe_environment'] = 'sandbox';
            }else {
                $data['payment_swipe_environment'] = $this->config->get('payment_swipe_enviroment');
            }



            if (isset($this->request->post['payment_swipe_api_key'])) {
                $data['payment_swipe_api_key'] = $this->request->post['payment_swipe_api_key'];
            } elseif(!isset($setting_row['payment_swipe_api_key'])){
                $data['payment_swipe_api_key'] = '';
            }else {
                $data['payment_swipe_api_key'] = $this->config->get('payment_swipe_api_key');
            }


            if (isset($this->request->post['payment_swipe_signature_key'])) {
                $data['payment_swipe_signature_key'] = $this->request->post['payment_swipe_signature_key'];
            }  elseif(!isset($setting_row['payment_swipe_signature_key'])){
                $data['payment_swipe_signature_key'] = '';
            }else {
                $data['payment_swipe_signature_key'] = $this->config->get('payment_swipe_signature_key');
            }



            if (isset($this->request->post['payment_swipe_description'])) {
                $data['payment_swipe_description'] = $this->request->post['payment_swipe_description'];
            }  elseif(!isset($setting_row['payment_swipe_description'])){
                $data['payment_swipe_description'] = 'Pay with Maybank2u, CIMB Clicks, Bank Islam, RHB, Hong Leong Bank, Bank Muamalat, Public Bank, Alliance Bank, Affin Bank, AmBank, Bank Rakyat, UOB, Standard Chartered, Boost, e-Wallet.';
            }else {
                $data['payment_swipe_description'] = $this->config->get('payment_swipe_description');
            }


            if (isset($this->request->post['payment_swipe_title'])) {
                $data['payment_swipe_title'] = $this->request->post['payment_swipe_title'];
            }    elseif(!isset($setting_row['payment_swipe_title'])){
                $data['payment_swipe_title'] = $this->config->get('config_name');
            }else {
                $data['payment_swipe_title'] = $this->config->get('payment_swipe_title');
            }

            if (isset($this->request->post['payment_swipe_total'])) {
                $data['payment_swipe_total'] = $this->request->post['payment_swipe_total'];
            }    elseif(!isset($setting_row['payment_swipe_total'])){
                $data['payment_swipe_total'] = 1;
            }else {
                $data['payment_swipe_total'] = $this->config->get('payment_swipe_total');
                $data['payment_swipe_total'] = 1;
            }


            if (isset($this->request->post['payment_swipe_geo_zone_id'])) {
                $data['payment_swipe_geo_zone_id'] = $this->request->post['payment_swipe_geo_zone_id'];
            }    elseif(!isset($setting_row['payment_swipe_geo_zone_id'])){
                $data['payment_swipe_geo_zone_id'] = '';
            }else {
                $data['payment_swipe_geo_zone_id'] = $this->config->get('payment_swipe_geo_zone_id');
            }


            if (isset($this->request->post['payment_swipe_sort_order'])) {
                $data['payment_swipe_geo_zone_id'] = $this->request->post['payment_swipe_sort_order'];
            }    elseif(!isset($setting_row['payment_swipe_sort_order'])){
                $data['payment_swipe_sort_order'] = 1;
            }else {
                $data['payment_swipe_sort_order'] = $this->config->get('payment_swipe_sort_order');
                $data['payment_swipe_sort_order'] = 1;
            }






            $this->load->model('localisation/order_status');

            $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();



            if (isset($this->request->post['payment_swipe_order_status_success'])) {
                $data['payment_swipe_order_status_success'] = $this->request->post['payment_swipe_order_status_success'];
            } else {
                /*$data['payment_swipe_order_status_success'] = $this->config->get('payment_swipe_order_status_success');*/
                $data['payment_swipe_order_status_success'] = 5;
            }


            if (isset($this->request->post['payment_swipe_order_status_pending'])) {
                $data['payment_swipe_order_status_pending'] = $this->request->post['payment_swipe_order_status_pending'];
            } else {
                /*$data['payment_swipe_order_status_pending'] = $this->config->get('payment_swipe_order_status_pending');*/
                $data['payment_swipe_order_status_pending'] = 1;
            }

            if (isset($this->request->post['payment_swipe_order_status_failed'])) {
                $data['payment_swipe_order_status_failed'] = $this->request->post['payment_swipe_order_status_failed'];
            } else {
                /*$data['payment_swipe_order_status_failed'] = $this->config->get('payment_swipe_order_status_failed');*/
                $data['payment_swipe_order_status_failed'] = 7;
            }




            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');

            $this->response->setOutput($this->load->view('extension/payment/swipe_settings', $data));


        }else{

            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');

            $this->response->setOutput($this->load->view('extension/payment/swipe', $data));

        }




    }

    private function validate() {
        if (!$this->user->hasPermission('modify', 'extension/payment/swipe')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['payment_swipe_app_id']) {
            $this->error['app_id'] = $this->language->get('error_app_id');
        }

        if (!$this->request->post['payment_swipe_merchant_private_key']) {
            $this->error['merchant_private_key'] = $this->language->get('error_merchant_private_key');
        }

        if (!$this->request->post['payment_swipe_swipe_public_key']) {
            $this->error['swipe_public_key'] = $this->language->get('error_swipe_public_key');
        }

        return !$this->error;
    }
    public function login() {


        $this->load->library('swipe');

        $json = array();
        if (!$this->user->hasPermission('modify', 'extension/payment/swipe')) {
            $json['warning'] = $this->language->get('error_permission');
        }
        if (!$this->request->post['email']) {
            $json['message'] = $this->language->get('error_email');
        }
        if (!$this->request->post['password']) {
            $json['message'] = $this->language->get('error_password');
        }
        if(!$json){
            try {

                $email = $this->request->post['email'];
                $password = $this->request->post['password'];

                $swipego = new Swipego_API();
                list( $code, $response ) = $swipego->sign_in( array(
                    'email'    => $email,
                    'password' => $password,
                ) );
                $data = isset( $response['data'] ) ? $response['data'] : false;
                $errors = isset( $response['errors'] ) ? $response['errors'] : false;
                if ( $errors ) {
                    foreach ( $errors as $error ) {
                        $json['status'] = 45;
                        $json['message'] = $error[0];
                    }
                }

                if ( isset( $data['token'] ) && !empty( $data['token'] ) ) {
                    $this->update_access_token($data['token']);
                    $json['status'] = 100;
                    $json['message'] = 'Successfully Logged in';
                } else {
                    $json['status'] = 55;
                    $json['message'] = 'An error occured! Please try again.';

                }
            } catch ( Exception $e ) {

                $json['status'] = 400;
                $json['message'] = $e->getMessage();
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function logout() {
        $json = array();

        if(!$json){
            try {



                if ( isset(  $this->session->data['swipe_access_token'] )  ) {
                    $this->session->data['swipe_access_token'] = false;
                    unset($this->session->data['swipe_access_token']);
                    $json['status'] = 100;
                    $json['message'] = 'Successfully Logged Out';
                } else {
                    $json['status'] = 100;
                    $json['message'] = 'Successfully Logged Out';

                }
            } catch ( Exception $e ) {

                $json['status'] = 400;
                $json['message'] = $e->getMessage();
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    public  function  retrieve(){
        $businesses = $this->get_businesses();
        $json = array();
        if (!$this->user->hasPermission('modify', 'extension/payment/swipe')) {
            $json['warning'] = $this->language->get('error_permission');
        }
        if(!$json){
            $json['api_key'] = '';
            $json['signature_key'] = '';
            foreach ($businesses as $business){
                if($business['id'] == $this->config->get('payment_swipe_business_id')){
                    $this->updateSetting($this->code,'payment_swipe_api_key',$business['api_key']);
                    $this->updateSetting($this->code,'payment_swipe_signature_key',$business['signature_key']);
                    $json['api_key'] = $business['api_key'];
                    $json['signature_key'] = $business['signature_key'];

                }
            }

            $json['status'] = true;
        }else{
            $json['status'] = false;
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));


    }
    public function updateBusinessSetting()
    {
        $this->load->library('swipe');

        $json = array();
        if (!$this->user->hasPermission('modify', 'extension/payment/swipe')) {
            $json['warning'] = $this->language->get('error_permission');
        }
        if (!$this->request->post['business_id']) {
            $json['message'] = $this->language->get('business_id');
        }

        if(!$json){
            $this->updateSetting($this->code,'payment_swipe_business_id',$this->request->post['business_id']);
            $json['status'] = true;
        }else{
            $json['status'] = false;
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    public function updateDescription()
    {

        $json = array();
        if (!$this->user->hasPermission('modify', 'extension/payment/swipe')) {
            $json['warning'] = $this->language->get('error_permission');
        }
        if (!$this->request->post['description']) {
            $json['message'] = $this->language->get('description');
        }
        if (!$this->request->post['title']) {
            $json['message'] = $this->language->get('title');
        }

        if(!$json){
            $this->updateSetting($this->code,'payment_swipe_description',$this->request->post['description']);
            $this->updateSetting($this->code,'payment_swipe_title',$this->request->post['title']);
            $this->updateSetting($this->code,'payment_swipe_geo_zone_id',$this->request->post['payment_swipe_geo_zone_id']);
            $this->updateSetting($this->code,'payment_swipe_total',$this->request->post['payment_swipe_total']);
            $this->updateSetting($this->code,'payment_swipe_sort_order',$this->request->post['payment_swipe_sort_order']);
            $this->updateSetting($this->code,'payment_swipe_order_status_success',$this->request->post['payment_swipe_order_status_success']);
            $this->updateSetting($this->code,'payment_swipe_order_status_pending',$this->request->post['payment_swipe_order_status_pending']);
            $this->updateSetting($this->code,'payment_swipe_order_status_failed',$this->request->post['payment_swipe_order_status_failed']);
            $json['status'] = true;

        }else{
            $json['status'] = false;
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    public function updateEnvironmentSetting()
    {

        $json = array();
        if (!$this->user->hasPermission('modify', 'extension/payment/swipe')) {
            $json['warning'] = $this->language->get('error_permission');
        }
        if (!$this->request->post['environment']) {
            $json['message'] = $this->language->get('business_id');
        }

        if(!$json){
            $this->updateSetting($this->code,'payment_swipe_environment',$this->request->post['environment']);
            $json['status'] = true;
        }else{
            $json['status'] = false;
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    public function updateSetting($code,$key,$value)
    {
        $this->editKeySetting($code,$key,$value);
    }
    public function  get_businesses() {

        try {
            $this->load->library('swipe');


            $swipego = new Swipego_API();
            $swipego->set_access_token( $this->swipego_get_access_token() );

            list( $code, $response ) = $swipego->get_approved_businesses();





            $data = isset( $response['data'] ) ? $response['data'] : false;

            $businesses = array();

            if ( is_array( $data ) ) {

                foreach ( $data as $item ) {

                    $business_id = isset( $item['id'] ) ? ( $item['id'] ) : null;

                    if ( !$business_id ) {
                        continue;
                    }

                    $businesses[$business_id] = array(
                        'id'             => $business_id,
                        'name'           => isset( $item['name'] ) ? ( $item['name'] ) : null,
                        'integration_id' => isset( $item['integration']['id'] ) ? ( $item['integration']['id'] ) : null,
                        'api_key'        => isset( $item['integration']['api_key'] ) ? ( $item['integration']['api_key'] ) : null,
                        'signature_key'  => isset( $item['integration']['signature_key'] ) ? ( $item['integration']['signature_key'] ) : null,
                    );
                }
            }





            return $businesses;

        } catch ( Exception $e ) {
            return false;
        }

    }
    public  function  updateSettingStatus(){

        $json = array();
        if (!$this->user->hasPermission('modify', 'extension/payment/swipe')) {
            $json['warning'] = $this->language->get('error_permission');
        }
        $status = 0;
        if(isset($this->request->post['enabled'] ) && $this->request->post['enabled'] == 'yes' ){
            $status = 1;
        }


        if(!$json){
            $this->updateSetting($this->code,'payment_swipe_status',$status);
            $json['status'] = true;
        }else{
            $json['status'] = false;
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));

    }
    public  function  updateWebHook(){


        $json = array();
        if (!$this->user->hasPermission('modify', 'extension/payment/swipe')) {
            $json['warning'] = $this->language->get('error_permission');
        }




        $setting_info =  $this->CheckSettingInfo();

        $integration_id = '';
        $business_id = '';
        $business_id= $setting_info['payment_swipe_business_id'];
        $businesses=  $this->get_businesses();
        foreach ($businesses as $business){
            if($business['id'] == $setting_info['payment_swipe_business_id']){
                $integration_id = $business['integration_id'];
            }
        }



        if(!$json){
            $this->load->library('swipe');
            $swipego = new Swipego_API();
            $swipego->set_access_token( $this->swipego_get_access_token() );
            list( $code, $response ) = $swipego->get_webhooks($business_id, $integration_id);
            $webhooks = isset( $response['data']['data'] ) ? $response['data']['data'] : array();
            if ($webhooks) {
                foreach ($webhooks as $webhook ) {
                    if ( !isset( $webhook['_id'] ) ) {
                        continue;
                    }
                    // Delete existing webhook first
                    $swipego->delete_webhook( $business_id, $integration_id, $webhook['_id'], array( 'enabled' => true ) );
                }
            }

            if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
                $data['catalog'] = HTTPS_CATALOG;
            } else {
                $data['catalog'] = HTTP_CATALOG;
            }


            $params = array(
                'name'    => 'payment.created',
                'url'     =>  $data['catalog'] . 'index.php?route=extension/payment/swipe/webhook',
                'enabled' => true,
            );
            $params = array(
                'name'    => 'payment.created',
                'url'     =>  $this->request->post['webhook_url'],
                'enabled' => true,
            );


            list( $code, $response ) = $swipego->store_webhook( $business_id, $integration_id, $params );
            $errors = isset( $response['errors'] ) ? $response['errors'] : false;

            if ( $errors ) {
                foreach ( $errors as $error ) {
                    throw new Exception( $error[0] );
                }
            }


            $json['status'] = true;
        }else{
            $json['status'] = false;
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));

    }
    function update_access_token( $access_token, $remember = false ) {

        /*$expires = DAY_IN_SECONDS;

        if ( $remember ) {
            $expires = DAY_IN_SECONDS * 7;
        }*/

        $this->session->data['swipe_access_token']= $access_token;

    }
    function swipego_delete_access_token() {
        unset($this->session->data['swipe_access_token']);
    }
    function swipego_is_logged_in() {
        if(isset($this->session->data['swipe_access_token'])){
            return true;
        }else{
            return  false;
        }
    }
    function  swipego_get_access_token(){
        if(isset($this->session->data['swipe_access_token'])){
            return $this->session->data['swipe_access_token'];
        }else{
            return  false;
        }
    }
    public function editSetting($code, $data, $store_id = 0) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $this->db->escape($code) . "'");

        foreach ($data as $key => $value) {
            if (substr($key, 0, strlen($code)) == $code) {
                if (!is_array($value)) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "'");
                } else {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape(json_encode($value, true)) . "', serialized = '1'");
                }
            }
        }
    }
    public function editKeySetting($code, $key,$value, $store_id = 0) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $this->db->escape($code) . "' AND `key` = '" . $this->db->escape($key) . "' ");

        $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET
         store_id = '" . (int)$store_id . "', 
         `code` = '" . $this->db->escape($code) . "',
         `key` = '" . $this->db->escape($key) . "',
          `value` = '" . $this->db->escape($value) . "',
           serialized = '0'");

    }
}