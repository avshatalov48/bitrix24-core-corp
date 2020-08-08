BX.namespace('BX.Crm.Widget.Custom');

BX.Crm.Widget.Custom.SaleTarget = (function(BX)
{
	'use strict';

	var Template = function(name)
	{
		this.loopData = {};
		this.rootNode = BX.type.isDomNode(name) ? BX.clone(name) : this.getTemplateNode(name);
	};
	Template.prototype =
	{
		getTemplateNode: function(name)
		{
			var tpl = window.document.querySelector('[data-template="'+ name +'"]');
			var node = BX.create('div', {html: tpl.innerHTML});
			this.replaceIncludes(node);

			return node;
		},

		getTemplateAttribute: function(name, attribute)
		{
			var tpl = window.document.querySelector('[data-template="'+ name +'"]');
			return tpl.getAttribute('data-' + attribute);
		},

		replaceIncludes: function(targetNode)
		{
			var includes = targetNode.querySelectorAll('[data-include]');
			for (var i = 0; i < includes.length; ++i)
			{
				var templateName = includes[i].getAttribute('data-include');
				includes[i].innerHTML = '';
				includes[i].appendChild(this.getTemplateNode(templateName));
			}
		},
		loop: function(loopName, data, loopFn)
		{
			var include = this.rootNode.querySelector('[data-loop="'+loopName+'"]');
			if (!include)
			{
				return false;
			}

			var copyNode = include.firstElementChild;
			this.loopData[loopName] = {itemNode: copyNode, loopFn: loopFn};
			BX.cleanNode(include);
			for (var i = 0; i < data.length; ++i)
			{
				var itemTpl = new Template(copyNode);
				if (loopFn(itemTpl, data[i]) !== false)
				{
					include.appendChild(itemTpl.get());
				}
			}
			return true;
		},
		loopAppend: function(loopName, item)
		{
			var include = this.rootNode.querySelector('[data-loop="'+loopName+'"]');
			if (!include || !this.loopData[loopName])
			{
				return false;
			}
			var itemTpl = new Template(this.loopData[loopName]['itemNode']);
			this.loopData[loopName]['loopFn'](itemTpl, item, true);
			include.appendChild(itemTpl.get());
			return true;
		},
		applyIf: function(conditionName, conditionValue)
		{
			conditionValue = conditionValue.toString();
			var nodes = this.rootNode.querySelectorAll('[data-if^="'+conditionName+'="]');
			for (var i = 0; i < nodes.length; ++i)
			{
				var value = (nodes[i].getAttribute('data-if')).split('=')[1];
				if (value !== conditionValue)
				{
					BX.remove(nodes[i]);
				}
			}
		},

		get: function(name)
		{
			if (!name)
				return this.rootNode;

			return this.rootNode.querySelector('[data-role="'+name+'"]');
		},
		getAll: function(name)
		{
			return Array.prototype.slice.call(
				this.rootNode.querySelectorAll('[data-role="'+name+'"]')
			);
		}
	};

	var Label =
	{
		getMonths: function() {
			return {
				1: BX.message('CRM_WIDGET_SALETARGET_MONTH_JAN'), 2: BX.message('CRM_WIDGET_SALETARGET_MONTH_FEB'),
				3: BX.message('CRM_WIDGET_SALETARGET_MONTH_MAR'), 4: BX.message('CRM_WIDGET_SALETARGET_MONTH_APR'),
				5: BX.message('CRM_WIDGET_SALETARGET_MONTH_MAY'), 6: BX.message('CRM_WIDGET_SALETARGET_MONTH_JUN'),
				7: BX.message('CRM_WIDGET_SALETARGET_MONTH_JUL'), 8: BX.message('CRM_WIDGET_SALETARGET_MONTH_AUG'),
				9: BX.message('CRM_WIDGET_SALETARGET_MONTH_SEP'), 10: BX.message('CRM_WIDGET_SALETARGET_MONTH_OCT'),
				11: BX.message('CRM_WIDGET_SALETARGET_MONTH_NOV'), 12: BX.message('CRM_WIDGET_SALETARGET_MONTH_DEC')
			}
		},
		getDaysOfMonth: function()
		{
			return {
				1: BX.message('CRM_WIDGET_SALETARGET_DAY_OF_JAN'), 2: BX.message('CRM_WIDGET_SALETARGET_DAY_OF_FEB'),
				3: BX.message('CRM_WIDGET_SALETARGET_DAY_OF_MAR'), 4: BX.message('CRM_WIDGET_SALETARGET_DAY_OF_APR'),
				5: BX.message('CRM_WIDGET_SALETARGET_DAY_OF_MAY'), 6: BX.message('CRM_WIDGET_SALETARGET_DAY_OF_JUN'),
				7: BX.message('CRM_WIDGET_SALETARGET_DAY_OF_JUL'), 8: BX.message('CRM_WIDGET_SALETARGET_DAY_OF_AUG'),
				9: BX.message('CRM_WIDGET_SALETARGET_DAY_OF_SEP'), 10: BX.message('CRM_WIDGET_SALETARGET_DAY_OF_OCT'),
				11: BX.message('CRM_WIDGET_SALETARGET_DAY_OF_NOV'), 12: BX.message('CRM_WIDGET_SALETARGET_DAY_OF_DEC')
			}
		},
		getQuarters: function()
		{
			return {
				1: BX.message('CRM_WIDGET_SALETARGET_QUARTER_1'), 2: BX.message('CRM_WIDGET_SALETARGET_QUARTER_2'),
				3: BX.message('CRM_WIDGET_SALETARGET_QUARTER_3'), 4: BX.message('CRM_WIDGET_SALETARGET_QUARTER_4')
			}
		},
		getHalfs: function()
		{
			return {
				1: BX.message('CRM_WIDGET_SALETARGET_HALF_YEAR_1'),
				2: BX.message('CRM_WIDGET_SALETARGET_HALF_YEAR_2')
			}
		},
		getDealPluralRules: function()
		{
			return [
				BX.message('CRM_WIDGET_SALETARGET_DEAL_PLURAL_1'),
				BX.message('CRM_WIDGET_SALETARGET_DEAL_PLURAL_2'),
				BX.message('CRM_WIDGET_SALETARGET_DEAL_PLURAL_3')
			]
		},
		dealPlural: function(quantity)
		{
			quantity = parseInt(quantity);
			var labelIndex = 0;
			if (quantity > 20)
				quantity = (quantity % 10);

			if (quantity === 1)
				labelIndex = 0;
			else if (quantity > 1 && quantity < 5)
				labelIndex = 1;
			else
				labelIndex = 2;
			return this.getDealPluralRules()[labelIndex];
		},
		month: function(date)
		{
			var month = date.getMonth() + 1;
			var months = this.getMonths();
			return months[month] ? months[month] : '';
		},
		day: function(date)
		{
			var day = date.getDate(),
				month = date.getMonth() + 1;

			var days = this.getDaysOfMonth();
			return (days[month] ? days[month] : '').replace('#NUM#', day.toString());
		},
		quarter: function(date)
		{
			var q = 1, month = date.getMonth();
			if (month > 8) q = 4;
			else if (month > 5) q = 3;
			else if (month > 2) q = 2;

			return this.getQuarters()[q];
		},
		halfYear: function(date)
		{
			var month = date.getMonth();
			var h = month < 6 ? 1 : 2;
			return this.getHalfs()[h];
		},
		number: function(value, opt)
		{
			var dec = opt ? opt['DEC_POINT'] : '';
			var tho = opt ? opt['THOUSANDS_SEP'] : ' ';
			return BX.util.number_format(value, 0, dec, tho);
		},
		target: function(value, type, opt)
		{
			if (type === Target.Type.Quantity)
			{
				return this.number(value, opt) + ' <span>'+this.dealPlural(value)+'</span>';
			}
			value = this.number(value, opt);
			var result = '';

			if (opt['FORMAT_STRING'].indexOf('#') === 0)
			{
				result = value + '<span>' + opt['FORMAT_STRING'].slice(1) + '</span>';
			}
			else
			{
				result = '<span>' + opt['FORMAT_STRING'].slice(0, -1) + '</span>' + value;
			}

			return result;
		},
		copyPeriod: function(periodType)
		{
			switch (periodType)
			{
				case Period.Type.Year:
					return BX.message('CRM_WIDGET_SALETARGET_CONFIG_COPY_Y');
				break;
				case Period.Type.Half:
					return BX.message('CRM_WIDGET_SALETARGET_CONFIG_COPY_H');
				break;
				case Period.Type.Quarter:
					return BX.message('CRM_WIDGET_SALETARGET_CONFIG_COPY_Q');
				break;
				case Period.Type.Month:
					return BX.message('CRM_WIDGET_SALETARGET_CONFIG_COPY_M');
				break;
			}
			return BX.message('CRM_WIDGET_SALETARGET_CONFIG_COPY_PERIOD');
		}
	};

	var Period = function(config)
	{
		this.type = config.type;
		this.month = config.month ? parseInt(config.month) : null;
		this.year = config.year ? parseInt(config.year) : null;
		this.half = config.half ? parseInt(config.half) : null;
		this.quarter = config.quarter ? parseInt(config.quarter) : null;

		this.calculate();
	};

	Period.Type = {
		Year: 'Y',
		Half: 'H',
		Quarter: 'Q',
		Month: 'M'
	};

	Period.getTypeList = function()
	{
		return [
			{id: 'M', name: BX.message('CRM_WIDGET_SALETARGET_CONFIG_PERIOD_TYPE_M')},
			{id: 'Q', name: BX.message('CRM_WIDGET_SALETARGET_CONFIG_PERIOD_TYPE_Q')},
			{id: 'H', name: BX.message('CRM_WIDGET_SALETARGET_CONFIG_PERIOD_TYPE_H')},
			{id: 'Y', name: BX.message('CRM_WIDGET_SALETARGET_CONFIG_PERIOD_TYPE_Y')}
		];
	};

	Period.getYearList = function()
	{
		var start = 2017; //SaleTarget widget release year
		var q = (new Date()).getFullYear() - start + 5;
		var list = [];
		for (var i = 0; i <= q; ++i)
		{
			list.push({id: start + i, name: (start + i).toString()})
		}
		return list;
	};

	Period.getHalfList = function()
	{
		return [
			{id: 1, shortName: 'I', name: BX.message('CRM_WIDGET_SALETARGET_HALF_YEAR_1')},
			{id: 2, shortName: 'II', name: BX.message('CRM_WIDGET_SALETARGET_HALF_YEAR_2')}
		];
	};
	Period.getQuarterList = function()
	{
		return [
			{id: 1, shortName: 'I', name: BX.message('CRM_WIDGET_SALETARGET_QUARTER_1')},
			{id: 2, shortName: 'II', name: BX.message('CRM_WIDGET_SALETARGET_QUARTER_2')},
			{id: 3, shortName: 'III', name: BX.message('CRM_WIDGET_SALETARGET_QUARTER_3')},
			{id: 4, shortName: 'IV', name: BX.message('CRM_WIDGET_SALETARGET_QUARTER_4')}
		];
	};

	Period.getMonthList = function()
	{
		return [
			{id: 1, name: BX.message('CRM_WIDGET_SALETARGET_MONTH_JAN')},
			{id: 2, name: BX.message('CRM_WIDGET_SALETARGET_MONTH_FEB')},
			{id: 3, name: BX.message('CRM_WIDGET_SALETARGET_MONTH_MAR')},
			{id: 4, name: BX.message('CRM_WIDGET_SALETARGET_MONTH_APR')},
			{id: 5, name: BX.message('CRM_WIDGET_SALETARGET_MONTH_MAY')},
			{id: 6, name: BX.message('CRM_WIDGET_SALETARGET_MONTH_JUN')},
			{id: 7, name: BX.message('CRM_WIDGET_SALETARGET_MONTH_JUL')},
			{id: 8, name: BX.message('CRM_WIDGET_SALETARGET_MONTH_AUG')},
			{id: 9, name: BX.message('CRM_WIDGET_SALETARGET_MONTH_SEP')},
			{id: 10, name: BX.message('CRM_WIDGET_SALETARGET_MONTH_OCT')},
			{id: 11, name: BX.message('CRM_WIDGET_SALETARGET_MONTH_NOV')},
			{id: 12, name: BX.message('CRM_WIDGET_SALETARGET_MONTH_DEC')}
		];
	};

	Period.prototype =
	{
		getId: function()
		{
			var id = Period.Type.Year + this.year + this.type;
			switch (this.type)
			{
				case Period.Type.Month:
					id += this.month;
					break;
				case Period.Type.Quarter:
					id += this.quarter;
					break;
				case Period.Type.Half:
					id += this.half;
					break;
			}
			return id;
		},
		calculate: function()
		{
			switch (this.type)
			{
				case Period.Type.Month:
					this.calculateMonth();
					break;
				case Period.Type.Quarter:
					this.calculateQuarter();
					break;
				case Period.Type.Half:
					this.calculateHalf();
					break;
				default:
					this.calculateYear();
			}
		},
		calculateMonth: function()
		{
			this.startDate = new Date(this.year, this.month - 1, 1);
			this.endDate = new Date(this.year, this.month, 0);
		},
		calculateQuarter: function()
		{
			var firstMonth = (this.quarter-1)*3;
			this.startDate = new Date(this.year, firstMonth, 1);
			this.endDate = new Date(this.year, firstMonth + 3, 0);
		},
		calculateHalf: function()
		{
			var firstMonth = this.half === 1 ? 0 : 6;
			this.startDate = new Date(this.year, firstMonth, 1);
			this.endDate = new Date(this.year, firstMonth + 6, 0);
		},
		calculateYear: function()
		{
			this.startDate = new Date(this.year, 0, 1);
			this.endDate = new Date(this.year, 11, 31);
		},
		getLabel: function()
		{
			var dateLabel, date = this.startDate;
			switch (this.type)
			{
				case Period.Type.Month:
					dateLabel = Label.month(date);
					break;
				case Period.Type.Half:
					dateLabel = Label.halfYear(date);
					break;
				case Period.Type.Quarter:
					dateLabel = Label.quarter(date);
					break;
			}

			return (dateLabel ? dateLabel + ' ' : '') + this.year;
		},
		getDayPositionPercent: function(date)
		{
			date.setHours(0, 0, 0, 0);
			var check = date.getTime();
			var left = this.startDate.getTime();
			var right = this.endDate.getTime();

			if (date < left || date > right) return -1;

			return ((check - left) / (right - left)) * 100;
		},
		next: function()
		{
			switch (this.type)
			{
				case Period.Type.Month:
					this.month += 1;
					if (this.month > 12)
					{
						this.month = 1;
						++this.year;
					}
				break;
				case Period.Type.Half:
					this.half += 1;
					if (this.half > 2)
					{
						this.half = 1;
						++this.year;
					}
				break;
				case Period.Type.Quarter:
					this.quarter += 1;
					if (this.quarter > 4)
					{
						this.quarter = 1;
						++this.year;
					}
				break;
				default:
					++this.year;
			}
			this.calculate();
			return this;
		},
		getLeftBorder: function()
		{
			return this.startDate.getTime() / 1000;
		},
		getRightBorder: function()
		{
			return this.endDate.getTime() / 1000;
		}
	};

	var Target = function(type, period, goal, current)
	{
		this.type = type;
		this.period = period;
		this.goal = parseInt(goal);
		this.current = parseInt(current);

		this.calculate();
	};

	Target.Type = {
		Sum: 'S',
		Quantity: 'Q'
	};

	Target.getTypeList = function()
	{
		return [
			{id: Target.Type.Sum, name: BX.message('CRM_WIDGET_SALETARGET_CONFIG_TARGET_TYPE_S')},
			{id: Target.Type.Quantity, name: BX.message('CRM_WIDGET_SALETARGET_CONFIG_TARGET_TYPE_Q')}
		];
	};

	Target.prototype =
	{
		calculate: function()
		{
			this.remaining = this.goal > this.current ? this.goal - this.current : 0;
			this.completedPercent = this.goal > 0 && this.current > 0 ? Math.floor((this.current / this.goal) * 100) : 0;
			this.progressPercent = Math.min(100, this.completedPercent);
		},
		append: function(target)
		{
			this.goal += target.goal;
			this.current += target.current;
			this.calculate();
			return this;
		}
	};

	return {
		useAutoHeight: function()
		{
			return true;
		},
		prepareButtons: function(widget)
		{
			var buttons = BX.CrmCustomWidget.superclass.prepareButtons.apply(widget);

			if (widget._data["attributes"]["isConfigurable"] === true)
			{
				buttons['intitle_config'] = BX.create("SPAN", {
					attrs: {className: "crm-widget-settings-text"},
					text: BX.message('CRM_WIDGET_SALETARGET_CONFIG_LABEL'),
					events: {
						click: this.openConfigDialog.bind(this, widget, null)
					}
				});
			}
			return buttons;
		},
		createContentNode: function(widget, item)
		{
			var tpl = new Template('widget-saletarget-main'),
				configuration = item.configuration;

			if (item.isNew)
			{
				this.changeConfiguration(null, widget, item);
				return BX.create('div');
			}

			var currencyFormat = widget.getPanel().getCurrencyFormat();
			var period = new Period(configuration.period);
			var totalTarget = new Target(
				configuration.target.type,
				period,
				Math.max(0, configuration.target.totalGoal),
				item.totalCurrent
			);

			tpl.get('current-period').textContent = period.getLabel();
			tpl.get('total-target').innerHTML = Label.target(totalTarget.goal, totalTarget.type, currencyFormat);
			tpl.get('total-complete').innerHTML = Label.target(totalTarget.current, totalTarget.type, currencyFormat);
			tpl.get('total-remaining').innerHTML = Label.target(totalTarget.remaining, totalTarget.type, currencyFormat);

			tpl.get('total-progress-percent').textContent = Math.abs(totalTarget.completedPercent);

			tpl.applyIf('view-type', configuration.type);

			this.dealCategories = BX.parseJSON(tpl.getTemplateAttribute('widget-saletarget-main', 'categories'));

			if (configuration.type === 'COMPANY')
			{
				this.createCompanyContentNode(tpl, widget, item);
			}
			else if (configuration.type === 'CATEGORY')
			{
				this.createCategoryContentNode(tpl, widget, item);
			}
			else if (configuration.type === 'USER')
			{
				this.createUserContentNode(tpl, widget, item);
			}

			tpl.getAll('today').forEach(function(node)
			{
				node.textContent = Label.day(new Date());
			});
			tpl.getAll('first-day').forEach(function(node)
			{
				node.textContent = Label.day(period.startDate);
			});
			tpl.getAll('last-day').forEach(function(node)
			{
				node.textContent = Label.day(period.endDate);
			});

			var todayPosition = Math.floor(period.getDayPositionPercent(new Date()));
			tpl.getAll('progress-point').forEach(function(node)
			{
				if (todayPosition > -1)
				{
					node.style.left = todayPosition + '%';
					if (todayPosition <= 2 && node.getAttribute('data-left-class'))
					{
						BX.addClass(node, node.getAttribute('data-left-class'));
					}
					else if (todayPosition >= 98 && node.getAttribute('data-right-class'))
					{
						BX.addClass(node, node.getAttribute('data-right-class'));
					}
				}
				else
				{
					BX.remove(node);
				}
			});
			tpl.getAll('progress-line').forEach(function(node)
			{
				node.style.width = totalTarget.progressPercent + '%';
			});
			tpl.getAll('progress-total').forEach(function(node)
			{
				node.textContent = totalTarget.completedPercent;
			});

			if (totalTarget.completedPercent > 95)
			{
				tpl.getAll('progress').forEach(function(node)
				{
					BX.addClass(node, node.getAttribute('data-more-class'));
				});
			}

			if (item.previousConfigurationId > 0)
			{
				BX.bind(
					tpl.get('previous-period'),
					'click',
					this.changeConfiguration.bind(this, item.previousConfigurationId, widget, item)
				);
			}
			else
			{
				BX.remove(tpl.get('previous-period'));
			}
			if (item.nextConfigurationId > 0)
			{
				BX.bind(
					tpl.get('next-period'),
					'click',
					this.changeConfiguration.bind(this, item.nextConfigurationId, widget, item)
				);
			}
			else
			{
				BX.remove(tpl.get('next-period'));
			}

			return tpl? tpl.get() : BX.create('div', {text: 'Invalid widget configuration.'});
		},
		changeConfiguration: function(configId, widget, item)
		{
			var me = this;

			if (widget._contentWrapper.firstElementChild)
			{
				widget._contentWrapper.firstElementChild.style.opacity = .3;
			}

			var ajaxData = {
				sessid: BX.bitrix_sessid(),
				site: BX.message('SITE_ID'),
				action: 'get_configuration',
				configuration_id: configId
			};

			if (configId === null)
			{
				ajaxData.action = 'get_current_configuration';
				delete ajaxData.configuration_id;
			}

			BX.ajax(
				{
					url: this.ajaxUrl || '/bitrix/components/bitrix/crm.widget.custom.saletarget/ajax.php',
					method: "POST",
					dataType: "json",
					data: ajaxData,
					onsuccess: function(response)
					{
						delete item.isNew;
						item.configuration = response.configuration;
						item.current = response.current;
						item.nextConfigurationId = response.nextConfigurationId;
						item.previousConfigurationId = response.previousConfigurationId;
						item.totalCurrent = response.totalCurrent;

						widget._contentWrapper.innerHTML = '';
						widget._contentWrapper.appendChild(
							me.createContentNode(widget, item)
						);
					},
					onfailure: function()
					{
						if (widget._contentWrapper.firstElementChild)
						{
							widget._contentWrapper.firstElementChild.style.opacity = 1;
						}
					}
				}
			);
		},
		createCompanyContentNode: function(template, widget, item)
		{
			return template;
		},
		createCategoryContentNode: function(template, widget, item)
		{
			var configuration = item.configuration;
			var currencyFormat = widget.getPanel().getCurrencyFormat();

			var period = new Period(configuration.period);

			//categories
			template.loop('categories', this.dealCategories, function(itemTpl, category)
			{
				var goal = configuration.target.goal[category['id']] || 0;
				var current = item.current[category['id']] || 0;

				if (goal <= 0)
				{
					return false;
				}

				var target = new Target(configuration.target.type, period, goal, current);

				BX.bind(itemTpl.get('category-row'), 'click', function(row)
				{
					BX.toggleClass(row, row.getAttribute('data-open-class'));
					BX.toggleClass(this, this.getAttribute('data-open-class'));
				}.bind(itemTpl.get('category-target-details'), itemTpl.get('category-row')));

				itemTpl.get('category-name').textContent = category.name;
				itemTpl.get('category-progress-line').style.width = target.progressPercent + '%';
				itemTpl.get('category-progress-line-value').textContent = target.completedPercent;
				if (target.completedPercent > 95)
				{
					BX.addClass(
						itemTpl.get('category-progress'),
						itemTpl.get('category-progress').getAttribute('data-more-class')
					);
				}
				itemTpl.get('category-target').innerHTML = Label.target(target.goal, target.type, currencyFormat);

				itemTpl.get('category-target-current').innerHTML = Label.target(target.current, target.type, currencyFormat);
				itemTpl.get('category-target-remaining').innerHTML = Label.target(target.remaining, target.type, currencyFormat);

				itemTpl.get('category-target-effective').textContent = Math.abs(target.completedPercent);
			});

			return template;
		},
		createUserContentNode: function(template, widget, item)
		{
			var configuration = item.configuration;
			var currencyFormat = widget.getPanel().getCurrencyFormat();
			var period = new Period(configuration.period);
			var isAlone = configuration.users.length < 2;

			//users
			template.loop('users', configuration.users, function(itemTpl, user)
			{
				var goal = configuration.target.goal[user['id']] || 0;
				var current = item.current[user['id']] || 0;

				if (goal <= 0)
				{
					return false;
				}

				var target = new Target(configuration.target.type, period, goal, current);

				BX.bind(itemTpl.get('user-row'), 'click', function(row)
				{
					BX.toggleClass(row, row.getAttribute('data-open-class'));
					BX.toggleClass(this, this.getAttribute('data-open-class'));
				}.bind(itemTpl.get('user-target-details'), itemTpl.get('user-row')));

				itemTpl.get('user-name').textContent = user.name;
				itemTpl.get('user-title').textContent = user.title;
				if (user.photo)
				{
					itemTpl.get('user-photo').style.background = 'url("'+user.photo+'")';
				}

				if (!user.active)
				{
					BX.addClass(itemTpl.get('user-row'), itemTpl.get('user-row').getAttribute('data-inactive-class'));
				}

				itemTpl.get('user-progress-line').style.width = target.progressPercent + '%';
				itemTpl.get('user-progress-line-value').textContent = target.completedPercent;
				if (target.completedPercent > 95)
				{
					BX.addClass(
						itemTpl.get('user-progress'),
						itemTpl.get('user-progress').getAttribute('data-more-class')
					);
				}
				itemTpl.get('user-target').innerHTML = Label.target(target.goal, target.type, currencyFormat);

				itemTpl.get('user-target-current').innerHTML = Label.target(target.current, target.type, currencyFormat);
				itemTpl.get('user-target-remaining').innerHTML = Label.target(target.remaining, target.type, currencyFormat);

				itemTpl.get('user-target-effective').textContent = Math.abs(target.completedPercent);

				if (isAlone)
				{
					BX.addClass(
						itemTpl.get('user-row'),
						itemTpl.get('user-row').getAttribute('data-open-class')
					);
					BX.addClass(
						itemTpl.get('user-target-details'),
						itemTpl.get('user-target-details').getAttribute('data-open-class')
					);
				}
			});

			return template;
		},
		openConfigDialog: function(widget, item)
		{
			if (!item)
			{
				item = BX.type.isArray(widget._data["items"]) && widget._data["items"].length > 0
					? widget._data["items"][0] : null;
			}

			(new BX.Crm.Widget.Custom.SaleTarget.ConfigPopup(widget, item, {categories: this.dealCategories})).show();
		},
		Template: Template,
		Period: Period,
		Target: Target,
		Label: Label
	};
})(window.BX || window.top.BX);