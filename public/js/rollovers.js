/* Requires jQuery */

$(document).ready(function() {
  
  // Preload all rollovers
  $("a.rollover img, input.rollover, img.rollover").each(function() {
    // Set the original src
    rollsrc = $(this).attr("src");
    rollON = rollsrc.replace(/.gif$/ig,"_OVER.gif").replace(/.jpg$/ig,"_OVER.jpg");
    $("<img>").attr("src", rollON);
  });
  
  // Link rollovers
  $("a.rollover img, input.rollover, img.rollover").mouseover(function(){
    imgsrc = $(this).attr("src");
    matches = imgsrc.match(/_OVER/);
    
    // don't do the rollover if state is already ON
    if (!matches) {
      imgsrcON = imgsrc.replace(/.gif$/ig,"_OVER.gif").replace(/.jpg$/ig,"_OVER.jpg"); // strip off extension
      $(this).attr("src", imgsrcON);
    }
    
  });
  $("a.rollover img, input.rollover, img.rollover").mouseout(function(){
    $(this).attr("src", imgsrc);
  });
  
});