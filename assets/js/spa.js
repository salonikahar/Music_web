const app = document.getElementById("app-content");

// Intercept clicks
document.addEventListener("click", (e) => {
  const link = e.target.closest("a[data-link]");
  if (!link) return;

  e.preventDefault();
  const url = link.getAttribute("href");

  loadPage(url);
});

function loadPage(url) {
  fetch(url)
    .then(res => res.text())
    .then(html => {
      app.innerHTML = extractContent(html);
      history.pushState({}, "", url);
    })
    .catch(err => console.error("SPA load error:", err));
}

// Handle back / forward
window.addEventListener("popstate", () => {
  loadPage(location.pathname + location.search);
});

// Extract only main content
function extractContent(html) {
  const temp = document.createElement("div");
  temp.innerHTML = html;

  const content = temp.querySelector(".main-content");
  return content ? content.innerHTML : html;
}
