<?php
/* parent of all report models */

class Report_model extends CI_Model {
	protected $db_group_name;
	public $arr_tables;
	protected $test_date;
	protected $arr_field_table;//DB field name is key
	protected $arr_joins = array(); //DB 2 dimensional: 'table' and 'join_text'  TO BE REPLACED BY USING db_table_id FIELD AND is_fk_field IN DB_FIELD TABLE
	protected $primary_table_name;
 	public $date_field;
 	protected $herd_code;
	protected $arr_fields = array(); //DB list of fields structured to mimic the header, field label is key
	protected $arr_db_field_list = array(); //DB list of fields in flat array, numeric key
	protected $arr_unsortable_columns = array(); //CODE list of fields
	protected $arr_natural_sort_fields = array(); //DB list of fields
	protected $arr_date_fields = array(); //DB list of fields
	protected $arr_datetime_fields = array(); //DB list of fields
	protected $arr_timespan_fields = array(); //DB list of fields
//	protected $arr_notnull_fields = array(); //DB list of fields, used for PCDart file import
	protected $arr_numeric_fields = array(); //DB list of fields
//	protected $arr_zero_is_null_fields = array();
	protected $arr_field_sort = array(); //DB field name is key, default sort order
	protected $arr_pdf_widths = array(); //DB field name is key
	protected $arr_aggregates = array(); //numeric key, must be in same order as $arr_select_fields (array_flatten($this->arr_fields), only located in search function)
	protected $arr_chart_type = array(); //numeric key, must be in same order as $arr_select_fields (array_flatten($this->arr_fields), only located in search function)
	protected $arr_axis_index = array(); //numeric key, must be in same order as $arr_select_fields (array_flatten($this->arr_fields), only located in search function)
	protected $arr_bool_display = array(); //numeric key, must be in same order as $arr_select_fields (array_flatten($this->arr_fields), only located in search function)
	protected $arr_decimal_points = array();//DB field name is key
	public $arr_unit_of_measure;//DB field name is key
	protected $arr_where_field = array();// CODE set in child classes
	protected $arr_where_operator = array();// CODE set in child classes
	protected $arr_where_criteria = array();// CODE set in child classes
	protected $arr_group_by_field = array();// CODE set in child classes
	protected $arr_auto_filter_field = array(); //add a criteria if >1000 records are returned with existing criteria
	protected $arr_auto_filter_operator = array(); //add a criteria if >1000 records are returned with existing criteria
	protected $arr_auto_filter_criteria = array(); //add a criteria if >1000 records are returned with existing criteria
	protected $arr_auto_filter_alert = array();
	protected $num_results;
	public $arr_pstring = array();
	public $arr_blocks = array();
	public $arr_messages = array();
	public $section_id;
	
	public function __construct($section_path = NULL){
		parent::__construct();
		/*in the case of the access log model, the section id is set AFTER the parent (this file/class) is called so that the reference
		 * back to the access log model does not cause problem.  That is the only other model that calls the get block links method.
		 */
		$this->arr_pstring = $this->herd_model->get_pstring_array($this->session->userdata('herd_code'));
		$this->tables  = $this->config->item('tables', 'ion_auth');
		$this->db_group_name = 'reports';
		$this->{$this->db_group_name} = $this->load->database($this->db_group_name, TRUE);
		if(isset($section_path)) $this->section_id = $this->ion_auth_model->get_section_id_by_path($section_path);
		if(isset($this->section_id)) $this->arr_blocks = $this->access_log_model->get_block_links($this->section_id);
		//$this->populate_field_meta_arrays(1); //parameter should be block id
		//$this->adjust_fields($this->session->userdata('herd_code'));
	}
	function get_primary_table_name(){
		return $this->primary_table_name;
	}
	function get_fields(){
		return $this->arr_fields;
	}
	function get_fieldlist_array(){
		return $this->arr_db_field_list;
	}
	function get_pdf_widths(){
		return $this->arr_pdf_widths;
	}
	function get_field_sort(){
		return $this->arr_field_sort;
	}
	function get_field_table(){
		return $this->arr_field_table;
	}
	function get_unsortable_columns(){
		return $this->arr_unsortable_columns;
	}
	function get_chart_type_array(){
		return $this->arr_chart_type;
	}
	function get_axis_index_array(){
		return $this->arr_axis_index;
	}
	function get_num_results(){
		return $this->num_results;
	}
	
	function set_primary_table($table_name){
		$this->primary_table_name = $table_name;
	}
	
	function add_field($arr_field_in){
		$this->arr_fields[] = $arr_field_in;
	}
	function add_sort_field($key, $value = 'ASC'){
		$this->arr_field_sort[$key] = $value;
	}
	
	function add_unsortable_column($column){
		$this->arr_unsortable_columns[] = $column;
	}
	/**
	 * @method get_default_sort()
	 * @param string block url segment
	 * @return returns multi-dimensional array, arr_sort_by and arr_sort_order
	 * @author Chris Tranel
	 **/
	function get_default_sort($block_url_segment){
		$arr_ret = array();
		$arr_res = $this->{$this->db_group_name}
			->select('users.dbo.db_fields.db_field_name, users.dbo.blocks_sort_by.sort_order')
			->where($this->tables['blocks'] . '.url_segment', $block_url_segment)
			->join('users.dbo.blocks_sort_by', $this->tables['blocks'] . '.id = users.dbo.blocks_sort_by.block_id' , 'left')
			->join('users.dbo.db_fields', 'users.dbo.blocks_sort_by.field_id = users.dbo.db_fields.id' , 'left')
			->order_by('users.dbo.blocks_sort_by.list_order', 'asc')
			->get($this->tables['blocks'])
			->result_array();
		if(is_array($arr_res)){
			foreach($arr_res as $s){
				$arr_ret['arr_sort_by'][] = $s['db_field_name'];
				$arr_ret['arr_sort_order'][] = $s['sort_order'];
			}
		}
		return $arr_ret;
	}
	
	/**
	 * @method get_group_by_fields()
	 * @param int id of current block
	 * @return array: ordered list of group by fields
	 * @author Chris Tranel
	 **/
	function get_group_by_fields($block_id){
		$arr_ret = array();
		$arr_res = $this->{$this->db_group_name}
			->select('users.dbo.db_fields.db_field_name')
			->where($this->tables['blocks'] . '.id', $block_id)
			->join('users.dbo.blocks_group_by', $this->tables['blocks'] . '.id = users.dbo.blocks_group_by.block_id' , 'left')
			->join('users.dbo.db_fields', 'users.dbo.blocks_group_by.field_id = users.dbo.db_fields.id' , 'left')
			->order_by('users.dbo.blocks_group_by.list_order', 'asc')
			->get($this->tables['blocks'])
			->result_array();
		if(is_array($arr_res)){
			foreach($arr_res as $s){
				$arr_ret[] = $s['db_field_name'];
			}
		}
		return $arr_ret;
	}
	
