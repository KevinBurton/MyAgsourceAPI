<?php
namespace myagsource\Filters;

require_once(APPPATH . 'helpers/multid_array_helper.php');

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* Name:  Filters Library File
*
* Author: ctranel
*
* Created:  10/3/2014
*
* Description:  Collection of filter criteria
*
* Requirements: PHP5 or above
*
*/
//@todo: extends FormControl
class Criteria{
	private $type;
	private $field_name;
	private $label;
	private $user_editable;
	private $options;
	private $options_source;
	//private $list_order;
	private $operator;
    private $default_value;
	private $log_filter_text;
	private $selected_values;
	private $arr_options; //array of criteria objects
	
	public function __construct($criteria_data, $options){
//var_dump($criteria_data);
		$this->type = $criteria_data['type'];
		$this->options = $options;
		$this->field_name = $criteria_data['db_field_name'];
		$this->label = $criteria_data['name'];
		$this->default_value = $criteria_data['default_value'];
        $this->operator = $criteria_data['operator'];
		$this->options_source = $criteria_data['options_source'];
//		$this->options_filter_form_field_name = $criteria_data['options_filter_form_field_name'];
		$this->user_editable = (bool)$criteria_data['user_editable'];
		if(isset($criteria_data['selected_values'])){
			$this->setFilterCriteria($criteria_data['selected_values']);
		}
		
		

		//$this->list_order = $criteria_data['list_order'];
		//$this->setOptions($options_conditions);
		$this->setDefaults();
	}

	/* -----------------------------------------------------------------
	*  Returns selected value

	*  Returns selected value

	*  @since: version 1
	*  @author: ctranel
	*  @date: Jun 17, 2014
	*  @return: array 
	*  @throws: 
	* -----------------------------------------------------------------
	*/
	public function getSelectedValue(){
		return $this->selected_values;
	}

    /* -----------------------------------------------------------------
    *  Returns operator value

    *  Returns operator value

    *  @since: version 1
    *  @author: ctranel
    *  @date: 2016-10-20
    *  @return: string
    *  @throws:
    * -----------------------------------------------------------------
    */
    public function operator(){
        return $this->operator;
    }

    /* -----------------------------------------------------------------
    *  Returns options

    *  Returns array of options

    *  @author: ctranel
    *  @date: Oct 15, 2014
    *  @return: array
    *  @throws:
    * -----------------------------------------------------------------
    public function getOptions(){
        return $this->options;
    }
    */

	/* -----------------------------------------------------------------
	*  isDisplayed

	*  Returns boolean

	*  @author: ctranel
	*  @date: 05-06-2015
	*  @return: boolean 
	*  @throws: 
	* -----------------------------------------------------------------
	*/
	public function isDisplayed(){
		return ((count($this->options) > 1 || !isset($this->options_source)) && $this->user_editable);
	}

	/* -----------------------------------------------------------------
	*  Returns filter text

	*  Returns filter text

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
	 *  Returns array representation of object
	
	*  Returns array representation of object
	
	*  @since: version 1
	*  @author: ctranel
	*  @date: Jun 17, 2014
	*  @return: array
	*  @throws:
	* -----------------------------------------------------------------
	*/
	public function toArray(){
		$arr_return = array(
			'type' => $this->type,
			'field_name' => $this->field_name,
			'label' => $this->label,
            'operator' => $this->operator,
			'selected_values' => $this->selected_values,
			'options' => $this->options,
            'editable' => $this->user_editable,
		);
		return $arr_return;
	}
	
	
	/* -----------------------------------------------------------------
	*  Sets selected value

	*  Sets selected value

	*  @since: version 1
	*  @author: ctranel
	*  @date: Jun 17, 2014
	*  @param $value
	*  @return: void 
	*  @throws: 
	* -----------------------------------------------------------------
	*/
	public function setSelectedValue($value){
		if(!isset($value)){
			return false;
		}
		if(!is_array($value) && $this->operator === 'IN'){
			$value = [$value];
		}
		$this->selected_values = $value;
	}

