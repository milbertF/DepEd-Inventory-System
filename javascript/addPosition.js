function addPosition() {
    const addPosition = document.getElementById("addPosition");
    addPosition.style.display = (addPosition.style.display === 'none') ? 'flex' : 'none';
}

function escPosition() {

    document.getElementById("addPosition").style.display = "none";
    
    const form = document.getElementById("position-form");
    form.reset();
    
    const container = document.getElementById('positions-container');
    while (container.children.length > 1) {
        container.removeChild(container.lastChild);
    }
    
    positionCount = 1;
}

let positionCount = 1;
    
function addMorePosition() {
    const container = document.getElementById('positions-container');
    const newEntry = document.createElement('div');
    newEntry.className = 'position-entry';
    newEntry.dataset.index = positionCount;
    
    newEntry.innerHTML = `
        <button type="button" class="remove-office" onclick="removePosition(this)">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="inpart">
            <label for="position-title-${positionCount}">Position Title</label>
            <div class="inputs">
                <input type="text" id="position-title-${positionCount}" name="positions[${positionCount}][title]" placeholder="e.g., Administrative Officer" required />
            </div>
        </div>

        <div class="inpart">
            <label for="position-description-${positionCount}">Description (optional)</label>
            <div class="inputs">
                <input type="text" id="position-description-${positionCount}" name="positions[${positionCount}][description]" placeholder="Short description..." />
            </div>
        </div>
    `;
    
    container.appendChild(newEntry);
    positionCount++;
}
  
function removePosition(button) {
    const entry = button.closest('.position-entry');
    entry.remove();
}