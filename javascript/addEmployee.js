class Createaccount extends HTMLElement {
  connectedCallback() {
    this.innerHTML = `
        <div class="addEmployee" style="display:none" id="addEmployee">
            <div class="esc">
                  <button id="btnEsc" onclick="escEmployee()">
                      <i class="fa-solid fa-xmark"></i>
                  </button> 
              </div>
            <div class="con">
              <h4>Create Account</h4>

              <div class="inpart">
                <label for="addEmployee-firstName">First Name</label>
                <div class="inputs">
                  <input type="text" id="addEmployee-firstName" />
                </div>
              </div>

              <div class="inpart">
                <label for="addEmployee-middleName">Middle Name</label>
                <div class="inputs">
                  <input type="text" id="addEmployee-middleName" />
                </div>
              </div>

              <div class="inpart">
                <label for="addEmployee-lastName">Last Name</label>
                <div class="inputs">
                  <input type="text" id="addEmployee-lastName" />
                </div>
              </div>

              <div class="inpart">
                <label for="addEmployee-position">Position</label>
                <div class="inputs">
                  <select id="addEmployee-position">
                    <option value="" style="display:none" disabled selected></option>
                    <option value="">option1</option>
                    <option value="">option2</option>
                    <option value="">option3</option>
                  </select>
                </div>
              </div>

              <div class="inpart">
                <label for="addEmployee-office">Office</label>
                <div class="inputs">
                  <select id="addEmployee-office">
                    <option value="" style="display:none" disabled selected></option>
                    <option value="">option1</option>
                    <option value="">option2</option>
                    <option value="">option3</option>
                  </select>
                </div>
              </div>

              <div class="inpart">
                <label for="addEmployee-accountType">Account Type</label>
                <div class="inputs">
                  <select id="addEmployee-accountType">
                    <option value="" style="display:none" disabled selected></option>
                    <option value="">option1</option>
                    <option value="">option2</option>
                    <option value="">option3</option>
                  </select>
                </div>
              </div>

              <div class="info">
                <p>Note: If the username and password fields are left blank, the default account credentials will be set to employee's firstname and lastname.</p>
                <i class="fa-solid fa-xmark"></i>
              </div>

              <div class="inpart">
                <label for="addEmployee-username">Username</label>
                <div class="inputs">
                  <input type="text" id="addEmployee-username" />
                </div>
              </div>

              <div class="inpart">
                <label for="addEmployee-password">Password</label>
                <div class="inputs">
                  <input type="text" id="addEmployee-password" />
                </div>
              </div>

              <div class="btnSave">
                <button>Save</button>
              </div>

            </div>
        </div>`;
  }
}

customElements.define("main-createaccount", Createaccount);


function addEmployee() {
  const addEmployee = document.getElementById("addEmployee");
  if (addEmployee.style.display === 'none'){
    addEmployee.style.display = 'flex';
  } else {
    addEmployee.style.display = 'none';
  }
}

function escEmployee() {
  const addEmployee = document.getElementById("addEmployee");
  addEmployee.style.display = "none";
}