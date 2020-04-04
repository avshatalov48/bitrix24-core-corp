;(function (window) {

	window.checkWhatsAppByTwilioFirst = function () {
		document.getElementById('webform-small-button-have').disabled = this.value ? false : "disabled";
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
			{props: {id: 'imconnector-whatsappbytwilio-sid'}},
			checkWhatsAppByTwilioFirst
		);
		BX.bindDelegate(
			document.body,
			'mouseout',
			{props: {id: 'imconnector-whatsappbytwilio-sid'}},
			checkWhatsAppByTwilioFirst
		);
		BX.bindDelegate(
			document.body,
			'keyup',
			{props: {id: 'imconnector-whatsappbytwilio-auth-token'}},
			checkWhatsAppByTwilioFirst
		);
		BX.bindDelegate(
			document.body,
			'mouseout',
			{props: {id: 'imconnector-whatsappbytwilio-auth-token'}},
			checkWhatsAppByTwilioFirst
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