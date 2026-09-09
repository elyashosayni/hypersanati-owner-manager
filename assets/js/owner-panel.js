(function($){

    'use strict';

    $(document).on('click', '.hom-question-action.approve, .hom-question-action.reject, .hom-question-action.delete', function(e){

        e.preventDefault();

        const button = $(this);
        const form = button.closest('form');

        const data = {
            action: 'hom_question_action',
            nonce: form.find('[name="hom_question_nonce"]').val(),
            question_id: form.find('[name="question_id"]').val(),
            question_action: button.val()
        };

        $.post(
            HOMOwnerPanel.ajaxUrl,
            data,
            function(response){

                if(response.success){

                    if(data.question_action === 'delete'){
                        button.closest('.hom-customer-question-item').fadeOut(300, function(){
                            $(this).remove();
                        });
                    } else {
                        button.closest('.hom-customer-question-item')
                            .replaceWith(response.data.html);
                    }

                }

            }
        );

    });


})(jQuery);
