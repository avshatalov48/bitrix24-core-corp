;(function ()
{
	'use strict';
	BX.namespace('BX.Salescenter.Connection');

	BX.Salescenter.Connection.init = function(options)
	{
		BX.Salescenter.Manager.init(options);
		var button = document.getElementById('bx-salescenter-connect-button');
		var loader = new BX.Loader({size: 200});
		button.addEventListener('click', function(event)
		{
			loader.show(document.body);
			BX.Salescenter.Manager.connect({
				no_redirect: options.withRedirect ? '' : 'Y',
				context: options.context,
			}).then(function()
			{
				BX.Salescenter.Manager.loadConfig().then(function(result)
				{
					loader.hide();
					if(result.isSiteExists && !options.withRedirect)
					{
						var slider = BX.SidePanel.Instance.getSliderByWindow(window);
						if(slider)
						{
							slider.close();
						}
					}
				});
			}).catch(function()
			{
				loader.hide();
			});
		});
	};
})();