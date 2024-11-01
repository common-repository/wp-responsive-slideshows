
loadWRSCSS = function(href) {
     var cssLink = jQuery("<link rel='stylesheet' type='text/css' href='"+href+"'>");
     jQuery("head").append(cssLink); 
};

loadWRSJS = function(src) {
     var jsLink = jQuery("<script type='text/javascript' src='"+src+"'>");
     jQuery("body").append(jsLink); 
}; 

function show_slideshow_shortcode_button_html() {
	var slideshow_shortcodes_html = jQuery('<div class="slideshow_shortcodes_popup">'+
			'<a class="media-modal-close close_wrs_popups" href="#" title="Close"><span class="media-modal-icon"></span></a>'+
			'<div id="slideshow-shortcodes-html">'+
			'<h2>List of Slideshow Shortcodes</h2>'+
			'<p>This is a list of all available slideshow shortcodes. You may insert a shortcode into a post or page here.</p>'+
			'<p>Click the “Insert Shortcode” button for the slideshow shortcode to automatically insert the<br>corresponding Shortcode (<input type="text" readonly="readonly" value="[WRS slideshow_id=&lt;ID&gt;/]" class="readonly_slideshow_shortcode" ltr">) into the editor.</p>'+
			'<div class="wrs_shortcodes_table">'+
			'<table id="slideshow-shortcodes-table" class="widefat fixed form-table">'+
			'<thead><tr><th class="shortcode_head">Shortcode</th><th class="shortcode_action_head">Action</th></tr></thead>'+	
			'<tbody id="slideshow_shortcodes_tbody">'+
			//AJAX Content
			'</tbody>'+
			'</table>'+
			'</div>'+
		'</div>'+
		'<div>');
		
	var table = slideshow_shortcodes_html.find('table');
	slideshow_shortcodes_html.appendTo('body').hide();

	jQuery.ajax({
			type: 'POST',				
	        url: wrs_script.ajaxurl, 
	        data: {
	           	action: "fetch_slideshow_shortcodes"
	        },
	        dataType: 'json',
	        success: function(res) {
	            table.find("#slideshow_shortcodes_tbody").html(res);
	        }
	});
}

jQuery(document).ready(function(){
	show_slideshow_shortcode_button_html();
	
	var load_flag = false;

	tinymce.create('tinymce.plugins.SlideshowShortcode', {
		init : function(ed, url) {
			ed.addButton('slideshow_shortcode_button', {
				title : 'Slideshow Shortcode',
				image : wrs_script.WRS_PLUGIN_URL + '/assets/slideshow_tinymce_button.png',
				onclick : function() {
					jQuery("#wrs_popup_overlay").show();
					jQuery(".slideshow_shortcodes_popup").show();
				}
			});
		},
		createControl : function(n, cm) {
				return null;
		}
	});
	tinymce.PluginManager.add('slideshow_shortcode_button', tinymce.plugins.SlideshowShortcode);

	jQuery("body").on("click", "#wrs_popup_overlay, .close_wrs_popups", function(){
		jQuery(".slideshow_shortcodes_popup").fadeOut(function(){
			jQuery("#wrs_popup_overlay").hide();
		});
		jQuery("#edit_slideshow_post_popup").fadeOut(function(){
			jQuery("#wrs_popup_overlay").hide();
		});	
	});

	// handles the click event of the insert shortcode button
	jQuery("body").on("click", ".insert_custom_shortcode", function(){
		var shortcode = "";
		var $_this = jQuery(this);
		shortcode = $_this.parent().prev().html();
		var win = window.dialogArguments || opener || parent || top;
		//tinyMCE.activeEditor.execCommand('mceInsertContent', 0, shortcode);
		win.send_to_editor( shortcode )
		jQuery(".slideshow_shortcodes_popup").hide();
		jQuery("#wrs_popup_overlay").hide();;
	});

	// handles the click event of the edit shortcode button
	jQuery("body").on("click", ".edit_slideshow_shortcode", function(){
		var shortcode = "";
		var $_this = jQuery(this);
		var slideshow_edit_url = $_this.attr("edit_url");
		jQuery(".slideshow_shortcodes_popup").hide();
		jQuery("#wrs_popup_overlay").show();
		jQuery(".edit_slideshow_post_popup_loader").css("display", "inline-block");
		jQuery("#edit_slideshow_post_popup .edit_slideshow_inner").load(slideshow_edit_url+" #wpbody",function(){
			if( load_flag == false ) {
				loadWRSCSS(wrs_script.WRS_PLUGIN_URL + "/admin_css_js/wrs-admin-style.css?ver=1.0.0");
				loadWRSJS(wrs_script.WRS_PLUGIN_URL + "/admin_css_js/wrs-admin-script.js?ver=1.0.0");
				load_flag = true;
			} else {
				WRS.run();
			}
			jQuery("#edit_slideshow_post_popup").show();
			jQuery(".edit_slideshow_post_popup_loader").hide();
		});
	});

	jQuery("body").on("click", ".done_edit_slideshow_post", function(){
		jQuery("#edit_slideshow_post_popup").fadeOut(function(){
			jQuery(".slideshow_shortcodes_popup").show();
		});
	});

});

