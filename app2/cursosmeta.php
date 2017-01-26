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
* @copyright  2017 Joaquin Rivano (jrivano@alumnos.uai.cl)
* @copyright  2017 Javier Gonzalez (javiergonzalez@alumnos.uai.cl)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
$cursos = facebook_getcoursesbyenrolment("meta", $USER->id);
//var_dump($cursos);
$count = 1;
echo '<div class="row">';
if (empty($cursos)){
	echo "Nop hay cursos que mostrar";
}
else{
	foreach($cursos as $curso){
		if($count == 1){
			echo '<div class="col l5 offset-l1 ">';
		}
		else if($count == 6){
			echo '<div class="col l5">';
		}
		echo '<div class = "row"><a class="waves-effect waves-light btn truncate" style="width:100%">' . $curso->fullname . '</a><span class="new badge red">'.$notice[$curso->id].'</span></div>';

		if($count == 5 || $count == 10){
			echo '</div>';
		}
		$count ++;
	}
}
echo '</div>';
?>