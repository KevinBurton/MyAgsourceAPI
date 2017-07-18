<?php
namespace myagsource\Report\Content;

require_once APPPATH . 'libraries/Report/iReport.php';
require_once APPPATH . 'libraries/Report/Content/Sort.php';
require_once APPPATH . 'libraries/Report/Content/WhereGroup.php';
require_once APPPATH . 'libraries/Datasource/DbObjects/DbField.php';
require_once APPPATH . 'libraries/Datasource/DbObjects/DataConversion.php';

use \myagsource\Report\iReport;
use \myagsource\Datasource\DbObjects\DbField;
use \myagsource\Datasource\DbObjects\DbTableFactory;
use \myagsource\DataHandler;
use \myagsource\Supplemental\Content\SupplementalFactory;
use \myagsource\Datasource\iDataField;
use \myagsource\Filters\ReportFilters;
use \myagsource\Datasource\DbObjects\DataConversion;

/**
* Name:  Report
*
* Author: ctranel
*  
* Created:  02-02-2015
*
* Description:  Contains properties and methods specific to displaying reports of the website.
*
*/

abstract class Report implements iReport {
    /**
     * id
     * @var int
     **/
    protected $id;

    /**
	 * array of ReportField objects
	 * @var ReportField[]
	 **/
	protected $report_fields;
	
	/**
	 * array of field names to be added to the field objects
	 * @var array of strings
	 **/
	protected $addl_select_field_names;

	/**
	 * array of WhereGroup objects
	 * @var WhereGroup[]
	 **/
	protected $where_groups;
	
	/**
	 * array of Sort objects
	 * @var Sort[]
	 **/
	protected $default_sorts;
	
	/**
	 * array of Sort objects
	 * @var Sort[]
	 **/
	protected $sorts;
	
	/**
	 * iDataField object
	 * @var iDataField
	 **/
	protected $pivot_field;
	
	/**
	 * max_rows
	 * @var int
	 **/
	protected $max_rows;

	/**
	 * cnt_row
	 * @var boolean
	 **/
	protected $cnt_row;
	
	/**
	 * sum_row
	 * @var boolean
	 **/
	protected $sum_row;
	
		/**
	 * avg_row
	 * @var boolean
	 **/
	protected $avg_row;
	
	/**
	 * bench_row
	 * @var boolean
	 **/
	protected $bench_row;
	
	/**
	 * is_summary
	 * @var boolean
	 **/
	protected $is_summary;

    /**
     * is_metric
     * @var boolean
     **/
    protected $is_metric;

    /**
	 * has_aggregate
	 * @var boolean
	 **/
	protected $has_aggregate;
	
	/**
	 * filters
	 * 
	 * @var ReportFilters
	 **/
	protected $filters;
	
	/**
	 * primary_table_name
	 * @var string
	 **/
	protected $primary_table_name;

    /**
     * db_tables
     * @var string
     **/
    protected $db_tables;

    /**
	 * joins
	 * @var Joins
	 **/
	protected $joins;
	
	/**
	 * supp_param_fieldnames
	 * @var array
	 **/
	//protected $supp_param_fieldnames;
	
	/**
	 * field_groups
	 * @var numerically keyed array
	 **/
	protected $field_groups;

    /**
     * dataset
     * @var array
     **/
    protected $dataset;

    /**
     * data_handler
     * @var DataHandler
     **/
   protected $data_handler;

    /**
     * supplemental
     * @var Supplemental
     **/
    //protected $supplemental;

	/**
	 * $dataset_supplemental
	 * @var Supplemental
	 **/
	protected $dataset_supplemental;

    /**
     * $header_supplemental
     * @var Supplemental
     **/
    protected $header_supplemental;

    /**
     * supplemental_factory
     * @var SupplementalFactory
     **/
    protected $supplemental_factory;

    /**
     * db_table_factory
     * @var DbTableFactory
     **/
    protected $db_table_factory;

