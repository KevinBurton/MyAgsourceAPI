<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Navigation_model extends CI_Model {
	public function __construct(){
		parent::__construct();
	}

	/**
	 * return array of sections to which herd is subscribed (child sections if a parent section is specified)
	 * 
	 * subscription is different in that it fetches content by herd data (i.e. herd output) for users that 
	 * have permission only for subscribed content.  All other scopes are strictly users-based
	 * 
	 * @return array of section and page data
	 * @author ctranel
	 **/
	public function getAllContent($herd_code) {
//order by parent_id, list_order
//don't need scope name here
		$sql = "
			SELECT * FROM (
				SELECT 999999 AS id, p.section_id AS parent_id, p.name, p.description, ls.name AS scope, p.path, p.route, p.isactive, p.path AS default_page_path, p.list_order
				FROM users.dbo.v_pages p
					INNER JOIN users.dbo.lookup_scopes ls ON p.scope_id = ls.id
				WHERE p.isactive = 1 AND (p.herd_code IS NULL OR p.herd_code = '" . $herd_code . "')
			
				UNION ALL
						
				SELECT s.id, s.parent_id, s.name, s.description, ls.name AS scope, s.path, NULL AS route, s.isactive, s.default_page_path, s.list_order
				FROM users.dbo.sections s
					INNER JOIN users.dbo.lookup_scopes ls ON s.scope_id = ls.id
				WHERE s.isactive = 1
			) a
			ORDER BY parent_id, list_order
		";
		
		$tmp_arr_sections = $this->db
		->query($sql)
		->result_array();

		return $tmp_arr_sections;
	}

	/**
	 * getContentByScope
	 * 
	 * @param array $scope names
	 * @return array of section and page data for given user
	 * @author ctranel
	 **/
	public function getContentByScope($scopes, $herd_code) {
		if(empty($scopes)){
			return false;
		}
		if(!is_array($scopes)){
			$scopes = [$scopes];
		}
		$scope_text = "'" . implode("','", $scopes) . "'";
		$sql = "
			WITH section_tree AS
			(
				SELECT id, parent_id, name, description, scope_id, path, isactive, default_page_path, list_order
				FROM users.dbo.sections
				WHERE id IN(
					SELECT DISTINCT p.section_id
						FROM users.dbo.v_pages p
						INNER JOIN users.dbo.lookup_scopes ls ON p.scope_id = ls.id
						WHERE p.isactive = 1 AND ls.name IN(" . $scope_text . ")
				)
						
				UNION ALL
						
				SELECT s.id, s.parent_id, s.name, s.description, s.scope_id, s.path, s.isactive, s.default_page_path, s.list_order
				FROM users.dbo.sections s
					JOIN section_tree st ON st.parent_id = s.id   
			)
						
			SELECT DISTINCT a.*, ls.name AS scope FROM (
				SELECT id, parent_id, name, description, scope_id, path, null AS route, isactive, default_page_path, list_order
				FROM section_tree
						
				UNION
						 
				SELECT 999999 AS id, p.section_id AS parent_id, p.name, p.description, p.scope_id, p.path, p.route, p.isactive, p.path, p.list_order
				FROM users.dbo.v_pages p
					INNER JOIN users.dbo.lookup_scopes ls ON p.scope_id = ls.id
				WHERE p.isactive = 1 AND ls.name IN(" . $scope_text . ") AND (herd_code IS NULL OR herd_code = '" . $herd_code . "')
			) a
						
			INNER JOIN users.dbo.lookup_scopes ls ON a.scope_id = ls.id
			ORDER BY list_order
		";
		
		$tmp_arr_sections = $this->db
		->query($sql)
		->result_array();

		return $tmp_arr_sections;
	}

	/**
	 * getSubscribedContent
	 * 
	 * subscription is different in that it fetches content by herd data (i.e. herd output) for users that 
	 * have permission only for subscribed content.  All other scopes are strictly users-based
	 * 
	 * @param string $herd_code
	 * @return array of section and page data for given herd
	 * @author ctranel
	 **/
	public function getSubscribedContent($herd_code) {
		$sql = "
			WITH section_tree AS (
				SELECT id, parent_id, name, description, scope_id, path, isactive, default_page_path, list_order
				FROM users.dbo.sections
				WHERE id IN(
					SELECT DISTINCT p.section_id
					FROM users.dbo.v_pages p
						INNER JOIN users.dbo.pages_dhi_products pr ON p.id = pr.page_id AND p.isactive = 1 AND p.scope_id = 2
						INNER JOIN users.dbo.v_user_status_info si ON pr.report_code = si.report_code AND si.herd_code = '" . $herd_code . "' AND (si.herd_is_paying = 1 OR si.herd_is_active_trial = 1)
				)
		
				UNION ALL
		
				SELECT s.id, s.parent_id, s.name, s.description, s.scope_id, s.path, s.isactive, s.default_page_path, s.list_order
				FROM users.dbo.sections s
					JOIN section_tree st ON st.parent_id = s.id   
			)
			
			SELECT a.*, ls.name AS scope FROM (
				SELECT DISTINCT id, parent_id, name, description, scope_id, path, NULL AS route, isactive, default_page_path, list_order
				FROM section_tree
			
				UNION
			 
				SELECT 999999 AS id, section_id AS parent_id, name, description, scope_id, path, p.route, isactive, path, list_order
				FROM users.dbo.v_pages p
					INNER JOIN users.dbo.pages_dhi_products pr ON p.id = pr.page_id AND p.isactive = 1 AND p.scope_id = 2  AND (p.herd_code IS NULL OR p.herd_code = '" . $herd_code . "')
					INNER JOIN users.dbo.v_user_status_info si ON pr.report_code = si.report_code AND si.herd_code = '" . $herd_code . "' AND (si.herd_is_paying = 1 OR si.herd_is_active_trial = 1)
			) a
			
			INNER JOIN users.dbo.lookup_scopes ls ON a.scope_id = ls.id
			ORDER BY list_order";
		
		$tmp_arr_sections = $this->db
		->query($sql)
		->result_array();

		return $tmp_arr_sections;
	}
}
