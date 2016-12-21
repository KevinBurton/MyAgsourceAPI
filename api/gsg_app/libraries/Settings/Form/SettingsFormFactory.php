<?php

namespace myagsource\Settings\Form;

require_once(APPPATH . 'libraries/Settings/SettingForm.php');
require_once(APPPATH . 'libraries/Settings/SettingFormControl.php');
//require_once(APPPATH . 'libraries/Form/Content/Form.php');
//require_once(APPPATH . 'libraries/Form/Content/Control/FormControl.php');
//require_once(APPPATH . 'libraries/Form/Content/SubForm.php');
require_once(APPPATH . 'libraries/Form/iFormFactory.php');
require_once(APPPATH . 'libraries/Validation/Input/Validator.php');
require_once(APPPATH . 'models/Forms/iForm_Model.php');

use \myagsource\Form\iFormFactory;
use \myagsource\Form\Content\SubForm;
use \myagsource\Supplemental\Content\SupplementalFactory;
use \myagsource\Settings\SettingForm;
use \myagsource\Settings\SettingFormControl;
use myagsource\Validation\Input\Validator;

/**
 * A factory for form objects
 * 
 * 
 * @name Forms
 * @author ctranel
 * 
 *        
 */
class SettingsFormFactory implements iFormFactory {
	/**
	 * datasource_blocks
	 * @var form_model
	 **/
	protected $datasource;

    /**
     * params for identify data that populates form
     * @var array
     **/
    protected $key_params;

    function __construct(\iForm_Model $datasource, SupplementalFactory $supplemental_factory = null, $key_params = null) {//, \db_field_model $datasource_dbfield
		$this->datasource = $datasource;
        $this->key_params = $key_params;
	}

    /*
     * getByPage
     *
     * @param int page_id
         * @param string herd_code
         * @param int user_id
     * @author ctranel
     * @returns \myagsource\Page\Content\FormBlock\FormBlock[]
     */
    public function getByPage($page_id, $herd_code = null, $user_id = null){
        $forms = [];
        $results = $this->datasource->getFormsByPage($page_id);
        if(empty($results)){
            return [];
        }

        foreach($results as $r){
            $forms[$r['list_order']] = $this->createForm($r, $user_id, $herd_code);
        }
        return $forms;
    }

    /*
     * getForm
     *
     * @param int form id
     * @param int user id
     * @param string herd_code
     * @author ctranel
     * @returns \myagsource\Settings\SettingForm
     */
	public function getForm($form_id, $herd_code, $user_id = null){
		$results = $this->datasource->getFormById($form_id);
		if(empty($results)){
			return false;
		}

		return $this->createForm($results[0], $user_id, $herd_code);
	}
	
    /*
     * createForm
     *
     * @param array form data
	 * @param int user id
	 * @param string herd_code
     * @author ctranel
     * @returns \myagsource\Settings\SettingForm
     */
    protected function createForm($form_data, $user_id, $herd_code, $ancestor_form_ids = null){
        $subforms = $this->getSubForms($form_data['form_id'], $user_id, $herd_code, $ancestor_form_ids);
        $control_data = $this->datasource->getFormControlData($form_data['form_id'], $this->key_params, $ancestor_form_ids);

        $fc = [];
        if(is_array($control_data) && !empty($control_data) && is_array($control_data[0])){
            foreach($control_data as $d){
                $validators = null;
                if(isset($d['validators'])){
                    $validators = [];
                    $valids = explode('|', $d['validators']);
                    foreach($valids as $v){
                        list($name, $comparison_value) = explode(':', $v);
                        $validators[] = new Validator($name, $comparison_value);
                    }
                }

                $sf = isset($subforms[$d['name']]) ? $subforms[$d['name']] : null;
                $options = null;
                if(strpos($d['control_type'], 'lookup') !== false){
                    $options = $this->getLookupOptions($d['id'], $d['control_type']);
                }
                $fc[] = new SettingFormControl($d, $validators, $options, $sf);
            }
        }
        return new SettingForm($form_data['form_id'], $this->datasource, $fc, $form_data['dom_id'], $form_data['action'],$user_id, $herd_code);
    }

    protected function getSubForms($parent_form_id, $user_id, $herd_code, $ancestor_form_ids = null){
        $results = $this->datasource->getSubFormsByParentId($parent_form_id); //would return control-name-indexed array

        if(empty($results)){
            return false;
        }

        if(is_array($ancestor_form_ids)){
            $ancestor_form_ids = $ancestor_form_ids + [$parent_form_id];
        }
        else{
            $ancestor_form_ids = [$parent_form_id];
        }

        $subforms = [];

        //get and organize all condition data for form
        $subform_data = $this->structureSubFormCondData($results);

        //parse each subform separately
        foreach($results as $k => $r){
            if(!isset($subforms[$r['form_control_name']][$r['form_id']])){
                $form = $this->createForm($r, $herd_code, $ancestor_form_ids);
                $subform_groups = $this->extractConditionGroups($subform_data[$r['form_control_name']][$r['form_id']]);
                $subforms[$r['form_control_name']][$r['form_id']] = new SubForm($subform_groups, $form);
            }
        }

        return $subforms;
    }

