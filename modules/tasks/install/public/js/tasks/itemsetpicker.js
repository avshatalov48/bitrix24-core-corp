BX.namespace('BX.Tasks');

// although this class called "UserItemSet" it allows also group and department selector due to the historical reasons
BX.Tasks.UserItemSet = BX.Tasks.Util.ItemSet.extend({
	sys: {
		code: 'user-item-set'
	},
    options: {
        nameTemplate: false,
		useSearch: false, // specify if we use a search input INSIDE the popup, not outside
	    useAdd: false, // specify if user add allowed
	    mode: 'user', // specify if we can select users or groups. todo: both variants together are not supported, like ['user', 'group]
	    prefixId: false,
	    popupOffsetTop: 0,
	    popupOffsetLeft: 0
    },
	methods: {
		construct: function()
		{
			if(typeof this.instances == 'undefined')
			{
				this.instances = {};
			}

			this.instances.selector = new BX.Tasks.Integration.Socialnetwork.NetworkSelector({
				scope: this.scope(),
				id: 'selector-'+this.getRandomHash(),
				mode: this.getNSMode(),
				useSearch: this.option('useSearch'),
				useAdd: this.option('useAdd'),
				forceTop: this.option('forceTop'),
				controlBind: this.option('controlBind'),
				parent: this,
				popupOffsetTop: this.option('popupOffsetTop'),
				popupOffsetLeft: this.option('popupOffsetLeft')
			});

			this.callConstruct(BX.Tasks.Util.ItemSet);

			this.vars.intendOpen = false;
			this.vars.initialized = false;
			this.vars.changed = false;

            this.showLoader = BX.Tasks.Util.delay(this.showLoader, false, 500, this);
		},

		bindEvents: function()
		{
			this.callMethod(BX.Tasks.Util.ItemSet, 'bindEvents', []);

			this.instances.selector.bindEvent('close', BX.delegate(this.onClose, this));
			this.instances.selector.bindEvent('initialized', BX.delegate(this.onSelectorInitialized, this));
			this.instances.selector.bindEvent('item-selected', BX.delegate(this.onSelectorItemSelected, this));
			this.instances.selector.bindEvent('item-deselected', BX.delegate(this.onSelectorItemDeselected, this));

			BX.Tasks.Util.filterFocusBlur(
				this.control('search'),
				false,
				BX.delegate(this.onSearchBlurred, this),
				200
			);
		},

        onClose: function()
        {
	        // may be do smth when changed

	        this.vars.changed = false;
        },

		getNSMode: function()
		{
			return this.option('mode');
		},

        showLoader: function()
        {
            this.setCSSFlag('loading')
        },

        hideLoader: function()
        {
            this.showLoader.cancel();
            this.dropCSSFlag('loading');
        },

        openAddForm: function()
		{
            this.setCSSFlag('search');
            this.showLoader();
			this.searchPopupOpen();
		},

		onSelectorItemSelected: function(data)
		{
			this.vars.changed = true;

			var value = this.extractItemValue(data);

			if(!this.hasItem(value))
			{
				if(!this.checkCanAddItems())
				{
					var itemLast = this.getItemLast();
					if(itemLast != null)
					{
						this.replaceItem(itemLast.value(), data);
					}
				}
				else
				{
					this.addItem(data, {});
				}

				if(!this.checkCanAddItems()) // hide popup if cannot add more
				{
					this.instances.selector.close();
					this.onSearchBlurred();
				}
			}

			this.resetInput();
		},

		onSelectorItemDeselected: function(data)
		{
			this.vars.changed = true;

            var value = this.extractItemValue(data);

            if(!this.hasItem(value))
            {
                return false;
            }

			this.deleteItem(value);
			this.resetInput();
		},

        deleteItem: function(value)
        {
            if(this.callMethod(BX.Tasks.Util.ItemSet, 'deleteItem', arguments))
            {
	            if(typeof value == 'object')
	            {
		            value = value.value();
	            }

                this.instances.selector.deselectItem(value);
                return true;
            }

            return false;
        },

		onSearchBlurred: function()
		{
			if(this.instances.selector.checkIsOpened())
			{
				return false;
			}

			this.toggleSearchOff();
			this.vars.intendOpen = false;

			return true;
		},

		toggleSearchOff: function()
		{
			this.dropCSSFlag('search');
		},

		searchPopupOpen: function()
		{
			this.vars.intendOpen = true;
			this.initializeSelector();
		},

		resetInput: function()
		{
			var search = this.control('search');
			if(search)
			{
				search.value = '';
				search.focus();
			}
		},

		initializeSelector: function()
		{
			this.instances.selector.initialize();
		},

		onSelectorInitialized: function()
		{
			if(this.vars.intendOpen)
			{
                this.hideLoader();
                this.setCSSFlag('ready');

				var search = this.control('search');
				if(BX.type.isElementNode(search))
				{
					search.focus();
				}

				this.instances.selector.open();
			}
		},

		extractItemDisplay: function(data)
		{
			if(typeof data.DISPLAY != 'undefined')
			{
				return data.DISPLAY;
			}

			if(typeof data.nameFormatted != 'undefined')
			{
				return BX.util.htmlspecialcharsback(data.nameFormatted); // socnetlogdest returns escaped name, we want unescaped
			}

			if(!('entityType' in data))
			{
				data.entityType = 'U';
			}

			if(data.entityType == 'U')
			{
				var nameTemplate = this.option('nameTemplate');
				if(nameTemplate)
				{
					var formatted = BX.formatName(data, nameTemplate, 'Y');
					if(formatted == 'Noname') // Noname - bad, login - good
					{
						formatted = data.LOGIN || data.login;
					}

					return formatted;
				}

				return data.LOGIN;
			}
			else
			{
				if(data.NAME)
				{
					return data.NAME;
				}
				if(data.TITLE)
				{
					return data.TITLE;
				}

				return data.ID;
			}
		},
		extractItemValue: function(data)
		{
			// todo: make a provider here to be able to access both "id" and "ID" keys in the same manner
			var id = typeof data.ID == 'undefined' ? data.id : data.ID;

			if (id)
			{
			}
			else if (data.email) // the following is for creating email users
			{
				 // to avoid adding duplicates when no ID defined
				id = 'n' + BX.util.hashCode(data.email);
			}
			else if (data.networkId) // the following is for creating network users
			{
				id = data.networkId;
			}
			else
			{
				id = 'n' + false;
			}

			if(this.option('prefixId'))
			{
				if(!('entityType' in data))
				{
					data.entityType = 'U';
				}

				// todo: the same
				var entityType = typeof data.ENTITY_TYPE == 'undefined' ? data.entityType : data.ENTITY_TYPE;
				if(!entityType)
				{
					entityType = '?';
				}

				id = entityType+id;
			}

			return id;
		},

		prepareData: function(data)
		{
			if(!('WORK_POSITION' in data))
			{
				if('description' in data)
				{
					data.WORK_POSITION = BX.util.htmlspecialcharsback(data.description);

					// getting rid of that annoying immortal &nbsp;
					if(data.WORK_POSITION == '&nbsp;')
					{
						data.WORK_POSITION = '';
					}
				}
				else
				{
					data.WORK_POSITION = '';
				}
			}

			if(!('AVATAR' in data))
			{
				data.AVATAR = data.avatar || '';
			}
			if(!('NAME' in data))
			{
				data.NAME = data.name || '';
			}
			if(!('LAST_NAME' in data))
			{
				data.LAST_NAME = data.lastName || '';
			}
			if(!('EMAIL' in data))
			{
				data.EMAIL = data.email || '';
			}

			if (data.type && "crmemail" in data.type)
			{
				data.IS_CRM_EMAIL_USER = data.type.crmemail;
			}
			if (data.type && "email" in data.type)
			{
				data.IS_EMAIL_USER = data.type.email;
			}
			if (data.type && "extranet" in data.type)
			{
				data.IS_EXTRANET_USER = data.type.extranet;
			}
			if (data.type && "network" in data.type)
			{
				data.IS_NETWORK_USER = data.type.network;
			}
			if (data.type && 'collab' in data.type)
			{
				data.IS_COLLAB = data.type.collab;
			}
			if (data.type && 'collaber' in data.type)
			{
				data.IS_COLLABER_USER = data.type.collaber;
			}

			if(!('entityType' in data))
			{
				data.entityType = 'U';
			}

			var typeSet = [];
			if(data.entityType == 'P')
			{
				data.USER_TYPE = "employee";
				if (data.IS_NETWORK_USER)
				{
					data.USER_TYPE = "network";
					typeSet.push('network');
				}
				data.ENTITY_TYPE_CODE = 'USER';
			}
			if(data.entityType == 'U')
			{
				data.USER_TYPE = "employee";
				if (data.IS_CRM_EMAIL_USER)
				{
					data.USER_TYPE = "crmemail";
					typeSet.push('crmemail');
				}
				else if (data.IS_EMAIL_USER)
				{
					data.USER_TYPE = "mail";
					typeSet.push('mail');
				}
				else if (data.IS_COLLABER_USER)
				{
					data.USER_TYPE = "collaber";
					typeSet.push('collaber');
				}
				else if (data.IS_EXTRANET_USER)
				{
					data.USER_TYPE = "extranet";
					typeSet.push('extranet');
				}
				else if (data.IS_COLLAB)
				{
					typeSet.push('collab');
				}
				data.ENTITY_TYPE_CODE = 'USER';
			}
			if(data.entityType == 'SG')
			{
				data.ENTITY_TYPE_CODE = 'GROUP';
				typeSet.push('group');
			}
			if(data.entityType == 'DR')
			{
				data.ENTITY_TYPE_CODE = 'DEPARTMENT';
				typeSet.push('department');
			}

			data.TYPE_SET = typeSet.join(' ');

			// path...
			if(!('URL' in data))
			{
				data.URL = 'javascript:void(0);';
				var path = this.option('path');

				if(path && path[data.entityType])
				{
					data.URL = path[data.entityType].toString().replace('{{ID}}', parseInt(data.id || data.ID));
				}
			}

            return data;
        }
	}
});

