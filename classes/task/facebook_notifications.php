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
		return get_string("tasks_facebook", "local_facebook");
	}
	public function execute(){
		global $DB, $CFG;
		require_once($CFG->dirroot."/local/facebook/locallib.php");
		
		$initialtime = time();
		$notifications = 0;
		$fb = facebook_newclass();
		
		list($posts, $resources, $links, $emarkings, $assignments) = facebook_queriesfornotifications();
		
		if ($facebookusers = facebook_getusers()){
			foreach ($facebookusers as $users){
				$totalcount = 0;
				if (isset($posts[$users->id])){
					$totalcount = $totalcount + $posts[$users->id];
				}
				if (isset($resources[$users->id])){
					$totalcount += $resources[$users->id];
				}
				if (isset($links[$users->id])){
					$totalcount += $links[$users->id];
				}
				if (isset($emarkings[$users->id])){
					$totalcount += $emarkings[$users->id];
				}
				if (isset($assignments[$users->id])){
					$totalcount += $assignments[$users->id];
				}
				if ($users->facebookid != null && $totalcount > 0) {
					if ($totalcount == 1) {
						$template = get_string("notificationcountA", "local_facebook").$totalcount.get_string("notificationcountsingular", "local_facebook");
					}
					else {
						$template = get_string("notificationcountA", "local_facebook").$totalcount.get_string("notificationcountplural", "local_facebook");
					}
					$data = array(
							"link" => "",
							"message" => "",
							"template" => $template
					);
					$fb->setDefaultAccessToken($appid.'|'.$secretid);
					if (facebook_handleexceptions($fb, $users, $data)){
						mtrace("Notifications sent to user with moodleid ".$users->id." - ".$users->name);
						$notifications = $notifications + 1;
					}
				}else{
					mtrace("No entra IF, Usuario ".$users->name." número ".$totalcount);
				}
			}
			mtrace("Notifications have been sent succesfully to ".$notifications." people.");
			$finaltime = time();
			$totaltime = $finaltime-$initialtime;
			mtrace("Execution time: ".$totaltime." seconds.");
		}	
	}	
}