    //The count of rows appended to the end of a dataset's rows; the number of these rows: sum_row,avg_row,cnt_row,bench_row
	protected $appended_rows_count;
	
/**
	 * __construct
	 *
	 * @return void
	 * @author ctranel
	 **/
	public function __construct($report_datasource, $report_meta, ReportFilters $filters, SupplementalFactory $supp_factory, DataHandler $data_handler, DbTableFactory $db_table_factory, iDataField $pivot_field = null, $field_groups = null, $is_metric) {//$id, $page_id, $name, $description, $scope, $active, $path,
		$this->datasource = $report_datasource;

		$this->id = $report_meta['id'];
		$this->max_rows = $report_meta['max_rows'];
		$this->cnt_row = $report_meta['cnt_row'];
		$this->sum_row = $report_meta['sum_row'];
		$this->avg_row = $report_meta['avg_row'];
		$this->bench_row = $report_meta['bench_row'];
		$this->is_summary = $report_meta['is_summary'];
        $this->is_metric = $is_metric;
		$this->field_groups = $field_groups;
		//$this->group_by_fields = $group_by_fields;
		//$this->where_fields = $group_by_fields;
		$this->display_type = $report_meta['display_type'];
        $this->filters = $filters;
        $this->data_handler = $data_handler;
        $this->db_table_factory = $db_table_factory;
        $this->has_aggregate = false;
        $this->pivot_field = $pivot_field;
        $this->report_fields = [];

        /*
         * myagsource special case: if PAGE filters or params contain only a pstring of 0, and the report is not a summary
        * Needed for pages that contain both cow level and summary reports.
         * @todo: should extend base class with MyAgSource-specific class
        */

        if($this->filters->criteriaExists('pstring') && !$this->isSummary()){
            $p_value = $this->filters->getCriteriaValueByKey('pstring');
            if(count($p_value) === 1 && $p_value[0] === 0){
                $this->filters->removeCriteria('pstring');
            }
        }
        /* end special case */

        $this->supplemental_factory = $supp_factory;
        //$this->supplemental = $supp_factory->getReportSupplemental($this->id);
		//$this->supp_param_fieldnames = []; //used in child classes
		
		//load data for remaining properties
        $this->setReportFields();
		$this->setDBTables();
        $this->setWhereGroups();
		$this->setDefaultSort();
		$this->setJoins();
        $this->verifyFilters();

        $this->appended_rows_count = 0;
		if ($report_meta['cnt_row']) {
		    $this->appended_rows_count++;
		}
		if ($report_meta['sum_row']) {
		    $this->appended_rows_count++;
		}
		if ($report_meta['avg_row']) {
		    $this->appended_rows_count++;
		}
		if ($report_meta['bench_row']) {
		    $this->appended_rows_count++;
		}
	}
	
	/*
	 * a slew of getters
	 */
	
	public function id(){
		return $this->id;
	}
/*
	public function path(){
		return $this->site_block->path();
	}

	public function title(){
		return $this->site_block->name();
	}
*/
	public function maxRows(){
		return $this->max_rows;
	}
	public function pivotFieldName(){
		if($this->pivot_field instanceof iDataField){
            return $this->pivot_field->dbFieldName();
        }
	}

	public function primaryTableName(){
        if(!isset($this->primary_table_name)){
            $this->setDBTables();
        }
		return $this->primary_table_name;
	}

	public function sorts(){
		return $this->sorts;
	}

/*
	public function joins(){
		return $this->joins;
	}
*/
/*
    public function name(){
        return $this->site_block->name();
    }

    public function description(){
        return $this->site_block->description();
    }

    public function displayType(){
		return $this->site_block->displayType();
	}
*/
	public function subtitle(){
		return $this->filters->get_filter_text();
	}

	public function hasBenchmark(){
		return $this->bench_row;
	}

	public function isSummary(){
		return $this->is_summary;
	}

	public function fieldGroups(){
		return $this->field_groups;
	}

	public function dataset(){
		return $this->dataset;
	}

    public function hasCntRow(){
        return $this->cnt_row;
    }

    public function hasAvgRow(){
		return $this->avg_row;
	}

	public function hasSumRow(){
		return $this->sum_row;
	}
	
	public function hasPivot(){
		return isset($this->pivot_field);
	}
	
	public function reportFields(){
		return $this->report_fields;
	}
	
	public function numFields(){
		return count($this->report_fields);
	}

    public function filterKeysValues(){
        return $this->filters->criteriaKeyValue();
    }
    
