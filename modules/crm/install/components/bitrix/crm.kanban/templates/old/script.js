if (typeof(BX.CrmKanbanHelper) === 'undefined')
{
	BX.CrmKanbanHelper = function(settings)
	{
		this._error = '';
		this._popup_name = 'kanban_dialog';
		this._delayStack = settings.DELAY_BETWEEN_LOADING || 25;//seconds
		this._type = settings.ENTITY_TYPE || '';
		this._gridId = settings.GRID_ID || '';
		this._ajaxPath = settings.AJAX_PATH || '/bitrix/components/bitrix/crm.kanban/ajax.php';
		this._data = settings.DATA || {};
		this._data.container = settings.CONTAINER || document.body;
		this._currency = settings.CURRENCY || '';
		this._extra = settings.EXTRA || [];
		this._more_fields = settings.MORE_FIELDS || [];
		this._more_fields_new = null;
		this._popup = null;
		this._entityId = 0;
		this._entityLastId = 0;
		this.settings = {
			path_column_edit: settings.PATH_COLUMN_EDIT || '/crm/configs/status/',
			show_activity: settings.SHOW_ACTIVITY === 'Y',
			access_config_perms: settings.ACCESS_CONFIG_PERMS === 'Y'
		};

		BX.addCustomEvent('onPullEvent-im', BX.proxy(this._pullEventHandler, this));
		BX.addCustomEvent('onPullEvent-crm', BX.proxy(this._pullEventHandler, this));
		BX.addCustomEvent('onCrmActivityTodoChecked', BX.proxy(this._activityCheckHandler, this));
		BX.addCustomEvent('onPopupClose', BX.proxy(this._onClosePopup, this));
		BX.addCustomEvent('BX.Main.Filter:apply', BX.proxy(this._applyFilter, this));
		BX.addCustomEvent('BX.CrmEntityCounterPanel:applyFilter', BX.proxy(this._applyFilterCounter, this));

		setInterval(BX.proxy(this._loadStack, this), this._delayStack * 1000);
	};

	BX.CrmKanbanHelper.prototype =
	{
		getEntityType: function()
		{
			return this._type;
		},
		getKanban: function()
		{
			return this._kanban;
		},
		getMoreFields: function()
		{
			if (this._more_fields_new === null)
			{
				this._more_fields_new = [];
				for (var i=0; i<this._more_fields.length; i++)
				{
					if (this._more_fields[i].new === 1)
					{
						this._more_fields_new.push(this._more_fields[i]);
					}
				}
			}
			return this._more_fields_new;
		},
		getSettings: function(code)
		{
			return this.settings[code];
		},
		openActivityItems: function(id)
		{
			this._entityId = id;
			this._showPopup(
							'',
							{onAfterPopupShow: BX.proxy(this._loadActivities, this)}
					);
		},
		moveState: function(id, newState, oldState, params)
		{
			var oldColumnItems = this._kanban.getColumn(oldState).items;
			var params = params || {};
			if (oldColumnItems.length > 1)
			{
				params.old_status_lastid = oldColumnItems[oldColumnItems.length-1].id;
			}
			this._loadJSON({
				entity_id: id,
				prev_entity_id: this._kanban.prevItem(id, newState),
				status: newState,
				status_params: params || {},
				action: 'status'
			});
		},
		setLastId: function(id)
		{
			id = parseInt(id);
			if (id > this._entityLastId)
			{
				this._entityLastId = id;
			}
		},
		loadPage: function(column, page)
		{
			this._loadJSON({
				column: column,
				page: page,
				action: 'page'
			});
		},
		_addFade: function (fade)
		{
			if(fade === true)
			{
				this._data.container.classList.add('crm-kanban-grid-search');
			}
			else
			{
				this._data.container.classList.remove('crm-kanban-grid-search');
			}
		},
		_applyFilter: function(filterId, values, filterInstance)
		{
			this._addFade(true);
			this._loadJSON({
				action: 'get',
				clear: true
			});
		},
		_applyFilterCounter: function(sender, eventArgs)
		{
			setTimeout(
				BX.delegate(
					function() {
						var fields = { ACTIVITY_COUNTER: eventArgs['counterTypeId'] };
						var filter = BX.Main.filterManager.getById(this._gridId);
						var api = filter.getApi();
						api.setFields(fields);
						api.apply();
					},
					this
				), 0
			);
			eventArgs['cancel'] = true;
		},
		_showPopup: function(title, events)
		{
			if (true || this._popup === null)
			{
				this._popup = new BX.PopupWindow(this._popup_name, BX.proxy_context, {
					closeIcon : false,
					autoHide: true,
					overlay: false,
					className: 'crm-kanban-popup-plan',
					closeByEsc : true,
					contentColor: 'white',
					angle: true,
					offsetLeft: 15,
					events: events
				});
			}
			var popupPreLoader =  "<div class=\"crm-kanban-user-loader-item\"><div class=\"crm-kanban-loader\"><svg class=\"crm-kanban-circular\" viewBox=\"25 25 50 50\"><circle class=\"crm-kanban-path\" cx=\"50\" cy=\"50\" r=\"20\" fill=\"none\" stroke-width=\"1\" stroke-miterlimit=\"10\"/></svg></div></div>";
			this._popup.setContent(popupPreLoader);
			this._popup.show();
		},
		_onClosePopup: function(popup, event)
		{
			if (popup.uniquePopupId === this._popup_name)
			{
				BX.cleanNode(popup.contentContainer);
			}

			if(popup.bindElement && (popup.bindElement.tagName == 'SPAN'))
			{
				var itemParentId = popup.bindElement.getAttribute('parent-id');
				var itemBlock = kanban.querySelector('[data-id="' + itemParentId  + '"]');

				if(itemBlock)
				{
					itemBlock.classList.remove('crm-kanban-deal-item-show')
				}
			}
		},
		_loadActivities: function()
		{
			var _this = this;
			var params = {
				entity_id: this._entityId,
				entity_type: this._type,
				action: 'activities'
			};
			BX.ajax.get(this._ajaxPath, params, function(data) {
					_this._popup.setContent(data);
					_this._popup.adjustPosition();
				});
		},
		_loadStack: function()
		{
			this._loadJSON({
				min_entity_id: this._entityLastId,
				action: 'get',
				show: true
			});
		},
		_loadJSON: function(params)
		{
			var _this = this;

			params.entity_type = this._type;
			params.sessid = BX.bitrix_sessid();
			params.extra = this._extra;
			BX.ajax.loadJSON(this._ajaxPath, params, function(data){

				BX.CrmKanbanGrid.prototype.counterTotalPrice();

				if (typeof data !== 'undefined')
				{
					var error = data.error || [];
					var items = data.items || [];
					var columns = data.columns || [];

					if (error.length > 0)
					{
						// alert(error + "\n" + BX.message('CRM_KANBAN_RELOAD_PAGE'));
					}
					else
					{
						if(params.clear === true)
						{
							if(items.length == 0)
							{
								kanban.classList.add('crm-kanban-empty-mode');
							}
							else
							{
								kanban.classList.remove('crm-kanban-empty-mode');
							}

							var currentItems = _this._kanban.items;
							for (var key in currentItems)
							{
								_this._kanban.getColumn(currentItems[key].columnId).removeItem(currentItems[key]);
							}
						}

						for (var i = 0; i < items.length; i++)
						{
							var itemExist = _this._kanban.getItem(items[i].id);
							if (!itemExist || params.clear === true)
							{
								_this._kanban.addItem(items[i], _this._kanban.getColumn(items[i].columnId).items[0]);
							}
							if (typeof params.page === 'undefined' && (typeof params.status_params === 'undefined' || typeof params.status_params.old_status_lastid === 'undefined'))
							{
								if(_this._kanban.getColumn(items[i].columnId) !== null)
								{
									var columnItems = _this._kanban.getColumn(items[i].columnId).items;
									_this._kanban.moveItemToColumFilter(items[i].id, items[i].columnId, columnItems.length > 0 ? columnItems[0].id : null, items[i].modifyByAvatar);
									if (params.show === true)
									{
										_this._kanban.moveItemToColum(items[i].id, items[i].columnId, columnItems.length > 0 ? columnItems[0].id : null, true);
									}
									else
									{
										_this._kanban.moveItemToColumFilter(items[i].id, items[i].columnId, columnItems.length > 0 ? columnItems[0].id : null, items[i].modifyByAvatar);
									}
								}
							}
						}
						_this._kanban.counterTotalPrice(columns);
					}
					_this._addFade(false);
				}
			});
		},
		_pullEventHandler: function(command, params)
		{
			if (command === 'activity_add' &&
				params.OWNER_TYPE_NAME === this._type &&
				params.COMPLETED !== 'Y'
			)
			{
				BX.CrmKanbanItem.changeActCount(params.OWNER_ID, 1);
			}

			if (command === 'kanban_add' || command === 'kanban_update')
			{
				var item = params;
				var itemExist = this._kanban.getItem(item.id);
				var column = this._kanban.getColumn(item.columnId);
				var columnItems = column.items;
				var beforeItem = columnItems.length > 0 ? columnItems[0].id : null;
				var columns = this._kanban.columns;
				var newColumns = [];

				if (!itemExist)
				{
					item.columnColor = column.color;
					this._kanban.addItem(item, beforeItem);
				}
				else if (itemExist.columnId === item.columnId)
				{
					return;
				}

				for (var key in columns)
				{
					var count = parseInt(columns[key].count);
					var total = parseFloat(columns[key].total);
					if (itemExist && itemExist.columnId === key)
					{
						count--;
						total = total - parseFloat(item.price);
					}
					else if (item.columnId === key)
					{
						count++;
						total = total + parseFloat(item.price);
					}
					columns[key].count = count;
					columns[key].total = total;
					columns[key].total_format = BX.Currency.currencyFormat(total, this._currency, true);
					newColumns.push({
						id: columns[key].id,
						price: columns[key].price,
						columnId: key,
						count: count,
						total: total,
						total_format: columns[key].total_format
					});
				}

				this._kanban.moveItemToColumShow(item.id, item.columnId, beforeItem, item.modifyByAvatar);
				this._kanban.counterTotalPrice(newColumns);
			}
		},
		_activityCheckHandler: function(activityId, ownerId, ownerTypeId)
		{
			BX.CrmKanbanItem.changeActCount(ownerId, -1);
		}
	};
	BX.CrmKanbanHelper.instance = null;
	BX.CrmKanbanHelper.create = function(settings)
	{
		if (BX.CrmKanbanHelper.instance === null)
		{
			var instance = new BX.CrmKanbanHelper(settings);
			BX.CrmKanbanHelper.instance = instance;
			instance._kanban = new BX.CrmKanbanGrid(instance._data);
			instance._kanban.draw();
		}
		return BX.CrmKanbanHelper.instance;
	};
	BX.CrmKanbanHelper.getInstance = function()
	{
		return BX.CrmKanbanHelper.instance;
	};
};

