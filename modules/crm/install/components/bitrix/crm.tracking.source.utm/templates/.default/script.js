;(function ()
{
	'use strict';

	var namespace = BX.namespace('BX.Crm.Analytics.Utm');
	if (namespace.Editor)
	{
		return;
	}

	/**
	 * Editor.
	 *
	 */
	function Editor()
	{
		this.context = null;
		this.editor = null;
	}
	Editor.prototype.init = function (params)
	{
		this.context = BX(params.containerId);
		this.isSaved = params.isSaved || false;
		this.mess = params.mess || {};

	};

	namespace.Editor = new Editor();
})();