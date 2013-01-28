$(function(){
    $('select.allegro').change( function() {
        selectCategory($(this));
    });

    $('.map-allegro').submit(validateForm);
});

function selectCategory(handler, automatic)
{
    var category_id = $(handler).find('option:selected').val();
    var last_child = !!(typeof($(handler).find('option:selected').attr('data-childs')) != 'undefined' && $(handler).find('option:selected').attr('data-childs') == 0);
    if ( last_child )
    {
        $(handler).parents('.control-group').find('input.map').val(category_id);
    }


    if ( typeof(automatic) == 'undefined' )
    {
        $(handler).attr('class', $(handler).attr('class')+' changed');
    }


    var column = $(handler).parents('.control-group').find('select').index( $(handler) );
    var mainParent = $(handler).parents('.control-group').find('select:first');
    $('.control-group').each(function(){
        if ( $(this).find('select').length )
        {
            var parent = $(this).find('select:first');
            var select = $(this).find('select')[column];
            if ( parent.data('parent') == mainParent.data('id') && (parent.val() == 0 || !parent.hasClass('changed')) )
            {
                $(select).val($(handler).find('option:selected').val());
                selectCategory($(select), true);
            }
        }
    });



    if ( category_id == 0 || (typeof($(handler).find('option:selected').attr('data-childs')) != 'undefined' && $(handler).find('option:selected').attr('data-childs') == 0) )
    {
        $(handler).nextUntil().remove();
        return false;
    }

    $(handler).parents('td').attr('class', 'loading');

    setTimeout(function (){

        $.ajax({
            url: $('form.allegro-map').attr('data-url')+'/'+category_id,//Routing.generate('shoplo_allegro_get_category_path', { id: category_id }),
            dataType: 'json',
            success: function(data, status){
                $(handler).nextUntil().remove();
                if ( data.length > 0 )
                {
                    var parent_id = $(handler).data('parent') == 0 ? $(handler).data('id') : $(handler).data('parent');
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
                $(handler).parents('td').attr('class', '');
            }
        });

    }, 0);
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