if (typeof(BX.CrmKanbanGrid) === 'undefined')
{
	BX.CrmKanbanGrid = function(options)
	{
		this.columns = Object.create(null);
		this.columns_sort = {};
		this.items = Object.create(null);
		this.dropzones = Object.create(null);
		this.container = options.container;
		this.dragger = new BX.CrmKanbanDragDrop(this);
		this.loadData(options);
		this.timer = null;
		this.kanban = null;
	};

	BX.CrmKanbanGrid.prototype =
	{

		setWidth: function ()
		{
			var kanbanPadding = null;
			var kanbanWidth = null;
			var dropZone = null;

			setTimeout(function ()
			{
				dropZone = document.querySelector('.crm-kanban-dropzone');
				kanbanPadding = parseInt(getComputedStyle(kanban.parentNode).paddingLeft);
				kanbanWidth = document.documentElement.clientWidth - BX.pos(kanban.parentNode).left - 64;
				kanban.parentNode.style.width = kanbanWidth + 'px';
				kanban.style.width = (kanbanWidth - (kanbanPadding * 2)) + 'px';
				dropZone.style.width = 	kanbanWidth + 'px';
				kanban.style.left = '';
			},0);

			setTimeout(this.resize, 500);
		},

		setHeight: function ()
		{
			var kanbanPadding = parseInt(getComputedStyle(kanban.parentNode).paddingLeft);

			if(kanban.parentNode.getBoundingClientRect().top >= 0)
			{
				var kanbanHeight = document.documentElement.clientHeight - kanban.getBoundingClientRect().top;
				kanban.style.height = kanbanHeight + 'px';
				kanban.style.top = '';
				kanban.parentNode.style.minHeight = (kanbanHeight + kanbanPadding * 2) + 'px';
			}

			if(kanban.parentNode.getBoundingClientRect().bottom >= document.documentElement.clientHeight)
			{
				setTimeout(function ()
				{
					if(kanban.offsetHeight !== (document.documentElement.clientHeight - kanban.getBoundingClientRect().top))
					{
						kanban.style.height = (document.documentElement.clientHeight - kanban.getBoundingClientRect().top) + 'px';
					}
				},200)
			}
		},

		resize: function (fixed)
		{
			var kanbanPadding = parseInt(getComputedStyle(kanban.parentNode).paddingLeft);
			var kanbanHeight = null;
			var dropZone = document.querySelector('.crm-kanban-dropzone');
			dropZone.style.left = BX.pos(kanban.parentNode).left + 'px';

			if(kanban.parentNode.getBoundingClientRect().top >= 0)
			{
				kanbanHeight = document.documentElement.clientHeight - kanban.getBoundingClientRect().top - 1;
				kanban.style.height = kanbanHeight + 'px';
				kanban.parentNode.style.minHeight = document.documentElement.clientHeight + 'px';
				kanban.style.left = '';
				kanban.style.top = '';
				kanban.classList.remove('crm-kanban-grid-fixed');
			}
			else
			{
				kanban.classList.add('crm-kanban-grid-fixed');
				kanban.style.left = (BX.pos(kanban.parentNode.parentNode).left + kanbanPadding) + 'px';
				kanban.style.top = kanbanPadding + 'px';
				kanban.style.height = '';

				if(kanban.parentNode.getBoundingClientRect().bottom <= document.documentElement.clientHeight)
				{
				}
				else
				{
					kanban.style.minHeight = (document.documentElement.clientHeight - kanbanPadding) + 'px';
					dropZone.style.bottom = '';
				}
			}

		},

		prevItem: function(id, newState) {

			var column = this.getColumn(newState);
			if (column)
			{
				var items = column.items || [];
				var c = items.length;
				if (c > 1)
				{
					for (var i=0; i<c-1; i++)
					{
						if (items[i+1].id === id)
						{
							return items[i].id;
						}
					}
				}
			}
			return 0;
		},

		counterTotalPrice: function (newColumns)
		{

			var columns = this.columns;

			if (typeof newColumns === 'undefined')
			{
				for (var key in columns)
				{
					setPrice(columns[key].total, columns[key].layout.summary, columns[key].count, columns[key].layout.total, columns[key].total_format, columns[key]);
				}
			}
			else
			{
				for (var key = 0; key < newColumns.length; key++)
				{
					if (columns[newColumns[key].id])
					{
						setPrice(newColumns[key].total, columns[newColumns[key].id].layout.summary, newColumns[key].count, columns[newColumns[key].id].layout.total, newColumns[key].total_format, columns[newColumns[key].id]);
					}
				}
			};

			function setPrice(priceTotal, priceLayout, countTotal, countLayout, total_format, column)
			{
				var countTotal = countTotal;
				var countLayout = countLayout;
				var priceTotal = priceTotal;
				var priceTotalLayout = priceLayout;
				var priceTotalStep;
				var priceAttr = priceTotalLayout.getAttribute("data-total");
				var priceData = +priceAttr;

				countLayout.innerHTML = countTotal;

				if(column.items.length < +countTotal)
				{
					column.layout.items.appendChild(column.layout.loadMore);
					column.layout.loadMore.classList.remove('crm-kanban-loadmore-show');
				}

				if (priceData !== null)
				{
					if (priceData > priceTotal)
					{
						priceTotalStep = (priceData - priceTotal) / 20;
					}
					else
					{
						priceTotalStep = (priceTotal - priceData) / 20;
					}
				}
				else
				{
					priceTotalStep = priceTotal / 20;
				}

				// animation
				if (priceTotal != 0)
				{
					function scroll(val, el, timeout, step, start)
					{
						val = parseInt(val);
						i = 0;
						if (start != null)
						{
							var i = +start;
						}
						if (i < val)
						{
							(function ()
							{
								if (i <= val)
								{
									setTimeout(arguments.callee, timeout);
									priceTotalLayout.innerHTML = BX.util.number_format(i, 0, ",", " ");
									i = i + step;
								}
								else
								{
									priceTotalLayout.innerHTML = total_format;
									priceTotalLayout.setAttribute("data-total", val);
								}
							})();
						}
						else if (i > val)
						{
							(function ()
							{
								if (i >= val)
								{
									setTimeout(arguments.callee, timeout);
									priceTotalLayout.innerHTML = BX.util.number_format(i, 0, ",", " ");
									i = i - step;
								}
								else
								{
									priceTotalLayout.innerHTML = total_format;
									priceTotalLayout.setAttribute("data-total", val);
								}
							})();
						}
						else
						{
							return false;
						}
					}

					scroll(priceTotal, priceTotalLayout, 5, priceTotalStep, priceData);

				}
				else
				{
					priceTotalLayout.setAttribute("data-total", "0");
					priceTotalLayout.innerHTML = "0";
				}
			}

		},

		addColumn: function(options) {
			options = options || {};

			if (this.getColumn(options.id) !== null)
			{
				return;
			}

			var column = new BX.CrmKanbanColumn(options);
			column.kanban = this;
			this.columns[options.id] = column;
			this.columns_sort[options.sort] = options.id;
		},

		addDrop: function(options) {
			options = options || {};

			if (this.getDrop(options.id) !== null)
			{
				return;
			}

			var drop = new BX.CrmKanbanDrop(options);
			drop.kanban = this;
			this.dropzones[options.id] = drop;
		},

		addItem: function(options) {

			options = options || {};
			var column = this.getColumn(options.columnId);
			if (column)
			{
				var item = new BX.CrmKanbanItem(options);
				item.kanban = this;

				this.items[options.id] = item;
				column.addItem(item);
			}
		},

		moveItemToColumShow: function(item, targetColumn, beforeItem, avatar) {

			item = this.getItem(item);
			targetColumn = this.getColumn(targetColumn);
			beforeItem = this.getItem(beforeItem);

			var currentColumn = this.getColumn(item.columnId);
			var containerStart = document.querySelector('[data-move="' + item.id + '"]');
			var containerStartWidth = containerStart.offsetWidth;
			var containerFinish = document.querySelector('[data-move-column="' + targetColumn.id + '"]');

			if(beforeItem)
			{
				containerFinish = document.querySelector('[data-move="' + beforeItem.id + '"]');
			}
			var top = null;
			var left = null;

			function getPosition(element)
			{
				var box = element.getBoundingClientRect();
				var body = document.body;
				var docElem = document.documentElement;
				var scrollTop = window.pageYOffset || docElem.scrollTop || body.scrollTop;
				var scrollLeft = window.pageXOffset || docElem.scrollLeft || body.scrollLeft;
				var clientTop = docElem.clientTop || body.clientTop || 0;
				var clientLeft = docElem.clientLeft || body.clientLeft || 0;
				top  = box.top +  scrollTop - clientTop;
				left = box.left + scrollLeft - clientLeft;
			}
			getPosition(containerStart);

			containerStart.classList.add('crm-kanban-deal-item-block');

			var targetClass = 'crm-kanban-grid-item-pre';
			if(beforeItem)
			{
				targetClass = 'crm-kanban-deal-item-pre';
			}
			containerFinish.classList.add(targetClass);

			var containerMove = containerStart.cloneNode(true);
			if(avatar)
			{
				var userBlock = BX.create("div", {
					attrs: {
						className: "crm-kanban-deal-item-touchuser",
						style: avatar ? "background-image: url(" + avatar + ")" : ''
					}
				});
				containerMove.appendChild(userBlock);
			}
			containerMove.classList.add('crm-kanban-deal-item-dragshow');
			containerMove.style.width = containerStartWidth + "px";
			containerMove.style.position = "absolute";
			containerMove.style.top = top + "px";
			containerMove.style.left = left + "px";
			containerMove.style.zIndex = "999999";
			document.body.appendChild(containerMove);

			setTimeout(getPosition(containerFinish),1);
			setTimeout(function(){
				containerMove.style.top = (top + 102) + "px";
				if(beforeItem)
				{
					containerMove.style.top = top + "px";
				}
				containerMove.style.left = left + "px";
			},1);

			setTimeout(function(){

				if (currentColumn !== targetColumn)
				{
					currentColumn.removeItem(item);
					targetColumn.addItem(item, beforeItem);
				}

				containerStart.classList.remove('crm-kanban-deal-item-block');
				containerFinish.classList.remove(targetClass);
				containerMove.parentNode.removeChild(containerMove);
			},1000);
		},

		moveItemToColumFilter: function(item, targetColumn, beforeItem) {

			item = this.getItem(item);
			targetColumn = this.getColumn(targetColumn);
			beforeItem = this.getItem(beforeItem);
			var currentColumn = this.getColumn(item.columnId);

			if (currentColumn !== targetColumn)
			{
				currentColumn.removeItem(item);
				targetColumn.addItem(item, beforeItem);
			}
		},

		moveItemToColum: function(item, targetColumn, beforeItem, added) {

			if (BX.type.isNumber(+item))
			{
				item = this.getItem(item);
			}

			if(added)
			{
				item.layout.container.classList.add('crm-kanban-deal-item-added');
				setTimeout(function ()
				{
					item.layout.container.classList.remove('crm-kanban-deal-item-added');
				}.bind(this), 2100)
			}

			var border = item.layout.container.querySelector('.crm-kanban-deal-item-wrapper-border');
			var borderColor = targetColumn.color;

			border.style.background = '#' + borderColor;

			if (BX.type.isNumber(targetColumn) || BX.type.isString(targetColumn))
			{
				targetColumn = this.getColumn(targetColumn);
			}

			if (BX.type.isNumber(+beforeItem))
			{
				beforeItem = this.getItem(beforeItem);
			}

			var currentColumn = this.getColumn(item.columnId);

			if (currentColumn !== targetColumn)
			{
				currentColumn.removeItem(item);
				targetColumn.addItem(item, beforeItem);
			}
			else if(beforeItem)
			{
				if(beforeItem !== item)
				{
					currentColumn.removeItem(item);
					targetColumn.addItem(item, beforeItem);
				}
			}
			else if(currentColumn && !beforeItem)
			{
				currentColumn.removeItem(item);
				targetColumn.addItem(item, beforeItem);
			}
		},

		/**
		 *
		 * @param columnId
		 * @returns {BX.CrmKanbanColumn}
		 */
		getColumn: function(columnId) {
			return this.columns[columnId] ? this.columns[columnId] : null;
		},

		/**
		 *
		 * @param dropId
		 * @returns {BX.CrmKanbanDrop}
		 */

		getDrop: function(dropId) {
			return this.dropzones[dropId] ? this.dropzones[dropId] : null;
		},

		/**
		 *
		 * @param itemId
		 * @returns {BX.CrmKanbanItem}
		 */
		getItem: function(itemId) {
			return this.items[itemId] ? this.items[itemId] : null;
		},

		draw: function() {

			var docFragment = document.createDocumentFragment();

			for (var i in this.columns_sort)
			{
				var columnId = this.columns_sort[i];
				docFragment.appendChild(this.columns[columnId].render());
			}

			var dropZone = BX.create("div", {
				attrs: { className: "crm-kanban-dropzone" },
				style: {
					left: BX.pos(this.container.parentNode).left + 'px',
					bottom: '0'
				}
			});

			var dropZoneItem = document.createDocumentFragment();

			for (var dropId in this.dropzones)
			{
				dropZoneItem.appendChild(this.dropzones[dropId].render());
			}

			var itemWrapper = BX.create("div", {
				attrs: { className: "crm-kanban-grid-wrapper" }
			});

			itemWrapper.appendChild(docFragment);

			dropZone.appendChild(dropZoneItem);
			this.container.appendChild(itemWrapper);
			document.body.appendChild(dropZone);
			this.counterTotalPrice();
			this.setWidth();
			this.setHeight();
			this.setScroll();

			BX.bind(window, 'scroll', this.resize);
			BX.bind(window, 'resize', this.resize);
			BX.bind(window, 'resize', this.setHeight);
			BX.bind(window, 'resize', this.setWidth);
			BX.bind(window, 'resize', this.setScrollPosition);

		},

		scrollingRight: function ()
		{
			var parentBlock = kanban.lastChild;
			this.timer = setInterval(function () {
				parentBlock.scrollLeft += 10;
			}, 10)
		},

		scrollingLeft: function ()
		{
			var parentBlock = kanban.lastChild;
			this.timer = setInterval(function () {
				parentBlock.scrollLeft -= 10;
			}, 10)
		},

		scrollingStop: function ()
		{
			clearInterval(this.timer);
		},

		setScroll: function ()
		{
			this.scrollLeft = this.container.querySelector('.crm-kanban-scroll-left');
			this.scrollRight = this.container.querySelector('.crm-kanban-scroll-right');

			if(kanban.lastElementChild.scrollWidth > (document.documentElement.clientWidth - BX.pos(kanban.parentNode).left - 65))
			{
				this.scrollRight.classList.add('crm-kanban-scroll-show');
			}

			BX.bind(this.container.lastElementChild, 'scroll', this.setScrollPosition);
			setTimeout(function ()
			{
				this.setScrollPosition();
			}.bind(this),0);

			BX.bind(this.scrollRight, 'mouseenter', this.scrollingRight);
			BX.bind(this.scrollLeft, 'mouseenter', this.scrollingLeft);
			BX.bind(this.scrollRight, 'mouseleave', this.scrollingStop);
			BX.bind(this.scrollLeft, 'mouseleave', this.scrollingStop);
		},

		setScrollPosition: function ()
		{
			var scrollLeft = kanban.querySelector('.crm-kanban-scroll-left');
			var scrollRight = kanban.querySelector('.crm-kanban-scroll-right');

			if(kanban.lastElementChild.scrollLeft > 0)
			{
				scrollLeft.classList.add('crm-kanban-scroll-show');
			}
			else
			{
				scrollLeft.classList.remove('crm-kanban-scroll-show');
			};

			setTimeout(function ()
			{
				if ((kanban.lastElementChild.scrollLeft + kanban.lastElementChild.offsetWidth) < kanban.lastElementChild.scrollWidth)
				{
					scrollRight.classList.add('crm-kanban-scroll-show');
				}
				else
				{
					scrollRight.classList.remove('crm-kanban-scroll-show');
				}
			},0);

			if ((kanban.lastElementChild.scrollLeft + kanban.lastElementChild.offsetWidth) < kanban.lastElementChild.scrollWidth)
			{
				scrollRight.classList.add('crm-kanban-scroll-show');
			}
			else
			{
				scrollRight.classList.remove('crm-kanban-scroll-show');
			}
		},

		setEmptyMode: function (items)
		{
			var blockEmpty = BX.create('div', {
				attrs: { className: 'crm-kanban-empty' },
				children: [
					BX.create('div', {
						attrs: {
							className: 'crm-kanban-empty-inner',
							'data-role': 'kanban-empty'
						},
						children: [
							BX.create('div', {
								attrs: { className: 'crm-kanban-empty-image' }
							}),
							BX.create('div', {
								attrs: { className: 'crm-kanban-empty-text' },
								text: BX.message('CRM_KANBAN_NO_DATA')
							})
						]
					})
				]
			});

			kanban.appendChild(blockEmpty);

			if(items == 0)
			{
				kanban.classList.add('crm-kanban-empty-mode');
			}
			else
			{
				kanban.classList.remove('crm-kanban-empty-mode');
			}
		},

		loadData: function(json) {

			if (BX.type.isArray(json.columns))
			{
				json.columns.forEach(function(column) {
					if (column.type === 'LOOSE')
					{
						this.addDrop(column);
					}
					else
					{
						this.addColumn(column, true);
					}
				}, this);
			}

			if (BX.type.isArray(json.items))
			{
				this.setEmptyMode(json.items.length);
				json.items.forEach(function(item) {
					this.addItem(item);
				}, this);
			}

			if (json.events)
			{
				for (var eventName in json.events)
				{
					if (json.events.hasOwnProperty(eventName))
					{
						BX.addCustomEvent(this, eventName, json.events[eventName]);
					}
				}
			}
		}
	};
}

