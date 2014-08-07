<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Share_your_exp_model extends BF_Model {

    protected $table = "share_your_exp";
    protected $key = "id";
    protected $soft_deletes = false;
    protected $date_format = "datetime";
    protected $set_created = true;
    protected $set_modified = false;
    protected $created_field = "created_on";
    protected $status_field = "status";

    public function __construct() {
        parent::__construct();
        $config = array(
            "table" => $this->table,
            "status_field" => $this->status_field,
            "select" => array(
                $this->table . ".*",
                "club_management.name_english",
                "club_management.status AS club_status",
                "users.display_name",
            ),
            "order" => array(
                "sortby" => $this->table . "." . $this->created_field,
                "order" => "DESC",
            ),
            "join" => array(
                "club_management" => array(
                    "condition" => "share_your_exp.club_id = club_management.id",
                    "type" => "inner"
                ),
                "users" => array(
                    "condition" => "share_your_exp.user_id = users.id",
                    "type" => "inner"
                )
            )
        );
        $this->load->library("CH_Grid_generator", $config, "grid");
    }

    public function read($req_data) {
        
        $where = array();
        $share_table = $this->db->dbprefix("share_your_exp");
        
        if (isset($req_data['from']) && !empty($req_data['to'])) {
            if (isset($req_data['to']) && !empty($req_data['to'])) {
                $req_data['between'] = array("DATE({$share_table}.created_on)" => array("from" => $req_data['from'], "to" => $req_data['to']));
            }
        }

        if (isset($req_data['username']) && !empty($req_data['username'])) {
            if ($req_data['username'] != 'all') {
                $where["users.id"] = $req_data['username'];
//                $this->grid->where = array("users.id" => $req_data['username']);
            }
        }
        
        if (isset($req_data['club_name']) && !empty($req_data['club_name'])) {
            if ($req_data['club_name'] != 'all') {
                $where["club_management.id"] = $req_data['club_name'];
//                $this->grid->where = array("club_management.id" => $req_data['club_name']);
            }
        }
        
        if(!empty($where)){
            $this->grid->where = $where;
        }

        $this->grid->initialize(array(
            "req_data" => $req_data
        ));

        return $this->grid->get_result();
    }

    public function insert_experience_ratings($data) {
        if (!empty($data)) {
//            return $this->db->insert($this->table, $data);
            if ($this->db->insert($this->table, $data)) {
                return $this->db->insert_id();
            }
        }
        return FALSE;
    }

    public function calc_avg_ratings_and_update($club_id) {

        if (!empty($club_id) && is_numeric($club_id)) {
            $sql = "SELECT count( `user_id` ) AS total_review , avg( `service_rating` ) AS avg_service , avg( `ambiance_rating` ) AS avg_ambiance , 
                           avg( `decoration_rating` ) AS avg_decoration , avg( `price_rating` ) AS avg_price , avg( `percentage_guys_girls` ) AS avg_percentage 
                           FROM `sn_share_your_exp` 
                           WHERE `club_id` = {$club_id} AND `status` = 1 
                           LIMIT 1 ";

            $query = $this->db->query($sql);
            if ($query->num_rows() != 0) {
                $result = array_shift($query->result());
                $avg_array = array(
                    "avg_service" => $result->avg_service,
                    "avg_ambiance" => $result->avg_ambiance,
                    "avg_decoration" => $result->avg_decoration,
                    "avg_price" => $result->avg_price,
                    "avg_percentage" => $result->avg_percentage,
                    "total_reviews" => $result->total_review
                );
                return $this->db->where("id", $club_id)->update("club_management", $avg_array);
            }
        }
        return FALSE;
    }

    public function update_experience_ratings($data, $id) {
        if (!empty($data) && !empty($id)) {
            $this->db->where("id", $id);
            return $this->db->update($this->table, $data);
        }
        return FALSE;
    }

    public function user_rating_for_club($club_id, $user_id) {
        if (!empty($club_id) && !empty($user_id)) {
            $query = $this->db->select("*")->from($this->table)->where(array("club_id" => $club_id, "user_id" => $user_id))->get();
            if ($query->num_rows() != 0) {
                return $query->row();
            }
        }
        return FALSE;
    }

    public function get_club_id_by_exp_id($id) {
        if (!empty($id)) {
            $query = $this->db->select("club_id")->from($this->table)->where("id", $id)->limit(1)->get();
            if ($query->num_rows() != 0) {
                return array_shift($query->result());
            }
//            echo $this->db->last_query();
        }
        return FALSE;
    }

    public function get_club_ids_by_exp_ids($ids) {
        if (!empty($ids)) {
            $query = $this->db->select("club_id")->from($this->table)->where_in('id', $ids)->get();
            if ($query->num_rows() != 0) {
                return $query->result();
            }
        }
        return FALSE;
    }

    public function get_exp_by_id($id) {
        if (!empty($id)) {
            $select = array(
                $this->table . ".*",
                "users.display_name"
            );
            $query = $this->db->select($select)
                    ->from($this->table)
                    ->join("users", "share_your_exp.user_id = users.id", "inner")
                    ->where($this->table . "." . $this->key, $id)
                    ->get();

            if ($query->num_rows() != 0) {
                return array_shift($query->result());
            }
        }
        return FALSE;
    }

    public function change_review_status($data, $id) {
        if (!empty($id) && !empty($data)) {
            return $this->db->where("id", $id)->update($this->table, $data);
        }
        return FALSE;
    }

    public function get_club_reviews($club_id) {
        if (!empty($club_id)) {
            $prefix = $this->db->dbprefix;
            $share_tbl = $prefix.$this->table;
            $user_tbl = $prefix . 'users';
            $role_tbl = $prefix . 'roles';
            $user_meta_tbl = $prefix . 'user_meta';
            $image_path = base_url("assets/uploads/users_images/thumb");
            
            $select = array(
                "{$share_tbl}.*",
                "'{$image_path}' AS image_path",
                "{$user_meta_tbl}.meta_value AS image",
                "{$user_tbl}.username",
                "{$user_tbl}.display_name",                
                "{$role_tbl}.role_id",
                "ROUND((service_rating + ambiance_rating + decoration_rating + price_rating) / 4, 1) AS avg_review"
            );
           
            $q = implode(",", $select);
            $sql  = "SELECT {$q} ";
            $sql .= "FROM {$share_tbl} ";
            $sql .= "INNER JOIN {$user_tbl} ON {$user_tbl}.`id` = {$share_tbl}.`user_id` ";
            $sql .= "INNER JOIN {$role_tbl} ON {$role_tbl}.`role_id` = {$user_tbl}.`role_id` ";
            $sql .= "LEFT JOIN {$user_meta_tbl} ON {$user_tbl}.`id` = {$user_meta_tbl}.`user_id` AND {$user_meta_tbl}.meta_key = 'profile_picture' ";
            $sql .= "WHERE `club_id` = '{$club_id}' AND {$this->status_field} = 1";
            
            $query = $this->db->query($sql);
//            echo $this->db->last_query();
            if ($query->num_rows() != 0) {
                $result = $query->result_array();
                foreach ($result as &$value) {                    
                    if($value['role_id'] == 2 || $value['role_id'] == 1){
                        $value['type'] = 'Admin';
                    }else{
                        $value['type'] = 'User';
                    }
                    unset($value['role_id']);
                }
                return $result;
            }
        }
        return FALSE;
    }

    public function get_club_reviews2($club_id) {
        if (!empty($club_id)) {
            $prefix = $this->db->dbprefix;
            $user_tbl = $prefix . 'users';
            $select = array(
                "{$this->table}.*",
                "{$user_tbl}.username",
                "{$user_tbl}.display_name",
                "ROUND((service_rating + ambiance_rating + decoration_rating + price_rating) / 4, 1) AS avg_review"
            );
            $query = $this->db->select($select)
                    ->from($this->table)
                    ->join($user_tbl, "{$user_tbl}.id = {$this->table}.user_id", "inner")
                    ->where(array("club_id" => $club_id, $this->status_field => 1))
                    ->order_by($this->created_field, "desc")
                    ->get();
            //echo $this->db->last_query();
            $this->load->model("users/user_model");
            if ($query->num_rows() != 0) {
                $result = $query->result_array();
                $pre_result = array();
                foreach ($result as $value) {
                    $user_id = $value['user_id'];
                    $meta = $this->user_model->find_meta_for($user_id, 'profile_picture');
                    if (isset($meta->profile_picture)) {
                        $value['profile_picture'] = $meta->profile_picture;
                    }
                    $pre_result[] = $value;
                }
                return $pre_result;
            }
        }
        return FALSE;
    }

    public function share_review() {
        $return = FALSE;
        $error = FALSE;
        $review = array();

        $review['club_id'] = $this->input->post("club_id");
        $review['user_id'] = $this->input->post("user_id");
        $review['experience'] = $this->input->post("review");
        $review['user_id'] = $this->input->post("user_id");
        $review['service_rating'] = (int) $this->input->post("service_rating");
        $review['ambiance_rating'] = (int) $this->input->post("ambiance_rating");
        $review['decoration_rating'] = (int) $this->input->post("decoration_rating");
        $review['price_rating'] = (int) $this->input->post("price_rating");
        $review['percentage_guys_girls'] = (int) $this->input->post("percentage_guys_girls");
        $review['created_on'] = date("Y-m-d h:m:s");
        $review['status'] = 0;

        if($this->input->post("title")){
            $review['title'] = $this->input->post("title");
        }
        
        //validating post data
        $this->form_validation->set_rules('club_id', 'Club id', 'required|numeric|xss_clean');
        $this->form_validation->set_rules('user_id', 'User id', 'required|numeric|xss_clean');
        $this->form_validation->set_rules('review', 'Review', 'required|trim|strip_tags|xss_clean');

        if ($this->form_validation->run() == FALSE) {
            $error = TRUE;
        }

        if (!empty($review) && is_array($review) && !$error) {
            //take the club_id and user_id
            $club_id = $review['club_id'];
            $user_id = $review['user_id'];

            $rating = $this->user_rating_for_club($club_id, $user_id);

            if ($rating) {
                //has given review for the club before then update user review. so take id.
                $id = $rating->id;

                if (!empty($id) && is_numeric($id)) {
                    if ($this->update_experience_ratings($review, $id)) {
                        $return = $id;
                    }
                }
            } else {
                //review not given for this club please insert review.
                $id = $this->insert_experience_ratings($review);
                if (is_numeric($id)) {
                    $return = $id;
                }
            }
        }
        return $return;
    }

    public function club_reviewed_by_user($user_id, $lang = "english") {
        $return = FALSE;
        $data = array();
        if (!empty($user_id) && is_numeric($user_id)) {
            $this->load->model("club_management/club_management_model", "cm");
            $clubs = $this->select("club_id")->where('user_id', $user_id)->where($this->status_field, 1)->find_all();
            
            
            if ($clubs && is_array($clubs)) {
                foreach ($clubs as $club) {
                    
                    $re = $this->cm->get_info_by_id($club->club_id, "active", $lang);
                    if ($re) {
                        $data[] = $re;
                    }
                    
//                    $data[] = $this->cm->get_info_by_id($club->club_id, "active", $lang);
                }

                if (!empty($data)) {
                    $return = $data;
                }
            }
        }
        return $return;
    }

    public function get_latest_review() {
        $lang = $this->config->item('language');
        $prefix = $this->db->dbprefix;
        $club_tbl = $prefix . 'club_management';
        $club_pic_tbl = $prefix . 'club_pictures';
        $city = "city_" . $lang;
        $country = "country_" . $lang;

        $select = array(
            "{$this->table}.*",
            "{$club_tbl}.name_english",
            "{$club_tbl}.name_french",
            "{$club_tbl}.name_german",
            "{$club_tbl}.{$city} AS city ",
            "{$club_tbl}.{$country} AS country",
//            "GROUP_CONCAT({$club_pic_tbl}.image_name WHERE primary = 1) AS image_name",
            "(SELECT group_concat(image_name separator ', ') FROM {$club_pic_tbl} WHERE {$club_pic_tbl}.club_id = {$club_tbl}.id AND `primary` = '1'  group by {$club_tbl}.id limit 1 ) AS image_name",
            "ROUND((service_rating + ambiance_rating + decoration_rating + price_rating) / 4 , 1) AS avg_review"
        );
        $query = $this->db->select($select)
                ->from($this->table)
                ->where(array($this->table . "." . $this->status_field => 1, $club_tbl . ".status" => 1))
                ->join($club_tbl, "{$club_tbl}.id = {$this->table}.club_id", "inner")
//                ->join($club_pic_tbl, "{$club_pic_tbl}.club_id = {$club_tbl}.id", "inner")
                ->group_by("{$this->table}.id")
                ->order_by($this->table . "." . $this->created_field, "DESC")
                ->limit(5)
                ->get();

//            echo $this->db->last_query();
        if ($query->num_rows() != 0) {
            return $query->result();
        }
        return FALSE;
    }
    
    public function get_latest_reviewed_club() {
        $lang = $this->config->item('language');
        $prefix = $this->db->dbprefix;
        $share_tbl = $prefix.  $this->table;
        $club_tbl = $prefix . 'club_management';
        $club_pic_tbl = $prefix . 'club_pictures';
        $city = "city_" . $lang;
        $country = "country_" . $lang;

        $sql = "select * from ( SELECT DISTINCT `{$share_tbl}`.*, `{$club_tbl}`.`name_english`, `{$club_tbl}`.`name_french`, `{$club_tbl}`.`name_german`, `{$club_tbl}`.`city_english` AS city, `{$club_tbl}`.`country_english` AS country, (SELECT group_concat(image_name separator ', ') FROM {$club_pic_tbl} WHERE {$club_pic_tbl}.club_id = {$club_tbl}.id AND `primary` = '1' group by {$club_tbl}.id limit 1 ) AS image_name, ROUND(({$club_tbl}.avg_service + {$club_tbl}.avg_ambiance + {$club_tbl}.avg_decoration + {$club_tbl}.avg_price) / 4 , 1) AS avg_review FROM (`{$share_tbl}`) INNER JOIN `{$club_tbl}` ON `{$club_tbl}`.`id` = `{$share_tbl}`.`club_id` WHERE `{$share_tbl}`.`status` = 1 AND `{$club_tbl}`.`status` = 1 GROUP BY `{$share_tbl}`.`id` ORDER BY `{$share_tbl}`.`{$this->created_field}` DESC ) as p group by p.club_id order by p.{$this->created_field} desc limit 5 ";
        
//        $select = array(
//            "{$share_tbl}.*",
//            "{$club_tbl}.name_english",
//            "{$club_tbl}.name_french",
//            "{$club_tbl}.name_german",
//            "{$club_tbl}.{$city} AS city ",
//            "{$club_tbl}.{$country} AS country",
////            "GROUP_CONCAT({$club_pic_tbl}.image_name WHERE primary = 1) AS image_name",
//            "(SELECT group_concat(image_name separator ', ') FROM {$club_pic_tbl} WHERE {$club_pic_tbl}.club_id = {$club_tbl}.id AND `primary` = '1'  group by {$club_tbl}.id limit 1 ) AS image_name",
//            "ROUND(({$club_tbl}.avg_service + {$club_tbl}.avg_ambiance + {$club_tbl}.avg_decoration + {$club_tbl}.avg_price) / 4 , 1) AS avg_review"
//        );
//        $query = $this->db->select($select)
//                ->distinct()
//                ->from($this->table)
//                ->where(array($this->table . "." . $this->status_field => 1, $club_tbl . ".status" => 1))
//                ->join($club_tbl, "{$club_tbl}.id = {$this->table}.club_id", "inner")
////                ->join($club_pic_tbl, "{$club_pic_tbl}.club_id = {$club_tbl}.id", "inner")
//                ->order_by($this->table . "." . $this->created_field, "DESC")
//                ->group_by("{$this->table}.id, {$this->table}.club_id")
//                ->limit(5)
//                ->get();
        $query = $this->db->query($sql);
//        echo $this->db->last_query();
        if ($query->num_rows() != 0) {
            return $query->result();
        }
        return FALSE;
    }

    public function share_your_exp() {

        $this->load->model("club_management/club_management_model");
        $this->load->model("share_your_exp/share_your_exp_model");
        $this->load->library('user_agent');

        $return = FALSE;

        $this->form_validation->set_rules('name', 'Club Name', 'required|trim|xss_clean|max_length[255]');
        $this->form_validation->set_rules('experience', 'Experience', 'required|trim|xss_clean');
        $this->form_validation->set_rules('city', 'City', 'required|trim|xss_clean|alpha_extra|max_length[255]');
        $this->form_validation->set_rules('country', 'Country', 'required|trim|alpha_extra|xss_clean|max_length[255]');
        $this->form_validation->set_rules('fashion_standing[]', 'Fashion Standing', 'trim');
        $this->form_validation->set_rules('kind_of_club[]', 'Kind of clubs', 'trim');


        //setting the upload config...
        define("DS", DIRECTORY_SEPARATOR);
        $uploadPath = FCPATH . "assets" . DS . "uploads" . DS . "club_images";
        $config['upload_path'] = $uploadPath;
        $config['allowed_types'] = 'gif|jpg|png';
        $config['max_size'] = '1000';
        $config['no_of_file'] = '12';
//        $config['max_width'] = '1024';
//        $config['max_height'] = '768';

        $this->load->library('upload');
        $this->upload->initialize($config);
//        var_dump($this->upload);
//        die();

        $thumb_path = FCPATH . "assets" . DS . "uploads" . DS . "club_images" . DS . "thumb";
        if (!file_exists($thumb_path)) {
            mkdir($thumb_path, 0777);
        }

        $config['image_library'] = 'gd2';
        $config['create_thumb'] = TRUE;
        $config['new_image'] = $thumb_path;
        $config['maintain_ratio'] = FALSE;
        $config['thumb_marker'] = "";
        $config['width'] = 310;
        $config['height'] = 140;

        $this->load->library('image_lib');



        if (!file_exists($config['upload_path'])) {
            mkdir($config['upload_path'], 0777);
        }

        if ($this->form_validation->run() === FALSE) {
            return $return;
        }


        $fashion_standing = $this->input->post("fashion_standing");

//        echo "fashion standing <pre>";
//        print_r($fashion_standing);
//        echo "</pre>";

        $kind_of_club = $this->input->post("kind_of_club");

//        echo "kind of club <pre>";
//        print_r($kind_of_club);
//        echo "</pre>";

        $club_images = $this->input->post("club_images");
        $images = array();

        if (!empty($club_images) && count($club_images)) {
            if (!empty($club_images['name'][0]) && !empty($club_images['size'][0])) {
                if (!$this->upload->do_multi_upload("club_images")) {
//                    $this->uploadError = $this->upload->display_errors();
                    Template::set_message($this->upload->display_errors());
                    return $return;
                } else {
                    $upload_data = $this->upload->get_multi_upload_data();
                    foreach ($upload_data as $value) {
                        $images[] = $value['file_name'];
                        $config['source_image'] = $value['full_path'];
                        $this->image_lib->initialize($config);
                        $this->image_lib->resize();
                    }
                }
            }
        }


        $review = array();
        $review["title"] = (string) $this->input->post("title");
        $review["experience"] = $this->input->post("experience");
        $review["service_rating"] = (int) $this->input->post("service");
        $review["ambiance_rating"] = (int) $this->input->post("ambiance");
        $review["decoration_rating"] = (int) $this->input->post("decoration");
        $review["price_rating"] = (int) $this->input->post("price");
        $review["percentage_guys_girls"] = (int) $this->input->post("percentage");
        $review["status"] = 0;
        $review["user_id"] = $this->input->post("user_id");
        $review["created_on"] = date("Y-m-d H:i:s");

        if ($this->agent->is_browser()) {
            $review["user_id"] = $this->session->userdata("user_id");
        }
        
        if($this->input->post("lang")){
            $lang = $this->input->post('lang');
        } else {
            $lang = $this->config->item("language");
        }

        
        //find club by name.
        $club = $this->club_management_model->find_club_by_name($this->input->post("name"), $this->input->post("city"), $this->input->post("country"), $lang);


        if ($review["user_id"] != FALSE) {
            if ($club) {
                //club already exists. only insert the reviews in the experience table...

                $id = $club->id;
                $review["club_id"] = $id;

                if (!empty($images) && count($images) > 0) {
                    $this->load->model('club_management/club_picture_model', "club_picture");
                    $this->club_picture->insert_club_picture($images, $id, $review["user_id"]);
                }

                if (!empty($review)) {
                    $rating = $this->share_your_exp_model->user_rating_for_club($id, $review["user_id"]);
                    if ($rating) {
                        //update the user rating
                        if (!empty($rating->id) && is_numeric($rating->id)) {
                            if ($this->share_your_exp_model->update_experience_ratings($review, $rating->id)) {
                                $this->share_your_exp_model->calc_avg_ratings_and_update($id);
                                $return = $id;
                            }
                        }
                    } else {
                        if ($this->share_your_exp_model->insert_experience_ratings($review)) {
                            $return = $id;
                        }
                    }
                }
            } else {
                //club not exist add the club.
                // make sure we only pass in the fields we want
                $data = array();
                $data['name_english'] = $this->input->post('name');
                $data['name_french'] = $this->input->post('name');
                $data['name_german'] = $this->input->post('name');
                $data['description_english'] = "description";
                $data['description_french'] = "description";
                $data['description_german'] = "description";
                $data['city_english'] = $this->input->post('city');
                $data['city_french'] = $this->input->post('city');
                $data['city_german'] = $this->input->post('city');
                $data['country_english'] = $this->input->post('country');
                $data['country_french'] = $this->input->post('country');
                $data['country_german'] = $this->input->post('country');
                $data['status'] = 2;
                $address = $this->input->post('city') . ", " . $this->input->post('country');
                $location = $this->get_lat_lng($address);
                if ($location) {
                    $data['lat'] = $location['lat'];
                    $data['lng'] = $location['lng'];
                }

                $id = $this->club_management_model->insert($data);

                if (is_numeric($id)) {

                    $review["club_id"] = $id;

                    if (!empty($review)) {
                        $this->share_your_exp_model->insert_experience_ratings($review);
                    }

                    $this->share_your_exp_model->calc_avg_ratings_and_update($id);

                    if (!empty($fashion_standing)) {
                        $this->load->model('club_management/club_fashion_standing_model', "club_fashion");
                        $this->club_fashion->insert_club_fashion_standing($fashion_standing, $id);
                    }

                    if (!empty($kind_of_club)) {
                        $this->load->model('club_management/club_kind_of_club_model', "club_kind");
                        $this->club_kind->insert_club_kind_of_club($kind_of_club, $id);
                    }

                    if (!empty($images) && count($images) > 0) {
                        $this->load->model('club_management/club_picture_model', "club_picture");
                        $this->club_picture->insert_club_picture($images, $id, $review["user_id"], 0);
                    }

                    $return = $id;
                }
            }
        }
        return $return;
    }

    public function get_review_count_by_user_id($user_id) {
        if (empty($user_id))
            return FALSE;
        $query1 = $this->db->from($this->table)->where(array("user_id" => $user_id, $this->status_field => 1))->get();
        $query2 = $this->db->from($this->table)->where(array("user_id" => $user_id, $this->status_field => 0))->get();

        $data = array(
            'a' => $query1->num_rows(),
            'u' => $query2->num_rows()
        );
        return $data;
    }

    public function total_review() {
        return $this->count_all();
    }

    public function review_approved() {
        $query = $this->db->select("count(*)")->from($this->table." AS share")->join($this->db->dbprefix("club_management")." AS c", "share.club_id = c.id", "inner")->where('share.status', 1)->where('c.status', 1)->get();
        $count = array_shift(array_shift($query->result_array()));
        return $count;
    }

    public function review_unapproved() {
        return $this->count_by("status", 0);
    }

    public function get_status() {
        $data = array();
        $data['all'] = $this->total_review();
        $data['approved'] = $this->review_approved();
        $data['unapproved'] = $this->review_unapproved();
        return $data;
    }

    public function get_lat_lng($address) {
        $ch = curl_init();
        $address = urlencode($address);
        $url = "http://maps.google.com/maps/api/geocode/json?address={$address}&sensor=false";

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $obj = json_decode(curl_exec($ch));
        if ($obj->status == "OK") {
            $data = array();
            $data['lat'] = $obj->results[0]->geometry->location->lat;
            $data['lng'] = $obj->results[0]->geometry->location->lng;
            $data['address'] = $obj->results[0]->formatted_address;
            return $data;
        }
        return FALSE;
    }

//new methods
    public function reviews_by_me($user_id) {
        $data = array();
        $lang = $this->config->item('language');
        $club_tbl = 'club_management';
        $name = "name_" . $lang;
        $city = "city_" . $lang;
        $country = "country_" . $lang;
        $desc = "description_" . $lang;
        $select = array(
            "cm.id AS club_id",
            "cm.{$name} AS name",
            "cm.{$city} AS city",
            "cm.{$country} AS country",
            "cm.{$desc} AS description",
            "exp.id AS exp_id",
            "exp.service_rating AS service_rating",
            "exp.ambiance_rating AS ambiance_rating",
            "exp.decoration_rating AS decoration_rating",
            "exp.price_rating AS price_rating",
            "exp.experience AS experience",
            "exp.title AS title",
        );
        $this->db->select($select)
                ->from("{$this->table} AS exp ")
                ->join("{$club_tbl} AS cm", "exp.club_id = cm.id", "inner")
                ->where("exp.{$this->status_field}", 1)
                ->where("cm.status", 1)                        
                ->where("exp.user_id", $user_id)
                ->order_by("exp.{$this->created_field}", 'desc');

        $query = $this->db->get();
        if ($query->num_rows() != 0){ 
            $result = $query->result_array();
            $this->load->model('club_management/club_picture_model', 'cp');
            foreach ($result as $value) {
                $club_id = $value['club_id'];
                $image = $this->cp->get_clubs_main_image($club_id);
                $value['image'] = $image;
                $data[] = $value;
            }
        }
        return $data;
    }

    public function get_user_exp_by_id($exp_id, $user_id=NULL) {
        $data = array();
        $lang = $this->config->item('language');
        $club_tbl = 'club_management';
        $name = "name_" . $lang;
        $desc = "description_" . $lang;
        $city = "city_" . $lang;
        $state = "state_" . $lang;
        $country = "country_" . $lang;
        $address1 = "address1_" . $lang;
        $address2 = "address2_" . $lang;

        $select = array(
            "cm.id AS club_id",
            "cm.{$name} AS name",
            "cm.{$desc} AS description",
            "cm.{$city} AS city",
            "cm.{$state} AS state",
            "cm.{$country} AS country",
            "cm.{$address1} AS address1",
            "cm.{$address2} AS address2",
            "cm.zip_code AS zip_code",
            "cm.phone_no AS phone_no",
            "cm.email AS email",
            "cm.web_address AS web_address",
            "cm.total_reviews AS total_reviews",
            "ROUND( (cm.avg_service+cm.avg_ambiance+cm.avg_decoration+cm.avg_price)/4 , 1)  AS total_avg",
            "exp.title AS title",
            "exp.service_rating AS service_rating",
            "exp.ambiance_rating AS ambiance_rating",
            "exp.decoration_rating AS decoration_rating",
            "exp.price_rating AS price_rating",
            "exp.percentage_guys_girls AS percentage_guys_girls",
            "exp.experience AS experience",
            "exp.user_id AS user_id",
        );
        $this->db->select($select)
                ->from("{$this->table} AS exp ")
                ->join("{$club_tbl} AS cm", "exp.club_id = cm.id", "inner")
                ->where("exp.{$this->status_field}", 1)
                ->where("cm.status", 1)
//                ->where("exp.user_id", $user_id)
                ->where("exp.id", $exp_id);

        $query = $this->db->get();
        if ($query->num_rows() != 0) {
            $result = $query->result_array();
            $this->load->model('club_management/club_picture_model', 'cp');
            foreach ($result as $value) {
                $club_id = $value['club_id'];
                $images = $this->cp->get_clubs_images_uploaded_by_user($club_id);
                $value['images'] = $images;
                
                //get user info
                $u_id = $value['user_id'];
                $this->load->model('users/user_model');                
                $value['user_info'] = $this->user_model->find_user_and_meta($u_id);
                
                $data[] = $value;
            }
            $data = array_shift($data);
            
        }
        return $data;
    }

}
