/**
* WRS Plugin Admin End Script
* @Version 1.0.0
* @author Sagar Pansare
*/

var WRS = {
	run: function() {
		this.image_manager = new this.SlideshowMediaManager();
	}
}

WRS.SlideshowMediaManager = Backbone.View.extend({

	el: "body",
	
	//Templates instances
	template: _.template(jQuery('#WRS_ADD_NEW_MEDIA_TML').html()), 
	insertimagebyurl_template: _.template(jQuery('#WRS_INSERT_IMAGEBYURL_TML').html()),
	video_template: _.template(jQuery('#WRS_ADD_NEW_VIDEO_TML').html()),
	edit_template: _.template(jQuery('#WRS_EDIT_MEDIA_TML').html()), 
	video_popup_template: _.template(jQuery('#WRS_ADD_NEW_VIDEO_POPUP_TML').html()), 
	play_video_popup_template: _.template(jQuery('#WRS_PLAY_VIDEO_POPUP_TML').html()),

	initialize: function() {
		_.bindAll(this, 'render', 'addSlideShowImages');
		this.media_frame_id = "";
		this.frame = "";
		this.render();
	},

	wrs_sortables: function()  {
		var _this = this;
		_this.$el.find(".slideshows_media_inner").sortable({
		    start: function(event, ui) {
		    	ui.item.css("cursor", "move");
		    },
		    stop: function(event, ui) {
		       ui.item.css("cursor", "pointer");
		       _this.$el.find("#post").submit();
		    }
		});
	},

	slideshow_update_messages: function() {
		jQuery(".post-type-wrs_slideshow form#post").before('<div class="updated below-h2" id="slideshow_update_response_message"><p>Slideshow Updated Successfully.</p></div>');
		jQuery("#edit_slideshow_post_popup form#post").before('<div class="updated below-h2" id="slideshow_update_response_message"><p>Slideshow Updated Successfully.</p></div>');
	},

	render: function() {
		jQuery(".post-type-wrs_slideshow form#post").addClass("slideshow_submit_form");
		jQuery("#edit_slideshow_post_popup form#post").addClass("slideshow_submit_form");
		this.wrs_sortables();
		this.slideshow_update_messages();
	},

	events: {
		"submit .slideshow_submit_form" : "updateSlideshowAJAX",
		"click .insert_slideshow_images" : "addSlideShowImages",
		"click .imagebyurlmenu" : "insertImageByURLFrame",
		"click .media-menu-item" : "activeOtherTabs",
		"click .upload_image_by_url" : "uploadImageByURL",
		"click .insert_slideshow_video" : "openInsertVideoPopup",
		"click .add_video" : "addSlideShowVideos",
		"click .slideshow_media .edit_media" : "editSlideShowMedia",
		"click .slideshow_media .remove_media" : "removeSlideShowMedia",
		"click .slideshow_media .download_video_image" : "downloadVideoThumbnail",
		"click .change_media" : "changeMedia",
		"click .save_edit_media" : "saveEditMedia",
		"click .play_video" : "playVideoModal",
		"click .close_video_modal" : "closeVideoModal",
		"click .slideshow_slides input" : "multipleSlideSettings",
	},

	//Event for Update Slideshow By AJAX
	updateSlideshowAJAX: function(event) {
		var _this = this;
		var original_post_status = _this.$el.find("#original_post_status").val();
		_this.$el.find("#save_slideshow_loader").css("display", "inline-block");
		var _message = _this.$el.find("#slideshow_update_response_message");
		var _lost_connection = _this.$el.find("#lost-connection-notice");
		if( original_post_status == "publish" ) {
			var form = jQuery( event.target );
			var postData = form.serialize();
			jQuery.ajax({
				type: 'POST',				
		        url: wrs_script.ajaxurl, 
		        data: {
		           	postData: postData,	
		           	action: "update_post"
		        },
		        dataType: 'json',
		        success: function(res) {
		        	_lost_connection.hide();
		        	if(res.response=="success") {
		        		_message.show().removeClass("error").html("<p>Slideshow Updated Successfully.</p>");
		        	} else {
		        		_message.show().addClass("error").html("<p><b>Error: </b> Slideshow Not Updated Successfully.</p>");
		        	}	
		        	setTimeout(function(){
		        	_this.$el.find("#save_slideshow_loader").hide();
		        	}, 600);
				},
				error: function() {
					_message.hide();
					_lost_connection.show();
					setTimeout(function(){
						_this.$el.find("#post").submit();
					}, 800);
					setTimeout(function(){
		        		_this.$el.find("#save_slideshow_loader").hide();
		        	}, 200);
				}
			});
			return false;
		}
	},

	//Event for Adding Slideshow Images using WP Media Uploader
	addSlideShowImages: function(event, action) {
			var _this = this;
			var set_multiple = true;
			var frame;

			if(action=="change") { 
				set_multiple = false
			} 
			// Handle results from media manager.
			if ( _this.frame ) {
				_this.frame.open();
			} else {
				_this.frame = wp.media({
					title : "WP Responsive Slideshows Image Uploader",
					multiple : set_multiple,
					library : { type : 'image' },
					button : { text : "Add Image" },
				});

				_this.frame.open();
				_this.media_frame_id = _this.frame.el.attributes.id.nodeValue;
				setTimeout(function(){
					var imagebyurl = '<a href="#" class="imagebyurlmenu">Insert Image By URL</a>';
					var uploader_frame = jQuery("#"+_this.media_frame_id);
					uploader_frame.find(".media-frame-router .media-router").append(imagebyurl);
					var custom_frame_content = "<div class='wrs-media-frame-content'></div>";
					jQuery(custom_frame_content).insertAfter(uploader_frame.find(".media-frame-content"));
				}, 500);
			}
			
			var modal = _this.frame.el;
			jQuery(modal).parent().parent().addClass("wrs_slideshow_image_uploader");

			_this.frame.on('select',function( ) {
				var attachments = _this.frame.state().get('selection').toJSON();
				if( attachments.length > 0 ) {
					for(var i=0; i< attachments.length; i++) {
						var image = attachments[i];
						var media_url = image.url;
						var media_id = image.id;
						var _image_exist;
						_image_exist = jQuery("#slideshow_media_"+media_id);
						if(_image_exist.length == 0){
							if(action!="change") { 
								_this.$el.find(".slideshows_medias .slideshows_media_inner").append( _this.template({ media_url: media_url, media_id: media_id }) );
								_this.$el.find("#post").submit();
							} else {
								_this.changeImage(media_id, media_url);
							}
						} else {
							_image_exist.addClass("media_exist");
						}
					}
					setTimeout(function(){ _this.$el.find(".slideshows_media_inner .slideshow_media").removeClass("media_exist"); }, 1200);
					_this.wrs_sortables();
				}
			});

			return false;
	},

	//Insert Image By URL Media Frame into WP Media
	insertImageByURLFrame: function(event) {	
		var _this = this;
		var _target = jQuery( event.target );
		var _parent = _target.parent();
		_parent.find("a").removeClass("active");
		_target.addClass("active");
		media_frame_id = _this.media_frame_id;
		jQuery("#"+media_frame_id).find(".media-frame-content").hide();
		jQuery("#"+media_frame_id).find(".media-frame-toolbar").hide();
		var _wrs_media_frame_content = jQuery("#"+media_frame_id).find(".wrs-media-frame-content");
		_wrs_media_frame_content.show().html(_this.insertimagebyurl_template);
		jQuery(".insert_image_url").val("");
		return false;
	},
	activeOtherTabs: function(event) {
		var _this = this;
		var _target = jQuery( event.target );
		var _parent = _target.parent();
		_parent.find("a").removeClass("active");
		_target.addClass("active");
		media_frame_id = _this.media_frame_id;
		jQuery("#"+media_frame_id).find(".wrs-media-frame-content").hide();
		jQuery("#"+media_frame_id).find(".media-frame-toolbar").show();
		jQuery("#"+media_frame_id).find(".media-frame-content").show();
	},

	//Upload Images By URL
	uploadImageByURL: function(event) {
		var _this = this;
		var _target = jQuery( event.target );
		var _parent = _target.parent();
		var image_urls = _parent.find(".insert_image_url").val();
		var images_array = Array();
		var _selected_images = Array();
		if(image_urls!="") {
			jQuery(".insert_image_byurl_loader").css("opacity", "1");
			
			images_array = image_urls.split(",");
			len = images_array.length;
			if ( len!== 0) {
				k=0;
				_this.ImageByURLUploaderAJAX(images_array, len, k, _selected_images);
			}	
		}
	},

	ImageByURLUploaderAJAX: function(images_array, length, k, _selected_images) {
		var _this = this;
		image_url = images_array[k];
		var _selected_image;
		jQuery.ajax({
				type: 'POST',				
		        url: wrs_script.ajaxurl, 
		        data: {
		           	image_url: image_url,
		           	action: "insert_image_by_url"
		    	},
		        dataType: 'json',
		        success: function(res) {
		        	media_frame = _this.media_frame_id;
		        	media_frame_inner = jQuery("#"+media_frame).find(".wrs_insert_image_by_url .external_uploaded_images");
		        	image = "<img src='"+res.image_url+"' />";
		        	media_frame_inner.append(image);
		        	setTimeout(function(){
		        		media_frame_inner.find("img").css("opacity", "1");
		        	}, 200);
		        	     	
		        	_selected_images.push(res);
		        	
		        	if(length != k+1) {
		        		_this.ImageByURLUploaderAJAX(images_array, len, k+1, _selected_images);
		        	} else {	
		        		_this.frame.content.get('gallery').collection.props.set({ignore: (+ new Date())});
		        		setTimeout(function() {
							jQuery(".insert_image_byurl_loader").css("opacity", "0");
							jQuery("#"+media_frame).find(".wrs-media-frame-content").hide();
							jQuery("#"+media_frame+" .media-frame-router").find("a").removeClass("active");
							jQuery("#"+media_frame+" .imagebyurlmenu").prev().addClass("active");
							jQuery("#"+media_frame).find(".media-frame-toolbar").show().find("a.button").removeAttr("disabled");
							jQuery("#"+media_frame).find(".media-frame-content").show();
							var selection = wp.media.frame.state().get('selection');
							for(var j=0; j<_selected_images.length; j++) {
								image_url = _selected_images[j].image_url;
								id = _selected_images[j].id;
								_selected_image = jQuery("#"+media_frame+ " .media-frame-content .attachments-browser .attachments").find( "img[src='"+image_url+"']" ).parent().parent().parent().parent();
								_selected_image.addClass("details selected");
								attachment = wp.media.attachment(id);  // get attachment with id
    	      					attachment.fetch();
	          					selection.add(attachment);  // add attachment to selection collection
							}
						}, 1200);
					} 
		        }
		});
		return;
	},

	//Event for Opening Insert Slideshow Videos Popup
	openInsertVideoPopup: function(event, action) {	
		var _this = this;
		var video_url = "";
		if(action!="") {
			action = action;
		} else {
			action = "";
		}
		_this.$el.append( _this.video_popup_template({ action: action }) );
		return false;
	},

	//Event for Adding Slideshow Video
	addSlideShowVideos: function(event) {
		var _this = this;
		var _target = jQuery( event.target );
		var _parent = _target.parent();
		var _action = _target.attr("action");
		var video_url = _parent.find("input").val();
		var video_id = "";
		var media_url = "";
		var media_id = "";
		var _video_exist;
		
		if(video_url) {
			_parent.find("input").css("border-color", "#ddd");
			_parent.find("input").attr("placeholder", "http://");

			id = video_url.match(/(http:|https:|)\/\/(player.|www.)?(vimeo\.com|youtu(be\.com|\.be|be\.googleapis\.com))\/(video\/|embed\/|watch\?v=|v\/)?([A-Za-z0-9._%-]*)(\&\S+)?/);
			if(id!=null){

				if (id[3].indexOf('youtu') > -1) {
						type = 'youtube';
				} else if (id[3].indexOf('vimeo') > -1) {
					type = 'vimeo';
				}
			
				if( type == "youtube" ){
					video_id = id[6];
					caption = "";
					media_id = video_id;
					_video_exist = jQuery("#slideshow_media_"+media_id);
					if(!_video_exist.length>0) {
						if(_action!="change") {
							_this.$el.find(".slideshows_medias .slideshows_media_inner").append('<i class="fa fa-circle-o-notch fa-fw fa-spin slideshow_video_loader"></i>');
						}
						jQuery.ajax({
							url: "http://gdata.youtube.com/feeds/api/videos/"+video_id+"?v=2&alt=json&orderby=published&prettyprint=true",
							dataType: "jsonp",
							success: function (data) {
								caption = data.entry.title.$t;
								media_url = "http://img.youtube.com/vi/" + video_id + "/hqdefault.jpg";
								if(_action!="change") {
									_this.$el.find(".slideshows_medias .slideshows_media_inner").append( _this.video_template({ media_url: media_url, media_id: media_id, video_type: type, caption: caption  }) );
									_this.$el.find(".slideshow_video_loader").remove();
									_this.$el.find("#post").submit();
								} else {
									_this.changeVideo(media_id, media_url, type, caption);
								} 
							}
						});
					} else {
						_video_exist.addClass("media_exist");
						setTimeout(function(){ _video_exist.removeClass("media_exist"); }, 1200);
					}
					
				} else {
					video_id = video_url.split('/').slice(-1)[0].split('?')[0];
					media_id = video_id;
					_video_exist = jQuery("#slideshow_media_"+media_id);
					if(!_video_exist.length>0) {
						if(_action!="change") {
							_this.$el.find(".slideshows_medias .slideshows_media_inner").append('<i class="fa fa-circle-o-notch fa-fw fa-spin slideshow_video_loader"></i>');
						}
					}
					jQuery.ajax({
						type: 'GET',
						url: 'http://vimeo.com/api/v2/video/' + video_id + '.json',
						jsonp: 'callback',
						dataType: 'jsonp',
						success: function(data) {
							caption = data[0].title;
							media_url = data[0].thumbnail_large;
							if(!_video_exist.length>0) {
								if(_action!="change") {
									_this.$el.find(".slideshows_medias .slideshows_media_inner").append( _this.video_template({ media_url: media_url, media_id: media_id, video_type: type, caption: caption  }) );
									_this.$el.find(".slideshow_video_loader").remove();
									_this.$el.find("#post").submit();
								} else {
									_this.changeVideo(media_id, media_url, type, caption);
								}
							} else {
								_video_exist.addClass("media_exist");
								setTimeout(function(){ _video_exist.removeClass("media_exist"); }, 1200);
							}
						},
						error: function(jqXHR, textStatus, errorThrown) {
							console.log(textStatus);
						}
					});
				}
				jQuery(".video_popup_overlay").remove();
				jQuery(".insert_video_popup").remove(); 			
			} else {
				_parent.find("input").css("border-color", "red");
				_parent.find("input").val("");
				_parent.find("input").attr("placeholder", "Please Correct Video URL's");
			}

			
		} else {
			_parent.find("input").css("border-color", "red");
			_parent.find("input").attr("placeholder", "Please Insert Video URL");
		}
		return false;
	},

	//Event for Edit Slideshow Media
	editSlideShowMedia: function(event) {
		var _this = this;
		var _target = jQuery( event.target );
		var _parent_target = _target.parent();
		_this.$el.find("#wrs_edit_media_meta_box").fadeIn(function() {
			
		});
		var media_url = _parent_target.find("img").attr("src");
		var media_id = _parent_target.attr("data-media-id");
		var caption = _parent_target.find(".slideshow_media_caption").val();
		var media_type = _parent_target.attr("data-media-type");
		_this.$el.find("#wrs_edit_media_meta_box .inside").html( _this.edit_template({ media_url: media_url, media_id: media_id, caption: caption, media_type: media_type }) );
	},

	//Event for Remove Slideshow Media
	removeSlideShowMedia: function(event) {
		var _this = this;
		var _target = jQuery( event.target );
		_target.parent().remove();
		_this.$el.find("#wrs_edit_media_meta_box").fadeOut();
		_this.$el.find("#post").submit();
	},

	changeMedia: function(event) {
		var _this = this;
		var _target = jQuery( event.target );
		var _parent = _target.parent();
		var media_type = _target.attr("media_type");
		if( media_type == "image" ){
			_this.addSlideShowImages("", "change");
		} else {
			_this.openInsertVideoPopup("", "change");
		}
	},

	saveEditMedia: function(event) {
		var _this = this;
		var _target = jQuery( event.target );
		var _parent = _target.parent();
		var media_caption = _parent.find("#media_caption").val();
		var media_id = _parent.attr("data-media-id");
		_this.$el.find("#slideshow_media_"+media_id+" .slideshow_media_caption").val( media_caption );
		_this.$el.find("#wrs_edit_media_meta_box").fadeOut();
		_this.$el.find("#post").submit();
	},

	downloadVideoThumbnail: function(event) {
		var _this = this;
		var _target = jQuery( event.target );
		var _parent = _target.parent();
		var video_id = _parent.attr("data-media-id");
		var image_url = _parent.find("img").attr("src");
		var caption = _parent.find(".slideshow_media_caption").val();
		_parent.find(".download_video_image_loader").show();
		jQuery.ajax({
			type: 'POST',				
	        url: wrs_script.ajaxurl, 
	        data: {
	           	image_url: image_url,
	           	video_id: video_id,
	           	image_name: caption,
	           	action: "store_video_thumbnail_images"
	        },
	        dataType: 'json',
	        success: function(res) {
	        	var _image_id = res.id;
	            _parent.find("img").attr("src", res.image_url);
	            _parent.find(".slideshow_video_image_id").val(_image_id);
	            _parent.find(".download_video_image_loader").hide();
				_parent.find(".download_video_image").remove();
				_this.$el.find("#post").submit();
			}
		});
		return false;
	},

	playVideoModal: function(event) {
		var _this = this;
		var video_url = "";
		var _target = jQuery( event.target );
		var _parent = _target.parent();
		var video_type = _parent.attr("data-video-type");
		var video_id = _parent.attr("data-video-id");
		if(video_type=="youtube") {
			video_url = "http://www.youtube.com/embed/"	+ video_id + "?autoplay=1&v=" + video_id;
		} else {
			video_url = "http://player.vimeo.com/video/" + video_id + "?autoplay=1";
		}
		_this.$el.append( _this.play_video_popup_template({ video_url: video_url }) );
	},

	closeVideoModal: function(event) {
		var _this = this;
		var _target = jQuery( event.target );
		jQuery(".video_popup_overlay").remove();
		jQuery(".video_popup").remove();
		return false;
	},

	changeImage: function(media_id, media_url) {
		var _this = this;
		var _media_exist = jQuery("#slideshow_media_"+media_id);
		if(!_media_exist.length>0) {
			var old_media_id = _this.$el.find(".edit_slideshow_media").attr("data-media-id");
			var _media_element = _this.$el.find("#slideshow_media_"+old_media_id);
			_media_element.attr("data-media-id", media_id);
			_media_element.find(".slideshow_media_val").val(media_id);
			_media_element.find("img").attr("src", media_url);
			_media_element.find(".slideshow_media_caption").attr("name", "slideshow_media_caption["+media_id+"]");
			_this.$el.find(".edit_slideshow_media").attr("data-media-id", media_id);
			_this.$el.find(".edit_slideshow_media img").attr("src", media_url);
			_media_element.attr("id", "slideshow_media_"+media_id);
			_this.$el.find("#post").submit();
		} else {
			_media_exist.addClass("media_exist");
			setTimeout(function(){ _media_exist.removeClass("media_exist"); }, 1200);
		}
	},	

	changeVideo: function(media_id, media_url, video_type, caption) {
		var _this = this;
		var _video_exist = jQuery("#slideshow_media_"+media_id);
		if(!_video_exist.length>0) {
			var old_media_id = _this.$el.find(".edit_slideshow_media").attr("data-media-id");
			var _media_element = _this.$el.find("#slideshow_media_"+old_media_id);
			_media_element.attr("data-media-id", media_id);
			_media_element.attr("data-video-type", video_type);
			_this.$el.find("#slideshow_media_"+old_media_id+" img").attr("src", media_url);
			_this.$el.find("#slideshow_media_"+old_media_id+" .slideshow_media_val").val(media_id);
			_this.$el.find("#slideshow_media_"+old_media_id+" .slideshow_media_caption").attr("name", "slideshow_media_caption["+media_id+"]");
			_this.$el.find("#slideshow_media_"+old_media_id+" .slideshow_media_caption").val(caption);
			_this.$el.find("#slideshow_media_"+old_media_id+" .slideshow_video_type").attr("name", "slideshow_video_type["+media_id+"]");
			_this.$el.find("#slideshow_media_"+old_media_id+" .slideshow_video_type").val(video_type);
			_this.$el.find(".edit_slideshow_media").attr("data-media-id", media_id);
			_this.$el.find(".edit_slideshow_media img").attr("src", media_url);
			_this.$el.find(".edit_slideshow_media #media_caption").val(caption);
			_this.$el.find("#slideshow_media_"+old_media_id).attr("id", "slideshow_media_"+media_id);
			_this.$el.find("#post").submit();
		} else {
			_video_exist.addClass("media_exist");
			setTimeout(function(){ _video_exist.removeClass("media_exist"); }, 1200);
		}	
	},

	multipleSlideSettings: function(event) {
		var _this = this;
		var target = jQuery( event.target );
		if(target.attr("id") == "multiple_slide") {
			_this.$el.find(".multiple_slides_settings").slideDown();
		} else {
			_this.$el.find(".multiple_slides_settings").slideUp();
		}
	}

});

jQuery(document).ready(function(){
	WRS.run();
});