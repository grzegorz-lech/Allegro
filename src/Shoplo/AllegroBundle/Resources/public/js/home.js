$(function(){
    /* Filtrowanie aukcji */
    $('select[name=filter]').change(function(){
        window.location.href = $('select[name=filter] option:selected').attr('data-url');
        /*if ( $('select[name=filter] option:selected').val() == 1 )
        {
            $('table tr.active').show();
            $('table tr.finish').hide();
        }
        else
        {
            $('table tr.finish').show();
            $('table tr.active').hide();
        }*/
    });


    /* Usuwanie aukcji */
    $('a.actionDelete').click(function(){
        if ( !confirm('Czy napewno chcesz usunąć tą aukcję?') )
        {
            return false;
        }
        return true;
    });

    /* Zakonczenie aukcji */
    $('a.actionFinish').click(function(){
        if ( !confirm('Czy napewno chcesz zakończyć tą aukcję?') )
        {
            return false;
        }
        return true;
    });

    /* Wystaw ponownie */
    $('#sell-again-dialog').modal({
        show: false
    });

    $('.actionRefresh').click(function(){
        var itemId = $(this).attr('rel');
        $('#sell-again-dialog .modal-body .title').html('Aukcja: ' + $('#item-title-'+itemId).html());
        $('#sell-again-dialog #item_id').val(itemId);
        $('#sell-again-dialog').modal('show');
        return false;
    });
});