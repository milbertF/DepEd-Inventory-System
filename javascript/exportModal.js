document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("exportForm");

  const minQuantity = document.getElementById("minQuantity");
  const maxQuantity = document.getElementById("maxQuantity");
  const minCost     = document.getElementById("minCost");
  const maxCost     = document.getElementById("maxCost");

  const quantityError = document.getElementById("quantityError");
  const costError     = document.getElementById("costError");
  const exportBtn     = document.getElementById("exportBtn");

  function validateNumberRange(minEl, maxEl, errorEl, label) {
    const hasMin = minEl.value !== "" && !isNaN(minEl.value);
    const hasMax = maxEl.value !== "" && !isNaN(maxEl.value);

    if (hasMin && hasMax && Number(minEl.value) > Number(maxEl.value)) {
      errorEl.textContent = ` Min ${label} cannot be greater than Max ${label}.`;
      return false;
    }
    errorEl.textContent = "";
    return true;
  }

  function validateAll() {
    let ok = true;
    
    ok = validateNumberRange(minQuantity, maxQuantity, quantityError, "quantity") && ok;
    ok = validateNumberRange(minCost, maxCost, costError, "cost") && ok;
    
    exportBtn.disabled = !ok;
    return ok;
  }

  [minQuantity, maxQuantity, minCost, maxCost].forEach(el =>
    el.addEventListener("input", validateAll)
  );

  form.addEventListener("submit", function (e) {
    if (!validateAll()) e.preventDefault();
  });


  validateAll();
});



function initDateValidation2() {
    const dateFromInput = document.getElementById("start_date");
    const dateToInput = document.getElementById("end_date");
  
    if (!dateFromInput || !dateToInput) return;
  

    dateFromInput.addEventListener("change", () => {
      if (dateFromInput.value) {
        dateToInput.min = dateFromInput.value;
  
     
        if (dateToInput.value && dateToInput.value < dateFromInput.value) {
          dateToInput.value = "";
        }
      } else {
      
        dateToInput.min = "";
      }
    });
  

    dateToInput.addEventListener("input", () => {
      if (dateFromInput.value && dateToInput.value < dateFromInput.value) {
        dateToInput.value = dateFromInput.value;
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function() {



  initDateValidation2();

});