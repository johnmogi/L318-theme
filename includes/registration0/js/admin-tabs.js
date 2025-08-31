jQuery(document).ready(function($) {
    'use strict';

    // Initialize tabs
    function initTabs() {
        // Main admin tabs
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var tab = $this.attr('href');
            
            // Update active tab
            $('.nav-tab').removeClass('nav-tab-active');
            $this.addClass('nav-tab-active');
            
            // Show active tab content
            $('.tab-pane').removeClass('active');
            $(tab).addClass('active');
            
            // Update URL
            if (history.pushState) {
                var newUrl = window.location.pathname + '?page=registration-codes&tab=' + tab.replace('#', '');
                window.history.pushState({ path: newUrl }, '', newUrl);
            }
        });

        // Import/Export tabs
        $('.import-export-tab-btn').on('click', function() {
            var tabId = $(this).data('tab');
            
            // Update active button
            $('.import-export-tab-btn').removeClass('active');
            $(this).addClass('active');
            
            // Show active tab content
            $('.import-export-tab-pane').removeClass('active');
            $('#' + tabId).addClass('active');
        });

        // Handle browser back/forward buttons
        $(window).on('popstate', function() {
            var urlParams = new URLSearchParams(window.location.search);
            var tab = urlParams.get('tab');
            
            if (tab) {
                // Update main tabs
                $('.nav-tab').removeClass('nav-tab-active');
                $('.nav-tab[href="#' + tab + '"]').addClass('nav-tab-active');
                
                $('.tab-pane').removeClass('active');
                $('#' + tab).addClass('active');
            }
        });
    }

    // Initialize datepickers
    function initDatepickers() {
        $('.datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            minDate: 0
        });
    }

    // Initialize tooltips
    function initTooltips() {
        $('.help-tip').tooltip({
            content: function() {
                return $(this).attr('title');
            },
            position: {
                my: 'center bottom-20',
                at: 'center top',
                using: function(position, feedback) {
                    $(this).css(position);
                    $('<div>')
                        .addClass('arrow')
                        .addClass(feedback.vertical)
                        .addClass(feedback.horizontal)
                        .appendTo(this);
                }
            },
            tooltipClass: 'wp-tooltip'
        });
    }

    // Initialize all components
    function init() {
        initTabs();
        initDatepickers();
        initTooltips();
    }

    // Run initialization
    init();
});
