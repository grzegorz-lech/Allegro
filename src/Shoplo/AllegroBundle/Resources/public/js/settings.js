$(function(){
    $('.ez-checkbox').click( function() {
        if ( $(this).hasClass('ez-checked') ) {
            $(this).attr('class', 'ez-checkbox');
            $(this).find('input').attr('checked', false);
        }
        else {
            $(this).attr('class', 'ez-checkbox ez-checked');
            $(this).find('input').attr('checked', 'checked');
        }

        if ( $(this).find('input[name=promotion]').length > 0 ) {
            if ( $(this).find('input[name=promotion]').is(':checked') ) {
                $('.show-promotion').slideDown();
            }
            else {
                $('.show-promotion').slideUp();
            }
        }


        return false
    });
});