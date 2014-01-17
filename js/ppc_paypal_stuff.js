//Given a set of checkboxes through the container (selector), returns the number of checked ones
function get_selected_checkbox_count(selector) {
	var selected_checkbox_count = 0;
	jQuery(selector + " input:checkbox").each(function() {
		if (jQuery(this).is(":checked"))
			selected_checkbox_count++;
	});
	
	return selected_checkbox_count;
}

//Given a set of checkboxes through the container (selector) and the maximum number of checkboxes that can be checked (selection_limit), forbids to select more boxes than the maximum allowed and makes the submit button enabled only if at least one is checked 
function handle_checkbox_selection(selector, selection_limit) {
	var selected_checkbox_count = get_selected_checkbox_count(selector);
	
	if (selected_checkbox_count == selection_limit) {
		jQuery(selector + " input:checkbox").each(function() {
			if (jQuery(this).is(":checked"))
				jQuery(this).removeAttr("disabled");
			else
				$(this).attr("disabled", "true");
		});
	} else {
		jQuery(selector + " input:checkbox").each(function() {
			jQuery(this).removeAttr("disabled");
		});
	}
}

//Paypal payment function
function ppc_paypal_payment(type) {
	jQuery("#ppc_paypal_payment_"+type).unbind('click').click(function(e) {
		e.preventDefault();
		jQuery("#ppc_paypal_error").css('display', 'none');
        
        if(ppc_paypal_stuff_vars.is_paypal_available != true) {
            jQuery("#ppc_paypal_error").html(ppc_paypal_stuff_vars.is_paypal_available);
            jQuery("#ppc_paypal_error").css("display", "block");
            return false;
        }
		
		//No checkboxes selected
		if(get_selected_checkbox_count('#ppc_stats') == 0) {
			jQuery("#ppc_paypal_error").html(ppc_paypal_stuff_vars.localized_no_selection);
			jQuery("#ppc_paypal_error").css("display", "block");
			return false;
		}
		
		var data = {
			action:         'ppc_'+type+'_paid_update',
			_ajax_nonce:     ppc_paypal_stuff_vars.nonce_ppc_paid_update,
            current_tstart: jQuery('#post_pay_counter_time_start').attr('accesskey'),
            current_tend:   jQuery('#post_pay_counter_time_end').attr('accesskey'), 
			payment_data:   jQuery('#ppc_stats').serialize()
		};
		var selected_items_confirmation_list = '';
		
		//Compile a list of selected items for confirmation - last chance to cancel
		jQuery('.ppc_paid_status_update:checked').each(function() {
			selected_items_confirmation_list = selected_items_confirmation_list + 
			jQuery(this).parent().parent().children('.'+type+'_id').text() + 
			' => ' +
			jQuery(this).parent().parent().children('.due_pay').children('abbr').text() + 
			'\n';
		});
		
		var agree = confirm(ppc_paypal_stuff_vars.localized_selected_items_confirmation + '\n\n' + selected_items_confirmation_list );
		if (!agree)
			return false;
		
		//Disable prepare button and checkboxes - no more payment edits after payment preparation is confirmed
		jQuery('#ppc_paypal_payment_'+type).attr("disabled", "true");
		jQuery('#ppc_paypal_payment_loading').css("display", "block");
		jQuery(".ppc_paid_status_update").each(function() {
			jQuery(this).attr("disabled", "true");
		});
		
		//AJAX request to prepare payment and get PayKey
		jQuery.post(ajaxurl, data, function(response) {
			jQuery('#ppc_paypal_payment_loading').css("display", "none");
			
			if(response != 'ok') {
				jQuery("#ppc_paypal_error").css("display", "block");
				jQuery("#ppc_paypal_error").html(response);
				jQuery('#ppc_paypal_payment_'+type).removeAttr("disabled");
				jQuery(".ppc_paid_status_update").each(function() {
					jQuery(this).removeAttr("disabled");
				});
			} else {
				jQuery("#ppc_paypal_success").css("display", "block");
			}
		});
	});
}

jQuery(document).ready(function($) {
    //Handles checkbox-ruler in pay selection
    $('#ppc_one_to_rule_them_all').unbind('change').change(function() {
        if(this.checked == false) {
            $('.ppc_paid_status_update').each(function() {
                $(this).attr('checked', false);
            });
        } else if(this.checked == true) {
            $('.ppc_paid_status_update').each(function() {
				if($(this).attr('disabled') != 'disabled') {
					$(this).attr('checked', true);
				}
            });
        }
    });
	
	//POST PAYMENT PREPARATION
    if($("#ppc_paypal_payment_post").length != 0) {
        ppc_paypal_payment('post');
    }
    
    //AUTHOR PAYMENT PREPARATION
    if($("#ppc_paypal_payment_author").length != 0) {
        ppc_paypal_payment('author');
	}
});