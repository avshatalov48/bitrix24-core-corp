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

		BX.addCustomEvent(
			'SidePanel.Slider:onMessage',
			function (event)
			{
				if (event.getEventId() === "ImConnector:vk.reload")
				{
					addPreloader();
					location.reload();
				}

			}
		);
		BX.bindDelegate(
			document.body,
			'click',
			{props: {id:'imconnector-vkgroup-link-help'}},
			() => {
				top.BX.Helper.show('redirect=detail&code=8288267');
				return false;
			}
		);
	});
})(window);