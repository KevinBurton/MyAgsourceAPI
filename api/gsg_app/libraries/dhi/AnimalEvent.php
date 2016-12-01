<?php
namespace myagsource\dhi;

/**
* Name:  AnimalEvent
*
* Author: ctranel
*		  ctranel@agsource.com
*
* Location: na
*
* Created:  2016-10-20
*
* Description:  Library for managing animal events
*
* Requirements: PHP5 or above
*
*/

class AnimalEvent
{
	/**
	 * datasource
	 *
	 * @var datasource
	 **/
	protected $datasource;

    /**
     * herd identifier
     *
     * @var string
     **/
    protected $herd_code;

    /**
     * serial number
     *
     * @var int
     **/
    protected $serial_num;

    /**
     * eligible messages
     *
     * @var array of strings
     **/
    protected $eligible_messages;

    /**
	 * __construct
	 *
	 * @return void
	 * @author ctranel
	 **/
	public function __construct(\Events_model $datasource, $herd_code, $serial_num) {
		if(empty($herd_code) || strlen($herd_code) != 8){
			throw new \Exception('Herd could not be loaded.  No herd code passed to constructor.');
		}
		$this->herd_code = $herd_code;
		$this->serial_num = (int)$serial_num;
        $this->datasource = $datasource;
	}

    /* -----------------------------------------------------------------
     *  eligibleMessage

     *  Returns array messages regarding animals eligibility for passed event

     *  @author: ctranel
     *  @date: 2016-10-21
     *  @return: string or null
     *  @throws:
     * -----------------------------------------------------------------*/

    public function eligibleMessage(){
        if(isset($this->eligible_messages)){
            return $this->eligible_messages;
        }
        return null;
    }

    /* -----------------------------------------------------------------
     *  isValidDateChange

     *

     *  @author: ctranel
     *  @date: 2016-10-24
     *  @return: boolean
     *  @throws:
     * -----------------------------------------------------------------*/
    protected function isValidDateChange($event_id, $event_dt){
        $data = $this->datasource->eventData($event_id);

        if(!isset($data) || !is_array($data)){
            throw new \Exception("Could not find an existing event matching submitted event.");
        }
        $original_event_dt = $data['event_dt'];
        $dtEvent_dt = new \DateTime($event_dt);
        $dtOriginal_event_dt = new \DateTime($original_event_dt);

        if($dtOriginal_event_dt->format('Y-m-d') === $dtEvent_dt->format('Y-m-d')) {
            return true;
        }

        if($dtOriginal_event_dt > $dtEvent_dt){
            $early = $event_dt;
            $late = $original_event_dt;
        }
        else{
            $early = $original_event_dt;
            $late = $event_dt;
        }

        $repro_events = $this->datasource->getEventsBetweenDates($this->herd_code, $this->serial_num, $early, $late, [33,34,40,30,31,32,36,39]);
        if(!is_array($repro_events) || count($repro_events) === 0){
            return true;
        }
        return false;
    }

    /* -----------------------------------------------------------------
     *  isValidDateChange

     *

     *  @author: ctranel
     *  @date: 2016-10-24
     *  @return: boolean
     *  @throws:
     * -----------------------------------------------------------------*/
    protected function inCurrentLactation($event_dt){
        $lact_date = $this->datasource->currentLactationStartDate($this->herd_code, $this->serial_num);

        if(!isset($lact_date)){
            throw new \Exception("Could not validate current lactation date.");
        }
        $dtEvent_dt = new \DateTime($event_dt);
        $dtLact_date = new \DateTime($lact_date);

        if($dtEvent_dt < $dtLact_date){
            return false;
        }

        return true;
    }

    /* -----------------------------------------------------------------
     *  isEligible

     *

     *  @author: ctranel
     *  @date: 2016-10-20
     *  @return: boolean
     *  @throws:
     * -----------------------------------------------------------------*/