if (typeof(BX.CrmKanbanDrop) === 'undefined')
{
	BX.CrmKanbanDrop = function(options)
	{
		this.id = options.id;
		this.name = options.name;
		this.color = options.color;
		this.kanban = null;
		this.scrollLeft = null;
		this.scrollRight = null;
		this.layout = {
			container: null
		};
	};
	BX.CrmKanbanDrop.prototype =
	{
		render: function() {
			var dropContainer = BX.create("div", {
				attrs: {
					className: "crm-kanban-dropzone-item",
					"data-type": "drop",
					"data-id": this.id
				},
				children: [
					BX.create("div", {
						attrs: { className: "crm-kanban-dropzone-item-title" },
						html: this.name
					}),
					BX.create("div", {
						attrs: {  className: "crm-kanban-item-remove" }
					}),
					BX.create("div", {
						attrs: {
							className: "crm-kanban-dropzone-item-bg",
							style: "background: #" + this.color
						}
					})
				]
			});

			this.scrollRight = BX.create("div", {
				attrs: {
					className: "crm-kanban-scroll-right",
					"data-role": "scroll-right",
					"data-type": "scroll"
				}
			});

			this.scrollLeft = BX.create("div", {
				attrs: {
					className: "crm-kanban-scroll-left",
					"data-role": "scroll-left",
					"data-type": "scroll"
				}
			});

			var tagBody = document.querySelector('.bx-layout-table');

			this.kanban.container.appendChild(this.scrollLeft);
			this.kanban.container.appendChild(this.scrollRight);
			this.kanban.dragger.registerScrollLeft(this.scrollLeft);
			this.kanban.dragger.registerScrollRight(this.scrollRight);
			this.kanban.dragger.registerDrop(dropContainer);
			if(tagBody)
			{
				this.kanban.dragger.registerBody(tagBody);
			}

			return dropContainer;
		}
	};
}

