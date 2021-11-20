<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$obj = &get_instance();
$obj->load->model('Defualt_model');

class Sliders_model extends Defualt_model{
    public $language ;
    public $auth_data = null ;
    public $tabel_name = "slider";
    public $tabel_name_vw = "slider_vw";

    public $normal_selected = array("id","store_id","image","video","link","default_language_code","default_title","default_tags","default_title2","default_paragraph","created_at","updated_at");

    public $json_selected_arr = array();
    public $selected_arr = array();
    function __construct(){
        parent::__construct();
    }  

    public function init( $language,$auth_data ){
        $this->auth_data = $auth_data;
        $this->language = $language;

        if($this->language != "all"){
            $this->json_selected_arr['title']= $this->language;
            $this->json_selected_arr['title2']= $this->language;
            $this->json_selected_arr['paragraph']= $this->language;
            $this->json_selected_arr['tags']= $this->language;
        }else{
            $this->normal_selected[]= 'title';
            $this->normal_selected[]= 'title2';
            $this->normal_selected[]= 'paragraph';
            $this->normal_selected[]= 'tags';
        }

        $this->selected_arr = array("json"=>$this->json_selected_arr,'normal'=>$this->normal_selected);
    }

    public function updateSlider($slider,$image,$video){

        $normal_conditions =array('id'=>$slider->id);
        if(isset($this->auth_data->store_id)){
            $normal_conditions['store_id'] = $this->auth_data->store_id;
        }
        $json_conditions = array();

        $conditions = array("json"=>$json_conditions,'normal'=>$normal_conditions);

        $updated_object = array();

        $normal_updated =array();
        $json_updated = array();
        $json_updated_temp =array();
        if(isset($slider->title)){
            $json_updated_temp ['title'] =$slider->title;
        }
        if(isset($slider->title2)){
            $json_updated_temp ['title2'] =$slider->title2;
        }
        if(isset($slider->paragraph)){
            $json_updated_temp ['paragraph'] =$slider->paragraph;
        }
        if(isset($slider->tags)){
            $json_updated_temp ['tags'] = $slider->tags;
        }
       
        if(isset($slider->link)){
            $normal_updated ['link'] =$slider->link;
        }
        if(isset($slider->is_deleted)){
            $normal_updated ['is_deleted'] =$slider->is_deleted;
        }

        if($image != ""){
            $normal_updated ['image'] =$image;
        }
        if($video != ""){
            $normal_updated ['video'] =$video;
        }

        if(sizeof($json_updated_temp) >0){
            $json_updated[''.$this->language] = $json_updated_temp;
        }

        $updated_object = array("json"=>$json_updated,'normal'=>$normal_updated);

       

        if(sizeof($updated_object)>0){
            $affected_rows=$this->update($this->tabel_name,$conditions,$updated_object);
        }

        if($affected_rows>0 ){
            return array('status'=>true,'slider'=>$this->getSlider($slider->id));
        }else{
            return array('status'=>false, 'errro'=> $this->lang->line('general_error'));
        }

    }

    public function addSlider($slider,$image,$video){

        $normal_inserted =array();
        
        $temp = array("title"=>$slider->title);

        if(isset($slider->tags)){
            $temp ['tags'] =$slider->tags;
        }

        if(isset($slider->title2)){
            $temp ['title2'] =$slider->title2;
        }
        if(isset($slider->paragraph)){
            $temp ['paragraph'] =$slider->paragraph;
        }


        if($image != ""){
            $normal_inserted ['image'] =$image;
        }
        if($video != ""){
            $normal_inserted ['video'] =$video;
        }
        
        if(isset($slider->link)){
            $normal_inserted ['link'] =$slider->link;
        }

        if(isset($this->auth_data->store_id)){
            $normal_inserted['store_id'] = $this->auth_data->store_id;
        }

        $normal_inserted ['default_language_code'] =$this->language;
      

        $json_inserted = array($this->language=>$temp);

        $inserted_object = array("json"=>$json_inserted,'normal'=>$normal_inserted);


        $inserted_id=$this->save($this->tabel_name,$inserted_object);

        if($inserted_id >0){
            return array('status'=>true,'slider'=>$this->getSlider($inserted_id));
        }else{
            return array('status'=>false,'error_message'=>$this->lang->line('general_error'));
        }
    }

    public function getSliders($start,$limit,$id,$word,$store_id){

        $whereGroups  = array();
        $joins  = array();
        $nativeWhere  = null;
        $sumColumn  = null;
        $returnType  = 'many';
        
        $firstWhereFilter = array('is_deleted'=>0);
        if(isset($store_id) && $store_id != ""){
            $firstWhereFilter['store_id'] = $store_id;
        }
        $firstlikeFilter = array();

    
        if(isset($id) && $id != ""){
            $firstWhereFilter['id'] = $id;
        }
     
        if(isset($word) && $word != ""){

            $key2 = "title";
            $nativeWhere = "  (JSON_EXTRACT(LOWER(".$key2."),'$.".$this->language."') LIKE LOWER('%".$word."%')  ) " ;
        }

        $whereGroups []= array("type"=>"group_start",'whereFilter'=>$firstWhereFilter,'likeFilter'=>$firstlikeFilter);

        $orders = array('id'=>'DESC');
        $group_by = array();
        $sliders = $this->search($this->tabel_name_vw,$this->selected_arr,$start,$limit,$orders,$whereGroups,$joins,$nativeWhere,$returnType,$sumColumn ,$group_by);

        $returnType  = 'count';

        

        if($limit ==0){
            $total_page=1;
        }else{

            $count = $this->search($this->tabel_name_vw,"*",0,0,$orders,$whereGroups,$joins,$nativeWhere,$returnType,$sumColumn,$group_by );

            $total_page= ceil($count / $limit);
        }


        return array('status'=>true,'total_page'=>$total_page,'sliders'=>$sliders);
    }

    public function getSlider($id){
        $normal_conditions = array('id'=>$id);

        $json_conditions = array();
        $conditions = array("json"=>$json_conditions,'normal'=>$normal_conditions);

        $usres_db = $this->find($this->tabel_name_vw,$conditions,$this->selected_arr);

        return $usres_db;
    }

    public function validateAddSlider($slider){
        if(!isset($slider->title) || $slider->title == ""){
            return $this->lang->line('enter_slider_title');
        }

        return "";
    }
    
    public function validateUpdateSlider($slider){

        if(!isset($slider->id) || $slider->id == ""){
            return $this->lang->line('enter_slider_id');
        }else{
            $normal_conditions = array('id'=>$slider->id);

            $json_conditions = array();
            $conditions = array("json"=>$json_conditions,'normal'=>$normal_conditions);

            $sliders_db = $this->find('slider',$conditions,array());
            if($sliders_db == null){
                return $this->lang->line('slider_not_found');
            }
        }

    



        return "";
    }

}