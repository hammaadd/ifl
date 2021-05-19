(function($) {
	'use strict'

	/*------------------------------------------------------------------------------*/
	/* TopSearch
	/*------------------------------------------------------------------------------*/
	jQuery( ".topbar-serach .btn" ).addClass('sclose');

	jQuery( ".topbar-serach .btn" ).on('click', function(event ) {

		jQuery(".field.searchform-s").focus();

		if (jQuery('.topbar-serach .btn').hasClass('sclose')) {
			jQuery( ".topbar-serach .btn i" ).removeClass('fa-search').addClass('fa-times');
			jQuery(this).removeClass('sclose').addClass('open');
			jQuery(".search-overlay").addClass('st-show');
		} else {
			jQuery(this).removeClass('open').addClass('sclose');
			jQuery( ".topbar-serach .btn i" ).removeClass('fa-times').addClass('fa-search');
			jQuery(".search-overlay").removeClass('st-show');
		}
		event.preventDefault(); 
	});


	/*------------------------------------------------------------------------------*/
	/* Enable tooltips everywhere
	/*------------------------------------------------------------------------------*/
	var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
	var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
		return new bootstrap.Tooltip(tooltipTriggerEl)
	})

	var divWidth = jQuery('.flip-box-one').width();

	jQuery(".single-flip-box").each(function(){
		if (jQuery(this).height() > divWidth) { 
			divWidth = jQuery(this).height(); 
		}
	});

	jQuery(".single-flip-box").height(divWidth);


	// $.fn.equalHeight = function() {
	// 	var maxHeight = 0;
	// 	return this.each(function(index, box) {
	// 		var boxHeight = $(box).height();
	// 		maxHeight = Math.max(maxHeight, boxHeight);
	// 	}).parent().height(maxHeight);
	// };

	// $('.team-wrap .col-md-3 .single-team-box').equalHeight();

	// $(window).resize(function () {
	// 	$('.team-wrap .col-md-3 .single-team-box').equalHeight();
	// });



})(jQuery);

function showNav() {
	var element = document.getElementById("navbars");
  	element.classList.toggle("d-block");
}

document.addEventListener( 'DOMContentLoaded', function () {
	new Splide( '.splide', {
		type: 'loop',
		autoplay: true
	} ).mount();
} );