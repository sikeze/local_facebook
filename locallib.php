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
 * @package    local
 * @subpackage facebook
 * @copyright  2015 Xiu-Fong Lin (xlin@alumnos.uai.cl)
 * @copyright  2015 Mihail Pozarski (mipozarski@alumnos.uai.cl)
 * @copyright  2015-2016 Hans Jeria (hansjeria@gmail.com)
 * @copyright  2016 Mark Michaelsen (mmichaelsen678@gmail.com)
 * @copyright  2017 Javier Gonzalez (javiergonzalez@alumnos.uai.cl)
 * @copyright  2017 Joaquin Rivano (joaquin.rivano@alumnos.uai.cl)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
/**
 * Constants
 */
// Visible Course Module
define('FACEBOOK_COURSE_MODULE_VISIBLE', 1);
define('FACEBOOK_COURSE_MODULE_NOT_VISIBLE', 0);
// Visible Module
define('FACEBOOK_MODULE_VISIBLE', 1);
define('FACEBOOK_MODULE_NOT_VISIBLE', 0);
// Image
define('FACEBOOK_IMAGE_POST', 'post');
define('FACEBOOK_IMAGE_RESOURCE', 'resource');
define('FACEBOOK_IMAGE_LINK', 'link');
define('FACEBOOK_IMAGE_EMARKING', 'emarking');
define('FACEBOOK_IMAGE_ASSIGN', 'assign');
define('MODULE_EMARKING', 24);
define('MODULE_ASSIGN', 1);
//
define('FACEBOOK_LINKED', 1);

/**
 * This function gets al the notification pending since the last check.
 * @param $sqlin from get_in_or_equal used in "IN ('')" clause    
 * @param $param from get_in_or_equal parameters      	
 * @param date $lastvisit        	
 * @return 3 arrays
 */
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once ($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot."/local/facebook/app/Facebook/autoload.php");
require_once($CFG->dirroot."/local/facebook/app/Facebook/FacebookRequest.php");
include $CFG->dirroot."/local/facebook/app/Facebook/Facebook.php";
use Facebook\FacebookResponse;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequire;
use Facebook\Facebook;
use Facebook\Request;

