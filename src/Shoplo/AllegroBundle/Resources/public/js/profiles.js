$(function(){
    /* Usuwanie profilu */
    $('a.actionDelete').click(function(){
        if ( !confirm('Czy napewno chcesz usunąć ten profil?') )
        {
            return false;
        }
        return true;
    });
});