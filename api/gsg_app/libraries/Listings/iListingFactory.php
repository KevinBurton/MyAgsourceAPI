<?php
namespace myagsource\Listings;

/**
 * Created by PhpStorm.
 * User: ctranel
 * Date: 9/27/2016
 * Time: 2:31 PM
 */



interface iListingFactory
{
    public function getListing($form_id, $criteria);
    public function getByPage($page_id, $criteria);
}