;(function(window){
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