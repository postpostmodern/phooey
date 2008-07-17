$(document).ready(function() {
	
	// Preload all rollovers
	$("a.rollover > img").each(function() {
		// Set the original src
		rollsrc = $(this).attr("src");
		rollON = rollsrc.replace(/.gif$/ig,"_OVER.gif").replace(/.jpg$/ig,"_OVER.jpg");
		$("<img>").attr("src", rollON);
	});
	
	// Link rollovers
	$("a.rollover").mouseover(function(){
		imgsrc = $(this).children("img").attr("src");
		matches = imgsrc.match(/_OVER/);
		
		// don't do the rollover if state is already ON
		if (!matches) {
		  imgsrcON = imgsrc.replace(/.gif$/ig,"_OVER.gif").replace(/.jpg$/ig,"_OVER.jpg"); // strip off extension
		  $(this).children("img").attr("src", imgsrcON);
		}
		
	});
	$("a.rollover").mouseout(function(){
		$(this).children("img").attr("src", imgsrc);
	});
	
});