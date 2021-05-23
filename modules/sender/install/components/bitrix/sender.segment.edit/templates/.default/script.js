;(function ()
{

	BX.namespace('BX.Sender.Connector');
	if (BX.Sender.Connector.Manager)
	{
		return;
	}

	var Page = BX.Sender.Page;
	var Helper = BX.Sender.Helper;


	/**
	 * Form.
	 *
	 */
	function Form(params)
	{
		this.node = params.node;
	}
	Form.prototype.getInputs = function (context)
	{
		var controls = this.node.elements;
		controls = BX.convert.nodeListToArray(controls);
		return controls.filter(this.checkInput.bind(this, context), this);

	};
	Form.prototype.checkInput = function (context, ctrl)
	{
		context = context || null;

		if(!ctrl || !ctrl.name || !BX.type.isString(ctrl.name))
		{
			return false;
		}

		if(ctrl.name.substring(0,11) !== 'CONNECTOR_S')
		{
			return false;
		}

		if (context && !context.contains(ctrl))
		{
			return false;
		}

		return !ctrl.disabled;
	};
	Form.prototype.getInputName = function (ctrl)
	{
		return ctrl.name;
	};
	Form.prototype.getInputValue = function (ctrl)
	{
		switch(ctrl.type.toLowerCase())
		{
			case 'text':
			case 'textarea':
			case 'password':
			case 'number':
			case 'hidden':
			case 'select-one':
				return ctrl.value;
				break;

			case 'file':
				break;
			case 'radio':
			case 'checkbox':
				if(ctrl.checked)
				{
					return ctrl.value;
				}
				break;
			case 'select-multiple':
				var multipleValues = [];
				for (var j = 0; j < ctrl.options.length; j++)
				{
					if (ctrl.options[j].selected)
					{
						multipleValues.push(ctrl.options[j].value);
					}
				}
				if (multipleValues.length > 0)
				{
					return multipleValues;
				}
				break;
			default:
				break;
		}

		return null;
	};
	Form.prototype.getFields = function (context)
	{
		var fields = {};
		var inputs = this.getInputs(context);
		for(var i = 0; i < inputs.length; i++)
		{
			var input = inputs[i];
			var name = this.getInputName(input);
			var value = this.getInputValue(input);

			if(BX.type.isString(fields[name]))
			{
				fields[name] = [fields[name]];
			}

			if(BX.type.isArray(fields[name]))
			{
				if(!BX.util.in_array(value, fields[name]))
				{
					fields[name].push(value);
				}
			}
			else
			{
				fields[name] = value;
			}
		}

		return fields;
	};


	/**
	 * Manager.
	 *
	 */
	function Manager()
	{

	}
	Manager.prototype.init = function (params)
	{
		this.list = [];
		this.groupId = params.groupId || 0;
		this.actionUri = params.actionUri || '';
		this.onlyConnectorFilters = params.onlyConnectorFilters;
		this.showContactSets = params.showContactSets;
		this.prettyDateFormat = params.prettyDateFormat;
		this.mess = params.mess || {patternTitle:"", newTitle: ""};
		this.availableConnectors = params.availableConnectors || [];
		this.context = BX(params.containerId);
		this.isFrame = params.isFrame || false;
		this.isSaved = params.isSaved || false;
		this.canViewConnData = params.canViewConnData || false;
		this.contactTileNameTemplate = params.contactTileNameTemplate || '';
		this.pathToResult = params.pathToResult || '';
		this.pathToContactList = params.pathToContactList || '';
		this.pathToContactImport = params.pathToContactImport || '';
		this.segmentTile = params.segmentTile || {};
		this.filterCounterTag = params.filterCounterTag || null;

		this.ajaxAction = new BX.AjaxAction(this.actionUri);
		this.form = new Form({node: this.context.querySelector('form')});
		new FilterListener({'manager': this});

		this.initUi();
		this.initItems();
		BX.addCustomEvent("SidePanel.Slider:onClose", this.actualizeSegment.bind(this));

		this.contactList = new ContactList({manager: this});
		Helper.hint.init(this.context);

		if (!this.ui.title.value.trim())
		{
			this.ui.title.value = Helper.replace(
				this.mess.patternTitle,
				{
					'name': this.mess.newTitle,
					'date': BX.date.format(this.prettyDateFormat)
				}
			);
		}

		Page.initButtons();

		if (this.isFrame)
		{
			Helper.titleEditor.init({'dataNode': this.ui.title});
		}

		if (this.isFrame && this.isSaved)
		{
			top.BX.onCustomEvent(top, 'sender-segment-edit-change', [this.segmentTile]);
			BX.Sender.Page.slider.close();
		}

		return this;
	};

	Manager.prototype.actualizeSegment = function (event)
	{
		var slider = event.getSlider();
		var _this = this;
		if(typeof slider.data.close === 'undefined' || slider.data.close === false)
		{
			this.ajaxAction.request({
				action: 'actualizeSegment',
				data: {
					'groupId': this.groupId
				},
				onsuccess: function()
				{
					BX.removeCustomEvent("SidePanel.Slider::onClose", _this.actualizeSegment);
					event.allowAction();
					slider.close();
					slider.data.close = true;
					setTimeout(function() {
						slider.destroy();
					}, 1);
				}
			});

			event.denyAction();
		}
	};

	Manager.prototype.initUi = function ()
	{
		this.ui = {
			counter: this.context.querySelector('[data-bx-counter]'),
			countInfo: this.context.querySelector('[data-bx-count-info]'),
			button: this.context.querySelector('[data-bx-button]'),
			list: this.context.querySelector('[data-bx-list]'),
			title: Helper.getNode('segment-title', this.context)
		};

		BX.unbindAll(this.ui.button);
		BX.bind(this.ui.button, 'click', this.showMenuAdd.bind(this));
	};
	Manager.prototype.initItems = function ()
	{
		var itemNodes = this.ui.list.querySelectorAll('[data-bx-item]');
		itemNodes = BX.convert.nodeListToArray(itemNodes);
		itemNodes.forEach(this.initItem.bind(this));
		if (this.onlyConnectorFilters)
		{
			this.availableConnectors.reverse().forEach(function (connectorData) {
				if (connectorData.ID === 'sender_contact_list')
				{
					return;
				}

				var hasSameCode = this.list.filter(function (item) {return connectorData.ID === item.getCode()}).length > 0;
				if (hasSameCode)
				{
					return;
				}

				this.createItem(connectorData.ID);
			}, this);
		}

		this.updateCounter();
	};
	Manager.prototype.getConnectorDataById = function (id)
	{
		var list = this.availableConnectors.filter(function (connectorData) {
			return connectorData.ID === id;
		});

		return (list[0] ? list[0] : null);
	};
	Manager.prototype.createItem = function (id)
	{
		var connectorData = this.getConnectorDataById(id);
		if (!connectorData)
		{
			return;
		}

		var isFilter = connectorData.IS_FILTER;
		var html = connectorData.FORM;

		var matches;
		var randomId;
		var filterId = connectorData.FILTER_ID;

		if (matches = html.match(/--filter--([^-]+)--/))
		{
			randomId = matches[1];
			if (this.getItemByFilterId(connectorData.ID + '_' + '--filter--' + randomId + '--'))
			{
				randomId = randomId + Math.floor(Math.random() * (10000 - 100 + 1)) + 100;
			}
			randomId = '--filter--' + randomId + '--';
			html = html.replace(/--filter--([^-]+)--/g, randomId);
			filterId = filterId.replace(/--filter--([^-]+)--/g, "%CONNECTOR_NUM%");
		}
		else
		{
			randomId = Math.floor(Math.random() * (10000 - 100 + 1)) + 100;
		}

		html = html.replace(new RegExp("%CONNECTOR_NUM%",'g'), randomId);

		html = this.getConnectorForm(
			{
				'%CONNECTOR_FILTER_ID%': filterId,
				'%CONNECTOR_NUM%': randomId,
				'%CONNECTOR_CODE%': connectorData.CODE,
				'%CONNECTOR_MODULE_ID%': connectorData.MODULE_ID,
				'%CONNECTOR_NAME%': BX.util.htmlspecialchars(connectorData.NAME),
				'%CONNECTOR_COUNT%': '0',
				'%CONNECTOR_COUNTER%': '',
				'%CONNECTOR_FORM%':  html,
				'%CONNECTOR_FILTER%': '',
				'%CONNECTOR_IS_RESULT_VIEWABLE%': connectorData.IS_RESULT_VIEWABLE
			},
			isFilter
		);

		var parsedHtml = BX.processHTML(html);
		var newParentElement = document.createElement('div');
		newParentElement.innerHTML = parsedHtml.HTML;

		var newConnectorNode = BX.findChild(newParentElement, {'tag': 'div'});
		var newConnectorNodeDisplay = newConnectorNode.style.display;
		newConnectorNode.style.display = 'none';

		this.ui.list.insertBefore(newConnectorNode, this.ui.list.firstChild);
		if (parsedHtml.SCRIPT.length>0)
		{
			var script;
			for(var i in parsedHtml['SCRIPT'])
			{
				if (!parsedHtml['SCRIPT'].hasOwnProperty(i))
				{
					continue;
				}

				script = parsedHtml['SCRIPT'][i];
				BX.evalGlobal(script.JS);
			}
		}

		var item = this.initItem(newConnectorNode);

		var easing = new BX.easing({
			duration : 500,
			start : { height : 0, opacity : 0 },
			finish : { height : 100, opacity: 100 },
			transition : BX.easing.transitions.quart,
			step : function(state){
				newConnectorNode.style.opacity = state.opacity/100;
				newConnectorNode.style.display = newConnectorNodeDisplay;
			},
			complete : function() {
			}
		});
		easing.animate();

		this.getCount(item);
	};

	Manager.prototype.extendWatch = function()
	{
		if(typeof BX.PULL !== 'undefined' && this.filterCounterTag !== null)
		{
			BX.PULL.extendWatch(this.filterCounterTag);
			window.setTimeout(BX.delegate(this.extendWatch, this), 60000);
		}
	};

	Manager.prototype.initItem = function (node)
	{
		var item = new Item({
			'caller': this,
			'context': node,
			'groupId': this.groupId,
			'code': node.getAttribute('data-code')
		});
		this.list.push(item);
		BX.addCustomEvent(item, 'remove', this.removeItem.bind(this, item));
		BX.addCustomEvent(item, 'change', BX.throttle(this.getCount.bind(this, item), 100));

		var self = this;

		if(typeof BX.PULL !== 'undefined')
		{
			BX.PULL.subscribe({
				type: BX.PullClient.SubscriptionType.Server,
				moduleId: 'sender',
				command: 'updateFilterCounter',
				callback: function (params) {
					if(
						item.groupId === params.groupId
						&& item.getFilterId() === params.filterId
					)
					{
						self.setCount(item, params);
					}
				}.bind(this)
			});

			this.extendWatch();
		}

		return item;
	};
	Manager.prototype.onMenuAddClick = function (id)
	{
		this.createItem(id);
		this.menuAdd.close();
	};
	Manager.prototype.showMenuAdd = function ()
	{
		if (this.menuAdd)
		{
			this.menuAdd.show();
			return;
		}

		var items = this.availableConnectors
			.filter(function (item) {
				return item.ID !== 'sender_contact_list';
			})
			.map(function (item) {
				return {
					id: item.ID,
					text: item.NAME,
					onclick: this.onMenuAddClick.bind(this, item.ID)
				};
			}, this);

		this.menuAdd = BX.PopupMenu.create(
			'sender-segment-edit-menu-add',
			this.ui.button,
			items,
			{
				autoHide: true,
				offsetLeft: 0,
				offsetTop: 0,
				//angle: {position: "top", offset: 42},
				events: {
					//onPopupClose : BX.delegate(this.onPopupClose, this)
				}
			}
		);

		this.menuAdd.show();
	};
	Manager.prototype.get = function (param)
	{
		this.actionUri = param.actionUri;
	};
	Manager.prototype.updateCounter = function ()
	{
		var cnt = 0;
		var counters = [];
		this.list.forEach(function (item) {
			cnt += item.getCount();

			item.getCounters().forEach(function (itemCounter) {
				var filtered = counters.filter(function (counter) {
					return counter.typeId === itemCounter.typeId;
				});
				if (filtered.length)
				{
					filtered[0].count += itemCounter.count;
				}
				else
				{
					counters.push(BX.clone(itemCounter));
				}
			});
		});


		this.ui.countInfo.textContent = counters.map(function (counter) {
			return counter.typeName + ' - ' + counter.count;
		}).join(', ');
		Helper.changeDisplay(this.ui.countInfo.previousElementSibling, counters.length > 0);
		this.ui.counter.textContent = cnt;
		Helper.changeDisplay(this.ui.counter, !cnt);
	};
	Manager.prototype.getConnectorForm = function (data, isFilter)
	{
		isFilter = isFilter || false;
		var templateNode = BX('connector-template' + (isFilter ? '-filter' : ''));
		var html = templateNode.innerHTML;

		for(var key in data)
		{
			if (!data.hasOwnProperty(key))
			{
				continue;
			}

			var value = data[key];
			if (BX.type.isString(value))
			{
				value = value.replace(new RegExp('\\$','g'), '$$$');
			}
			html = html.replace(new RegExp(key,'g'), value);
		}

		return html;
	};
	Manager.prototype.updateFilterData = function (filterId, callback)
	{
		this.ajaxAction.request({
			action: 'getFilterData',
			onsuccess: this.onFilterData.bind(this, filterId, callback),
			data: {
				'filterId': filterId,
				'groupId': this.groupId
			}
		});
	};
	Manager.prototype.onFilterData = function (filterId, callback, response)
	{
		if (!response.num)
		{
			return;
		}

		var item = this.getItemById(response.num);
		if (!item)
		{
			return;
		}

		this.setCount(item, response);
		item.flushFilterFields(response.data);

		if (callback)
		{
			callback.apply(this, []);
		}
	};
	Manager.prototype.getCount = function (item)
	{
		item.animateCounter(true, true);
		this.ajaxAction.request({
			action: 'getCount',
			onsuccess: this.setCount.bind(this, item),
			data: item.getFields()
		});
	};
	Manager.prototype.setCount = function (item, response)
	{
		response = response || {};

		item.animateCounter(false, true);

		if (typeof response.waiting !== 'undefined' && response.waiting)
		{
			item.showLoadingInfo();
		}
		else
		{
			item.setCount(response.count || {});
			this.updateCounter();

			if(typeof response.completed !== 'undefined' && !response.completed)
			{
				item.animateCounter(true, false);
			}
		}
	};
	Manager.prototype.getItemById = function (id)
	{
		var items = this.list.filter(function (item) {
			return item.getId() === id;
		});

		return items.length > 0 ? items[0] : null;
	};
	Manager.prototype.getItemByFilterId = function (filterId)
	{
		var items = this.list.filter(function (item) {
			return item.getFilterId() === filterId;
		});

		return items.length > 0 ? items[0] : null;
	};
	Manager.prototype.removeItem = function (item)
	{
		this.list = BX.util.deleteFromArray(this.list, this.list.indexOf(item));
		var easing = new BX.easing({
			duration : 300,
			start : { height : 100, opacity: 100 },
			finish : { height : 0, opacity : 0 },
			transition : BX.easing.transitions.quart,
			step : function(state){
				item.getContext().style.opacity = state.opacity/100;
			},
			complete : BX.proxy(function() {
				item.remove();
				this.updateCounter();
			}, this)
		});
		easing.animate();
	};


	/**
	 * Filter.
	 *
	 */
	function FilterListener(params)
	{
		this.manager = params.manager;

		this.init();
	}
	FilterListener.prototype.init = function ()
	{
		BX.addCustomEvent('BX.Filter.Search:input', this.onBeforeApplyFilter.bind(this));
		BX.addCustomEvent('BX.Main.Filter:beforeApply', this.onBeforeApplyFilter.bind(this));
		BX.addCustomEvent('BX.Main.Filter:apply', this.onApplyFilter.bind(this));
		BX.addCustomEvent("BX.Main.Filter:show", this.onFilterShow.bind(this));
		BX.addCustomEvent("BX.Main.Filter:blur", this.onFilterBlur.bind(this));
	};
	FilterListener.prototype.onBeforeApplyFilter = function (filterId)
	{
		var item = this.manager.getItemByFilterId(filterId);
		var filter = BX.Main.filterManager.getById(filterId)
		var dealCategory = filter.getField('DEAL_CATEGORY_ID');
		var hasValues = false;

		for(var id in filter.getFilterFieldsValues())
		{
			if(filter.getFilterFieldsValues().hasOwnProperty(id))
			{
				var value = filter.getFilterFieldsValues()[id];
				if(
					value !== 'exact' &&
					value !== 'NONE' &&
					value !== ''
				)
				{
					if(Array.isArray(value) && !value.length)
					{
						continue;
					}

					hasValues = true;
				}
			}
		}

		if(dealCategory && hasValues)
		{
			if(typeof dealCategory.options.ITEMS[0] !== 'undefined')
			{
				this.setDefaultValue(dealCategory, {0: dealCategory.options.ITEMS[0].VALUE});
			}
		}

		if (item)
		{
			item.animateCounter(true, true);
		}
	};

	FilterListener.prototype.setDefaultValue = function(field, value)
	{
		var container = field.parent.getFieldListContainer();
		Object.entries(value).forEach(function (data) {
			var fieldValue = data[1];

			var fieldNode = container.querySelector(
				"[data-name='"
					.concat(field.id, "'] [data-name='")
					.concat(field.id, "'], [data-name='")
					.concat(field.id, "'] [name='")
					.concat(field.id, "']"));

			if (fieldNode) {
				var dataValue = fieldNode.getAttribute('data-value');
				if(dataValue !== "[]")
				{
					return;
				}

				if (BX.Dom.hasClass(fieldNode, 'main-ui-multi-select')) {
					var items = BX.Dom.attr(fieldNode, 'data-items');

					if (BX.Type.isArray(items)) {
						var item = items.find(function (currentItem) {
							return currentItem.VALUE === fieldValue;
						});

						if (BX.Type.isPlainObject(item)) {
							BX.Dom.attr(fieldNode, 'data-value', item);
							var nameNode = fieldNode.querySelector('.main-ui-square-container');

							if (nameNode) {
								var squareNode =
									BX.create('span', {
										'props': {
											'className': 'main-ui-square'
										},
										'attrs': {
											'data-item': JSON.stringify(item)
										}
									});
								var squareNodeItem =
									BX.create('span', {
										'props': {
											'className': 'main-ui-square-item'
										}
									});
								var squareNodeRem =
									BX.create('span', {
										'props': {
											'className': 'main-ui-item-icon main-ui-square-delete'
										}
									});

								squareNodeItem.innerText = item.NAME;
								squareNode.append(squareNodeItem);
								squareNode.append(squareNodeRem);

								nameNode.append(squareNode);
							}
							var value = [item];
							fieldNode.setAttribute('data-value', JSON.stringify(value));
						}
					}
				}
			}
		});
	};
	FilterListener.prototype.onFilterData = function (filterId, promise)
	{
		var item = this.manager.getItemByFilterId(filterId);
		if (item)
		{
			item.animateCounter(false, true);
		}

		// resolve promise
		promise.fulfill();
	};
	FilterListener.prototype.onApplyFilter = function (id, data, ctx, promise, params)
	{
		//this.clearEmptyFilterFields(ctx);
		// disable promise auto resolving
		params.autoResolve = false;
		this.manager.updateFilterData(id, this.onFilterData.bind(this, id, promise));
	};
	FilterListener.prototype.getShowedFilterFields = function (filter)
	{
		return filter.getParam('FIELDS').filter(function (field) {
			var fieldNode = filter.presets.getField(field);
			if (!fieldNode)
			{
				return false;
			}

			return !filter.getFields().isFieldDelete(fieldNode);
		});
	};
	FilterListener.prototype.onFilterShow = function (filter)
	{
		if (this.getShowedFilterFields(filter).length === 0)
		{
			filter.restoreDefaultFields();
		}
	};
	FilterListener.prototype.onFilterBlur = function (filter)
	{
	};
	FilterListener.prototype.clearEmptyFilterFields = function (filter)
	{
		var values = filter.getFilterFieldsValues();
		var fields = this.getShowedFilterFields(filter).filter(function (field) {
			var name = field.NAME;

			switch (field.TYPE)
			{
				case 'DATE':
				case 'NUMBER':
					var subKeys = ['_datesel', '_numsel'];
					return Object.keys(field.VALUES).concat(subKeys).filter(function (key) {
						if (field.TYPE === 'NUMBER' && BX.util.in_array(key, subKeys))
						{
							return false;
						}

						var multiName = name + key;
						if (typeof (values[multiName]) === "undefined")
						{
							return false;
						}

						if (key === '_datesel' && values[multiName] === 'NONE')
						{
							return false;
						}

						return (values[multiName] !== "");
					}).length === 0;

				default:
					return (typeof (values[name]) === "undefined" || values[name] === ""  ||
						(typeof values[name] === "object" && values[name].hasOwnProperty('length') && !values[name].length));
			}
		});

		if (fields.length === 0)
		{
			return;
		}


		if (fields.length === filter.getParam('FIELDS').length)
		{
			return;
		}

		filter.presets.removeFields(fields);
	};


	/**
	 * Item.
	 *
	 */
	function Item(params)
	{
		this.code = params.code;
		this.caller = params.caller;
		this.context = params.context;
		this.groupId = params.groupId;

		this.init();
	}
	Item.prototype.init = function ()
	{
		this.ui = {
			remove: this.context.querySelector('[data-bx-item-remove]'),
			counter: this.context.querySelector('[data-bx-item-counter]'),
			countInfo: this.context.querySelector('[data-bx-item-count-info]'),
			resultView: this.context.querySelector('[data-bx-item-result-view]'),
			toggler: this.context.querySelector('[data-bx-item-toggler]'),
			close: this.context.querySelector('[data-bx-item-close]'),
			filter: this.context.querySelector('[data-bx-item-filter]')
		};

		BX.bind(this.ui.remove, 'click', this.onRemoveClick.bind(this));
		if (this.ui.toggler)
		{
			BX.bind(this.ui.toggler, 'click', this.toggleView.bind(this));
		}
		if (this.ui.close)
		{
			BX.bind(this.ui.close, 'click', this.toggleView.bind(this));
		}
		if (this.isResultViewable())
		{
			Helper.changeDisplay(this.ui.resultView, true);
			BX.bind(this.ui.resultView, 'click', this.viewResult.bind(this, null));
		}

		var counters = this.ui.countInfo.getAttribute('data-bx-item-count-info');
		if (counters)
		{
			try
			{
				counters = JSON.parse(counters);
			}
			catch (e)
			{
				counters = null;
			}

		}
		this.setCount(counters);

		this.caller.form.getInputs(this.context).forEach(this.listenInputChanges.bind(this));

		this.applyPreset();
		this.drawFilterFields();
		this.changeFilterPlaceholder();
	};
	Item.prototype.getId = function ()
	{
		return this.context.getAttribute('data-bx-item');
	};
	Item.prototype.getCode = function ()
	{
		return this.context.getAttribute('data-code');
	};
	Item.prototype.listenInputChanges = function (input)
	{
		BX.bind(input, 'change', BX.delegate(function() {
			BX.onCustomEvent(this, 'change', [this]);
		}, this));
	};
	Item.prototype.getFilterId = function ()
	{
		return this.context.getAttribute('data-bx-item-filter');
	};
	Item.prototype.getFilter = function ()
	{
		var filter = BX.Main.filterManager.getById(this.getFilterId());
		if (!filter || !(filter instanceof BX.Main.Filter))
		{
			return null;
		}

		return filter;
	};
	Item.prototype.applyPreset = function ()
	{
		var filter = this.getFilter();
		if (!filter)
		{
			return;
		}

		filter.disableAddPreset();

		var fields = this.getFilterFields();
		if (!fields.BX_PRESET_ID)
		{
			return;
		}

		setTimeout(function () {
			filter.getPreset().applyPreset(fields.BX_PRESET_ID);
		}, 100);
	};
	Item.prototype.flushFilterFields = function (fields)
	{
		if (!this.ui.filter)
		{
			return;
		}

		this.ui.filter.value = JSON.stringify(fields);
	};
	Item.prototype.getFilterFields = function ()
	{
		if (!this.ui.filter)
		{
			return {};
		}

		try
		{
			var fields = JSON.parse(this.ui.filter.value);
		}
		catch (e)
		{
			return {};
		}

		return BX.type.isPlainObject(fields) ? fields : {};
	};
	Item.prototype.drawFilterFields = function ()
	{
		var filter = this.getFilter();
		if (!filter)
		{
			return;
		}

		var fields = this.getFilterFields();
		if (fields.length === 0)
		{
			return;
		}

		// convert formats
		for(var key in fields)
		{
			if (!fields.hasOwnProperty(key))
			{
				continue;
			}

			// for multi-select
			if (BX.type.isArray(fields[key]))
			{
				fields[key] = fields[key].reduce(function(result, item, index) {
					result[index] = item;
					return result;
				}, {});
			}

			// for number and date
			if (BX.type.isPlainObject(fields[key]))
			{
				var values = fields[key];
				for(var parameterKey in values)
				{
					if (!values.hasOwnProperty(parameterKey))
					{
						continue;
					}

					if (!/[^\d]/.test(parameterKey))
					{
						continue;
					}

					fields[parameterKey] = values[parameterKey];
				}
			}
		}


		filter.getApi().setFields(fields);
	};
	Item.prototype.changeFilterPlaceholder = function ()
	{
		var filter = this.getFilter();
		if (!filter)
		{
			return;
		}

		var text = this.caller.mess.filterPlaceholder;
		var textCrmLead = this.caller.mess.filterPlaceholderCrmLead;
		var textCrmClient = this.caller.mess.filterPlaceholderCrmClient;
		if (textCrmLead && this.code === 'sender_crm_lead')
		{
			text = textCrmLead;
		}
		else if (textCrmClient && this.code === 'sender_crm_client')
		{
			text = textCrmClient;
		}

		filter.params["MAIN_UI_FILTER__PLACEHOLDER_DEFAULT"] = text;
		filter.params["MAIN_UI_FILTER__PLACEHOLDER_WITH_FILTER"] = text;
		filter.params["MAIN_UI_FILTER__PLACEHOLDER"] = text;
		filter.getSearch().adjustPlaceholder();
	};
	Item.prototype.getFields = function ()
	{
		return this.caller.form.getFields(this.context);
	};
	Item.prototype.toggleView = function ()
	{
		if (!this.isFormShown())
		{
			this.caller.list.forEach(function (item) {
				if (!item.isFormShown())
				{
					return;
				}
				item.toggleView();
			});
		}
		BX.toggleClass(this.context, 'sender-box-list-item-hidden');
	};
	Item.prototype.isFormShown = function ()
	{
		return !BX.hasClass(this.context, 'sender-box-list-item-hidden');
	};
	Item.prototype.isResultViewable = function ()
	{
		return (this.caller.canViewConnData && this.ui.resultView && this.context.getAttribute('data-result-viewable') === 'Y');
	};
	Item.prototype.viewResult = function (typeId)
	{
		if (!this.caller.canViewConnData)
		{
			return;
		}

		typeId = typeId || null;
		var parameters = {
			'code': this.getCode(),
			'fields': encodeURIComponent(JSON.stringify(this.getFilterFields()))
		};

		parameters.SENDER_RECIPIENT_TYPE_ID = typeId;
		parameters.apply_filter = 'Y';
		parameters.groupId = this.groupId;
		parameters.filterId = this.getFilterId();

		var uri = BX.util.add_url_param(this.caller.pathToResult, parameters);
		BX.SidePanel.Instance.open(uri, {cacheable: false});
	};
	Item.prototype.animateCounter = function (isAnimate, hideCounter)
	{
		Helper.changeClass(this.context, 'loading' + (!hideCounter ? '-partial' : ''), isAnimate);
		if (isAnimate && hideCounter)
		{
			this.setCount(null);
		}
	};
	Item.prototype.getContext = function ()
	{
		return this.context;
	};
	Item.prototype.setCount = function (count)
	{
		count = count || {};
		this.counters = count.counters || [];

		this.ui.counter.textContent = count.summary || 0;
		this.ui.countInfo.innerHTML = '';
		this.counters.filter(function (counter) {
			return counter.count > 0;
		}, this).map(function (counter) {
			var node = document.createElement('a');
			if (this.isResultViewable())
			{
				BX.addClass(node, 'sender-segment-counter-item');
				BX.bind(node, 'click', this.viewResult.bind(this, counter.typeId));
			}
			node.textContent = counter.typeName + ' - ' + counter.count;
			return node;
		}, this).forEach(function (node, i, list) {
			this.ui.countInfo.appendChild(node);
			if (list.length > i + 1)
			{
				this.ui.countInfo.appendChild(document.createTextNode(', '));
			}
		}, this);

		Helper.changeDisplay(this.ui.resultView, this.counters.length > 0 && this.isResultViewable());
		Helper.changeDisplay(this.ui.counter, count.summary <= 0);
	};
	Item.prototype.showLoadingInfo = function ()
	{
		this.ui.counter.textContent = BX.Loc.getMessage('SENDER_SEGMENT_SEARCH_INFORMATION');

		this.ui.countInfo.innerHTML = '';

		Helper.changeDisplay(this.ui.resultView, true);
		Helper.changeDisplay(this.ui.counter, true);
	};
	Item.prototype.getCounters = function ()
	{
		return this.counters;
	};
	Item.prototype.getCount = function ()
	{
		var count = parseInt(this.ui.counter.textContent);
		return isNaN(count) ? 0 : count;
	};
	Item.prototype.onRemoveClick = function (e)
	{
		e.preventDefault();
		BX.onCustomEvent(this, 'remove', [this]);
	};
	Item.prototype.remove = function ()
	{
		BX.unbindAll(this.ui.remove);
		BX.unbindAll(this.ui.toggler);
		BX.remove(this.context);
	};


	function ContactList(params)
	{
		this.manager = params.manager;
		this.init();
	}
	ContactList.prototype.init = function ()
	{
		var id = 'sender-segment-contacts';
		this.selector = BX.Sender.UI.TileSelector.getById(id);
		if (!this.selector)
		{
			throw new Error('Tile selector `' + id + '` not found.');
		}

		BX.addCustomEvent(this.selector, this.selector.events.buttonSelect, this.onButtonSelect.bind(this));
		BX.addCustomEvent(this.selector, this.selector.events.buttonSelectFirst, this.onButtonSelectFirst.bind(this));

		BX.addCustomEvent(this.selector, this.selector.events.containerClick, this.onButtonAdd.bind(this));
		BX.addCustomEvent(this.selector, this.selector.events.buttonAdd, this.onButtonAdd.bind(this));
		BX.addCustomEvent(this.selector, this.selector.events.tileClick, this.onTileClick.bind(this));
		BX.addCustomEvent(this.selector, this.selector.events.tileRemove, this.onTileRemove.bind(this));
		BX.addCustomEvent(this.selector, this.selector.events.tileAdd, this.onTileAdd.bind(this));

		BX.addCustomEvent(this.selector, this.selector.events.input, this.onInput.bind(this));
		BX.addCustomEvent(this.selector, this.selector.events.search, this.onSearch.bind(this));

		top.BX.addCustomEvent(top, 'BX.Sender.ContactImport::loaded', this.onContactImportLoaded.bind(this));
	};
	ContactList.prototype.onButtonSelect = function ()
	{
		this.selector.showSearcher(this.manager.mess.contactSearchTitle);
	};
	ContactList.prototype.onButtonSelectFirst = function ()
	{
		var selector = this.selector;
		this.manager.ajaxAction.request({
			action: 'getContactSets',
			onsuccess: function (data)
			{
				selector.setSearcherData(data.list || []);
			},
			onfailure: selector.hideSearcher.bind(selector),
			data: {}
		});
	};
	ContactList.prototype.onInput = function (value)
	{
	};
	ContactList.prototype.onSearch = function (value)
	{
	};
	ContactList.prototype.onTileAdd = function (tile)
	{
		this.setFields({'LIST_ID': tile.id || 0});
	};
	ContactList.prototype.onContactImportLoaded = function (listData)
	{
		var name = listData.NAME;
		if (!this.manager.showContactSets)
		{
			name = this.manager.contactTileNameTemplate.replace('%count%', listData.COUNT || 0);
		}

		var tile = this.getContactTile();
		if (tile)
		{
			this.selector.updateTile(tile, name);
		}
		else
		{
			this.selector.addTile(name, {}, listData.ID || 0);
		}

		//this.setFields({'LIST_ID': listData.ID || 0});
	};
	ContactList.prototype.setFields = function (fields)
	{
		var node = Helper.getNode('contact_list', this.manager.context);
		if (node)
		{
			node.value = BX.type.isPlainObject(fields) ? JSON.stringify(fields) : null;
		}
	};
	ContactList.prototype.getContactTile = function ()
	{
		var tiles = this.selector.getTiles();
		return tiles.length > 0 ? tiles[0] : null;
	};
	ContactList.prototype.onButtonAdd = function ()
	{
		var path = this.manager.pathToContactImport;
		var tile = this.getContactTile();
		if (tile)
		{
			path += path.indexOf('?') < 0 ? '?' : '&';
			path += 'listId=' + tile.id;
		}

		Page.open(path);
	};
	ContactList.prototype.onTileClick = function (tile)
	{
		var path = this.manager.pathToContactList;
		path += path.indexOf('?') < 0 ? '?' : '&';
		path += 'listId=' + tile.id;
		Page.open(path);
	};
	ContactList.prototype.onTileRemove = function ()
	{
		this.setFields(null);
	};


	BX.Sender.Connector.Item = Item;
	BX.Sender.Connector.Manager = new Manager();

})(window);