/**
 * @deprecated
 */
BX.Tasks.GroupItemSet = BX.Tasks.UserItemSet.extend({
	sys: {
		code: 'group-item-set'
	},
	methods: {
		extractItemDisplay: function(data)
		{
			if(typeof data.DISPLAY != 'undefined')
			{
				return data.DISPLAY;
			}

			if(typeof data.NAME != 'undefined')
			{
				return data.NAME;
			}

			if(typeof data.nameFormatted != 'undefined')
			{
				return BX.util.htmlspecialcharsback(data.nameFormatted); // socnetlogdest returns escaped name, we want unescaped
			}
		},
		getNSMode: function()
		{
			return 'group';
		}
	}
});

// for popup-based selectors
BX.Tasks.PopupItemSet = BX.Tasks.Util.ItemSet.extend({
	methods: {
		construct: function()
		{
			this.callConstruct(BX.Tasks.Util.ItemSet);

			this.vars.formShownBefore = false;
			this.vars.blockSelectorEvent = false;
			this.vars.temporalItems = false;

            this.instances.window = false;
		},

		openAddForm: function()
		{
			this.getSelector().then(function(selector){

				this.instances.window = BX.PopupWindowManager.create(this.getFullBxId('popup')+this.option('selectorCode'), this.getPopupAttachTo(), {
					autoHide : true,
					closeByEsc : true,
					content : this.getPickerContainer(),
					buttons : this.getPopupButtons()
				});
				this.instances.window.show();

				if(!this.vars.formShownBefore)
				{
					this.bindFormEvents();

					this.vars.formShownBefore = true;
				}

				if(selector && 'setFocus' in selector)
				{
					selector.setFocus();
				}

			}.bind(this), function(){
				BX.debug('unable to get selector');
			});
		},

		getPickerContainer: function()
		{
			return this.control('picker-content');
		},

		// just a dumb method, to be able to load selector with async call
		getSelector: function()
		{
			var p = new BX.Promise();
			p.resolve();

			return p;
		},

		getPopupAttachTo: function()
		{
			return this.scope();
		},

		bindFormEvents: function()
		{
		},

		getPopupButtons: function()
		{
			var btnDesc = this.getPopupButtonsDescription();

			var buttons = [];
			for(var k in btnDesc)
			{
				var btnClass = btnDesc[k].type == 'select' ? BX.PopupWindowButton : BX.PopupWindowButtonLink;

				buttons.push(new btnClass({
					text : btnDesc[k].text,
					className : btnDesc[k].type == 'select' ? "popup-window-button-accept" : "popup-window-button-link-cancel",
					events : {
						click : BX.delegate(btnDesc[k].action, this)
					}
				}));
			}

			return buttons;
		},

		getPopupButtonsDescription: function()
		{
			return [
				{
					text: BX.message("TASKS_COMMON_SELECT"),
					type: 'select',
					action: this.applySelectionChange
				},
				{
					text: BX.message("TASKS_COMMON_CANCEL"),
					type: 'cancel',
					action: this.discardSelectionChange
				}
			];
		},

		itemsChanged: function(list)
		{
			if(this.vars.blockSelectorEvent)
			{
				return;
			}

			this.vars.temporalItems = list;
		},

		getSelectionDelta: function()
		{
			var added = [];
			var deleted = [];

			// k may not be numeric

			for(var k in this.vars.items)
			{
				if(typeof this.vars.temporalItems[k] == 'undefined')
				{
					deleted.push(k);
				}
			}
			for(var k in this.vars.temporalItems)
			{
				if(typeof this.vars.items[k] == 'undefined')
				{
					added.push(k);
				}
			}

			return {added: added, deleted: deleted};
		},

		applySelectionChange: function()
		{
			if(this.vars.temporalItems != false)
			{
				var delta = this.getSelectionDelta();

				for(var k in delta.deleted)
				{
					this.deleteItem(delta.deleted[k]);
				}

				for(var k in delta.added)
				{
					var data = this.vars.temporalItems[delta.added[k]];
					this.addItem(data, {});
				}

				this.redraw();

				this.vars.temporalItems = false;
			}

			this.instances.window.close();
		},

		discardSelectionChange: function()
		{
			this.vars.temporalItems = false;
			this.instances.window.close();
		}
	}
});