if(typeof(BX.CrmSmsSend) === "undefined")
{
	BX.CrmSmsSend = function(container)
	{
		this._senderId = null;
		this._from = null;
		this._commEntityTypeId = null;
		this._commEntityId = null;
		this._to = null;

		this._fromList = [];
		this._toList = [];

		this._input = null;
		this._menu = null;
		this._isMenuShown = false;
		this._shownMenuId = null;

		this._canUse = null;
		this._canSendMessage = null;
		this._manageUrl = null;
		this._senders = null;
		this._defaults = null;
		this._communications = null;
		this._container = container;
		this._isRequestRunning = false;

		this.saveButton = null;
		this.cancelButton = null;
		this._serviceUrl = '';

		this._ownerTypeId = '';
		this._ownerId = '';
	};

	BX.CrmSmsSend.prototype.init = function(settings)
	{
		this._canUse = BX.prop.getBoolean(settings, "canUse", false);
		this._canSendMessage = BX.prop.getBoolean(settings, "canSendMessage", false);
		this._manageUrl = BX.prop.getString(settings, "manageUrl", '');
		this._senders = BX.prop.getArray(settings, "senders", []);
		this._defaults = BX.prop.getObject(settings, "defaults", {senderId:null,from:null});
		this._communications = BX.prop.getArray(settings, "communications", []);
		this._serviceUrl = BX.prop.getString(settings, 'serviceUrl', '');
		this._ownerTypeId = BX.prop.getInteger(settings, 'ownerTypeId', 0);
		this._ownerId = BX.prop.getInteger(settings, 'ownerId', 0);

		this._senderSelectorNode = this._container.querySelector('[data-role="sender-selector"]');
		this._fromContainerNode = this._container.querySelector('[data-role="from-container"]');
		this._fromSelectorNode = this._container.querySelector('[data-role="from-selector"]');
		this._clientContainerNode = this._container.querySelector('[data-role="client-container"]');
		this._clientSelectorNode = this._container.querySelector('[data-role="client-selector"]');
		this._toSelectorNode = this._container.querySelector('[data-role="to-selector"]');
		this._messageLengthCounterNode = this._container.querySelector('[data-role="message-length-counter"]');
		this._input = this._container.querySelector('[data-role="input"]');
		this.saveButton = this._container.querySelector('[data-role="button-save"]');
		this.cancelButton = this._container.querySelector('[data-role="button-cancel"]');

		if(this._canUse && this._canSendMessage)
		{
			this.initSenderSelector();
			this.initFromSelector();
			this.initClientContainer();
			this.initClientSelector();
			this.initToSelector();
			this.initMessageLengthCounter();
			this.initButtons();
			this.setMessageLengthCounter();
		}
	};

	BX.CrmSmsSend.prototype.initSenderSelector = function()
	{
		var defaultSenderId = this._defaults.senderId ;
		var defaultSender = this._senders[0].canUse ? this._senders[0] : null;
		var restSender = null;
		var menuItems = [];
		var handler = this.onSenderSelectorClick.bind(this);

		for (var i = 0; i < this._senders.length; ++i)
		{
			if (this._senders[i].canUse && this._senders[i].fromList.length && (this._senders[i].id === defaultSenderId || !defaultSender))
			{
				defaultSender = this._senders[i];
			}

			if (this._senders[i].id === 'rest')
			{
				restSender = this._senders[i];
				continue;
			}

			menuItems.push({
				text: this._senders[i].name,
				sender: this._senders[i],
				onclick: handler,
				className: (!this._senders[i].canUse || !this._senders[i].fromList.length)
					? 'crm-sms-send-popup-menu-item-disabled menu-popup-no-icon' : ''
			});
		}

		if (restSender)
		{
			if (restSender.fromList.length > 0)
			{
				menuItems.push({delimiter: true});
				for (i = 0; i < restSender.fromList.length; ++i)
				{
					menuItems.push({
						text: restSender.fromList[i].name,
						sender: restSender,
						from: restSender.fromList[i],
						onclick: handler
					});
				}
			}
			menuItems.push({delimiter: true}, {
				text: BX.message('CRM_SMS_REST_MARKETPLACE'),
				href: '/marketplace/category/crm_robot_sms/',
				target: '_blank'
			});
		}

		if (defaultSender)
		{
			this.setSender(defaultSender);
		}

		BX.bind(this._senderSelectorNode, 'click', this.openMenu.bind(this, 'sender', this._senderSelectorNode, menuItems));
	};

	BX.CrmSmsSend.prototype.onSenderSelectorClick = function(e, item)
	{
		if (item.sender)
		{
			if (!item.sender.canUse || !item.sender.fromList.length)
			{
				window.open(item.sender.manageUrl);
				return;
			}

			this.setSender(item.sender, true);
			var from = item.from ? item.from : item.sender.fromList[0];
			this.setFrom(from, true);
		}
		this._menu.close();
	};

	BX.CrmSmsSend.prototype.setSender = function(sender, setAsDefault)
	{
		this._senderId = sender.id;
		this._fromList = sender.fromList;
		this._senderSelectorNode.textContent = sender.shortName ? sender.shortName : sender.name;

		var visualFn = sender.id === 'rest' ? 'hide' : 'show';
		BX[visualFn](this._fromContainerNode);

		if (setAsDefault)
		{
			BX.userOptions.save("crm", "sms_manager_editor", "senderId", this._senderId);
		}
	};

	BX.CrmSmsSend.prototype.initFromSelector = function()
	{
		if (this._fromList.length > 0)
		{
			var defaultFromId = this._defaults.from || this._fromList[0].id;
			var defaultFrom = null;
			for (var i = 0; i < this._fromList.length; ++i)
			{
				if (this._fromList[i].id === defaultFromId || !defaultFrom)
				{
					defaultFrom = this._fromList[i];
				}
			}
			if (defaultFrom)
			{
				this.setFrom(defaultFrom);
			}
		}

		BX.bind(this._fromSelectorNode, 'click', this.onFromSelectorClick.bind(this));
	};

	BX.CrmSmsSend.prototype.onFromSelectorClick = function(e)
	{
		var menuItems = [];
		var handler = this.onFromSelectorItemClick.bind(this);

		for (var i = 0; i < this._fromList.length; ++i)
		{
			menuItems.push({
				text: this._fromList[i].name,
				from: this._fromList[i],
				onclick: handler
			});
		}

		this.openMenu('from_'+this._senderId, this._fromSelectorNode, menuItems, e);
	};
	BX.CrmSmsSend.prototype.onFromSelectorItemClick = function(e, item)
	{
		if (item.from)
		{
			this.setFrom(item.from, true);
		}
		this._menu.close();
	};
	BX.CrmSmsSend.prototype.setFrom = function(from, setAsDefault)
	{
		this._from = from.id;

		if (this._senderId === 'rest')
		{
			this._senderSelectorNode.textContent = from.name;
		}
		else
		{
			this._fromSelectorNode.textContent = from.name;
		}

		if (setAsDefault)
		{
			BX.userOptions.save("crm", "sms_manager_editor", "from", this._from);
		}
	};
	BX.CrmSmsSend.prototype.initClientContainer = function()
	{
		if (this._communications.length === 0)
		{
			BX.hide(this._clientContainerNode);
		}
	};
	BX.CrmSmsSend.prototype.initClientSelector = function()
	{
		var menuItems = [];
		var handler = this.onClientSelectorClick.bind(this);

		for (var i = 0; i < this._communications.length; ++i)
		{
			menuItems.push({
				text: this._communications[i].caption,
				client: this._communications[i],
				onclick: handler
			});
			if (i === 0)
			{
				this.setClient(this._communications[i]);
			}
		}

		BX.bind(this._clientSelectorNode, 'click', this.openMenu.bind(this, 'comm', this._clientSelectorNode, menuItems));
	};
	BX.CrmSmsSend.prototype.onClientSelectorClick = function(e, item)
	{
		if (item.client)
		{
			this.setClient(item.client);
		}
		this._menu.close();
	};
	BX.CrmSmsSend.prototype.setClient = function(client)
	{
		this._commEntityTypeId = client.entityTypeId;
		this._commEntityId = client.entityId;
		this._clientSelectorNode.textContent = client.caption;
		this._toList = client.phones;
		this.setTo(client.phones[0]);
	};
	BX.CrmSmsSend.prototype.initToSelector = function()
	{
		BX.bind(this._toSelectorNode, 'click', this.onToSelectorClick.bind(this));
	};
	BX.CrmSmsSend.prototype.onToSelectorClick = function(e)
	{
		var menuItems = [];
		var handler = this.onToSelectorItemClick.bind(this);

		for (var i = 0; i < this._toList.length; ++i)
		{
			menuItems.push({
				text: this._toList[i].valueFormatted || this._toList[i].value,
				to: this._toList[i],
				onclick: handler
			});
		}

		this.openMenu('to_'+this._commEntityTypeId+'_'+this._commEntityId, this._toSelectorNode, menuItems, e);
	};
	BX.CrmSmsSend.prototype.onToSelectorItemClick = function(e, item)
	{
		if (item.to)
		{
			this.setTo(item.to);
		}
		this._menu.close();
	};
	BX.CrmSmsSend.prototype.setTo = function(to)
	{
		this._to = to.value;
		this._toSelectorNode.textContent = to.valueFormatted || to.value;
	};
	BX.CrmSmsSend.prototype.openMenu = function(menuId, bindElement, menuItems, e)
	{
		if (this._shownMenuId === menuId)
		{
			return;
		}

		if(this._shownMenuId !== null && this._menu)
		{
			this._menu.close();
			this._shownMenuId = null;
		}

		BX.PopupMenu.show(
			this._id + menuId,
			bindElement,
			menuItems,
			{
				offsetTop: 0,
				offsetLeft: 36,
				angle: { position: "top", offset: 0 },
				events:
					{
						onPopupClose: BX.delegate(this.onMenuClose, this)
					}
			}
		);

		this._menu = BX.PopupMenu.currentItem;
		e.preventDefault();
	};
	BX.CrmSmsSend.prototype.onMenuClose = function()
	{
		this._shownMenuId = null;
		this._menu = null;
	};
	BX.CrmSmsSend.prototype.initMessageLengthCounter = function()
	{
		this._messageLengthMax = parseInt(this._messageLengthCounterNode.getAttribute('data-length-max'));
		BX.bind(this._input, 'keyup', this.setMessageLengthCounter.bind(this));
	};
	BX.CrmSmsSend.prototype.setMessageLengthCounter = function()
	{
		var length = this._input.value.length;
		this._messageLengthCounterNode.textContent = length;

		var classFn = length >= this._messageLengthMax ? 'addClass' : 'removeClass';
		BX[classFn](this._messageLengthCounterNode, 'sms-symbol-counter-number-overhead');
	};
	BX.CrmSmsSend.prototype.initButtons = function()
	{
		BX.bind(this.saveButton, 'click', this.save.bind(this));
		BX.bind(this.cancelButton, 'click', this.cancel.bind(this));
	};
	BX.CrmSmsSend.prototype.save = function()
	{
		var text = this._input.value;
		if(text === "")
		{
			return;
		}

		if (!this._communications.length)
		{
			alert(BX.message('CRM_SMS_ERROR_NO_COMMUNICATIONS'));
			return;
		}

		if(this._isRequestRunning)
		{
			return;
		}

		this._isRequestRunning = true;
		BX.ajax(
			{
				url: BX.util.add_url_param(this._serviceUrl, {
					"action": "save_sms_message",
					"sender": this._senderId
				}),
				method: "POST",
				dataType: "json",
				data:
					{
						'site': BX.message('SITE_ID'),
						'sessid': BX.bitrix_sessid(),
						"ACTION": "SAVE_SMS_MESSAGE",
						"SENDER_ID": this._senderId,
						"MESSAGE_FROM": this._from,
						"MESSAGE_TO": this._to,
						"MESSAGE_BODY": text,
						"OWNER_TYPE_ID": this._ownerTypeId,
						"OWNER_ID": this._ownerId,
						"TO_ENTITY_TYPE_ID": this._commEntityTypeId,
						"TO_ENTITY_ID": this._commEntityId
					},
				onsuccess: BX.delegate(this.onSaveSuccess, this),
				onfailure: BX.delegate(this.onSaveFailure, this)
			}
		);
	};
	BX.CrmSmsSend.prototype.cancel = function()
	{
		if(BX.SidePanel)
		{
			var curSlider = BX.SidePanel.Instance.getSliderByWindow(window);
			if(curSlider)
			{
				curSlider.close();
			}
		}
	};
	BX.CrmSmsSend.prototype.onSaveSuccess = function(data)
	{
		this._isRequestRunning = false;

		var error = BX.prop.getString(data, "ERROR", "");
		if(error !== "")
		{
			alert(error);
			return;
		}

		this.cancel();
	};
	BX.CrmSmsSend.prototype.onSaveFailure = function(data)
	{
		this._isRequestRunning = false;
	};
}