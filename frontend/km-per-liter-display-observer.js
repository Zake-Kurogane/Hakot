(function() {
  // We'll observe changes under the #truckCards container.
  // Whenever new .truck-card-item elements appear, we check if they have data-truck info.
  const container = document.getElementById('truckCards');
  if (!container) {
    console.warn("km-per-liter-display-observer.js: #truckCards not found");
    return;
  }

  // A helper to parse the 'data-truck' JSON from the edit icon
  function parseTruckData(item) {
    const editIcon = item.querySelector('.card-icons i[data-truck]');
    if (!editIcon) return null;
    let encoded = editIcon.getAttribute('data-truck');
    if (!encoded) return null;
    try {
      encoded = decodeURIComponent(encoded);
      return JSON.parse(encoded);
    } catch (err) {
      console.warn("Couldn’t parse data-truck JSON:", err);
      return null;
    }
  }

  // A helper that inserts the Km/L <p> if not already inserted.
  function insertKmPerLiter(item, kmValue) {
    // Avoid duplicates
    if (item.querySelector('.km-per-liter-line')) return;

    const cardBody = item.querySelector('.card-body');
    if (!cardBody) return;

    // Create the new paragraph
    const p = document.createElement('p');
    p.className = 'card-text km-per-liter-line';
    p.textContent = `Fuel Consumption: ${kmValue} km/L`;

    // We’ll look for the <p> containing “Collectors:” (case-insensitive) and insert above it
    const paragraphs = cardBody.querySelectorAll('p.card-text');
    let inserted = false;
    paragraphs.forEach(par => {
      const text = par.textContent.trim().toLowerCase();
      if (text.includes('collectors:')) {
        cardBody.insertBefore(p, par);
        inserted = true;
      }
    });

    // If no “Collectors:” paragraph was found, just append at the end
    if (!inserted) {
      cardBody.appendChild(p);
    }
  }

  // Callback for our MutationObserver
  function onMutation(mutations) {
    for (const mutation of mutations) {
      if (mutation.type === 'childList') {
        mutation.addedNodes.forEach(node => {
          if (node.nodeType === 1 && node.classList.contains('truck-card-item')) {
            const data = parseTruckData(node);
            if (data && typeof data.kmPerLiter !== 'undefined') {
              insertKmPerLiter(node, data.kmPerLiter);
            }
          }
        });
      }
    }
  }

  // Initialize the observer
  const observer = new MutationObserver(onMutation);
  observer.observe(container, { childList: true });

  // Also handle any .truck-card-item elements that may already be in the DOM
  document.addEventListener('DOMContentLoaded', () => {
    const initialItems = container.querySelectorAll('.truck-card-item');
    initialItems.forEach(item => {
      const data = parseTruckData(item);
      if (data && typeof data.kmPerLiter !== 'undefined') {
        insertKmPerLiter(item, data.kmPerLiter);
      }
    });
  });
})();
