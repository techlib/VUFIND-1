function loadScript(script) {
   var e = document.createElement('script');
   e.setAttribute('src', script);
   document.getElementsByTagName('head')[0].appendChild(e);
}

loadScript('/interface/themes/aleph/js/calendar-min.js');
loadScript('/interface/themes/aleph/js/calendar.js');
