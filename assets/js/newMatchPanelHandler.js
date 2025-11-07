const newMatchBtn = document.getElementById('new-match-btn');

const newMatchPanel = document.getElementById('new-match-panel');
const matchLibraryMainContent = document.getElementById('match-library-main-content');

const closeMatchPanel = document.getElementById('close-new-match-panel');

if (newMatchBtn) {
    newMatchBtn.addEventListener('click', () => {
        matchLibraryMainContent.classList.add('hidden');
        newMatchPanel.classList.remove('hidden');
    });
}

if (closeMatchPanel) {
    closeMatchPanel.addEventListener('click', () => {
        newMatchPanel.classList.add('hidden');
        matchLibraryMainContent.classList.remove('hidden');
    });
}