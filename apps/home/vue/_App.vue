<template>
<div id="app">
    <section class="hero is-primary">
        <navbar></navbar>
      <div class="hero-body">
        <div class="container">
            <headerbar></headerbar>
        </div>
      </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="columns">
              <div class="column is-narrow">
                <div style="width: 200px;">
                    <menubar></menubar>
                </div>
              </div>
              <div class="column">
                    <titlebar></titlebar>
                    <breadcrumb></breadcrumb>	
                    <contents></contents>
              </div>
            </div>
        </div>
    </section>

    <footer class="footer">
      <div class="container">
        <div class="content has-text-centered">
            <footerbar></footerbar>
        </div>
      </div>
    </footer>
</div>
</template>

<script>
import Headerbar from './headerbar.vue';
import Navbar from './navbar.vue';
import Breadcrumb from './breadcrumb.vue';
import Menubar from './menubar.vue';
import Titlebar from './titlebar.vue';
import Footerbar from './footerbar.vue';
export default {
    name: 'app',
    components: { Headerbar, Navbar, Breadcrumb, Menubar, Titlebar, Footerbar },
    data () {
        return {
            titleBar: 'Welcome to Your Vue.js App'
        }
    }
}
</script>

<style>

</style>
