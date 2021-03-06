<?php
//require_once APPPATH . 'models/report_model.php';
class Access_log_model extends CI_Model {
	public function __construct(){
		parent::__construct();
		$this->tables  = $this->config->item('tables', 'ion_auth');
	}
	
	/**
	 * writeEntry
	 *
	 * @param array of access log data
	 * @return in inserted record id
	 * @author ctranel
	 **/
	function writeEntry($data){
        $this->db->insert($this->tables['access_log'], $data);
        return $this->db->insert_id();
	}

	/**
	 * writeProducts
	 *
     * @param 2d array of report data
	 * @return boolean
	 * @author ctranel
	 **/
	function writeProducts($report_data){
		if(!isset($report_data) || !is_array($report_data[0])){
			return;
		}
        foreach($report_data as $rd){
            $this->db->insert('access_log_reports', $rd);
        }
		return true;
	}

	/**
	 * SGAccessInfoByTest
	 *
	 * @method log_by_user_herd_test
	 * @param int user id
	 * @param string herd code
	 * @param array of report code strings
	 * @param string test date (defaults to null)
	 * @return boolean
	 * @author ctranel
	function sgHasAccessedTest($sg_acct_num, $herd_code, $report_code, $test_date = NULL){
		if(isset($test_date)){
			$this->db->where('recent_test_date', $test_date);
		}
		return $this->db
			->select('')
			->join('users.sg.lookup_sg_request_status usg', 'al.user_id = usg.user_id', 'left')
			->where('usg.sg_acct_num', $sg_acct_num)
			->where('al.herd_code', $herd_code)
			->where('al.user_has_accessed_herd', 1)
			->get('users.dbo.v_user_status_info' . ' al')
			->result_array();
	}
**/

	/* -----------------------------------------------------------------
	 * returns the first date that the given user accessed the given report/product
	
	*  returns the first date that the given user accessed the given report/product
	
	*  @since: version 1
	*  @author: ctranel
	*  @date: Jul 7, 2014
	*  @param: int user id
	*  @param: string herd code
	*  @param: string report code
	*  @return date
	*  @throws:
	* -----------------------------------------------------------------
	*/
	public function getInitialAccessDate($user_id, $herd_code, $report_code){
		if(isset($user_id) && !empty($user_id)){
			$this->db->where('user_id', $user_id);
		}
		if(isset($herd_code) && !empty($herd_code)){
			$this->db->where('herd_code', $herd_code);
		}
		if(isset($report_code) && !empty($report_code)){
			if(!is_array($report_code)){
				$report_code = array($report_code);
			}
			$this->db->where_in('report_code', $report_code);
		}
		
		$results = $this->db
			->select('TOP 1 CONVERT(char(10), access_time, 126) as first_access')
			->order_by('access_time', 'asc')
			->get($this->tables['access_log'])
			->result_array();
		if(empty($results)){
			return 0;
		}
		return $results[0]['first_access'];
	}
}
