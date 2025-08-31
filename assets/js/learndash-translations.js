// Debug logging
console.log('LearnDash Translations Script Loaded');

/**
 * Translate LearnDash expand/collapse buttons to Hebrew
 */
function translateLearnDashButtons() {
    console.log('--- Translation attempt at:', new Date().toISOString());
    
    // Find ALL possible button selectors
    const allButtons = document.querySelectorAll('button, .button, [role="button"], a');
    console.log('Total buttons found on page:', allButtons.length);
    
    // Specific LearnDash button selectors
    const ldExpandButtons = document.querySelectorAll('.ld-expand-button');
    console.log('LD expand buttons found:', ldExpandButtons.length);
    
    const ldTextElements = document.querySelectorAll('.ld-expand-button .ld-text');
    console.log('LD text elements found:', ldTextElements.length);
    
    // Check if we're on a Hebrew site
    const isHebrew = document.documentElement.lang === 'he-IL' || 
                    document.documentElement.dir === 'rtl' || 
                    document.body.classList.contains('rtl');
    
    if (!isHebrew) {
        console.log('Not a Hebrew site, skipping translation');
        return;
    }
    
    // Translate all expand/collapse buttons
    const buttons = document.querySelectorAll('.ld-expand-button, .ld-expand-button .ld-text');
    
    buttons.forEach(button => {
        const text = button.textContent.trim();
        console.log('Processing button with text:', text);
        
        if (text === 'Expand' || text === 'Expand All') {
            button.textContent = button.textContent.replace('Expand', 'הרחב');
            button.textContent = button.textContent.replace('Expand All', 'הרחב הכל');
            console.log('Translated Expand to הרחב');
        } 
        else if (text === 'Collapse' || text === 'Collapse All') {
            button.textContent = button.textContent.replace('Collapse', 'צמצם');
            button.textContent = button.textContent.replace('Collapse All', 'צמצם הכל');
            console.log('Translated Collapse to צמצם');
        }
    });
    
    // Also handle any dynamically loaded content
    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            if (mutation.addedNodes.length) {
                console.log('New nodes added, checking for buttons...');
                // Small delay to allow for any animations
                setTimeout(translateLearnDashButtons, 500);
            }
        });
    });
    
    // Start observing the document with the configured parameters
    observer.observe(document.body, { childList: true, subtree: true });
}

// Run on DOM content loaded
document.addEventListener('DOMContentLoaded', translateLearnDashButtons);

// Also run when LearnDash content is loaded (for AJAX loaded content)
if (typeof document.dispatchEvent === 'function') {
    document.addEventListener('learndash-content-loaded', translateLearnDashButtons);
    document.addEventListener('learndash-content-refreshed', translateLearnDashButtons);
}

// Export for testing
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { translateLearnDashButtons };
}
