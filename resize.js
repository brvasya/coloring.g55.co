function resize() {
	$(".pages").mason({
		itemSelector: ".thumbnail",
		ratio: 2/3,
		sizes: [[1,1]],
		columns: [[0,480,2],[480,780,4],[780,1080,5],[1080,1320,6],[1320,1680,8]],
		layout: "fluid",
		gutter: 5
	});
}
document.addEventListener("DOMContentLoaded", function() { resize(); });