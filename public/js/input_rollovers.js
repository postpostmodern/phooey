$(document).ready(function() {
	
	// Preload all rollovers
	$("input.rollover").each(function() {
		// Set the original src
		rollsrc = $(this).attr("src");
		rollON = rollsrc.replace(/.gif$/ig,"_OVER.gif");
		$("<img>").attr("src", rollON);
	});
	
	// Input rollovers
	$("input.rollover").mouseover(function(){
		inputsrc = $(this).attr("src");
		matches = inputsrc.match(/_OVER/);
		
		// don't do the rollover if state is already ON
		if (!matches) {
		  inputsrcON = inputsrc.replace(/.gif$/ig,"_OVER.gif"); // strip off extension
		  $(this).attr("src", inputsrcON);
		}
		
	});
	$("input.rollover").mouseout(function(){
		$(this).attr("src", inputsrc);
	});
	
});