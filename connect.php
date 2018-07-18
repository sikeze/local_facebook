<?php 
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/*
*
*
* @package    local
* @subpackage facebook
* @copyright  2013 Francisco García Ralph (francisco.garcia.ralph@gmail.com)
* @copyright  2015 Mihail Pozarski (mipozarski@alumnos.uai.cl)
* @copyright  2015-2016 Hans Jeria (hansjeria@gmail.com)
* @copyright  2016 Mark Michaelsen (mmichaelsen678@gmail.com)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once (dirname(dirname(dirname(__FILE__)))."/config.php");
require_once ($CFG->dirroot."/local/facebook/locallib.php");
require_once ($CFG->dirroot."/local/facebook/forms.php");
require_once ($CFG->dirroot . "/local/facebook/app/Facebook/autoload.php");
use Facebook\FacebookResponse;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequire;
global $DB, $USER, $CFG; 

define('FACEBOOK_STATUS_LINKED', 1);

$connect = optional_param("code", null, PARAM_RAW);
$disconnect = optional_param ("disconnect", null, PARAM_TEXT );

require_login ();

// URL for current page
$url = new moodle_url("/local/facebook/connect.php");

$context = context_system::instance ();
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout("standard");
$PAGE->set_title(get_string("connecttitle", "local_facebook"));
$PAGE->navbar->add(get_string("facebook", "local_facebook"));
echo $OUTPUT->header ();

// gets all facebook information needed
$config = array (
		"app_id" => $CFG->fbk_appid,
		"app_secret" => $CFG->fbk_scrid,
		"default_graph_version" => "v2.5"
);
$facebook = new Facebook\Facebook($config);

$helper = $facebook->getRedirectLoginHelper();

// Search if the user have linked with facebook
$userinfo = $DB->get_record ( 'facebook_user', array(
		'moodleid' => $USER->id,
		'status' => FACEBOOK_STATUS_LINKED
));

$time = time ();
// Look if the user has accepted the permissions
// if by looking the facebook_id is 0, that means the user hasn't accepted it.
// if the status is 0 is because the user has unlink the facebook account and if the $user_info is null is because the user hasn't link the account yet.
// if any of these things happend it will give the user the option to link the account
if(isset($userinfo->status)){
	// If the user press the unlink account
	if($disconnect != NULL){

		// Save all the user info but with status 0
		$record = new stdClass ();
		$record->id = $userinfo->id;
		$record->moodleid = $USER->id;
		$record->facebookid = $userinfo->facebookid;
		$record->timemodified = $time;
		$record->status = 0;
		$record->lasttimechecked = $time;

		// Update the DB to deactivate the account.
		$DB->update_record("facebook_user", $record );
		echo $OUTPUT->heading(get_string("succesfullconnect", "local_facebook"), 3)
		."<a href='../../'>".get_string ( 'back', 'local_facebook' )."</a>";

	}else if($userinfo->firstname == "NULL"){
		
		$sqlfilteruser = "SELECT fu.facebookid,
				u.firstname,
				u.lastname,
				fu.link,
				fu.middlename
				FROM {facebook_user} AS fu INNER JOIN {user} AS u ON (fu.moodleid = u.id)
				WHERE fu.moodleid = ?";
		
		$information = new stdClass();
		
		if( $datauser = $DB->get_records_sql($sqlfilteruser,array($USER->id)) ){
			foreach($datauser as $data){
				$information->facebookid = $data->facebookid;
				$information->link = "https://www.facebook.com/app_scoped_user_id/".$data->facebookid."/";
				$information->firstname = $data->firstname;
				$information->middlename = "";
				$information->lastname = $data->lastname;
			}
		}
		
		//Tesis Roberto Jaunez
		if($USER->id == 10644 || $USER->id == 2 || $USER->id == 40214  || $USER->id == 381 || $USER->id == 60246 || $USER->id == 32806 || $USER->id == 28988){
			$toprow = array();
			$toprow[] = new tabobject("Tu cuenta", new moodle_url('/local/facebook/connect.php'), "Tu cuenta");
			$toprow[] = new tabobject("Facebook Analysis", new moodle_url('/local/facebook/facebookalgorithm.php'), "Facebook Analysis");
			echo $OUTPUT->tabtree($toprow, "Tu cuenta");
		}
		
		echo $OUTPUT->heading(get_string("connectheading", "local_facebook"));

		$table = facebook_connect_table_generator (
				$information->facebookid,
				$information->link,
				$information->firstname,
				$information->middlename,
				$information->lastname
		);

		$button = new buttons ();
		$button->display ();
	} else{
		$facebook_id = $userinfo->facebookid;
		$status = $userinfo->status;
		echo $OUTPUT->heading(get_string("connectheading", "local_facebook"));
		
		//Tesis Roberto Jaunez
		if($USER->id == 10644 || $USER->id == 2 || $USER->id == 40214  || $USER->id == 381 || $USER->id == 60246 || $USER->id == 32806 || $USER->id == 28988){
			$toprow = array();
			$toprow[] = new tabobject("Tu cuenta", new moodle_url('/local/facebook/connect.php'), "Tu cuenta");
			$toprow[] = new tabobject("Facebook Analysis", new moodle_url('/local/facebook/facebookalgorithm.php'), "Facebook Analysis");
			echo $OUTPUT->tabtree($toprow, "Tu cuenta");
		}
		
		$table = facebook_connect_table_generator (
			$userinfo->facebookid,
			$userinfo->link,
			$userinfo->firstname,
			$userinfo->middlename,
			$userinfo->lastname
		);
		
		$button = new buttons ();
		$button->display ();
	}
	// If the user hasn't accepted the permissions
}else if(!isset($facebook_id) && $connect == NULL ){

	echo $OUTPUT->heading(get_string("acountconnect","local_facebook"));

	$params = ["email",
			"publish_actions",
			"user_birthday",
			"user_tagged_places",
			"user_hometown",
			"user_likes",
			"user_friends"
	];
	$loginUrl = $helper->getLoginUrl(($CFG->wwwroot."/local/facebook/connect.php"), $params );

	echo "<br><center><a href='" . htmlspecialchars($loginUrl) . "'><img src='app/images/login.jpg'width='180' height='30'></a><center>";
}else{

	// If he clicked the link button.
if($connect != NULL){
		// Facebook code to search the user information.
		// We have a user ID, so probably a logged in user.
		// If not, we'll get an exception, which we handle below.
		try{
			$accessToken = $helper->getAccessToken();
				
			if(isset($accessToken)){

				// Logged in!
				
				$user_data = $facebook->get ("/me?fields=link,first_name,middle_name,last_name", $accessToken);
					
				$user_profile = $user_data->getGraphUser();
				$link = $user_profile["link"];
				$first_name = $user_profile["first_name"];
				if (isset ( $user_profile ["middle_name"] )) {
					$middle_name = $user_profile ["middle_name"];
				} else {
					$middle_name = "";
				}

				$last_name = $user_profile ["last_name"];
				
				$record = new stdClass ();
				$record->moodleid  = $USER->id;
				$record->facebookid = $user_profile["id"];
				$record->timemodified = $time;
				$record->status = FACEBOOK_STATUS_LINKED;
				$record->lasttimechecked = $time;
				$record->link = $link;
				$record->firstname = $first_name;
				$record->middlename = $middle_name;
				$record->lastname = $last_name;
				//$record->email = $link;
				
				if($user_inactive = $DB->get_record("facebook_user", array("moodleid" => $USER->id,"status" => 0))){
					$record->id =$user_inactive->id;
					$DB->update_record("facebook_user", $record );
				} else if ($DB->record_exists("facebook_user", array(
						"facebookid" => $user_profile["id"],
						"status" => FACEBOOK_STATUS_LINKED
				))) {
					throw new Exception(get_string("accused", "local_facebook"));
				} else {
					$DB->insert_record("facebook_user", $record );
				}

				
				echo "<script>location.reload();</script>";
				// Now you can redirect to another page and use the
				// access token from $_SESSION['facebook_access_token']
			} elseif ($helper->getError()) {
				// The user denied the request
				exit;
			}
		} catch(FacebookApiException $e) {
				
			// If the user is logged out, you can have a
			// user ID even though the access token is invalid.
			// In this case, we'll get an exception, so we'll
			// just ask the user to login again here.
				
			$params = ["email",
					"publish_actions",
					"user_birthday",
					"user_tagged_places",
					"user_hometown",
					"user_likes",
					"user_friends"
			];
			$$loginUrl = $helper->getLoginUrl(($CFG->wwwroot . "/local/facebook/connect.php"), $params );
			echo "Please <a href='" . $login_Url . "'>Log in with Facebook..</a>";
		} catch (Exception $e) {
			echo $e->getMessage();
			echo "<a href='https://www.facebook.com/'>".get_string("facebooklogin", "local_facebook")."</a>";
		}
	} else {

		echo $OUTPUT->heading(get_string("acountconnect", "local_facebook"));
		echo $OUTPUT->heading(get_string("connectwith", "local_facebook"), 5);

		if($userinfo->firstname == NULL){
			$sqlfilteruser = "SELECT fu.facebookid,
					u.firstname, u.lastname,
					fu.link, fu.middlename
					FROM {facebook_user} AS fu INNER JOIN {user} AS u ON (fu.moodleid = u.id)
					WHERE fu.moodleid = ?";
			
			$datauser = new stdClass();
			
			if( $querydata = $DB->get_records_sql($sqlfilteruser,array($USER->id)) ){
				foreach($querydata as $data){
					$datauser->facebookid = $data->facebookid;
					$datauser->link = "https://www.facebook.com/app_scoped_user_id/".$data->facebookid."/";
					$datauser->firstname = $data->firstname;
					$datauser->middlename = "";
					$datauser->lastname = $data->lastname;
				}
			}
		}else {
			$datauser = $DB->get_record("facebook_user",array("moodleid"=>$USER->id));
		}
		
		//Tesis Roberto Jaunez
		if($USER->id == 10644 || $USER->id == 2 || $USER->id == 40214  || $USER->id == 381 || $USER->id == 60246 || $USER->id == 32806 || $USER->id == 28988){
			$toprow = array();
			$toprow[] = new tabobject("Tu cuenta", new moodle_url('/local/facebook/connect.php'), "Tu cuenta");
			$toprow[] = new tabobject("Facebook Analysis", new moodle_url('/local/facebook/facebookalgorithm.php'), "Facebook Analysis");			
			echo $OUTPUT->tabtree($toprow, "Tu cuenta");
		}
		
		$table = facebook_connect_table_generator(
				$datauser->facebookid,
				$datauser->link,
				$datauser->firstname,
				$datauser->middlename,
				$datauser->lastname
		);
		// Look if the account was already linked
		$duplicate = $DB->get_record("facebook_user", array (
				"facebookid" => $facebook_id,
				"status" => FACEBOOK_STATUS_LINKED
		) );
		// if it isn't linked it will return false, if the status is 0 someone already linked it but it is not active.

		$button = new connect ( null, array (
				"duplicate" => $duplicate
		) );
		$button->display ();
	}
}
// if the user has the account linkd it will show his information and some other actions the user can perform.
echo $OUTPUT->footer ();
