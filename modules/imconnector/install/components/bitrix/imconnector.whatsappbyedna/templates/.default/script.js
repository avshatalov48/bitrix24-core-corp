;(function (window) {

	window.popupConfirmSaveShow = function () {
		var popupShowTrue = new BX.PopupWindow('uidsavewhatsappbyedna', null, {
			closeIcon: { right : '5px', top : '5px'},
			titleBar: BX.message('IMCONNECTOR_COMPONENT_WHATSAPBYEDNA_CONFIRM_TITLE'),
			closeByEsc : true,
			autoHide : true,
			content: '<p class=\"imconnector-popup-text\">' + BX.message('IMCONNECTOR_COMPONENT_WHATSAPBYEDNA_CONFIRM_DESCRIPTION') + '</p>',
			overlay: {
				backgroundColor: 'black', opacity: '80'
			},
			buttons: [
				new BX.PopupWindowButton({
					text: BX.message('IMCONNECTOR_COMPONENT_WHATSAPBYEDNA_CONFIRM_BUTTON_OK'),
					className : 'popup-window-button-accept',
					events:{
						click: function(){
							var form = BX('form_save_whatsappbyedna');
							if (form) {
								var hiddenInput = document.createElement('input');
								hiddenInput.type = 'hidden';
								hiddenInput.name = 'whatsappbyedna_save';
								hiddenInput.value = '1';
								form.appendChild(hiddenInput);

								BX.submit(form);
							}
							this.popupWindow.close();
						}
					}
				}),
				new BX.PopupWindowButton({
					text: BX.message('IMCONNECTOR_COMPONENT_WHATSAPBYEDNA_CONFIRM_BUTTON_CANCEL'),
					className : 'popup-window-button-link',
					events:{
						click: function(){this.popupWindow.close()}
					}
				})
			]
		});
		popupShowTrue.show();
	};

	window.checkWhatsAppByEdnaFirst = function () {
		const senderInput = document.getElementById('imconnector-whatsappbyedna-sender-id-0');
		const button = document.getElementById('webform-small-button-have');

		const dataValue = senderInput.getAttribute('data-original-value');

		if (senderInput.value && senderInput.value !== dataValue) {
			button.disabled = false;
		} else {
			button.disabled = true;
		}
	};

	BX.ready(function () {
		BX.bindDelegate(
			document.body,
			'click',
			{className: 'copy-to-clipboard'},
			copyToClipboard
		);
		BX.bindDelegate(
			document.body,
			'keyup',
			{props: {id: 'imconnector-whatsappbyedna-sender-id-0'}},
			checkWhatsAppByEdnaFirst
		);
		BX.bindDelegate(
			document.body,
			'mouseout',
			{props: {id: 'imconnector-whatsappbyedna-sender-id-0'}},
			checkWhatsAppByEdnaFirst
		);
		BX.bindDelegate(
			document.body,
			'keyup',
			{props: {id: 'imconnector-whatsappbyedna-api-key'}},
			checkWhatsAppByEdnaFirst
		);
		BX.bindDelegate(
			document.body,
			'mouseout',
			{props: {id: 'imconnector-whatsappbyedna-api-key'}},
			checkWhatsAppByEdnaFirst
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
			{props: {id:'imconnector-whatsappbyedna-link-help'}},
			() => {
				top.BX.Helper.show('redirect=detail&code=14214014');
				return false;
			})
	});
})(window);