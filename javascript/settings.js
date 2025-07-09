class Settings extends HTMLElement {
  connectedCallback() {
    this.innerHTML = `
        <div class="settings">
            <div class="esc">
                <button>
                    <i class="fa-solid fa-xmark"></i>
                </button> 
            </div>
            
            <div class="setCon">
                <div class="perSet">
                    <input type="radio" name="settingsRadio" id="settingsRadio-theme"/>
                    <label for="settingsRadio-theme">Theme</label>
                    <input type="radio" name="settingsRadio" id="settingsRadio-account"/>
                    <label for="settingsRadio-account">Account</label>
                </div>
                <div></div>
            </div>
        </div>
        
        `;
  }
}

customElements.define("main-settings", Settings);