	/**
	 * @method get_table_header_data()
	 * @return multi-dimensional array of header data ('arr_unsortable_columns', 'arr_field_sort', 'arr_header_data')
	 * @author Chris Tranel
	 **/
	function get_table_header_data(){
		$this->load->helper('table_header');
		$table_header_data = array(
			'arr_unsortable_columns' => $this->arr_unsortable_columns,
			'arr_field_sort' => $this->arr_field_sort,
			'arr_header_data' => $this->arr_fields,
		);
		$table_header_data['structure'] = get_table_header_array($table_header_data['arr_header_data']); //table header helper function
		return $table_header_data;
	}

	/**
	 * @method populate_field_meta_arrays()
	 * @param int id of current block
	 * @abstract populates report-specific object variable arrays (from DB)
	 * @return void
	 * @author Chris Tranel
	 **/
	public function populate_field_meta_arrays($block_in){
		$arr_numeric_types = array('bigint','decimal','int','money','smallmoney','numeric','smallint','tinyint','float','real');
		$arr_field_child = array();
		$arr_table_ref_cnt = array();

		$this->arr_group_by_field = $this->get_group_by_fields($block_in);
		$arr_field_data = $this->{$this->db_group_name}
			->where('block_id', $block_in)
			->order_by('list_order')
			->get('users.dbo.v_block_field_data')
			->result_array();
		$header_data = $this->get_select_field_structure($block_in);
		if(is_array($arr_field_data) && !empty($arr_field_data)){
			foreach($arr_field_data as $fd){
				$fn = $fd['db_field_name'];
				$this->arr_db_field_list[] = $fn;
				$arr_table_ref_cnt[$fd['table_name']] = isset($arr_table_ref_cnt[$fd['table_name']]) ? ($arr_table_ref_cnt[$fd['table_name']] + 1) : 1;
				$header_data['arr_order'][$fd['name']] = $fd['list_order'];
				$arr_field_child[$fd['block_header_group_id']][$fd['name']] = $fn; //used to create arr_fields nested array
				$this->arr_field_sort[$fn] = $fd['default_sort_order'];
				$this->arr_pdf_widths[$fn] = $fd['pdf_width'];
				$this->arr_aggregates[] = $fd['aggregate'];
				$this->arr_decimal_points[$fn] = $fd['decimal_points'];
				$this->arr_field_table[$fn] = $fd['table_name'];
				if(strpos($fd['data_type'], 'date') !== FALSE && strpos($fn, 'time') !== FALSE) $this->arr_datetime_fields[] = $fn;
				elseif(strpos($fd['data_type'], 'date') !== FALSE) $this->arr_date_fields[] = $fn;
				if($fd['is_nullable'] === FALSE) $arr_notnull_fields[] = $fn;
				if(in_array($fd['data_type'], $arr_numeric_types)) $this->arr_numeric_fields[] = $fn;
				if($fd['is_natural_sort']) $this->arr_natural_sort_fields[] = $fn;
			}
		}
		$this->primary_table_name = array_search(max($arr_table_ref_cnt), $arr_table_ref_cnt);
		//set up arr_fields hierarchy
		$this->arr_fields = $header_data['arr_fields'];
		if(is_array($arr_field_child) && !empty($arr_field_child)){
			foreach($arr_field_child as $k=>$fc){
				// individually insert each field that does not have a parent
				if(empty($k)){
					foreach($fc as $k1=>$fc1){
						$tmp = isset($header_data['arr_ref'][$k1]) ? $header_data['arr_ref'][$k1] : NULL;
//echo $tmp . ': ' . $k1 . ' -> ' . $fc1 . "\n";
						set_element_by_key($this->arr_fields, $tmp, array($k1 => $fc1), $header_data['arr_order']);
					} 
				}
				else set_element_by_key($this->arr_fields, $header_data['arr_ref'][$k], $fc, $header_data['arr_order']);
			}
		}
//var_dump($this->arr_fields);
		if(is_array($arr_table_ref_cnt) && count($arr_table_ref_cnt) >  1){
			foreach($arr_table_ref_cnt as $t => $cnt){
				if($t != $this->primary_table_name){
					$this->arr_joins[] = array('table'=>$t, 'join_text'=>$this->get_join_text($this->primary_table_name, $t));
				}
			}
		}
		$this->adjust_fields($this->session->userdata('herd_code'));
	}
	
	/**
	 * @method get_select_field_structure()
	 * @param int id of current block
	 * @abstract returns block (i.e., table) header structure which provides a skeleton for the organization of fields in the arr_fields object variable
	 * 				also 
	 * @return array: ref = lookup array for ids, arr_fields = skeleton structure for db_fields
	 * @author Chris Tranel
	 **/
	protected function get_select_field_structure($block_in){
		$arr_fields = array();
		$arr_ref = array();
		$arr_order = array();
		
		$arr_groupings = $this->{$this->db_group_name}
			->query("WITH cteAnchor AS (
					 SELECT bh.id, bh.[text], bh.parent_id, bh.list_order
					 FROM users.dbo.block_header_groups bh
					 	LEFT JOIN users.dbo.blocks_select_fields bs ON bh.id = bs.block_header_group_id
					 WHERE block_id = " . $block_in . "
				), cteRecursive AS (
					SELECT id, [text], parent_id, list_order
					  FROM cteAnchor
					 UNION all 
					 SELECT t.id, t.[text], t.parent_id, t.list_order
					 FROM users.dbo.block_header_groups t
					 join cteRecursive r ON r.parent_id = t.id
				)
				SELECT DISTINCT * FROM cteRecursive ORDER BY parent_id, list_order;" //
			)
			->result_array();
			
		if(!is_array($arr_groupings) || empty($arr_groupings)){
			$arr_groupings = $this->{$this->db_group_name}
				->query("SELECT 1 AS id, bf.header_text AS text, NULL AS parent_id, bf.list_order
				FROM users.dbo.blocks_select_fields bf
					LEFT JOIN users.dbo.db_fields f ON bf.field_id = f.id
				WHERE bf.block_id = " . $block_in
			)->result_array();
		}
			
		if(is_array($arr_groupings) && !empty($arr_groupings)){
			foreach($arr_groupings as $h){
				$arr_ref[$h['id']] = $h['text'];
				$arr_order[$h['text']] = $h['list_order'];
			}
			foreach($arr_groupings as $h){
				if($h['parent_id'] == NULL) {
					$arr_fields[$h['text']] = '';
				}
				else{
					set_element_by_key($arr_fields, $arr_ref[$h['parent_id']], array($h['text'] => ''));
				}
			}
		}
		
