function addItem() {
    const addItem = document.getElementById("addItem");
    addItem.style.display = (addItem.style.display === 'none') ? 'flex' : 'none';
  }

  function escItem() {
    document.getElementById("addItem").style.display = "none";
  }

  function previewItemPhoto(event) {
    const output = document.getElementById('itemPhotoOutput');
    output.src = URL.createObjectURL(event.target.files[0]);
    output.style.display = "block";
    output.onload = () => URL.revokeObjectURL(output.src);
  }


  document.getElementById('unit-cost').addEventListener('input', calculateTotalCost);
  document.getElementById('item-quantity').addEventListener('input', calculateTotalCost);

  function calculateTotalCost() {
    const qty = parseFloat(document.getElementById('item-quantity').value) || 0;
    const unit = parseFloat(document.getElementById('unit-cost').value) || 0;
    document.getElementById('total-cost').value = (qty * unit).toFixed(2);
  }