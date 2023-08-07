(function() {

	"use strict";

	/**
	 * @namespace BX.Disk.Document
	 */
	BX.namespace("BX.Disk.Document");

	/**
	 * @param {object} parameters
	 * @constructor
	 */
	BX.Disk.Document.Local = function(parameters)
	{
		this.secondsToWaitDesktop = 17;
		this.fallbacks = {};

		this.disableLocalEdit = false;

		this.bindEvents();
	};

	BX.Disk.Document.Local.prototype =
	{
		bindEvents: function ()
		{
			BX.addCustomEvent('BX.UI.Viewer.Controller:onItemError', this.handleViewerItemError.bind(this));
			BX.addCustomEvent('BX.UI.Viewer.Controller:onAfterProcessItemError', this.handleAfterViewerItemError.bind(this));

			BX.addCustomEvent('onPullEvent-disk', this.handlePullEvent.bind(this));
			BX.addCustomEvent('onPullClientEvent-disk', this.handlePullEvent.bind(this));
		},

		/**
		 * @param {BX.UI.Viewer.Controller} viewer
		 * @param {Object} reason
		 * @param {BX.UI.Viewer.Item} item
		 */
		handleAfterViewerItemError: function (viewer, reason, item)
		{
			if (reason.idViewFileLink)
			{
				BX.bind(BX(reason.idViewFileLink), 'click', function(event){
					event.preventDefault();

					this.viewFile({
						url: item.src,
						name: item.getTitle()
					});

				}.bind(this));
			}
		},

		/**
		 * @param {BX.UI.Viewer.Controller} viewer
		 * @param {Object} reason
		 * @param {BX.UI.Viewer.Item} item
		 */
		handleViewerItemError: function (viewer, reason, item)
		{
			if (!this.isSetWorkWithLocalBDisk())
			{
				return;
			}

			var id = 'runLocal' + (Math.floor(Math.random() * 10000) + 1);
			reason.message = BX.message('JS_DISK_DOC_TRY_TO_OPEN_BY_LOCAL_PROGRAM_TITLE').replace('#ID#', id);
			reason.description = BX.message('JS_DISK_DOC_TRY_TO_OPEN_BY_LOCAL_PROGRAM_OR_DOWNLOAD').replace('#DOWNLOAD_LINK#', item.getSrc());
			reason.idViewFileLink = id;
		},

		handlePullEvent: function (command, params)
		{
			if (command === 'bdisk' && params.uidRequest)
			{
				this.stopWaiting(params.uidRequest);
			}
		},

		isSetWorkWithLocalBDisk: function ()
		{
			return (BX.Disk.getDocumentService() === 'l') && this.isEnabled();
		},

		disable: function ()
		{
			this.disableLocalEdit = true;
		},

		isEnabled: function ()
		{
			if (this.disableLocalEdit)
			{
				return false;
			}

			return true;
		},

		editFile: function (params)
		{
			params = this.prepareParametersToWorkWithFile(params);

			this.goToBx('v2openFile', {
				objectId: params.objectId,
				url: params.url,
				name: params.name
			});
		},

		createFile: function (params)
		{
			var promise = new BX.Promise();

			var url = '/bitrix/tools/disk/document.php';
			url = BX.util.add_url_param(url, {
				service: 'l',
				type: params.type,
				document_action: 'publishBlank',
				primaryAction: 'publishBlank',
				action: ''
			});

			BX.Disk.ajaxPromise({
				method: 'POST',
				dataType: 'json',
				url: url,
				data: {
					targetFolderId: params.targetFolderId || ''
				}
			}).then(function (response) {
				if (!response || response.status !== 'success')
				{
					promise.reject({
						status: 'error'
					});

					return;
				}

				promise.fulfill(response);

				this.editFile({
					objectId: response.object.id,
					url: response.link,
					name: response.object.name
				});

			}.bind(this));

			return promise;
		},

		viewFile: function (params)
		{
			params = this.prepareParametersToWorkWithFile(params);

			this.goToBx('v2viewFile', {
				objectId: params.objectId,
				url: params.url,
				name: params.name
			});
		},

		prepareParametersToWorkWithFile: function (params)
		{
			if (!params.objectId && !params.attachedObjectId && !params.url)
			{
				return;
			}

			params.name = params.name || 'Unknown document';
			params.objectId = params.objectId || 0;
			params.attachedObjectId = params.attachedObjectId || 0;

			var url = params.url;
			if (!url)
			{
				url = params.objectId? '/bitrix/tools/disk/document.php' : '/bitrix/tools/disk/uf.php';
				url = BX.util.add_url_param(url, {
					objectId: params.objectId,
					attachedId: params.attachedObjectId,
					service: 'l',
					document_action: 'download',
					primaryAction: 'download',
					action: ''
				});
			}

			if (url.substring(0,1) === '/')
			{
				url = (window.location.origin || (window.location.protocol + "//" + window.location.hostname + (window.location.port ? ':' + window.location.port: ''))) + url;
			}

			params.url = url;

			return params;
		},

		goToBx: function (action, params)
		{
			var link = 'bx://' + action;
			params = BX.type.isPlainObject(params)? params : {};
			params.uidRequest = BX.util.getRandomString(16);

			if (BX.type.isPlainObject(params))
			{
				for (var name in params)
				{
					if (!params.hasOwnProperty(name))
					{
						continue;
					}

					link += '/' + name + '/' + encodeURIComponent(params[name]);
				}
			}

			if (typeof (BXFileStorage) == 'undefined')
			{
				top.BX.desktopUtils.runningCheck(function() {
					top.BX.desktopUtils.goToBx(link);
				}, function() {
					this.registerFallback(params.uidRequest);

					top.BX.desktopUtils.goToBx(link);
				}.bind(this));
			}
			else
			{
				top.BX.desktopUtils.goToBx(link);
			}

		},

		registerFallback: function(uidRequest)
		{
			top.BX.loadExt('disk').then(function () {

				top.BX.Disk.showLoader({
					text: BX.message('JS_DISK_DOC_WAITING_FOR_BITRIX24_DESKTOP')
				});

				var timeoutId = setTimeout(function(){
					top.BX.Disk.hideLoader();

					var popup = new BX.PopupWindow('b24-desktop', null, {
						titleBar: BX.message('JS_DISK_DOC_WAITING_FOR_BITRIX24_DESKTOP_TITLE'),
						content: BX.message('JS_DISK_DOC_WAITING_FOR_BITRIX24_DESKTOP_DESCR'),
						closeIcon: true,
						closeByEsc: true,
						width: 500,
						overlay: {
							backgroundColor: 'rgba(0,0,0,0.5)'
						},
						buttons: [
							new BX.PopupWindowCustomButton({
								text: BX.message('JS_DISK_DOC_WAITING_FOR_BITRIX24_DESKTOP_DOWNLOAD'),
								className: 'ui-btn ui-btn-primary',
								events: {
									click: function (e) {
										document.location.href = (BX.browser.IsMac() ? "https://dl.bitrix24.com/b24/bitrix24_desktop.dmg" : "https://dl.bitrix24.com/b24/bitrix24_desktop.exe");
									}
								}
							}),
							new BX.PopupWindowCustomButton({
								text: BX.message('JS_DISK_DOC_WAITING_FOR_BITRIX24_DESKTOP_HELP'),
								className: 'ui-btn ui-btn-link',
								events: {
									click: function (e) {
										if(top.BX.Helper)
											top.BX.Helper.show("redirect=detail&code=8626407");
										popup.destroy();
									}
								}
							})
						]
					});

					popup.show();
				}.bind(this), this.secondsToWaitDesktop * 1000);

				this.fallbacks[uidRequest] = {
					timeoutId: timeoutId
				};
			}.bind(this));

		},

		stopWaiting: function (uidRequest)
		{
			if (BX.getClass('top.BX.Disk'))
			{
				top.BX.Disk.hideLoader();
			}
			if (this.fallbacks.hasOwnProperty(uidRequest))
			{
				clearTimeout(this.fallbacks[uidRequest].timeoutId);
				delete this.fallbacks[uidRequest];
			}
		}
	};

	var instance = null;
	/**
	 * @memberOf BX.Disk.Document.Local
	 * @name BX.Disk.Document.Local#Instance
	 * @type BX.Disk.Document.Local
	 * @static
	 * @readonly
	 */
	Object.defineProperty(BX.Disk.Document.Local, 'Instance', {
		enumerable: false,
		get: function()
		{
			if (instance === null)
			{
				instance = new BX.Disk.Document.Local({});
			}

			return instance;
		}
	});

	BX.Disk.Document.Local.Instance.isEnabled();
})();
