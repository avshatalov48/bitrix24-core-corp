;(function(window){

	window.backgroundImagesChange = function()
	{
		var backgroundImages = document.getElementsByName('BACKGROUND_IMAGE');
		BX.bind(backgroundImages[0], 'bxchange', function(){
			var parts = [];
			parts = this.value.replace(/\\/g, '/').split( '/' );
			BX('BACKGROUND_IMAGE_TEXT').innerText = parts[parts.length-1];
		});
	};

	window.toggleOpen = function()
	{
		BX.toggleClass(BX('imconnector-livechat-open'), 'imconnector-livechat-public-open');
		if(BX('imconnector-livechat-open-block').value == '')
			BX('imconnector-livechat-open-block').value='Y';
		else
			BX('imconnector-livechat-open-block').value='';
	};

	window.toggleOpenPhrases = function()
	{
		BX.toggleClass(BX('imconnector-livechat-open-phrases'), 'imconnector-livechat-public-open');
		if(BX('imconnector-livechat-open-block-phrases').value == '')
			BX('imconnector-livechat-open-block-phrases').value='Y';
		else
			BX('imconnector-livechat-open-block-phrases').value='';
	};

	BX.ready(function() {
		backgroundImagesChange();
		BX.bind(
			BX('imconnector-livechat-public-link-settings-toggle'),
			'click',
			toggleOpen
		);
		BX.bind(
			BX('imconnector-livechat-phrases-config-toggle'),
			'click',
			toggleOpenPhrases
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