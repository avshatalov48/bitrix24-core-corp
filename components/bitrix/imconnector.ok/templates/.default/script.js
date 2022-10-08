;(function (window) {
	window.checkOkFirst = function () {
		document.getElementById('webform-small-button-have-bot').disabled = this.value || this.dataset.value ? false : "disabled";
	};

	BX.ready(function () {
		BX.bindDelegate(
			document.body,
			'click',
			{className: 'imconnector-field-box-entity-icon-copy-to-clipboard'},
			copyToClipboard
		);
		BX.bindDelegate(
			document.body,
			'keyup',
			{props: {id: 'imconnector-ok-have-bot'}},
			checkOkFirst
		);
		BX.bindDelegate(
			document.body,
			'mouseout',
			{props: {id: 'imconnector-ok-have-bot'}},
			checkOkFirst
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
			{props: {id:'imconnector-ok-link-help'}},
			() => {
				top.BX.Helper.show('redirect=detail&code=11579286');
				return false;
			}
		);
	});
})(window);