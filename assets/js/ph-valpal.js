var geocoder;
var original_submit_value = '';

jQuery(document).ready(function()
{
    // Form submitted
    jQuery('#frmValPal').submit(function()
    {
    	if ( ph_valpal.address_lookup != '1' )
    	{
    		if ( jQuery('#frmValPal input[name=\'line1\']').val() == '' )
	        {
	            alert('Please enter a valid property name/number');
	            return false;
	        }

	        if ( jQuery('#frmValPal input[name=\'line2\']').val() == '' )
	        {
	            alert('Please enter a valid street');
	            return false;
	        }
    	}

        if ( jQuery('#frmValPal input[name=\'postcode\']').val() == '' )
        {
            alert('Please enter a valid postcode');
            return false;
        }

        if ( jQuery('#frmValPal input[name=\'name\']').val() == '' )
        {
            alert('Please enter your name');
            return false;
        }

        if ( jQuery('#frmValPal input[name=\'email\']').val() == '' )
        {
            alert('Please enter a valid email address');
            return false;
        }

        if ( jQuery('#frmValPal input[name=\'telephone\']').val() == '' )
        {
            alert('Please enter a valid telephone number');
            return false;
        }

        if ( jQuery('#frmValPal input[name=\'disclaimer\']').length > 0 && jQuery('#frmValPal input[name=\'disclaimer\']:checked').length == 0 )
        {
            alert('Please agree to our privacy terms by checking the tickbox if you wish to proceed');
            return false;
        }

        original_submit_value = jQuery('#frmValPal input[type=\'submit\']').val();

        jQuery('#frmValPal input[type=\'submit\']').val('Retrieving Valuation...');
        jQuery('#frmValPal input[type=\'submit\']').attr('disabled', 'disabled');

        var data = { 
            'action': 'do_val_request',
            'type': jQuery('#frmValPal select[name=\'type\']').val(),
            'bedrooms': jQuery('#frmValPal select[name=\'bedrooms\']').val(),
            'propertytype': jQuery('#frmValPal select[name=\'propertytype\']').val(),
            'name': jQuery('#frmValPal input[name=\'name\']').val(),
            'email': jQuery('#frmValPal input[name=\'email\']').val(),
            'telephone': jQuery('#frmValPal input[name=\'telephone\']').val(),
            'comments': jQuery('#frmValPal textarea[name=\'comments\']').val(),
            'postcode': jQuery('#frmValPal input[name=\'postcode\']').val()
        };

        if ( ph_valpal.address_lookup == '1' )
        {
            data.property = jQuery('#frmValPal select[name=\'property\']').val();
        }
        else
        {
            data.number = jQuery('#frmValPal input[name=\'number\']').val();
            data.street = jQuery('#frmValPal input[name=\'street\']').val();
            data.postcode = jQuery('#frmValPal input[name=\'postcode\']').val();
        }

        jQuery.ajax({
          type: "POST",
          url: ph_valpal.ajax_url,
          data: data,
          success: function(data)
          {
                jQuery('#frmValPal input[type=\'submit\']').val(original_submit_value);
                jQuery('#frmValPal input[type=\'submit\']').attr('disabled', false);

                if (data.error && data.error != '')
                {
                    alert(data.error);
                    return false;
                }

                jQuery('html,body').animate({
                    scrollTop: jQuery('#frmValPal').offset().top - jQuery('header#header').outerHeight()
                });

                var amount = data.minvaluation.replace("£", "");
                if ( jQuery('#frmValPal select[name=\'type\']').val() == 'sales' && ph_valpal.sales_min_amount_percentage_modifier != 0 )
                {
                    amount = parseInt(amount.replace(/[^0-9]/g, ''));
                    amount = Math.round( amount + ( amount * ( ph_valpal.sales_min_amount_percentage_modifier / 100 ) ) );
                }
                
                if ( jQuery('#frmValPal select[name=\'type\']').val() == 'lettings' && ph_valpal.lettings_min_amount_percentage_modifier != 0 )
                {
                    amount = parseInt(amount.replace(/[^0-9]/g, ''));
                    amount = Math.round( amount + ( amount * ( ph_valpal.lettings_min_amount_percentage_modifier / 100 ) ) );
                }
                amount = '&pound;' + ph_valpal_add_commas(amount);
                jQuery('#valuation_results .min-amount span').html( amount );

                var amount = data.valuation.replace("£", "");
                if ( jQuery('#frmValPal select[name=\'type\']').val() == 'sales' && ph_valpal.sales_actual_amount_percentage_modifier != 0 )
                {
                    amount = parseInt(amount.replace(/[^0-9]/g, ''));
                    amount = Math.round( amount + ( amount * ( ph_valpal.sales_actual_amount_percentage_modifier / 100 ) ) );
                }
                if ( jQuery('#frmValPal select[name=\'type\']').val() == 'lettings' && ph_valpal.lettings_actual_amount_percentage_modifier != 0 )
                {
                    amount = parseInt(amount.replace(/[^0-9]/g, ''));
                    amount = Math.round( amount + ( amount * ( ph_valpal.lettings_actual_amount_percentage_modifier / 100 ) ) );
                }
                amount = '&pound;' + ph_valpal_add_commas(amount);
                jQuery('#valuation_results .actual-amount span').html( amount );

                var amount = data.maxvaluation.replace("£", "");
                if ( jQuery('#frmValPal select[name=\'type\']').val() == 'sales' && ph_valpal.sales_max_amount_percentage_modifier != 0 )
                {
                    amount = parseInt(amount.replace(/[^0-9]/g, ''));
                    amount = Math.round( amount + ( amount * ( ph_valpal.sales_max_amount_percentage_modifier / 100 ) ) );
                    
                }
                if ( jQuery('#frmValPal select[name=\'type\']').val() == 'lettings' && ph_valpal.lettings_max_amount_percentage_modifier != 0 )
                {
                    amount = parseInt(amount.replace(/[^0-9]/g, ''));
                    amount = Math.round( amount + ( amount * ( ph_valpal.lettings_max_amount_percentage_modifier / 100 ) ) );
                }
                amount = '&pound;' + ph_valpal_add_commas(amount);
                jQuery('#valuation_results .max-amount span').html( amount );

                jQuery('#valuation_results .area-info').html( data.areainformation );

                jQuery('#frmValPal').fadeOut('fast', function()
                {
                    jQuery('#valuation_results').fadeIn('fast', function()
                    {
                        if ( ph_valpal.show_map_in_results == '1' || ph_valpal.show_street_view_in_results == '1' )
                        {
                            // init street view
                            geocoder = new google.maps.Geocoder();

                            var address = data.number + ', ' + data.postcode;
                            geocoder.geocode({'address': address}, function(results, status) 
                            {
                                if (status === google.maps.GeocoderStatus.OK) 
                                {
                                    var position = results[0].geometry.location;

                                    if ( ph_valpal.show_map_in_results == '1' )
                                    {
                                        var map = new google.maps.Map(document.getElementById("map_canvas"), {
                                            zoom: 13,
                                            center: position
                                        });
                                    }
                                    if ( ph_valpal.show_street_view_in_results == '1' )
                                    {
                                        var panorama = new google.maps.StreetViewPanorama(
                                            document.getElementById('street_map_canvas'), {
                                                position: position
                                            }
                                        );
                                    }
                                }
                                else
                                {
                                    alert('Geocode was not successful for the following reason: ' + status);
                                }
                            });
                        }
                    });
                });
          },
          dataType: 'json'
        });

        return false;
    });

    jQuery('#cancel_find_address').click(function()
    {
        jQuery('#buildname').val('');
        jQuery('#subBname').val('');
        jQuery('#line1').val('');
        jQuery('#line2').val('');
        jQuery('#depstreet').val('');

        jQuery('#address_results_control').fadeOut('fast', function()
        {
            jQuery('#postcode_control').fadeIn();
        });
        return false;
    });

    jQuery('#postcode').keyup(function(e){
        if(e.keyCode == 13)
        {
            e.preventDefault();
            doPostcodeLookup();
        }
    });

    jQuery('#find_address').click(function(e)
    {
        e.preventDefault();
        doPostcodeLookup();
    });
});

