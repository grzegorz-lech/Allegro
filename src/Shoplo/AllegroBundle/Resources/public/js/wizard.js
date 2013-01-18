$(function(){
    /*** AUCTION PRICE ***/
    var wizard = new Wizard();

    wizard.changePrice(false);
    wizard.changeQuantity(false);
    wizard.changeImage(false);
    wizard.changePromotions(false);
    wizard.changeCategory(false);
    wizard.recalculate();

    $('#form_price').change( function() { wizard.changePrice(); });
    $('#form_extra_price').blur( function() { wizard.changePrice(); });
    $('#form_quantity').blur( function() { wizard.changeQuantity(); });
    $('#form_all_stock').change( function() { wizard.changeQuantity(); });
    $('#form_images input[type=radio]').change( function() { wizard.changeImage(); });
    $('#form_promotions input[type=checkbox]').change( function() { wizard.changePromotions(); });
    $('.product-category').change( function() { wizard.changeCategory(); });
    /*** END AUCTION PRICE ***/

    $('#dp').datepicker();


    $('#form_all_stock').click(function(){
        if ( $(this).is(':checked') ) {
            $('#form_quantity').attr('disabled', true);
            $('#form_quantity').val(0);
        }
        else {
            $('#form_quantity').attr('disabled', false);
            $('#form_quantity').val(1);
            $('#form_quantity').focus();
        }
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

/*function calculatePrice()
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
}*/

function changeProfile()
{
    if ( $('select#form_profiles option:selected').val() <= 0 ) {
        $('.profile').show();
    }
    else {
        $('.profile').hide();
    }
}

function Wizard()
{
    this._image_price = 0.10;

    this._promotion_thumbnail_price = 0.00;
    this._promotion_bold_price = 2.00;
    this._promotion_higllight_price = 3.00;

    this._promotion_distinction_common_price = 19.00;
    this._promotion_distinction_rare_price = 12.00;
    this._promotion_distinction_rare_categories = [1, 6, 7, 9, 20585, 26013, 73973, 76593, 122640]; // Muzyka, Kolekcje, Książki i Komiksy, Gry, Filmy, Antyki i Sztuka, Delikatesy, Rękodzieło, Instrumenty

    this._promotion_category_page_section_1_price = 12.00;
    this._promotion_category_page_section_1_categories = [1, 6, 7, 9, 20585, 122640]; // Filmy, Gry, Kolekcje, Książki i Komiksy, Muzyka, Instrumenty

    this._promotion_category_page_section_2_price = 15.00;
    this._promotion_category_page_section_2_categories = [4, 3919, 8845, 19732, 26013, 73973, 76593]; // Antyki i Sztuka, Biżuteria i Zegarki, Delikatesy, Fotografia, Rękodzieło, Sport i Turystyka, Telefony i Akcesoria

    this._promotion_category_page_section_3_price = 18.00;
    this._promotion_category_page_section_3_categories = [2, 5, 10, 11763, 63541, 16696]; // Komputery, Dom i Ogród, RTV i ADG, Zdrowie i Uroda, Dla Dzieci, Przemysł

    this._promotion_category_page_section_4_price = 22.00;
    this._promotion_category_page_section_4_categories = [64477, 1454, 63757]; // Biuro i Reklama, "Odzież, Obuwie, Dodatki", Erotyka


    /**
     * Zmiana ceny wystawianych przedmiotow
     */
    this.changePrice = function(recalculate)
    {
        var change;
        if ( $('#form_price option:selected').val() == 0 )
        {
            change = 0;
            $('#form_extra_price').val(0);
        }
        if ( $('#form_price option:selected').val() == 1 )
        {
            change = parseFloat($('#form_extra_price').val());
            change = isNaN(change) ? 0 : parseInt( change*100 );
        }
        else if ( $('#form_price option:selected').val() == 2 )
        {
            change = parseFloat($('#form_extra_price').val());
            change = isNaN(change) ? 0 : -parseInt( change*100 );
        }

        $('#auctionPrice li:not(.template)').each(function(){
            $(this).attr('data-price', parseInt($(this).data('price-orig'))+change);
        });

        if ( typeof(recalculate) == 'undefined' )
        {
            this.recalculate();
        }
    }

    this.changeQuantity = function( recalculate )
    {
        var quantity;
        if ( $('#form_all_stock').is(':checked') )
        {
            quantity = 100;
        }
        else
        {
            quantity = parseInt($('#form_quantity').val());
            quantity = isNaN(quantity) ? 1 : quantity;
            quantity = quantity == 0 ? 1 : quantity;
        }

        $('#auctionPrice li:not(.template)').each(function(){
            if ( !!$(this).data('in-stock') )
            {
                quantity = parseInt($(this).data('quantity-orig')) >= quantity ? quantity : parseInt($(this).data('quantity-orig'));
            }

            $(this).attr('data-quantity', quantity);
        });

        if ( typeof(recalculate) == 'undefined' )
        {
            this.recalculate();
        }
    }

    this.changeCategory = function(recalculate)
    {
        $('.product-category').each(function(){
            var variantId = $(this).data('variant-id');
            $('#auctionPrice #product'+variantId).attr('data-category-tree', $(this).find('option:selected').data('tree'));
        });

        if ( typeof(recalculate) == 'undefined' )
        {
            this.recalculate();
        }
    }

    this.changeImage = function(recalculate)
    {
        $this = this;

        var option = $('#form_images input[type=radio]:checked').val();

        $('#auctionPrice li:not(.template)').each(function(){
            var imageCount = parseInt($(this).data('image-count')) - 1;
            imageCount = imageCount >= 0 ? imageCount : 0;
            var extraImagePrice = option == 'one' ? 0 : imageCount*$this._image_price;
            $(this).attr('data-extra-image-price', extraImagePrice.toFixed(2) );
        });

        if ( typeof(recalculate) == 'undefined' )
        {
            this.recalculate();
        }
    }

    this.changePromotions = function(recalculate)
    {
        $this = this;

        $('#form_promotions input[type=checkbox]').each(function(){
            if ( $(this).is(':checked') && $('#auctionPrice li#promotion'+$(this).val()).length == 0 )
            {
                var item = $('#auctionPrice li.template').clone().removeClass('hide').removeClass('template').attr('id', 'promotion'+$(this).val());
                var count = $('#auctionPrice li.variant').length;

                switch( parseInt($(this).val()) )
                {
                    case 1: // Pogrubienie
                        $(item).find('.title').text( 'Pogrubienie' );
                        $(item).find('.provision').text( parseFloat($this._promotion_bold_price*count).toFixed(2) );
                        break;
                    case 2: // Miniaturka
                        $(item).find('.title').text( 'Miniaturka' );
                        $(item).find('.provision').text( parseFloat($this._promotion_thumbnail_price*count).toFixed(2) );
                        break;
                    case 4: // Podświetlenie
                        $(item).find('.title').text( 'Podświetlenie' );
                        $(item).find('.provision').text( parseFloat($this._promotion_higllight_price*count).toFixed(2) );
                        break;
                    case 8: // Wyróżnienie
                        var price = 0;
                        $('#auctionPrice li.variant').each(function(){
                            var categories = $(this).attr('data-category-tree') ? $(this).attr('data-category-tree').split('-') : [];
                            var hasIntersection = $this.hasIntersection($this._promotion_distinction_rare_categories, categories);

                            price += hasIntersection  ? $this._promotion_distinction_rare_price : $this._promotion_distinction_common_price;
                        });
                        $(item).find('.title').text( 'Wyróżnienie' );
                        $(item).find('.provision').text( price );
                        break;
                    case 16: // Strona kategorii
                        var price = 0;
                        $('#auctionPrice li.variant').each(function(){
                            var categories = $(this).attr('data-category-tree') ? $(this).attr('data-category-tree').split('-') : [];


                            var hasIntersection;
                            if ( hasIntersection = $this.hasIntersection($this._promotion_category_page_section_1_categories, categories) )
                            {
                                price += $this._promotion_category_page_section_1_price;
                            }
                            else if ( hasIntersection = $this.hasIntersection($this._promotion_category_page_section_2_categories, categories) )
                            {
                                price += $this._promotion_category_page_section_2_price;
                            }
                            else if ( hasIntersection = $this.hasIntersection($this._promotion_category_page_section_3_categories, categories) )
                            {
                                price += $this._promotion_category_page_section_3_price;
                            }
                            else if ( hasIntersection = $this.hasIntersection($this._promotion_category_page_section_4_categories, categories) )
                            {
                                price += $this._promotion_category_page_section_4_price;
                            }
                            else
                            {
                                price = 0;
                            }
                        });
                        $(item).find('.title').text( 'Strona kategorii' );
                        $(item).find('.provision').text( price );
                        break;
                }

                $(item).insertBefore('#auctionPrice .template');
            }
            else if ( !$(this).is(':checked') && $('#auctionPrice li#promotion'+$(this).val()).length > 0 )
            {
                $('#auctionPrice li#promotion'+$(this).val()).remove();
            }
        });

        if ( typeof(recalculate) == 'undefined' )
        {
            this.recalculate();
        }
    }

    this.recalculate = function(  )
    {
        $this = this;

        var total = 0;
        var specialCategories = [7, 98713, 89054, 100075, 20664]; // 'Książki i Komiksy', Płyty 3D, Płyty Blue-ray, Płyty DVD, Płyty VCD


        $('#auctionPrice li.variant').each(function(){
            var categories = $(this).attr('data-category-tree') ? $(this).attr('data-category-tree').split('-') : [];
            var hasIntersection = $this.hasIntersection(specialCategories, categories);


            var price = parseFloat($(this).attr('data-price')/100).toFixed(2);
            var quantity = $(this).attr('data-quantity');

            var provision, rate;
            if (price <= 9.99)
            {
                rate = hasIntersection ? 0.05 : 0.08;
            }
            else if (price <= 24.99)
            {
                rate = hasIntersection ? 0.08 : 0.13;
            }
            else if (price <= 49.99)
            {
                rate = hasIntersection ? 0.10 : 0.25;
            }
            else if (price <= 249.99)
            {
                rate = hasIntersection ? 0.15 : 0.50;
            }
            else
            {
                rate = hasIntersection ? 0.20 : 1.00;
            }
            provision = rate * quantity;
            if ( provision > 1.00 )
            {
                provision = 1.00;
            }

            var imagePrice = parseFloat($(this).attr('data-extra-image-price'));
            provision = provision + imagePrice;
            total += provision;

            $(this).find('.provision').text( provision.toFixed(2) );
        });

        $('#auctionPrice li.promotion:not(.template)').each(function(){
            total += parseFloat($(this).find('.provision').text());
        });

        $('.summaryBox .summary .price-all').text( total.toFixed(2) );
        $('#form_auction_price').val( total.toFixed(2) );
    }

    this.hasIntersection = function(arr1, arr2)
    {
        for(var i = 0; i < arr1.length; i++){
            for(var k = 0; k < arr2.length; k++){
                if(arr1[i] == arr2[k]){
                    return true;
                }
            }
        }
        return false;

    }
}