	public function getFieldlistArray(){
		if(!isset($this->report_fields) || count($this->report_fields) === 0){
			return false;
		}
		$ret = [];
		foreach($this->report_fields as $f){
			$ret[] = $f->dbFieldName();
		}
		return $ret;
	}

	public function getDisplayedFieldArray(){
		if(!isset($this->report_fields) || count($this->report_fields) === 0){
			return false;
		}
		$ret = [];
		foreach($this->report_fields as $f){
			if($f->isDisplayed()){
				$ret[] = $f->displayName();
			}
		}
		return $ret;
	}

    public function keyMetaArray(){
        if(!isset($this->report_fields) || count($this->report_fields) === 0){
            return false;
        }
        $ret = [];
        foreach($this->report_fields as $f){
            if($f->isKey()){
                $ret[] = $f->toArray();
            }
        }
        return $ret;
    }

    public function getFieldLabelByName($field_name){
        if(!isset($this->report_fields) || count($this->report_fields) === 0){
            return null;
        }

        foreach($this->report_fields as $f){
            if($f->dbFieldName() == $field_name){
                return $f->displayName();
            }
        }
        return null;
    }

    public function getFieldDecimalScaleByName($field_name){
        if(!isset($this->report_fields) || count($this->report_fields) === 0){
            return null;
        }

        foreach($this->report_fields as $f){
            if($f->dbFieldName() == $field_name){
                return $f->decimalScale();
            }
        }
        return null;
    }

    public function getFieldFormatByName($field_name){
        if(!isset($this->report_fields) || count($this->report_fields) === 0){
            return null;
        }

        foreach($this->report_fields as $f){
            if($f->dbFieldName() == $field_name){
                return $f->displayFormat();
            }
        }
        return null;
    }

    public function getAppendedRowsCount(){
	    return $this->appended_rows_count;
	}
	
	/**
	 * addFieldName
	 * 
	 * Add a field name to be included in the data query.  Will not create a new series
	 * 
	 * @return void
	 * @access public
	 *
	public function addFieldName($name){
		$this->addl_select_field_names[] = $name;
	}
**/

	/**
	 * @method getOutputData
     * 
     * Returns data needed by original non-API version of site
     * 
	 * @return array of output data for block
	 * @access public
	 *
	public function getOutputData(){
		return [
			'name' => $this->site_block->name(),
			'description' => $this->site_block->name(),
			'filter_text' => $this->filters->get_filter_text(),
			'block' => $this->site_block->path(), //original program included sort_by, sort_order, graph_order but couldn't find anywhere it was used
			'block_id' => $this->site_block->id(),
		];
	}
**/

    /**
     * toArray
     *
     * @return array representation of object
     *
     **/
    public function toArray(){
        $ret['report_id'] = $this->id;
        $ret['pivot_field'] = $this->pivot_field instanceof iDataField ? $this->pivot_field->dbFieldName() : null;
        $ret['is_summary'] = $this->is_summary;
        $ret['display_type'] = $this->display_type;
//        $ret['appended_rows_count'] = $this->appended_rows_count;
//            'max_rows' => $this->max_rows,
        $ret['cnt_row'] = $this->cnt_row;
        $ret['sum_row'] = $this->sum_row;
        $ret['avg_row'] = $this->avg_row;
        $ret['bench_row'] = $this->bench_row;
//            'field_groups' => $this->field_groups,

        if(is_array($this->dataset) && !empty($this->dataset)){
            $ret['dataset'] = $this->dataset;
        }

        //MOVING SUPPLEMENTAL TO BLOCK LEVEL
        //$tmp = array_filter($this->supplemental->toArray());
        //if(is_array($tmp) && !empty($tmp)){
        //    $ret['supplemental'] = $tmp;
        //}
        //unset($tmp);

        if(is_array($this->default_sorts) && !empty($this->default_sorts)){
            $dsorts = [];
            foreach($this->default_sorts as $s){
                $dsorts[] = $s->toArray();
            }
            $ret['default_sorts'] = $dsorts;
        }

		if(is_array($this->dataset_supplemental) && !empty($this->dataset_supplemental)){
			$supp = [];
			foreach($this->dataset_supplemental as $k=>$f){
				if(isset($f)){
                    $supp[$k] = $f->toArray();
                }
			}
			$ret['dataset_supplemental'] = $supp;
		}

		if(is_array($this->report_fields) && !empty($this->report_fields)){
			$data = [];
			foreach($this->report_fields as $k=>$f){
				$data[$f->dbFieldName()] = $f->toArray();
                $key_map[] = $f->dbFieldName();
			}

            $ret['metadata'] = $data;

			if($this->hasPivot()) {
                $pdata = [];
                $tmp = current($this->dataset);
                foreach($tmp as $k => $v){
                    $pdata[$k] = [
                        'aggregate' => null,
                        'datatype' => null,
                        'db_field_name' => null,
                        'decimal_scale' => null,
                        'default_sort_order' => null,
                        'description' => null,
                        'display_format' => null,
                        'is_displayed' => 1,
                        'is_natural_sort' => 0,
                        'is_sortable' => 0,
                        'is_timespan' => 0,
                        'max_length' => null,
                        'name' => null,
                        'unit_of_measure' => null,
                    ];
                }

                $ret['metadata'] = $pdata;
            }
		}

        return $ret;
    }