function doPostcodeLookup()
{
    jQuery('#find_address').attr('disabled', 'disabled');
    jQuery('#find_address').html('Finding Address...');

    jQuery.ajax({
        type: "POST",
        url: ph_valpal.ajax_url,
        data: {
            'action': 'do_postcode_lookup',
            'postcode': jQuery('#frmValPal input[name=\'postcode\']').val()
        },
        success: function(data)
        {
            jQuery('#address_results').empty();
            jQuery('#address_results').append(jQuery('<option>', {
                value: '',
                text: 'Select address...'
            }));

            if ( typeof data[2] != 'undefined' && typeof data[2].results != 'undefined' && data[2].results.length > 0 )
            {
                for ( var i in data[2].results )
                {
                    jQuery('#address_results').append(jQuery('<option>', {
                        value: data[2].results[i].id,
                        text: data[2].results[i].address,
                    }));
                }

                jQuery('#postcode_control').fadeOut('fast', function()
                {
                    jQuery('#address_results_control').fadeIn();

                    jQuery('#find_address').attr('disabled', false);
                    jQuery('#find_address').html('Find Address');
                });
            }
            else
            {
                jQuery('#find_address').attr('disabled', false);
                jQuery('#find_address').html('Find Address');
            }
            /*jQuery('#address_results').html(data.replace('<BR>', '').replace('<BR>', ''));

            jQuery('#postcode_control').fadeOut('fast', function()
            {
                jQuery('#address_results_control').fadeIn();

                jQuery('#find_address').attr('disabled', false);
                jQuery('#find_address').html('Find Address');
            });*/
        },
        dataType: 'json'
    });
}

function ph_valpal_add_commas(nStr)
{
    nStr += '';
    x = nStr.split('.');
    x1 = x[0];
    x2 = x.length > 1 ? '.' + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + ',' + '$2');
    }
    return x1 + x2;
}