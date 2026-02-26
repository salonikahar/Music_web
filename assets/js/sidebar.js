function searchSongs(query) {
  const searchResults = document.getElementById("searchResults");
  
  if (query.length < 2) {
    searchResults.innerHTML = "";
    searchResults.style.display = "none";
    return;
  }

  fetch(`${BASE_URL}/pages/search.php?q=${encodeURIComponent(query)}`)
    .then(res => res.text())
    .then(html => {
      searchResults.innerHTML = html;
      if (html.trim() !== "") {
        searchResults.style.display = "block";
      } else {
        searchResults.style.display = "none";
      }
    })
    .catch(err => {
      console.error('Search error:', err)
      searchResults.style.display = "none";
    });
}

// Handle Enter key to redirect to full search page
document.getElementById("searchInput").addEventListener("keypress", function(e) {
  if (e.key === "Enter") {
    const query = this.value.trim();
    if (query) {
      // Correctly build the URL for the full search page
      window.location.href = `${BASE_URL}/pages/search-results.php?q=${encodeURIComponent(query)}`;
    }
  }
});
