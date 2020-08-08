BX.namespace("BX.Crm");

//region CONTROLLERS
if(typeof BX.Crm.EditorFieldSingleEditController === "undefined")
{
	BX.Crm.EditorFieldSingleEditController = function()
	{
		this._id = "";
		this._settings = null;
		this._field = null;
		this._wrapper = null;

		this._fieldWrapperHandler = BX.delegate(this.onFieldWrapperClick, this);
		this._documentHandler = BX.delegate(this.onDocumentClick, this);
		this._documentTimeoutHandle = 0;

		this._isInitialized = false;
		this._isActive = false;
	};
	BX.Crm.EditorFieldSingleEditController.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};

				this._time = (new Date()).toString();

				this._field = BX.prop.get(this._settings, "field");
				if(!(this._field instanceof BX.Crm.EntityEditorField))
				{
					throw "EditorFieldSingleEditController: The 'field' param must be EntityEditorField.";
				}

				this._wrapper = this._field.getWrapper();
				if(!BX.type.isElementNode(this._wrapper))
				{
					throw "EditorFieldSingleEditController: Could not find the wrapper element.";
				}

				window.setTimeout(BX.delegate(this.bind, this), 100);
				this._isActive = this._isInitialized = true;
			},
			isActive: function()
			{
				return this._isActive;
			},
			setActive: function(active)
			{
				this._isActive = !!active;
			},
			setActiveDelayed: function(active, delay)
			{
				if(typeof(delay) === "undefined")
				{
					delay = 0;
				}

				window.setTimeout(
					BX.delegate(function(){ this.setActive(active); }, this),
					delay
				);
			},
			release: function()
			{
				this._isActive = this._isInitialized = false;
				this.unbind();
			},
			bind: function()
			{
				if(this._isInitialized)
				{
					BX.bind(this._wrapper, "click", this._fieldWrapperHandler);
					BX.bind(document, "click", this._documentHandler);
				}
			},
			unbind: function()
			{
				BX.unbind(this._wrapper, "click", this._fieldWrapperHandler);
				BX.unbind(document, "click", this._documentHandler);
			},
			saveControl: function()
			{
				if(!this._isActive)
				{
					return;
				}

				var editor = this._field.getEditor();
				if(editor)
				{
					editor.switchControlMode(this._field, BX.Crm.EntityEditorMode.view, BX.Crm.EntityEditorModeOptions.none);
					//Is not supported by the all controls
					//editor.saveControl(this._field);
				}

				this._isActive = false;
			},
			onFieldWrapperClick: function(e)
			{
				//The call of "preventDefault" is not allowed because of the checkbox controls
				BX.eventCancelBubble(e);
			},
			onDocumentClick: function(e)
			{
				if(this._documentTimeoutHandle > 0)
				{
					window.clearTimeout(this._documentTimeoutHandle);
					this._documentTimeoutHandle = 0;
				}

				this._documentTimeoutHandle = window.setTimeout(
					BX.delegate(this.saveControl, this),
					400
				);
			}
		};
	BX.Crm.EditorFieldSingleEditController.create = function(id, settings)
	{
		var self = new BX.Crm.EditorFieldSingleEditController();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EditorFieldViewController === "undefined")
{
	BX.Crm.EditorFieldViewController = function()
	{
		this._id = "";
		this._settings = null;
		this._field = null;
		this._wrapper = null;

		this._timeoutHandle = 0;
		this._time = 0;
		this._pos = { x: 0, y: 0 };

		this._mouseDownHandler = BX.delegate(this.onMouseDown, this);
		this._mouseUpHandler = BX.delegate(this.onMouseUp, this);

		this._isInitialized = false;
		this._isActive = false;
	};
	BX.Crm.EditorFieldViewController.prototype =
		{
			initialize: function (id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};

				this._field = BX.prop.get(this._settings, "field");
				if (!(this._field instanceof BX.Crm.EntityEditorField)) {
					throw "EditorFieldViewController: The 'field' param must be EntityEditorField.";
				}

				this._wrapper = BX.prop.getElementNode(this._settings, "wrapper");
				if (!BX.type.isElementNode(this._wrapper)) {
					throw "EditorFieldSingleEditController: Could not find the wrapper element.";
				}

				window.setTimeout(BX.delegate(this.bind, this), 100);
				this._isActive = this._isInitialized = true;
			},
			release: function()
			{
				this._isActive = this._isInitialized = false;
				this.unbind();
			},
			bind: function()
			{
				if(this._isInitialized)
				{
					BX.bind(this._wrapper, "mousedown", this._mouseDownHandler);
					BX.bind(this._wrapper, "mouseup", this._mouseUpHandler);
				}
			},
			unbind: function()
			{
				BX.unbind(this._wrapper, "mousedown", this._mouseDownHandler);
				BX.unbind(this._wrapper, "mouseup", this._mouseUpHandler);
			},
			onMouseDown: function(e)
			{
				if(this._timeoutHandle > 0)
				{
					window.clearTimeout(this._timeoutHandle);
					this._timeoutHandle = 0;
				}

				if(!this.isHandleableEvent(e))
				{
					return;
				}

				this._time = new Date().valueOf();
				this._pos = { x: e.clientX, y: e.clientY };
			},
			onMouseUp: function(e)
			{
				if(this._timeoutHandle > 0)
				{
					window.clearTimeout(this._timeoutHandle);
					this._timeoutHandle = 0;
				}

				if(!this.isHandleableEvent(e))
				{
					return;
				}

				//console.log(new Date().valueOf() - this._time);
				//console.log(Math.abs(this._pos.x - e.clientX));
				if((new Date().valueOf() - this._time) < 400 || Math.abs(this._pos.x - e.clientX) < 2)
				{
					this._timeoutHandle = window.setTimeout(
						function()
						{
							this.switchTo(BX.getEventTarget(e));
						}.bind(this),
						0
					);
				}

				this._time = 0;
			},
			isHandleableEvent: function(e)
			{
				var node = BX.getEventTarget(e);
				if(node.tagName === "A")
				{
					return false;
				}

				if(node.getAttribute("data-editor-control-type") === "button")
				{
					return false;
				}

				return !BX.findParent(node, { tagName: "a" }, this._wrapper);
			},
			switchTo: function(targetNode)
			{
				this._field.switchToSingleEditMode(targetNode);
			}
		};
	BX.Crm.EditorFieldViewController.create = function(id, settings)
	{
		var self = new BX.Crm.EditorFieldViewController();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorController === "undefined")
{
	BX.Crm.EntityEditorController = function()
	{
		this._id = "";
		this._settings = {};

		this._editor = null;
		this._model = null;
		this._config = null;

		this._isChanged = false;
	};
	BX.Crm.EntityEditorController.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};

				this._editor = BX.prop.get(this._settings, "editor", null);
				this._model = BX.prop.get(this._settings, "model", null);
				this._config = BX.prop.getObject(this._settings, "config", {});

				this.doInitialize();
			},
			doInitialize: function()
			{
			},
			getConfig: function()
			{
				return this._config;
			},
			getConfigStringParam: function(name, defaultValue)
			{
				return BX.prop.getString(this._config, name, defaultValue);
			},
			isChanged: function()
			{
				return this._isChanged;
			},
			markAsChanged: function()
			{
				if(this._isChanged)
				{
					return;
				}

				this._isChanged = true;
				if(this._editor)
				{
					this._editor.processControllerChange(this);
				}
			},
			release: function()
			{
			},
			rollback: function()
			{
			},
			innerCancel: function()
			{
			},
			/**
			 *
			 * @param result BX.Crm.EntityValidationResult
			 * @returns {null|Promise}
			 */
			validate: function(result)
			{
				return null;
			},
			onBeforeSubmit: function()
			{
			},
			onAfterSave: function()
			{
				if(this._isChanged)
				{
					this._isChanged = false;
				}
			},
			onBeforesSaveControl: function(data)
			{
				return data;
			}
		};
}

