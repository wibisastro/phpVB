<template>
<div>
    <nav class="navbar" role="navigation" aria-label="main navigation">
      <div class="navbar-brand">
        <a class="navbar-item" href="https://bulma.io">
          <img src="https://bulma.io/images/bulma-logo.png" alt="Bulma: a modern CSS framework based on Flexbox" width="112" height="28">
        </a>
          
        <a class="navbar-item is-hidden-desktop" href="https://github.com/wibisastro/phpFramewerk" target="_blank">
          <span class="icon" style="color: #333;">
            <i class="fa fa-lg fa-github"></i>
          </span>
        </a>
          
        <div class="navbar-burger burger" data-target="navMenuTransparentExample">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
      <div class="navbar-menu" id="navMenuTransparentExample">
          <div class="navbar-start">
            <div class="navbar-item">
            <a href="/">
              Home
            </a>
            </div> 
            <div class="navbar-item">
            <a href="#">
              Platform
            </a>
            </div> 
          </div>

          <div class="navbar-end">
            <div role="navigation" aria-label="dropdown navigation">
              <div class="navbar-item has-dropdown">
                <a class="navbar-link">
                  {{ loginname }}
                </a>

                <div class="navbar-dropdown is-right">
                  <a class="navbar-item">
                    Account
                  </a>
                  <a class="navbar-item">
                    Profile
                  </a>
                  <a class="navbar-item">
                    Components
                  </a>
                  <hr class="navbar-divider">
                  <div class="navbar-item">
                    Version 0.6.0
                  </div>
                </div>
              </div>
            </div>
          </div>
      </div>
    </nav>
</div>
</template>

<script>
module.exports = {
    name: 'navbar',
    data () {
        return {
            loginname:'Name',
        }
    }
}
</script>

<style>

</style>