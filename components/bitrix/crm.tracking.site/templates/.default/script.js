;(function ()
{
	'use strict';

	var namespace = BX.namespace('BX.Crm.Tracking');
	if (namespace.Site)
	{
		return;
	}

	/**
	 * Site checking.
	 *
	 */
	function Checking()
	{
		BX.bind(window, 'message', this.onMessage.bind(this));
		BX.bind(window, 'beforeunload', this.onWindowClose.bind(this));
		BX.bind(window, 'blur', this.onWindowBlur.bind(this));
		if (BX.SidePanel && BX.SidePanel.Instance)
		{
			BX.addCustomEvent("SidePanel.Slider:onClose", this.onWindowClose.bind(this));
		}

		this.timeout = 20;
		this.debug = false;
	}
	Checking.prototype.parameterName = 'b24_tracker_checking_origin';
	Checking.prototype.editParameterName = 'b24_tracker_edit_enabled';
	Checking.prototype.running = false;
	Checking.prototype.setDebug = function (mode, timeout)
	{
		this.debug = !!mode;
		if (this.debug)
		{
			this.timeout = timeout || 1000;
		}
	};
	Checking.prototype.log = function (mess)
	{
		if (this.debug && window.console && 'log' in console)
		{
			console.log('BX.Crm.Tracking.Site[checking]:', mess);
		}
	};
	Checking.prototype.edit = function (url)
	{
		if (this.editingWindow)
		{
			this.editingWindow.close();
		}

		var a = document.createElement('a');
		a.href = url;
		var host = a.hostname;
		var addressee = a.protocol + '//' + a.hostname + (a.port ? ':' + a.port : '');
		this.editingWindow = window.open(url + '?utm_source=&' + this.editParameterName + '=' + 'y', 'editingWindow');
		this.connector = new window.b24Tracker.Connector({
			addressee: addressee,
			responders: {
				'tracking.editor.getData': function ()
				{
					return namespace.Site.getData(host);
				}.bind(this)
			}
		});
	};
	Checking.prototype.isRunning = function ()
	{
		return this.running;
	};
	Checking.prototype.start = function (options)
	{
		if (this.running)
		{
			this.end();
		}

		var a = document.createElement('a');
		a.href = options.url;
		this.domain = a.href;

		this.bar = options.bar;
		var url = options.url + '?utm_source=&' + this.parameterName + '=' + encodeURIComponent(window.location.origin);
		if (this.debug)
		{
			url += '&bx_debug=y';
		}
		this.checkingWindow = window.open(url, 'checkingWindow', 'height=300,width=450');
		this.log('Open window ' + url);

		this.promise = new BX.Promise();
		this.timer = setTimeout(this.onError.bind(this), this.timeout * 1000);
		if (!this.debug)
		{
			this.updateBar();
		}
		// postMessage
		// resolve
		// clearTimeout(this.timer)

		this.running = true;
		return this.promise;
	};
	Checking.prototype.updateBar = function (n)
	{
		if (!this.timer)
		{
			return;
		}

		n = n || 0;

		this.bar.update(n * 10 / this.timeout);
		setTimeout(this.updateBar.bind(this, n + 1), 100);
	};
	Checking.prototype.onError = function ()
	{
		if (this.promise)
		{
			this.promise.cancelAutoResolve();
			this.promise.reject();
		}

		this.end();
	};
	Checking.prototype.end = function ()
	{
		if (this.timer)
		{
			clearTimeout(this.timer);
		}

		this.log('End. Close window');
		if (this.checkingWindow && !this.debug)
		{
			this.checkingWindow.close();
		}

		this.checkingWindow = null;
		this.timer = null;
		this.domain = null;
		this.promise = null;

		this.running = false;
	};
	Checking.prototype.onWindowClose = function ()
	{
		this.end();
		if (this.editingWindow)
		{
			this.editingWindow.close();
		}
		this.editingWindow = null;
	};
	Checking.prototype.onWindowBlur = function ()
	{
		if (!this.editingWindow)
		{
			return;
		}

		this.connector.request(this.editingWindow, 'b24.portal.refresh');
	};
	Checking.prototype.onMessage = function (event)
	{
		if (!this.promise)
		{
			return;
		}
		if (!this.domain || this.domain.indexOf(event.origin) !== 0)
		{
			this.log('Domains not equal `' + this.domain + '` & `' + event.origin + '`');
			return;
		}

		if (!this.checkingWindow || this.checkingWindow !== event.source)
		{
			this.log('Checking window not equals for event.source');
			return;
		}

		this.log('Data received ' + event.data);

		var data;
		try
		{
			data = JSON.parse(event.data || '');
			this.log(data);
		}
		catch (e)
		{
			this.log('Data parse error ' + e.message);
			return;
		}

		if (data.source !== 'b24Tracker')
		{
			this.log('Wrong data source ' + data.source);
			return;
		}

		this.promise.fulfill(data);
		this.end();
	};

	/**
	 * Site checking result.
	 *
	 */
	function Result()
	{
		this.context = null;
		this.editor = null;
	}
	Result.prototype.init = function (params)
	{

	};

	/**
	 * Editor.
	 *
	 */
	function Editor()
	{
		this.context = null;
		this.editor = null;
		this.checkTimeout = 5;
	}
	Editor.prototype.init = function (params)
	{
		this.context = BX(params.containerId);
		this.isInstalled = params.isInstalled || false;
		this.isSaved = params.isSaved || false;
		this.sources = params.sources || [];
		this.mess = params.mess || {};

		this.resultContainer = BX('result-container');

		this.siteDomain = BX('ADDRESS');
		this.btnCheck = BX('site-btn-check');
		BX.bind(this.btnCheck, 'click', this.checkSite.bind(this));
		this.btnConnect = BX('site-btn-connect');
		BX.bind(this.btnConnect, 'click', this.checkSite.bind(this));
		this.btnDisconnect = BX('site-btn-disconnect');
		BX.bind(this.btnDisconnect, 'click', this.disconnect.bind(this));
		this.btnEdit = BX('site-btn-edit');
		BX.bind(this.btnEdit, 'click', this.editSite.bind(this));

		this.scriptBlockBtn = BX('crm-tracking-site-code-header-title');
		this.scriptBlock = BX('crm-tracking-site-hidden');
		BX.bind(this.scriptBlockBtn, 'click', this.showScriptText.bind(this));
		BX.clipboard.bindCopyClick(BX('script-copy-btn'), {text: BX('script-text')});

		this.status = BX('site-status');
		this.statusBar = BX('site-status-bar');
		this.statusBarController = new BX.UI.ProgressBar({
			maxValue: 100,
			value: 0,
			statusType: BX.UI.ProgressBar.Status.PERCENT
		});
		this.statusBar.appendChild(this.statusBarController.getContainer());

		this.checking = new Checking();
		this.selectorPhone = BX.UI.TileSelector.getById('available-phone-list');
		this.selectorEmail = BX.UI.TileSelector.getById('available-email-list');

		this.additionalBlock = BX('crm-analytics-channel-addition-options');
		BX.bind(BX('crm-analytics-channel-addition-btn'), 'click', BX.proxy(function(){
			BX.toggleClass(this.additionalBlock, 'crm-analytics-channel-addition-options-open');
		}, this));

		if (this.isInstalled)
		{
			this.connect();
		}

		var btnPhoneAdd = BX('crm-tracking-phone-add');
		var btnEmailAdd = BX('crm-tracking-email-add');
		BX.bind(btnPhoneAdd, 'click', this.showAddItemDialog.bind(this, true, btnPhoneAdd));
		BX.bind(btnEmailAdd, 'click', this.showAddItemDialog.bind(this, false, btnEmailAdd));

		var buttonRemove = BX('crm-tracking-site-remove');
		if (buttonRemove)
		{
			BX.bind(buttonRemove, 'click', function () {
				BX.addClass(buttonRemove, 'ui-btn-wait');
				BX('crm-tracking-site-remove-input').value = 'Y';
				BX.submit(BX('crm-tracking-site-form'));
			});
		}
	};
	Editor.prototype.checkBrowser = function ()
	{
		if (!BX.browser.IsIE11() && !BX.browser.IsIE10())
		{
			return true;
		}

		alert(this.mess.oldBrowser);
		return false;
	};
	Editor.prototype.editSite = function ()
	{
		if (!this.checkBrowser())
		{
			return;
		}

		var url = this.siteDomain.value;
		if (!url)
		{
			return;
		}

		this.checking.edit(url);
	};
	Editor.prototype.connect = function ()
	{
		BX('DISCONNECTED').style.display = 'none';
		BX('CONNECTED').style.display = '';

		BX('IS_INSTALLED').value = 'Y';

		var a = document.createElement('a');
		a.href = this.siteDomain.value;
		var address = a.protocol + '//' + a.hostname;
		var input = BX('SITE_NAME');
		input.textContent = address;
		input.href = address;
		input.target = '_blank';

		this.showStatus(true);
	};
	Editor.prototype.disconnect = function ()
	{
		BX('CONNECTED').style.display = 'none';
		BX('DISCONNECTED').style.display = '';

		BX('IS_INSTALLED').value = 'N';

		this.showStatus();
		this.selectorPhone.removeTiles();
		this.selectorEmail.removeTiles();
	};
	Editor.prototype.checkSite = function ()
	{
		if (!this.checkBrowser())
		{
			return;
		}

		var url = this.siteDomain.value;
		if (!url || this.checking.isRunning())
		{
			return;
		}

		this.showStatus(null);

		this.checking.start({
			url: url,
			bar: this.statusBarController
		}).then(
			this.onFound.bind(this),
			this.showStatus.bind(this, false)
		);
	};
	Editor.prototype.getData = function (host)
	{
		return {
			enabled: true,
			sources: this.sources,
			sites: [{
				host: host,
				replaceText: BX('REPLACE_TEXT').checked,
				enrichText: BX('ENRICH_TEXT').checked,
				resolveDup: BX('RESOLVE_DUPLICATES').checked,
				replacement: [].concat(
					this.selectorPhone.getTiles().map(function (tile) {
						return {type: 'phone', value: tile.id};
					}),
					this.selectorEmail.getTiles().map(function (tile) {
						return {type: 'email', value: tile.id};
					})
				)
			}]
		};
	};
	Editor.prototype.onFound = function (result)
	{
		var hasPhones = this.selectorPhone.getTiles().length > 0;
		var hasEmails = this.selectorEmail.getTiles().length > 0;

		if (!hasPhones)
		{
			this.selectorPhone.removeTiles();
		}
		if (!hasEmails)
		{
			this.selectorEmail.removeTiles();
		}
		result.items.forEach(function (item) {
			switch (item.type)
			{
				case 'phone':
					if (!hasPhones)
					{
						this.selectorPhone.addTile(item.value, {}, item.value);
					}
					break;

				case 'email':
					if (!hasEmails)
					{
						this.selectorEmail.addTile(item.value, {}, item.value);
					}
					break;
			}
		}, this);

		this.connect();
	};
	Editor.prototype.showScriptText = function (showAlways)
	{
		var className = 'crm-tracking-site-hidden-closed';
		if (showAlways === true)
		{
			this.scriptBlock.classList.remove(className);
		}
		else if (showAlways === false)
		{
			this.scriptBlock.classList.add(className);
		}
		else
		{
			this.scriptBlock.classList.toggle(className);
		}
	};
	Editor.prototype.showResult = function (isShow)
	{
		this.resultContainer.style.display = isShow ? '' : 'none';
	};
	Editor.prototype.showStatus = function (hasScript)
	{
		this.status.classList.remove('crm-tracking-site-code-status-value-wait');
		this.status.classList.remove('crm-tracking-site-code-status-value-error');
		this.status.classList.remove('crm-tracking-site-code-status-value-success');
		this.statusBar.style.display = 'none';

		if (hasScript === null)
		{
			BX.addClass(this.btnConnect, 'ui-btn-wait');
			BX.addClass(this.btnCheck, 'ui-btn-wait');
			this.status.textContent = this.mess.statusProcess;
			this.status.classList.add('crm-tracking-site-code-status-value-wait');
			this.statusBar.style.display = '';
			this.showScriptText(false);
			return;
		}

		this.btnConnect.classList.remove('ui-btn-wait');
		this.btnCheck.classList.remove('ui-btn-wait');

		if (hasScript)
		{
			BX.addClass(this.status, 'crm-tracking-site-code-status-value-success');
			this.showScriptText(false);
			this.showResult(true);

			this.status.textContent = this.mess.statusSuccess;
		}
		else if (hasScript === false)
		{
			BX.addClass(this.status, 'crm-tracking-site-code-status-value-error');
			this.showScriptText(true);
			this.showResult(false);

			this.status.textContent = this.mess.statusError;
		}
		else
		{
			this.showScriptText(false);
			this.showResult(false);

			this.status.textContent = this.mess.statusNone;
		}
	};
	Editor.prototype.showAddItemDialog = function (isPhone, anchorNode)
	{
		if (!this.itemDialog)
		{
			this.itemDialog = new BX.PopupWindow(
				'crm-tracking-phone-add',
				anchorNode,
				{
					content: BX('crm-tracking-dialog-add'),
					minWidth: 300
				}
			);
			this.itemDialogName = BX('item-add-name');
			var buttonAdd = BX('item-add-name-btn-add');
			BX.bind(buttonAdd, 'click', function () {
				this.itemDialog.close();
				var value = this.itemDialogName.value.trim();
				if (!value)
				{
					return;
				}

				if (this.itemDialogIsPhone)
				{
					this.selectorPhone.addTile(value, {}, value);
				}
				else
				{
					this.selectorEmail.addTile(value, {}, value);
				}

			}.bind(this));
			BX.bind(BX('item-add-name-btn-close'), 'click', function () {
				this.itemDialog.close();
			}.bind(this));
		}

		this.itemDialogIsPhone = isPhone;
		this.itemDialogName.placeholder = isPhone ?
			this.itemDialogName.getAttribute('data-placeholder-phone')
			:
			this.itemDialogName.getAttribute('data-placeholder-email');
		if (this.itemDialog.isShown())
		{
			this.itemDialog.close();
		}
		else
		{
			this.itemDialogName.value = '';
			this.itemDialog.show();
			this.itemDialogName.focus();
		}
	};

	namespace.Site = new Editor();
	namespace.SiteChecker = Checking;
})();