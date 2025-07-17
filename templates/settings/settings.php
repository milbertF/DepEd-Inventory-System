<div class="settings" id="settings">
    <div class="esc">
        <button id="btnEscPosition" onclick="escPosition()">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <div class="setCon">
        <h4>Settings</h4>
        <div class="sec">
            <h5>Choose Accent Color</h5>
            <div class="listColors">
                <div class="colors color1" title="Color 1" onclick="choseColor(this)">
                    <p>Default</p>
                </div>
                <div class="colors color2" title="Color 2" onclick="choseColor(this)"></div>
                <div class="colors color3" title="Color 3" onclick="choseColor(this)"></div>
                <div class="colors color4" title="Color 4" onclick="choseColor(this)"></div>
                <div class="colors color5" title="Color 5" onclick="choseColor(this)"></div>
                <div class="colors color6" title="Color 6" onclick="choseColor(this)"></div>
                <div class="colors color7" title="Color 7" onclick="choseColor(this)"></div>
                <div class="colors color8" title="Color 8" onclick="choseColor(this)"></div>
            </div>
        </div>
    </div>
</div>


<script>
    function escPosition() {
        const settings = document.getElementById('settings');
        if (settings.style.display = 'none') {
            setting.style.display = 'flex';
        } else {
            setting.style.display = 'none';
        }
    }

    function openSettings() {
        const settings = document.getElementById('settings');
        settings.style.display = 'flex';
    }

    function choseColor(el) {
        document.querySelectorAll('.colors').forEach(c => {
            c.style.backgroundColor = '';
            c.style.boxShadow = '';
            c.style.zIndex = '';
        });

        el.style.boxShadow = 'rgb(255, 255, 255) 0rem 0rem 0rem 0.1rem, rgba(0, 0, 0, 0.5) 0rem 0rem 0rem 0.25rem';
        el.style.zIndex = '1';
    }
</script>