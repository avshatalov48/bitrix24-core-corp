 (function(window)
{
	BX.namespace('BX.Voximplant');

	var instance = null;
	var ajaxUrl = "/bitrix/components/bitrix/voximplant.queue.list/ajax.php";
	var gridId = "voximplant_queue_list";

	var defaults = {
		canCreateGroup: false,
		maximumGroups: -1,
		createUrl: ''
	};

	BX.Voximplant.QueueList = function()
	{
		this.bindEvents();
	};

	BX.Voximplant.QueueList.getInstance = function()
	{
		if (instance === null)
		{
			instance = new BX.Voximplant.QueueList();
		}

		return instance;
	};

	BX.Voximplant.QueueList.setDefaults = function(values)
	{
		for (var key in values)
		{
			if(values.hasOwnProperty(key) && defaults.hasOwnProperty(key))
			{
				defaults[key] = values[key];
			}
		}
	};

	BX.Voximplant.QueueList.prototype.bindEvents = function()
	{
		BX.addCustomEvent("SidePanel.Slider:onMessage", this._onSidePanelMessage.bind(this));
		BX.bind(BX("add-queue"), "click", this._onAddGroupButtonClick.bind(this));
	};

	BX.Voximplant.QueueList.prototype.edit = function(editUrl)
	{
		BX.SidePanel.Instance.open(editUrl, {cacheable: false});
	};

	BX.Voximplant.QueueList.prototype.delete = function(id)
	{
		var self = this;
		id = parseInt(id);
		var postParams = {
			action: 'delete',
			sessid: BX.bitrix_sessid(),
			id: id
		};
		var wait = BX.showWait();

		BX.ajax({
			url: ajaxUrl,
			method: 'POST',
			data: postParams,
			dataType: 'json',
			onsuccess: function(response)
			{
				BX.closeWait(null, wait);
				if(!response.SUCCESS && response.USAGES)
				{
					self.showUsages(response.USAGES);
				}
				else
				{
					var grid = BX.Main.gridManager.getInstanceById(gridId);
					if(grid)
						grid.reload();
				}
			},
			onfailure: function()
			{
				BX.closeWait(null, wait);
				window.alert("Network error");
			}
		})
	};

	BX.Voximplant.QueueList.prototype.showUsages = function(usageInfo)
	{
		var popup = new BX.PopupWindow('vi-queue-usage', null, {
			closeIcon: true,
			closeByEsc: true,
			autoHide: false,
			titleBar: BX.message('VOX_QUEUE_DELETE_ERROR'),
			content: this.renderUsagePopup(usageInfo),
			overlay: {
				color: 'gray',
				opacity: 30
			},
			buttons: [
				new BX.PopupWindowButton({
					'id': 'close',
					'text': BX.message('VOX_QUEUE_CLOSE'),
					'events': {
						'click': function(){
							popup.close();
						}
					}
				})
			],
			events: {
				onPopupClose: function() {
					this.destroy();
				},
				onPopupDestroy: function() {
					popup = null;
				}
			}
		});
		popup.show();
	};

	BX.Voximplant.QueueList.prototype.renderUsagePopup = function(usageInfo)
	{
		return BX.create('div', {children: [
			BX.create('div', {props: {className: 'vi-queue-popup-header'}, text: BX.message('VOX_QUEUE_IS_USED')}),
			BX.create('table', {children: this.renderUsages(usageInfo)})
		]})
	};

	BX.Voximplant.QueueList.prototype.renderUsages = function(usageInfo)
	{
		var result = [];
		var usageItem;

		for(var i = 0; i < usageInfo.length; i++)
		{
			usageItem = usageInfo[i];
			result.push(BX.create('tr', {
				children: [
					BX.create('td', {children: [
						BX.create('span', {
							props: {className: 'vi-queue-popup-usage-item'},
							text: (usageItem.TYPE === 'CONFIG' ? BX.message('VOX_QUEUE_NUMBER') : BX.message('VOX_QUEUE_IVR')) + ': '
						})
					]}),
					BX.create('td', {children: [
						BX.create('a', {
							text: usageItem.TITLE,
							attrs: {
								href: usageItem.URL,
								target: '_blank'
							}
						})
					]})
				]
			}))
		}
		return result;
	};

	BX.Voximplant.QueueList.prototype._onSidePanelMessage = function(event)
	{
		if(event.getEventId() === "QueueEditor::onSave")
		{
			var grid = BX.Main.gridManager.getInstanceById(gridId);
			if(grid)
			{
				grid.reload();
			}
		}
	};

	BX.Voximplant.QueueList.prototype._onAddGroupButtonClick = function(event)
	{
		if(defaults.canCreateGroup)
		{
			BX.SidePanel.Instance.open(defaults.createUrl, {cacheable: false});
		}
		else
		{
			if (defaults.maximumGroups == 0)
			{
				BX.UI.InfoHelper.show('limit_contact_center_telephony_groups_zero');
			}
			else
			{
				BX.UI.InfoHelper.show('limit_contact_center_telephony_groups');
			}
		}
	};


})(window);