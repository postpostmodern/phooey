// If you use swfobject to load Flash elements on your page,
// add the code here following the example below.

function loadSWFs() {
  
  if($('#logo').length) {
    var so_logo = new SWFObject("/swf/logo.swf", "logo-flash", "220", "120", "9.0.28", "#000000");
    so_logo.addParam("wmode", "transparent");
    so_logo.write('logo');
  }

}

jQuery(loadSWFs);