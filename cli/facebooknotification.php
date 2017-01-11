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
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once ($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot."/local/facebook/app/Facebook/autoload.php");
require_once($CFG->dirroot."/local/facebook/app/Facebook/FacebookRequest.php");
include $CFG->dirroot."/local/facebook/app/Facebook/Facebook.php";
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
$appid = $CFG->fbk_appid;
$secretid = $CFG->fbk_scrid;
$sent = 0;
$fb = new Facebook([
		"app_id" => $appid,
		"app_secret" => $secretid,
		"default_graph_version" => "v2.5"]);



if($facebokusers = getfacebookusersid()){
	foreach($facebookusers as $user){
		$courseidarray = getusercoursesids($users);
	}
	if(!empty($courseidarray)){
		// Use the last time in web or app
		if($user->lastaccess < $user->lasttimechecked){
			$user->lastaccess = $user->lasttimechecked;
		}
		$notifications = countnotifications($courseidarray);
		if ($user->facebookid != null && $notifications != 0) {
			$data = getarraynotification($notifications);
			$fb->setDefaultAccessToken($appid.'|'.$secretid);
			handleexceptions($fb, $user, $data);
			$sent += $notifications;
		}
	}
	print $sent." Notifications sent. \n";

	$finaltime = time();
	$executiontime = $finaltime - $initialtime;

	mtrace("Execution time: ".$executiontime." seconds. \n");
}
exit(0);