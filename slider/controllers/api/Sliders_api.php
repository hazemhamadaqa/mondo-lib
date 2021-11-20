<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'libraries/RESTController.php';
require_once(APPPATH.'third_party/token.php');
require_once(APPPATH.'third_party/permission.php');
use Restserver\Libraries\RESTController;

class Sliders_api extends RESTController {
    public $language ="";
    public function __construct(){
    parent::__construct();
        $headers = $this->input->request_headers();
        $token = new token();
        try {
            $this->language = $token->check_language($headers);
        }catch (Exception $e) {
            $this->language = "en";
        }
        try {
            $auth_data = $token->check_authorization($headers);
        }catch (Exception $e) {
            $auth_data= null;
        }
        
        $this->load->model('sliders_model');
        $this->sliders_model->init($this->language,$auth_data);
    }

    public function getSliders(){

        try {

            if($this->checkMethod('get')){ 

              $headers = $this->input->request_headers();
              $token = new token();
              $token->check_language($headers);
              $auth_data = $token->check_authorization($headers);
              if ($auth_data != false) {
                $permission = new permission();
              
                if($permission->check_authorization("getSliders",$auth_data)){ 
                  
                  $id = $this->input->get("id");
                  $word = $this->input->get("word");
                  $page_number = $this->input->get("page_number");
                  $limit = $this->input->get("limit");
                  $start=0;
          
                  if ((!$page_number || $page_number == null || $page_number =="") && (!$limit || $limit == null || $limit =="")){    // no pagenate
                      $start=0;
                      $limit=0;
                  }else{
                      $start= (($page_number-1)*$limit);
                  }

                  $store_id = null ; 

                  if(isset($auth_data->store_id) && $auth_data->store_id != ""){
                      $store_id = $auth_data->store_id;
                  }

                  $retval = $this->sliders_model->getSliders($start,$limit,$id,$word,$store_id);
          
                  $this->response($retval, self::HTTP_OK);
                  return;

                }else{
                  throw new Exception($this->lang->line('permission_denied'));
                }

              }
              $response = [
                'status' => false,
                'message' => $this->lang->line('forbidden'),
              ];
              $this->set_response($response, self::HTTP_OK);

            }else{
              throw new Exception('miss match method');
            }
        }catch (Exception $e) {
            $this->response([
                'status' => FALSE,
                'error_message' => $e->getMessage()
            ], self::HTTP_OK);
        }
    }
    public function addSlider(){
        $video = "";
        $image = "";
        /*
        {
            "title":"test",
            "title2":"test",
            "paragraph":"test",
            "link":"test.com",
            "tags":"test,test"
        }

        */
        try {

          if($this->checkMethod('post')){ 

            $headers = $this->input->request_headers();
            $token = new token();
            $token->check_language($headers);
            $auth_data = $token->check_authorization($headers);
            if ($auth_data != false) {
              $permission = new permission();
            
              if($permission->check_authorization("addSlider",$auth_data)){ 
                
                $slider = $this->input->post("slider");
                $store_id = null ; 

                if(isset($auth_data->store_id) && $auth_data->store_id != ""){
                    $store_id = $auth_data->store_id;
                }else{
                    $store_id = $this->input->post("store_id");
                    if(!isset($store_id) || $store_id== ""){
                        throw new Exception($this->lang->line('enter_store_id'));
                    }
                }

                if (!$slider || $slider == null) {
                    throw new Exception("slider information error");
                }
      
                $slider = @json_decode($slider);
      
                if (@count((array)$slider) == 0) {
                    throw new Exception("slider information error");
                }
      
                // if(!isset($slider->password) || $slider->password == ""){
                //   throw new Exception("please enter slider password");
                // }
                $retval = $this->sliders_model->validateAddSlider($slider);

                if($retval  == ""){
                
                  if (!is_dir('upload')) {
                    mkdir('./upload', 0777, TRUE);
                  }
                  if (!is_dir('upload/'.$store_id)) {
                      mkdir('./upload/'.$store_id, 0777, TRUE);
                  }
                  if (!is_dir('upload/'.$store_id.'/images')) {
                      mkdir('./upload/'.$store_id.'/images', 0777, TRUE);
                  }
                  if (!is_dir('upload/'.$store_id.'/videos')) {
                      mkdir('./upload/'.$store_id.'/videos', 0777, TRUE);
                  }

                  $today = date('Y-m-d');
                  $year = date('Y', strtotime($today));
                  $month = date('m', strtotime($today));
                  $day = date('d', strtotime($today));
                  
                  if (!is_dir('upload/'.$store_id.'/images/'.$year)) {
                      mkdir('./upload/'.$store_id.'/images/'.$year, 0777, TRUE);
                  }
                  if (!is_dir('upload/'.$store_id.'/images/'.$year.'/'.$month)) {
                      mkdir('./upload/'.$store_id.'/images/'.$year.'/'.$month, 0777, TRUE);
                  }
                  if (!is_dir('upload/'.$store_id.'/images/'.$year.'/'.$month.'/'.$day)) {
                      mkdir('./upload/'.$store_id.'/images/'.$year.'/'.$month.'/'.$day, 0777, TRUE);
                  }
                  if (!is_dir('upload/'.$store_id.'/videos/'.$year)) {
                      mkdir('./upload/'.$store_id.'/videos/'.$year, 0777, TRUE);
                  }
                  if (!is_dir('upload/'.$store_id.'/videos/'.$year.'/'.$month)) {
                      mkdir('./upload/'.$store_id.'/videos/'.$year.'/'.$month, 0777, TRUE);
                  }
                  if (!is_dir('upload/'.$store_id.'/videos/'.$year.'/'.$month.'/'.$day)) {
                      mkdir('./upload/'.$store_id.'/videos/'.$year.'/'.$month.'/'.$day, 0777, TRUE);
                  }

                  $config['upload_path'] = './upload/'.$store_id.'/images/'.$year.'/'.$month.'/'.$day.'/';
                  $config['allowed_types'] = 'gif|jpg|png|jpeg|ico|mp4|mpeg|mpg|mp4|mpe|qt|mov|avi';
                  $config['encrypt_name'] = true;
                  $config['remove_spaces'] = true;
                  $this->load->library('upload', $config);

                  if (isset($_FILES['image']['name']) && $_FILES['image']['name'] != null) {
                      if (!$this->upload->do_upload('image')) {
                          throw new Exception($this->upload->display_errors());
                      }else{
                          $succ = array('upload_data' => $this->upload->data());
                          $image=$year.'/'.$month.'/'.$day.'/'.$succ['upload_data']['file_name'];
                      }
                  }

                  $config['upload_path'] = './upload/'.$store_id.'/videos/'.$year.'/'.$month.'/'.$day.'/';

                  $this->upload->initialize($config);

                  if (isset($_FILES['video']['name']) && $_FILES['video']['name'] != null) {
                      if (!$this->upload->do_upload('video')) {
                          throw new Exception($this->upload->display_errors());
                      }else{
                          $succ = array('upload_data' => $this->upload->data());
                          $video=$year.'/'.$month.'/'.$day.'/'.$succ['upload_data']['file_name'];
                      }
                  }
                  $retval = $this->sliders_model->addSlider($slider,$image,$video);
        
                  $this->response($retval, self::HTTP_OK);
                  return;
                }else{
                
                  $this->response(array('status'=>false,'error_message'=>$retval), self::HTTP_OK);
                  return;
                }

              
              }else{
                throw new Exception($this->lang->line('permission_denied'));
              }

            }
            $response = [
              'status' => false,
              'message' => $this->lang->line('forbidden'),
            ];
            $this->set_response($response, self::HTTP_OK);

          }else{
            throw new Exception('miss match method');
          }
        }catch (Exception $e) {
            $this->response([
                'status' => FALSE,
                'error_message' => $e->getMessage()
            ], self::HTTP_OK);
        }
    }
    public function updateSlider(){
        $video = "";
        $image = "";
        /*
          {
            "id":1,
            "title":"test",
            "title2":"test",
            "paragraph":"test",
            "link":"test.com",
            "tags":"test,test"
            "is_deleted":1
          }

        */
        try {

          if($this->checkMethod('post')){ 

            $headers = $this->input->request_headers();
            $token = new token();
            $token->check_language($headers);
            $auth_data = $token->check_authorization($headers);
            if ($auth_data != false) {
              $permission = new permission();
            
              if($permission->check_authorization("updateSlider",$auth_data)){ 
                
                $slider = $this->input->post("slider");
                $store_id = null ; 

                if(isset($auth_data->store_id) && $auth_data->store_id != ""){
                    $store_id = $auth_data->store_id;
                }else{
                    $store_id = $this->input->post("store_id");
                    if(!isset($store_id) || $store_id== ""){
                        throw new Exception($this->lang->line('enter_store_id'));
                    }
                }

                if (!$slider || $slider == null) {
                    throw new Exception("slider information error");
                }
      
                $slider = @json_decode($slider);
      
                if (@count((array)$slider) == 0) {
                    throw new Exception("slider information error");
                }
      
                // if(!isset($slider->password) || $slider->password == ""){
                //   throw new Exception("please enter slider password");
                // }
                $retval = $this->sliders_model->validateUpdateSlider($slider);

                if($retval  == ""){
                  if (!is_dir('upload')) {
                    mkdir('./upload', 0777, TRUE);
                  }
                  if (!is_dir('upload/'.$store_id)) {
                      mkdir('./upload/'.$store_id, 0777, TRUE);
                  }
                  if (!is_dir('upload/'.$store_id.'/images')) {
                      mkdir('./upload/'.$store_id.'/images', 0777, TRUE);
                  }
                  if (!is_dir('upload/'.$store_id.'/videos')) {
                      mkdir('./upload/'.$store_id.'/videos', 0777, TRUE);
                  }

                  $today = date('Y-m-d');
                  $year = date('Y', strtotime($today));
                  $month = date('m', strtotime($today));
                  $day = date('d', strtotime($today));
                  
                  if (!is_dir('upload/'.$store_id.'/images/'.$year)) {
                      mkdir('./upload/'.$store_id.'/images/'.$year, 0777, TRUE);
                  }
                  if (!is_dir('upload/'.$store_id.'/images/'.$year.'/'.$month)) {
                      mkdir('./upload/'.$store_id.'/images/'.$year.'/'.$month, 0777, TRUE);
                  }
                  if (!is_dir('upload/'.$store_id.'/images/'.$year.'/'.$month.'/'.$day)) {
                      mkdir('./upload/'.$store_id.'/images/'.$year.'/'.$month.'/'.$day, 0777, TRUE);
                  }
                  if (!is_dir('upload/'.$store_id.'/videos/'.$year)) {
                      mkdir('./upload/'.$store_id.'/videos/'.$year, 0777, TRUE);
                  }
                  if (!is_dir('upload/'.$store_id.'/videos/'.$year.'/'.$month)) {
                      mkdir('./upload/'.$store_id.'/videos/'.$year.'/'.$month, 0777, TRUE);
                  }
                  if (!is_dir('upload/'.$store_id.'/videos/'.$year.'/'.$month.'/'.$day)) {
                      mkdir('./upload/'.$store_id.'/videos/'.$year.'/'.$month.'/'.$day, 0777, TRUE);
                  }

                  $config['upload_path'] = './upload/'.$store_id.'/images/'.$year.'/'.$month.'/'.$day.'/';
                  $config['allowed_types'] = 'gif|jpg|png|jpeg|ico|mp4|mpeg|mpg|mp4|mpe|qt|mov|avi';
                  $config['encrypt_name'] = true;
                  $config['remove_spaces'] = true;
                  $this->load->library('upload', $config);

                  if (isset($_FILES['image']['name']) && $_FILES['image']['name'] != null) {
                      if (!$this->upload->do_upload('image')) {
                          throw new Exception($this->upload->display_errors());
                      }else{
                          $succ = array('upload_data' => $this->upload->data());
                          $image=$year.'/'.$month.'/'.$day.'/'.$succ['upload_data']['file_name'];
                      }
                  }

                  $config['upload_path'] = './upload/'.$store_id.'/videos/'.$year.'/'.$month.'/'.$day.'/';

                  $this->upload->initialize($config);

                  if (isset($_FILES['video']['name']) && $_FILES['video']['name'] != null) {
                      if (!$this->upload->do_upload('video')) {
                          throw new Exception($this->upload->display_errors());
                      }else{
                          $succ = array('upload_data' => $this->upload->data());
                          $video=$year.'/'.$month.'/'.$day.'/'.$succ['upload_data']['file_name'];
                      }
                  }

                  $retval = $this->sliders_model->updateSlider($slider,$image,$video);
        
                  $this->response($retval, self::HTTP_OK);
                  return;
                }else{
                
                  $this->response(array('status'=>false,'error_message'=>$retval), self::HTTP_OK);
                  return;
                }

              
              }else{
                throw new Exception($this->lang->line('permission_denied'));
              }

            }
            $response = [
              'status' => false,
              'message' => $this->lang->line('forbidden'),
            ];
            $this->set_response($response, self::HTTP_OK);

          }else{
            throw new Exception('miss match method');
          }
        }catch (Exception $e) {
            $this->response([
                'status' => FALSE,
                'error_message' => $e->getMessage()
            ], self::HTTP_OK);
        }
    }
}