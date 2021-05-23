;(function(window){

	window.toggleOpen = function()
	{
		BX.toggleClass(BX('imconnector-botframework-open'), 'imconnector-botframework-public-open');
		if(BX('imconnector-botframework-open-block').value == '')
			BX('imconnector-botframework-open-block').value='Y';
		else
			BX('imconnector-botframework-open-block').value='';
	};

	BX.ready(function() {
		BX.bind(
			BX('imconnector-botframework-public-link-settings-toggle'),
			'click',
			toggleOpen
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