    abstract protected function setReportFields();


    /**
     * @method setDBTables()
     * @return void
     * @author ctranel
     **/
    protected function setDBTables(){
        $max_cnt = 0;
        $max_table = null;

        if(is_array($this->report_fields) && count($this->report_fields) >  1){
            foreach($this->report_fields as $f){
                $tbl = $f->dbTableName();

                if(isset($this->db_tables[$tbl])){
                    $this->db_tables[$tbl]['cnt']++;
                    $max_cnt = $this->db_tables[$tbl]['cnt'];
                    $max_table = $this->db_tables[$tbl]['DBTable'];
                    continue;
                }

                $this->db_tables[$tbl] = [
                    'DBTable' => $this->db_table_factory->getTable($tbl),
                    'cnt' => 1,
                ];
                if($this->db_tables[$tbl]['cnt'] > $max_cnt){
                    $max_cnt = 1;
                    $max_table = $this->db_tables[$tbl]['DBTable'];
                }
            }

            $this->primary_table_name = $max_table->full_table_name();
        }
    }

    /**
     * @method setSupplemental()
     *
     * sets header and dataset supplemental for given field name
     *
     * @param array of field data
     *
     * @return void
     * @author ctranel
     **/
    protected function setSupplemental($data){
        //$this->header_supplemental[$data['db_field_name']] = null;
        //$this->dataset_supplemental[$data['db_field_name']] = null;

        if(isset($this->supplemental_factory)){
            if(isset($data['head_supp_id'])){
                $this->header_supplemental[$data['db_field_name']] = $this->supplemental_factory->getColHeaderSupplemental($data['head_supp_id'], $data['head_a_href'], $data['head_a_rel'], $data['head_a_title'], $data['head_a_class'], $data['head_comment']);
            }
            if(isset($data['supp_id'])){
                //@todo: this is currently being stored with report objects and the underlying field object
                $this->dataset_supplemental[$data['db_field_name']] = $this->supplemental_factory->getColDataSupplemental($data['supp_id'], $data['a_href'], $data['a_rel'], $data['a_title'], $data['a_class']);
                //$this->supp_param_fieldnames = array_unique(array_merge($this->supp_param_fieldnames, $this->dataset_supplemental[$s['db_field_name']]->getLinkParamFields()));
            }
       }
    }

    /**
	 * setWhereGroups()
	 * 
	 * @return void
	 * @author ctranel
	 * @todo: implement child/nested groups
	 **/
	protected function setWhereGroups(){
		$this->where_groups = [];
		$criteria = [];
		$arr_ret = [];
		$arr_res = $this->datasource->getWhereData($this->id, $this->is_metric);
		if(!is_array($arr_res) || empty($arr_res)){
			return;
		}
		$prev_group = $arr_res[0]['where_group_id'];
		$prev_op = $arr_res[0]['operator'];
		if(is_array($arr_res)){
			foreach($arr_res as $s){
				if($prev_group != $s['where_group_id']){
					$this->where_groups[] = new WhereGroup($prev_op, $criteria);
					$criteria = [];
				}
                $data_conversion = null;
                if(isset($s['conversion_name'])) {
                    $data_conversion = new DataConversion($s['conversion_name'], $s['metric_label'], $s['metric_abbrev'],
                        $s['to_metric_factor'], $s['metric_rounding_precision'], $s['imperial_label'], $s['imperial_abbrev'], $s['to_imperial_factor'], $s['imperial_rounding_precision']);
                }
                $criteria_datafield = new DbField($s['db_field_id'], $s['table_name'], $s['db_field_name'], $s['name'], $s['description'], $s['pdf_width'], $s['default_sort_order'],
                    $s['datatype'], $s['max_length'], $s['decimal_scale'], $s['unit_of_measure'], $s['is_timespan'], $s['is_foreign_key'], $s['is_nullable'], $s['is_natural_sort'], $data_conversion);
                $criteria[] = new WhereCriteria($criteria_datafield, $s['condition']);

				$prev_group = $s['where_group_id'];
				$prev_op = $s['operator'];
			}
			//add the last item
			$this->where_groups[] = new WhereGroup($s['operator'], $criteria);
		}
	}
	
