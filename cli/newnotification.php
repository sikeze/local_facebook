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

/**
 * This script send notifications on facebook
*
* @package    local/facebook/
* @subpackage cli
* @copyright  2010 Jorge Villalon (http://villalon.cl)
* @copyright  2015 Mihail Pozarski (mipozarski@alumnos.uai.cl)
* @copyright  2015 - 2016 Hans Jeria (hansjeria@gmail.com)
* @copyright  2016 Mark Michaelsen (mmichaelsen678@gmail.com)
* @copyright  2017 Javier Gonzalez (javiergonzalez@alumnos.uai.cl)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once ($CFG->libdir . '/clilib.php');
require_once ($CFG->dirroot."/local/facebook/locallib.php");
//require_once($CFG->dirroot."/local/facebook/app/Facebook/autoload.php");
//require_once($CFG->dirroot."/local/facebook/app/Facebook/FacebookRequest.php");
//include $CFG->dirroot."/local/facebook/app/Facebook/Facebook.php";
use Facebook\FacebookResponse;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequire;
use Facebook\Facebook;
use Facebook\Request;

// Now get cli options
list($options, $unrecognized) = cli_get_params(
		array('help'=>false),
		array('h'=>'help')
		);
if($unrecognized) {
	$unrecognized = implode("\n  ", $unrecognized);
	cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}
// Text to the facebook console
if($options['help']) {
	$help =
	// Todo: localize - to be translated later when everything is finished
	"Send facebook notifications when a course have some news.
Options:
-h, --help            Print out this help
Example:
\$sudo /usr/bin/php /local/facebook/cli/notifications.php";
	echo $help;
	die();
}


cli_heading('Facebook notifications'); // TODO: localize

echo "\nSearching for new notifications\n";
echo "\nStarting at ".date("F j, Y, G:i:s")."\n";

$initialtime = time();
$notifications = 0;


$appid = $CFG->fbk_appid;
$secretid = $CFG->fbk_scrid;

$fb = new Facebook([
		"app_id" => $appid,
		"app_secret" => $secretid,
		"default_graph_version" => "v2.5"]);

$queryusers = "SELECT  
		us.id AS id,
		fb.facebookid,
		us.lastaccess,
		CONCAT(us.firstname,' ',us.lastname) AS name,
		fb.lasttimechecked,
		us.email
		FROM {facebook_user} AS fb
		RIGHT JOIN {user} AS us ON (us.id = fb.moodleid AND fb.status = ?)
		WHERE fb.facebookid IS NOT NULL
		GROUP BY fb.facebookid, us.id";

$queryposts = "SELECT fp.id, 
		COUNT(fp.id) AS count,
		us.id AS userid,
		fb.facebookid,
		us.lastaccess,
		CONCAT(us.firstname,' ',us.lastname) AS name,
		fb.lasttimechecked,
		us.email
		FROM {enrol} AS en
		INNER JOIN {user_enrolments} AS uen ON (en.id = uen.enrolid)
		INNER JOIN {forum_discussions} AS discussions ON (en.courseid = discussions.course)
		INNER JOIN {forum_posts} AS fp ON (fp.discussion = discussions.id)
		INNER JOIN {forum} AS forum ON (forum.id = discussions.forum)
		INNER JOIN {user} AS us ON (us.id = fp.userid AND uen.userid = us.id)
		INNER JOIN {course_modules} AS cm ON (cm.instance = forum.id AND cm.visible = ?)
		INNER JOIN {facebook_user} AS fb ON (fb.moodleid = us.id AND fb.status = ?)
		WHERE fp.modified > fb.lasttimechecked OR fp.modified > us.lastaccess
		AND fb.facebookid IS NOT NULL
		GROUP BY fp.id, us.id";

$queryresources = "SELECT cm.id,
		COUNT(cm.id) AS count,
		us.id AS userid,
		fb.facebookid,
		us.lastaccess,
		CONCAT(us.firstname,' ',us.lastname) AS name,
		fb.lasttimechecked,
		us.email
		FROM {enrol} AS en
		INNER JOIN {user_enrolments} AS uen ON (en.id = uen.enrolid)
		INNER JOIN {course_modules} AS cm ON (en.courseid = cm.course AND cm.visible = ?)
		INNER JOIN {resource} AS r ON (cm.instance = r.id )
		INNER JOIN {modules} AS m ON (cm.module = m.id AND m.name = ?)
		INNER JOIN {user} AS us ON (uen.userid = us.id)
		INNER JOIN {facebook_user} AS fb ON (fb.moodleid = us.id AND fb.status = ?)
		WHERE r.timemodified > fb.lasttimechecked OR r.timemodified > us.lastaccess
		AND fb.facebookid IS NOT NULL
		GROUP BY cm.id, us.id";

$querylink = "SELECT url.id,
		COUNT(url.id) AS count,
		us.id AS userid,
		fb.facebookid,
		us.lastaccess,
		CONCAT(us.firstname,' ',us.lastname) AS name,
		fb.lasttimechecked,
		us.email
		FROM {enrol} AS en
		INNER JOIN {user_enrolments} AS uen ON (en.id = uen.enrolid)
		INNER JOIN {course_modules} AS cm ON (en.courseid = cm.course AND cm.visible = ?)
		INNER JOIN {url} AS url ON (cm.instance = url.id)
		INNER JOIN {modules} AS m ON (cm.module = m.id AND m.name = ?)
		INNER JOIN {user} AS us ON (uen.userid = us.id)
		INNER JOIN {facebook_user} AS fb ON (fb.moodleid = us.id AND fb.status = ?)
		WHERE url.timemodified > fb.lasttimechecked OR url.timemodified > us.lastaccess
		AND fb.facebookid IS NOT NULL
		GROUP BY url.id, us.id";
		
$queryemarking = "SELECT d.id, 
		COUNT(d.id) AS count,
		us.id AS userid,
		fb.facebookid,
		us.lastaccess,
		CONCAT(us.firstname,' ',us.lastname) AS name,
		fb.lasttimechecked,
		us.email
		FROM {emarking_draft} AS d JOIN {emarking} AS e ON (e.id = d.emarkingid AND e.type in (1,5,0))
		INNER JOIN {emarking_submission} AS s ON (d.submissionid = s.id AND d.status IN (20,30,35,40))
		INNER JOIN {user} AS us ON (s.student = us.id)
		INNER JOIN {user_enrolments} AS uen ON (us.id = uen.userid)
		INNER JOIN {enrol} AS en ON (en.id = uen.enrolid)
		INNER JOIN {course_modules} AS cm ON (cm.instance = e.id AND cm.course = en.courseid)
		INNER JOIN {modules} AS m ON (cm.module = m.id AND m.name = 'emarking')
		INNER JOIN {facebook_user} AS fb ON (fb.moodleid = us.id AND fb.status = ?)
		WHERE d.timemodified > fb.lasttimechecked OR d.timemodified > us.lastaccess)
		AND fb.facebookid IS NOT NULL
		GROUP BY us.id";

$queryassignments = "SELECTa.id, 
		COUNT(a.id) AS count,
		us.id AS userid,
		fb.facebookid,
		us.lastaccess,
		CONCAT(us.firstname,' ',us.lastname) AS name,
		fb.lasttimechecked,
		us.email
		FROM {assign} AS a
		INNER JOIN {course} AS c ON (a.course = c.id)
		INNER JOIN {enrol} AS e ON (c.id = e.courseid)
		INNER JOIN {user_enrolments} AS ue ON (e.id = ue.enrolid)
		INNER JOIN {user} AS us ON (us.id = ue.userid)
		INNER JOIN {course_modules} AS cm ON (c.id = cm.course AND cm.module = ? AND cm.visible = ?)
		INNER JOIN {assign_submission} AS s ON (a.id = s.assignment)
		INNER JOIN {facebook_user} AS fb ON (fb.moodleid = us.id AND fb.status = ?)
		WHERE a.timemodified > fb.lasttimechecked OR a.timemodified > us.lastaccess
		AND fb.facebookid IS NOT NULL
		GROUP BY a.id, us.id";



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

$arraynewposts = array();
$arraynewresources = array();
$arraynewlinks = array();
$arraynewemarkings = array();
$arraynewassignments = array();

$arraynewposts = addtoarray($queryposts, array_merge($paramspost, $paramsusers), $arraynewposts);
$arraynewresources = addtoarray($queryresources, array_merge($paramsresource, $paramsusers), $arraynewresources);
$arraynewlinks = addtoarray($querylink, array_merge($paramslink, $paramsusers), $arraynewlinks);
$arraynewemarkings = addtoarray($queryemarking, $paramsusers, $arraynewemarkings);
$arraynewassignments = addtoarray($queryassignments, array_merge($paramsassignment, $paramsusers), $arraynewassignments);

if ($facebookusers = $DB->get_records_sql($queryusers, $paramsusers)){
	foreach ($facebookusers as $users){
		$totalcount = 0;
		if (isset($arraynewposts[$users->id])){
			$totalcount = $totalcount + $arraynewposts[$users->id];
			mtrace($arraynewposts[$users->id]." notifications have been found in posts for user ".$users->id."\n");
		}
		if (isset($arraynewresources[$users->id])){
			$totalcount = $totalcount + $arraynewresources[$users->id];
			mtrace($arraynewresources[$users->id]." notifications have been found in resources for user ".$users->id."\n");
		}
		if (isset($arraynewlinks[$users->id])){
			$totalcount = $totalcount + $arraynewlinks[$users->id];
			mtrace($arraynewlinks[$users->id]." notifications have been found in links for user ".$users->id."\n");
		}
		if (isset($arraynewemarkings[$users->id])){
			$totalcount = $totalcount + $arraynewemarkings[$users->id];
			mtrace($arraynewemarkings[$users->id]." notifications have been found in emarkings for user ".$users->id."\n");
		}
		if (isset($arraynewassignments[$users->id])){
			$totalcount = $totalcount + $arraynewassignments[$users->id];
			mtrace($arraynewassignments[$users->id]." notifications have been found in assignments for user ".$users->id."\n");
		}
		mtrace("A total of ".$totalcount." notifications have been found for user ".$users->id."\n");
		mtrace("---------------------------------------------------------------------------------------------------");
		if ($users->facebookid != null && $totalcount != 0) {
			if ($totalcount == 1) {
				$template = "Tienes $totalcount notificación de Webcursos.";
			}
			else {
				$template = "Tienes $totalcount notificaciones de Webcursos.";
			}
			$data = array(
					"link" => "",
					"message" => "",
					"template" => $template
			);	
			$fb->setDefaultAccessToken($appid.'|'.$secretid);
			if (handleexceptions($fb, $users, $data)){
				$notifications = $notifications + 1;
			}
		}
	}
	mtrace("Notifications have been sent succesfully to ".$notifications."people.");
	$finaltime = time();
	mtrace("Execution time: ".$finaltime - $initialtime." seconds.");
}
exit(0);