if (typeof(BX.CrmKanbanColumn) === 'undefined')
{
	BX.CrmKanbanColumn = function(options)
	{
		this.id = options.id;
		this.name = options.name;
		this.color = options.color;
		this.sort = options.sort;
		this.count = options.count;
		this.total = options.total;
		this.total_format = options.total_format;
		this.currency = options.currency;
		this.items = [];
		this.layout = {
			container: null,
			items: null,
			itemsPre: null,
			title: null,
			summary: null,
			total: null,
			input: null,
			name: null,
			color: null,
			loadMore: null,
			loadMorePre: null,
			loadMoreClick: null,
			scrollTop: null,
			scrollBottom: null
		};
		this.kanban = null;
		this.page = 1;
		this.lastId = 0;
	};
	BX.CrmKanbanColumn.prototype = {

		addItem: function(item, beforeItem) {
			if (!item instanceof BX.CrmKanbanItem)
			{
				throw "item must be an instance of BX.CrmKanbanItem";
			}

			item.columnId = this.id;
			this.lastId = item.id;

			BX.CrmKanbanHelper.getInstance().setLastId(item.id);

			var index = BX.util.array_search(beforeItem, this.items);
			if (index >= 0)
			{
				this.items.splice(index, 0, item);
			}
			else
			{
				this.items.push(item);
			}

			if (this.layout.container)
			{
				this.render();
			}
		},

		removeItem: function(itemToRemove) {
			this.items = this.items.filter(function(item) {
				return item !== itemToRemove;
			});

			if (this.layout.container)
			{
				this.render();
			}
		},

		setName: function(name) {
			this.name = name;
		},

		loadMoreClick: function()
		{
			this.page++;
			BX.CrmKanbanHelper.getInstance().loadPage(this.id, this.page);
			this.layout.loadMore.classList.add('crm-kanban-loadmore-show');
		},

		createLayout: function() {

			this.layout.loadMore = BX.create("div", {
				attrs: { className: "crm-kanban-loadmore" },
				children: [
					this.layout.loadMorePre = BX.create("div", {
						attrs: { className: "crm-kanban-loadmore-pre" }
					}),
					BX.create("div", {
						attrs: { className: "crm-kanban-user-loader" },
						html:   '<div class="crm-kanban-user-loader-item">' +
								'<div class="crm-kanban-loader">' +
									'<svg class="crm-kanban-circular" viewBox="25 25 50 50">' +
										'<circle class="crm-kanban-path" cx="50" cy="50" r="20" fill="none" stroke-width="1" stroke-miterlimit="10"/>' +
									'</svg>' +
								'</div>' +
							'</div>'
					})/*,
					BX.create("span", {
						attrs: { className: "crm-kanban-loadmore-link" },
						text: BX.message("CRM_KANBAN_ACTIVITY_MORE"),
						events: {
							click: BX.proxy(this.loadMoreClick, this)
						}
					})*/
				]
			});

			if (this.layout.container !== null)
			{
				return this.layout.container;
			}

			var leadOff = 'crm-kanban-header crm-kanban-header-lead';

			BX.CrmKanbanHelper.getInstance().getEntityType() !== 'LEAD' ? leadOff = 'crm-kanban-header' : null

			// color option
			var titleParam = 'crm-kanban-step-title';

			function hexToRgb(hex) {
				var bigint = parseInt(hex, 16);
				var r = (bigint >> 16) & 255;
				var g = (bigint >> 8) & 255;
				var b = bigint & 255;
				var y = 0.21 * r + 0.72 * g + 0.07 * b;
				if(y < 145)
				{
					titleParam = 'crm-kanban-step-title crm-kanban-step-title-dark';
				}
			}

			if(this.color)
			{
				hexToRgb(this.color);
			}

			this.layout.container = BX.create("div", {
				attrs: {
					className: "crm-kanban-grid-item"
				},
				children: [
					this.layout.scrollTop = BX.create("div", {
						attrs: {
							className: "crm-kanban-items-scroll-top",
							"data-role": "scroll-top",
							"data-type": "scroll",
							"data-column-id": this.id
						},
						events: {
							mouseenter: function(){
								BX.CrmKanbanDragDrop.prototype.startScrollUp(this.layout.items);
							}.bind(this),
							mouseleave: function ()
							{
								BX.CrmKanbanDragDrop.prototype.stopScroll();
							}
						}
					}),
					this.layout.scrollBottom = BX.create("div", {
						attrs: {
							className: "crm-kanban-items-scroll-bottom",
							"data-role": "scroll-bottom",
							"data-type": "scroll",
							"data-column-id": this.id
						},
						events: {
							mouseenter: function(){
								BX.CrmKanbanDragDrop.prototype.startScrollDown(this.layout.items);
							}.bind(this),
							mouseleave: function ()
							{
								BX.CrmKanbanDragDrop.prototype.stopScroll();
							}
						}
					}),
					BX.create("div", {
						attrs: {
							className: leadOff
						},
						children: [
							BX.create("div", {
								attrs: { className: titleParam },
								children: [
									(this.layout.color = BX.create("div", {
										attrs: {
											className: "crm-kanban-step-title-bg",
											style: "background: #" + this.color
										}
									})),
									(this.layout.name = BX.create("span", {
										attrs: {
											className: "crm-kanban-step-title-name"
										}
									})),
									(this.layout.total = BX.create("span", {
										attrs: {
											className: "crm-kanban-step-title-total"
										}
									})),
									BX.CrmKanbanHelper.getInstance().getSettings('access_config_perms')
									? BX.create("a", {
										attrs: {
											href: BX.CrmKanbanHelper.getInstance().getSettings('path_column_edit'),
											className: "crm-kanban-step-title-edit"
										}
									})
									: null,
									this.color == "" ?
									BX.create("span", {
										attrs: {
											className: "crm-kanban-step-title-right"
										}
									}) :
									BX.create("span", {
										attrs: {
											className: "crm-kanban-step-title-right",
											style: "background: #fff url(data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20width%3D%2213%22%20height%3D%2232%22%20viewBox%3D%220%200%2013%2032%22%3E%3Cpath%20fill%3D%22%23" + this.color + "%22%20fill-opacity%3D%221%22%20d%3D%22M0%200h3c2.8%200%204%203%204%203l6%2013-6%2013s-1.06%203-4%203H0V0z%22/%3E%3C/svg%3E) no-repeat"
										}
									})
								]
							}),
							BX.CrmKanbanHelper.getInstance().getEntityType() !== 'LEAD' ?
							BX.create("div", {
								attrs: {
									className: "crm-kanban-total-price"
								},
								children: [
									(this.layout.summary = BX.create("span", {
										attrs: {
											className: "crm-kanban-total-price-total"
										}
									}))
								]
							}) :
							BX.create("div", {
								attrs: {
									className: "crm-kanban-total-price crm-kanban-total-price-off"
								},
								children: [
									(this.layout.summary = BX.create("span", {
										attrs: {
											className: "crm-kanban-total-price-total"
										}
									}))
								]
							})
						]
					}),
					this.layout.items = BX.create("div", {
						attrs: {
							className: "crm-kanban-items",
							"data-id": this.id,
							"data-move-column": this.id,
							"data-type": "column"
						},
						events: {
							scroll: function()
							{
								this.showScrollGradient();
							}.bind(this)
						},
						children: [
							this.layout.itemsPre = BX.create("div", {
								attrs: { className: "crm-kanban-items-preitem" }
							})
						]
					})
				]
			});

			this.kanban.dragger.registerColumn(this.layout.items);
			this.kanban.dragger.registerScrollTop(this.layout.scrollTop);
			this.kanban.dragger.registerScrollBottom(this.layout.scrollBottom);

			BX.bind(this.layout.items, 'mousewheel', this.blockedScroll);

			return this.layout.container;
		},

		blockedScroll: function (event)
		{
			if(this.scrollHeight > this.offsetHeight)
			{
				var mouseScroll = event.deltaY || event.detail || event.wheelDelta;

				if (mouseScroll < 0 && this.scrollTop == 0) {
					event.preventDefault();
				}

				if (mouseScroll > 0 && this.scrollHeight - this.clientHeight - this.scrollTop <= 1) {
					event.preventDefault();
				}
			}
		},

		showScrollGradient: function ()
		{
			if(this.layout.items.scrollHeight > this.layout.items.offsetHeight + this.layout.items.scrollTop)
			{
				this.layout.container.classList.add('crm-kanban-grid-item-scroll-bottom')
			}
			else if(this.layout.container.classList.contains('crm-kanban-grid-item-scroll-bottom'))
			{
				this.loadMoreClick();

				this.layout.container.classList.remove('crm-kanban-grid-item-scroll-bottom')
			}

			if(this.layout.items.scrollTop > 0)
			{
				this.layout.container.classList.add('crm-kanban-grid-item-scroll-top')
			}
			else if (this.layout.items.scrollTop <= 0)
			{
				this.layout.container.classList.remove('crm-kanban-grid-item-scroll-top')
			}
		},

		render: function() {

			var loadMoreShow = true;
			var columnEmpty = true;

			if (this.layout.container === null)
			{
				this.createLayout();
			}

			this.layout.container.style.maxWidth = (this.kanban.container.offsetWidth / 2) + 'px';

			BX.cleanNode(this.layout.items);

			for (var i = 0; i < this.items.length; i++)
			{
				var item = this.items[i];

				if (this.id === item.columnId)
				{
					columnEmpty = false;
					if (item.page === item.pageCount)
					{
						loadMoreShow = false;
					}
				}
				this.layout.items.appendChild(item.render());
			}

			this.layout.name.innerHTML = this.name;
			this.layout.items.appendChild(this.layout.itemsPre);

			setTimeout(this.showScrollGradient.bind(this), 200);

			return this.layout.container;
		}
	};
}

