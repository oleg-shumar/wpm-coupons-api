jQuery(document).ready(function($) {
    $('body').on('click', '.add-item', function() {
        var element = $(this).closest('.items-list');
        var count = $(this).closest('.items-list').find('.item-content').length;

        $(this).before($(element).find('.item-content:last').clone());
        $(element).find('.item-content:last').find('input').val('');
        var counter = parseInt($(element).find('.item-content:last').find('.number_element').text());

        counter++;
        $(element).find('.item-content:last').find('.number_element').text(counter);

        if(count === 1) {
            $(element).find('.item-content:last').append('<div class="delete_item">Delete</div>');
        }
    });

    $("body").on("click",".delete_item",function(){
        $(this).closest('.item-content').remove();
    });

    $("body").on("click","a.change-table",function(){
        var table = $(this).data('table');

        $('.change-table').removeClass('active');
        $(this).addClass('active');

        $('.select-table').hide();
        $('#'+table).show();
    });
});