<?php
class Alert_model extends CI_Model {
	protected $arr_fields;
	protected $arr_pdf_widths;
	protected $arr_sort_fields;
	protected $unsortable_columns;
	protected $tables;
	
	public function __construct()
	{
		parent::__construct();
		//$this->{$this->db_group_name} = $this->load->database($this->db_group_name, TRUE);
	}
	
	public function get_benchmarks($herd_code){
		$ret = $this->db->get_where('[herd_summary].[dbo].[vma_home_benchmarks]', array('herd_code' => $herd_code))->result_array();
		return $ret[0];
	}
}
