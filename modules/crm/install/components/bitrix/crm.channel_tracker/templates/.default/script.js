;(function()
{
	"use strict";
	BX.namespace("BX.Crm");

	BX.Crm.Start = function (options)
	{
		this.renderArea = options.renderArea;
		this.url = options.url;
		this.params = options.params;
		this.loadingTimeout = options.loadingTimeout;
		this.layout = {
			lazyLoadPresetContainer: null
		};
		this.init();
	};

	BX.Crm.Start.prototype = {
		getLazyLoadPresetContainer: function()
		{
			if (this.layout.lazyLoadPresetContainer)
			{
				return this.layout.lazyLoadPresetContainer;
			}

			this.layout.lazyLoadPresetContainer = BX.create('div', {
				attrs: {
					className: 'crm-start-lazy-load-preset'
				},
				children: [
					BX.create('img', {
						props: {
							height: '100',
							width: '100',
							src: '/bitrix/components/bitrix/crm.channel_tracker/templates/.default/images/loader.svg'
						}
					})
				]
			});
			return this.layout.lazyLoadPresetContainer;
		},
		init: function ()
		{
			this.renderArea.appendChild(this.getLazyLoadPresetContainer());
			if (this.loadingTimeout > 0)
			{
				setTimeout(this.loadCrmWidgets.bind(this), this.loadingTimeout);
			}
			else
			{
				this.loadCrmWidgets();
			}

		},
		loadCrmWidgets: function ()
		{

			var config = {};
			config.method = 'POST';
			config.onsuccess = BX.delegate(function(data)
			{
				BX.cleanNode(this.renderArea);
				BX.html(this.renderArea, data);
			}, this);
			config.url = this.url;
			config.data = this.params || {};
			config.data['sessid'] = BX.bitrix_sessid();
			return BX.ajax(config);
		}
	}
})();