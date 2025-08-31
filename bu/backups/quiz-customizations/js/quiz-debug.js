jQuery(function($) {
    const debug = {
        init() {
            this.setupHintListeners();
            console.log('Quiz debug initialized');
        },
        setupHintListeners() {
            $(document)
                .on('click', '.take-hint', this.logHintEvent)
                .on('click', '.mark-hint', this.logHintEvent);
        },
        logHintEvent(e) {
            console.group(`Hint ${e.type.replace('click', '')}`);
            console.log('Element:', e.target);
            console.log('Question ID:', $(e.target).data('question-id'));
            console.groupEnd();
        }
    };
    debug.init();
});
