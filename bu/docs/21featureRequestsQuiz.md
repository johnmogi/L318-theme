# 21featureRequestsQuiz

1. force right answer solution 
set up a new checkbox on our custom quiz override

C:\Users\anist\Desktop\CLIENTS\AVIV\LILAC\app\public\wp-content\themes\hello-theme-child-master\includes\admin\views\quiz-extensions.php

the request is to ¨force¨ the student to answer the right question before moving to the next question

when the student hits the wrong answer he will be directed to press the hint button
for example -
<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;">

and retake until the question is solved


2. change main quiz layout
on the learn options page there is allready a show sidebar checkbox

once enabled the layout will look like
[quiz][sidebar]

the sidebar will contain either the meida image or the video if they are inside the current answer acf field

C:\Users\anist\Desktop\CLIENTS\AVIV\LILAC\acf-export-2025-05-08.json
                "key": "field_681c4d22bf5ad",
                "label": "תמונה או סרטון למבחן",
                "name": "rich_media",
                