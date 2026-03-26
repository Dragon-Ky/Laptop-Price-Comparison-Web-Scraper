$(document).ready(function() {

    // Lấy từ biến global PHP truyền vào
    var userLoggedIn = window.userLoggedIn ?? false;

    $('.btn-bookmark-star').on('click', function() {
        var starIcon = $(this);

        if (!userLoggedIn) {
            alert('Bạn cần đăng nhập để sử dụng chức năng này!');
            window.location.href = '/public/user/login.php';
            return;
        }

        if (starIcon.hasClass('loading')) return;
        starIcon.addClass('loading');

        let isBookmarked = starIcon.hasClass('bookmarked');
        let action = isBookmarked ? "remove" : "add";

        $.ajax({
            type: 'POST',
            url: "/controllers/product/bookmark_controller.php",
            data: {
                action: action,
                url: starIcon.data('url'),
                name: starIcon.data('name'),
                price: starIcon.data('price'),
                old_price: starIcon.data('old-price') || null,
                source_site: starIcon.data('site'),
                image_url: starIcon.data('image-url'),
                specs_summary: starIcon.data('specs'),
                
            },
            dataType: 'json',

            success: function(response) {
                starIcon.removeClass('loading');

                if (response.success) {
                    if (action === "add") {
                        starIcon.addClass('bookmarked').html('&#9733;'); // ⭐
                    } else {
                        starIcon.removeClass('bookmarked').html('&#9734;'); // ☆
                    }
                } else {
                    alert('Lỗi: ' + response.message);
                }
            },

            error: function(jqXHR, textStatus, errorThrown) {
                starIcon.removeClass('loading');
                alert('Lỗi kết nối máy chủ: ' + textStatus);
                console.error("AJAX Error:", errorThrown);
            }
        });
    });

});
