<?php
/**
 * Created by PhpStorm.
 * User: ctranel
 * Date: 8/22/2016
 * Time: 11:34 AM
 */

namespace myagsource\Form\Content;

require_once APPPATH . 'libraries/Form/iSubForm.php';

use myagsource\Form\iSubForm;
use myagsource\Form\iForm;

class SubForm implements iSubForm
{
    /**
     * @var string
     */
    protected $operator;

    /**
     * @var string
     */
    protected $operand;

    /**
     * @var iForm
     */
    protected $form;

    public function __construct($operator, $operand, iForm $form)
    {
        $this->operator = $operator;
        $this->operand = $operand;
        $this->form = $form;
    }
    
    public function toArray(){
        return [
            'operator' => $this->operator,
            'operand' => $this->operand,
            'form' => $this->form->toArray(),
        ];
    }
    
    public function write($form_data)    {
        $this->form->write($form_data);
    }
}