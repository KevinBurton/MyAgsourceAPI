<?php
namespace myagsource\report_filters;
require_once 'CriteriaFactory.php';

use myagsource\report_filters\CriteriaFactory;


if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* Name:  Filters Library File
*
* Author: ctranel
*		  Compiled and Expanded by Kevin Marshall, refactored by ctranel
*

*
* Created:  20131118
*
* Description:  Collection of filter criteria
*
* Requirements: PHP5 or above
*
*/

class Filters{
	private $filter_model; //model object
	private $arr_criteria; //array of criteria objects
	private $arr_criteria_key_value;
	private $primary_model;
	private $log_filter_text;
	
	public function __construct(\Filter_model $filter_model){
		$this->filter_model = $filter_model;
	}
	
	/* -----------------------------------------------------------------
	*  Returns filter text

	*  Returns filter text

	*  @since: version 1
	*  @author: ctranel
	*  @date: Jun 17, 2014
	*  @return: string filter text
	*  @throws: 
	* -----------------------------------------------------------------
	*/
	public function get_filter_text(){
		if(empty($this->log_filter_text)){
			$this->set_filter_text();
		}
		return $this->log_filter_text;
	}

	/* -----------------------------------------------------------------
	*  displayFilters() returns boolean of whether filters should be displayed

	*  Returns boolean of whether filters should be displayed

	*  @since: version 1
	*  @author: ctranel
	*  @date: Jun 17, 2014
	*  @return boolean
	*  @throws: 
	* -----------------------------------------------------------------
	*/
	public function displayFilters(){
		if(count($this->arr_criteria) == 0){
			return false;
		}
		foreach($this->arr_criteria as $c){
			if($c->isDisplayed()) {
				return true;
			}
		}
		return false;
	}
	
	/* -----------------------------------------------------------------
	*  setCriteria() sets default filter criteria based on the field-specific values (herd code, test date)
	*   and page filters pulled from the database

	*  sets default filter criteria based on the field-specific values (herd code, test date)
	*   and page filters pulled from the database

	*  @author: ctranel
	*  @date: Jun 17, 2014
	*  @param: array page-level filters
	*  @param: $arr_params key=>value pairs either hard-coded or from form submission
	*  @return void
	*  @throws: 
	* -----------------------------------------------------------------
	*/
	public function setCriteria($sect_id, $page_path, $arr_form_data = null){
		//get filters from DB for the current page, set other vars
		$arr_page_filter_data = $this->filter_model->get_page_filters($sect_id, $page_path);
		//set default criteria as base
		$this->setFilterCriteria($arr_page_filter_data, $arr_form_data);
	}

	/* -----------------------------------------------------------------
	*  removeCriteria

	*  removes specified criteria object from filters array

	*  @author: ctranel
	*  @date: May 13, 2015
	*  @param: string 
	*  @return void
	*  @throws: 
	* -----------------------------------------------------------------
	*/
	public function removeCriteria($criteria_field_name){
			foreach($this->arr_criteria as $k => $c){
			if($k === $criteria_field_name) {
				unset($this->arr_criteria[$k]);
				return;
			}
		}
	}

	/* -----------------------------------------------------------------
	*  setFilterCriteria() sets default filter criteria based on the field-specific values (herd code, test date)
	*   and page filters pulled from the database

	*  sets default filter criteria based on the field-specific values (herd code, test date)
	*   and page filters pulled from the database

	*  @author: ctranel
	*  @date: Jun 17, 2014
	*  @param: array page-level filter data
	*  @param: array filter form data
	*  @return void
	*  @throws: 
	* -----------------------------------------------------------------
	*/
	protected function setFilterCriteria($arr_page_filter_data, $arr_form_data){
		if(!isset($arr_page_filter_data)){
			return false;
		}

		//if there is a value in form data that is not in FilterCriteria (e.g., a value that is set programmatically (herd_code)), need to set that up
		$arr_to_create = array_diff_key($arr_form_data, $arr_page_filter_data);
		if(is_array($arr_to_create) && !empty($arr_to_create)){
			foreach($arr_to_create as $k=>$f){
				if(!is_array($f)){
					$f = [$f];
				}
				$arr_tmp = array(
					'name' => ucwords(str_replace('_', ' ', $k)),
					'type' => 'value',
					'options_source' => null,
					'default_value' => $f,
					'db_field_name' => $k,
					'arr_selected_values' => $arr_form_data[$k],
				);
				$this->arr_criteria[$k] = CriteriaFactory::createCriteria($this->filter_model, $arr_tmp, $arr_form_data['herd_code']);
			}
		}
		foreach($arr_page_filter_data as $k=>$f){
			//if there is a form value set for this filter, use that
			if(isset($arr_form_data[$k]) && !empty($arr_form_data[$k])){
				$f['arr_selected_values'] = $arr_form_data[$k];
			}
			$this->arr_criteria[$k] = CriteriaFactory::createCriteria($this->filter_model, $f, $arr_form_data['herd_code']);
		}
	}

