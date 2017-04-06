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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package    local
 * @subpackage facebook
 * @copyright  2013 Francisco García Ralph (francisco.garcia.ralph@gmail.com)
 * @copyright  2015 Xiu-Fong Lin (xlin@alumnos.uai.cl)
 * @copyright  2015 Mihail Pozarski (mipozarski@alumnos.uai.cl)
 * @copyright  2015 - 2016 Hans Jeria (hansjeria@gmail.com)
 * @copyright  2016 Mark Michaelsen (mmichaelsen678@gmail.com)
 * @copyright  2016 Andrea Villarroel (avillarroel@alumnos.uai.cl)
 * @copyright  2016 Jorge Cabané (jcabane@alumnos.uai.cl)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once (dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . '/config.php');
require_once ($CFG->dirroot . '/local/facebook/locallib.php');
require_once ($CFG->dirroot . "/local/facebook/app/Facebook/autoload.php");
global $DB, $USER, $CFG, $OUTPUT;
use Facebook\FacebookResponse;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequire;
use Facebook\Request;

require_once ("htmltoinclude/bootstrap.html");

// gets all facebook information needed
$config = array (
		"app_id" => $CFG->fbk_appid,
		"app_secret" => $CFG->fbk_scrid,
		"default_graph_version" => "v2.5" 
);

$fb = new Facebook\Facebook ( $config );

$helper = $fb->getCanvasHelper ();

try {
	$accessToken = $helper->getAccessToken ();
} catch ( Facebook\Exceptions\FacebookResponseException $e ) {
	// When Graph returns an error
	// echo 'Graph returned an error: ' . $e->getMessage ();
	// exit ();
	redirect($CFG->fbk_url);
} catch ( Facebook\Exceptions\FacebookSDKException $e ) {
	// When validation fails or other local issues
	// echo 'Facebook SDK returned an error: ' . $e->getMessage ();
	// exit ();
	redirect($CFG->fbk_url);
}

if (! isset ( $accessToken )) {
	// echo 'No OAuth data could be obtained from the signed request. User has not authorized your app yet.';
	// exit ();
	redirect($CFG->fbk_url);
}

$facebookdata = $helper->getSignedRequest ();

$user_data = $fb->get ( "/me?fields=id", $accessToken );
$user_profile = $user_data->getGraphUser ();
$facebook_id = $user_profile ["id"];

// Aquire User Facebook Info
$getInfo = TRUE;
try {
	// Returns a `Facebook\FacebookResponse` object
	$response = $fb->get('/me?fields=id,name,age_range,education,location,email,gender,hometown,sports,music,movies,likes,friends,tagged_places',
	$accessToken);
} catch(Facebook\Exceptions\FacebookResponseException $e) {
	echo 'Graph returned an error: ' . $e->getMessage();
	//exit;
	$getInfo = FALSE;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
	echo 'Facebook SDK returned an error: ' . $e->getMessage();
	//exit;
	$getInfo = FALSE;
}
if($getInfo){
	$userinfo = $response->getGraphUser();
	
	$jsoninfo = array();
	foreach ($userinfo as $key => $value){
		if( count($value) == 1 ){
			$jsoninfo[$key] = array($value);
		}else{
			$data = array();
			foreach ($value as $jsoncode){
				$data [] = json_decode($jsoncode);
			}
			$jsoninfo[$key] = $data;
		}		
	}
	//var_dump($jsoninfo);
	$json = json_encode($jsoninfo);
}

$app_name = $CFG->fbk_appname;
$app_email = $CFG->fbk_email;
$tutorial_name = $CFG->fbk_tutorialsname;
$tutorial_link = $CFG->fbk_tutorialurl;
echo html_writer::nonempty_tag("div", " ", array(
			"id" => "divurl",
			"url" => $CFG->fbk_ajax
	));
$messageurl = new moodle_url ( '/message/edit.php' );
$connecturl = new moodle_url ( '/local/facebook/connect.php' );

// gets the UAI left side bar of the app
include 'htmltoinclude/sidebar.html';

// search for the user facebook information
$userfacebookinfo = $DB->get_record ( 'facebook_user', array (
		'facebookid' => $facebook_id,
		'status' => 1 
) );

