function toggleUploadModal() {
    const modal = document.getElementById('uploadModal');
    // We check the style specifically to handle initial display states
    if (modal.style.display === 'flex') {
        modal.style.display = 'none';
    } else {
        modal.style.display = 'flex';
    }
}

// Close modal if user clicks outside of the white box
window.onclick = function(event) {
    const modal = document.getElementById('uploadModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}