function get_total_notification($moodleid){
	global  $DB, $CFG;

	//sql that counts all the new of recently modified resources
	$totalresourceparams = array(
			FACEBOOK_COURSE_MODULE_VISIBLE,
			'resource',
			$moodleid,
			FACEBOOK_MODULE_VISIBLE
	);

	// Sql that counts all the resourses since the last time the app was used
	$totalresourcesql = "SELECT cm.course AS idcoursecm,
						COUNT(cm.id) AS countallresource,
						fb.facebookid
						FROM {enrol} AS en
						INNER JOIN {user_enrolments} AS uen ON (en.id = uen.enrolid)
						INNER JOIN {course_modules} AS cm ON (en.courseid = cm.course AND cm.visible = ?)
						INNER JOIN {resource} AS r ON (cm.instance = r.id )
						INNER JOIN {modules} AS m ON (cm.module = m.id AND m.name = ?)
						INNER JOIN {user} AS us ON (uen.userid = us.id AND us.id = ?)
						INNER JOIN {facebook_user} AS fb ON (fb.moodleid = us.id AND fb.status = ?)
						WHERE r.timemodified > fb.lasttimechecked
						AND fb.facebookid IS NOT NULL
						GROUP BY cm.course";
	
	$totalresource = $DB->get_records_sql($totalresourcesql, $totalresourceparams);

	$resourcepercourse = array();

	// If the query brings something generate an array with all the course ids
	if($totalresource){
		foreach($totalresource as $totalresources){
			$resourcepercourse[$totalresources->idcoursecm] = $totalresources->countallresource;
		}
	}

	//Parameters of the urls
	$totalurlparams = array(
			FACEBOOK_COURSE_MODULE_VISIBLE,
			'url',
			FACEBOOK_MODULE_VISIBLE,
			$moodleid
	);

	// Sql that counts all the urls since the last time the app was used
	$totalurlsql = "SELECT 	idcoursecm, count(url) AS  countallurl
					FROM
					(SELECT cm.course AS idcoursecm,
					url.id AS url,
					url.name
					FROM mdl_enrol AS en
					INNER JOIN mdl_user_enrolments AS uen ON (en.id = uen.enrolid)
					INNER JOIN mdl_course_modules AS cm ON (en.courseid = cm.course AND cm.visible = ?)
					INNER JOIN mdl_url AS url ON (cm.instance = url.id)
					INNER JOIN mdl_modules AS m ON (cm.module = m.id AND m.name = ?)
					INNER JOIN mdl_user AS us ON (uen.userid = us.id)
					INNER JOIN mdl_facebook_user AS fb ON (fb.moodleid = us.id AND fb.status = ?)
					WHERE url.timemodified > fb.lasttimechecked
					AND fb.facebookid IS NOT NULL
					AND us.id = ?
					group by url.id,cm.course) AS tablewithdata
					GROUP BY idcoursecm";

	// Gets the infromation of the above query
	$totalurl = $DB->get_records_sql($totalurlsql, $totalurlparams);

	$urlpercourse = array();

	// Makes an array that associates the course id with the counted items
	if($totalurl){
		foreach($totalurl as $totalurls){
			$urlpercourse[$totalurls->idcoursecm] = $totalurls->countallurl;
		}
	}



	// Post parameters for query
	$totalpostparams = array(
			FACEBOOK_COURSE_MODULE_VISIBLE,
			$moodleid
	);

	// Sql that counts all the posts since the last time the app was conected.
	$totalpostsql = "SELECT 	idcoursefd,
			count(countallpost) AS  countallpost
			FROM	(SELECT discussions.course AS idcoursefd,
				COUNT(fp.id) AS countallpost
				FROM mdl_enrol AS en
				INNER JOIN mdl_user_enrolments AS uen ON (en.id = uen.enrolid)
				INNER JOIN mdl_forum_discussions AS discussions ON (en.courseid = discussions.course)
				INNER JOIN mdl_forum_posts AS fp ON (fp.discussion = discussions.id)
				INNER JOIN mdl_forum AS forum ON (forum.id = discussions.forum)
				INNER JOIN mdl_user AS us ON (uen.userid = us.id)
				INNER JOIN mdl_facebook_user AS fb ON (fb.moodleid = us.id AND fb.status = ?)
				WHERE fp.modified > fb.lasttimechecked
				AND fb.facebookid IS NOT NULL
				AND us.id = ?
				GROUP BY fp.id, discussions.course) AS tablewithdata
			GROUP BY idcoursefd";

	$totalpost = $DB->get_records_sql($totalpostsql, $totalpostparams);

	$totalpostpercourse = array();

	// Makes an array that associates the course id with the counted items
	if($totalpost){
		foreach($totalpost as $objects){
			$totalpostpercourse[$objects->idcoursefd] = $objects->countallpost;
		}
	}


	$paramsassignment = array(
			MODULE_ASSIGN,
			FACEBOOK_COURSE_MODULE_VISIBLE,
			$moodleid
	);

	$totalassignmentsql= "SELECT a.course AS acourseid,
						COUNT(a.id) AS countallassignments
						FROM {assign} AS a
						INNER JOIN {course} AS c ON (a.course = c.id)
						INNER JOIN {enrol} AS e ON (c.id = e.courseid)
						INNER JOIN {user_enrolments} AS ue ON (e.id = ue.enrolid)
						INNER JOIN {user} AS us ON (us.id = ue.userid)
						INNER JOIN {facebook_user} AS fb ON (fb.moodleid = us.id AND fb.status = ?)
						WHERE a.timemodified > fb.lasttimechecked
						AND fb.facebookid IS NOT NULL
						AND us.id = ?
						GROUP BY c.id";

	$totalassignment = $DB->get_records_sql($totalassignmentsql, $paramsassignment);
	$totalassignmentpercourse = array();
	if($totalassignment){
		foreach($totalassignment as $objects){
			$totalassignmentpercourse[$objects->acourseid] = $objects->countallassignments;
		}
	}


	$totalemarkingperstudent = array();
	if($CFG->fbk_emarking){
		$paramsemarking = array(
				FACEBOOK_LINKED,
				$moodleid
		);
		$dataemarkingsql= "SELECT CONCAT(s.id,e.id,s.grade) AS ids,
				COUNT(s.id) AS total,
				e.id AS emarkingid,
				e.course AS course,
				e.name AS testname,
				d.grade AS grade,
				d.status AS status,
				d.timemodified AS date,
				s.teacher AS teacher,
				cm.id as moduleid,
				CONCAT(us.firstname,' ',us.lastname) AS user
				FROM {emarking_draft} AS d
				JOIN {emarking} AS e ON (e.id = d.emarkingid AND e.type in (1,5,0))
				INNER JOIN {emarking_submission} AS s ON (d.submissionid = s.id AND d.status IN (20,30,35,40))
				INNER JOIN {user} AS us ON (s.student = us.id)
				INNER JOIN {user_enrolments} AS uen ON (us.id = uen.userid)
				INNER JOIN {enrol} AS en ON (en.id = uen.enrolid)
				INNER JOIN {course_modules} AS cm ON (cm.instance = e.id AND cm.course = en.courseid)
				INNER JOIN {modules} AS m ON (cm.module = m.id AND m.name = 'emarking')
				INNER JOIN {facebook_user} AS fb ON (fb.moodleid = us.id AND fb.status = ?)
				WHERE d.timemodified > fb.lasttimechecked
				AND fb.facebookid IS NOT NULL
				AND us.id = ?
				GROUP BY s.id";


		if($totalemarking = $DB->get_records_sql($dataemarkingsql, $paramsemarking)){
			foreach($totalemarking as $objects){
				$totalemarkingperstudent[$objects->course] = $objects->total;
			}
		}
	}

	return array($resourcepercourse, $urlpercourse, $totalpostpercourse, $totalassignmentpercourse, $totalemarkingperstudent);
}
/**
 * Sort the records by the field inside record.
 * @param array $records        	
 * @param string $field        	
 * @param string $reverse        	
 * @return the records sorted
 */
