(function() {
    tinymce.PluginManager.add("lilac_quiz_answers_button", function(editor, url) {
        editor.addButton("lilac_quiz_answers_button", {
            text: "Quiz Answers",
            icon: "dashicons-welcome-learn-more",
            onclick: function() {
                editor.windowManager.open({
                    title: "Insert Quiz Answers",
                    body: [
                        {
                            type: "textbox",
                            name: "quiz_id",
                            label: "Quiz ID",
                            value: ""
                        }
                    ],
                    onsubmit: function(e) {
                        editor.insertContent("[lilac_quiz_answers quiz_id=\"" + e.data.quiz_id + "\"]");
                    }
                });
            }
        });
    });
})();