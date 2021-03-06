<?php

namespace myagsource\Report\Content\Chart;

require_once APPPATH . 'libraries/Report/Content/ReportData.php';

use \myagsource\Report\Content\ReportData;

/**
 * Name:  ChartData
 *
 * Author: ctranel
 *
 * Created:  04-29-2015
 *
 * Description: Data handler for chart reports
 *
 * accommodates only 1 x axes field
 *
 */
class ChartData extends ReportData{
	/**
	FROM PARENT CLASS:
	protected $report;
	protected $report_datasource;
	protected $dataset;
	**/	
	
	/**
	 * categories
	 *
	 * @var Array
	protected $categories;
	 **/
	
	/**
	 * x_axis
	 *
	 * @var XAxis
	 **/
	protected $x_axis;
	
	/**
	 * @todo: add filter data
	 */
	function __construct(Chart $report, \Report_data_model $report_datasource, $is_metric) {
		parent::__construct($report, $report_datasource, $is_metric);
		$this->setXAxesField();
	}

	/* -----------------------------------------------------------------
	*  setCategories

	*  Sets category properties for chart data object

	*  @author: ctranel
	*  @date: Apr 29, 2015
	*  @return void
	*  @throws: 
	* -----------------------------------------------------------------
	*/
	protected function setXAxesField(){
		foreach($this->report->xAxes() as $x){
				$this->x_axis = $x;
		}
	}
	
	/**
	 * @method getData()
	 * @param array - key-value pairs for criteria
	 * @return array of data for the chart
	 * @access public
	 *
	 *@todo: criteria param should be removed when filter data is included in object
	 **/
	public function getData(){
        $criteria_key_value = $this->report->filterKeysValues();
        if(isset($this->x_axis)){
            $x_axis_dbfield_name = $this->x_axis->dbFieldName();
        }

		$criteria_key_value = $this->whereCriteria($criteria_key_value);
		$select_fields = $this->selectFields($this->is_metric);
		$this->dataset = $this->report_datasource->search($this->report, $select_fields, $criteria_key_value);

		$tmp_cat = $this->report->categories();

		//categories
		if(isset($tmp_cat) && is_array($tmp_cat) && !empty($tmp_cat)){
			$this->splitByCategories();
			
			//field groups
			$tmp_fg = $this->report->fieldGroups();
			if(isset($tmp_fg) && is_array($tmp_fg) && !empty($tmp_fg)){
				$this->concatFieldGroups($tmp_cat);
			}
			else{
				$this->stripFieldNames();
			}
		}
		//linear
		else{
			$this->splitLinearData($this->report->keepNulls());
			
			//field groups
			$tmp_fg = $this->report->fieldGroups();
			if(isset($tmp_fg) && is_array($tmp_fg) && !empty($tmp_fg)){
				$this->concatFieldGroups();
			}
			else{
				$this->stripFieldNames();
			}
		}
		return $this->dataset;
	}
	
	/**
	 * @method splitLinearData()
	 * @param string date field used on graph (test_date)
	 * @return array of data for the graph
	 * @access protected
	 *
	 **/
	protected function splitLinearData($keep_nulls = true){
		$count = count($this->dataset);
		$x_val = 0;
        if(isset($this->x_axis)){
            $x_axis_dbfield_name = $this->x_axis->dbFieldName();
        }
		$tmp_fg = $this->report->fieldGroups();
        $has_field_groups = (isset($tmp_fg) && !empty($tmp_fg));
		unset($tmp_fg);

		for($x = 0; $x < $count; $x++){
			$ser_key = 1;
			$arr_y_values = $this->dataset[$x];
			$arr_fields = array_keys($arr_y_values);
			$date_key = isset($x_axis_dbfield_name) ? array_search($x_axis_dbfield_name, $arr_fields) : null;
			if(isset($arr_fields[$date_key]) && !empty($date_key)){
				unset($arr_fields[$date_key]);
			}
			//if no x axis (pie charts)
			if(!isset($x_axis_dbfield_name)){
				$x_val++;
			}
			//@todo: use field meta data rather than hard-coding "age_months"?
			elseif($x_axis_dbfield_name == 'age_months' || strpos($x_axis_dbfield_name, 'dim') !== false){
				$x_val = $this->dataset[$x][$x_axis_dbfield_name];
			}
			elseif(isset($this->dataset[$x][$x_axis_dbfield_name]) && !empty($this->dataset[$x][$x_axis_dbfield_name])){
				$arr_d = explode('-', $this->dataset[$x][$x_axis_dbfield_name]);
				$x_val = (mktime(0, 0, 0, $arr_d[1], $arr_d[2],$arr_d[0]) * 1000);
			}
			else{ //can't plot without an x axes value, remove row and move on
                unset($this->dataset[$x]);
                continue;
            }
			foreach($arr_fields as $k=>$f){
			    $tmp_data = is_numeric($this->dataset[$x][$f]) ? (float)$this->dataset[$x][$f] : $this->dataset[$x][$f];
				if($keep_nulls === true || isset($tmp_data)) {
   				    $tmp_data = is_numeric($this->dataset[$x][$f]) ? (float)$this->dataset[$x][$f] : $this->dataset[$x][$f];
   					if($has_field_groups){
   						$arr_return[$ser_key][$x_val][$f] = [
	   						$x_val,
	   						$tmp_data
	   					];
   					}
   					else{
   						$arr_return[$ser_key][$x_val] = [
	   						$x_val,
	   						$tmp_data
	   					];
   					}
				}
   				$ser_key++;
			}
		}
		if(isset($arr_return) && is_array($arr_return)){
		//if there is a null value for the first series, subsequent series' will be in the first position of the array.  Need to sort by series index key
    		ksort($arr_return);
		    $this->dataset = $arr_return;
		}
		else return FALSE;
	}

		
	/**
	 * @method splitByCategories - used when data for multiple categories are returned in one row.  
	 * Breaks data down so that there is one row per category, each row having one entry for each series.
	 * 
	 * @return array of data for the graph
	 * @access protected
	 *
	 **/
	
