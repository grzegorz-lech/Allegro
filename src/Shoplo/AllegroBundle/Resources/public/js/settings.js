$(function () {
    $('.ez-checkbox:not(.ez-disabled)').click(function () {
        var id = $(this).find('input').attr('id');

        if (id == 'promotion') {
            $('.show-promotion').slideToggle();
        }

        if ($(this).hasClass('ez-checked')) {
            $(this).attr('class', 'ez-checkbox');
            $(this).find('input').attr('checked', false);
        }
        else {
            $(this).attr('class', 'ez-checkbox ez-checked');
            $(this).find('input').attr('checked', 'checked');
        }
    });
});
