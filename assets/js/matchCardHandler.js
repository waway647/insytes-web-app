// 2. Add event listener to run when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    fetchAndRenderMatches();
});

/**
 * Main function to fetch data and render the cards
 */
async function fetchAndRenderMatches() {

    // Status → color map (define colors here)
    const STATUS_COLORS = {
        'ready': '#48ADF9',
        'tagging in progress': '#B14D35',
        'completed': '#209435',
        'waiting for video': '#B6BABD'
    };

    // 3. Get references to containers and templates
    const seasonHeader = document.getElementById('season-header');
    const monthCardsContainer = document.getElementById('month-cards-container');

    // Get the template cards
    const monthCardTemplate = document.getElementById('month-card');

    // Check if templates exist
    if (!monthCardsContainer || !monthCardTemplate) {
        console.error('Month container or template not found.');
        return;
    }

    // Get the inner match card template *from the month template*
    const matchCardTemplate = monthCardTemplate.querySelector('#match-card');

    if (!matchCardTemplate) {
        console.error('Match card template not found inside #month-card.');
        return;
    }

    // 4. IMPORTANT: Clone the inner template *before* removing the parent
    const matchCardTemplateClone = matchCardTemplate.cloneNode(true);

    // 5. Now, remove the hardcoded template card from the DOM
    monthCardTemplate.remove();

    // 6. Define the controller URL
    const fetchUrl = `get_all_matches`; // Adjust to your route if necessary

    try {
        // 7. Call the Fetch API
        const response = await fetch(fetchUrl);
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        const data = await response.json();

        // 8. Clear the container
        monthCardsContainer.innerHTML = '';

        // 9. Update the season header
        if (seasonHeader && data.season) {
            seasonHeader.textContent = data.season + ' Season';
        }

        // 10. --- Outer Loop (Months) ---
        (data.months || []).forEach(month => {

            // 11. Clone the month card template
            const monthCardClone = monthCardTemplate.cloneNode(true);
            monthCardClone.removeAttribute('id');

            // 12. Populate the cloned month card (using NEW IDs)
            // Use querySelector *on the clone*
            const monthHeader = monthCardClone.querySelector('#month-header');
            const year = monthCardClone.querySelector('#year');
            const viewAllBtnText = monthCardClone.querySelector('.month-name-in-btn');

            if (monthHeader) monthHeader.textContent = month.monthName;
            if (year) year.textContent = month.year;
            if (viewAllBtnText) viewAllBtnText.textContent = month.monthName;

            // 13. Get the *inner* match container *from the clone*
            const matchCardsContainer = monthCardClone.querySelector('#match-cards-container');

            // Clear the template match card that was cloned with it
            matchCardsContainer.innerHTML = '';

            // 14. --- Inner Loop (Matches) ---
            (month.matches || []).forEach(match => {

                // 15. Clone the match card template (using the one we saved)
                const matchCardClone = matchCardTemplateClone.cloneNode(true);
                matchCardClone.removeAttribute('id'); // Avoid duplicate IDs

                // 16. Populate the cloned match card

                // Set Thumbnail
                const thumbnailEl = matchCardClone.querySelector('#thumbnail');
                if (thumbnailEl && match.thumbnailUrl) {
                    thumbnailEl.style.backgroundImage = `url('${match.thumbnailUrl}')`;
                    thumbnailEl.style.backgroundSize = 'cover';
                    thumbnailEl.style.backgroundPosition = 'center';
                }

                // Set Status (use mapping instead of reading statusColor from API)
                // You had this selector earlier: '.border-blue-500' (template class). Keep using it.
                const statusContainer = matchCardClone.querySelector('.border-blue-500');
                const statusDot = matchCardClone.querySelector('#status-color');
                const statusText = matchCardClone.querySelector('#status');

                if (statusContainer && statusDot && statusText) {
                    // Remove template color classes so they don't conflict
                    statusContainer.classList.remove('border-blue-500');
                    statusDot.classList.remove('bg-blue-500');

                    // derive color from mapping (case-insensitive)
                    const key = (match.status || '').toString().toLowerCase().trim();
                    const color = STATUS_COLORS[key] || '#B6BABD'; // default/fallback

                    // apply inline styles (works reliably without Tailwind purge issues)
                    statusContainer.style.borderStyle = 'solid';
                    // keep border width if template set it via CSS; otherwise set 1px
                    statusContainer.style.borderWidth = statusContainer.style.borderWidth || '1px';
                    statusContainer.style.borderColor = color;

                    statusDot.style.backgroundColor = color;

                    statusText.textContent = match.status || '';
                }

                // Set Match Info
                const matchNameEl = matchCardClone.querySelector('#match-name');
                const matchDateEl = matchCardClone.querySelector('#match-date');

                if (matchNameEl) matchNameEl.textContent = match.matchName;
                if (matchDateEl) matchDateEl.textContent = match.matchDate;

                // 17. Append the populated match card to the *inner* container
                matchCardsContainer.appendChild(matchCardClone);
            });

            // 18. Append the fully populated month card to the *main* container
            monthCardsContainer.appendChild(monthCardClone);
        });

    } catch (error) {
        console.error('Failed to fetch matches:', error);
        monthCardsContainer.innerHTML = `<p class="text-red-500 text-center">We couldn't load the match library. Please try again later.</p>`;
    }
}
