;(function (window) {

	window.checkTelegramFirst = function () {
		document.getElementById('webform-small-button-have-bot').disabled = this.value ? false : "disabled";
	};
	window.checkEshopEnabled = function () {
		var checkbox = document.getElementById('imconnector-telegrambot-eshop-enabled');
		var eshopUrlField = document.getElementById('imconnector-telegrambot-eshop-url');
		var eshopCustomUrlField = document.getElementById('imconnector-telegrambot-eshop-custom-url');

		if (checkbox && checkbox.checked)
		{
			if (eshopUrlField)
			{
				eshopUrlField.style.display = 'block';
			}
			window.checkCustomEshopSelected();
		}
		else
		{
			if (eshopUrlField)
			{
				eshopUrlField.style.display = 'none';
			}
			if (eshopCustomUrlField)
			{
				eshopCustomUrlField.style.display = 'none';
			}
		}
	};

	window.checkCustomEshopSelected = function () {
		var eshopList = document.getElementById('imconnector-telegrambot-eshop-list');
		var checkbox = document.getElementById('imconnector-telegrambot-eshop-enabled');
		var eshopCustomUrlField = document.getElementById('imconnector-telegrambot-eshop-custom-url');

		if (eshopList && eshopList.value === '0' && checkbox && checkbox.checked)
		{
			if (eshopCustomUrlField)
			{
				eshopCustomUrlField.style.display = 'block';
			}
		}
		else
		{
			if (eshopCustomUrlField)
			{
				eshopCustomUrlField.style.display = 'none';
			}
		}
	};

	BX.ready(function () {
		window.checkEshopEnabled();
		window.checkCustomEshopSelected();
	});
	BX.ready(function () {
		BX.bindDelegate(
			document.body,
			'click',
			{props: {id: 'imconnector-telegrambot-eshop-enabled'}},
			checkEshopEnabled
		);
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
			{props: {id:'imconnector-telegrambot-link-help'}},
			() => {
				top.BX.Helper.show('redirect=detail&code=6352401');
				return false;
			}
		);
	});
})(window);