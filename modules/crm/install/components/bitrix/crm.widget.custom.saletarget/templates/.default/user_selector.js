BX.namespace('BX.Crm.Widget.Custom.SaleTarget');
BX.Crm.Widget.Custom.SaleTarget.UserSelector = (function(BX)
{
	'use strict';

	var UserSelector = function(config)
	{
		this.id = 'saletarget-user-selector-' + BX.util.getRandomString(7);
		this.bindTo = config.bindTo;
		this.selected = {};
		if (config.selected)
		{
			for (var i = 0; i < config.selected.length; ++i)
			{
				this.selected[config.selected[i].id] = 'user';
			}
		}

		config.selected ? BX.clone(config.selected) : [];
		this.addCallback = config.addCallback;
		this.parentPopup = config.parentPopup;

		this.bind();
	};

	UserSelector.canUse = function()
	{
		return !!BX.SocNetLogDestination;
	};

	UserSelector.prototype = {
		bind: function()
		{
			BX.bind(this.bindTo, 'click', this.onBindClick.bind(this));
			if (this.parentPopup)
			{
				BX.addCustomEvent(this.parentPopup, 'onPopupClose', this.onParentPopupClose.bind(this));
			}
		},
		initDialog: function()
		{
			if (!UserSelector.canUse())
			{
				return false;
			}

			if (this.inited)
			{
				return true;
			}

			var items = {
				users : UserSelector.data.users || {},
				department : UserSelector.data.department || {},
				departmentRelation : UserSelector.data.departmentRelation || {}
			};
			var itemsLast =  {
				users: UserSelector.data.last.USERS || {}
			};

			if (!items["departmentRelation"])
			{
				items["departmentRelation"] = BX.SocNetLogDestination.buildDepartmentRelation(items["department"]);
			}

			var addCallback = this.addCallback;
			BX.SocNetLogDestination.init({
				name: this.id,
				showSearchInput: true,
				bindMainPopup: {node: this.bindTo, offsetTop: '5px', offsetLeft: '15px'},
				departmentSelectDisable: true,
				sendAjaxSearch: true,
				allowAddUser: false,
				extranetUser:  false,
				useClientDatabase: false,

				items : items,
				itemsLast: itemsLast,
				itemsSelected: this.selected,
				destSort: UserSelector.data.destSort || {},
				callback: {
					select : function(item, type, search, unDeleted, name, state)
					{
						if (state !== 'select')
						{
							return;
						}
						var user = {
							id: parseInt(item['entityId']),
							name: BX.util.htmlspecialcharsback(item['name']),
							title: BX.util.htmlspecialcharsback(item['desc']),
							photo: item['avatar'],
							active: true
						};

						addCallback(user);
						BX.SocNetLogDestination.closeDialog();
					}
				}
			});

			return (this.inited = true);
		},
		onBindClick: function()
		{
			if (this.initDialog())
			{
				BX.SocNetLogDestination.openDialog(this.id);
			}
		},
		onParentPopupClose: function()
		{
			if (this.inited)
			{
				if (BX.SocNetLogDestination.isOpenDialog())
				{
					BX.SocNetLogDestination.closeDialog();
				}
			}
		}
	};

	return UserSelector;
})(window.BX || window.top.BX);