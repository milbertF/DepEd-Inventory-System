class Header extends HTMLElement {
  connectedCallback() {
    this.innerHTML = `
        <div class="header">
        
        </div>`;
  }
}

customElements.define("main-header", Header);
