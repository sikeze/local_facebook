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

/*highlight_string("<?php\n\$data =\n" . var_export($resources, true) . ";\n?>");*/
require_once (dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . '/config.php');
require_once ($CFG->dirroot . '/local/facebook/locallib.php');
require_once ($CFG->dirroot . "/local/facebook/app/Facebook/autoload.php");
global $DB, $USER, $CFG, $OUTPUT;
/*use Facebook\FacebookResponse;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequire;
use Facebook\Request;*/
$courses = enrol_get_users_courses($USER->id);
$notice = facebook_notificationspercourse($USER, $courses);
?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="css/materialize.min.css">
<link href="http://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">


</head>
<body>

	<!-- here start the fixed NavBar -->
	<header>
		<div class="navbar-fixed">
			<nav>
				<div class="nav-wrapper teal darken-3">
					<a href="#" class="brand-logo center">WebC</a>
					<ul id="nav-mobile" class="right hide-on-med-and-down">
						<?php 
						if($notice[0] == 0){
							echo "<li id='cursosli'><a>Cursos</a></li>";
						}
						else{
							
							echo "<li id='cursosli'><a>Cursos
 		 						  <span class='new badge light-blue darken-'>".$notice[0]."</span></a></li>";
							
						}
								?>
 		<li><a href=""><i class="material-icons right">home</i>Webcursos</a></li> 
					</ul>

					<ul>
						<li><a href="http://webcursos-d.uai.cl/local/facebook/connect.php" target="_blank">Cuenta</a></li>
						<li><a href="<?php echo $CFG->fbk_tutorialurl;?>" target="_blank"><?php echo $CFG->fbk_tutorialsname;?></a></li>
						<li><a href="http://webcursos.uai.cl/local/tutoriales/faq.php" target="_blank">Privacidad</a></li>
						<li><a class="tooltipped" data-position="bottom" data-delay="50" data-tooltip="Enviar mail a contacto" 
						       href="mailto:<?php echo $CFG->fbk_email;?>" >Contacto</a></li>
					</ul>
				</div>
			</nav>
		</div>
	</header>
	<!-- here Start the Popout collapsible -->


	<ul class="collapsible popout" data-collapsible="accordion">
		<!-- first row of the collapsible with the info cards -->
		<li class="hidecursosactuales">
			<div class="collapsible-header active">
				<i class="material-icons">notifications</i>Noticias
			</div>
			<div class="collapsible-body">

				<div class="row" style="height: 100%;">
					<!-- first card with info -->
					<div class="col s12 m6 l3" >
						<div class="card sticky-action z-depth-4 hoverable ">
							<div class="card-image waves-effect waves-block waves-light">
								<div class="row center-align">
									<i class="large material-icons activator"><?php echo $CFG->fbk_card1icon;?></i>
								</div>

							</div>
							<div class="card-content">
								<div class="right-align">
									<i class="material-icons right">more_vert</i>
								</div>
								<div class="row center-align">
									<span class="card-title activator grey-text text-darken-4"><?php echo $CFG->fbk_card1title;?> </span>
								</div>
								<p class="truncate"><?php echo $CFG->fbk_card1text;?></p>
							</div>

							<div class="card-action">
								<a class="waves-effect waves-light btn" href="<?php echo $CFG->fbk_card1link;?>" target="_blank"">More About</a>
							</div>

							<div class="card-reveal">
								<span class="card-title grey-text text-darken-4"><?php echo $CFG->fbk_card1title;?><i
									class="material-icons right">close</i></i></span>
								<p><?php echo $CFG->fbk_card1text;?></p>
							</div>
						</div>
					</div>
					<!-- second card with info -->
					<div class="col s12 m6 l3" >
						<div class="card sticky-action z-depth-4 hoverable">
							<div class="card-image waves-effect waves-block waves-light">
								<div class="row center-align">
									<i class="large material-icons center-align activator"><?php echo $CFG->fbk_card2icon;?></i>
								</div>
							</div>
							<div class="card-content">
								<div class="right-align">
									<i class="material-icons right">more_vert</i>
								</div>
								<span class="card-title activator grey-text text-darken-4"><?php echo $CFG->fbk_card2title;?> </span>

								<p class="truncate"><?php echo $CFG->fbk_card2text;?></p>
							</div>

							<div class="card-action">
								<a class="waves-effect waves-light btn" href="<?php echo $CFG->fbk_card2link;?>" target="_blank">More About</a>
							</div>

							<div class="card-reveal">
								<span class="card-title grey-text text-darken-4"><?php echo $CFG->fbk_card2title;?><i
									class="material-icons right">close</i></i></span>
								<p><?php echo $CFG->fbk_card2text;?></p>
							</div>
						</div>
					</div>
					<!-- third card with info -->
					<div class="col s12 m6 l3">
						<div class="card sticky-action z-depth-4 hoverable">
							<div class="card-image waves-effect waves-block waves-light">
								<div class="row center-align">
									<i class="large material-icons center-align activator"><?php echo $CFG->fbk_card3icon;?></i>
								</div>
							</div>
							<div class="card-content">
								<div class="right-align">
									<i class="material-icons right">more_vert</i>
								</div>
								<span class="card-title activator grey-text text-darken-4"><?php echo $CFG->fbk_card3title;?></span>

								<p class="truncate"><?php echo $CFG->fbk_card3text;?></p>
							</div>

							<div class="card-action">
								<a class="waves-effect waves-light btn" href="<?php echo $CFG->fbk_card3link;?>" target="_blank">More About</a>
							</div>

							<div class="card-reveal">
								<span class="card-title grey-text text-darken-4"><?php echo $CFG->fbk_card3title;?><i
									class="material-icons right">close</i></i></span>
								<p><?php echo $CFG->fbk_card3text;?></p>
							</div>
						</div>
					</div>
					<!-- fourth card with info -->
					<div class="col s12 m6 l3">
						<div class="card sticky-action z-depth-4 hoverable"">
							<div class="card-image waves-effect waves-block waves-light">
								<div class="row center-align">
									<i class="large material-icons center-align activator"><?php echo $CFG->fbk_card4icon;?></i>
								</div>

							</div>
							<div class="card-content">
								<div class="right-align">
									<i class="material-icons right">more_vert</i>
								</div>
								<div class="row center-align">
									<span class="card-title activator black-text text-darken-4"><?php echo $CFG->fbk_card4title;?></span>
								</div>
								<p class="truncate"><?php echo $CFG->fbk_card4text;?></p>
							</div>

							<div class="card-action">
								<div class="row center-align">
									<a class="waves-effect waves-light btn" href="<?php echo $CFG->fbk_card4link;?>" target="_blank">More About</a>
								</div>
							</div>

							<div class="card-reveal">
								<span class="card-title grey-text text-darken-4"><?php echo $CFG->fbk_card4title;?><i
									class="material-icons right">close</i></i></span>
								<p><?php echo $CFG->fbk_card4text;?></p>
							</div>
						</div>
					</div>
				</div>
			</div>

		</li>
		<!-- second row of the collapsible with the actual courses -->
		<li>
			<div class="collapsible-header" id="cursosactual">
				<i class="material-icons">school</i>Semestre Actual
			</div>
			<div class="collapsible-body">
				<div class="row loadcursosactuales"></div>
					<?php include_once "cursosactuales.php";?>
				</div>
		</li>
		<!-- third row of the collapsible with the manual enrol courses -->
		<li class="hidecursosactuales">
			<div class="collapsible-header">
				<i class="material-icons">add_box</i>Cursos Adicionales
			</div>
			<div class="collapsible-body">
				<div class="row"></div>
					<?php include_once "cursosadicionales.php";?>
				</div>
		</li>
<!--  -->
	<li class="hidecursosactuales">
			<div class="collapsible-header">
				<i class="material-icons">add_box</i>Cursos Generales
			</div>
			<div class="collapsible-body">
				<div class="row"></div>
					<?php include_once "cursosmeta.php";?>
				</div>
		</li>
<!--  -->		

	</ul>

	<script type="text/javascript" src="js/jquery-3.1.1.min.js"></script>
	<script type="text/javascript" src="js/materialize.min.js"></script>
	<script> 
$(document).ready(function(){
    $('.collapsible').collapsible();
 });
</script>
	<script> 
$(document).ready(function(){
	$( "#cursosli" ).click(function() {
		$('#cursosactual').click();
	});
});
	</script>
	
<script>
$( document ).ready(function() {
	$( ".curso" ).click(function() {
		var moodleidvar = $(this).attr("moodleid");
		var courseidvar = $(this).attr("courseid");
		$( ".cursosactuales" ).hide();
		$( ".hidecursosactuales" ).hide();
		$( ".loadcursosactuales" ).load( "coursetable.php" , {moodleid:moodleidvar, courseid:courseidvar});
	});
});
</script>
</body>
</html>