		return array('arr_ref' => $arr_ref, 'arr_fields' => $arr_fields, 'arr_order' => $arr_order);
	}

	/**
	 * @method adjust_fields()
	 * @param string herd code
	 * @abstract for now, this function removes the pstring column from the arr_fields object variable if the herd does not have pstrings, it could be used for other purposes as well.
	 * @return void
	 * @author Chris Tranel
	 **/
	protected function adjust_fields($herd_code){
		//remove pstring column if the herd does not have pstrings
		$this->arr_pstring = $this->herd_model->get_pstring_array($herd_code);
		if (empty($this->arr_pstring) || count($this->arr_pstring) == 1) {
			//if (($key = array_search('pstring', $this->arr_fields)) !== false) {
				//unset($arr_select_fields[$key]);
				$this->load->helper('multid_array_helper');
				$this->arr_fields = multid_remove_element($this->arr_fields, 'PString');
			//}
		}
	}

	protected function get_join_text($primary_table, $join_table){
		$join_text = '';
		list($a, $b, $tmp_tbl_only) = explode('.', $primary_table);
		$arr_primary_table_fields = $this->{$this->db_group_name}
			->select('db_field_name')
			->from('users.dbo.db_fields')
			->join('users.dbo.db_tables', 'users.dbo.db_fields.db_table_id = users.dbo.db_tables.id')
			->where(array('users.dbo.db_fields.is_fk_field'=>1, 'users.dbo.db_tables.name'=>$tmp_tbl_only))
			->get()
			->result_array();
		list($a, $b, $tmp_tbl_only) = explode('.', $join_table);
		$arr_join_table_fields = $this->{$this->db_group_name}
			->select('db_field_name')
			->from('users.dbo.db_fields')
			->join('users.dbo.db_tables', 'users.dbo.db_fields.db_table_id = users.dbo.db_tables.id')
			->where(array('users.dbo.db_fields.is_fk_field'=>1, 'users.dbo.db_tables.name'=>$tmp_tbl_only))
			->get()
			->result_array();
		if(is_array($arr_primary_table_fields) && is_array($arr_join_table_fields)){
			$arr_intersect = array_intersect(array_flatten($arr_primary_table_fields), array_flatten($arr_join_table_fields));
			foreach($arr_intersect as $j){
				if(!empty($join_text)) $join_text .= ' AND ';
				$join_text .= $primary_table . '.' . $j . '=' . $join_table . '.' . $j;
			}
			return $join_text;
		}
		else return FALSE;
	}
	
	/**
	 * @method search()
	 * @param string herd code
	 * @param array filter criteria
	 * @param array sort by
	 * @param array sort order
	 * @return array results of search
	 * @author Chris Tranel
	 **/
	function search($herd_code, $arr_filter_criteria, $arr_sort_by = array('test_date'), $arr_sort_order = array('ASC'), $limit = NULL) {
		$this->load->helper('multid_array_helper');
		$this->herd_code = $herd_code;
		$this->{$this->db_group_name}->start_cache();
		$this->{$this->db_group_name}->from($this->primary_table_name);
//			->where('herd_code',$herd_code);
		if(is_array($this->arr_joins) && !empty($this->arr_joins)) {
			foreach($this->arr_joins as $j){
				$this->{$this->db_group_name}->join($j['table'], $j['join_text']);
			}
		}		
		if(is_array($arr_filter_criteria) && !empty($arr_filter_criteria)) $this->prep_where_criteria($arr_filter_criteria);
		if(is_array($this->arr_fields)){
			$arr_select_fields = array_flatten($this->arr_fields);
//var_dump($this->arr_fields);
			$arr_select_fields = $this->prep_select_fields($arr_select_fields);
			// resolve field name/data/format exceptions (see animal_model prep_select_fields function override)
			//process zero is null
			//if(isset($this->arr_zero_is_null_fields) && !empty($this->arr_zero_is_null_fields)){
			//	foreach($this->arr_zero_is_null_fields as $df){
			//		if (($key = array_search($df, $arr_select_fields)) !== false) $arr_select_fields[$key] = "IF(" . $df . " = 0, '', " . $df . ") AS " . $df . "";
			//	}
			//}
			//convert dates
			if(isset($this->arr_date_fields) && !empty($this->arr_date_fields)){
				foreach($this->arr_date_fields as $d){
					if (($key = array_search($d, $arr_select_fields)) !== false) $arr_select_fields[$key] = "FORMAT(" . $d . ",'MM-dd-yyyy', 'en-US') AS '" . $d . "'";
				}
			}
			//convert times
			if(isset($this->arr_datetime_fields) && !empty($this->arr_datetime_fields)){
				foreach($this->arr_datetime_fields as $d){
					if (($key = array_search($d, $arr_select_fields)) !== false) $arr_select_fields[$key] = "FORMAT(" . $d . ",'MM-dd-yyyy, hh:mm', 'en-US') AS '" . $d . "'";
				}
			}
		}

		//set variable to be used in the query - if select fields ar specified, keep them in an array so that 
		$select_fields = is_array($arr_select_fields) && !empty($arr_select_fields) ? $arr_select_fields:'*';
		
		
		/*now that the where clauses are set, let's see how many rows would be returned with that criteria.
		 *If over 1000 and a filter has not yet been set for quartiles, add the 1st quartile as a filter.
		 *Then we can add the select and sort data to the query.
		 **/
		$this->{$this->db_group_name}->stop_cache();
		if(isset($limit) === FALSE){
			$this->{$this->db_group_name}->select('COUNT(*) AS c');
			$count_result = $this->{$this->db_group_name}->get()->result_array();
			$this->num_results = $count_result[0]['c'];
			
			if($this->num_results > 1000) {// && empty($arr_filter_criteria[$this->arr_auto_filter_field[0]])) {
				$this->_set_autofilter($arr_filter_criteria);
			}
		}
		else $this->{$this->db_group_name}->limit($limit);
//$this->{$this->db_group_name}->where('x', 'z');
		
		// Group By
		$this->prep_group_by(); // the prep_group_by function adds group by field to the active record object

		// Sort
		$this->prep_sort($arr_sort_by, $arr_sort_order); // the prep_sort function adds the sort field to the active record object

		//add select fields to query
//$select_fields[] = 'd'; //uncomment to dump search query to screen
		$this->{$this->db_group_name}->select($select_fields, FALSE);
		$ret = $this->{$this->db_group_name}->get()->result_array();
		$this->num_results = count($ret);
		$this->{$this->db_group_name}->flush_cache();
		//$ret['arr_unsortable_columns'] = $this->arr_unsortable_columns;
		return $ret;
	}
	
	/**
	 * @method prep_select_fields()
	 * @param arr_fields: copy of fields array to be formatted into SQL
	 * @return array of sql-prepped select fields
	 * @author Chris Tranel
	 **/
	protected function prep_select_fields($arr_select_fields){
		if (($key = array_search('test_date', $arr_select_fields)) !== FALSE) {
			$arr_select_fields[$key] = "FORMAT(" . $this->primary_table_name . ".test_date, 'MM-dd-yy', 'en-US') AS test_date";//MMM-dd-yy
		}
		if (($key = array_search('fresh_month', $arr_select_fields)) !== FALSE) {
			$arr_select_fields[$key] = "FORMAT(" . $this->primary_table_name . ".fresh_month, 'MM-dd-yy', 'en-US') AS fresh_month";//MMM-dd-yy
		}
		foreach($arr_select_fields as $k => $v){
			if(!empty($this->arr_aggregates[$k])){
				$new_name = strtolower($this->arr_aggregates[$k]) . '_' . $v;
				$arr_select_fields[$k] = $this->arr_aggregates[$k] . '(' . $this->primary_table_name . '.' . $v . ') AS ' . $new_name;
				$this->arr_db_field_list[$k] = $new_name;
				//$arr_select_fields[$k] = $new_name;
			} 
		}
		return($arr_select_fields);
	}

	/** function prep_where_criteria
	 * 
	 * translates filter criteria into sql format
	 * @param $arr_filter_criteria
	 * @return void
	 */
	
	protected function prep_where_criteria($arr_filter_criteria){
		//incorporate built-in report filters if set
		if(is_array($this->arr_where_field) && !empty($this->arr_where_field)){
			$tmp_cnt = count($this->arr_where_field);
			for($x = 0; $x < $tmp_cnt; $x++){
				//if the field does not have a table prefix, add it
				if(strpos($this->arr_where_field[$x], '.') === FALSE){
					$this->arr_where_field[$x] = 
						isset($this->arr_field_table[$this->arr_where_field[$x]]) && !empty($this->arr_field_table[$this->arr_where_field[$x]])
						? $this->arr_field_table[$this->arr_where_field[$x]] . '.' . $this->arr_where_field[$x]
						: $this->primary_table_name . '.' . $this->arr_where_field[$x];
				}
				$this->{$this->db_group_name}->where($this->arr_where_field[$x] . $this->arr_where_operator[$x] . $this->arr_where_criteria[$x]);
			}
		}

		foreach($arr_filter_criteria as $k => $v){
			if(strpos($k, '.') === FALSE && strpos($k, 'dbfrom') === FALSE && strpos($k, 'dbto') === FALSE) $k = isset($this->arr_field_table[$k]) && !empty($this->arr_field_table[$k])?$this->arr_field_table[$k] . '.' . $k: $this->primary_table_name . '.' . $k;
			
			if(empty($v) === FALSE || $v === '0'){
				if(is_array($v)){
					if(($tmp_key = array_search('NULL', $v)) !== FALSE){
						unset($v[$tmp_key]);
						$text = implode(',', $v);
						if(!empty($v)) $this->{$this->db_group_name}->where("($k IS NULL OR $k IN ( $text ))");
						else $this->{$this->db_group_name}->where("$k IS NULL");
					}
					else $this->{$this->db_group_name}->where_in($k, $v);
				}
				else { //is not an array
					if(substr($k, -5) == "_dbto"){ //ranges
						$db_field = substr($k, 0, -5);
						if(strpos($db_field, '.') === FALSE) $db_full_field = isset($this->arr_field_table[$db_field]) && !empty($this->arr_field_table[$db_field])?$this->arr_field_table[$db_field] . '.' . $db_field: $this->primary_table_name . '.' . $db_field;
						$this->{$this->db_group_name}->where("$db_full_field BETWEEN '" . date_to_mysqldatetime($arr_filter_criteria[$db_field . '_dbfrom']) . "' AND '" . date_to_mysqldatetime($arr_filter_criteria[$db_field . '_dbto']) . "'");
					}
					elseif(substr($k, -7) != "_dbfrom"){ //default--it skips the opposite end of the range as _dbto
						$this->{$this->db_group_name}->where($k, $v);
					}
				} 
			}
		}
	}
	
	/*  
	 * @method prep_group_by()
	 * @author Chris Tranel
	 */
	protected function prep_group_by(){
//var_dump($this->arr_group_by_field);
		$arr_len = is_array($this->arr_group_by_field)?count($this->arr_group_by_field):0;
		for($c=0; $c<$arr_len; $c++) {
			$table = isset($this->arr_field_table[$this->arr_group_by_field[$c]]) && !empty($this->arr_field_table[$this->arr_group_by_field[$c]])?$this->arr_field_table[$this->arr_group_by_field[$c]] . '.':$this->primary_table_name . '.';
			if(!empty($this->arr_group_by_field[$c])){
				$this->{$this->db_group_name}->group_by($table . $this->arr_group_by_field[$c]);
			}
		}
	}
	
	/*  
	 * @method prep_sort()
	 * @param array fields to sort by
	 * @param array sort order--corresponds to first parameter
	 * @author Chris Tranel
	 */
	protected function prep_sort($arr_sort_by, $arr_sort_order){
		$arr_len = is_array($arr_sort_by)?count($arr_sort_by):0;
		for($c=0; $c<$arr_len; $c++) {
			$sort_order = (strtoupper($arr_sort_order[$c]) == 'DESC') ? 'DESC' : 'ASC';
			$table = isset($this->arr_field_table[$arr_sort_by[$c]]) && !empty($this->arr_field_table[$arr_sort_by[$c]])?$this->arr_field_table[$arr_sort_by[$c]] . '.':$this->primary_table_name . '.';
			if((!is_array($this->arr_unsortable_columns) || in_array($arr_sort_by[$c], $this->arr_unsortable_columns) === FALSE) && !empty($arr_sort_by[$c])){
				//if($this->arr_field_sort[$arr_sort_by[$c]] == 'ASC'){
					//put the select in an array in case the field includes a function with commas between parameters 
				//	$this->{$this->db_group_name}->select(array('ISNULL(' . $table . $arr_sort_by[$c] . ', 1) AS isnull' . $c), FALSE);
				//	$this->{$this->db_group_name}->order_by('isnull' . $c, $sort_order);
				//}
				if(is_array($this->arr_natural_sort_fields) && in_array($arr_sort_by[$c], $this->arr_natural_sort_fields) !== FALSE){
					$this->{$this->db_group_name}->order_by('rpm.dbo.naturalize(' . $table . $arr_sort_by[$c] . ')', $sort_order);
				}
				else {
					$this->{$this->db_group_name}->order_by($table . $arr_sort_by[$c], $sort_order);
				}
			}
		}
	}
	
	/*  
	 * @method pivot()
	 * @param array dataset
	 * @param string header field
	 * @param int pdf with of header field
	 * @param bool add average column
	 * @param bool add sum column
	 * @return array pivoted resultset
	 * @author Chris Tranel
	 */
	public function pivot($arr_dataset, $header_field, $header_field_width, $label_column_width, $bool_avg_column = FALSE, $bool_sum_column = FALSE, $bool_bench_column = FALSE){
$bool_bench_column = FALSE;
		$this->date_field = $header_field;
		$sess_benchmarks = $this->session->userdata('benchmarks');
		$header_text = ' ';
		$arr_sum = 0;
		$count = 0;
		$new_dataset = array();
		//headers not allowed in pivot tables, so we flatten the array
		$this->arr_fields = array_flatten($this->arr_fields);
		foreach($this->arr_fields as $k=>$v){
			if($v == $header_field){
				$header_text = $k;
				$this->arr_unsortable_columns[] = $v;
			}
			else {
//echo $header_field . '<br>';
				$new_dataset[$v][$header_field] = $k;
			}
		}
		
		$this->arr_fields = array($header_text => $header_field); //used for labels in left-most column that are set in foreach loop above
		$this->arr_field_sort[$header_field] = 'ASC';
		$this->arr_pdf_widths[$header_field] = $label_column_width;
		if(!isset($arr_dataset) || empty($arr_dataset)) return FALSE;
		
//******************pull decimal points from meta data???***************
		foreach($arr_dataset[0] as $k => $v){
			$dec_pts[$k] = 0;
		}
		foreach($arr_dataset as $row){
			$cnt = 0;
//var_dump($row);
//die();
			foreach($row as $name => $val){
				if($name == $header_field){
					$this->arr_fields[$val] = $val;
					$this->arr_pdf_widths[$val] = $header_field_width;
					$this->arr_field_sort[$val] = 'ASC';
					$this->arr_unsortable_columns[] = $val;
				}
				elseif(strpos($name, 'isnull') === FALSE) {
					if(isset($this->arr_decimal_points[$name])){
						$dec_pts[$name] = $this->arr_decimal_points[$name];
					} 
					else{
						$tmp_dec_pts  = strlen(substr(strrchr($val, "."), 1));
						if($tmp_dec_pts > $dec_pts[$name]) $dec_pts[$name] = $tmp_dec_pts;
					}
					if(isset($new_dataset[$name]['total']) === FALSE && $val !== NULL){
						$new_dataset[$name]['total'] = 0;
						$new_dataset[$name]['count'] = 0;
					} 
						$new_dataset[$name][$row[$header_field]] = $val;
					if($val !== NULL){
						$new_dataset[$name]['total'] += $val;
						$new_dataset[$name]['count'] ++;
					} 
				}				
				$cnt++;
			}
		}
		//begin benchmarks
		if($bool_bench_column){
			$this->load->library('benchmarks_lib');
			$bench_settings = $this->get_bench_settings();
			$this->benchmarks_lib->set_criteria($this->primary_table_name, $header_field, $bench_settings['metric'], $bench_settings['criteria'], $bench_settings['arr_herd_size'], $bench_settings['arr_states']);
			$bench_sql = $this->benchmarks_lib->build_benchmark_query($this);
			$arr_benchmarks = $this->{$this->db_group_name}->query($bench_sql)->result_array();
			$arr_benchmarks = $arr_benchmarks[0];
			$arr_summary_fields[ucwords(strtolower(str_replace('_', ' ', $sess_benchmarks['metric']))) . ' (n=' . $arr_benchmarks['cnt_herds'] . ')'] = 'benchmark';
			$this->arr_pdf_widths['benchmark'] = $header_field_width;
			$this->arr_field_sort['benchmark'] = 'ASC';
			$this->arr_unsortable_columns[] = 'benchmark';
		}
		if($bool_avg_column){
			$this->arr_fields['Average'] = 'average';
			$this->arr_pdf_widths['average'] = $header_field_width;
			$this->arr_field_sort['average'] = 'ASC';
			$this->arr_unsortable_columns[] = 'average';
		}
		if($bool_sum_column){
			$this->arr_fields['Total'] = 'total';
			$this->arr_pdf_widths['total'] = $header_field_width;
			$this->arr_field_sort['total'] = 'ASC';
			$this->arr_unsortable_columns[] = 'total';
			}
		foreach($new_dataset as $k=>$a){
			if($bool_bench_column){
				if($arr_benchmarks[$k] !== NULL) $sum_data['benchmark'] = round($arr_benchmarks[$k], $dec_pts[$k]);//strpos($arr_benchmarks[$k], '.') !== FALSE ? trim(trim($arr_benchmarks[$k],'0'), '.') : $arr_benchmarks[$k];
				else $sum_data['benchmark'] = NULL;
			}
			if($bool_avg_column){
				$count = count($a) - 1;
				$new_dataset[$k]['average'] = $new_dataset[$k]['total'] / $count;
			}
			if($bool_sum_column){
				$new_dataset[$k]['total'] = $sum;
			}
			if(($bool_avg_column && !$bool_sum_column) || (!$bool_avg_column && !$bool_sum_column)){ //total column should not be displayed on PDF if it is only used to calculate avg 
				unset($new_dataset[$k]['total']);
			}
		}
		$this->arr_db_field_list = $this->arr_fields;
//var_dump($this->arr_fields);
//var_dump($new_dataset);
//die();
		return $new_dataset;
	}
	
	/*  
	 * @method date_to_header()
	 * @param array dataset
	 * @param string header field
	 * @param int pdf with of header field
	 * @param bool add average column
	 * @param bool add sum column
	 * @return array pivoted resultset
	 * @author Chris Tranel
	 */
	protected function date_to_header($arr_dataset, $x_axis_field, $y_axis_field, $header_field_width, $label_column_width, $bool_avg_column = TRUE, $bool_sum_column = FALSE, $bool_bench_column = FALSE){
		$this->date_field = $x_axis_field;
		$sess_benchmarks = $this->session->userdata('benchmarks');
		$header_text = ' ';
		$arr_sum = 0;
		$count = 0;
		$new_dataset = array();
		foreach($this->arr_fields as $k=>$v){
			if($v == $x_axis_field){
				$header_text = $k;
				$this->arr_unsortable_columns[] = $v;
			}
			else {
				//$new_dataset[$v[$y_axis_field]][$x_axis_field] = $k;
			}
		}
		
		$this->arr_fields = array($header_text => $x_axis_field); //used for labels in left-most column that are set in foreach loop above
		$this->arr_field_sort[$x_axis_field] = 'ASC';
		$this->arr_pdf_widths[$x_axis_field] = $label_column_width;
		foreach($arr_dataset as $row){
			$dec_pts[$row[$y_axis_field]] = 0;
			foreach($row as $k => $v){
				if(!isset($dec_pts[$k])) $dec_pts[$k] = 0;
				if($k == $x_axis_field){
					$this->arr_fields[$row[$x_axis_field]] = $v;
					$this->arr_pdf_widths[$row[$x_axis_field]] = $header_field_width;
					$this->arr_field_sort[$row[$x_axis_field]] = 'ASC';
					$this->arr_unsortable_columns[] = $v; 
				}
				elseif($k == $y_axis_field){
					
				}
				elseif(strpos($row[$y_axis_field], 'isnull') === FALSE) {
					if(isset($this->arr_decimal_points[$k])) $dec_pts[$k] = $this->arr_decimal_points[$k];
					else{
						$tmp_dec_pts  = strlen(substr(strrchr($v, "."), 1));
						if($tmp_dec_pts > $dec_pts[$k]) $dec_pts[$k] = $tmp_dec_pts;
					}
					
					$new_dataset[$row[$y_axis_field]][$x_axis_field] = $row[$y_axis_field];
					if(isset($new_dataset[$row[$y_axis_field]]['total']) === FALSE && $v !== NULL){
						$new_dataset[$row[$y_axis_field]]['total'] = 0;
						$new_dataset[$row[$y_axis_field]]['average'] = 0;
						$new_dataset[$row[$y_axis_field]]['count'] = 0;
					} 
					$new_dataset[$row[$y_axis_field]][$row[$x_axis_field]] = $v;
					if($v !== NULL){
						$new_dataset[$row[$y_axis_field]]['total'] += $v;
						$new_dataset[$row[$y_axis_field]]['count'] ++;
					} 
				}				
			}
		}

		//begin benchmarks
		if($bool_bench_column){
			$bench_settings = $this->get_bench_settings();
			$this->benchmarks_lib->set_criteria($this->primary_table_name, $header_field, $bench_settings['metric'], $bench_settings['criteria'], $bench_settings['arr_herd_size'], $bench_settings['arr_states']);
			$bench_sql = $this->benchmarks_lib->build_benchmark_query($this);
			$arr_benchmarks = $this->{$this->db_group_name}->query($bench_sql)->result_array();
			$arr_benchmarks = $arr_benchmarks[0];
			
			$arr_summary_fields[ucwords(strtolower(str_replace('_', ' ', $sess_benchmarks['metric']))) . ' (' . $arr_benchmarks['cnt_herds'] . ')'] = 'benchmark';
			$this->arr_pdf_widths['benchmark'] = $header_field_width;
			$this->arr_field_sort['benchmark'] = 'ASC';
			$this->arr_unsortable_columns[] = 'benchmark';
		}
		if($bool_avg_column){
			$arr_summary_fields['Rolling 12'] = 'average';
			$this->arr_pdf_widths['average'] = $header_field_width;
			$this->arr_field_sort['average'] = 'ASC';
			$this->arr_unsortable_columns[] = 'average';
		}
		if($bool_sum_column){
			$arr_summary_fields['Total'] = 'total';
			$this->arr_pdf_widths['total'] = $header_field_width;
			$this->arr_field_sort['total'] = 'ASC';
		}
		$this->arr_fields = array_slice($this->arr_fields, 0, 1) + $arr_summary_fields + array_slice($this->arr_fields, 1);
		unset($arr_summary_fields);
		foreach($new_dataset as $k=>$a){
			if($bool_bench_column){
				if($arr_benchmarks[$k] !== NULL) $sum_data['benchmark'] = round($arr_benchmarks[$k], $dec_pts[$k]);//strpos($arr_benchmarks[$k], '.') !== FALSE ? trim(trim($arr_benchmarks[$k],'0'), '.') : $arr_benchmarks[$k];
				else $sum_data['benchmark'] = NULL;
			}
			if($bool_avg_column){
				$sum_data['average'] = isset($new_dataset[$k]['count']) ? $new_dataset[$k]['total'] / $new_dataset[$k]['count'] : NULL;
				if($sum_data['average'] !== NULL) $sum_data['average'] = round($sum_data['average'], $dec_pts[$k]);
			}
			if($bool_sum_column){
				$sum_data['total'] = $sum;
			}
			if($bool_avg_column && !$bool_sum_column){ //total column should not be displayed on PDF if it is only used to calculate avg 
				unset($new_dataset[$k]['total']);
			}
			if(!$bool_avg_column && !$bool_sum_column)
			unset($new_dataset[$k]['count']);
			$new_dataset[$k] = array_slice($new_dataset[$k], 0, 1) + $sum_data + array_slice($new_dataset[$k], 1);
			unset($sum_data);
		}
		return $new_dataset;
	}
	
	protected function _set_autofilter($arr_filter_criteria){
		$this->arr_messages['filter_alert'] = '';
		$num_fields = count($this->arr_auto_filter_field);
		for($c = 0; $c < $num_fields; $c++){
			if(empty($arr_filter_criteria[$this->arr_auto_filter_field[$c]])){
				//handle range fields
				$dbfield = str_replace('_dbfrom', '', $this->arr_auto_filter_field[$c]);
				$dbfield = str_replace('_dbto', '', $dbfield);
				//end handle range fields
				
				$criteria = $this->arr_auto_filter_criteria[$c];
				if(in_array($dbfield, $this->arr_date_fields) || in_array($dbfield, $this->arr_datetime_fields)) $criteria = date_to_mysqldatetime($criteria);
				if(in_array($dbfield, $this->arr_numeric_fields) === FALSE) $criteria = "'" . $criteria . "'";
				
				$this->{$this->db_group_name}->where($dbfield . $this->arr_auto_filter_operator[$c] . $criteria);
				$this->arr_messages['filter_alert'] .= $this->arr_auto_filter_alert[$c];
			}
		}
	}

	public function get_auto_filter_criteria(){
		$arr_return = array();
		$num_fields = count($this->arr_auto_filter_field);
		for($c = 0; $c < $num_fields; $c++){
			$arr_return[] = array('key' => $this->arr_auto_filter_field[$c], 'value' => $this->arr_auto_filter_criteria[$c]);
		}
		return $arr_return;
	}
	
	/**
	 * get_recent_dates
	 * @return date string
	 * @author Chris Tranel
	 **/
	public function get_recent_dates($date_field = 'test_date', $num_dates = 1, $date_format = 'MMM-yy') {
		if($date_format) $this->db->select("FORMAT(" . $date_field . ", '" . $date_format . "', 'en-US') AS " . $date_field, FALSE);
		else $this->db->select($date_field);
		$this->db
			->where($this->primary_table_name . '.herd_code', $this->session->userdata('herd_code'))
			->where('pstring', $this->session->userdata('pstring'))
			->where($date_field . ' IS NOT NULL')
			->order_by($this->primary_table_name . '.' . $date_field, 'desc');
			//->join('herd.dbo.herd_test_results', 'herd.dbo.herd_id.herd_code = herd.dbo.herd_test_results.herd_code','left');
		if(isset($num_dates) && !empty($num_dates)) $this->{$this->db_group_name}->limit($num_dates);		
		$result = $this->db->get($this->primary_table_name)->result_array();
		if(is_array($result) && !empty($result)){
			//if($num_dates == 1) return $result[0][$date_field];
			return array_flatten($result);
		} 
		else return FALSE;
	}

	/**
	 * get_start_test_date
	 * @param int num_test_dates - number of test dates to include
	 * @return date string
	 * @author Chris Tranel
	 **/
	public function get_start_date($date_field = 'test_date', $num_dates = 12, $date_format = 'MMM-yy') {
		$sql = "SELECT FORMAT(a." . $date_field . ", 'MM-dd-yyyy', 'en-US') AS " . $date_field . "
FROM (SELECT DISTINCT TOP " . ($num_dates + 1) . " " . $date_field . "
	FROM " . $this->primary_table_name . " 
	WHERE herd_code = '" . $this->session->userdata('herd_code') . "' AND pstring = '" . $this->session->userdata('pstring') . "' AND " . $date_field . " IS NOT NULL
	ORDER BY " . $date_field . " DESC) a";
		$result = $this->{$this->db_group_name}->query($sql)->result_array();
		
/*			->select("FORMATs(" . $date_field . ", '" . $date_format . "', 'en-US') AS " . $date_field, FALSE)
			->where('herd_code', $this->session->userdata('herd_code'))
			->order_by('herd.dbo.herd_test_results.' . $date_field, 'desc');
		if(isset($num_dates) && !empty($num_dates)) $this->{$this->db_group_name}->limit(1, ($num_dates));		
		$result = $this->{$this->db_group_name}->get('herd.dbo.herd_test_results')->result_array();
*/		
		if(isset($num_dates) === FALSE || ($num_dates) > (count($result) -1)) $num_dates = (count($result) -1);
		if(is_array($result) && !empty($result)) return $result[($num_dates)][$date_field];
		else return FALSE;
	}
	
	
