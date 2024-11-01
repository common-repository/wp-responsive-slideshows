/**
* WRS Plugin Front End Script
* @Version 1.0.0
* @author Sagar Pansare
*/

//Localize Variables 
var slider_script = slideshow_object.slider_script;

//Load Slider Script JS
eval(slider_script);

var WRSFRONT = {
	run: function() {
		this.slideshow = new this.SLIDESHOW();
	}
};

WRSFRONT.SLIDESHOW = Backbone.View.extend({
	
	el: "body",
	slideshow_data: slideshow_object.slideshow_data,

	play_video_popup_template: _.template(jQuery('#WRS_PLAY_VIDEO_POPUP_TML').html()),

	initialize: function() {
		_.bindAll(this, 'renderSlideshow');
		this.renderSlideshow();
	},

	events: {
		"click .wrs_slideshow .video .video_play_button .play_icon" : "playVideo",
		"click .wrs_close_video_modal" : "closeVideo",
	},

	renderSlideshow: function() {
		var _this = this;
		var length = _this.slideshow_data.length;
		for( var i=0; i< length; i++ ) {
			slideshow_data = _this.slideshow_data[i];
			_this.sliderCallback( slideshow_data );
		}
	},

	sliderCallback: function( slideshow_data ) {
		var _this = this;
		var slideshow_id = slideshow_data[0];
		var loop = jQuery('#wrs_slideshow_'+slideshow_id).attr("data-loop");
		var autoplay = jQuery('#wrs_slideshow_'+slideshow_id).attr("data-autoplay");
		var autoplay_timeout = jQuery('#wrs_slideshow_'+slideshow_id).attr("data-autoplay-timeout");
		var navigation = jQuery('#wrs_slideshow_'+slideshow_id).attr("data-navigation");
		var prevnavtext = jQuery('#wrs_slideshow_'+slideshow_id).attr("data-nav-prev-text");
		var nextnavtext = jQuery('#wrs_slideshow_'+slideshow_id).attr("data-nav-next-text");
		var dotsnavigation = jQuery('#wrs_slideshow_'+slideshow_id).attr("data-dots-navigation");

		loop = loop == 'true' ? true : false;
		autoplay = autoplay == 'true' ? true : false;
		navigation = navigation == 'true' ? true : false;
		dotsnavigation = dotsnavigation == 'true' ? true : false;
		autoplay_timeout = autoplay_timeout == '' ? 8000 : autoplay_timeout;
		
		jQuery('#wrs_slideshow_'+slideshow_id).owlCarousel({
			loop: loop,
		    margin: 10,
		    nav: navigation,
		    dots: dotsnavigation,
		    navText: [prevnavtext, nextnavtext],
		    items: 1,
		    autoHeight: true,
		    lazyLoad: true,
		    autoplay: autoplay,
    		autoplayTimeout: autoplay_timeout,
    		smartSpeed: 1000,
    		autoplayHoverPause: true
		});
	},

	playVideo: function( event ) {
		var _this = this;
		var video_url = "";
		var _target = jQuery( event.target );
		var _parent = _target.parent().parent();
		var video_type = _parent.attr("data-video-type");
		var video_id = _parent.attr("data-video-id");
		if(video_type=="youtube") {
			video_url = "http://www.youtube.com/embed/"	+ video_id + "?autoplay=1&v=" + video_id;
		} else {
			video_url = "http://player.vimeo.com/video/" + video_id + "?autoplay=1";
		}
		_this.$el.append( _this.play_video_popup_template({ video_url: video_url }) );
	},

	closeVideo: function( event ) {
		var _this = this;
		_this.$el.find(".wrs_play_video_popup").remove();
		return false;
	}

});

jQuery(document).ready(function(){
	WRSFRONT.run();
});