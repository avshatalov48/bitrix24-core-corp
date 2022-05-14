;(function(window){
	if (window.BX.PopupWindowManager) return;

	BX.PopupWindowManager = function()
	{
		this.create = () => {};
	};

	BX.PopupWindow = function()
	{
		this.create = () => {};
		this.popup = {
			popupContainer: null,
			cancelBubble: null
		};
	};
})(window);