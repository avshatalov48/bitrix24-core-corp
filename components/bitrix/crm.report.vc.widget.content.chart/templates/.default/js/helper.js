;(function ()
{
	'use strict';
	var namespace = BX.namespace('BX.Crm.Analytics.Report.ViewChart');

	/**
	 * Helper.
	 */
	namespace.Helper = {
		context: null,
		getNode: function(role, context)
		{
			context = context || this.context;
			var nodes = this.getNodes(role, context);
			return nodes.length > 0 ? nodes[0] : null;
		},
		getNodes: function(role, context)
		{
			context = context || this.context;
			if (!BX.type.isDomNode(context))
			{
				return [];
			}
			return BX.convert.nodeListToArray(context.querySelectorAll('[data-role="' + role + '"]'));
		}
	};
})();