	/* -----------------------------------------------------------------
	*  criteriaKeyValue() Returns an key=>value array of field_name=>selected_value

	*  Returns an key=>value array of field_name=>selected_value.  These values are populated with the set filter function

	*  @since: version 1
	*  @author: ctranel
	*  @date: Jun 17, 2014
	*  @return array field_name=>selected_value
	*  @throws: 
	* -----------------------------------------------------------------
	*/
	public function criteriaKeyValue(){
		if(!isset($this->arr_criteria)){
			return false;
		}
		if(!isset($this->arr_criteria_key_value)){
			$this->setCriteriaKeyValue();
		}
		return $this->arr_criteria_key_value;
	}

	/* -----------------------------------------------------------------
	*  criteriaKeyValue() Returns an key=>value array of field_name=>selected_value

	*  Returns an key=>value array of field_name=>selected_value.  These values are populated with the set filter function

	*  @since: version 1
	*  @author: ctranel
	*  @date: 10/17/2014
	*  @param string criteria key
	*  @return array field_name=>selected_value
	*  @throws: 
	* -----------------------------------------------------------------
	*/
	public function getCriteriaValueByKey($key){
		return $this->arr_criteria[$key]->getSelectedValue();
	}

	/* -----------------------------------------------------------------
	*  setCriteriaKeyValue() sets a key=>value array of field_name=>selected_value

	*  Sets a key=>value array of field_name=>selected_value.

	*  @since: version 1
	*  @author: ctranel
	*  @date: 10/08/2014
	*  @return void
	*  @throws: 
	* -----------------------------------------------------------------
	*/
	protected function setCriteriaKeyValue(){
		if(!isset($this->arr_criteria)){
			return false;
		}
		foreach($this->arr_criteria as $k=>$c){
			$this->arr_criteria_key_value[$k] = $c->getSelectedValue();
		}
	}
	
	/* -----------------------------------------------------------------
	 *  criteriaExists() checks for the existance of key passed to method
	
	*  @since: version 1
	*  @author: ctranel
	*  @date: 10/15/2014
	*  @param string criteria key
	*  @return boolean
	*  @throws:
	* -----------------------------------------------------------------
	*/
	public function criteriaExists($key){
		if(!isset($this->arr_criteria)){
			return false;
		}
		return isset($this->arr_criteria[$key]);
	}
	
	/* -----------------------------------------------------------------
	*  setCriteriaValue() sets a key=>value array of field_name=>selected_value

	*  Sets a key=>value array of field_name=>selected_value.

	*  @since: version 1
	*  @author: ctranel
	*  @date: 10/15/2014
	*  @param string criteria key
	*  @param value to set
	*  @return void
	*  @throws: 
	* -----------------------------------------------------------------
	*/
	public function setCriteriaValue($key, $value){
		if(!isset($this->arr_criteria)){
			return false;
		}
		$this->arr_criteria[$key]->setSelectedValue($value);
	}
	
	/* -----------------------------------------------------------------
	*  toArray() returns an array representation of objects

	*  Sets a key=>value array of field_name=>selected_value.

	*  @since: version 1
	*  @author: ctranel
	*  @date: 10/08/2014
	*  @return array representation of object
	*  @throws: 
	* -----------------------------------------------------------------
	*/
	public function toArray(){
		if(!isset($this->arr_criteria)){
			return false;
		}
		$arr_return = array();
		foreach($this->arr_criteria as $k=>$c){
			$arr_return[$k] = $c->toArray();
		}
		return $arr_return;
	}

	/**
	 * set_filter_text
	 * 
	 * Sets arr_filter_text variable.  Composes filter text property for use in the GSG Library file
	 * 
	 * @author ctranel
	 * @return void
	 * 
	 **/
	protected function set_filter_text(){
		$this->log_filter_text = '';
		if(!is_array($this->arr_criteria) || empty($this->arr_criteria)){
			return FALSE;
		}
		
		$arr_filter_text = [];
		foreach($this->arr_criteria as $k => $c){
			if($k == 'block'); //don't show block filter info because it is specified in heading
			else{
				$c->set_filter_text();
				$txt = $c->get_filter_text();
				if(!empty($txt)){
					if(!empty($this->log_filter_text)){
						$this->log_filter_text .= '; ';
					}
					$this->log_filter_text .= $txt;
				}
			}
		}
	}
}