/******* CHART FUNCTIONS ****************/
	public function set_chart_fields($block_in){
		$arr_numeric_types = array('bigint','decimal','int','money','smallmoney','numeric','smallint','tinyint','float','real');
		$arr_field_child = array();
		$arr_table_ref_cnt = array();

		$arr_field_data = $this->{$this->db_group_name}
			->where('block_id', $block_in)
			->order_by('list_order')
			->get('users.dbo.v_block_field_data')
			->result_array();
		if(is_array($arr_field_data) && !empty($arr_field_data)){
			foreach($arr_field_data as $fd){
				$fn = $fd['db_field_name'];
				$this->arr_fields[$fd['name']] = $fn;
				$arr_table_ref_cnt[$fd['table_name']] = isset($arr_table_ref_cnt[$fd['table_name']]) ? ($arr_table_ref_cnt[$fd['table_name']] + 1) : 1;
				$this->arr_field_sort[$fn] = $fd['default_sort_order'];
				$this->arr_decimal_points[$fn] = $fd['decimal_points'];
				$this->arr_aggregates[$fn] = $fd['aggregate'];
				$this->arr_axis_index[$fn] = $fd['axis_index'];
				$this->arr_bool_display[$fn] = $fd['display'];
				$this->arr_chart_type[$fn] = $fd['chart_type'];
				$this->arr_unit_of_measure[$fn] = $fd['unit_of_measure'];
				$this->arr_field_table[$fn] = $fd['table_name'];
				if(strpos($fd['data_type'], 'date') !== FALSE && strpos($fn, 'time') !== FALSE) $this->arr_datetime_fields[] = $fn;
				elseif(strpos($fd['data_type'], 'date') !== FALSE) $this->arr_date_fields[] = $fn;
				if($fd['is_nullable'] === FALSE) $arr_notnull_fields[] = $fn;
				if(in_array($fd['data_type'], $arr_numeric_types)) $this->arr_numeric_fields[] = $fn;
				if($fd['is_natural_sort']) $this->arr_natural_sort_fields[] = $fn;
			}
		}
		$this->primary_table_name = array_search(max($arr_table_ref_cnt), $arr_table_ref_cnt);
		//set up arr_fields hierarchy
		if(is_array($arr_table_ref_cnt) && count($arr_table_ref_cnt) >  1){
			foreach($arr_table_ref_cnt as $t => $cnt){
				if($t != $this->primary_table_name){
					$this->arr_joins[] = array('table'=>$t, 'join_text'=>$this->get_join_text($this->primary_table_name, $t));
				}
			}
		}
		$this->adjust_fields($this->session->userdata('herd_code'));
	}
	
	/**
	 * @method get_chart_axes - retrieve data for categories, axes, etc.
	 * @param int block id
	 * @return array of meta data for the block
	 * @access public
	 *
	 **/
	public function get_chart_axes($block_id){
		$arr_return = array();
		$this->{$this->db_group_name}
			->select("a.id, a.x_or_y, a.min, a.max, a.opposite, a.data_type, f.db_field_name, f.name AS field_name, f.unit_of_measure, text,c.name AS category")
			->from('users.dbo.block_axes AS a')
			->join('users.dbo.chart_categories AS c', 'a.id = c.block_axis_id', 'left')
			->join('users.dbo.db_fields AS f', 'a.db_field_id = f.id', 'left')
			->where('a.block_id', $block_id)
			->order_by('a.list_order', 'asc')
			->order_by('c.list_order', 'asc');
		$result = $this->{$this->db_group_name}->get()->result_array();
		
		$arr_keep_keys = array('min' => '', 'max' => '', 'opposite' => '', 'data_type' => '', 'db_field_name' => '', 'field_name' => '', 'unit_of_measure' => '', 'text' => '');
		if(is_array($result) && !empty($result)){
			foreach($result as $a){
				if(!isset($arr_return[$a['x_or_y']][$a['id']])) $arr_return[$a['x_or_y']][$a['id']] = array_intersect_key($a, $arr_keep_keys);
				if(isset($a['category'])) $arr_return[$a['x_or_y']][$a['id']]['categories'][] = $a['category'];
			}
			return $arr_return;
		}
		else return FALSE;
	}
	
	/**
	 * @method set_row_to_series - used when each row from a set of database results corresponds with a series of data.
	 * @param array of field name base text (for percentages, add '_pct')
	 * @return array of data for the graph
	 * @access protected
	 *
	 **/
	protected function set_row_to_series($data, $arr_fieldname_base, $arr_categories){
		$mod_base = count($arr_categories);
		if(is_array($data) && !empty($data)){
			//loop for data in which each row represents a series.
			$key = 0;
			foreach($data as $k=>$row){
				$count = 1;
				$key++;
				//must account for multiple series being returned in a single row
				foreach($arr_fieldname_base as $kk => $f){
					if($count > $mod_base && $count % $mod_base == 1) $key++;
					if(!isset($key)) $key = $k;
					$arr_return[$key][] = (float)$row[$f];
					$count++;
				}
			}
			return $arr_return;
		}
		else return FALSE;
	}
	
	/**
	 * @method get_12_mo_avg_graph_data()
	 * @param array database field names included on graph
	 * @param string herd code
	 * @param int number of tests to include on report
	 * @param string date field used on graph (test_date)
	 * @return array of data for the chart
	 * @access public
	 *
	 **/
	function get_12_mo_avg_graph_data($arr_fieldname, $herd_code, $num_tests = 12, $date_field = 'test_date', $arr_categories = NULL){
		if(is_array($arr_fieldname) && !empty($arr_fieldname)) {
			$select_str = '';
			foreach($arr_fieldname AS $f){
				$select_str .= 'ROUND(AVG(' . $f . ' * 1.0), 0) AS ' . $f . ', ';
			}
			$this->{$this->db_group_name}
			//->select($date_field)
			->select(substr($select_str, 0, -2), FALSE);	
		} 
		$data = $this->{$this->db_group_name}
		->where('herd_code', $this->session->userdata('herd_code'))
		//->order_by($date_field, 'desc', FALSE)
		->limit($num_tests)
		->get($this->primary_table_name)
		->result_array();
		if(isset($arr_categories) && is_array($arr_categories)) $return_val = $this->set_row_to_series($data, $arr_fieldname, $arr_categories);
		else $return_val = $this->set_longitudinal_data($data, $date_field);
		return $return_val;
	}
	
	/**
	 * @method get_graph_data()
	 * @param array database field names included on graph
	 * @param string herd code
	 * @param int number of tests to include on report
	 * @param string date field used on graph (test_date)
	 * @return array of data for the chart
	 * @access public
	 *
	 **/
	function get_graph_data($arr_fieldname, $herd_code, $num_dates, $date_field, $arr_categories = NULL){
		if(isset($date_field)){
			$from_date = $this->get_start_date($date_field, $num_dates, 'MM-dd-yyyy');
			$arr_to_date = $this->get_recent_dates($date_field, 1, 'MM-dd-yyyy');
			$data = $this->search($herd_code, array('herd_code'=>$herd_code, 'pstring'=>$this->session->userdata('pstring'), $date_field . '_dbfrom' => $from_date, $date_field . '_dbto' => $arr_to_date[0]), array($date_field . ''), array('ASC'), $num_dates);
		}
		else{
			$data = $this->search($herd_code, array('herd_code'=>$herd_code, 'pstring'=>$this->session->userdata('pstring')), array($date_field), array('ASC'), $num_dates);
		}
		if(isset($arr_categories) && is_array($arr_categories)) $return_val = $this->set_row_to_series($data, $arr_fieldname, $arr_categories);
		else $return_val = $this->set_longitudinal_data($data, $date_field);
		return $return_val;
	}
	
	/**
	 * @method get_longitudinal_data()
	 * @param array of field name base text (for percentages, add '_pct')
	 * @param string date field used on graph (test_date)
	 * @return array of data for the graph
	 * @access protected
	 *
	 **/
	protected function set_longitudinal_data($data, $date_field = 'test_date'){
		$count = count($data);
		for($x = 0; $x < $count; $x++){//($x = $count-1; $x >=0; $x--){
			$arr_y_values = $data[$x];

			$arr_fields = array_keys($arr_y_values);
			$date_key = array_search($date_field, $arr_fields);
			unset($arr_fields[$date_key]);
			if($date_field == 'age_months'){
				foreach($arr_fields as $k=>$f){
					$tmp_data = is_numeric($data[$x][$f]) ? (float)$data[$x][$f] : $data[$x][$f];
					$arr_return[$k][] = array($data[$x][$date_field], $tmp_data);
				}
			}
			else{
				$arr_d = explode('-', $data[$x][$date_field]);
				foreach($arr_fields as $k=>$f){
					$tmp_data = is_numeric($data[$x][$f]) ? (float)$data[$x][$f] : $data[$x][$f];
					//YYYY-MM-DD: $arr_return[$k][] = array((mktime(0, 0, 0, $arr_d[1], $arr_d[2],$arr_d[0]) * 1000), $tmp_data);
					//MM-DD-YYYY:
					$arr_return[$k][] = array((mktime(0, 0, 0, $arr_d[0], $arr_d[1],$arr_d[2]) * 1000), $tmp_data);
				}
			}
		}
		if(isset($arr_return) && is_array($arr_return)) return $arr_return;
		else return FALSE;
	}

	
	/**
	 * @method get_benchmark_fields()
	 * @param array fields to exclude from the returned value
	 * @return array of data fields for the current primary table, excluding those fields in the param
	 * @access protected
	 *
	 **/
	function get_benchmark_fields($arr_excluded_fields = NULL){
		$sql = "SELECT CAST ((select ',AVG('+quotename(C.name)+') AS '+quotename(C.name)
         from sys.columns as C
         where C.object_id = object_id('" . $this->primary_table_name . "')";
        if(is_array($arr_excluded_fields) && !empty($arr_excluded_fields)) $sql .= " and C.name NOT IN('" . implode("','", $arr_excluded_fields) . "')";// AND C.name NOT LIKE 'cnt%'";
        $sql .= " AND TYPE_NAME(C.user_type_id) NOT IN('char','smalldatetime','varchar','date')";
		$sql .= "for xml path('')) AS text) AS fields";

        $results = $this->{$this->db_group_name}
		->query($sql)
		->result_array();
		return substr($results[0]['fields'], 1);
	}
	
	/**
	 * @method get_bench_settings()
	 * @return array of data for the graph
	 * @access protected
	 **/
	protected function get_bench_settings(){
		//arr_criteria (field_name, sort_order, table_name, join_field) and arr_herd_size (herd_size_floor, herd_size_ceiling) are arrays, region must be translated to arr_states
		$arr_user_herd_settings = $this->ion_auth_model->get_user_herd_settings();
		$herd_info = $this->herd_model->header_info($this->herd_code);
		$arr_default = $this->benchmarks_lib->get_default_settings($herd_info['herd_size'], $herd_info['state']);
		if(isset($arr_user_herd_settings) && is_array($arr_user_herd_settings)){
			$arr_common = array_intersect_key($arr_default, $arr_user_herd_settings);
			if(is_array($arr_common) && !empty($arr_common)){
				foreach($arr_common as $k=>$v){
					$arr_default[$k] = $arr_user_herd_settings[$k];
				}
			}
		}
		return $arr_default;
	}
	
	
	/**
	 * @method set_boxplot_data()
	 * @param array of data from active record result_array() function
	 * @param int number of boxplot series (BOXPLOT SERIES FIELDS MUST ALL BE IMMEDIATELY AFTER THE TEST DATE)
	 * @return array of data for the graph
	 * @access protected
	 *
	 **/
	protected function set_boxplot_data($data, $num_boxplot_series = 1, $series_space = 400000000){
		$row_count = 0;
		$arr_series = array();
		foreach ($data as $d){
		$arr_d = explode('-', $d['test_date']);
			unset($d['test_date']);
			$this_date = mktime(0, 0, 0, $arr_d[1], $arr_d[2],$arr_d[0]) * 1000;
			$field_count = 1;
			$series_count = 0;
			$arr_series[$series_count][$row_count] = array($this_date);
			foreach ($d as $f){
				$tmp_data = is_numeric($f) ? (float)$f : $f;
				if($field_count <= ($num_boxplot_series * 4)){//boxplot work-around using candlestick chart requires 4 datapoints
					$arr_series[$series_count][$row_count][] = $tmp_data;
					if($field_count%4 == 0 && $field_count > 1){
						$series_count++;
						if(($field_count + 1) <= ($num_boxplot_series * 4)) $arr_series[$series_count][$row_count] = array($this_date + ($series_space * $series_count)); //adjust date so that multiple boxplots are not on top of each other
					}
				}
				else { //assumes that non-box series correspond to box series
					$arr_series[$series_count][$row_count] = array(($this_date + ($series_space * ($series_count - $num_boxplot_series))), $tmp_data);
					$series_count++;
				}
				$field_count++;
			}
			$row_count++;
		}
		return $arr_series;
	}
	
	/**
	 * @method get_snapshot_data()
	 * @param array of field name base text (for percentages, add '_pct')
	 * @return array of data for the graph
	 * @access private
	 *
	private function set_snapshot_data($data, $arr_fieldname_base){
//		$this->{$this->db_group_name}->order_by('test_date', 'desc')
//			->limit(1);
//		$data = $this->{$this->db_group_name}->get($this->tables['rpm_uhm_new'])->result_array();
		if(is_array($data) && !empty($data)){
			$data = $data[0];
//			$percentiles = $this->get_percentiles($data, $this->tables['rpm_uhm_new'], $this->test_date);
//			$percentiles = $percentiles[0];
			
			foreach($data as $k=>$v){
				$arr_return[] = array('y'=>$percentiles[$k], 'value'=>$v);
			}
			
			$arr_bench = $this->get_bench_graph_data($arr_fieldname_base);
			return array($arr_return, $arr_bench);
		}
		else return FALSE;
	}
	 **/
}