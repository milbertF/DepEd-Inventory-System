function addEmployee() {
  const addEmployee = document.getElementById("addEmployee");
  addEmployee.style.display = (addEmployee.style.display === 'none') ? 'flex' : 'none';
}

function escEmployee() {
  document.getElementById("addEmployee").style.display = "none";
}

function previewPhoto(event) {
  const file = event.target.files[0];
  const preview = document.getElementById("photoOutput");
  if (file) {
    preview.src = URL.createObjectURL(file);
    preview.style.display = "block";
  } else {
    preview.src = "";
    preview.style.display = "none";
  }
}

