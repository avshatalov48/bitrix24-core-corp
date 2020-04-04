(function()
{
	var AJAX_URL = '/bitrix/components/bitrix/crm.activity.call_list/ajax.php';
	var lastInstance = null;

	var CallListActivity = function(config)
	{
		this.node = config.node;
		this.callListId = config.callListId || 0;
		this.webformId = config.webformId || 0;
		this.webformSecCode = config.webformSecCode || '';
		this.allowEdit = (config.allowEdit == true);
		this.gridId = config.gridId || '';
		this.lastGridUrl = '';
		this.enableSavePopup = config.enableSavePopup == true;

		this.tabs = [];

		//flags
		this._itemsState = 'idle';

		this._handleTabHeaderClickEvent = this._handleTabHeaderClick.bind(this);

		this.init();

		this.externalRequests = {};

	};

	CallListActivity.create = function(config)
	{
		lastInstance = new CallListActivity(config);
		return lastInstance;
	};

	CallListActivity.getLast = function()
	{
		return lastInstance;
	};

	CallListActivity.prototype.init = function()
	{
		var self = this;
		if(this.getNode('call-list-display'))
		{
			this.setActiveTab('params');
		}

		var tabHeader = this.getNode('call-list-tab-header');
		if (tabHeader)
		{
			var tabNodes = BX.findChildrenByClassName(tabHeader, 'activity-call-list-display-tab');
			if(BX.type.isArray(tabNodes))
			{
				tabNodes.forEach(function(tabNode)
				{
					self.tabs.push(tabNode.dataset.tabHeader);
				})
			}
		}
		this.bindEvents();

		BX.addCustomEvent(window, 'Grid::beforeRequest', function(gridData, requestParams)
		{
			requestParams.url =	BX.util.add_url_param(requestParams.url, {
				'sessid': BX.bitrix_sessid(),
				'ajax_action': 'GET_ITEMS_GRID',
				'callListId': self.callListId,
				'allowEdit': (self.allowEdit ? 'Y' : 'N')
			});
			self.lastGridUrl = requestParams.url;
		});

		BX.addCustomEvent(window, "onLocalStorageSet", this._OnExternalEvent.bind(this));
	};

	CallListActivity.prototype.reInit = function ()
	{
		var self = this;
		this.tabs = [];
		this._itemsState = 'idle';
		var tabHeader = this.getNode('call-list-tab-header');
		if (tabHeader)
		{
			var tabNodes = BX.findChildrenByClassName(tabHeader, 'activity-call-list-display-tab');
			if(BX.type.isArray(tabNodes))
			{
				tabNodes.forEach(function(tabNode)
				{
					self.tabs.push(tabNode.dataset.tabHeader);
				})
			}
		}
		this.bindEvents();
	};

	CallListActivity.prototype.bindEvents = function()
	{
		var self = this;
		var tabHeader = this.getNode('call-list-tab-header');
		if (tabHeader)
		{
			var tabNodes = BX.findChildren(tabHeader, {className: 'activity-call-list-display-tab'});
			if(BX.type.isArray(tabNodes))
			{
				tabNodes.forEach(function(tabNode)
				{
					BX.bind(tabNode, 'click', self._handleTabHeaderClickEvent);
				});
			}
		}

		var createFromLeads = this.getNode('create-from-leads');
		if (createFromLeads)
			BX.bind(createFromLeads, 'click', self._handleCreateFromClick.bind(self));

		var createFromContacts = this.getNode('create-from-contacts');
		if (createFromContacts)
			BX.bind(createFromContacts, 'click', self._handleCreateFromClick.bind(self));

		var createFromCompanies = this.getNode('create-from-companies');
		if (createFromCompanies)
			BX.bind(createFromCompanies, 'click', self._handleCreateFromClick.bind(self));

		var createFromDeals = this.getNode('create-from-deals');
		if (createFromDeals)
			BX.bind(createFromDeals, 'click', self._handleCreateFromClick.bind(self));

		var createFromQuotes = this.getNode('create-from-quotes');
		if (createFromQuotes)
			BX.bind(createFromQuotes, 'click', self._handleCreateFromClick.bind(self));

		var createFromInvoices = this.getNode('create-from-invoices');
		if (createFromInvoices)
			BX.bind(createFromInvoices, 'click', self._handleCreateFromClick.bind(self));

		var addMore = this.getNode('add-more');
		if (addMore)
			BX.bind(addMore, 'click', self._handleAddMore.bind(self));

		var callButton = this.getNode('invoke-call-interface', document);
		if (callButton)
		{
			BX.bind(callButton, 'click', function(e)
			{
				if(!top.BXIM)
					return false;

				if(top.BX.Bitrix24 && top.BX.Bitrix24.Slider)
				{
					top.BX.Bitrix24.Slider.closeAll();
				}
				else
				{
					for(dialogId in top.BX.CrmActivityProvider.dialogs)
					{
						if(top.BX.CrmActivityProvider.dialogs.hasOwnProperty(dialogId) && top.BX.CrmActivityProvider.dialogs[dialogId])
						{
							top.BX.CrmActivityProvider.dialogs[dialogId].close();
						}
					}
				}

				if(top.BX.FoldedCallView)
					top.BX.FoldedCallView.getInstance().destroy();

				top.BXIM.startCallList(self.callListId, {
					webformId: self.webformId,
					webformSecCode: self.webformSecCode
				});
			})
		}

		var openFilterButton = this.getNode('open-filter');
		if (openFilterButton)
		{
			BX.bind(openFilterButton, 'click', self._handleOpenFilterClick.bind(self));
		}

		var planner = BX.Crm.Activity.Planner.Manager.getLast();
		if(planner)
		{
			BX.addCustomEvent(planner, 'onAfterActivitySave', this._onAfterActivitySave.bind(this));
		}
		BX.addCustomEvent(window, 'onActivityEditorClose', this._onPlannerClose.bind(this));
	};

	CallListActivity.prototype.getNode = function(name, scope)
	{
		if (!scope)
			scope = this.node;

		return scope ? scope.querySelector('[data-role="'+name+'"]') : null;
	};

	CallListActivity.prototype.getNodeValue = function(name, scope)
	{
		if (!scope)
			scope = this.node;

		var node = scope ? scope.querySelector('[data-role="'+name+'"]') : null;
		return (node ? node.value : '');
	};

	CallListActivity.prototype.getTab = function(name, scope)
	{
		if (!scope)
			scope = this.node;

		return scope ? scope.querySelector('[data-tab="'+name+'"]') : null;
	};

	CallListActivity.prototype.getTabHeader = function(name, scope)
	{
		if (!scope)
			scope = this.node;

		return scope ? scope.querySelector('[data-tab-header="'+name+'"]') : null;
	};


	CallListActivity.prototype.setActiveTab = function(tabName)
	{
		var self = this;
		this.tabs.forEach(function(tab)
		{
			if(tab === tabName)
			{
				BX.removeClass(self.getTab(tab), 'activity-call-list-display-hidden');
				BX.addClass(self.getTabHeader(tab), 'activity-call-list-display-tab-active');
				BX.removeClass(self.getTabHeader(tab), 'activity-call-list-display-tab-inactive');
			}
			else
			{
				BX.addClass(self.getTab(tab), 'activity-call-list-display-hidden');
				BX.removeClass(self.getTabHeader(tab), 'activity-call-list-display-tab-active');
				BX.addClass(self.getTabHeader(tab), 'activity-call-list-display-tab-inactive');
			}
		});

		if(tabName === 'grid')
		{
			this.showItemsGrid();
		}
	};

	CallListActivity.prototype.showItemsGrid = function()
	{
		var self = this;
		var gridContainer = this.getNode('grid-container');

		if (self._itemsState === 'idle')
		{
			self._itemsState = 'loading';
			self._loadItemsGrid(function(pageHtml)
			{
				self._itemsState = 'loaded';
				gridContainer.innerHTML = pageHtml;
			});

		}
	};

	CallListActivity.prototype._loadItemsGrid = function(successCallback)
	{
		var params = {
			'sessid': BX.bitrix_sessid(),
			'ajax_action': 'GET_ITEMS_GRID',
			'callListId': this.callListId,
			'allowEdit': (this.allowEdit ? 'Y' : 'N')
		};
		
		BX.ajax({
			method: 'POST',
			dataType: 'html',
			url: AJAX_URL,
			data: params,
			onsuccess: function (HTML)
			{
				successCallback(HTML);
			}
		});
	};
	
	CallListActivity.prototype._reloadLayout = function()
	{
		var self = this;
		var subject = this.getNodeValue('call-list-subject');
		var description = this.getNodeValue('call-list-description');

		var params = {
			'sessid': BX.bitrix_sessid(),
			'ajax_action': 'RELOAD',
			'callListId': this.callListId,
			'subject': subject,
			'description': description
		};

		BX.ajax({
			method: 'POST',
			dataType: 'html',
			url: AJAX_URL,
			data: params,
			onsuccess: function (HTML)
			{
				self.node.outerHTML = HTML;
				self.node = BX('activity-call-list');
				self.reInit();
				self.getNode('call-list-subject').value = subject;
				self.getNode('call-list-description').value = description;
			}
		});
	};

	CallListActivity.prototype._handleTabHeaderClick = function(e)
	{
		var tabName = e.target.dataset.tabHeader;
		this.setActiveTab(tabName);
	};
	
	CallListActivity.prototype._handleOpenFilterClick = function(e)
	{
		var params = {
			'sessid': BX.bitrix_sessid(),
			'ajax_action': 'APPLY_ORIGINAL_FILTER',
			'callListId': this.callListId
		};

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: AJAX_URL,
			data: params,
			onsuccess: function (response)
			{
				if(response.SUCCESS)
				{
					var data = response.DATA;
					if (data.LIST_URL)
					{
						window.open(data.LIST_URL);
					}
				}
			}
		});
	};

	CallListActivity.prototype._handleCreateFromClick = function(e)
	{
		var context = this._generateExternalContext();
		var listUrl = e.target.dataset.url;

		this.externalRequests[context] = {
			context: context,
			window: window.open(BX.util.add_url_param(listUrl, {call_list_context: context, call_list_id: this.callListId}))
		}
	};

	CallListActivity.prototype._handleAddMore = function(e)
	{
		var context = this._generateExternalContext();
		var listUrl = e.target.dataset.url;

		this.externalRequests[context] = {
			context: context,
			window: window.open(BX.util.add_url_param(listUrl, {call_list_context: context, call_list_id: this.callListId}))
		}
	};

	CallListActivity.prototype._OnExternalEvent = function(params)
	{
		params = BX.type.isPlainObject(params) ? params : {};
		params.key = params.key || '';

		var value = params.value || {};

		if(params.key === 'onCrmCallListUpdate' && this.externalRequests[value.context])
		{
			var grid = this.getGrid();
			if(grid)
			{
				this.reloadGrid()
			}
			else
			{
				this._reloadLayout();
			}

			if(this.externalRequests[value.context]['window'])
				this.externalRequests[value.context]['window'].close();

			delete this.externalRequests[value.context];
		}
	};

	CallListActivity.prototype._onAfterActivitySave = function(activityFields)
	{
		if(!activityFields.NEW || activityFields.NEW == 'N')
			return;

		if(!this.enableSavePopup)
			return;

		var popup = new BX.PopupWindow('call_list_activity_created', null, {
			autoHide: true,
			overlay: false,
			lightShadow: true,
			closeIcon: true,
			closeByEsc: true,
			contentColor : 'white',
			titleBar: BX.message('CRM_ACTIVITY_CALL_LIST_ACTIVITY_CREATED'),
			content: BX.create('span', {props: {className: 'crm-activity-call-list-success-text'}, text: BX.message('CRM_ACTIVITY_CALL_LIST_ACTIVITY_CREATED_TEXT')}),
			buttons: [
				new BX.PopupWindowButton({
					text: BX.message('CRM_ACTIVITY_CALL_LIST_ACTIVITY_GOTO'),
					events: {
						click: function()
						{
							if(activityFields.VIEW_URL)
							{
								popup.close();
								window.open(activityFields.VIEW_URL);
							}
						}
					}
				})
			],
			events: {
				onPopupClose: function()
				{
					popup.destroy();
				}
			}
		});
		popup.show();
	};

	CallListActivity.prototype._onPlannerClose = function()
	{
		BX.Main.gridManager.destroy(this.gridId);
	};

	CallListActivity.prototype.deleteItems = function(items)
	{
		var self = this;
		if(!BX.type.isArray(items))
			return;

		var params = {
			'sessid': BX.bitrix_sessid(),
			'ajax_action': 'DELETE_ITEMS',
			'callListId': this.callListId,
			'items': items
		};

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: AJAX_URL,
			data: params,
			onsuccess: function (response)
			{
				if(response.SUCCESS)
				{
					self.reloadGrid();
				}
			}
		});
	};

	CallListActivity.prototype.deleteSelected = function()
	{
		var grid = this.getGrid();
		if(!grid)
			return false;

		var selectedIds = grid.getRows().getSelectedIds();
		if(!BX.type.isArray(selectedIds))
			return false;

		this.deleteItems(selectedIds);
	};

	CallListActivity.prototype.getGrid = function()
	{
		var gridHandle;
		if(BX && BX.Main && BX.Main.gridManager)
		{
			gridHandle = BX.Main.gridManager.getById(this.gridId);
		}

		return gridHandle ? gridHandle.instance : null;
	};

	CallListActivity.prototype.reloadGrid = function()
	{
		var url = this.lastGridUrl != '' ? this.lastGridUrl : AJAX_URL;
		var grid = this.getGrid();
		if(!grid)
			return false;

		grid.reload(url);
		return true;
	};

	CallListActivity.prototype._generateExternalContext = function()
	{
		return this._getRandomString(16);
	};

	CallListActivity.prototype._getRandomString = function (len)
	{
		charSet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		var randomString = '';
		for (var i = 0; i < len; i++) {
			var randomPoz = Math.floor(Math.random() * charSet.length);
			randomString += charSet.substring(randomPoz,randomPoz+1);
		}
		return randomString;
	};

	CallListActivity.prototype.destroy = function()
	{
		BX.Main.gridManager.destroy(gridId);
	};

	BX.CallListActivity = CallListActivity;
})();