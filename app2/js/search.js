$(document).ready(function () {
	//script for searching courses
	$("#search").on('change keyup paste', function() {
		var searchValue = $('#search').val();
		$("button").each(function() {
			var buttonId = $(this).attr('courseid');

			if($(this).attr('fullname').toLowerCase().indexOf(searchValue) == -1) {
				$(this).hide();
				$(this).parent().css('height', '0');
			} else {
				$(this).show();
				$(this).parent().css('height', '4em');
			}
		});
	});
});