	/* -----------------------------------------------------------------
	*  setFilterCriteria() sets filter criteria based on filter form submission

	*  sets filter criteria based on filter form submission

	*  @since: version 1
	*  @author: ctranel
	*  @date: Jun 17, 2014
	*  @param: array page-level filters
	*  @param: $arr_params
	*  @return void
	*  @throws: 
	* -----------------------------------------------------------------
	*/
	public function setFilterCriteria($page_filter_value){
		if(!isset($page_filter_value)){
			//nothing to do here
            return;
		}
		//? if($field_name == 'page') $this->arr_criteria['page'] = $this->arr_pages[$this->$arr_params['page']]['name'];
		if($this->operator === 'BETWEEN'){ //data should be a 2 element assoc array
			if(!isset($page_filter_value[0]) || !isset($page_filter_value[1])){
				//can't set a range unless both ends are set
                return;
			}
			$this->selected_values['dbfrom'] = $page_filter_value[0];
			$this->selected_values['dbto'] = $page_filter_value[1];
		}
		elseif($this->operator === 'IN'){ //data should be an array
			if(is_array($page_filter_value)){
                foreach($page_filter_value as $k1=>$v1){
                    $page_filter_value[$k1] = explode('|', $v1);
                }
//var_dump($page_filter_value);
                $page_filter_value = \array_flatten($page_filter_value);
//var_dump($page_filter_value);
                $this->selected_values = $page_filter_value;
            }
            elseif(strpos($page_filter_value, '|')){
                $this->selected_values = explode('|', $page_filter_value);
            }
            else {
                $this->selected_values = [$page_filter_value];
            }
        }
		else{ //take data as-is
            $this->selected_values = $page_filter_value;
        }
	}

	/* -----------------------------------------------------------------
	*  setDefaults() sets looks up options and sets default criteria

	*  sets default filter criteria pulled from the database.  Defaults can either be set
	*  by the default property of the filter, or the default flag of the options

	*  @since: version 1
	*  @author: ctranel
	*  @date: Oct 9, 2014
	*  @return void
	*  @throws: 
	* -----------------------------------------------------------------
	*/
	protected function setDefaults(){
		if(isset($this->selected_values) && !empty($this->selected_values)){
			return; //default not needed
		}
		
		//if default property of the filter is not set, and the filter has options, look for the default flag on options
		if(!isset($this->default_value) || empty($this->default_value)){
			if(!isset($this->options)){//no defaults to be found
				return;
			}
			if(isset($this->options) && is_array($this->options)){
				foreach($this->options as $o){
					if($o['is_default']){
						$this->default_value = $o['value'];
					}
				}
			}
		}
		if(isset($this->default_value)){
            if($this->operator === 'BETWEEN'){
                if(strpos($this->default_value, '|') !== FALSE){
                    list($this->selected_values['dbfrom'], $this->selected_values['dbto']) = explode('|', $this->default_value);
                }
                else {
                    $this->default_value = '|';
                }
                return;
            }
            elseif($this->operator === 'IN'){
                if(isset($this->default_value) && is_array($this->default_value)) {
                    $this->selected_values = $this->default_value;
                }
                if(isset($this->default_value) && !is_array($this->default_value)){
                    $this->selected_values = [$this->default_value];
                }
                if(isset($this->default_value)){
                    $this->selected_values = [];
                }
            }
            else {
                $this->selected_values = $this->default_value;
            }
		}
	}

	/**
	 * set_filter_text
	 * 
	 * Sets arr_filter_text variable.  Composes filter text property
	 * 
	 * @author ctranel
	 * @return void
	 * 
	 **/
	public function set_filter_text(){
		$this->log_filter_text = '';
		$val = array_filter($this->selected_values);
		if(is_array($val) && !empty($val)){
			if(($tmp_key = array_search('NULL', $val)) !== FALSE){
				unset($val[$tmp_key]);
			}
			//if it is a range
			elseif(key($val) === 'dbfrom' || key($val) === 'dbto'){
				if(isset($val['dbfrom']) && isset($val['dbto'])){
                    $this->log_filter_text = $this->label . ': Between ' . $val['dbfrom'] . ' and ' . $val['dbto'];
                }
			}
			else{
				$this->log_filter_text = $this->label . ': ' . implode(', ', $val);
			}
		}
		elseif(!empty($val)){
			$this->log_filter_text = $this->label . ': ' . $val;
		}
	}
}