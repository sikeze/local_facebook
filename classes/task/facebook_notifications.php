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
* @copyright  2017 Javier González (javiergonzalez@alumnos.uai.cl)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

namespace local_facebook\task;

class facebook_notifications extends \core\task\scheduled_task {
	public function get_name() {
		return get_string("task_courses", "local_sync");
	}
	public function execute(){
		$appid = $CFG->fbk_appid;
		$secretid = $CFG->fbk_scrid;
		$fb = facebookclass($appid, $secretid);
		$initialtime = time();
		$sent = 0;


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
	}
}