// if the user exist then show the app, if not tell him to connect to his facebook account
if ($userfacebookinfo != false) {
	// Save facebook info
	$userfacebookinfo->information = $json;
	$DB->update_record('facebook_user', $userfacebookinfo);
	
	$moodleid = $userfacebookinfo->moodleid;
	$lastvisit = $userfacebookinfo->lasttimechecked;
	
	$usercourse = enrol_get_users_courses ( $moodleid );
	
	// generates an array with all the users courses
	$courseidarray = array ();
	foreach ( $usercourse as $courses ) {
		// Only visible courses
		if($courses->visible){
			$courseidarray [] = $courses->id;
		}else{
			// Remove invisible courses
			unset($usercourse[$key]);
		}
	}
	
	// get_in_or_equal used after in the IN ('') clause of multiple querys
	list ( $sqlin, $param ) = $DB->get_in_or_equal ( $courseidarray );
	
	// list the 3 arrays returned from the funtion
	list ( $totalresource, $totalurl, $totalpost, $totalemarkingperstudent ) = get_total_notification ( $sqlin, $param, $lastvisit, $moodleid );
	//$dataarray = get_data_post_resource_link ( $sqlin, $param, $moodleid );
	
	// foreach that reorganizes array
	foreach ( $usercourse as $courses ) {
		$courses->totalnotifications = 0;
		
		if (isset ( $totalresource [$courses->id] )) {
			$courses->totalnotifications += intval ( $totalresource [$courses->id] );
		}
		
		if (isset ( $totalurl [$courses->id] )) {
			$courses->totalnotifications += intval ( $totalurl [$courses->id] );
		}
		
		if (isset ( $totalpost [$courses->id] )) {
			$courses->totalnotifications += intval ( $totalpost [$courses->id] );
		}
		
		if (isset ( $totalemarkingperstudent [$courses->id] )) {
			$courses->totalnotifications += intval ( $totalemarkingperstudent [$courses->id] );
		}
	}
	
	// reorganizes the courses by notifications
	usort ( $usercourse, 'cmp' );
	
	// foreach that generates each course square
	echo '<div style="line-height: 4px"><br></div>';
	
	foreach ( $usercourse as $courses ) {
		
		$fullname = $courses->fullname;
		$courseid = $courses->id;
		$shortname = $courses->shortname;
		$totals = $courses->totalnotifications;
		var_dump($totals);
		
		echo '<div class="block" style="height: 4em;"><button type="button" class="btn btn-info btn-lg" style="white-space: normal; width: 90%; height: 90%; border: 1px solid lightgray; background: #F0F0F0;" courseid="' . $courseid . '" fullname="' . $fullname . '" moodleid="'.$moodleid.'" lastvisit="'.$lastvisit.'" component="button">';
		echo '<p class="name" align="left" style="position: relative; height: 3em; overflow: hidden; color: black; font-weight: bold; text-decoration: none; font-size:13px; word-wrap: initial;" courseid="' . $courseid . '" moodleid="'.$moodleid.'" lastvisit="'.$lastvisit.'" component="button">
 				' . $fullname . '</p>';
		if ($totals > 0) {
			echo '<span class="badge" style="color: white; background-color: red; position: relative; right: -58%; top: -64px; margin-right:9%;" courseid="' . $courseid . '" component="button">' . $totals . '</span></button></div>';
		} else {
			echo '</button></div>';
		}
	}
	echo "<p></p>";
	echo "</div>";
	include 'htmltoinclude/likebutton.html';
	// include 'htmltoinclude/news.html';
	echo "</div>";
	
	// Front images
	$image = $CFG->fbk_frontimage;
	echo "<div class='col-md-9 col-sm-9 col-xs-12'>";
	echo "<div class='advert'><div style='position: relative;'> <img src='".$image."'style='margin-top:10%; margin-left:8%; width:80%'> </div></div>";
	echo "<div id='loadinggif' align='center' style='margin-top: 10%; text-align: center; display:none;'><img src='https://webcursos.uai.cl/local/facebook/app/images/ajaxloader.gif'></div>";
	echo "<div id='table-body'></div>";
	
	// Define the modal
	echo "<div class='modal fade' id='forum-modal' tabindex='-1' role='dialog' aria-labelledby='modal'>
			<div class='modal-dialog' role='document'>
				<div class='modal-content' id='modal-content'>
				</div>
			</div>
		</div>";

	echo "</div></div>";
	include 'htmltoinclude/spacer.html';


	// updates the user last time in the app
	$userfacebookinfo->lasttimechecked = time ();
	$DB->update_record ( 'facebook_user', $userfacebookinfo );

} else {
	echo '</div></div>';
	echo '<div class="popup" role="dialog" aria-labelledby="modal">';
	echo '<div class="cuerpo" style="margin:200px"><h1>' . get_string ( 'existtittle', 'local_facebook' ) . '</h1>
    <p>Para enlazar tu cuenta click <a  target="_blank" href="' . $connecturl . '" >Aquí</a></p></div>';
	echo '</div>';
	include 'htmltoinclude/spacer.html';
}

?>
<script type="text/javascript" src="js/onclick.js"></script>
<script type="text/javascript" src="js/search.js"></script>
