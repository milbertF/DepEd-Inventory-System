function addEmployee() {
  const addEmployee = document.getElementById("addEmployee");
  addEmployee.style.display = (addEmployee.style.display === 'none') ? 'flex' : 'none';
}

function escEmployee() {
  const addEmployee = document.getElementById("addEmployee");
  addEmployee.style.display = "none";


  const form = addEmployee.querySelector('form');
  if (form) {
    form.reset();
  }


  const preview = document.getElementById("photoOutput");
  if (preview) {
    preview.src = "";
    preview.style.display = "none";
  }
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

