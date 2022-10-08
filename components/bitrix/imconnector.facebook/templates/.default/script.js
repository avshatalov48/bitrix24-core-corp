;(function(window){
	window.toggleHideList = function(event)
	{
		var hiddenList = document.getElementById('hidden-list');

		hiddenList.style.display = (hiddenList.style.display !== 'block') ? 'block' : 'none';
		return false;
	};

	BX.ready(function(){
		var channelSettingsBlock = BX('bx-connector-user-list')
		var urlSearchParams = (new URL(document.location)).searchParams;
		var hideChannelsBlock = false;
		if (urlSearchParams.get('wrong_user'))
		{
			urlSearchParams.delete('wrong_user');
			hideChannelsBlock = true;
		}
		if (urlSearchParams.get('MENU_TAB') === 'catalog')
		{
			hideChannelsBlock = true;
		}
		if (hideChannelsBlock && channelSettingsBlock)
		{
			channelSettingsBlock.style.display = 'none';
		}

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
		BX.bindDelegate(
			document.body,
			'click',
			{className: 'ui-sidepanel-menu-link'},
			BX.FacebookConnector.reloadHandler.bind(window.BX.FacebookConnector)
		);
	});
	window.BX.FacebookConnector = {
		reloadHandler: function(e) {
			e.preventDefault();
			var href = e.target.href || e.target.parentElement.href;
			if (!href)
			{
				return;
			}
			var page = this.getUrlParam(href, 'MENU_TAB');
			this.visualReload(page);
		},
		visualReload: function(page) {
			var pages = document.querySelectorAll('[data-fb-connector-page]');
			for (var i = 0; i < pages.length; i++)
			{
				if (pages[i].dataset.fbConnectorPage === page)
				{
					BX.animationHandler.smoothShow(pages[i]);
				}
				else
				{
					BX.animationHandler.smoothHide(pages[i]);
				}
			}
		},
		getUrlParam: function(url, paramName) {
			var objUrl = new URL(url);
			var page = objUrl.searchParams.get(paramName);

			return BX.util.htmlspecialchars(page);
		},
	}

	window.BX.animationHandler = {
		smoothShow: function (node)
		{
			var channelSettingsBlock = BX('bx-connector-user-list')
			if (channelSettingsBlock)
			{
				if (node.dataset.fbConnectorPage === 'catalog')
				{
					channelSettingsBlock.style.display = 'none';
				}
				else
				{
					channelSettingsBlock.style.display = 'block';
				}
			}

			BX.removeClass(node, 'imconnector-page-hide');
			BX.removeClass(node, 'imconnector-hidden-page');
			BX.addClass(node, 'imconnector-page-show');
		},
		smoothHide: function (node)
		{
			BX.addClass(node, 'imconnector-hidden-page');
			BX.removeClass(node, 'imconnector-page-show');
			BX.addClass(node, 'imconnector-page-hide');
		},
	};
})(window);