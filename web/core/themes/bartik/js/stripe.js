
(function ($, Drupal) {
    Drupal.behaviors.stripe = {
      attach: function attach(context, settings) {
          // Set your publishable key
Stripe.setPublishableKey('pk_test_mR0niTovK0HMAqfLNiW2ttli');



// Callback to handle the response from stripe
function stripeResponseHandler(status, response) {
    if (response.error) {
        // Enable the submit button
        $('#edit-submit').removeAttr("disabled");
        // Display the errors on the form
        $(".payment-status", context).html('<p>'+response.error.message+'</p>');
    } else {
        var form$ = $("#payment-form", context);
        // Get token id
        var token = response.id;
        console.log(token);
        // Insert the token into the form
        $(".stripeToken", context).val(token);
        //form$.append("<input type='hidden' name='stripeToken' value='" + token + "' />");
        // Submit form to the server
        form$.get(0).submit();
    }
}

//$(document).ready(function() {
    // On form submit
    $("#payment-form" , context).submit(function() {
        // Disable the submit button to prevent repeated clicks
        $('#edit-submit').attr("disabled", "disabled");
		
        // Create single-use token to charge the user
        Stripe.createToken({
            number: $('#edit-card-number').val(),
            exp_month: $('#edit-card-exp-month').val(),
            exp_year: $('#edit-card-exp-year').val(),
            cvc: $('#edit-card-cvc').val()
        }, stripeResponseHandler);
		
        // Submit from callback
        return false;
    });
//});
}
};
})(jQuery, Drupal); 