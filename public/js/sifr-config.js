  var avenir = {
    src: '/flash/avenir.swf'
  };

  //sIFR.debugMode = true;
  //sIFR.domains = ['example.com']
  sIFR.prefetch(avenir);
  sIFR.activate();
  
  sIFR.replace({ 
    src: avenir,
    selector: '#primary h1',
    css: {'.sIFR-root': {'color': '#084323', 'text-transform': 'uppercase', 'background-color': '#F5E9DE'}},
    //tuneHeight: -8,
    wmode: 'transparent'
  });

  sIFR.replace({ 
    src: avenir,
    selector: '#content h2',
    css: {'.sIFR-root': {'color': '#795334', 'background-color': '#F5E9DE'}},
    tuneHeight: -3,
    wmode: 'transparent'
  });
  
  
//  sIFR.replaceElement(named({sSelector:"#primary h1", sFlashSrc:"/flash/avenir.swf", sColor:"#084323", sBgColor:"#f5e9de", sWmode:"transparent", sCase:"upper", nPaddingTop:20, nPaddingRight:50, nPaddingBottom:45, nPaddingLeft:50, sFlashVars:"textalign=center"}));
//  sIFR.replaceElement(named({sSelector:"#content h2", sFlashSrc:"/flash/avenir.swf", sColor:"#ab7649", sBgColor:"#f5e9de", sWmode:"transparent"}));
