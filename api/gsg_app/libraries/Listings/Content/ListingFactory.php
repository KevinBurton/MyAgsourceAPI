<?php

namespace myagsource\Listings\Content;

require_once(APPPATH . 'libraries/Listings/Content/Listing.php');
require_once(APPPATH . 'libraries/Listings/iListingFactory.php');
require_once(APPPATH . 'libraries/Listings/Content/Columns/ListingColumn.php');
require_once(APPPATH . 'models/Listings/herd_options_model.php');

use \myagsource\Listings\iListingFactory;
use \myagsource\Listings\Content\Columns\ListingColumn;

/**
 * A factory for listing objects
 * 
 * 
 * @name ListingFactory
 * @author ctranel
 * 
 *        
 */
class ListingFactory implements iListingFactory {
	/**
	 * datasource_blocks
	 * @var listing_model
	 **/
	protected $datasource;

	function __construct($datasource) {//, \db_field_model $datasource_dbfield
		$this->datasource = $datasource;
	}
	
	/*
	 * getListing
	 * 
	 * @param int listing id
	 * @param string herd code
	 * @author ctranel
	 * @returns \myagsource\Listing
	 */
	public function getListing($listing_id, $criteria){
		$results = $this->datasource->getListingById($listing_id);
		if(empty($results)){
			return false;
		}

		return $this->createListing($results[0], $criteria);
	}

	/*
    * createListing
    *
    * @param array of listing data
    * @param string herd code
    * @author ctranel
    * @returns Array of Listings
    */
    protected function createListing($listing_data, $criteria){
        $column_data = $this->datasource->getListingColumnMeta($listing_data['listing_id']);
        $dataset = $this->datasource->getListingData($listing_data['listing_id'], $listing_data['order_by'], $listing_data['sort_order']);//, implode(', ', array_column($column_data, 'name')));

        $lc = [];
        if(is_array($column_data) && !empty($column_data) && is_array($column_data[0])){
            foreach($column_data as $d){
                $lc[$d['name']] = new ListingColumn($d);
            }
        }

        return new Listing($listing_data['listing_id'], $listing_data['form_id'], $listing_data['delete_path'], $lc, $dataset, $listing_data['active'], $criteria);
    }

    /*
     * getByPage
     *
     * @param int page_id
         * @param string herd_code
     * @author ctranel
     * @returns array of Listing objects
     */
    public function getByPage($page_id, $criteria){//$herd_code = null, $serial_num = null){
        $listings = [];
        //unset herd code and ser num?
        $results = $this->datasource->getListingsByPage($page_id);
        if(empty($results)){
            return [];
        }

        foreach($results as $r){
            $listings[$r['list_order']] = $this->createListing($r, $criteria);
        }
        return $listings;
    }
}