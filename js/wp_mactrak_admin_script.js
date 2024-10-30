//alert("admin");

jQuery( document ).ready( function( $ ) {
	var mactrak_image_frame;
	
	$('#mactrak_upload_image_button').on('click', function( event ){
		event.preventDefault();
		
		if ( mactrak_image_frame ) {
			mactrak_image_frame.open();
			return;
		}
		
		mactrak_image_frame = wp.media.frames.mactrak_image_frame = wp.media({
			title: 'Select Image for Current Location Marker',
			button: {
				text: 'Use this image',
			},
			multiple: false	// Set to true to allow multiple files to be selected
		});
		
		mactrak_image_frame.on( 'select', function() {
			var selectedImage = mactrak_image_frame.state().get('selection').first().toJSON();

			$( '#mactrak_image_preview' ).attr( 'src', selectedImage.url ).css( 'width', 'auto' );
			$( '#mactrak_custom_image_id' ).val( selectedImage.id );
			
			var newImg = new Image();
			newImg.onload = function() {
				$('#mactrak_current_x').html(this.width);
				$('#mactrak_current_y').html(this.height);
				
				var scaleVal = $('#mactrak_current_scale').val();
				
				$('#mactrak_current_scaled_x').html(parseInt(scaleVal/100*this.width));
				$('#mactrak_current_scaled_y').html(parseInt(scaleVal/100*this.height));
			}
			newImg.src = selectedImage.url;
			//console.log(selectedImage);
		});

		mactrak_image_frame.open();
	});
	
	$('#mactrak_add_customline_start').on('click', function(event) { $('#mactrak_add_customline').slideToggle(); });
	$('#mactrak_add_marker_start').on('click', function(event) { $('#mactrak_add_marker').slideToggle(); });
	
	$('#mactrak_import_spot').on('click', function(event) { $('#mactrak_import_spot_content').slideToggle(); });
	$('#mactrak_export_route').on('click', function(event) { $('#mactrak_export_route_data').slideToggle(); });
	
	$('#mactrak_view_live_spot').on('click', function(event) { 
		$('#mactrak_view_live_spot_content').show(); 
		
		var data = {
			'action': 'mactrak_rtnspotdata',
			'security': $('#mactrak_nonce_spot').val()
		}
		
		$.post(ajaxurl, data, function (response){
			$(".mactrak_spot_data").text(response);
		});
		
	});
	
	/*$('#mactrak_export_route_data').on('submit', function(){
		var data = {
			'action': 'mactrak_rtncsvexport',
			'security': $('#mactrak_nonce_export').val()
		}
		
		$.post(ajaxurl, data, function (response){
			document.write(response);//$(".mactrak_spot_data").text(response);
		});	
		return false;
	});*/
	
	$('#mactrak_current_scale').on('keyup', function( event ){
		var scaleVal = parseInt($(this).val());

		// Read current x&y
		var currentX = parseInt($('#mactrak_current_x').html());
		var currentY = parseInt($('#mactrak_current_y').html());
		
		var scaledX = Math.floor(scaleVal/100*currentX);
		var scaledY = Math.floor(scaleVal/100*currentY);

		$('#mactrak_current_scaled_x').html(scaledX);
		$('#mactrak_current_scaled_y').html(scaledY);
	});
	
	$('#mactrak_newline_color').on('keyup', function(event){ change_color_disp_box('#mactrak_newline_colordisp', $('#mactrak_newline_color').val()) });
	$('#mactrak_recent_color').on('keyup', function(event){ change_color_disp_box('#mactrak_recent_colordisp', $('#mactrak_recent_color').val()) });
	$('#mactrak_default_color').on('keyup', function(event){ change_color_disp_box('#mactrak_default_colordisp', $('#mactrak_default_color').val()) });
	$('#mactrak_destination_color').on('keyup', function(event){ change_color_disp_box('#mactrak_destination_colordisp', $('#mactrak_destination_color').val()) });


});

function change_color_disp_box(outputId, input){
	//console.log(input);
	var newBgColor = input.replace(/[^A-Fa-f0-9]/gi, "");
	//console.log(newBgColor);
	var bgColorExpl = [];
	
	newBgColor = newBgColor.substr(0, 6);
	
	if (newBgColor.length < 6) {
		switch (newBgColor.length) {
			case 0:
				bgColorExpl['r'] = String('0');
				bgColorExpl['g'] = String('0');
				bgColorExpl['b'] = String('0');
				break;
			case 1:
				bgColorExpl['r'] = String(newBgColor.substr(0, 1));
				bgColorExpl['g'] = String('0');
				bgColorExpl['b'] = String('0');
				break;
			case 2:
				bgColorExpl['r'] = String(newBgColor.substr(0, 1));
				bgColorExpl['g'] = String(newBgColor.substr(1, 1));
				bgColorExpl['b'] = String('0');
				break;
			case 3:
				newBgColor = newBgColor.substr(0, 3);
				break;
			case 4:
				bgColorExpl['r'] = String(newBgColor.substr(0, 2));
				bgColorExpl['g'] = String(newBgColor.substr(2, 1)) + String(newBgColor.substr(2, 1));
				bgColorExpl['b'] = String(newBgColor.substr(3, 1)) + String(newBgColor.substr(3, 1));
				break;
			case 5:
				bgColorExpl['r'] = String(newBgColor.substr(0, 2));
				bgColorExpl['g'] = String(newBgColor.substr(2, 2));
				bgColorExpl['b'] = String(newBgColor.substr(4, 1)) + String(newBgColor.substr(4, 1));
				break;
		}
		//console.log(newBgColor.length + ': '+ bgColorExpl['r'] + ', ' + bgColorExpl['g'] + ', ' +  bgColorExpl['b']);
		if (newBgColor.length != 3) newBgColor = bgColorExpl['r'] + bgColorExpl['g'] + bgColorExpl['b'];
		//console.log(newBgColor);
	}
	newBgColor = newBgColor.toUpperCase();
	
	jQuery(outputId).css('background-color', "#" + newBgColor);
}

