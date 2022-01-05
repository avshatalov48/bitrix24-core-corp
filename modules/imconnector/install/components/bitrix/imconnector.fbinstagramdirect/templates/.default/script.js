;(function(window){
	window.popupCommentShow = function(id)
	{
		var input = BX(id);
		if(!!input.checked)
		{
			var popupShowTrue = new BX.PopupWindow('uid_instagram_for_business', null, {
				closeIcon: { right : '5px', top : '5px'},
				titleBar: BX.message('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONFIRM_TITLE'),
				closeByEsc : true,
				autoHide : true,
				content: '<p class=\"imconnector-popup-text\">' + BX.message('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONFIRM_DESCRIPTION') + '</p>',
				overlay: {
					backgroundColor: 'black', opacity: '80'
				},
				buttons: [
					new BX.PopupWindowButton({
						text: BX.message('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONFIRM_BUTTON_OK'),
						className : 'popup-window-button-accept',
						events:{
							click: function(){
								input.checked = true;
								this.popupWindow.close();
								popupShowTrue.destroy();
							}
						}
					}),
					new BX.PopupWindowButton({
						text: BX.message('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONFIRM_BUTTON_CANCEL'),
						className : 'popup-window-button-link',
						events:{
							click: function(){
								input.checked = false;
								this.popupWindow.close();
								popupShowTrue.destroy();
							}
						}
					})
				]
			});
			popupShowTrue.show();
		}
	};
	window.toggleHideList = function(event)
	{
		var hiddenList = document.getElementById('hidden-list');

		hiddenList.style.display = (hiddenList.style.display !== 'block') ? 'block' : 'none';
		return false;
	};

	BX.ready(function(){
		BX.bindDelegate(
			document.body,
			'click',
			{className: 'imconnector-field-box-entity-icon-copy-to-clipboard'},
			copyToClipboard
		);
		BX.bindDelegate(
			document.body,
			'click',
			{props: {id: 'toggle-list'}},
			toggleHideList
		);
		BX.bindDelegate(
			document.body,
			'click',
			{className: 'show-preloader-button'},
			addPreloader
		);
		BX.bindDelegate(
			document.body,
			'submit',
			{tag: 'form'},
			addPreloader
		);
	});
})(window);
