;(function ()
{
	'use strict';

	var namespace = BX.namespace('BX.Crm.Tracking');
	if (namespace.B24Site)
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
	}
	Editor.prototype.init = function (params)
	{
		this.context = BX(params.containerId);
		this.sources = params.sources || [];
		this.sites = params.sites || [];
		this.mess = params.mess || {};

		BX.convert.nodeListToArray(this.context.querySelectorAll('[data-site-id]'))
			.forEach(function (node) {
				BX.bind(node, 'click', this.openSite.bind(this, node));
			}, this);

		BX.bind(window, 'beforeunload', this.onWindowClose.bind(this));
		if (BX.SidePanel.Instance)
		{
			BX.addCustomEvent("SidePanel.Slider:onClose", this.onWindowClose.bind(this));
		}
	};
	Editor.prototype.editSite = function (url)
	{
		if (this.editingWindow)
		{
			this.editingWindow.close();
		}

		var a = document.createElement('a');
		a.href = url;
		var addressee = a.protocol + '//' + a.hostname + (a.port ? ':' + a.port : '');

		this.editingWindow = window.open(url + '?utm_source=&b24_tracker_edit_enabled=y', 'editingWindow');
		this.connector = new window.b24Tracker.Connector({
			addressee: addressee,
			responders: {
				'tracking.editor.getData': this.getData.bind(this)
			}
		});
	};
	Editor.prototype.getData = function ()
	{
		return {
			enabled: true,
			sources: this.sources,
			sites: [{
				host: this.sites.map(function (site) {
					return site.DOMAIN_NAME;
				}),
				replaceText: false,
				enrichText: false,
				resolveDup: false,
				replacement: 'all'
			}]
		};
	};
	Editor.prototype.openSite = function (node)
	{
		var siteId = node.getAttribute('data-site-id');
		if (!siteId)
		{
			return;
		}

		var site = this.sites.filter(function (site) {
			return site.ID === siteId;
		})[0];

		if (!site)
		{
			return;
		}

		this.editSite(site.DOMAIN_PROTOCOL + '://' + site.DOMAIN_NAME);
	};
	Editor.prototype.onWindowClose = function ()
	{
		if (this.editingWindow)
		{
			this.editingWindow.close();
		}
		this.editingWindow = null;
	};

	namespace.B24Site = new Editor();
})();