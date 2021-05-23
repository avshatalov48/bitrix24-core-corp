if(typeof(BX.CrmActivityView) === 'undefined')
{
	BX.CrmActivityView = function()
	{
		this._id = '';
		this._settings = {};
		this._dispatcher = null;
		this._isDirty = false;
		this._enableForcedReloading = false;
		this._prefix = '';
	};

	if(typeof(BX.CrmActivityView.messages) === 'undefined')
	{
		BX.CrmActivityView.messages = {};
	}

	BX.CrmActivityView.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._dispatcher = this.getSetting('dispatcher', null);
			this._prefix = this.getSetting('prefix');

			var context = BX.CrmMobileContext.getCurrent();

			BX.addCustomEvent(
				window,
				'onCrmEntityUpdate',
				BX.delegate(this._onExternalUpdate, this)
			);

			BX.addCustomEvent(
				window,
				'onCrmEntityDelete',
				BX.delegate(this._onExternalDelete, this)
			);

			BX.addCustomEvent(
				window,
				'onOpenPageAfter',
				BX.delegate(this._onAfterPageOpen, this)
			);

			var permissions = this.getSetting('permissions', {});
			if(permissions['EDIT'] || permissions['DELETE'])
			{
				var menuItems = [];

				if (permissions["CAN_COMPLETE"])
				{
					var m = this._dispatcher.getModelById(this.getEntityId());
					var isCompleted = m && m.getDataParam('COMPLETED', false);
					if(!isCompleted)
					{
						menuItems.push(
							{
								icon: 'finish',
								name:  this.getMessage('menuSetCompleted'),
								action: BX.delegate(this._onSetCompleted, this)

							}
						);
					}
					else
					{
						menuItems.push(
							{
								icon: 'play',
								name:  this.getMessage('menuSetNotCompleted'),
								action: BX.delegate(this._onSetNotCompleted, this)

							}
						);
					}
				}

				if(permissions['EDIT'])
				{
					menuItems.push(
						{
							icon: 'edit',
							name:  this.getMessage('menuEdit'),
							action: BX.delegate(this._onEdit, this)

						}
					);
				}
				if(permissions['DELETE'])
				{
					menuItems.push(
						{
							icon: 'delete',
							name: this.getMessage('menuDelete'),
							action: BX.delegate(this._onDelete, this)
						}
					);
				}
				context.prepareMenu(menuItems);
			}
		},
		getId: function()
		{
			return this._id;
		},
		getSettings: function()
		{
			return this._settings;
		},
		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		setSetting: function(name, val)
		{
			this._settings[name] = val;
		},
		getMessage: function(name)
		{
			var items = BX.CrmActivityView.messages;
			return BX.type.isNotEmptyString(items[name]) ? items[name] : '';
		},
		getEntityId: function()
		{
			return parseInt(this.getSetting('entityId', 0));
		},
		prepareElementId: function(name)
		{
			name = name.toLowerCase();
			return this._prefix !== ''
					? (this._prefix + '_' + name) : name;
		},
		resolveElement: function(name)
		{
			return BX(this.prepareElementId(name));
		},
		reloadAsync: function()
		{
			var self = this;
			window.setTimeout(
				function()
				{
					var context = BX.CrmMobileContext.getCurrent();
					context.showPopupLoader();
					context.reload();
					self._isDirty = false;
					context.hidePopupLoader();
				},
				0
			);
		},
		_onEdit: function()
		{
			var url = this.getSetting('editUrl', '');
			if(url === '')
			{
				return;
			}

			BX.CrmMobileContext.getCurrent().open({ url: url, cache: false });
		},
		_onSetCompleted: function()
		{
			var self = this;
			this._dispatcher.execUpdateAction(
				'complete',
				{ 'ID': this.getEntityId(), 'COMPLETED': 1 },
				function(){ self._enableForcedReloading = true; }
			);
		},
		_onSetNotCompleted: function()
		{
			var self = this;
			this._dispatcher.execUpdateAction(
				'complete',
				{ 'ID': this.getEntityId(), 'COMPLETED': 0 },
				function(){ self._enableForcedReloading = true; }
			);
		},
		_onDelete: function()
		{
			BX.CrmMobileContext.getCurrent().confirm(
				this.getMessage("deletionTitle"),
				this.getMessage("deletionConfirmation"),
				["OK", BX.message["JS_CORE_WINDOW_CANCEL"]],
				BX.delegate(
					function(btn){ if(btn === 1) this._dispatcher.deleteEntity(this.getEntityId()); },
					this
				)
			);
		},
		_onExternalUpdate: function(eventArgs)
		{
			var typeName = typeof(eventArgs['typeName']) !== 'undefined' ? eventArgs['typeName'] : '';
			var id = typeof(eventArgs['id']) !== 'undefined' ? parseInt(eventArgs['id']) : 0;

			if(typeName === BX.CrmActivityModel.typeName && id === this.getEntityId())
			{
				if(!this._enableForcedReloading)
				{
					this._isDirty = true;
				}
				else
				{
					this._enableForcedReloading = false;
					this.reloadAsync();
				}

			}
		},
		_onExternalDelete: function(eventArgs)
		{
			var typeName = typeof(eventArgs['typeName']) !== 'undefined' ? eventArgs['typeName'] : '';
			var id = typeof(eventArgs['id']) !== 'undefined' ? parseInt(eventArgs['id']) : 0;

			if(typeName === BX.CrmActivityModel.typeName && id === this.getEntityId())
			{
				BX.CrmMobileContext.getCurrent().close();
			}
		},
		_onAfterPageOpen: function()
		{
			if(this._isDirty)
			{
				this._isDirty = false;
				this.reloadAsync();
			}
		}
	};

	BX.CrmActivityView.items = {};
	BX.CrmActivityView.create = function(id, settings)
	{
		var self = new BX.CrmActivityView();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
}
