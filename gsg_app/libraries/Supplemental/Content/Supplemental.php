<?php
namespace myagsource\Supplemental\Content;

require_once(APPPATH . 'libraries/Supplemental/iSupplemental.php');
require_once(APPPATH . 'libraries/Supplemental/Content/SupplementalLink.php');
require_once(APPPATH . 'libraries/Supplemental/Content/SupplementalComment.php');

use \myagsource\Supplemental\Content\SupplementalLink;
use \myagsource\Supplemental\Content\SupplementalComment;
use \myagsource\Supplemental\iSupplemental;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* Contains properties and methods specific supplemental data links for various sections of the website.
* 
* Supplemental links can be added to any level of the content hierarchy (column data, column headers, blocks, pages or sections).
* They are links to content that is designed to be deliver within another pages as an overlay or callout
* 
* @author: ctranel
* @date: May 9, 2014
*
*/

class Supplemental implements iSupplemental
{
	/**
	 * supplemental link objects
	 * @var SupplementalLink object
	 **/
	protected $link;

	/**
	 * supplemental comment objects
	 * @var SplObjectStorage of Supplemental_comment objects
	 **/
	protected $comment;

	/**
	 * __construct
	 *
	 * @param: SplObjectStorage of supplementalLink objects
	 * @param: SplObjectStorage of supplementalComment objects
	 * @return void
	 * @author ctranel
	 **/
	public function __construct(\SplObjectStorage $links, \SplObjectStorage $comments)
	{
		$this->links = $links;
		$this->comments = $comments;
	}
	
	public function supplementalLinks(){
		return $this->links;
	}
	
	public function supplementalComments(){
		return $this->comments;
	}
	
	/* -----------------------------------------------------------------
	 * getContent
	
	*  Factory for supplemental objects for blocks
	
	*  @author: ctranel
	*  @date: Oct 28, 2014
	*  @return: Array of strings
	*  @throws:
	* -----------------------------------------------------------------*/
	
	public function getContent(){
		$arr_supplemental = [];
		if (isset($this->links) && is_object($this->links)){
			foreach($this->links as $s){
				$arr_supplemental['links'][] = $s->anchorTag();
			}
		}
		if (isset($this->comments) && is_object($this->comments)){
			foreach($this->comments as $s){
				$arr_supplemental['comments'][] = $s->comment();
			}
		}
		return $arr_supplemental;
	}

	/* -----------------------------------------------------------------
	 * getProperties

	*  Factory for supplemental objects for blocks

	*  @author: ctranel
	*  @date: Oct 28, 2014
	*  @return: Array of strings
	*  @throws:
	* -----------------------------------------------------------------*/

	public function getProperties(){
		$arr_supplemental = [];
		if (isset($this->links) && is_object($this->links)){
			foreach($this->links as $s){
				$arr_supplemental['links'][] = $s->propertiesArray();
			}
		}
		if (isset($this->comments) && is_object($this->comments)){
			foreach($this->comments as $s){
				$arr_supplemental['comments'][] = $s->propertiesArray();
			}
		}
		return $arr_supplemental;
	}

	/* -----------------------------------------------------------------
	 * getContent
	
	*  Factory for supplemental objects for blocks
	
	*  @author: ctranel
	*  @date: Oct 28, 2014
	*  @return: Array of strings
	*  @throws:
	* -----------------------------------------------------------------*/
	
	public function getLinkParamFields(){
		$arr_supplemental = [];
		if(isset($this->links) && is_object($this->links)){
			foreach($this->links as $s){
				$params = $s->params();
				if(isset($params) && $params->count() > 0){
					foreach($params as $p){
						$db_field_name = $p->value_db_field_name();
						if(isset($db_field_name) && !empty($db_field_name)){
							$arr_supplemental[] = $db_field_name;
						}
					}
				}
			}
		}
		return $arr_supplemental;
	}
}