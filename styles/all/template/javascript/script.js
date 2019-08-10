$(document).ready(function() {
    if (($(this).find(".adychangecover.message_success")) || $(this).find(".adychangecover.message_error")) {
        window.setTimeout(function() {
            window.location.href = '/app.php/changecover/requestcover';
        }, 5000);
    }
})