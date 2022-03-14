!function(){"use strict";var e={n:function(t){var n=t&&t.__esModule?function(){return t.default}:function(){return t};return e.d(n,{a:n}),n},d:function(t,n){for(var r in n)e.o(n,r)&&!e.o(t,r)&&Object.defineProperty(t,r,{enumerable:!0,get:n[r]})},o:function(e,t){return Object.prototype.hasOwnProperty.call(e,t)}},t=window.wp.element,n=window.wp.components,r=window.wp.notices,o=window.wp.i18n,a=window.wp.data,c=window.wp.apiFetch,l=e.n(c),s=window.wp.primitives,u=(0,t.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"},(0,t.createElement)(s.Path,{d:"M20 5h-5.7c0-1.3-1-2.3-2.3-2.3S9.7 3.7 9.7 5H4v2h1.5v.3l1.7 11.1c.1 1 1 1.7 2 1.7h5.7c1 0 1.8-.7 2-1.7l1.7-11.1V7H20V5zm-3.2 2l-1.7 11.1c0 .1-.1.2-.3.2H9.1c-.1 0-.3-.1-.3-.2L7.2 7h9.6z"})),i=(0,t.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"},(0,t.createElement)(s.Path,{d:"M5 5v1.5h14V5H5zm0 7.8h14v-1.5H5v1.5zM5 19h14v-1.5H5V19z"}));const h="object"==typeof self?self:globalThis,g="",{toString:p}={},{keys:m}=Object,w=e=>{const t=typeof e;if("object"!==t||!e)return[0,t];const n=p.call(e).slice(8,-1);switch(n){case"Array":return[1,g];case"Object":return[2,g];case"Date":return[3,g];case"RegExp":return[4,g];case"Map":return[5,g];case"Set":return[6,g]}return n.includes("Array")?[1,n]:n.includes("Error")?[7,n]:[2,n]},d=([e,t])=>0===e&&("function"===t||"symbol"===t);var f="function"==typeof structuredClone?structuredClone:(e,t)=>{return n=((e,{json:t,lossy:n}={})=>{const r=[];return((e,t,n,r)=>{const o=(e,t)=>{const o=r.push(e)-1;return n.set(t,o),o},a=r=>{if(n.has(r))return n.get(r);let[c,l]=w(r);switch(c){case 0:{let t=r;switch(l){case"bigint":c=8,t=r.toString();break;case"function":case"symbol":if(e)throw new TypeError("unable to serialize "+l);t=null}return o([c,t],r)}case 1:{if(l)return o([l,[...r]],r);const e=[],t=o([c,e],r);for(const t of r)e.push(a(t));return t}case 2:{if(l)switch(l){case"BigInt":return o([l,r.toString()],r);case"Boolean":case"Number":case"String":return o([l,r.valueOf()],r)}if(t&&"toJSON"in r)return a(r.toJSON());const n=[],s=o([c,n],r);for(const t of m(r))!e&&d(w(r[t]))||n.push([a(t),a(r[t])]);return s}case 3:return o([c,r.toISOString()],r);case 4:{const{source:e,flags:t}=r;return o([c,{source:e,flags:t}],r)}case 5:{const t=[],n=o([c,t],r);for(const[n,o]of r)(e||!d(w(n))&&!d(w(o)))&&t.push([a(n),a(o)]);return n}case 6:{const t=[],n=o([c,t],r);for(const n of r)!e&&d(w(n))||t.push(a(n));return n}}const{message:s}=r;return o([c,{name:l,message:s}],r)};return a})(!(t||n),!!t,new Map,r)(e),r})(e,t),((e,t)=>{const n=(t,n)=>(e.set(n,t),t),r=o=>{if(e.has(o))return e.get(o);const[a,c]=t[o];switch(a){case 0:return n(c,o);case 1:{const e=n([],o);for(const t of c)e.push(r(t));return e}case 2:{const e=n({},o);for(const[t,n]of c)e[r(t)]=r(n);return e}case 3:return n(new Date(c),o);case 4:{const{source:e,flags:t}=c;return n(new RegExp(e,t),o)}case 5:{const e=n(new Map,o);for(const[t,n]of c)e.set(r(t),r(n));return e}case 6:{const e=n(new Set,o);for(const t of c)e.add(r(t));return e}case 7:{const{name:e,message:t}=c;return n(new h[e](t),o)}case 8:return n(BigInt(c),o);case"BigInt":return n(Object(BigInt(c)),o)}return n(new h[a](c),o)};return r})(new Map,n)(0);var n};const _=e=>(0,t.createElement)(t.Fragment,null,(0,o.__)("The following error has occurred:","wp-google-auth"),(0,t.createElement)("pre",{className:"error"},JSON.stringify(e,null,4))),E=()=>{const e=(0,a.useSelect)((e=>e(r.store).getNotices())).filter((e=>"snackbar"===e.type)),{removeNotice:o}=(0,a.useDispatch)(r.store);return(0,t.createElement)(n.SnackbarList,{notices:e,onRemove:o})},v=()=>(0,t.createElement)(n.Placeholder,null,(0,t.createElement)("div",{className:"placeholder-center"},(0,t.createElement)(n.Spinner,null))),b=e=>{let{pattern:r,onDelete:a,onPatternChange:c}=e;return(0,t.createElement)("div",{className:"email-pattern-row"},(0,t.createElement)("div",{className:"email-pattern-row-button"},(0,t.createElement)(n.Button,{onClick:a,isSecondary:!0},(0,t.createElement)(n.Icon,{icon:u,size:24}))),(0,t.createElement)("div",{className:"email-pattern-row-center"},(0,t.createElement)(n.TextControl,{label:(0,o.__)("Email regex pattern","wp-google-auth"),onChange:e=>c({regex:e,roles:r.roles}),value:r.regex})),(0,t.createElement)("div",{className:"email-pattern-row-button"},(0,t.createElement)(n.DropdownMenu,{icon:i,label:(0,o.__)("Select roles","wp-google-auth")},(()=>wpGoogleAuth.roles.map(((e,o)=>(0,t.createElement)(n.MenuItem,{key:`${o}`},(0,t.createElement)(n.CheckboxControl,{label:e.name,checked:r.roles.includes(e.key),onChange:t=>((e,t)=>{const n=r.roles.indexOf(e),o=f(r);n>=0&&!t&&o.roles.splice(n,1),n<0&&t&&o.roles.push(e),c(o)})(e.key,t)}))))))))},y=()=>{const[e,c]=(0,t.useState)(null),[s,u]=(0,t.useState)(null);(0,t.useEffect)((()=>{l()({path:"/wp/v2/settings"}).then((e=>{u(null==e?void 0:e.wp_google_auth_option)})).catch((e=>c(e)))}),[]);const{createNotice:i}=(0,a.useDispatch)(r.store);return e?(0,t.createElement)(_,{error:e}):s?(0,t.createElement)(t.Fragment,null,(0,t.createElement)("h2",null,(0,o.__)("OAuth Client","wp-google-auth")),(0,t.createElement)(n.TextControl,{label:(0,o.__)("Google OAuth client ID","wp-google-auth"),value:s.client_id,onChange:e=>u({...s,client_id:e})}),(0,t.createElement)(n.TextControl,{label:(0,o.__)("Google OAuth client secret","wp-google-auth"),value:s.client_secret,onChange:e=>u({...s,client_secret:e})}),(0,t.createElement)("h2",null,(0,o.__)("Account Settings","wp-google-auth")),(0,t.createElement)("p",null,(0,o.__)("Here you can enter regex pattern to be matched against user emails. For every match, you can select which roles should be applied to the user.","wp-google-auth")),s.email_patterns.map(((e,n)=>(0,t.createElement)(b,{key:`${n}`,pattern:e,onDelete:()=>{const e=f(s);e.email_patterns.splice(n,1),u(e)},onPatternChange:e=>{const t=f(s);t.email_patterns[n]=e,u(t)}}))),(0,t.createElement)(n.Button,{onClick:()=>{const e=f(s);e.email_patterns.push({regex:"",roles:[]}),u(e)},isSecondary:!0},(0,o.__)("Add pattern","wp-google-auth")),(0,t.createElement)("h2",null,(0,o.__)("Miscellaneous Settings","wp-google-auth")),(0,t.createElement)(n.TextControl,{label:(0,o.__)("Cache refresh interval (hours)","wp-google-auth"),type:"number",min:"0",value:s.cache_refresh,onChange:e=>u({...s,cache_refresh:Number(e)})}),(0,t.createElement)(n.Button,{onClick:()=>{const e=e=>i("error",(null==e?void 0:e.message)||JSON.stringify(e),{type:"snackbar"});l()({path:`/wp-google-auth/v1/validate/oauth-client?client_id=${s.client_id}&client_secret=${s.client_secret}`}).then((t=>{const n=t;(null==n?void 0:n.length)>0&&n.forEach((e=>i("error",e,{type:"snackbar",isDismissible:!1}))),l()({path:"/wp/v2/settings",method:"POST",data:{wp_google_auth_option:s}}).then((()=>{(null==n?void 0:n.length)<=0&&i("success",(0,o.__)("Settings saved! Please test out the login functionality to verify that everything is working as expected.","wp-google-auth"),{type:"snackbar"})})).catch(e)})).catch(e)},isPrimary:!0},(0,o.__)("Save changes","wp-google-auth"))):(0,t.createElement)(v,null)};var S=()=>(0,t.createElement)(t.Fragment,null,(0,t.createElement)("h1",null,(0,o.__)("WP Google Auth Settings","wp-google-auth")),(0,t.createElement)(y,null),(0,t.createElement)(E,null));document.addEventListener("DOMContentLoaded",(()=>{const e=document.getElementById("wp_google_auth_settings");e&&(0,t.render)((0,t.createElement)(S,null),e)}))}();