    /*
    * extractConditionGroups
     *
    *
    * @param array of hierarchical subform condition data
    * @author ctranel
    * @returns Array condition group objects keyed by group id
    */
    protected function extractConditionGroups($condition_data){
        if(!isset($condition_data) || !is_array($condition_data)){
            return;
        }

        $ret = [];

        foreach($condition_data['groups'] as $grp_id => $grp){
            $subgroups = null;
            $conditions = null;

            if(isset($grp['conditions']) && is_array($grp['conditions']) && !empty($grp['conditions'])) {
                foreach($grp['conditions'] as $cond_id => $cond) {
                    //$ret[$cond['form_control_name']][$cond['form_id']][$cond['group_id']][$cond['condition_id']] = new SubFormCondition($cond['operator'], $cond['operand']);
                    $conditions[] = new SubFormCondition($cond['operator'], $cond['operand']);
                }
            }
            if (isset($grp['groups']) && is_array($grp['groups']) && !empty($grp['groups'])) {
                $subgroups = $this->extractConditionGroups($grp);


            }
            $ret[$grp_id] = new SubFormConditionGroup($grp['group_operator'], $subgroups, $conditions);
        }

        return $ret;
    }

    /*
    * structureSubFormCondData
     *
     * parses flat data from datasource into a hierarchical structure of condition groups with control name and form id as keys
    *
    * @param array condition data
    * @author ctranel
    * @returns array of hierarchical subform condition data
    */
    protected function structureSubFormCondData($condition_data){
        if(!isset($condition_data) || !is_array($condition_data)){
            return;
        }

        $conditions_data = [];

        foreach($condition_data as $k=>$v){
            if(isset($v['group_parent_id']) && !empty($v['group_parent_id'])){
                $parent_id = $v['group_parent_id'];
                $v['group_parent_id'] = null;
                $conditions_data[$v['form_control_name']][$v['form_id']]['groups'][$parent_id]['groups'][$v['group_id']]['conditions'][$v['condition_id']]
                    = $this->structureSubFormCondData([$v], $v['group_operator'])[$v['form_control_name']][$v['form_id']]['groups'][$v['group_id']]['conditions'][$v['condition_id']];
                $conditions_data[$v['form_control_name']][$v['form_id']]['groups'][$parent_id]['groups'][$v['group_id']]['group_operator'] = $v['group_operator'];

                //need to get the group operator from the parent group
                $parent_key = array_search($parent_id, array_column($condition_data, 'group_id'));
                $conditions_data[$v['form_control_name']][$v['form_id']]['groups'][$parent_id]['group_operator'] = $condition_data[$parent_key]['group_operator'];
            }
            else{
                $conditions_data[$v['form_control_name']][$v['form_id']]['groups'][$v['group_id']]['conditions'][$v['condition_id']] = $v;
                $conditions_data[$v['form_control_name']][$v['form_id']]['groups'][$v['group_id']]['group_operator'] = $v['group_operator'];
            }
        }

        return $conditions_data;
    }


    /* -----------------------------------------------------------------
*  getLookupOptions

*  Returns all options

*  @since: version 1
*  @author: ctranel
*  @date: Jun 26, 2014
*  @param: string setting name
*  @return array of key=>value pairs
*  @throws:
* -----------------------------------------------------------------
*/
    protected function getLookupOptions($control_id, $control_type){
        if(strpos($control_type, 'lookup') === false){
            return false;
        }

        if(strpos($control_type, 'data_lookup') !== false){
            $options = $this->datasource->getLookupOptions($control_id);
        }
        $herd_code = isset($this->key_params['herd_code']) ? $this->key_params['herd_code'] : null;
        if(strpos($control_type, 'herd_lookup') !== false && isset($herd_code)){
            $options = $this->datasource->getHerdLookupOptions($control_id, $herd_code);
        }
        $serial_num = isset($this->key_params['serial_num']) ? $this->key_params['serial_num'] : null;
        if(strpos($control_type, 'animal_lookup') !== false && isset($herd_code) && isset($serial_num)){
            $options = $this->datasource->getAnimalLookupOptions($control_id, $herd_code, $serial_num);
        }
        $ret = [];

        if(isset($options) && is_array($options) && !empty($options)){
            $keys = array_keys($options[0]);
            foreach($options as $o){
                //if(isset($o['value'])){
                $ret[] = ['value' => $o[$keys[0]], 'label' => $o[$keys[1]]];
                //}
                //else{
                //    $this->options[] = ['value' => $o['key_value'], 'label' => $o['description']];
                //}
            }
        }

        return $ret;
    }
}