if (typeof(BX.CrmKanbanItem) === 'undefined')
{
	BX.CrmKanbanItem = function(options)
	{
		this.id = options.id;
		this.name = options.name;
		this.link = options.link;
		this.price = options.price;
		this.price_formatted = options.price_formatted;
		this.date = options.date;
		this.im = options.im;
		this.activityProgress = options.activityProgress;
		this.activityTotal = options.activityTotal;
		this.activityShow = options.activityShow;
		this.contactName = options.contactName;
		this.contactLink = options.contactLink;
		this.contactId = options.contactId;
		this.contactType = options.contactType;
		this.mail = options.email;
		this.phone = options.phone;
		this.columnId = options.columnId;
		this.columnColor = options.columnColor;
		this.page = options.page;
		this.pageCount = options.pageCount;
		this.fields = options.fields;
		this.layout = {
			container: null,
			containerPre: null,
			title: null,
			items: null,
			popup: null
		};
		this.popupTooltip = null;
		this.kanban = null;
		this.helper = BX.CrmKanbanHelper.getInstance();
		this.entityType = this.helper.getEntityType();
		this.moreFields = this.helper.getMoreFields();
		this.activityShow = this.helper.getSettings('show_activity');
	};
	BX.CrmKanbanItem.prototype =
	{

		clickChat: function() {
			BXIM.openMessengerSlider(this.im.value, {RECENT: 'N', MENU: 'N'});
		},

		clickContact: function(type) {

			var fields = type === 'mail' ? this.mail : this.phone;
			if (fields.length > 1)
			{
				var id = BX.findParent(BX.proxy_context, {className: 'crm-kanban-deal-item'}).getAttribute('data-id');
				var menuItems = [];

				this.layout.container.classList.add('crm-kanban-deal-item-active');
				document.body.addEventListener('click', BX.proxy(this.onBodyClick, this), true);

				for (var i = 0; i < fields.length; i++)
				{
					if (type === 'mail')
					{
						menuItems.push({
							text: fields[i]['value'] + ' (' + fields[i]['title'] + ')',
							href: 'mailto:' + fields[i]['value']
						});
					}
					else
					{
						menuItems.push({
							phone: fields[i]['value'],
							text: fields[i]['value'] + ' (' + fields[i]['title'] + ')',
							onclick: BX.proxy(this.clickPhoneCall, this)
						});
					}
				}
				BX.PopupMenu.show('kanban-contact-' + type + '-' + id, BX.proxy_context, menuItems,
				{
					autoHide: true,
					zIndex: 1200,
					offsetLeft: 20,
					angle: true,
					closeByEsc : true
				});
			}
			else
			{
				var i = 0;
				if (type === 'mail')
				{
					// top.location.href = 'mailto:' + fields[i]['value'];
				}
				else
				{
					this.clickPhoneCall(i, {phone: fields[i]['value']});
				}
			}
		},

		onBodyClick: function() {
			var itemClassRemove = document.body.querySelector('.crm-kanban-deal-item-active');
			itemClassRemove.classList.remove('crm-kanban-deal-item-active');
			document.body.removeEventListener("click", BX.proxy(this.onBodyClick, this), true);
		},

		clickMail: function() {
			this.clickContact('mail');
		},

		clickPhone: function() {
			this.clickContact('phone');
		},

		clickPhoneCall: function(i, item) {
			if (typeof(BXIM) !== 'undefined') {
				BXIM.phoneTo(item.phone, {ENTITY_TYPE: this.contactType, ENTITY_ID: this.contactId});
			}
		},

		addField: function() {
			var id = BX.findParent(BX.proxy_context, {className: 'crm-kanban-deal-item'}).getAttribute('data-id');
			var menuItems = [];
			for (var i = 0; i < this.moreFields.length; i++)
			{
				menuItems.push({
					code: this.moreFields[i]['code'],
					text: this.moreFields[i]['title'],
					onclick: BX.proxy(this.addFieldClick, this)
				});
			}
			BX.PopupMenu.show('kanban-more-fields-' + id, BX.proxy_context, menuItems);
		},

		addFieldClick: function(i, item) {
			var href = top.location.href;
			top.location.href = href + (href.indexOf('?') === -1 ? '?' : '&') + 'set_field=' + item.code
		},

		delField: function() {
			var href = top.location.href;
			href = href + (href.indexOf('?') === -1 ? '?' : '&') + 'del_field=' + BX.proxy_context.getAttribute('data-code');
			top.location.href = href;
		},

		fixedItemPosition: function (div)
		{
			div.layout.container.classList.add('crm-kanban-deal-item-show')
		},

		activityClick: function() {
			this.fixedItemPosition(this);
			BX.CrmKanbanHelper.getInstance().openActivityItems(this.id);
		},

		activityPlanClick: function() {

			this.fixedItemPosition(this);

			var id = BX.findParent(BX.proxy_context, {className: 'crm-kanban-deal-item'}).getAttribute('data-id');
			var menuItems = [
				{
					type: 'call',
					text: BX.message('CRM_KANBAN_ACTIVITY_PLAN_CALL'),
					onclick: BX.proxy(this.activityPlanClickItem, this)
				},
				{
					type: 'meeting',
					text: BX.message('CRM_KANBAN_ACTIVITY_PLAN_MEETING'),
					onclick: BX.proxy(this.activityPlanClickItem, this)
				},
				{
					type: 'task',
					text: BX.message('CRM_KANBAN_ACTIVITY_PLAN_TASK'),
					onclick: BX.proxy(this.activityPlanClickItem, this)
				},
			];
			BX.PopupMenu.show('kanban-plan-' + id, BX.proxy_context, menuItems,
			{
				autoHide: true,
				offsetLeft: 20,
				angle: true,
				overlay: false
			});
		},

		activityPlanClickItem: function(i, item) {
			if (item.type === 'meeting' || item.type === 'call')
			{
				(new BX.Crm.Activity.Planner()).showEdit({
					TYPE_ID: BX.CrmActivityType[item.type],
					OWNER_TYPE: this.entityType,
					OWNER_ID: this.id
				});
			}
			else if (item.type === 'task')
			{
				if (typeof window['taskIFramePopup'] !== 'undefined')
				{
					var taskData =
						{
							UF_CRM_TASK: [BX.CrmOwnerTypeAbbr.resolve(this.entityType) + '_' + this.id],
							TITLE: 'CRM: ',
							TAGS: 'crm'
						};
					window['taskIFramePopup'].add(taskData);
				}
			}
		},

		renderFields: function() {
			var fields = [];
			for (var i=0; i<this.fields.length; i++)
			{
				fields.push(BX.create("div", {
					attrs: {
					},
					children: [
						BX.create("span", {
							attrs: {
								className: "crm-kanban-deal-item-field"
							},
							html: this.fields[i]["title"] + ": " + this.fields[i]["value"]
						}),
						BX.create("span", {
							attrs: {
								className: "crm-kanban-deal-item-field-del",
								"data-code": this.fields[i]["code"]
							},
							html: "*",
							events: {
								click: BX.proxy(this.delField, this)
							}
						})
					]
				}));
			}
			return fields;
		},

		render: function() {

			if (this.layout.container === null)
			{
				this.layout.container = BX.create("div", {
					attrs: {
						className: "crm-kanban-deal-item",
						"data-id": this.id,
						"data-move": this.id,
						"data-type": "item"
					},
					children: [
						this.layout.containerPre = BX.create("div", {
							attrs: { className: "crm-kanban-deal-item-wrapper-pre" }
						}),
						BX.create("div", {
							attrs: { className: "crm-kanban-deal-item-wrapper" },
							children: [
								BX.create("div", {
									attrs: {
										className: "crm-kanban-deal-item-wrapper-border",
										"data-role": "item-color",
										style: "background: #" + this.columnColor
									}
								}),
								BX.create("a", {
									attrs: {
										className: "crm-kanban-deal-item-title",
										href: this.link,
										title: BX.util.htmlspecialcharsback(this.name)
									},
									html: this.name
								}),
								BX.CrmKanbanHelper.getInstance().getEntityType() !== 'LEAD' ?
								BX.create("div", {
									attrs: { className: "crm-kanban-deal-item-total" },
									children: [
										BX.create("div", {
											attrs: {
												className: "crm-kanban-deal-item-total-price"
											},
											html: this.price_formatted
										})
									]
								}) : null,
								BX.create("a", {
									attrs: {
										className: "crm-kanban-deal-item-user",
										href: this.contactLink
									},
									html: this.contactName
								}),
								BX.create("div", {
									attrs: { className: "crm-kanban-deal-item-date" },
									html: this.date
								}),
								BX.create("div", {
									attrs: { className: "crm-kanban-deal-item-planner" },
									children: [
										this.activityShow ? BX.create("span", {
											attrs: {
												className: "crm-kanban-deal-item-activity",
												id: "crm-kanban-act-count-" + this.id,
												"parent-id": this.id,
												'data-count': this.activityProgress,
												style: this.activityProgress > 0 ? 'display: block' : 'display: none;'
											},
											text: BX.message('CRM_KANBAN_ACTIVITY_PLAN') + ': ' + this.activityProgress,
											events: {
												click: BX.proxy(this.activityClick, this)
											}
										}) : null,
										this.activityShow ? BX.create('div', {
											attrs: {
												className: 'crm-kanban-deal-item-activity-empty',
												id: "crm-kanban-act-icon-" + this.id,
												"parent-id": this.id,
												style: this.activityProgress > 0 ? 'display: none' : 'display: block;'
											},
											events: {
												mouseover: function ()
												{
													this.popupTooltip = new BX.PopupWindow('kanban_plan_tooltip', this, {
														className: 'crm-kanban-withiot-tooltip',
														offsetLeft: 14,
														darkMode: true,
														closeByEsc: true,
														angle : true,
														autoHide: true,
														content: BX.message('CRM_KANBAN_ACTIVITY_LETSGO')
													})
													this.popupTooltip.show();
												},
												mouseout: function ()
												{
													this.popupTooltip.destroy();
												}
											}
										}) : null,
										this.activityShow ? BX.create("span", {
											attrs: {
												className: "crm-kanban-deal-item-plan",
												"parent-id": this.id
											},
											text: BX.message('CRM_KANBAN_ACTIVITY_MY'),
											events: {
												click: BX.proxy(this.activityPlanClick, this)
											}
										})
										: null
									]
								}),
								BX.create("div", {
									attrs: { className: "crm-kanban-deal-item-contact" },
									children: [
										BX.create("a", {
											attrs: {
												className: this.phone ? "crm-kanban-step-contact-phone" : "crm-kanban-step-contact-phone crm-kanban-step-contact-phone-disabled",
												href: "javascript:void(0)"
											},
											events: {
												click: this.phone ? BX.proxy(this.clickPhone, this) : null,
												mouseover: this.phone ? null : function ()
												{
													this.popupTooltip = new BX.PopupWindow('kanban_plan_tooltip', this, {
														className: 'crm-kanban-withiot-tooltip',
														offsetLeft: 16,
														offsetTop: -4,
														darkMode: true,
														closeByEsc: true,
														angle : true,
														autoHide: true,
														content: BX.message('CRM_KANBAN_NO_PHONE')
													});
													this.popupTooltip.show();
												},
												mouseout: this.phone ? null : function ()
												{
													this.popupTooltip.destroy();
												}
											}
										}),
										BX.create("a", {
											attrs: {
												className: this.mail ? "crm-kanban-step-contact-mail" : "crm-kanban-step-contact-mail crm-kanban-step-contact-mail-disabled",
												href: (!this.mail || this.mail.length > 1) ? "javascript:void(0)" : "mailto:" + this.mail[0]['value']
											},
											events: {
												click: this.mail ? BX.proxy(this.clickMail, this) : null,
												mouseover: this.mail ? null : function ()
												{
													this.popupTooltip = new BX.PopupWindow('kanban_plan_tooltip', this, {
														className: 'crm-kanban-withiot-tooltip',
														offsetLeft: 16,
														offsetTop: -4,
														darkMode: true,
														closeByEsc: true,
														angle : true,
														autoHide: true,
														content: BX.message('CRM_KANBAN_NO_EMAIL')
													});
													this.popupTooltip.show();
												},
												mouseout: this.mail ? null : function ()
												{
													this.popupTooltip.destroy();
												}
											}
										}),
										BX.create("span", {
											attrs: {
												className: this.im ? "crm-kanban-step-contact-message" : "crm-kanban-step-contact-message crm-kanban-step-contact-message-disabled"
											},
											events: {
												click: this.im ? BX.proxy(this.clickChat, this) : null,
												mouseover: this.im ? null : function ()
												{
													this.popupTooltip = new BX.PopupWindow('kanban_plan_tooltip', this, {
														className: 'crm-kanban-withiot-tooltip',
														offsetLeft: 16,
														offsetTop: -4,
														darkMode: true,
														closeByEsc: true,
														angle : true,
														autoHide: true,
														content: BX.message('CRM_KANBAN_NO_IMOL')
													});
													this.popupTooltip.show();
												},
												mouseout: this.im ? null : function ()
												{
													this.popupTooltip.destroy();
												}
											}
										})
									]
								})
							]
						})
					]
				});

				this.kanban.dragger.registerItem(this.layout.container);
			}

			return this.layout.container;
		}
	};

	BX.CrmKanbanItem.changeActCount = function(id, type)
	{
		var count = Math.max(0, parseInt(BX.data(BX('crm-kanban-act-count-' + id), 'count')) + parseInt(type) * 1);
		BX.data(BX('crm-kanban-act-count-' + id), 'count', count);
		BX('crm-kanban-act-count-' + id).innerText = BX.message('CRM_KANBAN_ACTIVITY_PLAN') + ': ' + count;
		if (count > 0)
		{
			BX.show(BX('crm-kanban-act-count-' + id));
			BX.hide(BX('crm-kanban-act-icon-' + id));
		}
		else
		{
			BX.hide(BX('crm-kanban-act-count-' + id));
			BX.show(BX('crm-kanban-act-icon-' + id));
		}
	};
}