	protected function splitByCategories(){
		$mod_base = (int)($this->report->numFields() /count($this->report->categories()));//
		if(is_array($this->dataset) && !empty($this->dataset)){
			$ser_key = 0;
			$prev_cat = '';
			foreach($this->dataset as $k=>$row){
				//must account for multiple series being returned in a single row
				$categories = [];
				$cat_idx = 0;
				foreach($this->report->reportFields() as $kk => $f){
					//get the position of the category, 0 indexed
					$cat = $f->categoryId();
					if(!array_key_exists($cat, $categories)){
						$categories[$cat] = $cat_idx;
						$cat_idx++;
					}
					$x_val = $categories[$cat];
					
					//When changing to new category, reset ser_key counter
					if($cat !== $prev_cat){
						$ser_key = 1;
					}
					else{
						$ser_key++;
					}
					$prev_cat = $cat;
					$arr_return[$ser_key][$x_val][$f->dbFieldName()] = $row[$f->dbFieldName()];
				}
			}
			$this->dataset = $arr_return;
		}
		else return FALSE;
	}

	/**
	 * @method concatFieldGroups - used when data for multiple categories are returned in one row.  
	 * Breaks data down so that there is one row per category, each row having one entry for each series.
	 * 
	 * @return array of data for the graph
	 * @access protected
	 *
	 * @todo: needs to handle time 
	 **/
	
	protected function concatFieldGroups($categories = null){
		if(!is_array($this->dataset) || empty($this->dataset)){
			return false;
		}

        if(isset($this->x_axis)){
			$x_axis_dbfield_name = $this->x_axis->dbFieldName();
		}
		$arr_return = [];
		foreach($this->dataset as $series=>$rows){
			foreach($rows as $x_val=>$v){
				$field_definitions = $this->report->reportFields();
				foreach($field_definitions as $f){
					if(!isset($v[$f->dbFieldName()])){
						continue;
					}
					$ser_idx = $f->fieldGroup();
					$ref_key = $f->fieldGroupRefKey();
					if(isset($ref_key)){
						//if the value is an array, we assume [xValue, yValue].  We want the yValue
						$tmp_val = is_array($v[$f->dbFieldName()]) ? $v[$f->dbFieldName()][1] : $v[$f->dbFieldName()];
						if(!isset($x_axis_dbfield_name) && !isset($categories)){ //no x axis nor categories means only one series (pie chart), don't need a to include series
							$arr_return[$x_val][$ref_key] = $tmp_val;
						}
						else{
							$arr_return[$ser_idx][$x_val][$ref_key] = $tmp_val;
						}
					}
					else{
						$arr_return[$ser_idx][$x_val][] = $v[$f->dbFieldName()][1];
					}
				}
				//if the xaxis is ser, and the x value is not included in the data, add it in
				if(isset($x_axis_dbfield_name) && array_key_exists('x', $arr_return[$ser_idx][$x_val]) === false){
					$arr_return[$ser_idx][$x_val]['x'] = $x_val;
				}
			}
		}
		$this->dataset = $arr_return;
		$this->stripFieldNames(isset($x_axis_dbfield_name));
	}
	
	/**
	 * stripFieldNames
	 * 
	 * Removes text keys from dataset recursively (keys become numeric).
	 * 
	 * @return array of data for the graph
	 * @access protected
	 *
	 **/
	protected function stripFieldNames($has_x_axis = true){
		if(!isset($this->dataset) || !is_array($this->dataset)){
			return false;
		}
		$this->dataset = array_values_recursive($this->dataset, $has_x_axis);
	}
}

//utility function outside of class.  See todo below
/**
	 * Get all values from a multidimensional array
	 *
	 * @param $arr array
	 * @return null|string|array
	 * @todo -- build into class for dataset object?
	 */
	function array_values_recursive(array $arr, $has_x_axis = true){
//$k = key($arr);
		$arr = array_values($arr);
		foreach($arr as $key => $val){
			if(is_array($val)){//array_values($val) === $val){
//if(isset($val['x'])) echo "$k: " . $val['x'] . "<br>\n";
//else echo "$k: x not set<br>\n";
				if(!isset($val['x']) && $has_x_axis){
					$arr[$key] = array_values_recursive($val, $has_x_axis);
				}
				else{
					$arr[$key] = $val;
				}
			}
		}
		return $arr;
	}
?>
