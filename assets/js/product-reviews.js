document.addEventListener('DOMContentLoaded', function () {

    const container = document.getElementById(
        'hom-product-reviews-container'
    );

    if (!container || typeof HOMProductReviews === 'undefined') {
        return;
    }


    function loadReviews() {

        const formData = new FormData();

        formData.append(
            'action',
            'hom_load_product_reviews'
        );

        formData.append(
            'nonce',
            HOMProductReviews.nonce
        );


        fetch(
            HOMProductReviews.ajaxUrl,
            {
                method: 'POST',
                body: formData
            }
        )
        .then(response => response.json())
        .then(result => {

            if (result.success) {

                container.innerHTML = result.data.html;

            } else {

                container.innerHTML =
                    '<p>خطا در دریافت نظرات</p>';

            }

        });

    }


    loadReviews();

});
