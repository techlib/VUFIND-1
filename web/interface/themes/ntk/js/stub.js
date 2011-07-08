function loadScript(script) {
   var e = document.createElement('script');
   e.setAttribute('src', script);
   document.getElementsByTagName('head')[0].appendChild(e);
}

loadScript('/interface/themes/ntk/js/calendar-min.js');
loadScript('/interface/themes/ntk/js/calendar.js');
