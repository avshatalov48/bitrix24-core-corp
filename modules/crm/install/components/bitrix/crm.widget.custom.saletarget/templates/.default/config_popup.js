BX.namespace('BX.Crm.Widget.Custom.SaleTarget');
BX.Crm.Widget.Custom.SaleTarget.ConfigPopup = (function(BX)
{
	'use strict';

	var Template = BX.Crm.Widget.Custom.SaleTarget.Template;
	var Period = BX.Crm.Widget.Custom.SaleTarget.Period;
	var Target = BX.Crm.Widget.Custom.SaleTarget.Target;
	var Label = BX.Crm.Widget.Custom.SaleTarget.Label;
	var UserSelector = BX.Crm.Widget.Custom.SaleTarget.UserSelector;

	var Selector = function(config)
	{
		this.id = 'saletarget-selector-' + Math.random();
		this.controlNode = config.controlNode;
		this.displayNode = config.displayNode;
		this.parentPopup = config.parentPopup;
		this.menuPopup = null;
		this.listeners = config.events || {};

		this.values = config.values || [];

		this.createInput(config);

		if (config.value)
		{
			this.setValue(config.value, true);
		}

		this.bind();
	};
	Selector.prototype =
	{
		createInput: function(config)
		{
			this.input = null;
			if (config.form && config.name)
			{
				this.input = BX.create('input', {
					attrs: {
						type: 'hidden',
						name: config.name
					}
				});
				config.form.appendChild(this.input);
			}
		},
		bind: function()
		{
			BX.bind(this.controlNode, 'click', this.onControlClick.bind(this));
			if (this.parentPopup)
			{
				BX.addCustomEvent(this.parentPopup, 'onPopupClose', this.onParentPopupClose.bind(this));
			}
		},
		onControlClick: function()
		{
			var me = this, i, menuItems = [];

			for (i = 0; i < this.values.length; ++i)
			{
				menuItems.push({
					text: this.values[i]['name'],
					valueId: this.values[i]['id'],
					onclick: function(e, item)
					{
						this.popupWindow.close();
						me.setValue(item.valueId);
					}
				});
			}

			BX.PopupMenu.show(
				this.id,
				this.controlNode,
				menuItems,
				{
					autoHide: true,
					offsetLeft: 40,
					angle: { position: 'top'}
				}
			);
			this.menuPopup = BX.PopupMenu.currentItem;
		},
		setValues: function(values)
		{
			this.values = values;
			return this;
		},
		getValueById: function(id)
		{
			var value = null;
			for (var i = 0; i < this.values.length; ++i)
			{
				if (this.values[i]['id'] === id)
				{
					value = this.values[i];
					break;
				}
			}
			return value;
		},
		setValue: function(valueId, silent)
		{
			if (this.value !== valueId)
			{
				var oldId = this.value;
				var value = this.getValueById(valueId);
				if (value !== null)
				{
					if (silent || this.fire('beforeChange', [valueId, oldId]))
					{
						this.value = valueId;
						this.displayNode.textContent = value['shortName'] || value['name'];
						if (this.input)
						{
							this.input.value = valueId;
						}
						if (!silent)
						{
							this.fire('change', [valueId, oldId])
						}
					}
				}
			}

			return this;
		},
		getValue: function()
		{
			return this.value;
		},
		onParentPopupClose: function()
		{
			if (this.menuPopup)
			{
				this.menuPopup.close();
				this.menuPopup.destroy();
				this.menuPopup = null;
			}
		},
		show: function()
		{
			BX.show(this.controlNode);
			return this;
		},
		hide: function()
		{
			BX.hide(this.controlNode);
			return this;
		},
		fire: function(eventName, params)
		{
			var result = true;
			if (this.listeners && this.listeners[eventName])
			{
				if (this.listeners[eventName].apply(this, params) === false)
				{
					result = false;
				}
			}
			return result;
		}
	};

	var Configuration = function(data)
	{
		this.isDirty = false;

		this.type = data.type;
		this.period = BX.type.isPlainObject(data.period) ? new Period(data.period) : data.period;
		this.target = data.target;
		this.users = BX.type.isArray(data.users) ? data.users : [];
	};
	Configuration.prototype =
	{
		dirty: function()
		{
			this.isDirty = true;
		},
		getId: function()
		{
			return (new Period(this.period)).getId();
		},
		setType: function(type)
		{
			if (this.type !== type)
			{
				this.type = type;
				this.clearGoal();
				this.dirty();
			}
		},
		setTargetType: function(type)
		{
			if (this.target.type !== type)
			{
				this.target.type = type;
				this.dirty();
			}
		},
		setGoal: function(goalId, value)
		{
			if (typeof value === 'undefined' && BX.type.isPlainObject(goalId))
			{
				this.target.goal = BX.clone(goalId);
				this.dirty();
				return this;
			}

			value = parseInt(value);
			if (isNaN(value) || value < 0)
				value = 0;

			if (this.target.goal[goalId] !== value)
			{
				this.target.goal[goalId] = value;
				this.dirty();
			}
			return this;
		},
		getGoal: function(goalId)
		{
			var goal = parseInt(this.target.goal[goalId]);
			return goal > 0 ? goal : 0;
		},
		clearGoal: function(goalId)
		{
			if (typeof goalId === 'undefined')
			{
				this.target.goal = {};
				this.dirty();
			}
			else if (BX.type.isPlainObject(this.target.goal))
			{
				delete this.target.goal[goalId];
				this.dirty();
			}

			return this;
		},
		addUser: function(user)
		{
			this.users.push(user);
			if (this.type === 'USER')
			{
				this.setGoal(user.id, 0);
			}

			return this;
		},
		removeUser: function(userId)
		{
			for (var i = 0; i < this.users.length; ++i)
			{
				if (this.users[i]['id'] === userId)
				{
					if (this.type === 'USER')
					{
						this.clearGoal(this.users[i]['id']);
					}
					this.users.splice(i, 1);
					break;
				}
			}
		},
		serialize: function()
		{
			return {
				type: this.type,
				period_type: this.period.type,
				period_year: this.period.year,
				period_half: this.period.half,
				period_quarter: this.period.quarter,
				period_month: this.period.month,
				target_type: this.target.type,
				target_goal: this.target.goal
			};
		}
	};

	var ConfigPopup = function(widget, item, config)
	{
		this.id = 'saletarget-config-popup-' + BX.util.getRandomString(7);
		this.widget = widget;
		if (item.configuration.id > 0)
		{
			this.initConfigurationPeriod = (new Period(item.configuration.period));
		}
		else
		{
			this.initConfigurationPeriod = (new Period({
				type: Period.Type.Month,
				year: (new Date()).getFullYear(),
				month: (new Date()).getMonth() + 1
			})).next();
		}

		this.configurations = [];
		this.users = [];
		this.categories = config.categories || [];
	};

	ConfigPopup.prototype = {
		loadData: function(next)
		{
			if (this.loading)
			{
				return;
			}
			if (this.loaded)
			{
				if (next)
					next();
				return;
			}

			this.loading = true;

			var me = this;
			BX.ajax(
				{
					url: this.ajaxUrl || '/bitrix/components/bitrix/crm.widget.custom.saletarget/ajax.php',
					method: "POST",
					dataType: "json",
					data: {
						sessid: BX.bitrix_sessid(),
						site: BX.message('SITE_ID'),
						action: 'GET_CONFIGURATIONS'
					},
					onsuccess: function(response)
					{
						me.loading = false;
						if (response.success)
						{
							me.loaded = true;
							me.users = response.users;
							me.setConfigurations(response.configurations);
							me.setCurrentConfiguration(me.initConfigurationPeriod);
							if (next)
								next();
						}
						else
						{
							me.showAccessDeniedDialog(response);
						}
					},
					onfailure: function()
					{
						me.loading = false;
						window.alert(BX.message('CRM_WIDGET_SALETARGET_CONFIG_DENIED'));
					}
				}
			);
		},
		showAccessDeniedDialog: function(response)
		{
			if (response.admins.length > 0 && BX.getClass('BX.Intranet.NotifyDialog'))
			{
				var url = this.ajaxUrl || '/bitrix/components/bitrix/crm.widget.custom.saletarget/ajax.php';
				url = BX.util.add_url_param(url, {action: 'notify_admin', site: BX.message('SITE_ID')});

				var notifyDialog = new BX.Intranet.NotifyDialog({
					"listUserData": response.admins,
					"notificationHandlerUrl": url,
					"popupTexts": {
						"title": BX.message('CRM_WIDGET_SALETARGET_CONFIG_DENIED_TITLE'),
						"header": BX.message('CRM_WIDGET_SALETARGET_CONFIG_DENIED_HEADER'),
						"description": BX.message('CRM_WIDGET_SALETARGET_CONFIG_DENIED_DESCRIPTION'),
						"sendButton": BX.message('CRM_WIDGET_SALETARGET_CONFIG_DENIED_NOTIFY')
					}
				});
				notifyDialog.show();

				return;
			}
			window.alert(BX.message('CRM_WIDGET_SALETARGET_CONFIG_DENIED'));
		},
		setConfigurations: function(configurations)
		{
			for (var i = 0; i < configurations.length; ++i)
			{
				this.configurations.push(new Configuration(configurations[i]));
			}
		},
		setCurrentConfiguration: function(period)
		{
			var config = this.findConfiguration(period);
			if (!config)
			{
				var prev = this.findPreviousConfiguration(period);
				config = new Configuration({
					type: 'USER',
					period: period,
					target: {
						type: Target.Type.Sum,
						goal: {}
					},
					users: prev && prev.users.length ? prev.users: []
				});
				this.configurations.push(config);
			}

			this.configuration = config;
		},
		findConfiguration: function(period)
		{
			var id = period.getId();
			for (var i = 0; i < this.configurations.length; ++i)
			{
				if (this.configurations[i].getId() === id)
				{
					return this.configurations[i];
				}
			}
			return null;
		},
		findPreviousConfiguration: function(period)
		{
			var type = period.type, leftBorder = period.getLeftBorder(),
				foundBorder = 0, foundConfiguration = null;

			for (var i = 0; i < this.configurations.length; ++i)
			{
				if (this.configurations[i].period.type !== type)
					continue;
				var checkBorder = this.configurations[i].period.getLeftBorder();
				if (checkBorder >= leftBorder)
					continue;
				if (checkBorder > foundBorder)
				{
					foundBorder = checkBorder;
					foundConfiguration = this.configurations[i];
				}
			}
			return foundConfiguration;
		},
		show: function()
		{
			this.loadData(this.showPopup.bind(this));
		},
		showPopup: function()
		{
			var me = this;

			var template = new Template('widget-saletarget-config');
			this.template = template;

			var saveFn = function()
			{
				return this.saveConfigurations().then(function(response)
				{
					if (response.success)
					{
						//emulate widget save configuration event
						me.widget.onAfterConfigSave();
					}
					else if (response.errors)
					{
						window.alert(response.errors[0]);
					}

					return response;

				}, function(response)
				{
					if (response.errors)
					{
						window.alert(response.errors[0]);
					}
					return response;
				});
			}.bind(this);

			var popup = new BX.PopupWindow('crm-start-config', null, {
				closeIcon: true,
				draggable: true,
				titleBar: BX.message('CRM_WIDGET_SALETARGET_CONFIG_TITLE'),
				closeByEsc: true,
				content: template.get(),
				width: 530,
				buttons: [
					new BX.PopupWindowCustomButton({
						className: 'webform-small-button webform-small-button-accept',
						text: BX.message('JS_CORE_WINDOW_SAVE'),
						events : {
							click: function(){
								var popup = this.popupWindow,
									button = this.buttonNode;

								BX.addClass(button, 'webform-small-button-wait');

								saveFn().then(function(response)
									{
										if (response.success)
										{
											popup.close();
										}
										BX.removeClass(button, 'webform-small-button-wait');
									}, function()
									{
										BX.removeClass(button, 'webform-small-button-wait');
									}
								);
							}
						}
					}),
					new BX.PopupWindowButtonLink({
						className: "popup-window-button-link-cancel",
						text: BX.message('JS_CORE_WINDOW_CANCEL'),
						events : {
							click: function(){
								this.popupWindow.close()
							}
						}
					})
				],
				events: {
					onPopupClose: function(popup)
					{
						popup.destroy();
					}
				}
			});

			var showChangePeriodConfirmation = true;

			this.periodTypeSelector = new Selector({
				controlNode: template.get('period-type'),
				displayNode: template.get('period-type'),
				parentPopup: popup,
				values: Period.getTypeList(),
				events: {
					beforeChange: function(value)
					{
						if (showChangePeriodConfirmation && me.configuration.period.type !== value && me.configurations.length > 1)
						{
							if (!window.confirm(BX.message('CRM_WIDGET_SALETARGET_CONFIG_PERIOD_CHANGE_CONFIRMATION')))
							{
								return false;
							}
							showChangePeriodConfirmation = false;
						}

						me.onPeriodTypeChange(value);
					},
					change: this.onAfterPeriodChange.bind(this)
				},
				value: this.configuration.period.type
			});

			this.periodYearSelector = new Selector({
				controlNode: template.get('period-year'),
				displayNode: template.get('period-year-value'),
				parentPopup: popup,
				values: Period.getYearList(),
				value: parseInt(this.configuration.period.year),
				events:
					{
						change: this.onAfterPeriodChange.bind(this)
					}
			});

			this.periodHalfSelector = new Selector({
				controlNode: template.get('period-half'),
				displayNode: template.get('period-half-value'),
				parentPopup: popup,
				values: Period.getHalfList(),
				value: parseInt(this.configuration.period.half) || 1,
				events:
					{
						change: this.onAfterPeriodChange.bind(this)
					}
			});

			this.periodQuarterSelector = new Selector({
				controlNode: template.get('period-quarter'),
				displayNode: template.get('period-quarter-value'),
				parentPopup: popup,
				values: Period.getQuarterList(),
				value: parseInt(this.configuration.period.quarter) || 1,
				events:
					{
						change: this.onAfterPeriodChange.bind(this)
					}
			});

			this.periodMonthSelector = new Selector({
				controlNode: template.get('period-month'),
				displayNode: template.get('period-month-value'),
				parentPopup: popup,
				values: Period.getMonthList(),
				value: parseInt(this.configuration.period.month) || 1,
				events:
					{
						change: this.onAfterPeriodChange.bind(this)
					}
			});

			this.targetTypeSelector = new Selector({
				controlNode: template.get('target-type'),
				displayNode: template.get('target-type-value'),
				parentPopup: popup,
				values: Target.getTypeList(),
				value: this.configuration.target.type,
				events:
					{
						change: function(value)
						{
							me.configuration.setTargetType(value);
						}
					}
			});

			this.viewTypeSelector = new Selector({
				controlNode: template.get('view-type'),
				displayNode: template.get('view-type-value'),
				parentPopup: popup,
				values: [
					{id: 'USER', name: BX.message('CRM_WIDGET_SALETARGET_CONFIG_VIEW_TYPE_USER')},
					{id: 'CATEGORY', name: BX.message('CRM_WIDGET_SALETARGET_CONFIG_VIEW_TYPE_CATEGORY')},
					{id: 'COMPANY', name: BX.message('CRM_WIDGET_SALETARGET_CONFIG_VIEW_TYPE_COMPANY')}
				],
				events: {
					change: function(value)
					{
						me.configuration.setType(value);
						me.refreshConfigurationView();
					}
				}
			});

			this.onPeriodTypeChange(this.configuration.period.type);
			this.viewTypeSelector.setValue(this.configuration.type);

			var copyControlNode = template.get('copy-configuration');
			BX.bind(copyControlNode, 'click', this.onCopyConfiguration.bind(this));

			this.configPopup = popup;
			this.configTemplate = template;
			popup.show();

			return true;
		},
		onPeriodTypeChange: function(type)
		{
			switch (type)
			{
				case Period.Type.Year:
					this.periodHalfSelector.hide();
					this.periodQuarterSelector.hide();
					this.periodMonthSelector.hide();
					break;
				case Period.Type.Half:
					this.periodHalfSelector.show();
					this.periodQuarterSelector.hide();
					this.periodMonthSelector.hide();
					break;
				case Period.Type.Quarter:
					this.periodHalfSelector.hide();
					this.periodQuarterSelector.show();
					this.periodMonthSelector.hide();
					break;
				case Period.Type.Month:
					this.periodHalfSelector.hide();
					this.periodQuarterSelector.hide();
					this.periodMonthSelector.show();
					break;
			}
		},
		refreshConfigViewType: function(viewType)
		{
			var viewTypeContent = this.template.get('view-type-content');
			viewTypeContent.innerHTML = '';

			var contentTemplate;

			if (viewType === 'COMPANY')
			{
				contentTemplate = this.getConfigCompanyTemplate();
			}
			else if (viewType === 'CATEGORY')
			{
				contentTemplate = this.getConfigCategoryTemplate();
			}
			else if (viewType === 'USER')
			{
				contentTemplate = this.getConfigUserTemplate();
			}

			viewTypeContent.appendChild(contentTemplate.get());

			/* show/hide link to categories configs */
			BX[viewType === 'CATEGORY' ? 'show' : 'hide'](this.template.get('categories-link'));
		},
		onAfterPeriodChange: function()
		{
			var period = new Period({
				type: this.periodTypeSelector.getValue(),
				year: this.periodYearSelector.getValue(),
				half: this.periodHalfSelector.getValue(),
				quarter: this.periodQuarterSelector.getValue(),
				month: this.periodMonthSelector.getValue()
			});

			this.setCurrentConfiguration(period);
			this.refreshConfigurationView();

			var copyControlNode = this.template.get('copy-configuration');
			copyControlNode.textContent = ' ';

			var previousConfiguration = this.findPreviousConfiguration(period);
			if (previousConfiguration)
			{
				copyControlNode.textContent = Label.copyPeriod(period.type);
			}
		},
		refreshConfigurationView: function()
		{
			this.targetTypeSelector.setValue(this.configuration.target.type);
			this.viewTypeSelector.setValue(this.configuration.type, true);
			this.refreshConfigViewType(this.configuration.type);
		},
		onCopyConfiguration: function()
		{
			var copyConfiguration = this.findPreviousConfiguration(this.configuration.period);
			if (copyConfiguration)
			{
				this.configuration.setType(copyConfiguration.type);
				this.configuration.setTargetType(copyConfiguration.target.type);
				this.configuration.setGoal(copyConfiguration.target.goal);
				if (copyConfiguration.type === 'USER')
				{
					this.configuration.users = copyConfiguration.users;
				}

				this.refreshConfigurationView();
			}
		},
		getConfigCompanyTemplate: function()
		{
			var template = new Template('widget-saletarget-config-company');
			var goal = this.configuration.getGoal('COMPANY');

			this.decorateGoalInput(template.get('company-target'), 'COMPANY', goal);

			return template;
		},

		getConfigCategoryTemplate: function()
		{
			var me = this, template = new Template('widget-saletarget-config-category');

			template.loop('categories', this.categories, function(itemTpl, category)
			{
				itemTpl.get('category-name').textContent = category.name;

				var goal = me.configuration.getGoal(category.id);
				me.decorateGoalInput(itemTpl.get('category-target'), category.id, goal);
			});

			return template;
		},

		getConfigUserTemplate: function()
		{
			var me = this, template = new Template('widget-saletarget-config-user');
			var users = this.configuration.users;
			if (!users.length && this.users.length > 0)
			{
				users = this.users;
			}

			//users
			template.loop('users', users, function(itemTpl, user, appended)
			{
				var goal = me.configuration.getGoal(user.id);

				itemTpl.get('user-name').textContent = user.name;
				itemTpl.get('user-title').textContent = user.title;
				if (user.photo)
				{
					itemTpl.get('user-photo').style.background = 'url("'+user.photo+'")';
				}

				if (!user.active)
				{
					BX.addClass(itemTpl.get(), itemTpl.get().getAttribute('data-inactive-class'));
				}

				me.decorateGoalInput(itemTpl.get('user-target'), user.id, goal);

				BX.bind(itemTpl.get('user-remove'), 'click', function()
				{
					me.configuration.removeUser(user.id);
					BX.addClass(itemTpl.get(), itemTpl.get().getAttribute('data-remove-class'));
					setTimeout(function()
					{
						BX.remove(itemTpl.get());
					}, 250);
				});

				if (appended)
				{
					var baseNode = itemTpl.get();
					BX.addClass(baseNode, baseNode.getAttribute('data-new-class'))
				}
			});

			new UserSelector({
				bindTo: template.get('user-add'),
				selected: users,
				addCallback: function(user)
				{
					for (var i = 0; i < users.length; ++i)
					{
						if (parseInt(users[i]['id']) === user['id'])
						{
							return;
						}
					}
					me.configuration.addUser(user);
					template.loopAppend('users', user)
				},
				parentPopup: this.configPopup
			});

			return template;
		},
		decorateGoalInput: function(node, goalId, value)
		{
			node.setAttribute('name', 'target_goal['+goalId+']');
			node.value = value > 0 ? Label.number(value) : '';
			BX.bind(node, 'bxchange', this.onGoalInputChange.bind(this, node, goalId));
		},
		onGoalInputChange: function(inputNode, goalId)
		{
			var goal = parseInt(inputNode.value.replace(/[^0-9]+/g, ''));
			if (isNaN(goal) || goal < 0)
				goal = 0;

			inputNode.value = goal > 0 ? Label.number(goal) : '';
			this.configuration.setGoal(goalId, goal);
		},
		saveConfigurations: function()
		{
			var data = [];
			for (var i = 0; i < this.configurations.length; ++i)
			{
				if (this.configurations[i].isDirty)
				{
					data.push(this.configurations[i].serialize());
				}
			}

			var promise = new BX.Promise();

			if (!data.length)
			{
				promise.fulfill({success: true});
				return promise;
			}

			BX.ajax(
				{
					url: this.ajaxUrl || '/bitrix/components/bitrix/crm.widget.custom.saletarget/ajax.php',
					method: "POST",
					dataType: "json",
					data: {
						sessid: BX.bitrix_sessid(),
						site: BX.message('SITE_ID'),
						action: 'save_configurations',
						configurations: data,
						period_type: this.periodTypeSelector.getValue()
					},
					onsuccess: function(response)
					{
						promise.fulfill(response);
					},
					onfailure: function()
					{
						promise.fulfill({success: false, errors: [BX.message('CRM_WIDGET_SALETARGET_CONFIG_SAVE_ERROR')]});
					}
				}
			);

			return promise;
		}
	};

	return ConfigPopup;
})(window.BX || window.top.BX);