if(typeof BX.Crm.EntityEditorProductRowProxy === "undefined")
{
	BX.Crm.EntityEditorProductRowProxy = function()
	{
		BX.Crm.EntityEditorProductRowProxy.superclass.constructor.apply(this);
		this._externalEditor = null;
		this._editorCreateHandler = null;
		this._sumTotalChangeHandler = null;
		this._productAddHandler = null;
		this._productChangeHandler = null;
		this._productRemoveHandler = null;
		this._editorModeChangeHandler = BX.delegate(this.onEditorModeChange, this);
		this._editorControlChangeHandler = BX.delegate(this.onEditorControlChange, this);

		this._currencyId = "";
		this._isManualOpportunity = null;
		this._prevProductCount = 0;
		this._curProductCount = 0;
	};
	BX.extend(BX.Crm.EntityEditorProductRowProxy, BX.Crm.EntityEditorController);
	BX.Crm.EntityEditorProductRowProxy.prototype.doInitialize = function()
	{
		BX.Crm.EntityEditorProductRowProxy.superclass.doInitialize.apply(this);

		this._sumTotalChangeHandler = BX.delegate(this.onSumTotalChange, this);
		this._productAddHandler = BX.delegate(this.onProductAdd, this);
		this._productChangeHandler = BX.delegate(this.onProductChange, this);
		this._productRemoveHandler = BX.delegate(this.onProductRemove, this);

		var externalEditor = typeof BX.CrmProductEditor !== "undefined"
			? BX.CrmProductEditor.get(this.getExternalEditorId()) : null;
		if(externalEditor)
		{
			this.setExternalEditor(externalEditor);
		}
		else
		{
			this._editorCreateHandler = BX.delegate(this.onEditorCreate, this);
			BX.addCustomEvent(window, "ProductRowEditorCreated", this._editorCreateHandler);
		}

		this._editor.addModeChangeListener(this._editorModeChangeHandler);

		BX.addCustomEvent(window, "onEntityDetailsTabShow", BX.delegate(this.onTabShow, this));

		setTimeout(BX.delegate(function()
		{
			var opportunityControl = this.getOpportunityControl();
			if (opportunityControl)
			{
				opportunityControl.addChangeAmountEditModeListener(this.onchangeAmountEditMode.bind(this));
			}
		}, this), 0);
		this._isManualOpportunity = this._model.getField('IS_MANUAL_OPPORTUNITY');
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.getOpportunityControl = function()
	{
		var opportunityControl = this._editor.getControlByIdRecursive('OPPORTUNITY_WITH_CURRENCY');
		if (opportunityControl instanceof BX.Crm.EntityEditorMoney)
		{
			return opportunityControl;
		}
		return null;
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.onTabShow = function(tab)
	{
		if(tab.getId() !== "tab_products")
		{
			return;
		}

		if(this._externalEditor && !this._externalEditor.hasLayout())
		{
			this._externalEditor.layout();
		}
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.getExternalEditorId = function()
	{
		return this.getConfigStringParam("editorId", "");
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.setExternalEditor = function(editor)
	{
		if(this._externalEditor === editor)
		{
			return;
		}

		if(this._externalEditor)
		{
			this._externalEditor.setForm(null);
			BX.removeCustomEvent(this._externalEditor, "sumTotalChange", this._sumTotalChangeHandler);
			BX.removeCustomEvent(this._externalEditor, "productAdd", this._productAddHandler);
			BX.removeCustomEvent(this._externalEditor, "productChange", this._productChangeHandler);
			BX.removeCustomEvent(this._externalEditor, "productRemove", this._productRemoveHandler);
		}

		this._externalEditor = editor;

		this._prevProductCount = 0;
		this._curProductCount = 0;
		if(this._externalEditor)
		{
			this._externalEditor.setForm(this._editor.getFormElement());
			BX.addCustomEvent(this._externalEditor, "sumTotalChange", this._sumTotalChangeHandler);
			BX.addCustomEvent(this._externalEditor, "productAdd", this._productAddHandler);
			BX.addCustomEvent(this._externalEditor, "productChange", this._productChangeHandler);
			BX.addCustomEvent(this._externalEditor, "productRemove", this._productRemoveHandler);
			this._prevProductCount = this._curProductCount = this._externalEditor.getProductCount();

			this.adjustLocks();
		}
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.isManualOpportunity = function()
	{
		return (this._model.getField('IS_MANUAL_OPPORTUNITY') =='Y');
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.adjustLocks = function()
	{
		if (!this.isManualOpportunity())
		{
			this.doAdjustLocks();
		}
	}
	BX.Crm.EntityEditorProductRowProxy.prototype.doAdjustLocks = function()
	{
		if(!this._externalEditor)
		{
			return;
		}

		if(this._externalEditor.getProductCount() > 0)
		{
			this._model.lockField("OPPORTUNITY");
		}
		else
		{
			this._model.unlockField("OPPORTUNITY");
		}
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.adjustTotals = function(totals, needMarkAsChanged)
	{
		var opportunityControl = this.getOpportunityControl();
		if (
			opportunityControl &&
			opportunityControl.getManualOpportunityValue() === 'Y' &&
			this._curProductCount === 0 &&
			this._externalEditor.getProductCount() > 0 // only when product was added first time
		)
		{
			var popup = BX.Crm.EditorAuxiliaryDialog.getById("manual_opportunity_mode_selector");
			if (popup)
			{
				popup.close();
			}

			BX.Crm.EditorAuxiliaryDialog.create(
				"manual_opportunity_mode_selector",
				{
					title: BX.Crm.EntityEditorProductRowProxy.messages.manualOpportunityChangeModeTitle,
					content: BX.Crm.EntityEditorProductRowProxy.messages.manualOpportunityChangeModeText,
					zIndex: 100,
					overlay: true,
					buttons:
						[
							{
								id: "yes",
								type: BX.Crm.DialogButtonType.accept,
								text: BX.Crm.EntityEditorProductRowProxy.messages.manualOpportunityChangeModeYes,
								callback: BX.delegate(function(event)
								{
									event.getDialog().close();
									this.setManualOpportunity(false);
									this.doAdjustTotals(totals);
									if (needMarkAsChanged)
									{
										this.markAsChanged();
									}
								}, this)
							},
							{
								id: "no",
								type: BX.Crm.DialogButtonType.cancel,
								text: BX.Crm.EntityEditorProductRowProxy.messages.manualOpportunityChangeModeNo,
								callback: BX.delegate(function(event)
								{
									event.getDialog().close();
									this.setManualOpportunity(true);
									this.doAdjustTotals(totals);
									if (needMarkAsChanged)
									{
										this.markAsChanged();
									}
								}, this)
							}
						]
				}
			).open();
		}
		else
		{
			this.doAdjustTotals(totals);
			if (needMarkAsChanged)
			{
				this.markAsChanged();
			}
		}
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.doAdjustTotals = function(totals)
	{
		if (this.isManualOpportunity())
		{
			return;
		}
		this._model.setField(
			"FORMATTED_OPPORTUNITY",
			totals["FORMATTED_SUM"],
			{ enableNotification: false }
		);

		this._model.setField(
			"FORMATTED_OPPORTUNITY_WITH_CURRENCY",
			totals["FORMATTED_SUM_WITH_CURRENCY"],
			{ enableNotification: false }
		);

		this._model.setField(
			"OPPORTUNITY",
			totals["SUM"],
			{ enableNotification: true }
		);
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.setManualOpportunity = function(isManual)
	{
		if(isManual)
		{
			this._model.unlockField("OPPORTUNITY");
		}
		else
		{
			this.doAdjustLocks();
		}
		this._model.setField(
			"IS_MANUAL_OPPORTUNITY",
			isManual ? 'Y' : 'N',
			{ enableNotification: true }
		);
		this.markAsChanged();
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.notifyOpportunityControl = function()
	{
		var opportunityControl = this.getOpportunityControl();
		if (opportunityControl)
		{
			opportunityControl.setHasRelatedProducts(this._externalEditor ? this._externalEditor.getProductCount() > 0 : false);
		}
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.onEditorCreate = function(sender)
	{
		if(sender.getId() !== this.getExternalEditorId())
		{
			return;
		}

		BX.removeCustomEvent(window, "ProductRowEditorCreated", this._editorCreateHandler);
		delete(this._editorCreateHandler);
		this.setExternalEditor(sender);
		this.notifyOpportunityControl();
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.onEditorModeChange = function(sender)
	{
		if(this._editor.getMode() === BX.Crm.EntityEditorMode.edit)
		{
			this._editor.addControlChangeListener(this._editorControlChangeHandler);
		}
		else
		{
			this._editor.removeControlChangeListener(this._editorControlChangeHandler);
		}

		this._isChanged = false;
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.onEditorControlChange = function(sender, params)
	{
		if(!this._externalEditor)
		{
			return;
		}

		var name = BX.prop.getString(params, "fieldName", "");
		if(name !== "CURRENCY_ID")
		{
			return;
		}

		var currencyId = BX.prop.getString(params, "fieldValue", "");
		if(currencyId !== "")
		{
			this._currencyId = currencyId;
			this._externalEditor.setCurrencyId(this._currencyId);
		}
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.onProductAdd = function(product)
	{
		this.adjustLocks();
		this.markAsChanged();
		this.notifyOpportunityControl();
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.onProductChange = function(product)
	{
		this.adjustLocks();
		this.markAsChanged();
		this.notifyOpportunityControl();
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.onProductRemove = function(product)
	{
		this.adjustLocks();
		this.markAsChanged();
		this.notifyOpportunityControl();
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.onSumTotalChange = function(totalSum, allTotals, needMarkAsChanged)
	{
		this.adjustTotals(
			{
				"FORMATTED_SUM_WITH_CURRENCY": allTotals["TOTAL_SUM_FORMATTED"],
				"FORMATTED_SUM": allTotals["TOTAL_SUM_FORMATTED_SHORT"],
				"SUM": allTotals["TOTAL_SUM"]
			},
			needMarkAsChanged
		);
		this._prevProductCount = this._curProductCount;
		this._curProductCount = this._externalEditor ? this._externalEditor.getProductCount() : 0;
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.onAfterSave = function()
	{
		BX.Crm.EntityEditorProductRowProxy.superclass.onAfterSave.apply(this);
		this._isManualOpportunity = this._model.getField("IS_MANUAL_OPPORTUNITY", null);
		if (this._manualOpportunityHiddenInput)
		{
			BX.Dom.remove(this._manualOpportunityHiddenInput);
			this._manualOpportunityHiddenInput = null;
		}
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.rollback = function()
	{
		var currencyId = this._model.getField("CURRENCY_ID", "");
		if(this._currencyId !== currencyId)
		{
			this._currencyId = currencyId;
			if(this._externalEditor)
			{
				this._externalEditor.setCurrencyId(this._currencyId);
			}
		}
		if (this._isManualOpportunity)
		{
			this.setManualOpportunity(this._isManualOpportunity === 'Y');
		}
		this.adjustLocks();
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.onBeforeSubmit = function()
	{
		if(this._externalEditor)
		{
			this._externalEditor.handleFormSubmit();
		}

		var form = this._editor.getFormElement();
		var isManualOpportunity = this._model.getField("IS_MANUAL_OPPORTUNITY", null);
		if (isManualOpportunity !== null)
		{
			if (!BX.findChild(
				form,
				{tagName: "input", attr: {name: 'IS_MANUAL_OPPORTUNITY'}},
				true,
				false
			))
			{
				this._manualOpportunityHiddenInput = BX.create("input",
					{
						attrs:
							{
								name: 'IS_MANUAL_OPPORTUNITY',
								type: "hidden",
								value: isManualOpportunity
							}
					}
				);
				form.appendChild(this._manualOpportunityHiddenInput);
			}
		}
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.onBeforesSaveControl = function(data)
	{
		if(this._externalEditor)
		{
			data = this._externalEditor.handleControlSave(data);
		}
		return data;
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.onchangeAmountEditMode = function(field, isManual)
	{
		if (isManual)
		{
			if (!BX.Crm.EditorAuxiliaryDialog.isItemOpened("enable_manual_opportunity_confirmation"))
			{

				BX.Crm.EditorAuxiliaryDialog.create(
					"enable_manual_opportunity_confirmation",
					{
						title: BX.Crm.EntityEditorProductRowProxy.messages.manualOpportunityConfirmationTitle,
						content: BX.Crm.EntityEditorProductRowProxy.messages.manualOpportunityConfirmationText,
						zIndex: 100,
						overlay: true,
						buttons:
							[
								{
									id: "yes",
									type: BX.Crm.DialogButtonType.accept,
									text: BX.Crm.EntityEditorProductRowProxy.messages.manualOpportunityConfirmationYes,
									callback: BX.delegate(function(event)
									{
										event.getDialog().close();
										this.setManualOpportunity(true);
									}, this)
								},
								{
									id: "no",
									type: BX.Crm.DialogButtonType.cancel,
									text: BX.Crm.EntityEditorProductRowProxy.messages.manualOpportunityConfirmationNo,
									callback: function(event)
									{
										event.getDialog().close();
									}
								}
							]
					}
				).open();
			}
		}
		else
		{
			this._model.setField("OPPORTUNITY","");
			this.setManualOpportunity(false);
			if (this._externalEditor)
			{
				this._externalEditor.calculateTotals();
			}
		}
	};
	BX.Crm.EntityEditorProductRowProxy.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorProductRowProxy();
		self.initialize(id, settings);
		return self;
	}
}

//endregion
