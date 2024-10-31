<?php
require_once ROVER_IDX_PLUGIN_PATH.'rover-content.php';

class Rover_IDX_Dashboard
	{

	public				$dyn_meta			= null;

	function __construct() {


		}

	public function dashboard_active_summary()	{

		$rover_content				= Rover_IDX_Content::rover_content(
															'ROVER_COMPONENT_DASHBOARD_ACTIVE_LISTINGS'
															);
		echo $rover_content['the_html'];

		}

	public function dashboard_activity()	{

		$rover_content				= Rover_IDX_Content::rover_content(
															'ROVER_COMPONENT_DASHBOARD_ACTIVITY'
															);
		echo $rover_content['the_html'];

		}

	public function dashboard_mail()	{

		$rover_content				= Rover_IDX_Content::rover_content(
															'ROVER_COMPONENT_DASHBOARD_MAIL'
															);
		echo $rover_content['the_html'];

		}
	}

global $rover_idx_dashboard;
$rover_idx_dashboard	= new Rover_IDX_Dashboard();
?>