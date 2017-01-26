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
* @copyright  2017 Jorge Cabané (jcabane@alumnos.uai.cl)
* @copyright  2017 Joaquin Rivano (jrivano@alumnos.uai.cl)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
$cursos = facebook_getcoursesbyenrolment ( "self", $USER->id );
// var_dump($cursos);
$count = 1;
echo '<div class="row">';
$cantidadcursos = count ( $cursos );
if ($cursos == true) {
	foreach ( $cursos as $curso ) {
		if ($count == 1) {
			if ($cantidadcursos <= 5) {
				echo "<div class='col l6 offset-l3'>";
			} else if ($cantidadcursos > 5 && $cantidadcursos <= 10) {
				echo "<div class='col l5 offset-l1'>";
			} else if ($cantidadcursos > 10) {
			}
		}
		
		if ($notice[$curso->id] != 0) {
			echo '<div class = "row"><a class="waves-effect waves-light btn truncate" style="width:100%" href="coursetable.php">' . $curso->fullname . '</a><span class="new badge deep-orange accent-3" data-badge-caption="Recurso Nuevo">' . $notice [$curso->id] . '</span></div>';
		} else {
			echo '<div class = "row"><a class="waves-effect waves-light btn truncate" style="width:100%" href="coursetable.php">' . $curso->fullname . '</a></div>';
		}
		
		if ($count % 5 == 0) {
			echo "</div>";
			
			if ($cantidadcursos > 5 && $cantidadcursos <= 10 && $count < 10) {
				echo "<div class='col l5'>";
			} else if ($cantidadcursos > 10) {
			}
		}
		
		$count ++;
	}
} else {
	echo "No hay cursos inscritos este semestre";
}
echo '</div>';
?>