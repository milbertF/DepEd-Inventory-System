function addRequest() {
    const addRequest = document.getElementById("addRequest");
    addRequest.style.display = (addRequest.style.display === 'none') ? 'flex' : 'none';
  }
  
  function escRequest() {
    const addRequest = document.getElementById('addRequest');
    addRequest.style.display = 'none';
  
    const form = document.getElementById('request-form');
    if (form) {
      form.reset();
    }
  }
  