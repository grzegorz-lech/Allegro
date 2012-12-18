$(function(){
    $('select.allegro').change( function() {
        selectCategory($(this));
    });
});

function selectCategory(handler)
{
    var category_id = $(handler).find('option:selected').val();
    if ( category_id == 0 )
    {
        return false;
    }

    $.ajax({
        url: url+'?category_id='+category_id,
        dataType: 'json',
        success: function(data, status){
            if ( data.length > 0 )
            {
                $(handler).next('select').remove();

                var select_id = $(handler).attr('id')+'-'+category_id;
                var html = $('<select id="'+select_id+'" class="allegro span2"></select>');

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