if (typeof(BX.CrmKanbanDragDrop) === 'undefined')
{
	BX.CrmKanbanDragDrop = function(kanban)
	{
		/**
		 * @var {BX.CrmKanbanGrid}
		 */
		this.kanban = kanban;
		this.draggableItem = null;
		this.droppableColumn = null;
		this.stub = null;
		this.droppableZone = null;
		this.dropContainer = null;
		this.preDraggableItem = null;
		this.scrollZone = null;
		this.timer = null;
		this.body = null;
		this.timeoutId = null;
	};
	BX.CrmKanbanDragDrop.prototype.registerItem = function(object)
	{
		object.onbxdragstart = BX.proxy(this.onDragStart, this);
		object.onbxdrag = BX.proxy(this.onDrag, this);
		object.onbxdragstop = BX.proxy(this.onDragStop, this);
		object.onbxdraghover = BX.proxy(this.onDragOver, this );
		jsDD.registerObject(object);
		jsDD.registerDest(object, 30);
	};
	BX.CrmKanbanDragDrop.prototype.registerDrop = function(object)
	{
		jsDD.registerDest(object, 10);
	};
	BX.CrmKanbanDragDrop.prototype.registerColumn = function(object)
	{
		jsDD.registerDest(object, 40);
	};
	BX.CrmKanbanDragDrop.prototype.registerScrollTop = function(object)
	{
		jsDD.registerDest(object, 20);
	};
	BX.CrmKanbanDragDrop.prototype.registerScrollBottom = function(object)
	{
		jsDD.registerDest(object, 20);
	};
	BX.CrmKanbanDragDrop.prototype.registerScrollLeft = function(object)
	{
		jsDD.registerDest(object, 20);
	};
	BX.CrmKanbanDragDrop.prototype.registerScrollRight = function(object)
	{
		jsDD.registerDest(object, 20);
	};
	BX.CrmKanbanDragDrop.prototype.registerBody = function(object)
	{
		jsDD.registerDest(object, 60);
	};

	BX.CrmKanbanDragDrop.prototype.onDragStart = function()
	{

		BX.CrmKanbanGrid.prototype.resize();

		this.dropContainer = document.querySelector('.crm-kanban-dropzone');
		this.dropContainer.classList.remove('crm-kanban-dropzone-pre');
		this.dropContainer.classList.add('crm-kanban-dropzone-show');

		var div = BX.proxy_context;
		var itemId = BX.type.isDomNode(div) ? div.getAttribute("data-id") : null;

		this.draggableItem = this.kanban.getItem(itemId);
		this.preDraggableItem = this.kanban.getItem(this.draggableItem.layout.container.nextSibling.dataset.id);
		this.draggableItem.layout.container.classList.add('crm-kanban-deal-item-block');

		if (!this.draggableItem)
		{
			jsDD.stopCurrentDrag();
			return;
		}

		if (!this.stub)
		{
			var widthItem = this.draggableItem.layout.container.offsetWidth;
			var heightItem = this.draggableItem.layout.container.offsetHeight;
			this.stub = this.draggableItem.layout.container.cloneNode(true);
			this.stub.dataset.column = this.draggableItem.columnId;
			this.stub.dataset.pre = this.draggableItem.layout.container.nextElementSibling.dataset.id;
			this.stub.dataset.drag = "drag";
			this.stub.style.position = "absolute";
			this.stub.style.width = widthItem + "px";
			this.stub.style.height = heightItem + "px";
			this.stub.className = "crm-kanban-deal-item crm-kanban-deal-item-drag";
			document.body.appendChild(this.stub);
		}
	};

	BX.CrmKanbanDragDrop.prototype.onDrag = function(x, y)
	{
		this.stub.style.left = x + "px";
		this.stub.style.top = y + "px";
	};

	BX.CrmKanbanDragDrop.prototype.onDragOver = function(destination, x, y)
	{
		setTimeout(function ()
		{
			var itemsPre = this.kanban.container.querySelectorAll('.crm-kanban-loadmore-pre');
			for(var i = 0; i < itemsPre.length; i++)
			{
				itemsPre[i].style.height = '';
			}
		}.bind(this), 0);

		if (this.droppableItem)
		{
			this.droppableItem.layout.container.classList.remove('crm-kanban-deal-item-pre');
			this.droppableItem.layout.containerPre.style.height = '';
		}

		if (this.droppableColumn)
		{
			this.droppableColumn.layout.itemsPre.style.height = this.stub.offsetHeight + 'px';

			if(this.droppableColumn.layout.container.classList.contains('crm-kanban-grid-item-scroll-bottom') && this.droppableColumn.layout.container.classList.contains('crm-kanban-grid-item-scroll-top'))
			{
				this.droppableColumn.layout.container.className = "crm-kanban-grid-item crm-kanban-grid-item-scroll-bottom crm-kanban-grid-item-scroll-top";
			}
			else if(this.droppableColumn.layout.container.classList.contains('crm-kanban-grid-item-scroll-top'))
			{
				this.droppableColumn.layout.container.className = "crm-kanban-grid-item crm-kanban-grid-item-scroll-top";
			}
			else if(this.droppableColumn.layout.container.classList.contains('crm-kanban-grid-item-scroll-bottom'))
			{
				this.droppableColumn.layout.container.className = "crm-kanban-grid-item crm-kanban-grid-item-scroll-bottom";
			}
			else
			{
				this.droppableColumn.layout.container.className = "crm-kanban-grid-item";
				this.droppableColumn.layout.itemsPre.style.height = "";
			}
		}

		if (this.droppableZone)
		{
			this.droppableZone.parentNode.className  = "crm-kanban-dropzone crm-kanban-dropzone-show";
			this.droppableZone.className = "crm-kanban-dropzone-item";
		}

		if (this.scrollZone)
		{
			this.scrollZone.classList.remove("crm-kanban-items-scroll-show")
		}

		var itemId = destination.getAttribute("data-id");
		var type = destination.getAttribute("data-type");
		var tag = destination.tagName;

		if (tag !== 'DIV')
		{
			this.body = destination;
			this.droppableItem = this.kanban.getItem(itemId);
			this.droppableColumn = null;
			this.droppableZone = null;
			this.scrollZone = null;
			this.stopScroll();
		}

		if (type === "item")
		{
			this.droppableItem = this.kanban.getItem(itemId);
			this.droppableColumn = null;
			this.droppableZone = null;
			this.scrollZone = null;
			this.stopScroll();
		}
		else if (type === "column")
		{
			this.droppableColumn = this.kanban.getColumn(itemId);
			this.droppableItem = null;
			this.droppableZone = null;
			this.scrollZone = null;
			this.stopScroll();
		}
		else if (type === "drop")
		{
			this.droppableZone = destination;
			this.droppableColumn = null;
			this.droppableItem = null;
			this.scrollZone = null;
			this.stopScroll();
			this.droppableZone.parentNode.classList.add('crm-kanban-dropzone-pre');
		}
		else if (type === "scroll")
		{
			this.stopScroll();
			this.scrollZone = destination;

			var dataRole = destination.dataset.role;
			var dataColumnId = destination.dataset.columnId;

			this.scrollZone.classList.add("crm-kanban-items-scroll-show");

			this.droppableColumn = this.kanban.getColumn(dataColumnId);

			if (dataRole === "scroll-top")
			{
				this.startScrollUp(this.droppableColumn.layout.items);
			}
			else if (dataRole === "scroll-bottom")
			{
				this.startScrollDown(this.droppableColumn.layout.items);
			}
			else if (dataRole === "scroll-left")
			{
				this.startScrollLeft();
			}
			else if (dataRole === "scroll-right")
			{
				this.startScrollRight();
			}

			this.droppableItem = null;
			this.droppableZone = null;
		}

		if (this.droppableItem)
		{
			this.droppableItem.layout.container.classList.add('crm-kanban-deal-item-pre');
			this.droppableItem.layout.containerPre.style.height = this.stub.style.height;
		}

		if (this.droppableZone)
		{
			this.droppableZone.className = "crm-kanban-dropzone-item crm-kanban-dropzone-item-pre";
		}

		if (this.droppableColumn)
		{
			var parentClass = 'crm-kanban-grid-item-pre';

			if(this.droppableColumn.layout.loadMore.parentNode == null)
			{
				parentClass = 'crm-kanban-grid-item-column-pre';
				this.droppableColumn.layout.itemsPre.style.height = this.stub.style.height;
			}
			this.droppableColumn.layout.loadMorePre.style.height =  this.stub.style.height;
			this.droppableColumn.layout.container.classList.add(parentClass)
		}
	};

	BX.CrmKanbanDragDrop.prototype.stopScroll = function()
	{
		clearInterval(this.timer);
	};

	BX.CrmKanbanDragDrop.prototype.startScrollDown = function(column)
	{
		this.timer = setInterval(function () {
			column.scrollTop += 10;
			jsDD.refreshDestArea();
		}, 20)
	};

	BX.CrmKanbanDragDrop.prototype.startScrollUp = function(column)
	{
		this.timer = setInterval(function ()
		{
			jsDD.refreshDestArea();
			column.scrollTop -= 10;
			jsDD.refreshDestArea();
		}, 20)
	};

	BX.CrmKanbanDragDrop.prototype.startScrollLeft = function()
	{
		var parentBlock = this.kanban.container.lastChild;
		this.timer = setInterval(function () {
			parentBlock.scrollLeft -= 10;
			jsDD.refreshDestArea();
		}, 10)
	};

	BX.CrmKanbanDragDrop.prototype.startScrollRight = function()
	{
		var parentBlock = this.kanban.container.lastChild;
		this.timer = setInterval(function () {
			parentBlock.scrollLeft += 10;
			jsDD.refreshDestArea();
		}, 10)
	};

	BX.CrmKanbanDragDrop.prototype.invoicePopupInstance = null;
	BX.CrmKanbanDragDrop.prototype.invoicePopup = function(id, status, oldStatus)
	{
		if (this.invoicePopupInstance === null)
		{
			this.invoicePopupInstance = new BX.PopupWindow('kanban_invoice', window.body, {
					offsetLeft : 0,
					lightShadow : true,
					overlay : true,
					titleBar: {content: BX.create('span', {html: ''})},
					draggable: true,
					contentColor: 'white',
					content: BX.create('div', {
								attrs: { className: 'crm-kanban-popup-wrapper' },
								children: [
									BX.create('table', {
										attrs: { className: 'crm-kanban-popup-table' },
										children: [
											BX.create('tr', {
												children: [
													BX.create('td', {
														children: [
															BX.create('SPAN', {
																attrs: { className: 'crm-kanban-popup-text' },
																text: BX.message('CRM_KANBAN_INVOICE_PARAMS_DATE')
															})
														]
													}),
													BX.create('td', {
														children: [
															BX.create('INPUT', {
																attrs: {
																	id: 'crm-kanban-droppopup-date',
																	className: 'crm-kanban-popup-input'
																},
																events: {
																	click: function()
																	{
																		BX.calendar({
																			node: this,
																			field: this
																		});
																	}
																}
															})
														]
													})
												]
											}),
											BX.create('tr', {
												attrs: { id: 'crm-kanban-droppopup-winblock' },
												children: [
													BX.create('td', {
														children: [
															BX.create('SPAN', {
																attrs: { className: 'crm-kanban-popup-text' },
																text: BX.message('CRM_KANBAN_INVOICE_PARAMS_DOCNUM')
															})
														]
													}),
													BX.create('td', {
														children: [
															BX.create('INPUT', {
																attrs: {
																	id: 'crm-kanban-droppopup-docnum',
																	className: 'crm-kanban-popup-input'
																}
															})
														]
													})
												]
											}),
											BX.create('tr', {
												children: [
													BX.create('td', {
														attrs: {
															colspan: '2',
															className: 'crm-kanban-popup-border'
														},
														children: [
															BX.create('SPAN', {
																attrs: { className: 'crm-kanban-popup-text' },
																text: BX.message('CRM_KANBAN_INVOICE_PARAMS_COMMENT')
															}),
															BX.create('TEXTAREA', {
																attrs: {
																	id: 'crm-kanban-droppopup-comment',
																	className: 'crm-kanban-popup-textarea'
																}
															}),
															BX.create('INPUT',{
																attrs: { type: 'hidden', id: 'crm-kanban-droppopup-id' }
															}),
															BX.create('INPUT',{
																attrs: { type: 'hidden', id: 'crm-kanban-droppopup-status' }
															}),
															BX.create('INPUT',{
																attrs: { type: 'hidden', id: 'crm-kanban-droppopup-oldstatus' }
															})
														]
													}),
												]
											})
										]
									})
								]
							}),
					buttons:
							[
								new BX.PopupWindowButton(
									{
										text: BX.message('CRM_KANBAN_INVOICE_PARAMS_SAVE'),
										className: 'popup-window-button-accept',
										events:
										{
											click: function()
											{
												BX.CrmKanbanHelper.getInstance().moveState(
															BX('crm-kanban-droppopup-id').value,
															BX('crm-kanban-droppopup-status').value,
															BX('crm-kanban-droppopup-oldstatus').value,
															{
																comment: BX('crm-kanban-droppopup-comment').value,
																date: BX('crm-kanban-droppopup-date').value,
																docnum: BX('crm-kanban-droppopup-docnum').value
															}
														);
												this.popupWindow.close();
											}
										}
									}
								)
							]
				});
			this.invoicePopupInstance.setTitleBar(BX.message('CRM_KANBAN_INVOICE_PARAMS'));
		}
		BX('crm-kanban-droppopup-comment').value = '';
		BX('crm-kanban-droppopup-date').value = BX.date.format(BX.date.convertBitrixFormat(BX.message('FORMAT_DATE')));
		BX('crm-kanban-droppopup-docnum').value = '';
		BX('crm-kanban-droppopup-id').value = id;
		BX('crm-kanban-droppopup-status').value = status;
		BX('crm-kanban-droppopup-oldstatus').value = oldStatus;

		if (status === 'P')
		{
			BX.show(BX('crm-kanban-droppopup-winblock'));
		}
		else
		{
			BX.hide(BX('crm-kanban-droppopup-winblock'));
		}
		this.invoicePopupInstance.show();
	};

	BX.CrmKanbanDragDrop.prototype.convertId = null;
	BX.CrmKanbanDragDrop.prototype.convertPopupInstance = null;
	BX.CrmKanbanDragDrop.prototype.convertPopup = function(id)
	{
		this.convertPopupInstance = null;
		BX.CrmKanbanDragDrop.prototype.convertId = id;
		if (this.convertPopupInstance === null)
		{
			var targets = [];
			for (var key in BX.CrmLeadConversionScheme.messages)
			{
				targets.push(
					BX.create('DIV', {
						attrs: { 'data-type': key },
						text: BX.CrmLeadConversionScheme.messages[key],
						events: {
							click: BX.proxy(this.convertPopupClick, this)
						}
					})
				);
			}
			targets.push(
					BX.create('div', {
						attrs: { 'data-type': 'SELECT' },
						text: BX.message('CRM_KANBAN_CONVERT_SELECT_ENTITY'),
						events: {
							click: BX.proxy(this.convertPopupClick, this)
						}
					})
				);

			this.convertPopupInstance = new BX.PopupWindow('kanban_convert', window, {
					className: 'crm-kanban-popup-convert',
					offsetLeft : -50,
					lightShadow : true,
					closeIcon : false,
					overlay : true,
					titleBar: {content: BX.create('span', {html: ''})},
					draggable: true,
					closeByEsc : false,
					content: BX.create('div', {
						attrs: { className: 'crm-kanban-popup-convert-list' },
						children: targets
					})
				});

			var dragItem = this.stub;

			var closeButtonConvertPopup = BX.create('div', {
				attrs: { className: 'crm-kanban-popup-convert-button' },
				children: [
					BX.create('input', {
						attrs: {
							className: 'webform-small-button webform-small-button-cancel',
							type: 'submit',
							value: BX.message('CRM_KANBAN_FAIL_CONFIRM_CANCEL')
						},
						events: {
							click: function()
							{
								this.convertPopupInstance.close();
								this.kanban.moveItemToColum(this.kanban.getItem(dragItem.dataset.id), this.kanban.getColumn(dragItem.dataset.column), this.kanban.getItem(dragItem.dataset.pre));
							}.bind(this)
						}
					})
				]
			});
			var popupParent = this.convertPopupInstance.contentContainer.parentNode;
			popupParent.appendChild(closeButtonConvertPopup);
			this.convertPopupInstance.setTitleBar(BX.message('CRM_KANBAN_CONVERT_POPUP_TITLE'));
		}
		this.convertPopupInstance.show();
	};
	BX.CrmKanbanDragDrop.prototype.convertPopupClick = function()
	{
		var scheme = BX.proxy_context.getAttribute('data-type');
		var id = this.convertId;
		if (scheme === 'SELECT')
		{
			BX.CrmLeadConverter.getCurrent().openEntitySelector(function(result){ BX.CrmLeadConverter.getCurrent().convert(id, result.config, '', result.data); });
		}
		else
		{
			BX.CrmLeadConverter.getCurrent().convert(id, BX.CrmLeadConversionScheme.createConfig(scheme), '');
		}
		this.convertPopupInstance.close();
	};

	BX.CrmKanbanDragDrop.prototype.onDragStop = function(x, y, event)
	{
		this.stopScroll();
		this.draggableItem.layout.container.classList.remove('crm-kanban-deal-item-block');

		var oldColumnId = this.draggableItem.columnId;

		document.body.style.height = '';
		document.body.style.overflow = '';
		document.body.style.width = '';

		var stopOpoup = new BX.PopupWindow('kanban_stop_drag', window, {
			className: 'crm-kanban-popup-convert',
			closeIcon: true,
			closeByEsc: true,
			autoHide: true,
			content: BX.message('CRM_KANBAN_ERROR_DISABLE_CONVERTED_LEAD'),
			overlay: false
		});

		if (this.draggableItem)
		{
			if (this.droppableZone)
			{
				if(this.draggableItem.columnId == 'CONVERTED')
				{
					var targetColumn = this.kanban.getColumn(this.draggableItem.columnId);
					this.kanban.moveItemToColum(this.draggableItem, targetColumn, this.preDraggableItem);
					this.dropContainer.className = "crm-kanban-dropzone";
					this.droppableZone.className = 'crm-kanban-dropzone-item';
					stopOpoup.show();

					setTimeout(function ()
					{
						stopOpoup.close();
					}, 5000)
				}
				else
				{
					var removeId = this.droppableZone.dataset.item;

					this.droppableZone.className = 'crm-kanban-dropzone-item';
					this.droppableZone.setAttribute('data-item', this.draggableItem.id);

					var targetColumnId = this.droppableZone.getAttribute('data-id');
					var droppableZoneInner = this.droppableZone;
					var draggableItemInner = this.draggableItem;

					var innerContent = this.droppableZone.querySelector('.crm-kanban-item-remove');
					var draggableItem = this.draggableItem.layout.container;

					BX.cleanNode(innerContent);

					this.dropContainer.className = 'crm-kanban-dropzone crm-kanban-dropzone-show';
					this.droppableZone.className = 'crm-kanban-dropzone-item crm-kanban-dropzone-item-pre';

					var clearZone = function (zone, starColumnId, newColumnId)
					{
						if(zone.dataset.item)
						{
							startAction(+zone.dataset.item, starColumnId, newColumnId, zone, draggableItemInner.id)
						}
						clearTimeout(this.timeoutId);
					}.bind(this);

					var startAction = function (dragItemId, starColumnId, newColumnId, zone)
					{
						var remItem = document.querySelector('[data-move="' + dragItemId + '"]');

						if(remItem.classList.contains('crm-kanban-deal-item-deleted')) {
							if (
								BX.CrmKanbanHelper.getInstance().getEntityType() === 'INVOICE' &&
								(newColumnId === 'P' || zone) &&
								starColumnId != newColumnId
							)
							{
								this.invoicePopup(dragItemId, newColumnId, starColumnId);
							}
							else
							{
								BX.CrmKanbanHelper.getInstance().moveState(dragItemId, newColumnId, starColumnId);
							}
							if (
								BX.CrmKanbanHelper.getInstance().getEntityType() === 'LEAD' &&
								newColumnId === 'CONVERTED' &&
								starColumnId != newColumnId
							)
							{
								this.convertPopup(dragItemId);
							}
						}
					}.bind(this);

					var startActionUp = function (dragItemId, starColumnId, newColumnId, zone)
					{
							if (
								BX.CrmKanbanHelper.getInstance().getEntityType() === 'INVOICE' &&
								(newColumnId === 'P' || zone) &&
								starColumnId != newColumnId
							)
							{
								this.invoicePopup(dragItemId, newColumnId, starColumnId);
							}
							else
							{
								BX.CrmKanbanHelper.getInstance().moveState(dragItemId, newColumnId, starColumnId);
							}
							if (
								BX.CrmKanbanHelper.getInstance().getEntityType() === 'LEAD' &&
								newColumnId === 'CONVERTED' &&
								starColumnId != newColumnId
							)
							{
								this.convertPopup(dragItemId);
							}

					}.bind(this);

					var cancelButton = BX.create('span', {
						attrs: { className: 'crm-kanban-item-remove-confirm' },
						events: {
							click: function ()
							{
								removeCancel(draggableItemInner, innerContent);
								draggableItem.classList.remove('crm-kanban-deal-item-deleted');
							}.bind(this)
						},
						html: BX.message('CRM_KANBAN_FAIL_CONFIRM_CANCEL')
					});

					var removeCancel = function (item, zone)
					{
						if(item.id === zone.parentNode.dataset.item)
						{
							BX.cleanNode(innerContent);
							droppableZoneInner.className = 'crm-kanban-dropzone-item';
						}

						var removeButton = document.querySelectorAll('.crm-kanban-item-remove-confirm');

						if(removeButton.length === 0)
						{
							this.dropContainer.classList.remove('crm-kanban-dropzone-show');
						}
					}.bind(this);

					innerContent.appendChild(cancelButton);

					draggableItem.classList.add('crm-kanban-deal-item-deleted');

					var droppableZone = this.droppableZone;

					this.timeoutId = setTimeout(function ()
					{
						clearZone(droppableZone, oldColumnId, targetColumnId, draggableItemInner);
					}.bind(this),8000);

					if(removeId)
					{
						startActionUp(+removeId, this.kanban.items[+removeId].columnId, this.droppableZone.dataset.id, this.droppableZone);
					}

					setTimeout(function(){
						removeCancel(draggableItemInner, innerContent)
					}, 8000);
				}

			}
			else
			{
				this.dropContainer.className = "crm-kanban-dropzone";

				if (this.droppableItem)
				{

					this.droppableItem.layout.container.style = '';
					this.droppableItem.layout.containerPre.style.height = '';
					this.droppableItem.layout.container.classList.remove('crm-kanban-deal-item-pre');
					var targetColumn = this.kanban.getColumn(this.droppableItem.columnId);
					var targetColumnId = this.droppableItem.columnId;
					this.dropContainer.className = "crm-kanban-dropzone";

					if((this.draggableItem.columnId == 'CONVERTED') && (this.droppableItem.columnId !== 'CONVERTED'))
					{
						targetColumn = this.kanban.getColumn(this.draggableItem.columnId);
						this.kanban.moveItemToColum(this.draggableItem, targetColumn, this.preDraggableItem);
						stopOpoup.show();

						setTimeout(function ()
						{
							stopOpoup.close();
						}, 5000)
					}
					else
					{
						this.kanban.moveItemToColum(this.draggableItem, targetColumn, this.droppableItem);
					}

				}
				else if (this.droppableColumn)
				{
					var targetColumnId = this.droppableColumn.id;
					this.droppableColumn.layout.container.className = 'crm-kanban-grid-item';
					this.droppableColumn.layout.itemsPre.style.height = "";
					this.dropContainer.className = "crm-kanban-dropzone";

					if((this.draggableItem.columnId == 'CONVERTED') && (this.droppableColumn.id !== 'CONVERTED'))
					{
						targetColumn = this.kanban.getColumn(this.draggableItem.columnId);
						this.kanban.moveItemToColum(this.draggableItem, targetColumn, this.preDraggableItem);
						stopOpoup.show();

						setTimeout(function ()
						{
							stopOpoup.close();
						}, 5000)
					}
					else
					{
						this.kanban.moveItemToColum(this.draggableItem, this.droppableColumn);
					}
				}

				if (
					BX.CrmKanbanHelper.getInstance().getEntityType() === 'INVOICE' &&
					(targetColumnId === 'P' || this.droppableZone) &&
					oldColumnId != targetColumnId
				)
				{
					this.invoicePopup(this.draggableItem.id, targetColumnId, oldColumnId);
				}
				else
				{
					BX.CrmKanbanHelper.getInstance().moveState(this.draggableItem.id, targetColumnId, oldColumnId);
				}
				if (
					BX.CrmKanbanHelper.getInstance().getEntityType() === 'LEAD' &&
					targetColumnId === 'CONVERTED' &&
					oldColumnId != targetColumnId
				)
				{
					this.convertPopup(this.draggableItem.id);
				}
			}
		}

		setTimeout(function ()
		{
			var itemsPre = this.kanban.container.querySelectorAll('.crm-kanban-loadmore-pre');
			for(var i = 0; i < itemsPre.length; i++)
			{
				itemsPre[i].style.height = '';
			}
		}.bind(this), 50);

		this.stub.parentNode.removeChild(this.stub);
		this.stub = null;
		this.draggableItem = null;
		this.droppableColumn = null;
		this.droppableItem = null;
		this.droppableZone = null;
		this.scrollZone = null;
		this.timer = null;
	};
}