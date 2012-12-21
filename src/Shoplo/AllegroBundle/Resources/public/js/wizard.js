$(function(){
    $('#dp').datepicker();


    calculatePrice();
    $('#stock-quantity').keyup(calculatePrice);
    $('input.promotion').click(calculatePrice);
    $('select.product-category').change(calculatePrice);

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
});

function calculatePrice()
{
    var quantity = $('.summaryBox .unstyled').data('quantity');

    // promocja przedmiotow
    $('input.promotion').each(function(){
        if ( $(this).is(':checked') ) {
            if ( $('.summaryBox .unstyled #promotion'+$(this).data('id')).length == 0 ) {
                $('<li class="row offset1" id="promotion'+$(this).data('id')+'"><div class="span7 alignRight">'+$(this).parents('.controls').find('.title').text()+'</div><div class="span5 alignRight"><span class="quantity">'+quantity+'</span> x <span class="price">'+$(this).data('price')+'</span>zł</div></li>').appendTo('.summaryBox .unstyled');
            }
        }
        else {
            $('.summaryBox .unstyled').find('#promotion'+$(this).data('id')).remove();
        }
    });

    // czyste koszty wystawienia przedmiotow
    $('.product-category').each(function(){
        $('li#product'+$(this).data('product-id')).find('.price').text( $(this).find('option:selected').data('price') );
    });

    var sum = 0;
    $('.summaryBox .unstyled li').each(function(){
        sum += (parseInt($(this).find('.quantity').text()) * parseFloat($(this).find('.price').text()));
    });



    $('.summaryBox .price-all').text( parseFloat(sum).toFixed(2) );
}