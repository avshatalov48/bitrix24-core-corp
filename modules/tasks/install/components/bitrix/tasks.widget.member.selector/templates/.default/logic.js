'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksWidgetMemberSelector != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksWidgetMemberSelector = BX.Tasks.Component.extend({
		sys: {
			code: 'tdp-mem-sel',
			types: []
		},
		methodsStatic: {
			instances: [],

			getInstance: function(name)
			{
				return BX.Tasks.Component.TasksWidgetMemberSelector.instances[name];
			},

			addInstance: function(name, obj)
			{
				BX.Tasks.Component.TasksWidgetMemberSelector.instances[name] = obj;
			}
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);
				BX.Tasks.Component.TasksWidgetMemberSelector.addInstance(this.id(), this);

				this.getSelector();

				if(this.option('inputSpecial'))
				{
					this.getSelector().bindEvent('change', this.onChanged.bind(this));
				}
			},

			onChanged: function(items)
			{
				var value = '';
				if(items[0])
				{
					value = this.getSelector().get(items[0]).id();
				}

				this.control('sole-input').value = value;
			},

			getSelector: function()
			{
				return this.subInstance('selector', function(){

					var options = {
						scope: this.scope(),
						hidePreviousIfSingleAndRequired: true,
						data: this.option('data'),
						max: this.option('max'),
						min: this.option('min'),
						nameTemplate: this.option('nameTemplate'),
						path: this.option('path'),
						preRendered: true,

						popupOffsetTop: 3,
						popupOffsetLeft: 40,

						readOnly: this.option('readOnly'),
						parent: this
					};

					var types = this.option('types');

					// todo: setting both 'user' and 'group' is not implemented
					// todo: setting 'depratment' is not implemented
					options.mode = types.USER ? 'user' : 'group';
					options.useAdd = !!types['USER.MAIL'] && this.option('modulesAvailable').mail;

					var selector = new this.constructor.ItemManager(options);

					// proxy 'change' event of the aggregated controller
					selector.bindEvent('change', function ProxyChangeEvent(){
						this.fireEvent('change', [arguments[0]]);
					}.bind(this));

					return selector;
				});
			},

			count: function()
			{
				return this.getSelector().count();
			},

			export: function()
			{
				return this.getSelector().exportItemData(true);
			},

			replaceItem: function(value, data)
			{
				this.getSelector().replaceItem(value, data);
			},

			value: function()
			{
				return this.getSelector().value();
			},

			replaceAll: function(data) {
				var selector = this.getSelector();
				selector.unload({
					itemFx: false, // no effects
					checkRestrictions: false // no restrictions: i know what i am doing, i am code, not a user
				});
				selector.load(data);
			},

			readOnly: function(flag)
			{
				this.getSelector().readonly(flag);
			}
		}
	});

	BX.Tasks.Component.TasksWidgetMemberSelector.ItemManager = BX.Tasks.UserItemSet.extend({
		sys: {
			code: 'tdp-mem-sel-is'
		},
		options: {
			controlBind: 'class',
			itemFx: 'horizontal',
			itemFxHoverDelete: true,
			prefixId: true,
			mode: 'all', // users, groups and departments selected
			path: {},

			// hacky flag, allows to hide previously selected item when picking a new one in (single mode (max == 1) and required mode (min > 0))
			hidePreviousIfSingleAndRequired: false
		},
		methods: {

			onSearchBlurred: function()
			{
				var emailUserPopup = BX('invite-email-email-user-popup');
				if (emailUserPopup !== null && emailUserPopup.style.display === 'block')
				{
					return;
				}

				if (this.callMethod(BX.Tasks.UserItemSet, 'onSearchBlurred'))
				{
					if (this.option('hidePreviousIfSingleAndRequired') && this.vars.constraint.min > 0)
					{
						this.restoreKept();
					}
				}
			},

			onSelectorItemSelected: function(data)
			{
				if(this.option('hidePreviousIfSingleAndRequired') && this.vars.constraint.min > 0)
				{
					this.vars.changed = true;
					var value = this.extractItemValue(data);

					if(!this.hasItem(value))
					{
						this.addItem(data);
						this.vars.toDelete = false;

						if(!this.checkCanAddItems()) // can not add new items anymore - close search form
						{
							this.instances.selector.close();
							this.onSearchBlurred();
						}
					}

					this.resetInput();
				}
				else
				{
					this.callMethod(BX.Tasks.UserItemSet, 'onSelectorItemSelected', arguments);
				}
			},

			// link clicked
			openAddForm: function()
			{
				if(this.option('hidePreviousIfSingleAndRequired')) // special behaviour
				{
					if(this.vars.constraint.min == 1 && this.vars.constraint.max == 1)
					{
						this.forceDeleteFirst();
					}
				}

				this.callMethod(BX.Tasks.UserItemSet, 'openAddForm');
			},

			// item "delete" cross clicked
			onItemDeleteByCross: function(value)
			{
				if(!this.callMethod(BX.Tasks.UserItemSet, 'onItemDeleteByCross', arguments))
				{
					if(this.option('hidePreviousIfSingleAndRequired')) // special behaviour
					{
						if(this.vars.constraint.min == 1 && this.count() == 1)
						{
							this.forceDeleteFirst();
							this.callMethod(BX.Tasks.UserItemSet, 'openAddForm');
						}
					}

					return false;
				}

				return true;
			},

			forceDeleteFirst: function()
			{
				var first = this.getItemFirst();
				if(first)
				{
					this.vars.toDelete = first.data();
					this.deleteItem(first.value(), {checkRestrictions: false});
				}
			},

			restoreKept: function()
			{
				if(this.vars.toDelete)
				{
					this.addItem(this.vars.toDelete, {checkRestrictions: false});
					this.vars.toDelete = false;
				}
			}
		}
	});

}).call(this);