	/**
	 * getWhereGroupArray()
	 * 
	 * @return array of (nested) where groups
	 * @author ctranel
	 **/
	public function getWhereGroupArray(){
		$ret = [];
		if(!is_array($this->where_groups) || empty($this->where_groups)){
			$this->where_groups = $this->setWhereGroups();
		}
		if(count($this->where_groups) === 0){
			return;
		}

		foreach($this->where_groups as $wg){
			$ret[] = $wg->criteria();
		}
		return $ret;
	}
	
	/**
	 * @method sortText()
	 * @return string sort text
	 * @access public
	* */
	public function sortText($is_verbose = false){
		$ret = '';
		$is_first = true;
		if(isset($this->sorts) && count($this->sorts) > 0){
			foreach($this->sorts as $s){
				$ret .= $is_verbose ? $s->sortText($is_first) : $s->sortTextBrief($is_first);
				$is_first = false;
			}
		}
		
		return $ret;
	}

	/**
	 * getSortDateFieldName()
	 *
	 * returns field-name-keyed array of sort orders
	 *
	 * @return string field name
	 * @access public
	 * */
	public function getSortDateFieldName(){
		if(isset($this->sorts) && count($this->sorts) > 0){
			foreach($this->sorts as $s){
				if($s->isDate()){
					return $s->fieldName();
				}
			}
		}
		return null;
	}

	/**
	 * getSortArray()
	 * 
	 * returns field-name-keyed array of sort orders
	 * 
	 * @return string sort text
	 * @access public
	* */
	public function getSortArray(){
		$ret = [];
		if(isset($this->sorts) && count($this->sorts) > 0){
			foreach($this->sorts as $s){
				$ret[$s->fieldName()] = $s->order();
			}
		}
		return $ret;
	}
	
	/**
	 * @method resetSort()
	 * @return void
	 * @access public
	public function resetSort(){
		if(isset($this->sorts) && count($this->sorts) > 0){
			$this->sorts->removeAll($this->sorts);
		}
	}
* */

	/**
	 * @method addSort()
	 * @param array of Sort objects
	 * @return void
	 * @access public
	public function addSort(Sort $sort){
		$this->sorts[] = $sort;
	}
* */

	/**
	 * @method setDefaultSort()
	 * @return void
	 * @author ctranel
	 **/
	protected function setDefaultSort(){
		$this->default_sorts = [];
		$arr_ret = [];
		$arr_res = $this->datasource->getSortData($this->id);
		if(is_array($arr_res)){
			foreach($arr_res as $s){
				$datafield = new DbField($s['db_field_id'], $s['table_name'], $s['db_field_name'], $s['name'], $s['description'], $s['pdf_width'], $s['default_sort_order'],
						 $s['datatype'], $s['max_length'], $s['decimal_scale'], $s['unit_of_measure'], $s['is_timespan'], $s['is_foreign_key'], $s['is_nullable'], $s['is_natural_sort'], null);
				$this->default_sorts[] = new Sort($datafield, $s['sort_order']);
			}
		}
		$this->sorts = $this->default_sorts;
	}

    /**
     * @method setJoins()
     * @return void
     * @author ctranel
     **/
    protected function setJoins(){
        if(is_array($this->db_tables) && count($this->db_tables) >  1){
            foreach($this->db_tables as $t => $cnt){
                if($t != $this->primaryTableName()){
                    $this->joins[] = array('table'=>$t, 'join_text'=>$this->get_join_text($this->primaryTableName(), $t));
                }
            }
        }
    }

