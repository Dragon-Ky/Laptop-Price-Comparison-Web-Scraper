(function(){
    // Adjust font size class based on actual rendered width / length
    function adjustStatTimes() {
        document.querySelectorAll('.stat-card .stat-time').forEach(function(el){
            // remove previous classes
            el.classList.remove('small','xsmall');

            // prefer measuring overflow (works across fonts)
            var overflows = el.scrollWidth > el.clientWidth + 2;

            // fallback: check character length
            var longText = el.textContent.trim().length > 40;
            var veryLongText = el.textContent.trim().length > 80;

            if (veryLongText || (longText && overflows)) {
                el.classList.add('xsmall');
            } else if (longText || overflows) {
                el.classList.add('small');
            }
        });
    }

    // Run on load and on window resize (debounced)
    window.addEventListener('DOMContentLoaded', adjustStatTimes);
    var t;
    window.addEventListener('resize', function(){
        clearTimeout(t);
        t = setTimeout(adjustStatTimes, 150);
    });

    // If dashboard content loads async, expose helper
    window.adjustStatTimes = adjustStatTimes;
})();