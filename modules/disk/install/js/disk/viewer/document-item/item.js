(function() {

	"use strict";

	/**
	 * @namespace BX.Disk.Viewer
	 */
	BX.namespace("BX.Disk.Viewer");


	/**
	 * @extends {BX.UI.Viewer.Item}
	 * @param options
	 * @constructor
	 */
	BX.Disk.Viewer.DocumentItem = function (options)
	{
		options = options || {};

		BX.UI.Viewer.Item.apply(this, arguments);
		this.contentNode = null;
		this.iframeNode = null;
		this.justErrorInGoogle = false;
		this.viewUrl = options.viewUrl;
		this.neededCheckView = options.neededCheckView;
		this.neededDelete = options.neededDelete;
		this.objectId = options.objectId;
		this.attachedObjectId = options.attachedObjectId;
	};

	BX.Disk.Viewer.DocumentItem.prototype =
	{
		__proto__: BX.UI.Viewer.Item.prototype,
		constructor: BX.UI.Viewer.Item,

		/**
		 * @param {HTMLElement} node
		 */
		setPropertiesByNode: function (node)
		{
			BX.UI.Viewer.Item.prototype.setPropertiesByNode.apply(this, arguments);
			this.objectId = node.dataset.objectId;
			this.attachedObjectId = node.dataset.attachedObjectId;
		},

		buildUrlToShow: function ()
		{
			return this.buildUrlToAction('show');
		},

		buildUrlToAction: function (action)
		{
			var url = '/bitrix/tools/disk/document.php';

			if (this.attachedObjectId)
			{
				url = '/bitrix/tools/disk/uf.php';
				url = BX.util.add_url_param(url, {'attachedId': this.attachedObjectId});
			}
			else
			{
				url = BX.util.add_url_param(url, {'objectId': this.objectId});
			}

			url = BX.util.add_url_param(url, {
				document_action: action,
				primaryAction: action,
				service: null
			});

			return url;
		},

		loadData: function ()
		{
			var promise = new BX.Promise();

			if (this.justErrorInGoogle)
			{
				promise.reject({
					item: this,
					type: 'error'
				});

				return promise;
			}

			BX.ajax.promise({
				url: this.buildUrlToShow(),
				method: 'POST',
				dataType: 'json',
				data: {
					SITE_ID: BX.message('SITE_ID'),
					sessid: BX.bitrix_sessid()
				}
			}).then(function (response) {
				if (response && response.authUrlOpenerMode)
				{
					this.runAuth = response;
				}
				else
				{
					if (!response || response.status !== 'success')
					{
						promise.reject({
							item: this,
							type: 'error'
						});

						return;
					}

					this.viewId = response.id;
					this.viewUrl = response.viewUrl;
					this.neededCheckView = response.neededCheckView;
					this.neededDelete = response.neededDelete;
					this.service = response.service;
				}

				promise.fulfill(this);

			}.bind(this));

			return promise;
		},

		buildNodeWithAuthMessage: function ()
		{
			return BX.create('div', {
				props: {
					className: 'ui-viewer-error'
				},
				children: [
					BX.create('div', {
						props: {
							className: 'ui-viewer-info-title'
						},
						html: BX.message('JS_VIEWER_DOCUMENT_ITEM_SHOW_FILE_DIALOG_OAUTH_NOTICE').replace('#SERVICE#', this.runAuth.serviceName)
					})
				]
			});
		},

		renderStubForOfiice365: function ()
		{
			return BX.create('div', {
				props: {
					className: 'ui-viewer-unsupported'
				},
				children: [
					BX.create('div', {
						props: {
							className: 'ui-viewer-unsupported-title'
						},
						text: BX.message('JS_VIEWER_DOCUMENT_ITEM_OPEN_DESCR_OFFICE365')
					}),
					BX.create('div', {
						props: {
							className: 'ui-viewer-unsupported-text disk-viewer-office365-text'
						},
						html: BX.message('JS_VIEWER_DOCUMENT_ITEM_OPEN_HELP_HINT_OFFICE365')
					}),
					BX.create('a', {
						props: {
							className: 'ui-btn ui-btn-light-border ui-btn-themes',
							href: this.viewUrl,
							target: '_blank'
						},
						text: BX.message('JS_VIEWER_DOCUMENT_ITEM_OPEN_FILE_OFFICE365')
					})
				]
			});
		},

		render: function ()
		{
			var item = document.createDocumentFragment();

			if (this.runAuth)
			{
				item.appendChild(this.buildNodeWithAuthMessage());
			}
			else
			{
				this.iframeNode = BX.create('iframe', {
					props: {
						src: this.viewUrl
					},
					events: {
						load: this.handleOnLoadIframe.bind(this)
					},
					style: {
						maxWidth: '1024px',
						width: '100%',
						height: '100%',
						border: 'none'
					}
				});

				item.appendChild(this.iframeNode);
				this.register204Checker();
			}

			return item;
		},

		/**
		 * @returns {BX.Promise}
		 */
		getContentWidth: function()
		{
			var promise = new BX.Promise();

			promise.fulfill(this.iframeNode.offsetWidth);

			return promise;
		},

		register204Checker: function ()
		{
			setTimeout(function () {
				if (this.iframeNode && this.iframeNode.contentDocument && this.iframeNode.contentDocument.URL === 'about:blank')
				{
					this.controller.reload(this, {
						justErrorInGoogle: true
					});
				}
			}.bind(this), 12000)
		},

		handleOnLoadIframe: function ()
		{
			if (!BX.browser.IsChrome() || !this.neededCheckView)
			{
				return;
			}

			BX.ajax.promise({
				url: this.buildUrlToAction('checkView'),
				method: 'POST',
				dataType: 'json',
				data: {
					id: this.viewId,
					SITE_ID: BX.message('SITE_ID'),
					sessid: BX.bitrix_sessid()
				}
			}).then(function (response) {
				if (!response || (response.viewed === undefined && !response.viewByGoogle || response.viewByGoogle === undefined && !response.viewed))
				{
					this.controller.reload(this, {
						justErrorInGoogle: true
					});
				}
			}.bind(this));
		},

		applyReloadOptions: function (options)
		{
			if (options.justErrorInGoogle)
			{
				this.justErrorInGoogle = true;
			}
		},

		afterRender: function()
		{
			if (this.runAuth)
			{
				BX.bind(BX('bx-js-disk-run-oauth-modal'), 'click', function (e) {
					BX.util.popup(this.runAuth.authUrlOpenerMode, 1030, 700);

					e.preventDefault();
				}.bind(this));

				BX.bind(window, 'hashchange', BX.proxy(function () {
					var matches = document.location.hash.match(/external-auth-(\w+)/);
					if (!matches)
					{
						return;
					}

					this.controller.reload(this);
				}, this));
			}
			else if(this.iframeNode)
			{
				this.controller.showLoading();
			}
		}
	};

})();
