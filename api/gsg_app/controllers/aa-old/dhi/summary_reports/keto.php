<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH . 'controllers/report_parent.php';
class Keto extends report_parent {
	function __construct(){
//		$this->section_path = 'herd_summary'; //this should match the name of this file (minus ".php".  Also used as base for css and js file names and model directory name
//		$this->primary_model = 'herd_summary_model';
		parent::__construct();
		/* Load the profile.php config file if it exists
		$this->config->load('profiler', false, true);
		if ($this->config->config['enable_profiler']) {
			$this->output->enable_profiler(TRUE);
		} */
	}

	function index($block_in = NULL, $display_format = NULL, $sort_by = NULL, $sort_order = NULL){
		$this->session->keep_all_flashdata();
		redirect(site_url('dhi/summary_reports/keto/keto_summary'));
	}
	
	function keto_summary($block_in = NULL, $display_format = NULL, $sort_by = NULL, $sort_order = NULL){
	 	$this->product_name = 'Summary';
	 	parent::display($block_in, $display_format);
	}
	
	function keto_list($block_in = NULL, $display_format = NULL, $sort_by = NULL, $sort_order = NULL){
	 	$this->product_name = 'Cow List';
	 	parent::display($block_in, $display_format);
	}

	function keto_cows_due($block_in = NULL, $display_format = NULL, $sort_by = NULL, $sort_order = NULL){
	 	$this->product_name = 'Cows Due';
	 	parent::display($block_in, $display_format);
	}
}
