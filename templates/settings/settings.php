

<style>
.settings {
  display: none; 
  position: fixed;
  top: 0;
  right: 0;
  width: 18rem;
  height: 100%;
  background-color: white;
  box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
  z-index: 999;
  flex-direction: column;
  padding: 1rem;

  /* ensure panel doesn’t affect body scroll */
  overflow-y: auto;
}

</style>
<div class="settings" id="settings">
  <div class="esc">
    <button id="btnEscPosition" onclick="escSetting()">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>
  <div class="setCon">
    <h4>Settings</h4>
    <div class="sec">
      <h5>Choose Accent Color</h5>
      <div class="listColors">
      <div class="colors" style="background-color: #e74c3c;" onclick="choseColor(this, '#e74c3c')">
          <p>Default</p>
        </div>

        <div class="colors" style="background-color: #1e90ff;" onclick="choseColor(this, '#1e90ff')"></div>
        <div class="colors" style="background-color: #27ae60;" onclick="choseColor(this, '#27ae60')"></div>
        <div class="colors" style="background-color: #f39c12;" onclick="choseColor(this, '#f39c12')"></div>
        <div class="colors" style="background-color: #8e44ad;" onclick="choseColor(this, '#8e44ad')"></div>
        <div class="colors" style="background-color: #16a085;" onclick="choseColor(this, '#16a085')"></div>
        <div class="colors" style="background-color: #2c3e50;" onclick="choseColor(this, '#2c3e50')"></div>
        <div class="colors" style="background-color: #34495e;" onclick="choseColor(this, '#34495e')"></div>
      </div>
    </div>
  </div>
</div>

<script>
function escSetting() {
  const settings = document.getElementById('settings');
  if (settings.style.display === 'none' || settings.style.display === '') {
    settings.style.display = 'flex';
    document.body.style.overflow = 'hidden'; 
  } else {
    settings.style.display = 'none';
    document.body.style.overflow = '';
  }
}


  function openSettings() {
    const settings = document.getElementById('settings');
    settings.style.display = 'flex';
  }

  function choseColor(el, colorValue) {

  localStorage.setItem('accentColor', colorValue);


  document.documentElement.style.setProperty('--accentColor', colorValue);


  highlightSelectedColor(colorValue);


  escSetting();
}

  function highlightSelectedColor(currentColor) {
    document.querySelectorAll('.colors').forEach(c => {
      const bgColor = window.getComputedStyle(c).backgroundColor;
      const tempDiv = document.createElement("div");
      tempDiv.style.backgroundColor = currentColor;
      document.body.appendChild(tempDiv);
      const standardizedColor = window.getComputedStyle(tempDiv).backgroundColor;
      document.body.removeChild(tempDiv);

      if (bgColor === standardizedColor) {
        c.style.boxShadow = 'rgb(255, 255, 255) 0rem 0rem 0rem 0.1rem, rgba(0, 0, 0, 0.5) 0rem 0rem 0rem 0.25rem';
        c.style.zIndex = '1';
      } else {
        c.style.boxShadow = '';
        c.style.zIndex = '';
      }
    });
  }

  window.addEventListener('DOMContentLoaded', () => {
    const savedColor = localStorage.getItem('accentColor');
    if (savedColor) {
      document.documentElement.style.setProperty('--accentColor', savedColor);
      highlightSelectedColor(savedColor);
    }
  });
</script> 