    public function isEligible($event_cd, $event_dt, $event_id = null){
        //if an event is being edited (event id is already set)
        if(isset($event_id) && !empty($event_id)){
            //if a fresh event is being edited (event is fresh or abort)
            if(in_array($event_cd, [1,2,5])){
                if(!$this->isValidDateChange($event_id, $event_dt)){
                    $this->eligible_messages[] = "Fresh event date change conflicts with existing reproductive events.";
                    return false;
                }
            }

            if(!$this->inCurrentLactation($event_dt)){
                $this->eligible_messages[] = "Cannot edit events outside of current lactation.";
                return false;
            }

            //if sold or died event is selected
            if(isset($event_id) && !empty($event_id) && in_array($event_cd, [21,22,23,24,25,26,27,28])){
                $this->eligible_messages[] = "Cannot edit sold or died events.";
                return false;
            }

        }

        $data = $this->datasource->eventEligibilityData($this->herd_code, $this->serial_num, $event_dt);
        $now = new \DateTime();
        $event_dt = new \DateTime($event_dt);
        if($data['is_active'] == false || $data['TopSoldDiedDate'] !== null){
            $this->eligible_messages[] = "Cannot enter events for inactive, sold or dead animals.";
            return false;
        }
        if($event_cd == 22 || $event_cd == 23 || $event_cd == 24 || $event_cd == 29){
            $this->eligible_messages[] = "Event entered is an internal event and cannot be keyed.";
            return false;
        }
        if($event_dt > $now){
            $this->eligible_messages[] = "Cannot enter events with date or time in the future.";
            return false;
        }
        if($data['sex_cd'] === 2 && ($event_cd < 21 && $event_cd > 28)){
            //@todo: custom events need to be allowed
            $this->eligible_messages[] = "Can only enter sold or died events for males.";
            return false;
        }

        //Fresh events
        if($event_cd == 1 || $event_cd == 2){
            $earliest = new \DateTime($data['earliest_fresh_eligible_date']);
            if($data['earliest_fresh_eligible_date'] === null){
                $this->eligible_messages[] = "This animal is not eligible for a fresh event.  Current status is " . $data['current_status'] . ", and the animal must be dry.";
            }
            elseif($event_cd == 1){
                if($data['is_youngstock']){
                    $this->eligible_messages[] = "Heifers are not eligible for the cow fresh event.";
                }
                if(isset($event_dt) && !empty($event_dt) && $earliest > $event_dt){
                    $this->eligible_messages[] = "This animal is not eligible for a fresh event until " . $data['earliest_fresh_eligible_date'] . ".  Last fresh date was " . $data['TopFreshDate'] . ".";
                }
            }
            elseif($event_cd == 2) {
                if(!$data['is_youngstock']){
                    $this->eligible_messages[] = "Mature cows are not eligible for the heifer fresh event.";
                }
                if(isset($event_dt) && !empty($event_dt) && $earliest > $event_dt){
                    $this->eligible_messages[] = "This animal is not eligible for a fresh event until " . $data['earliest_fresh_eligible_date'] . ".  Birth date is " . $data['birth_dt'] . ".";
                }
            }
        }

        //Abort events
        if($event_cd == 5){
            $earliest = new \DateTime($data['earliest_abort_eligible_date']);
            if($data['earliest_abort_eligible_date'] === null){
                if($data['is_bred'] == false){
                    $this->eligible_messages[] = "Animal has not been bred, and is not eligible for an abort event.";
                }
                else{
                    $this->eligible_messages[] = "This animal is not eligible for an abort event.  Current status is " . $data['current_status'] . ".";
                }
            }
            elseif(isset($event_dt) && !empty($event_dt) && $earliest > $event_dt){
                $this->eligible_messages[] = "This animal is not eligible for an abort event until " . $data['earliest_abort_eligible_date'] . ".  Animal was last bred on " . $data['TopBredDate'] . ".";
            }
        }

        //Dry events
        if($event_cd == 6 || $event_cd == 10){
            $earliest = new \DateTime($data['earliest_dry_eligible_date']);
            if($data['earliest_dry_eligible_date'] === null){
                $this->eligible_messages[] = "This animal is not eligible for a dry event.  Current status is " . $data['current_status'] . ".";
            }
            elseif(isset($event_dt) && !empty($event_dt) && $earliest > $event_dt){
                $this->eligible_messages[] = "This animal is not eligible for a dry event until " . $data['earliest_dry_eligible_date'] . ".  Animal last freshened on " . $data['TopFreshDate'] . ".";
            }
        }

        //Repro Eligible
        $repro_eligible_codes = [30,31,34,35,38,39,40,32,36];
        if(in_array($event_cd, $repro_eligible_codes)){
            $earliest = new \DateTime($data['earliest_repro_eligible_date']);
            if($data['earliest_repro_eligible_date'] === null){
                $this->eligible_messages[] = "This animal is not eligible for a repro event.  Current status is " . $data['current_status'] . ".";
            }
            elseif(isset($event_dt) && !empty($event_dt) && $earliest > $event_dt){
                if($data['is_youngstock']){
                    $this->eligible_messages[] = "This animal is not eligible for a reproductive event until " . $data['earliest_repro_eligible_date'] . ".  Animal was born on " . $data['birth_dt'] . ".";
                }
                else{
                    $this->eligible_messages[] = "This animal is not eligible for a reproductive event until " . $data['earliest_repro_eligible_date'] . ".  Animal last calved on " . $data['TopFreshDate'] . ".";
                }
            }
        }

        //Preg events
        if($event_cd == 33){
            $earliest = new \DateTime($data['earliest_preg_eligible_date']);
            if($data['earliest_preg_eligible_date'] === null){
                if($data['is_bred'] == false){
                    $this->eligible_messages[] = "Animal has not been bred, and is not eligible for a pregnancy event.";
                }
                else{
                    $this->eligible_messages[] = "This animal is not eligible for a pregnancy event.  Current status is " . $data['current_status'] . ".";
                }
            }
            elseif(isset($event_dt) && !empty($event_dt) && $earliest > $event_dt){
                $this->eligible_messages[] = "This animal is not eligible for a pregnancy event until " . $data['earliest_preg_eligible_date'] . ".  Animal was last bred on " . $data['TopBredDate'] . ".";
            }
        }

        //if error message is set, animal is not eligible for this event
        return (isset($this->eligible_messages) && count($this->eligible_messages) > 0) ? false : true;
    }
}
