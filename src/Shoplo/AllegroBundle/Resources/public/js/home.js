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
});