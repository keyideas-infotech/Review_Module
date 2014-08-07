<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Share_your_exp extends Front_Controller {

    private $uploadError;

    public function __construct() {
        parent::__construct();
        $this->lang->load('share_your_exp');
        $this->load->library("form_validation");
        $this->load->model('club_management/club_management_model', null, true);
        $this->load->model('share_your_exp_model', null, true);
        $this->load->helper("form");

        $this->load->model("fashion_standing/fashion_standing_model", "fashion");
        $this->load->model("kind_of_club/kind_of_club_model", "kind_club");
    }

    public function index($id = NULL) {
        if ($this->session->userdata("logged_in") == FALSE) {
            Template::redirect("/");
        }
        Assets::add_module_js("share_your_exp", "share_your_exp.js");

        if ($this->input->post()) {
            if ($insert_id = $this->save_club()) {
                Template::set_message("Your experience successfully shared", "success");
                Template::redirect("share_your_exp");
            }
        }
        if (isset($id)) {
            Template::set('club_id', $id);
        }

        Template::set("fashion_standing", $this->fashion->fahion_standings_for_api($this->config->item("language")));
        Template::set("kind_of_club", $this->kind_club->get_all_kind_of_club());
        Template::render();
    }

    public function save_club() {
        if ($this->session->userdata("logged_in") == FALSE) {
            Template::redirect("/");
        }
        $id = $this->share_your_exp_model->share_your_exp();
        return $id;
    }

    public function auto_suggest_clubs() {
        if ($this->session->userdata("logged_in") == FALSE) {
            Template::redirect("/");
        }
        if ($this->input->is_ajax_request()) {
            $search = $this->input->post("search");
            $name = "name_" . $this->config->item("language");
            $city = "city_" . $this->config->item("language");
            $country = "country_" . $this->config->item("language");

            $data = array();
            if (!empty($search)) {
                $clubs = $this->club_management_model->find_club_for_auto_suggest_by_name($search);
                if ($clubs !== FALSE) {
                    foreach ($clubs as $club) {
                        $data[] = array(
                            "label" => $club->$name . ", " . $club->$city . " (" . $club->$country . ")",
                            "value" => $club->$name,
                            "id" => $club->id
                        );
                    }
                    $this->output
                            ->set_content_type('application/json')
                            ->set_output(json_encode($data));
                }
            }
        } else {
            show_404();
        }
    }

    public function get_club_info() {
        if ($this->session->userdata("logged_in") == FALSE) {
            Template::redirect("/");
        }
//        var_dump($this->input->post());
        if ($this->input->is_ajax_request()) {
            $club_id = $this->input->post("id");
            if (!empty($club_id)) {
                $club_info = $this->club_management_model->get_all_club_info_by_id($club_id);
                if (!empty($club_info)) {
                    $this->output
                            ->set_content_type('application/json')
                            ->set_output(json_encode($club_info));
                }
            }
        } else {
            show_404();
        }
    }
    
    /*
     * Save review added by user
     */
    public function share_review() {
        if ($this->session->userdata("logged_in") == FALSE) {
            Template::redirect("/");
        }
        if ($this->input->is_ajax_request()) {
            $id = $this->share_your_exp_model->share_review();
            $errors = validation_errors();
            if (!empty($errors)) {
                echo $errors;
            } else {
                echo "Experience successfully shared and waiting for approval.";
            }
        } else {
            show_404();
        }
    }
    
    public function my_review($exp_id) {
        $this->load->library('users/auth');

        $this->lang->load('club_management/club_management');
        $this->load->model('share_your_exp/share_your_exp_model', 'exp');
        $club_detail = $this->exp->get_user_exp_by_id($exp_id);

        if (empty($club_detail))
            redirect('/');
        Template::set('club_detail', $club_detail);
        Template::render();
    }

}

?>
