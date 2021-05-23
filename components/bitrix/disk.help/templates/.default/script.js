BX.namespace("BX.Disk");
BX.Disk.Help = (function ()
{
	return {
		onClickForScroll: function(e, node){
			BX.PreventDefault(e);
			this.scrollTo(node, 500, -30);
		},
		scrollTo: function (destinationNode, duration, correction)
		{

			var startY, finishY;

			startY = window.pageYOffset || document.documentElement.scrollTop;

			if (!correction && typeof correction != 'number')
				correction = 0;

			finishY = BX.pos(destinationNode).top + correction;

			var easing = new BX.easing({
				duration: duration,
				start: {
					scrollY: startY
				},
				finish: {
					scrollY: finishY
				},
				transition: BX.easing.makeEaseOut(BX.easing.transitions.quad),
				step: function (state)
				{
					window.scrollTo(0, state.scrollY);
				}
			});
			easing.animate()
		}
	};
})();

