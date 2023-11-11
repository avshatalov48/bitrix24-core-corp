(function() {

	//"use strict";


	BX.namespace("BX.CRM.Kanban");

	/**
	 *
	 * @param options
	 * @extends {BX.Kanban.Column}
	 * @constructor
	 */
	BX.CRM.Kanban.Column = function(options)
	{
		BX.Kanban.Column.apply(this, arguments);
	};

	BX.CRM.Kanban.Column.prototype = {
		__proto__: BX.Kanban.Column.prototype,
		constructor: BX.CRM.Kanban.Column,
		renderSubtitleTime: 6,
		subtitleNode: null,
		pathToAdd: null,
		editorNodeWaiting: null,
		editorNodeIsBlock: null,
		editorNodeIsVisible: false,
		editorNode: null,
		editorNodeContainer: null,
		editorNodeCreate: null,
		editorNodeSelectFields: null,
		editorNodeSelectPopup: null,
		editorLoaded: false,
		editorOpen: false,
		quickFormSaveButton: null,
		quickFormCancelButton: null,
		editorId: null,
		editor: null,
		loader: null,
		isKeyMetaPressed: false,
		clickStatus: null,
		cancelEditHandler: null,
		blockSize: 20,

		/**
		 * Custom format method from BXcrm-kanban-quick-form-show .2s cubic-bezier(0.88, -0.08, 0.46, 0.91) forwards.Currency.
		 * @param {float} price Price.
		 * @param {string} currency Currency.
		 * @param {boolean} useTemplate Use or not template.
		 * @returns {string}
		 */
		currencyFormat: function (price, currency, useTemplate)
		{
			var result = "",
				format;

			if (typeof BX.Currency === "undefined")
			{
				return price;
			}

			useTemplate = !!useTemplate;
			format = BX.Currency.getCurrencyFormat(currency);

			if (!!format && typeof format === "object")
			{
				format.CURRENT_DECIMALS = format.DECIMALS;
				format.HIDE_ZERO = "Y";//always
				if (format.HIDE_ZERO === "Y" && price == parseInt(price, 10))
				{
					format.CURRENT_DECIMALS = 0;
				}

				result = BX.util.number_format(
					price,
					format.CURRENT_DECIMALS,
					format.DEC_POINT,
					format.THOUSANDS_SEP
				);
				if (useTemplate)
				{
					result = format.FORMAT_STRING.replace(/(^|[^&])#/, "$1" + result);
				}
			}
			return result;
		},

		/**
		 * Decrement total price of column.
		 * @param {Number} val Value to decrement.
		 * @returns {void}
		 */
		decPrice: function(val)
		{
			var data = this.getData();
			data.sum = parseFloat(data.sum) - val;
			this.setData(data);
		},

		/**
		 * Increment total price of column.
		 * @param {Integer} val Value to increment.
		 * @returns {void}
		 */
		incPrice: function(val)
		{
			var data = this.getData();
			data.sum = parseFloat(data.sum) + val;
			this.setData(data);
		},

		/**
		 * Return add-button for new column.
		 * @returns {DOM|null}
		 */
		getAddColumnButton: function ()
		{
			var columnData = this.getData();

			if (columnData.type === "WIN")
			{
				this.layout.info.style.marginRight = "0";
				return BX.create("div");
			}
			else
			{
				return BX.Kanban.Column.prototype.getAddColumnButton.apply(this, arguments);
			}
		},

		/**
		 * Get path for add mew element.
		 * @returns {string}
		 */
		getAddPath: function()
		{
			if (this.pathToAdd !== null)
			{
				return this.pathToAdd;
			}

			var gridData = this.getGridData();
			var type = gridData.entityType.toLowerCase();
			var wrapperId, button;

			if (type === "invoice")
			{
				wrapperId = "crm_invoice_toolbar";
			}
			else if (type === "order")
			{
				wrapperId = "toolbar_order_kanban";
			}
			else
			{
				wrapperId = "toolbar_" + type + "_list";
			}

			if (BX(wrapperId))
			{
				button = BX(wrapperId).querySelector("a");
				if (BX.type.isDomNode(button))
				{
					this.pathToAdd = button.getAttribute("href");
					this.pathToAdd += this.pathToAdd.indexOf("?") === -1 ? "?" : "&";
				}
			}

			return this.pathToAdd;
		},

		/**
		 *
		 * @param {BX.CRM.Kanban.Item} item
		 * @param {BX.CRM.Kanban.Item} beforeItem
		 */
		addItem: function(item, beforeItem)
		{
			if (!(item instanceof BX.Kanban.Item))
			{
				throw new Error("item must be an instance of BX.Kanban.Item");
			}

			if(item.layout.container && item.layout.container.classList.contains("main-kanban-item-disabled"))
			{
				BX.removeClass(item.layout.container, "main-kanban-item-disabled");
			}

			var oldColumnId = item.getColumnId();
			item.setColumnId(this.getId());
			//? setGrid

			if(item.checked)
			{
				item.unSelectItem();
			}

			var index = BX.util.array_search(beforeItem, this.items);
			var items = this.getItems();
			var alreadySet = false;

			for (const itemId in items)
			{
				if (items[itemId].id === item.getId())
				{
					items[itemId] = item;
					alreadySet = true;
				}
			}

			if (!alreadySet)
			{
				if (index >= 0)
				{
					this.items.splice(index, 0, item);
				}
				else
				{
					this.items.push(item);
				}

				if (item.isCountable())
				{
					this.incrementTotal();
				}
			}

			this.setPullItemBackground(item);
			item.animate({
				duration: this.getGrid().animationDuration / 4,
				draw: function(progress) {
					const currentHeight = item.layout.container.scrollHeight * progress;
					item.layout.container.style.height = `${currentHeight}px`;
					item.layout.container.style.zIndex = -1;
				},
				useAnimation: item.useAnimation
			}).then(function(){
				this.setPullItemBackground(item, '#fff');
				item.useAnimation = false;

				Object.assign(
					item.layout.container.style,
					{
						height: 'auto',
						opacity: '100%',
						zIndex: null,
					}
				);

				BX.Event.EventEmitter.emit(
					'Crm.Kanban.Column:onItemAdded',
					{
						item:item,
						targetColumn: this,
						beforeItem: beforeItem,
						oldColumn: this.grid.getColumn(oldColumnId),
					});
			}.bind(this));

			if (this.getGrid().isRendered())
			{
				this.render();
			}
		},

		addItems: function(items, beforeItem)
		{
			if(!items)
			{
				items = this.getGrid().getChecked();
			}

			var forSend = [];

			var index = BX.util.array_search(beforeItem, this.items);

			var afterItemId = 0;
			var afterItem = this.getPreviousItemSibling(beforeItem);
			if (afterItem)
			{
				afterItemId = afterItem.getId();
			}

			for (var i = 0; i < items.length; i++)
			{
				items[i].visible = true;

				if(items[i].getColumn() !== this)
				{

					items[i].getColumn().decPrice(items[i].data.price);
					items[i].getColumn().renderSubTitle();
					this.incPrice(items[i].data.price);
				}

				if(items[i].layout.container && items[i].layout.container.classList.contains("main-kanban-item-disabled"))
				{
					BX.removeClass(items[i].layout.container, "main-kanban-item-disabled");
				}

				items[i].setColumnId(this.getId());

				//? setGrid

				if(items[i].checked)
				{
					items[i].unSelectItem();
				}

				var itemIndex = BX.util.array_search(items[i], this.items);

				if(beforeItem)
				{
					if (itemIndex >= 0)
					{
						this.items.splice(itemIndex, 0, items[i]);
					}
					else
					{
						this.items.splice(index, 0, items[i]);
					}
				}
				else
				{
					this.items.splice(this.items.length, 0, items[i]);
				}

				if(items[i].isCountable())
				{
					this.incrementTotal();
				}

				items[i].parentColumn = null;

				forSend.push(items[i].getId());
			}

			// ajax
			this.getGrid().ajax(
				{
					action: "status",
					entity_id: forSend,
					prev_entity_id: afterItemId,
					status: this.getId()
				},
				(data) => {
					if (!data)
					{
						return;
					}

					if (data.error)
					{
						BX.Kanban.Utils.showErrorDialog(data.error, true);
					}
					else if (data.IS_SHOULD_UPDATE_CARD)
					{
						const useAnimation = (!Array.isArray(forSend) || forSend.length <= 1);
						void this.getGrid().loadNew(forSend, false, true, true, useAnimation);
					}
				},
				(error) => BX.Kanban.Utils.showErrorDialog('Error: ' + error, true)
			);

			if (this.getGrid().isRendered())
			{
				// crutches for real total items
				const arr = [];

				for (const prop in this.items)
				{
					if (!BX.util.in_array(this.items[prop].id, arr))
					{
						arr.push(this.items[prop].id);
					}
				}

				this.render();
			}
		},

		onDragDrop: function(itemNode, x, y)
		{
			this.hideDragTarget();

			var event,
				success;

			var draggableItem = this.getGrid().getItemByElement(itemNode);

			event = new BX.Kanban.DragEvent();
			event.setItem(draggableItem);
			event.setTargetColumn(this);


			BX.onCustomEvent(this.getGrid(), "Kanban.Grid:onBeforeItemMoved", [event]);

			if (!event.isActionAllowed())
			{
				return;
			}

			success = this.getGrid().moveItem(draggableItem, this);

			if (success)
			{
				BX.onCustomEvent(this.getGrid(), "Kanban.Grid:onItemMoved", [draggableItem, this, null]);
			}
		},

		/**
		 * Saving quick editor form.
		 * @return void
		 */
		processQuickEditor: function()
		{
			this.editor.save();
		},

		/**
		 * Reset loaded editor form.
		 * @returns {void}
		 */
		resetQuickEditor: function()
		{
			this.editorNodeContainer.style.height = this.editorNodeContainer.offsetHeight + "px";
			this.editorNodeContainer.innerHTML = "";
		},

		/**
		 * Gets quick editor instance.
		 * @return {BX.Crm.EntityEditor}
		 */
		getQuickEditor: function()
		{
			return this.editor;
		},

		/**
		 * Show quick editor form.
		 * @param {boolean} hidden
		 * @returns {void}
		 */
		showQuickEditor: function(hidden)
		{
			if(!hidden)
			{
				this.editorOpen = true;
			}

			this.getBody().scrollTop = 0;

			var gridData = this.getGridData();
			var entityType = gridData.entityType;
			var categoryId = gridData.params.CATEGORY_ID
							? parseInt(gridData.params.CATEGORY_ID)
							: 0;
			this.editorId = "quick_editor_v6_" + this.getId() + "_" + entityType.toLowerCase() + "_" + categoryId;

			if (!this.getGrid().getTypeInfoParam('isQuickEditorEnabled'))
			{
				return;
			}
			var isFactoryBasedApproach = this.getGrid().getTypeInfoParam('useFactoryBasedApproach');
			if (
				typeof gridData.quickEditorPath[entityType.toLowerCase()] === "undefined"
				&& !isFactoryBasedApproach
			)
			{
				return;
			}

			var params = gridData.params;
			params['VIEW_MODE'] = gridData.viewMode;

			var context = {
				PARAMS: params,
			};
			context[this.getGrid().getTypeInfoParam('stageIdKey')] = this.getId();

			// fields for form
			var formFields = this.getGrid().getTypeInfoParam('defaultQuickFormFields');

			if (!this.editorNodeContainer.innerHTML)
			{
				if(!hidden)
				{
					this.layout.subTitleAddButton.classList.add("crm-kanban-column-add-item-button-wait");
					this.disabledAddButton();
				}

				if (isFactoryBasedApproach)
				{
					BX.ajax.runAction('crm.api.item.getEditor',  {
						data: {
							entityTypeId: gridData.entityTypeInt,
							id: 0,
							stageId: this.getId(),
							categoryId: gridData.params.CATEGORY_ID ? gridData.params.CATEGORY_ID : 0,
							guid: this.editorId,
							configId: gridData.editorConfigId,
							viewMode: gridData.viewMode,
							params: {
								'ENABLE_PERSONAL_CONFIGURATION_UPDATE': true,
								'ENABLE_COMMON_CONFIGURATION_UPDATE': true,
								'ENABLE_CONFIG_SCOPE_TOGGLE': true,
								'ENABLE_SETTINGS_FOR_ALL': true,
							}
						}
					}).then(function (response){
						var result = BX.processHTML(response.data.html);

						this.editorNodeContainer.innerHTML = response.data.html;
						this.editorNodeContainer.appendChild(this.editorNodeCreate);

						this.editorNode.style.height = "0px";

						BX.ajax.processScripts(result.SCRIPT, undefined, function(){
							var interval = setInterval(function ()
							{
								if (this.editorNodeContainer.offsetHeight < 150)
								{
									return
								}

								if (!this.editorOpen)
								{
									this.layout.subTitleAddButton.classList.remove("crm-kanban-column-add-item-button-wait");
									return;
								}
								if (hidden)
								{
									return;
								}

								this.editorNode.style.height = this.editorNodeContainer.offsetHeight + "px";
								this.layout.subTitleAddButton.classList.remove("crm-kanban-column-add-item-button-wait");

								var autoHideEditor = function ()
								{
									this.editorNode.style.height = null;
									BX.unbind(this.editorNode, 'transitionend', autoHideEditor);
								}.bind(this);

								BX.bind(this.editorNode, 'transitionend', autoHideEditor);
								clearInterval(interval);
							}.bind(this), 100);
						}.bind(this));
					}.bind(this));
				}
				else
				{
					BX.ajax.post(
						gridData.quickEditorPath[entityType.toLowerCase()],
						{
							ACTION: "PREPARE_EDITOR_HTML",
							ACTION_ENTITY_TYPE_NAME: entityType,
							ACTION_ENTITY_ID: 0,
							GUID: this.editorId,
							CONFIG_ID: gridData.editorConfigId,
							FORCE_DEFAULT_CONFIG: "N",
							FORCE_DEFAULT_OPTIONS: "Y",
							IS_EMBEDDED: "Y",
							ENABLE_CONFIG_SCOPE_TOGGLE: "Y",
							ENABLE_CONFIGURATION_UPDATE: "Y",
							ENABLE_REQUIRED_USER_FIELD_CHECK: "Y",
							ENABLE_FIELDS_CONTEXT_MENU: "N",
							FIELDS: formFields,
							CONTEXT: context
						},
						function (result)
						{
							this.editorNodeContainer.innerHTML = result;
							this.editorNodeContainer.appendChild(this.editorNodeCreate);

							if (!this.editorOpen)
							{
								this.layout.subTitleAddButton.classList.remove("crm-kanban-column-add-item-button-wait");
								return;
							}

							if (hidden)
							{
								return;
							}

							this.editorNode.style.height = "0px";

							var interval = setInterval(function (){
								if (this.editorNodeContainer.offsetHeight < 150)
								{
									return
								}

								this.editorNode.style.height = this.editorNodeContainer.offsetHeight + "px";
								this.layout.subTitleAddButton.classList.remove("crm-kanban-column-add-item-button-wait");

								var autoHideEditor = function ()
								{
									this.editorNode.style.height = null;
									BX.unbind(this.editorNode, 'transitionend', autoHideEditor);
								}.bind(this);

								BX.bind(this.editorNode, 'transitionend', autoHideEditor);
								clearInterval(interval);
							}.bind(this), 100);
						}.bind(this)
					);
				}
			}
			else
			{
				this.getLoader().hide();
				this.hideQuickEditorLoader();
			}

			// catch editor instance after load
			if (!this.editorLoaded)
			{
				BX.addCustomEvent(
					window,
					"BX.Crm.EntityEditor:onInit",
					function(sender, eventArgs)
					{
						if (sender.getId() === this.editorId)
						{
							this.editor = sender;
						}
					}.bind(this)
				);

				BX.addCustomEvent(
					window,
					"onCrmEntityCreateError",
					function(params)
					{
						if (typeof params.error !== "undefined")
						{
							this.hideQuickEditorLoader();

							this.openQuickFormPartialEditor(Object.keys(params.checkErrors));
						}
					}.bind(this)
				);

				if (!this.cancelEditHandler)
				{
					this.cancelEditHandler = function(params)
					{
						this.hideQuickEditorLoader();
					}.bind(this);

					BX.addCustomEvent(
						window,
						"BX.Crm.EntityEditor:onFailedValidation",
						this.cancelEditHandler
					);

					BX.addCustomEvent(
						window,
						"BX.Crm.EntityEditor:onRestrictionAction",
						this.cancelEditHandler
					);
				}

				BX.addCustomEvent(
					window,
					'BX.Crm.EntityEditorAjax:onSubmitFailure',
					function(errors)
					{
						if(this.editorOpen)
						{
							this.quickFormSaveButton.classList.remove("ui-btn-wait");
							this.editorNode.classList.remove("crm-kanban-quick-form-wait");

							var message = '';
							var requiredFields = [];
							for (var i in errors)
							{
								if(errors.hasOwnProperty(i) && errors[i].message)
								{
									if(
										errors[i].code === 'CRM_FIELD_ERROR_REQUIRED'
										&& errors[i].customData
										&& errors[i].customData.fieldName
									)
									{
										requiredFields.push(errors[i].customData.fieldName);
									}
									message += errors[i].message + ', ';
								}
							}

							if(requiredFields.length > 0)
							{
								this.openQuickFormPartialEditor(requiredFields);
							}
							else
							{
								BX.Kanban.Utils.showErrorDialog(BX.Text.encode(message), true);
							}
						}
					}.bind(this)
				);

				BX.addCustomEvent(
					window,
					"onCrmEntityCreate",
					function(entityData)
					{
						var context = entityData.sender.getContext();
						var statusKey = this.getGrid().getTypeInfoParam('stageIdKey');
						if (context[statusKey] === this.getId())
						{
							this.getGrid().loadNew(
								entityData.entityId,
								true,
								false,
								false,
								true,
							);
						}

						if(this.editorOpen)
						{
							this.hideQuickEditorLoader();
							entityData.isCancelled = true;
						}
					}.bind(this)
				);

				var currentColumn = this;

				BX.addCustomEvent("CRM.Kanban.Column:clickAddButton", function() {
					if(currentColumn !== this)
					{
						currentColumn.hideQuickFormEditor();
						currentColumn.enabledAddButton();
						currentColumn.cleanEditor();
					}
				});

				BX.bind(window, "keydown", function(ev) {
					if(	ev.code === "MetaRight" ||
						ev.code === "MetaLeft" ||
						ev.code === "ControlRight" ||
						ev.code === "ControlLeft" )
					{
						this.isKeyMetaPressed = true;
					}
				}.bind(this));

				BX.bind(window, "keyup", function(ev) {
					if(	ev.code === "MetaRight" ||
						ev.code === "MetaLeft" ||
						ev.code === "ControlRight" ||
						ev.code === "ControlRight" )
					{
						this.isKeyMetaPressed = false;
					}
				}.bind(this));

				BX.bind(window, "keydown", function(ev) {
					if(
						(ev.code === "Enter" || ev.code === "NumpadEnter")
						&& this.isKeyMetaPressed && this.editorOpen)
					{
						this.processQuickEditor();
						this.showQuickEditorLoader();
						BX.PreventDefault(ev);
					}
				}.bind(this));

				BX.addCustomEvent(window, "BX.CRM.Kanban.Item.select", this.hideQuickFormEditor.bind(this));
				BX.addCustomEvent(window, "BX.CRM.Kanban.Item.select", this.enabledAddButton.bind(this));
				//BX.addCustomEvent(window, "Kanban.Column:render", this.hideQuickFormEditor.bind(this));
				BX.addCustomEvent(window, "onCrmEntityCreate", this.hideQuickFormEditor.bind(this));
				BX.addCustomEvent(window, "Kanban.Column:render", this.enabledAddButton.bind(this));
				BX.addCustomEvent(window, "Kanban.Grid:onItemDragStart", this.enabledAddButton.bind(this));
				BX.addCustomEvent(window, "Kanban.Grid:onItemDragStart", function()
				{
					if(this.editorOpen)
					{
						BX.bind(this.editorNode, "transitionend", function() {
							for (var i = 0; i < this.items.length; i++)
							{
								this.items[i].makeDroppable();
							}
						}.bind(this))
					}

					this.hideQuickFormEditor();
					this.enabledAddButton();
				}.bind(this));
			}

			this.editorLoaded = true;

			this.layout.items.insertBefore(this.editorNode, this.layout.items.firstChild);
		},

		openQuickFormPartialEditor: function(fieldNames)
		{
			if (
				!this.editorOpen
				||
				(this.quickFormPartialEditor && this.quickFormPartialEditor._isLocked)
			)
			{
				return;
			}
			var formData = new FormData(this.editor._ajaxForm._elementNode),
				presetValues = {};

			var formDataEntries = formData.entries(),
				formDataEntry = formDataEntries.next(),
				pair;

			while (!formDataEntry.done) {
				pair = formDataEntry.value;
				if (presetValues[pair[0]] === undefined)
				{
					presetValues[pair[0]] = [];
				}
				presetValues[pair[0]].push(pair[1]);
				formDataEntry = formDataEntries.next();
			}

			var gridData = this.grid.getData();
			var context = {};
			context[this.getGrid().getTypeInfoParam('stageIdKey')] = this.id;
			context['NOT_CHANGE_STATUS'] = 'Y';

			var settings = {
				entityTypeId: gridData.entityTypeInt,
				entityId: 0,
				fieldNames: fieldNames,
				context: context,
				values: [],
				presetValues: presetValues,
			};

			if(this.getGrid().getTypeInfoParam('useFactoryBasedApproach'))
			{
				settings.title = BX.message('CRM_TYPE_ITEM_PARTIAL_EDITOR_TITLE');
				settings.isController = true;
				settings.entityTypeName = gridData.entityType;
				settings.stageId = this.getId();
			}
			else
			{
				settings.title = BX.message(
					"CRM_KANBAN_REQUIRED_FIELDS_TITLE_" + gridData.entityType
				)
			}

			this.quickFormPartialEditor = BX.Crm.QuickFormPartialEditorDialog.create(
				"quickform-partial-entity-editor",
				settings
			);
			this.quickFormPartialEditor.open();
		},

		isEditorOpen: function()
		{
			return this.editorOpen;
		},

		showQuickEditorLoader: function()
		{
			this.quickFormSaveButton.classList.add("ui-btn-wait");
			this.editorNode.classList.add("crm-kanban-quick-form-wait");
		},

		hideQuickEditorLoader: function()
		{
			this.quickFormSaveButton.classList.remove("ui-btn-wait");
			this.editorNode.classList.remove("crm-kanban-quick-form-wait");
		},

		/**
		 * Hide quick form editor.
		 * @return {void}
		 */
		hideQuickFormEditor: function()
		{
			if(!this.editorOpen)
			{
				return
			}

			this.editorOpen = false;
			this.editorNode.style.height = this.editorNode.offsetHeight + "px";

			setTimeout(function(){
				this.editorNode.style.height = "0px";
			}.bind(this), 10);
		},

		disabledAddButton: function()
		{
			BX.addClass(this.layout.subTitleAddButton, "crm-kanban-column-add-item-button-event");
		},

		enabledAddButton: function()
		{
			BX.removeClass(this.layout.subTitleAddButton, "crm-kanban-column-add-item-button-event");
		},

		/**
		 * Is quick form popup?
		 * @param {Element} target
		 * @return {boolean}
		 */
		isQuickFormPopup: function(target)
		{
			return BX.findParent(target, {
				className: "popup-window"
			});
		},

		/**
		 * Is target bound to document or was removed in another callback of this onclick event?
		 * @param {Element} target
		 * @return {boolean}
		 */
		isBoundToDocument: function(target)
		{
			return !!target.closest('body');
		},

		/**
		 * Is quick form editor?
		 * @param {Element} target
		 * @return {boolean}
		 */
		isQuickFormEditor: function(target)
		{
			return BX.findParent(target, {
				className: "ui-entity-editor-column-content"
			});
		},

		/**
		 * Renders subtitle content.
		 * @returns {Element}
		 */
		renderSubTitle: function()
		{
			var data = this.getData();
			var gridData = this.getGridData();

			if (this.canAddItem === null)
			{
				this.canAddItem = true;
			}

			// render layout first time
			if (this.getGrid().getTypeInfoParam('showTotalPrice'))
			{
				if (!this.layout.subTitlePrice)
				{
					this.layout.subTitlePriceText = BX.create("span", {
						attrs: {
							className: "crm-kanban-total-price-total"
						}
					});
					this.layout.subTitlePrice = BX.create("div", {
						attrs: {
							className: "crm-kanban-total-price"
						},
						children: [
							this.layout.subTitlePriceText
						]
					});
				}
			}
			else
			{
				this.layout.subTitlePrice = null;
			}

			// animate change
			if (this.layout.subTitlePriceText)
			{
				data.sum = parseFloat(data.sum);
				data.sum_init = data.sum;
				data.sum_old = data.sum_old ? data.sum_old : data.sum_init;

				if (this.subTitleAnimationInterval)
				{
					clearInterval(this.subTitleAnimationInterval);
				}

				this.subTitleAnimationInterval = this.renderSubTitleAnimation(
					data.sum_old,
					data.sum,
					Math.abs(data.sum_old - data.sum) / 20,
					this.layout.subTitlePriceText,
					function (element, value)
					{
						element.innerHTML = this.currencyFormat(
							Math.round(value),
							gridData.currency,
							true
						);
						data.sum_old = data.sum;
					}.bind(this)
				);

				this.setData(data);
			}

			if (this.subtitleNode)
			{
				return this.subtitleNode;
			}

			// create sum and button if no exists

			var plusTitle = '',
				quickForm = true;

			if (data.sort === 100 && this.getGrid().getTypeInfoParam('hasPlusButtonTitle'))
			{
				plusTitle = gridData.isDynamicEntity
					? BX.message('CRM_KANBAN_PLUS_TITLE_DYNAMIC')
					: (BX.message(`CRM_KANBAN_PLUS_TITLE_${gridData.entityType}`) || BX.message(`CRM_KANBAN_PLUS_TITLE_${gridData.entityType}_MSGVER_1`));
			}

			if (quickForm)
			{
				this.editorNode = BX.create("div", {
					props: {
						className: "crm-kanban-quick-form"
					},
					style: {
						height: "0px"
					},
					children: [
						this.editorNodeContainer = BX.create("div", {
							props: {
								className: "crm-kanban-quick-form-container"
							}
						})
					]
				});

				this.editorNodeCreate = BX.create("div", {
					props: {
						className: "crm-kanban-quick-form-buttons"
					},
					children: [
						this.quickFormSaveButton = BX.create("input", {
							attrs: {
								type: "button",
								value: BX.message("CRM_KANBAN_POPUP_SAVE"),
								className: "ui-btn ui-btn-xs ui-btn-primary"
							},
							events: {
								click: function(ev) {
									this.processQuickEditor();
									this.showQuickEditorLoader();
									BX.PreventDefault(ev);
								}.bind(this)
							}
						}),
						this.quickFormCancelButton = BX.create("input", {
							attrs: {
								type: "button",
								value: BX.message("CRM_KANBAN_CONFIRM_N"),
								className: "ui-btn ui-btn-xs ui-btn-link"
							},
							events: {
								click: function() {
									this.enabledAddButton();
									this.hideQuickFormEditor();
									this.cleanEditor();
								}.bind(this)
							}
						})
					]
				})
			}

			var stageIdKey = this.getGrid().getTypeInfoParam('stageIdKey');
			stageIdKey = stageIdKey.toLowerCase();

			if (
				this.canAddItem
				&& this.getGrid().getTypeInfoParam('isQuickEditorEnabled')
			)
			{
				this.layout.subTitleAddButton = BX.create("div", {
					text: plusTitle,
					attrs: {
						className: "crm-kanban-column-add-item-button"
					},
					events: {
						click: quickForm
							? function(ev) {
								const tariffRestrictions = gridData.tariffRestrictions || {};
								if (tariffRestrictions.addItemNotPermittedByTariff === true)
								{
									BX.Crm.Router.Instance.showFeatureSlider();
									return;
								}
							// @todo Checking bx-ie is still actually?
								if(document.getElementsByTagName("html")[0].classList.contains("bx-ie"))
								{
									if(gridData.entityType === "LEAD")
									{
										BX.SidePanel.Instance.open("/crm/lead/details/0/?category_id=" + gridData.params.CATEGORY_ID);
									}
									else if(gridData.entityType === "DEAL")
									{
										BX.SidePanel.Instance.open("/crm/deal/details/0/");
									}
									return;
								}

								if(BX.hasClass(this.layout.subTitleAddButton, "crm-kanban-column-add-item-button-event"))
								{
									return;
								}

								this.disabledAddButton();

								if(!this.editorNodeContainer.innerHTML)
								{
									var columns = this.getGrid().getColumns();

									for (var i = 0; i < columns.length; i++)
									{
										if(columns[i] !== this)
										{
											if(columns[i].editor)
											{
												columns[i].editor.release();
												columns[i].editor = null;
												columns[i].editorOpen = false;
												columns[i].editorLoaded = false;
												BX.cleanNode(columns[i].editorNodeContainer);
											}

											columns[i].hideQuickFormEditor();
											columns[i].enabledAddButton();
											columns[i].cleanEditor();
										}
									}

									this.showQuickEditor();
									return;
								}

								BX.onCustomEvent(this, "CRM.Kanban.Column:clickAddButton", this);

								if(!this.editorNode.parentNode)
								{
									this.layout.items.insertBefore(this.editorNode, this.layout.items.firstElementChild);
								}

								this.getBody().scrollTop = 0;

								this.editorNode.style.height = "0px";
								this.editorOpen = true;

								setTimeout(function(){
									this.editorNode.style.height = this.editorNodeContainer.offsetHeight + "px";

									var autoHideEditor = function()
									{
										this.editorNode.style.height = null;
										BX.unbind(this.editorNode, 'transitionend', autoHideEditor);
									}.bind(this);

									BX.bind(this.editorNode, 'transitionend', autoHideEditor);


									if(this.editor)
									{
										this.editor.refreshLayout({ reset: true });
									}
								}.bind(this), 10);
							}.bind(this)
							: null
					}
				});
			}
			else if (this.canAddItem)
			{
				this.layout.subTitleAddButton = (
					this.getAddPath()
						? BX.create("a", {
							text: plusTitle,
							attrs: {
								className: "crm-kanban-column-add-item-button",
								href: this.getAddPath() + stageIdKey + '=' + this.getId()
							},
						}) : null
				);
			}
			else if (this.isShowHiddenAddItemButton())
			{
				this.layout.subTitleAddButton = BX.Tag.render`
					<div class="crm-kanban-column-add-item-button--dummy"</div>
				`;
			}

			this.subtitleNode = BX.create("div", {
				children: [
					this.layout.subTitlePrice,
					quickForm
					// quick form for some types and first column
					? this.layout.subTitleAddButton
					// just a button for new window
					: (
						this.getAddPath()
							? BX.create("a", {
								text: plusTitle,
								attrs: {
									className: "crm-kanban-column-add-item-button",
									href: this.getAddPath() + stageIdKey + '=' + this.getId()
								}
							}) : null
					),
					this.editorNode
				]
			});

			return this.subtitleNode;
		},

		isShowHiddenAddItemButton: function()
		{
			const columns = this.getGrid().getColumns();
			return columns.some(column => column.canAddItem);
		},

		cleanEditorNode: function()
		{
			BX.cleanNode(this.editorNodeContainer);
		},

		cleanEditor: function()
		{
			if(this.editor) {
				this.editor.rollback();
				this.editor.refreshLayout();
			}
		},

		/**
		 * Gets system loader.
		 * @return {Element}
		 */
		getLoader: function()
		{
			if(!this.loader)
			{
				this.loader = new BX.Loader({
					target: this.editorNode
				});
			}

			return this.loader;
		},

		/**
		 * Animate change from start to val with step in element.
		 * @param {Number} start
		 * @param {Number} value
		 * @param {Number} step
		 * @param {DOM} element
		 * @param {Function} finalCall Call finally for element with val.
		 * @returns {void}
		 */
		renderSubTitleAnimation: function(start, value, step, element, finalCall)
		{
			var i = start;
			var val = parseFloat(value);
			var timeout = this.renderSubtitleTime;

			if (i === val)
			{
				if (typeof finalCall === 'function')
				{
					finalCall(element, value);
				}
				return;
			}

			var sign = (start > value ? 'minus' : 'plus');

			var condition = function(currentValue){
				return (sign === 'plus' ? (value < currentValue) : (value > currentValue));
			};

			if (start > val)
			{
				step = -1 * step;
			}

			var timer = setInterval(function() {
				element.textContent = BX.util.number_format(i, 0, ",", " ");
				i += step;
				if (condition(i))
				{
					clearInterval(timer);
					this.subTitleAnimationInterval = null;
					finalCall(element, value);
				}
			}, timeout);

			return timer;
		},

		/**
		 * Hook on add column button.
		 * @param {MouseEvent} event
		 * @returns {void}
		 */
		handleAddColumnButtonClick: function(event)
		{
			var gridData = this.getGridData();
			// if no access, show access-query popup
			if (
				gridData.rights &&
				gridData.rights.canAddColumn
			)
			{
				this.getGrid().getColumns().forEach(function(column){
					column.getAddColumnButton().style.visibility = 'hidden';
				});

				var newColumn = this.getGrid().addColumn({
					id: "kanban-new-column-" + BX.util.getRandomString(5),
					type: "BX.CRM.Kanban.DraftColumn",
					canSort: false,
					canAddItem: false,
					droppable: false,
					targetId: this.getGrid().getNextColumnSibling(this)
				});

				newColumn.switchToEditMode();
			}
			else if (typeof BX.Intranet !== "undefined")
			{
				this.getGrid().accessNotify();
			}
		},

		/**
		 * Switch from view to edit mode (column).
		 * @returns {void}
		 */
		switchToEditMode: function()
		{
			var gridData = this.getGridData();
			// if no access, show access-query popup
			if (
				gridData.rights &&
				gridData.rights.canAddColumn
			)
			{
				BX.Kanban.Column.prototype.switchToEditMode.apply(this, arguments);
			}
			else if (typeof BX.Intranet !== "undefined")
			{
				this.getGrid().accessNotify();
			}
		},

		focusTextBox: function()
		{
			setTimeout(function () {
				this.getTitleTextBox().focus();
			}.bind(this))
		},

		makeDroppable: function()
		{
			if (!this.isDroppable())
			{
				return;
			}

			var columnBody = this.getBody();

			columnBody.onbxdestdraghover = BX.delegate(this.onDragEnter, this);
			columnBody.onbxdestdraghout = BX.delegate(this.onDragLeave, this);
			columnBody.onbxdestdragfinish = BX.delegate(this.onDragDrop, this);

			columnBody.onbxdestdragstop = BX.delegate(this.onItemDragEnd, this);

			jsDD.registerDest(columnBody, 10);

			this.disableDropping();
		},

		/**
		 *
		 * @param {BX.CRM.Kanban.Item} itemToRemove
		 */
		removeItem: function(itemToRemove)
		{
			return new Promise(function(resolve, reject)
			{
				var found = false;
				this.items = this.items.filter(function(item) {

					if (item === itemToRemove)
					{
						found = true;
						return false;
					}

					return true;
				});

				if (found)
				{
					const currentOpacityPercent = itemToRemove.layout.container.style.opacity * 100;
					const useAnimation = (currentOpacityPercent === 100 ? itemToRemove.useAnimation : false);

					this.setPullItemBackground(itemToRemove);
					itemToRemove.animate({
						useAnimation,
						duration: this.getGrid().animationDuration,
						draw: function(progress) {
							const opacity = currentOpacityPercent - progress * currentOpacityPercent + '%';
							itemToRemove.layout.container.style.opacity = opacity;
						},
					}).then(function(value){
						if (itemToRemove.isCountable() && itemToRemove.isVisible())
						{
							this.decrementTotal();
							this.getGrid().resetMultiSelectMode();
						}
						if (this.getGrid().isRendered())
						{
							this.render();
						}
						resolve();
					}.bind(this));
				}
				else
				{
					resolve();
				}
			}.bind(this));
		},

		/**
		 *
		 * @param {BX.CRM.Kanban.Item} item
		 * @param {string} backgroundColor
		 */
		setPullItemBackground: function(item, backgroundColor = '#fffabf')
		{
			if (item.changedInPullRequest && item.layout.bodyContainer.children[0])
			{
				item.layout.bodyContainer.children[0].style.backgroundColor = backgroundColor;
			}
		},

		cleanLayoutItems: function()
		{
			var childNodes = Array.from(this.layout.items.childNodes);
			childNodes.map(function(item, index){
				if (item.classList.contains('main-kanban-item'))
				{
					this.layout.items.removeChild(item);
				}
			}.bind(this));
		}
	};

	BX.CRM.Kanban.DraftColumn = function(options)
	{
		BX.Kanban.DraftColumn.apply(this, arguments);
	};

	BX.CRM.Kanban.DraftColumn.prototype = {
		__proto__: BX.Kanban.DraftColumn.prototype,
		constructor: BX.CRM.Kanban.DraftColumn,
		handleRemoveButtonClick: function(event)
		{
			this.getGrid().getColumns().forEach(function(column){
				column.getAddColumnButton().style.visibility = null;
			});
			BX.Kanban.DraftColumn.prototype.handleRemoveButtonClick.apply(this, arguments);
		},
	}
})();