function record_sort($records, $field, $reverse = false){
	
	$hash = array();
	foreach($records as $record){
		$hash[$record[$field]] = $record;
	}
	
	($reverse) ? krsort ($hash) : ksort ($hash);
	
	$records = array();
	foreach($hash as $record){
		$records[] = $record;
	}
	
	return $records;
}
/**
 * This Function gets all the posts resources and links, posted recently in the course ordered by date.
 * @param $sqlin from get_in_or_equal used in "IN ('')" clause    
 * @param $param from get_in_or_equal parameters      	
 * @return array
 */
function get_course_data ($moodleid, $courseid) {
	global $DB, $CFG;
	
	// Parameters for post query
	$paramspost = array(
			$courseid,
			FACEBOOK_COURSE_MODULE_VISIBLE
	);
	
	// Query for the posts information
	$datapostsql = "SELECT fp.id AS postid, us.firstname AS firstname, us.lastname AS lastname, fp.subject AS subject,
			fp.modified AS modified, discussions.course AS course, discussions.id AS dis_id, cm.id AS moduleid
			FROM {forum_posts} AS fp
			INNER JOIN {forum_discussions} AS discussions ON (fp.discussion = discussions.id AND discussions.course = ?)
			INNER JOIN {forum} AS forum ON (forum.id = discussions.forum)
			INNER JOIN {user} AS us ON (us.id = fp.userid)
			INNER JOIN {course_modules} AS cm ON (cm.instance = forum.id)
			WHERE cm.visible = ? 
			GROUP BY fp.id";
	
	// Get the data from the above query
	$datapost = $DB->get_records_sql($datapostsql, $paramspost);
	
	// Parameters for resource query
	$paramsresource = array(
			$courseid,
			FACEBOOK_COURSE_MODULE_VISIBLE,
			'resource'
	);
	
	// Query for the resource information
	$dataresourcesql = "SELECT cm.id AS coursemoduleid, r.id AS resourceid, r.name AS resourcename, r.timemodified, 
			  r.course AS resourcecourse, cm.visible, cm.visibleold
			  FROM {resource} AS r 
              INNER JOIN {course_modules} AS cm ON (cm.instance = r.id AND cm.course = ? AND cm.visible = ?)
              INNER JOIN {modules} AS m ON (cm.module = m.id AND m.name = ?)
              GROUP BY cm.id";
	// Get the data from the above query
	$dataresource = $DB->get_records_sql($dataresourcesql, $paramsresource);
	
	// Parameters for the link query
	$paramslink = array(
			$courseid,
			FACEBOOK_COURSE_MODULE_VISIBLE,
			'url'
	);
	
	//query for the link information
	$datalinksql="SELECT url.id AS id, url.name AS urlname, url.externalurl AS externalurl, url.timemodified AS timemodified,
	          url.course AS urlcourse, cm.visible AS visible, cm.visibleold AS visibleold
		      FROM {url} AS url
              INNER JOIN {course_modules} AS cm ON (cm.instance = url.id AND cm.course = ? AND cm.visible = ?)
              INNER JOIN {modules} AS m ON (cm.module = m.id AND m.name = ?)
              GROUP BY url.id";
	
	// Get the data from the above query
	$datalink = $DB->get_records_sql($datalinksql, $paramslink);
	
	// Query for getting eMarkings by course
	$dataemarkingsql= "SELECT CONCAT(s.id,e.id,s.grade) AS ids,
			s.id AS id, 
			e.id AS emarkingid, 
			e.course AS course,
			e.name AS testname,
			d.grade AS grade,
			d.status AS status,
			d.timemodified AS date,
			s.teacher AS teacher,
			cm.id as moduleid,
			CONCAT(u.firstname,' ',u.lastname) AS user
			FROM {emarking_draft} AS d JOIN {emarking} AS e ON (e.id = d.emarkingid AND e.type in (1,5,0))
			INNER JOIN {emarking_submission} AS s ON (d.submissionid = s.id AND d.status IN (20,30,35,40) AND s.student = ?)
			INNER JOIN {user} AS u ON (u.id = s.student)
			INNER JOIN {course_modules} AS cm ON (cm.instance = e.id AND cm.course = ?)
			INNER JOIN {modules} AS m ON (cm.module = m.id AND m.name = 'emarking')";
	
	//$emarkingparams = $param;
	$paramsemarking = array(
			$moodleid,
			$courseid
	);
	
	if($CFG->fbk_emarking){
		$dataemarking = $DB->get_records_sql($dataemarkingsql, $paramsemarking);
	}
	
	$dataassignmentsql = "SELECT a.id AS id,
			s.status AS status,
			a.timemodified AS date,
			a.duedate AS duedate,
			s.timemodified AS lastmodified,
			a.name AS assignmentname,
			cm.id AS moduleid
			FROM {assign} AS a
			INNER JOIN {course} AS c ON (a.course = c.id AND c.id = ?)
			INNER JOIN {enrol} AS e ON (c.id = e.courseid)
			INNER JOIN {user_enrolments} AS ue ON (e.id = ue.enrolid AND ue.userid = ?)
			INNER JOIN {course_modules} AS cm ON (c.id = cm.course AND cm.module = ? AND cm.visible = ?)
			INNER JOIN {assign_submission} AS s ON (a.id = s.assignment)
			GROUP BY a.id";
	
	$paramsassignment = array(
			$courseid,
			$moodleid,
			MODULE_ASSIGN,
			FACEBOOK_COURSE_MODULE_VISIBLE
	);
	
	$dataassign = $DB->get_records_sql($dataassignmentsql, $paramsassignment);
	
	//$assignparams = array_merge($userid,$param,$sqlparams,$userid);	
	//$dataassign = $DB->get_records_sql($dataassignmentsql, $assignparams);
	
	$totaldata = array();
	// Foreach used to fill the array with the posts information
	foreach($datapost as $post){
		$posturl = new moodle_url('/mod/forum/discuss.php', array(
				'd'=>$post->dis_id 
		));
		
		$totaldata[] = array(
				'image'=>FACEBOOK_IMAGE_POST,
				'discussion'=>$post->dis_id,
				'link'=>$posturl,
				'title'=>$post->subject,
				'from'=>$post->firstname . ' ' . $post->lastname,
				'date'=>$post->modified,
				'course'=>$post->course,
				'moduleid'=>$post->moduleid
		);
	}
	
	// Foreach used to fill the array with the resource information	
	foreach($dataresource as $resource){
		$date = date("d/m H:i", $resource->timemodified);
		$resourceurl = new moodle_url('/mod/resource/view.php', array(
				'id'=>$resource->coursemoduleid
		));
		
		if($resource->visible == FACEBOOK_COURSE_MODULE_VISIBLE && $resource->visibleold == FACEBOOK_COURSE_MODULE_VISIBLE){
			$totaldata[] = array (
					'image'=>FACEBOOK_IMAGE_RESOURCE,
					'link'=>$resourceurl,
					'title'=>$resource->resourcename,
					'from'=>'',
					'date'=>$resource->timemodified,
					'course'=>$resource->resourcecourse 
			);
		}
	}
	// Foreach used to fill the array with the link information
	foreach($datalink as $link){
		$date = date("d/m H:i", $link->timemodified);
		
		if($link->visible == FACEBOOK_COURSE_MODULE_VISIBLE && $link->visibleold == FACEBOOK_COURSE_MODULE_VISIBLE){
			$totaldata[] = array(
					'image'=>FACEBOOK_IMAGE_LINK,
					'link'=>$link->externalurl,
					'title'=>$link->urlname,
					'from'=>'',
					'date'=>$link->timemodified,
					'course'=>$link->urlcourse 
			);
		}
	}
	
	if($CFG->fbk_emarking){
		foreach($dataemarking as $emarking){
			$emarkingurl = new moodle_url('/mod/emarking/view.php', array(
					'id' => $emarking->moduleid
			));
			
			$totaldata[] = array(
					'image'=>FACEBOOK_IMAGE_EMARKING,
					'link'=>$emarkingurl,
					'title'=>$emarking->testname,
					'from'=>$emarking->user,
					'date'=>$emarking->date,
					'course'=>$emarking->course,
					'id'=>$emarking->id,
					'grade'=>$emarking->grade,
					'status'=>$emarking->status,
					'teacherid'=>$emarking->teacher
			);
		}
	}
	
	foreach($dataassign as $assign){
		$assignurl = new moodle_url('/mod/assign/view.php', array(
				'id'=>$assign->moduleid
		));
		
		$duedate = date("d/m H:i", $assign->duedate);
		$date = date("d/m H:i", $assign->lastmodified);
		
		if ($assign->status == 'submitted') {
			$status = get_string('submitted', 'local_facebook');
		} else {
			$status = get_string('notsubmitted', 'local_facebook');
		}
		
		if ($DB->record_exists('assign_grades', array(
				'assignment' => $assign->id,
				'userid' => $moodleid
		))) {
			$totaldata[] = array(
					'id'=>$assign->id,
					'image'=>FACEBOOK_IMAGE_ASSIGN,
					'link'=>$assignurl,
					'title'=>$assign->assignmentname,
					'date'=>$assign->date,
					'due'=>$duedate,
					'from'=>'',
					'modified'=>$date,
					'status'=>$status,
					'grade'=>get_string('graded', 'local_facebook')
			);
		} else {
			$totaldata[] = array(
					'id'=>$assign->id,
					'image'=>FACEBOOK_IMAGE_ASSIGN,
					'link'=>$assignurl,
					'title'=>$assign->assignmentname,
					'date'=>$assign->date,
					'due'=>$duedate,
					'from'=>'',
					'modified'=>$date,
					'status'=>$status,
					'grade'=>get_string('notgraded', 'local_facebook')
			);
		}
	
		
	}
	
	// Returns the final array ordered by date to index.php
	return record_sort($totaldata, 'date', 'true');
}
function facebook_connect_table_generator($facebook_id, $link, $first_name, $middle_name, $last_name) {
	global $CFG;
	$imagetable = new html_table ();
	$infotable = new html_table ();
	$infotable->data [] = array (
			get_string ( "fbktablename", "local_facebook" ),
			$first_name." ".$middle_name." ".$last_name
	);
	$infotable->data [] = array (
			get_string ( "profile", "local_facebook" ),
			"<a href='" . $link . "' target=_blank>" . $link . "</a>"
	);

	$infotable->data [] = array (
			"Link a la app",
			"<a href='" . $CFG->fbk_url . "' target=_blank>" . $CFG->fbk_url . "</a>"
	);

	$imagetable->data [] = array (
			"<img src='https://graph.facebook.com/" .$facebook_id . "/picture?type=large'>",
			html_writer::table ($infotable)
	);
	echo html_writer::table ($imagetable);
}
function get_posts_from_discussion($discussionid) {
	global $DB;
	
	$sql = "SELECT fp.id AS id,
			fp.subject AS subject,
			fp.message AS message,
			fp.created AS date,
			fp.parent AS parent, 
			CONCAT(u.firstname, ' ', u.lastname) AS user 
			FROM {forum_posts} AS fp INNER JOIN {user} AS u ON (fp.userid = u.id)
			WHERE fp.discussion = ? 
			GROUP BY fp.id";
	
	$discussiondata = $DB->get_records_sql($sql, array($discussionid));
	
	$data = array();
	foreach($discussiondata as $post) {
		$data[] = array(
				'id' => $post->id,
				'subject' => $post->subject,
				'message' => $post->message,
				'date' => $post->date,
				'parent' => $post->parent,
				'user' => $post->user
		);
	}
	
	return $data;
}
function cmp($a, $b){
	return strcmp ($b->totalnotifications, $a->totalnotifications);
}
function facebook_newclass(){
	global $CFG;
	require_once($CFG->dirroot."/local/facebook/app/Facebook/autoload.php");
	require_once($CFG->dirroot."/local/facebook/app/Facebook/FacebookRequest.php");
	
	$fb = new Facebook([
			"app_id" => $CFG->fbk_appid,
			"app_secret" => $CFG->fbk_scrid,
			"default_graph_version" => "v2.8"]);
	
	return $fb;
}
function facebook_handleexceptions($fb, $user, $data){
	global $DB;
	
	try {
		$response = $fb->post('/'.$user->facebookid.'/notifications', $data);
		return $response->getDecodedBody();
	} catch (Exception $e) {
		$exception = $e->getMessage();
		mtrace("Exception found: $exception \n");

		// If the user hasn't installed the app, update it's record to status = 0
		if (strpos($exception, "not installed") !== FALSE) {
			mtrace("USER ".$user->name."with ID ".$user->id."does not have the App installed.");
				/*$updatequery = "UPDATE {facebook_user}
						SET status = ?
						WHERE moodleid = ?";
				$updateparams = array(
						0,
						$user->id
						
				);
			if ($DB->execute($updatequery, $updateparams)) {
				mtrace("Record updated, set status to 0. \n Moodle id: ". $user->id);
			} else {
				mtrace("Could not update the record. \n Moodle id: ". $user->id);
			}*/
		}
	return false;
	}
}
function facebook_addtoarray($query, $params){
	global $DB;
	$arraydata = array();
	if ($facebookusers = $DB->get_records_sql($query, $params)){
		foreach ($facebookusers as $users){
			$arraydata[$users->userid] = $users->count;
		}
	}
	return $arraydata;
}
function facebook_queriesfornotifications(){
	global $DB;
	
	$queryposts = "SELECT us.id AS userid,
		COUNT(fp.id) AS count,
		fb.facebookid,
		CONCAT(us.firstname,' ',us.lastname) AS name
		FROM {enrol} AS en
		INNER JOIN {user_enrolments} AS uen ON (en.id = uen.enrolid)
		INNER JOIN {forum_discussions} AS discussions ON (en.courseid = discussions.course)
		INNER JOIN {forum_posts} AS fp ON (fp.discussion = discussions.id)
		INNER JOIN {forum} AS forum ON (forum.id = discussions.forum)
		INNER JOIN {user} AS us ON (uen.userid = us.id)
		INNER JOIN {facebook_user} AS fb ON (fb.moodleid = us.id AND fb.status = ?)
		WHERE fp.modified > fb.lasttimechecked
		AND fb.facebookid IS NOT NULL
		GROUP BY us.id";
	
	$queryresources = "SELECT us.id AS userid,
		COUNT(cm.id) AS count,
		fb.facebookid,
		CONCAT(us.firstname,' ',us.lastname) AS name
		FROM {enrol} AS en
		INNER JOIN {user_enrolments} AS uen ON (en.id = uen.enrolid)
		INNER JOIN {course_modules} AS cm ON (en.courseid = cm.course AND cm.visible = ?)
		INNER JOIN {resource} AS r ON (cm.instance = r.id )
		INNER JOIN {modules} AS m ON (cm.module = m.id AND m.name = ?)
		INNER JOIN {user} AS us ON (uen.userid = us.id)
		INNER JOIN {facebook_user} AS fb ON (fb.moodleid = us.id AND fb.status = ?)
		WHERE r.timemodified > fb.lasttimechecked
		AND fb.facebookid IS NOT NULL
		GROUP BY us.id";
	
	$querylink = "SELECT us.id AS userid,
		COUNT(url.id) AS count,
		fb.facebookid,
		CONCAT(us.firstname,' ',us.lastname) AS name
		FROM {enrol} AS en
		INNER JOIN {user_enrolments} AS uen ON (en.id = uen.enrolid)
		INNER JOIN {course_modules} AS cm ON (en.courseid = cm.course AND cm.visible = ?)
		INNER JOIN {url} AS url ON (cm.instance = url.id)
		INNER JOIN {modules} AS m ON (cm.module = m.id AND m.name = ?)
		INNER JOIN {user} AS us ON (uen.userid = us.id)
		INNER JOIN {facebook_user} AS fb ON (fb.moodleid = us.id AND fb.status = ?)
		WHERE url.timemodified > fb.lasttimechecked
		AND fb.facebookid IS NOT NULL
		GROUP BY us.id";
	
	$queryemarking = "SELECT us.id AS userid,
		COUNT(d.id) AS count,
		fb.facebookid,
		CONCAT(us.firstname,' ',us.lastname) AS name
		FROM {emarking_draft} AS d JOIN {emarking} AS e ON (e.id = d.emarkingid AND e.type in (1,5,0))
		INNER JOIN {emarking_submission} AS s ON (d.submissionid = s.id AND d.status IN (20,30,35,40))
		INNER JOIN {user} AS us ON (s.student = us.id)
		INNER JOIN {user_enrolments} AS uen ON (us.id = uen.userid)
		INNER JOIN {enrol} AS en ON (en.id = uen.enrolid)
		INNER JOIN {course_modules} AS cm ON (cm.instance = e.id AND cm.course = en.courseid)
		INNER JOIN {modules} AS m ON (cm.module = m.id AND m.name = 'emarking')
		INNER JOIN {facebook_user} AS fb ON (fb.moodleid = us.id AND fb.status = ?)
		WHERE d.timemodified > fb.lasttimechecked
		AND fb.facebookid IS NOT NULL
		GROUP BY us.id";
	
	$queryassignments = "SELECT us.id AS userid,
		COUNT(a.id) AS count,
		fb.facebookid,
		CONCAT(us.firstname,' ',us.lastname) AS name
		FROM {assign} AS a
		INNER JOIN {course} AS c ON (a.course = c.id)
		INNER JOIN {enrol} AS e ON (c.id = e.courseid)
		INNER JOIN {user_enrolments} AS ue ON (e.id = ue.enrolid)
		INNER JOIN {user} AS us ON (us.id = ue.userid)	
		INNER JOIN {facebook_user} AS fb ON (fb.moodleid = us.id AND fb.status = ?)
		WHERE a.timemodified > fb.lasttimechecked
		AND fb.facebookid IS NOT NULL
		GROUP BY us.id";
	
	$paramsusers = array(
			FACEBOOK_LINKED
	);
	$paramspost = array(
			FACEBOOK_COURSE_MODULE_VISIBLE
	);
	
	$paramsresource = array(
			FACEBOOK_COURSE_MODULE_VISIBLE,
			'resource'
	);	
	$paramslink = array(
			FACEBOOK_COURSE_MODULE_VISIBLE,
			'url'
	);
	$paramsassignment = array(
			MODULE_ASSIGN,
			FACEBOOK_COURSE_MODULE_VISIBLE
	);
	
	return array(
			facebook_addtoarray($queryposts, $paramsusers),
			facebook_addtoarray($queryresources, array_merge($paramsresource, $paramsusers)),
			facebook_addtoarray($querylink, array_merge($paramslink, $paramsusers)),
			facebook_addtoarray($queryemarking, $paramsusers),
			facebook_addtoarray($queryassignments, array_merge($paramsassignment, $paramsusers))
	);
}
function facebook_getusers(){
	global $DB;
	$queryusers = "SELECT
		us.id AS id,
		fb.facebookid,
		CONCAT(us.firstname,' ',us.lastname) AS name
		FROM {facebook_user} AS fb
		RIGHT JOIN {user} AS us ON (us.id = fb.moodleid AND fb.status = ?)
		WHERE fb.facebookid IS NOT NULL
		GROUP BY fb.facebookid, us.id";
	$paramsusers = array(
			FACEBOOK_LINKED
	);
	$getrecords = $DB->get_records_sql($queryusers, $paramsusers);
	return $getrecords;
}

