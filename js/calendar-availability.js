/**
 * Calendar Availability Handler
 * Handles the logic for determining when dates should be marked as fully booked
 */
(function($) {
    'use strict';

    /**
     * Check if a date is fully booked based on stock and orders
     * @param {number} stock - Available stock for the product
     * @param {number} orders - Number of orders for the date
     * @returns {boolean} - True if the date is fully booked
     */
    function isDateFullyBooked(stock, orders) {
        return stock > 0 && orders >= stock;
    }

    /**
     * Apply availability classes to calendar dates
     * @param {jQuery} calendarDay - The calendar day element to update
     * @param {Date} date - The date to check
     * @param {number} stock - Available stock
     * @param {number} orders - Number of orders for this date
     */
    function applyAvailabilityClasses(calendarDay, date, stock, orders) {
        // First check if it's a past date
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const isPastDate = date < today;

        // Remove existing availability classes
        calendarDay.removeClass('disabled reserved-date');

        if (isPastDate) {
            // Past dates are always disabled but never red
            calendarDay.addClass('disabled past-date');
            return;
        }

        // Check if the date is fully booked
        const isFullyBooked = isDateFullyBooked(stock, orders);
        
        if (isFullyBooked) {
            // Fully booked dates are both red and disabled
            calendarDay.addClass('disabled reserved-date');
            
            // Add tooltip showing it's fully booked
            calendarDay.attr('title', 'תאריך תפוס במלואו');
            console.log(`Date ${date.toISOString().split('T')[0]} is fully booked (Stock: ${stock}, Orders: ${orders})`);
        } else {
            // Clear any existing tooltip
            calendarDay.removeAttr('title');
            console.log(`Date ${date.toISOString().split('T')[0]} is available (Stock: ${stock}, Orders: ${orders})`);
        }
    }

    /**
     * Update the calendar legend to reflect availability states
     * @param {jQuery} calendar - The calendar container
     */
    function updateCalendarLegend(calendar) {
        const legend = $(`
            <div class="calendar-legend">
                <div class="legend-item"><span class="legend-color available"></span> פנוי</div>
                <div class="legend-item"><span class="legend-color reserved-date"></span> תפוס במלואו</div>
                <div class="legend-item"><span class="legend-color weekend"></span> שבת (סגור)</div>
            </div>
        `);

        // Replace existing legend if it exists, otherwise append
        const existingLegend = calendar.find('.calendar-legend');
        if (existingLegend.length) {
            existingLegend.replaceWith(legend);
        } else {
            calendar.append(legend);
        }
    }

    /**
     * Initialize availability handling for the calendar
     * @param {jQuery} calendar - The calendar container
     * @param {Object} stockData - Object containing stock information
     */
    function initializeAvailability(calendar, stockData) {
        if (!calendar || !calendar.length) {
            console.error('Calendar container not found');
            return;
        }

        const stock = parseInt(stockData.stock || 0, 10);
        const ordersData = stockData.orders || {};

        // Update each day in the calendar
        calendar.find('.day-cell').each(function() {
            const cell = $(this);
            const dateStr = cell.data('date');
            
            if (!dateStr) return; // Skip empty cells

            const date = new Date(dateStr);
            const orders = parseInt(ordersData[dateStr] || 0, 10);

            applyAvailabilityClasses(cell, date, stock, orders);
        });

        // Update the legend
        updateCalendarLegend(calendar);
    }

    // Expose functions to global scope
    window.CalendarAvailability = {
        initialize: initializeAvailability,
        isDateFullyBooked: isDateFullyBooked,
        applyAvailabilityClasses: applyAvailabilityClasses
    };

})(jQuery);
