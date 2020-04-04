;(function(window){

	window.checkWechatFirst = function() {
		var appId = document.getElementById('imconnector-wechat-app-id'),
			appSecret = document.getElementById('imconnector-wechat-app-secret');
		document.getElementById('webform-small-button-have-chat').disabled = appId.value && appSecret.value ? false : "disabled";
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
			'keyup',
			{className: 'imconnector-input-data'},
			checkWechatFirst
		);
		BX.bindDelegate(
			document.body,
			'mouseout',
			{className: 'imconnector-input-data'},
			checkWechatFirst
		);
		BX.bindDelegate(
			document.body,
			'click',
			{className: 'copy-to-clipboard'},
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
	});
})(window);