$(document).ready(function() {
	setTimeout(updateKillsLastHour, 60000);

	// Check to see if the user has ads enabled
	if ( $("iframe").length == 0 ) {
		$("#adsensetop, #adsensebottom").html("<div>Advertising seems to be blocked by your browser.<br/>This isn't very nice as the ads pay for the servers!<br/>May all your ships quickly become wrecks...</div>");
	}

    if ($("[rel=tooltip]").length) {
		$("[rel=tooltip]").tooltip({
			placement: "bottom",
			animation: false
		});
    }

	//
    $('.dropdown-toggle').dropdown();
    $("abbr.timeago").timeago();
    $(".alert").alert()

    // Javascript to enable link to tab
    var url = document.location.toString();
    if (url.match('#')) {
        $('.nav-pills a[href=#'+url.split('#')[1]+']').tab('show') ;
    }

    // Change hash for page-reload
    $('.nav-pills a').on('shown', function (e) {
        window.location.hash = e.target.hash;
    })

	// hide #back-top first
	$("#back-top").hide();

	// fade in #back-top
	$(function () {
		$(window).scroll(function () {
			if ($(this).scrollTop() > 500) {
				$('#back-top').fadeIn();
			} else {
				$('#back-top').fadeOut();
			}
		});

		// scroll body to 0px on click
		$('#back-top a').click(function () {
			$('body,html').animate({
				scrollTop: 0
			}, 100);
			return false;
		});
	});
	
	//add the autocomplete search thing
	$('#searchbox').zz_search( function(data, event) { window.location = '/' + data.type + '/' + data.id + '/'; event.preventDefault(); } );
	
	//and for the tracker entity lookup
	$('#addentitybox').zz_search( function(data) { 
		$('#addentity input[name="entitymetadata"]').val(JSON.stringify(data));
		$('#addentity input[name="addentitybox"]').val(data.name);
		$('#addentity').submit();
	});

    // prevent firing if window.location in table rows if a link is clicked directly
	$('.killListRow a').click(function(e) {
		e.stopPropagation();
	});
});

function updateKillsLastHour() {
	$("#lasthour").load("/killslasthour/");
	setTimeout(updateKillsLastHour, 60000);
}

$('body').on('touchstart.dropdown', '.dropdown-menu', function (e) { e.stopPropagation(); });
