$(function(){
    $('#dp').datepicker();

    /* AUCTION PRICE */
    calculatePrice();
    $('#stock-quantity').keyup(calculatePrice);
    $('input.promotion').click(calculatePrice);
    $('select.product-category').change(calculatePrice);
    $('#form_all_stock').click(function(){
        if ( $(this).is(':checked') ) {
            $('#form_quantity').attr('disabled', true);
            $('#form_quantity').val(0);
        }
        else {
            $('#form_quantity').attr('disabled', false);
            $('#form_quantity').val('');
            $('#form_quantity').focus();
        }
        calculatePrice();
    });

    /* AUCTION PROFILE */
    changeProfile();
    $('#form_profiles').change( changeProfile );

    /* FLOATING SIDEBAR */
    if ($('.sidebar').length) {
        var $floating = $('.sidebar'),
            origOffset = $floating.offset().top-10;

        $(window).scroll(function() {
            var windowOffset = $(window).scrollTop();

            if (windowOffset > origOffset) {
                var maxOffset = ($('.container-fluid .content').offset().top + ($('.container-fluid .content')[0].offsetHeight - $floating[0].offsetHeight)) - 120;

                $floating.addClass('sticked');

                if (windowOffset > maxOffset) {
                    $floating.css({
                        position: 'absolute'
                        //top: maxOffset - $floating[0].offsetHeight
                    });
                }
                else {
                    $floating.css({
                        position: ''
                        //top: ''
                    }).addClass('sticked');
                }
            }
            else if (windowOffset <= origOffset) {
                $floating.removeClass('sticked');
            }
        });
    }

    /*** Extra delivery ***/
    $('#form_extra_delivery input[type=text]').focus(function(){
        $(this).parents('.controls').find('input[type=checkbox]').attr('checked', true);
    });
    $('#form_extra_delivery input[type=text]').blur(function(){
        if ( $(this).val() != '' )
        {
            $(this).parents('.controls').find('input[type=checkbox]').attr('checked', true);
        }
        else
        {
            $(this).parents('.controls').find('input[type=checkbox]').attr('checked', false);
        }
    });

    /*** New price ***/
    $('#form_price').change(function(){
        if ( $('#form_price option:selected').val() > 0 )
        {
            $('#form_extra_price').show();
            $('#form_extra_price').focus();
        }
        else
        {
            $('#form_extra_price').hide();
        }
    });
});

function calculatePrice()
{
    var itemQuantity = $('#stock-quantity').val() ? $('#stock-quantity').val() : 1;
    var productsQuantity = $('.summaryBox .unstyled').data('quantity');

    // promocja przedmiotow
    $('input.promotion').each(function(){
        if ( $(this).is(':checked') ) {
            if ( $('.summaryBox .unstyled #promotion'+$(this).data('id')).length == 0 ) {
                $('<li class="row offset1" id="promotion'+$(this).data('id')+'"><div class="span7 alignRight">'+$(this).parents('.controls').find('.title').text()+'</div><div class="span5 alignRight"><span class="quantity">'+productsQuantity+'</span> x <span class="price">'+$(this).data('price')+'</span>zł</div></li>').appendTo('.summaryBox .unstyled');
            }
        }
        else {
            $('.summaryBox .unstyled').find('#promotion'+$(this).data('id')).remove();
        }
    });

    // czyste koszty wystawienia przedmiotow
    $('.product-category').each(function(){
        $('li#product'+$(this).data('product-id')).find('.quantity').text(itemQuantity);
        $('li#product'+$(this).data('product-id')).find('.price').text( $(this).find('option:selected').data('price') );
    });

    $('ul li.variant').each(function(){
        if( $('#all-stock').is(':checked') ) {
            var quantity = $(this).data('quantity');
        }
        else {
            var quantity = $(this).data('quantity') < itemQuantity ? $(this).data('quantity') : itemQuantity;
        }
        $(this).find('.quantity').text(quantity);
    });

    var sum = 0;
    $('.summaryBox .unstyled li').each(function(){
        sum += (parseInt($(this).find('.quantity').text()) * parseFloat($(this).find('.price').text()));
    });



    $('.summaryBox .price-all').text( parseFloat(sum).toFixed(2) );
}

function changeProfile()
{
    if ( $('select#form_profiles option:selected').val() <= 0 ) {
        $('.profile').show();
    }
    else {
        $('.profile').hide();
    }
}