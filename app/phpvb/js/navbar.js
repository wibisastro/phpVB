!function(n){function e(r){if(t[r])return t[r].exports;var a=t[r]={i:r,l:!1,exports:{}};return n[r].call(a.exports,a,a.exports,e),a.l=!0,a.exports}var t={};e.m=n,e.c=t,e.d=function(n,t,r){e.o(n,t)||Object.defineProperty(n,t,{configurable:!1,enumerable:!0,get:r})},e.n=function(n){var t=n&&n.__esModule?function(){return n.default}:function(){return n};return e.d(t,"a",t),t},e.o=function(n,e){return Object.prototype.hasOwnProperty.call(n,e)},e.p="",e(e.s=0)}([function(n,e,t){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var r=t(1);Vue.component(r.a.name,r.a)},function(n,e,t){"use strict";var r=t(8),a=t.n(r),s=t(9),i=!1,o=t(7)(a.a,s.a,!1,function(n){i||t(2)},null,null);o.options.__file="src/Navbar.vue",o.esModule&&Object.keys(o.esModule).some(function(n){return"default"!==n&&"__"!==n.substr(0,2)})&&console.error("named exports are not supported in *.vue files."),e.a=o.exports},function(n,e,t){var r=t(3);"string"==typeof r&&(r=[[n.i,r,""]]),r.locals&&(n.exports=r.locals);t(5)("c9cb00f0",r,!1)},function(n,e,t){(n.exports=t(4)(!0)).push([n.i,"\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n","",{version:3,sources:[],names:[],mappings:"",file:"Navbar.vue",sourceRoot:""}])},function(n,e){function t(n,e){var t=n[1]||"",a=n[3];if(!a)return t;if(e&&"function"==typeof btoa){var s=r(a),i=a.sources.map(function(n){return"/*# sourceURL="+a.sourceRoot+n+" */"});return[t].concat(i).concat([s]).join("\n")}return[t].join("\n")}function r(n){return"/*# "+("sourceMappingURL=data:application/json;charset=utf-8;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(n)))))+" */"}n.exports=function(n){var e=[];return e.toString=function(){return this.map(function(e){var r=t(e,n);return e[2]?"@media "+e[2]+"{"+r+"}":r}).join("")},e.i=function(n,t){"string"==typeof n&&(n=[[null,n,""]]);for(var r={},a=0;a<this.length;a++){var s=this[a][0];"number"==typeof s&&(r[s]=!0)}for(a=0;a<n.length;a++){var i=n[a];"number"==typeof i[0]&&r[i[0]]||(t&&!i[2]?i[2]=t:t&&(i[2]="("+i[2]+") and ("+t+")"),e.push(i))}},e}},function(n,e,t){function r(n){for(var e=0;e<n.length;e++){var t=n[e],r=l[t.id];if(r){r.refs++;for(i=0;i<r.parts.length;i++)r.parts[i](t.parts[i]);for(;i<t.parts.length;i++)r.parts.push(s(t.parts[i]));r.parts.length>t.parts.length&&(r.parts.length=t.parts.length)}else{for(var a=[],i=0;i<t.parts.length;i++)a.push(s(t.parts[i]));l[t.id]={id:t.id,refs:1,parts:a}}}}function a(){var n=document.createElement("style");return n.type="text/css",d.appendChild(n),n}function s(n){var e,t,r=document.querySelector('style[data-vue-ssr-id~="'+n.id+'"]');if(r){if(p)return m;r.parentNode.removeChild(r)}if(h){var s=v++;r=f||(f=a()),e=i.bind(null,r,s,!1),t=i.bind(null,r,s,!0)}else r=a(),e=o.bind(null,r),t=function(){r.parentNode.removeChild(r)};return e(n),function(r){if(r){if(r.css===n.css&&r.media===n.media&&r.sourceMap===n.sourceMap)return;e(n=r)}else t()}}function i(n,e,t,r){var a=t?"":r.css;if(n.styleSheet)n.styleSheet.cssText=b(e,a);else{var s=document.createTextNode(a),i=n.childNodes;i[e]&&n.removeChild(i[e]),i.length?n.insertBefore(s,i[e]):n.appendChild(s)}}function o(n,e){var t=e.css,r=e.media,a=e.sourceMap;if(r&&n.setAttribute("media",r),a&&(t+="\n/*# sourceURL="+a.sources[0]+" */",t+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(a))))+" */"),n.styleSheet)n.styleSheet.cssText=t;else{for(;n.firstChild;)n.removeChild(n.firstChild);n.appendChild(document.createTextNode(t))}}var c="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!c)throw new Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var u=t(6),l={},d=c&&(document.head||document.getElementsByTagName("head")[0]),f=null,v=0,p=!1,m=function(){},h="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());n.exports=function(n,e,t){p=t;var a=u(n,e);return r(a),function(e){for(var t=[],s=0;s<a.length;s++){var i=a[s];(o=l[i.id]).refs--,t.push(o)}e?r(a=u(n,e)):a=[];for(s=0;s<t.length;s++){var o=t[s];if(0===o.refs){for(var c=0;c<o.parts.length;c++)o.parts[c]();delete l[o.id]}}}};var b=function(){var n=[];return function(e,t){return n[e]=t,n.filter(Boolean).join("\n")}}()},function(n,e){n.exports=function(n,e){for(var t=[],r={},a=0;a<e.length;a++){var s=e[a],i=s[0],o={id:n+":"+a,css:s[1],media:s[2],sourceMap:s[3]};r[i]?r[i].parts.push(o):t.push(r[i]={id:i,parts:[o]})}return t}},function(n,e){n.exports=function(n,e,t,r,a,s){var i,o=n=n||{},c=typeof n.default;"object"!==c&&"function"!==c||(i=n,o=n.default);var u="function"==typeof o?o.options:o;e&&(u.render=e.render,u.staticRenderFns=e.staticRenderFns,u._compiled=!0),t&&(u.functional=!0),a&&(u._scopeId=a);var l;if(s?(l=function(n){(n=n||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(n=__VUE_SSR_CONTEXT__),r&&r.call(this,n),n&&n._registeredComponents&&n._registeredComponents.add(s)},u._ssrRegister=l):r&&(l=r),l){var d=u.functional,f=d?u.render:u.beforeCreate;d?(u._injectStyles=l,u.render=function(n,e){return l.call(e),f(n,e)}):u.beforeCreate=f?[].concat(f,l):[l]}return{esModule:i,exports:o,options:u}}},function(n,e){n.exports={name:"navbar",data:()=>({loginname:"Name"})}},function(n,e,t){"use strict";var r=function(){var n=this,e=n.$createElement,t=n._self._c||e;return t("div",[t("nav",{staticClass:"navbar",attrs:{role:"navigation","aria-label":"main navigation"}},[n._m(0),n._v(" "),t("div",{staticClass:"navbar-menu",attrs:{id:"navMenuTransparentExample"}},[n._m(1),n._v(" "),t("div",{staticClass:"navbar-end"},[t("div",{attrs:{role:"navigation","aria-label":"dropdown navigation"}},[t("div",{staticClass:"navbar-item has-dropdown"},[t("a",{staticClass:"navbar-link"},[n._v("\n                  "+n._s(n.loginname)+"\n                ")]),n._v(" "),n._m(2)])])])])])])};r._withStripped=!0;var a={render:r,staticRenderFns:[function(){var n=this,e=n.$createElement,t=n._self._c||e;return t("div",{staticClass:"navbar-brand"},[t("a",{staticClass:"navbar-item",attrs:{href:"https://bulma.io"}},[t("img",{attrs:{src:"https://bulma.io/images/bulma-logo.png",alt:"Bulma: a modern CSS framework based on Flexbox",width:"112",height:"28"}})]),n._v(" "),t("a",{staticClass:"navbar-item is-hidden-desktop",attrs:{href:"https://github.com/wibisastro/phpFramewerk",target:"_blank"}},[t("span",{staticClass:"icon",staticStyle:{color:"#333"}},[t("i",{staticClass:"fa fa-lg fa-github"})])]),n._v(" "),t("div",{staticClass:"navbar-burger burger",attrs:{"data-target":"navMenuTransparentExample"}},[t("span"),n._v(" "),t("span"),n._v(" "),t("span")])])},function(){var n=this,e=n.$createElement,t=n._self._c||e;return t("div",{staticClass:"navbar-start"},[t("div",{staticClass:"navbar-item"},[t("a",{attrs:{href:"/"}},[n._v("\n              Home\n            ")])]),n._v(" "),t("div",{staticClass:"navbar-item"},[t("a",{attrs:{href:"#"}},[n._v("\n              Platform\n            ")])])])},function(){var n=this,e=n.$createElement,t=n._self._c||e;return t("div",{staticClass:"navbar-dropdown is-right"},[t("a",{staticClass:"navbar-item"},[n._v("\n                    Account\n                  ")]),n._v(" "),t("a",{staticClass:"navbar-item"},[n._v("\n                    Profile\n                  ")]),n._v(" "),t("a",{staticClass:"navbar-item"},[n._v("\n                    Components\n                  ")]),n._v(" "),t("hr",{staticClass:"navbar-divider"}),n._v(" "),t("div",{staticClass:"navbar-item"},[n._v("\n                    Version 0.6.0\n                  ")])])}]};e.a=a}]);