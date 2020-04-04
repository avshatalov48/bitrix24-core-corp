;(function () {

	var namespace = BX.namespace("BX.Crm");

	if (namespace.TrackingEntityDetails)
	{
		return;
	}

	BX.Crm.TrackingEntityDetails = {
		init: function (options)
		{
			this.userOptionName = options.userOptionName;
			this.userOptionKeyName = options.userOptionKeyName;
			this.context = BX(options.containerId);
			if (!this.context)
			{
				return;
			}

			this.getNodes('tracking/banner/btn/hide').forEach(function (node) {
				BX.bind(node, 'click', this.close.bind(this));
			}, this);
			BX.bind(this.getNodes('tracking/banner/btn/setup')[0], 'click', this.setup.bind(this));

			this.context.style.display = '';
		},
		close: function ()
		{
			this.context.style.display = 'none';
			BX.userOptions.save('crm', this.userOptionName, this.userOptionKeyName, 'Y');
		},
		setup: function ()
		{
			BX.SidePanel.Instance.open('/crm/tracking/', {width: 800, cacheable: false});
		},
		getNodes: function (role)
		{
			var list = this.context.querySelectorAll('[data-role="' + role + '"]');
			return BX.convert.nodeListToArray(list);
		}
	};


})();
