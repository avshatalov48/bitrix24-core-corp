;(function(window){

	window.avatarImagesChange = function()
	{
		var avatarImages = document.getElementsByName('avatar');
		BX.bind(avatarImages[0], 'bxchange', function(){
			var parts = [];
			parts = this.value.replace(/\\/g, '/').split( '/' );
			BX('avatar_text').innerText = parts[parts.length-1];
		});
	};

	window.toggleMultidialogCount = function()
	{
		if (BX('imconnector-network-multidialog').checked == true)
		{
			BX('imconnector-network-multidialog-count').classList.remove("invisible");
		}
		else
		{
			BX('imconnector-network-multidialog-count').classList.add("invisible");
		}
	};

	BX.ready(function() {
		avatarImagesChange();
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
		BX.bind(
			BX('imconnector-network-multidialog'),
			'change',
			toggleMultidialogCount
		);
	});
})(window);