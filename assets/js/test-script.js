/**
 * Simple test script
 */
alert('TEST SCRIPT LOADED - CLICK OK');
console.log('TEST SCRIPT CONSOLE LOG');

// Add a visible element to the page
window.addEventListener('DOMContentLoaded', function() {
    var testDiv = document.createElement('div');
    testDiv.style.position = 'fixed';
    testDiv.style.top = '0';
    testDiv.style.left = '0';
    testDiv.style.backgroundColor = 'red';
    testDiv.style.color = 'white';
    testDiv.style.padding = '10px';
    testDiv.style.zIndex = '9999';
    testDiv.innerHTML = 'TEST SCRIPT RUNNING';
    document.body.appendChild(testDiv);
});
