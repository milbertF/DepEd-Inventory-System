document.querySelectorAll('.action-btn.delete').forEach(button => {
    button.addEventListener('click', function () {
      const employeeId = this.getAttribute('data-id');
      const employeeName = this.getAttribute('data-name');

      Swal.fire({
        title: 'Are you sure?',
        text: `You are about to delete "${employeeName}".`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          sessionStorage.setItem('deletedEmployeeName', employeeName);
          window.location.href = `/templates/employee/function/deleteEmp.php?id=${employeeId}`;
        }
      });
    });
  });


  const deletedName = sessionStorage.getItem('deletedEmployeeName');
  if (deletedName) {
    Swal.fire({
      icon: 'success',
      title: 'Deleted!',
      html: `Employee <b>${deletedName}</b> was deleted successfully.`,
      confirmButtonColor: '#3085d6'
    }).then(() => {
      const url = new URL(window.location.href);
      url.searchParams.delete('deleted');
      url.searchParams.delete('id');
      window.history.replaceState({}, document.title, url.pathname);
    });

    sessionStorage.removeItem('deletedEmployeeName');
  }


  document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("searchEmployee");
    const rows = document.querySelectorAll(".employeeTable tbody tr");
  
    searchInput.addEventListener("input", function () {
      const searchValue = searchInput.value.toLowerCase();
  
      rows.forEach((row) => {
        const rowText = row.innerText.toLowerCase();
        row.style.display = rowText.includes(searchValue) ? "" : "none";
      });
    });
  });


  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.action-btn.view').forEach(button => {
      button.addEventListener('click', function () {
        const row = this.closest('tr');
        const photo = row.querySelector('img').src;
        const fullName = row.cells[2].innerText.trim();
        const role = row.cells[3].innerText.trim();
        const position = row.cells[4].innerText.trim();
        const office = row.cells[5].innerText.trim();
        const contact = row.cells[6].innerText.trim();
        const address = row.cells[7].innerText.trim();
        const dateAdded = row.cells[8].innerText.trim();
  
        document.getElementById('view-photo').src = photo || '/images/user-profile/default-image.jpg';
        document.getElementById('view-full-name').innerText = fullName;
        document.getElementById('view-role').innerText = role;
        document.getElementById('view-position').innerText = position;
        document.getElementById('view-office').innerText = office;
        document.getElementById('view-contact').innerText = contact;
        document.getElementById('view-address').innerText = address;
        document.getElementById('view-date-added').innerText = dateAdded;
  
        document.getElementById('employeeViewModal').style.display = 'flex';
      });
    });
  });
  
  function closeEmployeeView() {
    document.getElementById('employeeViewModal').style.display = 'none';
  }