    /**
     * @verifyFilters
     *
     * verifies that the filter columns exist in the tables behind the report.  Removes any that do not.
     *
     * @return void
     * @author ctranel
     **/
    protected function verifyFilters(){
        if(is_array($this->db_tables) && !empty($this->db_tables)){
            $criteria = $this->filterKeysValues();
            $criteria = array_keys($criteria);
            $cols = [];

            foreach($this->db_tables as $tname => $tobj){
                $cols = array_merge($cols, $tobj['DBTable']->columns());
            }
            $cols = array_column($cols, 'COLUMN_NAME');
            $remove = array_diff($criteria, $cols);
            if(isset($remove) && !empty($remove)){
                foreach($remove as $r){
                    $this->filters->removeCriteria($r);
                }
            }
        }
    }

    /**
	 * @method sortFieldNames()
	 * @return ordered array of field names
	 * @access public
	public function sortFieldNames(){
		$ret = [];
		if(isset($this->sorts) && count($this->sorts) > 0){
			foreach($this->sorts as $s){
				$ret[] = $s->fieldName();
			}
		}
		
		return $ret;
	}
	* */
	/**
	 * sortOrders
	 *
	 * @method sortOrders()
	 * @return ordered array of sort orders
	 * @access public
	 public function sortOrders(){
	 $ret = [];
	 if(isset($this->sorts) && count($this->sorts) > 0){
	 foreach($this->sorts as $s){
	 $ret[] = $s->order();
	 }
	 }
	
	 return $ret;
	 }
	 * */
	
	/**
	 * @method setFilters()
	 * @param Filter object
	 * @return void
	 * @access public
	public function setFilters(Filters $filters){
		$this->filters = $filters;
	}
* */
	
	/**
	 * @method getFieldTable()
	 * @param field name
	 * @return string table name
	 * @access public
	 * 
	 * @todo: change this to return tables for all fields and iterate where it is called?
	* */
	public function getFieldTable($field_name){
		if(isset($this->report_fields) && count($this->report_fields) > 0){
			foreach($this->report_fields as $f){
				if($f->dbFieldName() === $field_name){
					return $f->dbTableName();
				}
			}
		}
		return null;
	}
	
	/**
	 * @method isNaturalSort()
	 * @param field name
	 * @return boolean
	 * @access public
	 *
	 * */
	public function isNaturalSort($field_name){
		if(isset($this->report_fields) && count($this->report_fields) > 0){
			foreach($this->report_fields as $f){
				if($f->dbFieldName() === $field_name){
					return $f->isNaturalSort();
				}
			}
		}
		return false;
	}
	
	/**
	 * @method isNaturalSort()
	 * @param field name
	 * @return boolean
	 * @access public
	 *
	 * */
	public function isSortable($field_name){
		if(isset($this->report_fields) && count($this->report_fields) > 0){
			foreach($this->report_fields as $f){
				if($f->dbFieldName() === $field_name){
					return $f->isSortable();
				}
			}
		}
		return false;
	}

	/**
	 * @method getGroupBy()
	 * //@param array of GroupBy objects
	 * @return void
	 * @access public
	* */
	public function getGroupBy(){
		$ret = [];
		
		//@todo: pull in group by fields from database
		if($this->has_aggregate && isset($this->report_fields) && count($this->report_fields) > 0){
			foreach($this->report_fields as $f){
				if(!$f->isAggregate()){
					$ret[] = $f->dbFieldName();
				}
			}
		}
		return $ret;
	}
	
	public function defaultSort(){
		return $this->default_sort;
	}

	public function displayBenchRow(){
		return $this->bench_row;
	}
	
    /**
     * setDataset
     *
	 * @param string path
     * sets the objects dataset property with report data
     * @access public
     * */
    protected function setDataset($path){
        $db_table = $this->db_table_factory->getTable($this->primaryTableName());
        $tmp_path = 'libraries/DataHandlers/' . $path . '.php';
        $report_data_handler = $this->data_handler->load($this, $tmp_path, $db_table);
        $this->dataset = $report_data_handler->getData();
    }
}


