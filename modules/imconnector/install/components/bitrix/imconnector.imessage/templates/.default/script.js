;(function(window){
	window.popupIMessageShow = function()
	{
		var popupShowTrue = new BX.PopupWindow('uid_imessage_active', null, {
			closeIcon: { right : '5px', top : '5px'},
			titleBar: BX.message('IMCONNECTOR_COMPONENT_IMESSAGE_CONFIRM_TITLE'),
			closeByEsc : true,
			autoHide : true,
			content: '<p class=\"imconnector-popup-text\">' + BX.message('IMCONNECTOR_COMPONENT_IMESSAGE_CONFIRM_DESCRIPTION') + '</p>',
			overlay: {
				backgroundColor: 'black', opacity: '80'
			},
			buttons: [
				new BX.PopupWindowButton({
					text: BX.message('IMCONNECTOR_COMPONENT_IMESSAGE_CONFIRM_BUTTON_OK'),
					className : 'popup-window-button-accept',
					events:{
						click: function(){
							BX.Http.Cookie.set(BX.message('IMCONNECTOR_COMPONENT_IMESSAGE_COOKIE_NAME'), BX.message('IMCONNECTOR_COMPONENT_IMESSAGE_LINE_ID'), {'path':'/'});
							top.location.href = BX.message('IMCONNECTOR_COMPONENT_IMESSAGE_URL_NETWORK');
						}
					}
				}),
				new BX.PopupWindowButton({
					text: BX.message('IMCONNECTOR_COMPONENT_IMESSAGE_CONFIRM_BUTTON_CANCEL'),
					className : 'popup-window-button-link',
					events:{
						click: function(){this.popupWindow.close()}
					}
				})
			]
		});
		popupShowTrue.show();
	};
	function showIMessageHelpdesk()
	{
		top.BX.Helper.show('redirect=detail&code=10798618');
		return false;
	}
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
			{className: 'show-preloader-button'},
			addPreloader
		);
		BX.bindDelegate(
			document.body,
			'submit',
			{tag: 'form'},
			addPreloader
		);
		BX.bindDelegate(
			document.body,
			'click',
			{props: {id:'imconnector-imessage-link-help-create'}},
			showIMessageHelpdesk
		);
		BX.bindDelegate(
			document.body,
			'click',
			{props: {id:'imconnector-imessage-link-help-connect'}},
			showIMessageHelpdesk
		);
	});
})(window);