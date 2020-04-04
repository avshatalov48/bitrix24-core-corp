;(function ()
{
	'use strict';
	var namespace = BX.namespace('BX.Crm.Analytics.Report.ViewChart');
	var list = [];

	/**
	 * Source.
	 * @param options
	 * @constructor
	 */
	function Source(options)
	{
		this.init(options)
	}
	Source.create = function (options)
	{
		var filtered = list.filter(function (instance) {
			return instance.data.code === options.data.code;
		});
		if (filtered.length === 0)
		{
			return new namespace.Source(options);
		}

		return filtered[0];
	};
	Source.prototype.init = function (options)
	{
		this.data = options.data;
	};

	namespace.Source = Source;
})();