function facebook_getcoursesbyenrolment($enrolment, $userid){
	global $DB;
	if ($enrolment == "manual" || $enrolment == "self" || "meta"){
		if ($enrolment == "meta"){
			$isitnull = "= '' ";
		}
		else{
			$isitnull = "!= '' ";
		}
		$sql = "SELECT c.id,
		c.fullname
		FROM {user_enrolments} AS ue
		INNER JOIN {enrol} AS e ON e.id = ue.enrolid
		INNER JOIN {course} AS c ON c.id = e.courseid
		WHERE e.enrol =?
		AND c.idnumber $isitnull
		AND ue.userid =?
		GROUP BY c.id";
		$sqlparams = array($enrolment, $userid);
		$queryexecution = $DB->get_records_sql($sql, $sqlparams);
		$courses = array();
		foreach ($queryexecution as $userscourses){
			$courses[] = $userscourses;
		}
		if (!empty($courses)){
			return $courses;
		}
	}
}
function facebook_notificationspercourse($user, $courses){
	global $DB;
	$courseidarray = array();
	foreach ($courses as $course){
		$courseidarray[] = $course->id;
	}
	list($sqlin, $paramcourses) = $DB->get_in_or_equal($courseidarray);
	$coursesnotificationscounter = get_total_notification($user->id);

	$finalarray = array();
	$totalnot = 0;
	foreach ($courseidarray AS $idarray){
		$totalcount = 0;
		if (isset($coursesnotificationscounter[0][$idarray])){
			$totalcount += $coursesnotificationscounter[0][$idarray];

		}if (isset($coursesnotificationscounter[1][$idarray])){
			$totalcount += $coursesnotificationscounter[1][$idarray];

		}if (isset($coursesnotificationscounter[2][$idarray])){
			$totalcount += $coursesnotificationscounter[2][$idarray];

		}if (isset($coursesnotificationscounter[3][$idarray])){
			$totalcount += $coursesnotificationscounter[3][$idarray];
		}if (isset($coursesnotificationscounter[4][$idarray])){
			$totalcount += $coursesnotificationscounter[4][$idarray];
		}
		$totalnot += $totalcount;
		$finalarray [$idarray] = $totalcount;
	}
	$finalarray [0] = $totalnot;

	return $finalarray;
}
function paperattendance_convertdate($i) {
	// arrays of days and months
	$days = array (
			get_string ( 'sunday', 'local_paperattendance' ),
			get_string ( 'monday', 'local_paperattendance' ),
			get_string ( 'tuesday', 'local_paperattendance' ),
			get_string ( 'wednesday', 'local_paperattendance' ),
			get_string ( 'thursday', 'local_paperattendance' ),
			get_string ( 'friday', 'local_paperattendance' ),
			get_string ( 'saturday', 'local_paperattendance' )
	);
	$months = array (
			"",
			get_string ( 'january', 'local_paperattendance' ),
			get_string ( 'february', 'local_paperattendance' ),
			get_string ( 'march', 'local_paperattendance' ),
			get_string ( 'april', 'local_paperattendance' ),
			get_string ( 'may', 'local_paperattendance' ),
			get_string ( 'june', 'local_paperattendance' ),
			get_string ( 'july', 'local_paperattendance' ),
			get_string ( 'august', 'local_paperattendance' ),
			get_string ( 'september', 'local_paperattendance' ),
			get_string ( 'october', 'local_paperattendance' ),
			get_string ( 'november', 'local_paperattendance' ),
			get_string ( 'december', 'local_paperattendance' )
	);

	$dateconverted = date('H:i',$i)." - ".$days [date ( 'w', $i )] . ", " . date ( 'd', $i ) . get_string ( 'of', 'local_paperattendance').$months[date('n',$i)].get_string('from', 'local_paperattendance').date('Y',$i);
	return $dateconverted;
}