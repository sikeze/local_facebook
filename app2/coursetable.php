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
* @copyright  2017 Javier Gonzalez (javiergonzalez@alumnos.uai.cl)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once (dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . '/config.php');
require_once ($CFG->dirroot . '/local/facebook/locallib.php');

global $DB, $USER;
$moodleid = $_REQUEST["moodleid"];
$courseid = $_REQUEST["courseid"];

$resources = get_course_data($moodleid,$courseid);

//paperattendance percentage

$sessions = $DB->count_records("paperattendance_session", array ("courseid" => $courseid));
if($sessions){
	$present = "SELECT COUNT(*)
				FROM {paperattendance_presence} AS p
				INNER JOIN {paperattendance_session} AS s ON (s.id = p.sessionid AND p.status = 1 AND s.courseid = ? AND p.userid = ?)";
	$present = $DB->count_records_sql($present, array($courseid, $USER->id));
	$absent = $sessions - $present;
	$percentagestudent = round(($present/$sessions)*100);
}
else{
	$percentagestudent = 0;
}
?>




<div class="row">
	<a href="index.php"><i class="material-icons">arrow_back</i></a>
</div>

<div class="row"><?php echo $percentagestudent."%";?>
 <div class="progress col l2 m2 s2 offset-l1 ">
      <div class="determinate" style="height:100px; width: <?php echo $percentagestudent."%";?>"></div>
 </div>
	<div class="col l4 offset-l8">
		<a filter="resource" class="btn-floating btn-large waves-effect waves-light green z-depth-5 filter"><img src="images/resource.png"></a>
		<a filter="emarking" class="btn-floating btn-large waves-effect waves-light green z-depth-5 filter"><img src="images/emarking.png"></a> 
		<a filter="link" class="btn-floating btn-large waves-effect waves-light green z-depth-5 filter"><img src="images/link.png"></a> 
		<a filter="post" class="btn-floating btn-large waves-effect waves-light green z-depth-5 filter"><img src="images/post.png"></a> 
		<a filter="	" class="btn-floating btn-large waves-effect waves-light green z-depth-5 filter"><img src="images/resource.png"></a> 
	</div>
</div>
<div class="container">
	<table class="highlight">
		<thead>
			<tr>
				<th data-field="id">Tipo</th>
				<th data-field="name">Nombre</th>
				<th data-field="lastmodified">Ultima Modificacion</th>
			</tr>
		</thead>

		<tbody>
		<?php 
		//TODO: agregar un discussionid y un moduleid al "<a>" y hacer una pagina con el ajax defbk
			foreach($resources as $resource){
				$time = paperattendance_convertdate($resource["date"]);
				echo '<tr type="'.$resource["image"].'" class="'.$resource["image"].'">
						<td><img src="images/'.$resource["image"].'.png"></td>
						<td><a class="openmodal" >'.$resource["title"].'</a></td>
						<td>'.$time.'</td>
					</tr>' ;
			}
		?>
			
		</tbody>
	</table>
</div>

<!-- Modal -->
  <div id="modal" class="modal modal-fixed-footer">
    <div class="modal-content">
      <h4>Modal Header</h4>
      <p>A bunch of text</p>
    </div>
    <div class="modal-footer">
      <a href="#!" class="modal-action modal-close waves-effect waves-green btn-flat ">Agree</a>
    </div>
  </div>
          
<script>
$( document ).ready(function() {
	$( ".filter" ).click(function() {
		var button = $(this);
		var filter = button.attr("filter");
		var tr = $("tr");
		$.each(tr, function( index, value ) {
			 if( $(this).hasClass(filter) ) {
				 $(this).toggle( "slow" );
			 }
		});
		if(button.hasClass("green")){
			button.removeClass("green").addClass("grey");
			button.removeClass("z-depth-5").addClass("z-depth-1");
		}
		else{
			button.removeClass("grey").addClass("green");
			button.removeClass("z-depth-1").addClass("z-depth-5");
		}
	});

});
</script>
<script>
$(document).ready(function(){
    $('.modal').modal();
	$( ".openmodal" ).click(function() {
		var type = $(this).parent().parent().attr("type");
		if( type == "post" || type == "emarking"){	
			$('#modal').modal('open');
		}
	});
});
</script>