jQuery(document).ready(function ($) {
    // Removed outdated debug log

    $(document).on('click', '#mark-hint', function () {
        var hintElement = $('.highlight-hint');
        var hintPopup = $('.wpProQuiz_tipp');

        if (hintElement.length) {
            hintPopup.show();
            hintElement.addClass('highlight-effect');
            $('html, body').animate({
                scrollTop: hintElement.offset().top - 100 
            }, 500);
        }
    });

    $(document).on('click', '#close-hint', function () {
        $('.wpProQuiz_tipp').hide();
        $('.highlight-hint').removeClass('highlight-effect');
    });




    //  // Listen for clicks on the "Check" button
    //  $(document).on("click", ".wpProQuiz_QuestionButton", function () {
    //     setTimeout(function () {
    //         // Re-enable all answer options in the same question
    //         $(".wpProQuiz_questionInput").prop("disabled", false);
    //     }, 100); // Slight delay to override LearnDash behavior
    // });


});
