$(function(){
    $('select.allegro').change( function() {
        selectCategory($(this));
    });

    $('.map-allegro').submit(validateForm);
});

function selectCategory(handler)
{
    var category_id = $(handler).find('option:selected').val();
    var last_child = !!(typeof($(handler).find('option:selected').attr('data-childs')) != 'undefined' && $(handler).find('option:selected').attr('data-childs') == 0);
    if ( last_child )
    {
        $(handler).parents('.control-group').find('input.map').val(category_id);
    }

    if ( category_id == 0 || (typeof($(handler).find('option:selected').attr('data-childs')) != 'undefined' && $(handler).find('option:selected').attr('data-childs') == 0) )
    {
        $(handler).nextUntil().remove();
        return false;
    }

    $.ajax({
        url: url+'?category_id='+category_id,
        dataType: 'json',
        success: function(data, status){
            $(handler).nextUntil().remove();
            if ( data.length > 0 )
            {
                var select_id = $(handler).attr('id')+'-'+category_id;
                var html = $('<select id="'+select_id+'" class="allegro span2" name="'+$(handler).attr('name')+'"></select>');

                html.append('<option value="0">Wybierz kategorię</option>');
                for ( d in data )
                {
                    html.append('<option value="'+data[d]['id']+'" data-childs="'+data[d]['childs_count']+'">'+data[d]['name']+'</option>');
                }

                if ( $('select#'+select_id).length == 0 )
                {
                    html.insertAfter($(handler));
                }

                $('select#'+select_id).change( function() {
                    selectCategory($(this));
                });
            }
        }
    });
}

function validateForm()
{
    var valid = true;
    $('.map-allegro input.map').each(function(){
        if ( $(this).val() == 0 )
        {
            valid = false;
            $(this).parents('.control-group').addClass('error');
        }
        else
        {
            $(this).parents('.control-group').attr('class', 'control-group clear');
        }
    });

    if ( valid )
    {
        $('.alert.alert-error').hide();
        return true;
    }

    $('.alert.alert-error').show();

    return false;
}