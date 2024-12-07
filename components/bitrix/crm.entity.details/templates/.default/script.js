BX.namespace("BX.Crm");

//region MANAGER
if(typeof BX.Crm.EntityDetailManager === "undefined")
{
	BX.Crm.EntityDetailManager = function()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
		this._entityTypeId = 0;
		this._entityId = 0;
		this._serviceUrl = "";
		this._tabManager = null;
		this._overlay = null;
		this._pageUrlCopyButton = null;
		this._externalEventHandler = null;
		this._externalRequestData = null;
	};
	BX.Crm.EntityDetailManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._entityTypeId = BX.prop.getInteger(this._settings, "entityTypeId", 0);
			this._entityId = BX.prop.getInteger(this._settings, "entityId", 0);

			this._serviceUrl = BX.prop.getString(this._settings, "serviceUrl", "");

			this._container = BX(BX.prop.get(this._settings, "containerId"));

			this._tabManager = BX.Crm.EntityDetailTabManager.create(
				this._id,
				{
					container: BX(BX.prop.get(this._settings, "tabContainerId")),
					menuId: BX.prop.get(this._settings, "tabMenuContainerId"),
					data: BX.prop.getArray(this._settings, "tabs")
				}
			);

			if(this._entityId <= 0)
			{
				this._overlay = BX.create("div", { attrs: { className: "crm-entity-overlay" } });
				this._container.appendChild(this._overlay);

				if(window === window.top)
				{
					this._overlay.style.position = "absolute";
					this._overlay.style.top = this._overlay.style.left = this._overlay.style.right = "-15px";
				}
			}

			this._pageUrlCopyButton = BX("page_url_copy_btn");
			if(this._pageUrlCopyButton)
			{
				this._pageUrlCopyButton.title = this.getMessage("copyPageUrl");
				BX.bind(this._pageUrlCopyButton, "click", BX.delegate(this.onCopyCurrentPageUrl, this));
			}

			BX.addCustomEvent(window, "OpenEntityDetailTab", BX.delegate(this.onTabOpenRequest, this));
			this._externalRequestData = {};

			this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
			BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);

			BX.addCustomEvent(window, "BX.Crm.EntityEditor:onFailedValidation", this.onFailedValidation.bind(this));

			this.doInitialize();
		},
		doInitialize: function()
		{
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			return BX.prop.getString(BX.Crm.EntityDetailManager.messages, name, name);
		},
		getEntityTypeId: function()
		{
			return this._entityTypeId;
		},
		getEntityTypeName: function()
		{
			return BX.CrmEntityType.resolveName(this._entityTypeId);
		},
		getEntityId: function()
		{
			return this._entityId;
		},
		getCurrentPageUrl: function()
		{
			return BX.util.remove_url_param(window.location.href, ["IFRAME", "IFRAME_TYPE"]);
		},
		getEntityListUrl: function(entityTypeName)
		{
			return BX.prop.getString(
				BX.Crm.EntityDetailManager.entityListUrls,
				entityTypeName,
				""
			);
		},
		getEntityCreateUrl: function(entityTypeName)
		{
			return BX.CrmEntityManager.getCurrent().getEntityCreateUrl(entityTypeName);
		},
		getTabManager: function()
		{
			return this._tabManager;
		},
		prepareCreationUrlParams: function(urlParams)
		{
		},
		onCopyCurrentPageUrl: function(e)
		{
			var url = this.getCurrentPageUrl();
			if(!BX.clipboard.copy(url))
			{
				return;
			}

			var popup = new BX.PopupWindow(
				"crm_page_url_clipboard_copy",
				this._pageUrlCopyButton,
				{
					//content: BX.message('TASKS_TIP_TEMPLATE_LINK_COPIED'),
					content: this.getMessage("pageUrlCopied"),
					darkMode: true,
					autoHide: true,
					zIndex: 1000,
					angle: true,
					offsetLeft: 20,
					bindOptions: { position: "top" }
				}
			);
			popup.show();

			setTimeout(function(){ popup.close(); }, 1500);
		},
		onTabOpenRequest: function(tabName)
		{
			var item = this._tabManager.findItemById(tabName);
			if(item)
			{
				this._tabManager.selectItem(item);
			}
		},
		isTabButtonVisible: function(tabName)
		{
			const item = this._tabManager.findItemById(tabName);
			if (item)
			{
				return this._tabManager.isVisibleItemMenu(item);
			}

			return false;
		},
		getTabMenuItemContainer: function(tabName)
		{
			const item = this._tabManager.findItemById(tabName);
			if (item)
			{
				return item.getMenuContainer();
			}

			return null;
		},
		getTabFromMoreMenu: function(tabName)
		{
			const moreMenu = this._tabManager.getMoreMenu();
			if (moreMenu)
			{
				const menuItem = moreMenu.getMenuItem(tabName);
				const menuItemContainer = menuItem && menuItem.getContainer();
				if (BX.Type.isDomNode(menuItemContainer))
				{
					return menuItemContainer;
				}
			}

			return null;
		},
		processRemoval: function()
		{
			this._detetionConfirmDlgId = "entity_details_deletion_confirm";
			var dlg = BX.Crm.ConfirmationDialog.get(this._detetionConfirmDlgId);
			if(!dlg)
			{
				dlg = BX.Crm.ConfirmationDialog.create(
					this._detetionConfirmDlgId,
					{
						title: this.getMessage("deletionDialogTitle"),
						content: this.getMessage("deletionConfirmDialogContent")
					}
				);
			}
			dlg.open().then(BX.delegate(this.onRemovalConfirm, this));
		},
		remove: function()
		{
			if(this._serviceUrl === "")
			{
				throw "Crm.EntityDetailManager: The 'serviceUrl' parameter is not defined or empty.";
			}

			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION": "DELETE",
						"ACTION_ENTITY_TYPE_ID": this._entityTypeId,
						"ACTION_ENTITY_ID": this._entityId
					},
					onsuccess: BX.delegate(this.onRemovalRequestSuccess, this)
				}
			);
		},
		exclude: function ()
		{
			if(this._serviceUrl === "")
			{
				throw "Crm.EntityDetailManager: The 'serviceUrl' parameter is not defined or empty.";
			}

			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data:
						{
							"ACTION": "EXCLUDE",
							"ACTION_ENTITY_TYPE_ID": this._entityTypeId,
							"ACTION_ENTITY_ID": this._entityId
						},
					onsuccess: BX.delegate(this.onExclusionRequestSuccess, this)
				}
			);
		},
		processExclusion: function()
		{
			this._exclusionConfirmDlgId = "entity_details_exclusion_confirm";
			var dlg = BX.Crm.ConfirmationDialog.get(this._exclusionConfirmDlgId);
			if(!dlg)
			{
				dlg = BX.Crm.ConfirmationDialog.create(
					this._exclusionConfirmDlgId,
					{
						title: this.getMessage("exclusionDialogTitle"),
						content: this.getMessage("exclusionConfirmDialogContent")
							+ ' <a href="javascript: top.BX.Helper.show(\'redirect=detail&code=7362845\');">'
							+ this.getMessage("exclusionConfirmDialogContentHelp")
							+ '</a>'
					}
				);
			}
			dlg.open().then(BX.delegate(this.onExclusionConfirm, this));
		},
		createEntity: function(entityTypeName, options)
		{
			var context = ("details_" + this.getEntityTypeName() + "_" + this.getEntityId() + "_" + BX.util.getRandomString(12)).toLowerCase();
			var urlParams = { external_context: context };
			this.prepareCreationUrlParams(urlParams);

			if (options)
			{
				var additionalUrlParams = BX.prop.getObject(options, "urlParams", null);
				if(additionalUrlParams)
				{
					urlParams = BX.mergeEx(urlParams, additionalUrlParams);
				}
			}

			BX.CrmEntityManager.createEntity(
				entityTypeName,
				{ urlParams: urlParams }
				).then(
					function(result)
					{
						this._externalRequestData[context] = { context: context, wnd: BX.prop.get(result, "wnd", null) };
					}.bind(this)
				);
		},
		createQuote: function(options)
		{
			this.createEntity(BX.CrmEntityType.names.quote, options);
		},
		createOrder: function()
		{
			this.createEntity(BX.CrmEntityType.names.order);
		},
		createInvoice: function()
		{
			this.createEntity(BX.CrmEntityType.names.invoice);
		},
		createDeal: function(options)
		{
			this.createEntity(BX.CrmEntityType.names.deal, options);
		},
		onRemovalConfirm: function(result)
		{
			if(BX.prop.getBoolean(result, "cancel", true))
			{
				return;
			}

			this.remove();
		},
		extractUrlParamsFromEvent: function(callback)
		{
			return function(options)
			{
				if (options && options.data && options.data.urlParams)
				{
					options.urlParams = {
						...options.urlParams,
						...options.data.urlParams
					};
				}

				callback(options);
			};
		},
		onRemovalRequestSuccess: function(result)
		{
			var error = BX.prop.getString(result, "ERROR", "");
			if(error !== "")
			{
				var dlg = BX.Crm.NotificationDialog.create(
					"entity_details_deletion_error",
					{
						title: this.getMessage("deletionDialogTitle"),
						content: error
					}
				);
				dlg.open();
				return;
			}

			window.top.BX.UI.Notification.Center.notify(
				{
					autoHideDelay: 5000,
					content: this.getMessage("deletionWarning"),
					actions:
					[
						{
							title: this.getMessage("goToDetails"),
							events:
								{
									click:
										function(event, balloon, action)
										{
											balloon.close();

											//HACK: Try to get parent window from event for this window may be already deleted.
											var targetWindow = event.target.ownerDocument.defaultView;
											if(!targetWindow)
											{
												targetWindow = window;
											}

											if(targetWindow.BX.Helper)
											{
												targetWindow.BX.Helper.show("redirect=detail&code=8969825");
											}
										}
								}
						}
					]
				}
			);

			var current = BX.Crm.Page.getTopSlider();

			var eventParams = null;
			if(current)
			{
				eventParams = { "sliderUrl": current.getUrl() };
			}

			BX.Crm.EntityEvent.fireDelete(this._entityTypeId, this._entityId, "", eventParams);

			eventParams['id'] = this._entityId;
			BX.onCustomEvent(window, BX.Crm.EntityEvent.names.delete, [eventParams]);

			if(current)
			{
				window.setTimeout(function() { current.close(true); }, 100);
			}
			else
			{
				var listUrl = this.getEntityListUrl(BX.CrmEntityType.resolveName(this._entityTypeId));
				if(listUrl !== "")
				{
					window.location.href = listUrl;
				}
			}
		},
		onExclusionConfirm: function(result)
		{
			if(BX.prop.getBoolean(result, "cancel", true))
			{
				return;
			}

			this.exclude();
		},
		onExclusionRequestSuccess: function(result)
		{
			var error = BX.prop.getString(result, "ERROR", "");
			if(error !== "")
			{
				var dlg = BX.Crm.NotificationDialog.create(
					"entity_details_exclusion_error",
					{
						title: this.getMessage("exclusionDialogTitle"),
						content: error
					}
				);
				dlg.open();
				return;
			}

			var current = BX.Crm.Page.getTopSlider();

			var eventParams = null;
			if(current)
			{
				eventParams = { "sliderUrl": current.getUrl() };
			}

			BX.Crm.EntityEvent.fireDelete(this._entityTypeId, this._entityId, "", eventParams);

			eventParams['id'] = this._entityId;
			BX.onCustomEvent(window, BX.Crm.EntityEvent.names.delete, [eventParams]);

			if(current)
			{
				window.setTimeout(function() { current.close(true); }, 100);
			}
			else
			{
				var listUrl = this.getEntityListUrl(BX.CrmEntityType.resolveName(this._entityTypeId));
				if(listUrl !== "")
				{
					window.location.href = listUrl;
				}
			}
		},
		onExternalEvent: function(params)
		{
			var key = BX.prop.getString(params, "key", "");
			var data = BX.prop.getObject(params, "value", {});

			this.processExternalEvent(key, data);

			if(key === BX.Crm.EntityEvent.names.invalidate)
			{
				var entityId = BX.prop.getInteger(data, "entityId", 0);
				var entityTypeId = BX.prop.getInteger(data, "entityTypeId", 0);
				if(entityTypeId === this.getEntityTypeId() && entityId === this.getEntityId())
				{
					window.location.reload(true);
				}
				return;
			}

			if(key !== "onCrmEntityCreate")
			{
				return;
			}

			var context = BX.prop.getString(data, "context", "");
			var requestData = BX.prop.getObject(this._externalRequestData, context, null);
			if(!requestData)
			{
				return;
			}

			delete this._externalRequestData[context];

			var wnd = BX.prop.get(requestData, "wnd", null);
			if(wnd)
			{
				wnd.close();
			}
		},
		processExternalEvent: function(key, data)
		{
			return false;
		},
		/**
		 * @deprecated
		 */
		prepareAnalyticParams: function(action, contextParams)
		{
			return null;
		},
		onFailedValidation: function(sender, eventArgs)
		{
			if (
				typeof BX.Crm.EntityEditor === "undefined"
				|| !(sender instanceof BX.Crm.EntityEditor)
				|| sender.getEntityId() !== this.getEntityId()
			)
			{
				return;
			}

			var main = this._tabManager._items[0];
			if (main instanceof BX.Crm.EntityDetailTab && !main.isActive())
			{
				main.setActive(true);

				for(var i = 1, length = this._tabManager._items.length; i < length; i++)
				{
					var currentItem = this._tabManager._items[i];
					currentItem.setActive(false);
				}

				var field = eventArgs.getTopmostField();
				if(field)
				{
					setTimeout(function(){field.focus()}, 350);
				}
			}
		}
	};
	BX.Crm.EntityDetailManager.items = {};
	BX.Crm.EntityDetailManager.get = function(id)
	{
		return (BX.type.isNotEmptyString(id) && this.items.hasOwnProperty(id)) ? this.items[id] : null;
	};

	if(typeof(BX.Crm.EntityDetailManager.entityListUrls) === "undefined")
	{
		BX.Crm.EntityDetailManager.entityListUrls = {};
	}

	if(typeof(BX.Crm.EntityDetailManager.messages) === "undefined")
	{
		BX.Crm.EntityDetailManager.messages = {};
	}
	BX.Crm.EntityDetailManager.create = function(id, settings)
	{
		var self = new BX.Crm.EntityDetailManager();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	}
}
//endregion

//region LEAD MANAGER
if(typeof BX.Crm.LeadDetailManager === "undefined")
{
	BX.Crm.LeadDetailManager = function()
	{
		BX.Crm.LeadDetailManager.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.LeadDetailManager, BX.Crm.EntityDetailManager);
	BX.Crm.LeadDetailManager.prototype.doInitialize = function()
	{
		BX.addCustomEvent(window, "Crm.EntityConverter.Converted", BX.delegate(this.onConversionComplete, this));
		BX.addCustomEvent(window, "Crm.EntityProgress.Saved", BX.delegate(this.onProgressSave, this));
		BX.addCustomEvent(window, "CrmCreateQuoteFromLead", this.extractUrlParamsFromEvent(this.createQuote.bind(this)).bind(this));
		BX.addCustomEvent(window, "CrmCreateOrderFromLead", BX.delegate(this.onCreateOrder, this));
	};
	BX.Crm.LeadDetailManager.prototype.processConversionCompletion = function(eventArgs)
	{
		if(window.top !== window)
		{
			//Slider mode
			window.setTimeout(function(){ window.location.reload(true); }, 0);
			return;
		}

		//Page mode
		var redirectUrl = BX.prop.getString(eventArgs, "redirectUrl", "");
		if(redirectUrl !== "" && !BX.prop.getBoolean(eventArgs, "isRedirected", false))
		{
			window.setTimeout(
				function(){ window.location.replace(redirectUrl); },
				0
			);
			eventArgs["isRedirected"] = true;
		}
	};
	BX.Crm.LeadDetailManager.prototype.processStatusSave = function(eventArgs)
	{
		var current = BX.prop.getString(eventArgs, "currentSemantics", "");
		var previous = BX.prop.getString(eventArgs, "previousSemantics", "");

		if(previous === current)
		{
			return;
		}

		if(previous === "success" || current === "success")
		{
			window.setTimeout(function(){ window.location.reload(true); }, 0);
		}
	};
	BX.Crm.LeadDetailManager.prototype.processExternalEvent = function(name, eventArgs)
	{
		if(name !== "onCrmEntityConvert")
		{
			return false;
		}

		if(BX.prop.getInteger(eventArgs, "entityTypeId", 0) !== BX.CrmEntityType.enumeration.lead
			|| BX.prop.getInteger(eventArgs, "entityId", 0) !== this.getEntityId()
		)
		{
			return false;
		}

		this.processConversionCompletion(eventArgs);
		return true;
	};
	BX.Crm.LeadDetailManager.prototype.onConversionComplete = function(sender, eventArgs)
	{
		if(BX.prop.getInteger(eventArgs, "entityTypeId", 0) !== BX.CrmEntityType.enumeration.lead
			|| BX.prop.getInteger(eventArgs, "entityId", 0) !== this.getEntityId()
		)
		{
			return;
		}

		this.processConversionCompletion(eventArgs);
	};
	BX.Crm.LeadDetailManager.prototype.onProgressSave = function(sender, eventArgs)
	{
		if(BX.prop.getInteger(eventArgs, "entityTypeId", 0) !== BX.CrmEntityType.enumeration.lead
			|| BX.prop.getInteger(eventArgs, "entityId", 0) !== this.getEntityId()
		)
		{
			return;
		}

		this.processStatusSave(eventArgs);

	};
	BX.Crm.LeadDetailManager.prototype.onCreateQuote = function(option)
	{
		this.createQuote(option);
	};
	BX.Crm.LeadDetailManager.prototype.onCreateOrder = function()
	{
		this.createOrder();
	};
	BX.Crm.LeadDetailManager.prototype.prepareCreationUrlParams = function(urlParams)
	{
		urlParams["lead_id"] = this.getEntityId();
	};
	BX.Crm.LeadDetailManager.create = function(id, settings)
	{
		var self = new BX.Crm.LeadDetailManager();
		self.initialize(id, settings);
		BX.Crm.EntityDetailManager.items[self.getId()] = self;
		return self;
	}
}
//endregion

//region CONTACT MANAGER
if(typeof BX.Crm.ContactDetailManager === "undefined")
{
	BX.Crm.ContactDetailManager = function()
	{
		BX.Crm.ContactDetailManager.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.ContactDetailManager, BX.Crm.EntityDetailManager);
	BX.Crm.ContactDetailManager.prototype.doInitialize = function()
	{
		BX.addCustomEvent(window, "CrmCreateQuoteFromContact", this.extractUrlParamsFromEvent(this.createQuote.bind(this)).bind(this));
		BX.addCustomEvent(window, "CrmCreateInvoiceFromContact", BX.delegate(this.onCreateInvoice, this));
		BX.addCustomEvent(window, "CrmCreateDealFromContact", this.extractUrlParamsFromEvent(this.createDeal.bind(this)).bind(this));
		BX.addCustomEvent(window, "CrmCreateOrderFromContact", BX.delegate(this.onCreateOrder, this));
	};
	BX.Crm.ContactDetailManager.prototype.onCreateQuote = function(options)
	{
		this.createQuote(options);
	};
	BX.Crm.ContactDetailManager.prototype.onCreateOrder = function()
	{
		this.createOrder();
	};
	BX.Crm.ContactDetailManager.prototype.onCreateInvoice = function()
	{
		this.createInvoice();
	};
	BX.Crm.ContactDetailManager.prototype.onCreateDeal = function(options)
	{
		this.createDeal(options);
	};
	BX.Crm.ContactDetailManager.prototype.prepareCreationUrlParams = function(urlParams)
	{
		urlParams["contact_id"] = this.getEntityId();
	};
	BX.Crm.ContactDetailManager.create = function(id, settings)
	{
		var self = new BX.Crm.ContactDetailManager();
		self.initialize(id, settings);
		BX.Crm.EntityDetailManager.items[self.getId()] = self;
		return self;
	}
}
//endregion

//region COMPANY MANAGER
if(typeof BX.Crm.CompanyDetailManager === "undefined")
{
	BX.Crm.CompanyDetailManager = function()
	{
		BX.Crm.CompanyDetailManager.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.CompanyDetailManager, BX.Crm.EntityDetailManager);
	BX.Crm.CompanyDetailManager.prototype.doInitialize = function()
	{
		BX.addCustomEvent(window, "CrmCreateQuoteFromCompany", this.extractUrlParamsFromEvent(this.createQuote.bind(this)).bind(this));
		BX.addCustomEvent(window, "CrmCreateInvoiceFromCompany", BX.delegate(this.onCreateInvoice, this));
		BX.addCustomEvent(window, "CrmCreateDealFromCompany", this.extractUrlParamsFromEvent(this.createDeal.bind(this)).bind(this));
		BX.addCustomEvent(window, "CrmCreateOrderFromCompany", BX.delegate(this.onCreateOrder, this));
	};
	BX.Crm.CompanyDetailManager.prototype.onCreateQuote = function(options)
	{
		this.createQuote(options);
	};
	BX.Crm.CompanyDetailManager.prototype.onCreateOrder = function()
	{
		this.createOrder();
	};
	BX.Crm.CompanyDetailManager.prototype.onCreateInvoice = function()
	{
		this.createInvoice();
	};
	BX.Crm.CompanyDetailManager.prototype.onCreateDeal = function(options)
	{
		this.createDeal(options);
	};
	BX.Crm.CompanyDetailManager.prototype.prepareCreationUrlParams = function(urlParams)
	{
		urlParams["company_id"] = this.getEntityId();
	};
	BX.Crm.CompanyDetailManager.create = function(id, settings)
	{
		var self = new BX.Crm.CompanyDetailManager();
		self.initialize(id, settings);
		BX.Crm.EntityDetailManager.items[self.getId()] = self;
		return self;
	}
}
//endregion

//region DEAL RECURRING MANAGER
if(typeof BX.Crm.DealRecurringDetailManager === "undefined")
{
	BX.Crm.DealRecurringDetailManager = function()
	{
		BX.Crm.DealRecurringDetailManager.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.DealRecurringDetailManager, BX.Crm.EntityDetailManager);

	BX.Crm.DealRecurringDetailManager.prototype.doInitialize = function()
	{
		BX.addCustomEvent(window, "CrmDealRecurringExpose", BX.delegate(this.onExposeDeal, this));
	};
	BX.Crm.DealRecurringDetailManager.prototype.onExposeDeal = function(sender, eventArgs)
	{
		if(BX.prop.getInteger(eventArgs, "entityTypeId", 0) !== BX.CrmEntityType.enumeration.dealrecurring
			|| BX.prop.getInteger(eventArgs, "entityId", 0) !== this.getEntityId()
		)
		{
			return;
		}

		window.setTimeout(function(){ window.location.reload(true); }, 0);
	};
	BX.Crm.DealRecurringDetailManager.create = function(id, settings)
	{
		var self = new BX.Crm.DealRecurringDetailManager();
		self.initialize(id, settings);
		BX.Crm.EntityDetailManager.items[self.getId()] = self;
		return self;
	}
}
//endregion

//region DEAL MANAGER
if(typeof BX.Crm.DealDetailManager === "undefined")
{
	BX.Crm.DealDetailManager = function()
	{
		BX.Crm.DealDetailManager.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.DealDetailManager, BX.Crm.EntityDetailManager);
	BX.Crm.DealDetailManager.prototype.doInitialize = function()
	{
		//Managed by DealConverter
		//BX.addCustomEvent(window, "CrmCreateQuoteFromDeal", BX.delegate(this.onCreateQuote, this));
	};
	BX.Crm.DealDetailManager.prototype.prepareCreationUrlParams = function(urlParams)
	{
		urlParams["deal_id"] = this.getEntityId();
	};
	BX.Crm.DealDetailManager.create = function(id, settings)
	{
		var self = new BX.Crm.DealDetailManager();
		self.initialize(id, settings);
		BX.Crm.EntityDetailManager.items[self.getId()] = self;
		return self;
	}
}
//endregion
//region ORDER MANAGER
if(typeof BX.Crm.OrderDetailManager === "undefined")
{
	BX.Crm.OrderDetailManager = function()
	{
		BX.Crm.OrderDetailManager.superclass.constructor.apply(this);
	};

	BX.extend(BX.Crm.OrderDetailManager, BX.Crm.EntityDetailManager);

	BX.Crm.OrderDetailManager.prototype.doInitialize = function()
	{
		BX.addCustomEvent(window, "Crm.EntityProgress.Saved", BX.delegate(this.onProgressSave, this));
		BX.addCustomEvent(window, "Crm.EntityProgress.onSaveBefore", BX.delegate(this.onProgressSaveBefore, this));
		this.subscribeToProductRowSummaryEvents();
		this._isProductListFocusing = false;
		this._cancelReason = "";
	};
	BX.Crm.OrderDetailManager.prototype.onProgressSave = function(sender, eventArgs)
	{
		if(BX.prop.getInteger(eventArgs, "entityTypeId", 0) !== BX.CrmEntityType.enumeration.order
			|| BX.prop.getInteger(eventArgs, "entityId", 0) !== this.getEntityId()
		)
		{
			return;
		}

		var requestData = BX.prop.getObject(eventArgs, "requestData", {});
		var error = BX.prop.getString(requestData, "ERROR", "");
		if (BX.type.isNotEmptyString(error))
		{
			var step = sender.getStepById(BX.prop.getString(requestData, "STATUS_ID", ""));
			if(step)
			{
				var stepIndex = sender.findStepInfoIndex(step.getId());
				if(stepIndex >= 0)
				{
					var stepInfo = sender._stepInfos[stepIndex];
					sender.adjustSteps(step.getIndex(), step.getBackgroundColor());
					sender.setCurrentStep(stepInfo);
				}
			}

			var dlg = BX.Crm.NotificationDialog.create(
				"entity_details_cancel_error",
				{
					title: BX.prop.getString(requestData, "ERROR_TITLE", ""),
					content: error
				}
			);
			dlg.open();
		}
	};
	BX.Crm.OrderDetailManager.prototype.onProgressSaveBefore = function(sender, eventArgs)
	{
		if(BX.prop.getString(eventArgs, "TYPE", "") !== BX.CrmEntityType.names.order
			|| BX.prop.getInteger(eventArgs, "ID", 0) !== this.getEntityId()
		)
		{
			return;
		}

		var self = BX.CrmOrderStatusManager.current;

		if (BX.type.isPlainObject(self.saveParams))
		{
			var entityId = this.getEntityId();

			for (var name in self.saveParams)
			{
				BX.CrmOrderStatusManager.statusInfoValues[entityId][name] = self.saveParams[name];
				eventArgs[name] = self.saveParams[name];
			}
		}

		eventArgs['STATE_SUCCESS'] = self.isSuccess ? "Y" : "N";
	};

	BX.Crm.OrderDetailManager.prototype.subscribeToProductRowSummaryEvents = function()
	{
		BX.addCustomEvent(window, "BX.UI.EntityEditorProductRowSummary:onDetailProductListLinkClick", () => {
			BX.onCustomEvent(window, "OpenEntityDetailTab", ['tab_products']);
		});

		const onFirstProductListFocusHandler = () => {
			const onProductListInitHandler = () => {
				BX.onCustomEvent(window, "onFocusToProductList");
			}
			BX.addCustomEvent(window, "crmOrderProductListInit", onProductListInitHandler);
			onProductListFocusHandler();
			const onAfterFocusHandler = () => {
				BX.removeCustomEvent("BX.UI.EntityEditorProductRowSummary:onAddNewRowInProductList", onFirstProductListFocusHandler);
				BX.addCustomEvent(window, "BX.UI.EntityEditorProductRowSummary:onAddNewRowInProductList", onProductListFocusHandler);

				BX.removeCustomEvent("crmOrderProductListInit", onProductListInitHandler);
				BX.removeCustomEvent("crmOrderProductListFocused", onAfterFocusHandler);
			}
			BX.addCustomEvent(window, "crmOrderProductListFocused", onAfterFocusHandler);
		};
		BX.addCustomEvent(window, "BX.UI.EntityEditorProductRowSummary:onAddNewRowInProductList", onFirstProductListFocusHandler);

		const onProductListFocusHandler = () => {
			if (this._isProductListFocusing)
			{
				return;
			}
			else
			{
				this._isProductListFocusing = true;
			}

			BX.onCustomEvent(window, "OpenEntityDetailTab", ['tab_products']);
			setTimeout(() => {
				BX.onCustomEvent(window, 'onFocusToProductList');
			}, 200);
		}

		BX.addCustomEvent(window, "crmOrderProductListFocused", () => {
			this._isProductListFocusing = false;
		});
	};

	BX.Crm.OrderDetailManager.prototype.getMessage = function(name)
	{
		var m = BX.Crm.OrderDetailManager.messages;
		return (m.hasOwnProperty(name)
				? m[name]
				: BX.Crm.OrderDetailManager.superclass.getMessage.apply(this, arguments)
		);
	};
	BX.Crm.OrderDetailManager.create = function(id, settings)
	{
		var self = new BX.Crm.OrderDetailManager();
		self.initialize(id, settings);
		BX.Crm.EntityDetailManager.items[self.getId()] = self;
		return self;
	}
}
//endregion
//region FACTORY
if(typeof BX.Crm.EntityDetailFactory === "undefined")
{
	BX.Crm.EntityDetailFactory =
	{
		create: function(id, settings)
		{
			var entityTypeId = BX.prop.getInteger(settings, "entityTypeId", BX.CrmEntityType.enumeration.undefined);
			if(entityTypeId === BX.CrmEntityType.enumeration.lead)
			{
				return BX.Crm.LeadDetailManager.create(id, settings);
			}
			else if(entityTypeId === BX.CrmEntityType.enumeration.dealrecurring)
			{
				return BX.Crm.DealRecurringDetailManager.create(id, settings);
			}
			else if(entityTypeId === BX.CrmEntityType.enumeration.deal)
			{
				return BX.Crm.DealDetailManager.create(id, settings);
			}
			else if(entityTypeId === BX.CrmEntityType.enumeration.contact)
			{
				return BX.Crm.ContactDetailManager.create(id, settings);
			}
			else if(entityTypeId === BX.CrmEntityType.enumeration.company)
			{
				return BX.Crm.CompanyDetailManager.create(id, settings);
			}
			else if(entityTypeId === BX.CrmEntityType.enumeration.order)
			{
				return BX.Crm.OrderDetailManager.create(id, settings);
			}

			return BX.Crm.EntityDetailManager.create(id, settings);
		}
	}
}
//endregion

//region TAB MANAGER
if(typeof BX.Crm.EntityDetailTabManager === "undefined")
{
	BX.Crm.EntityDetailTabManager = function()
	{
		this._id = "";
		this._container = null;
		this._items = null;
		this._menuManager = null;
	};
	BX.Crm.EntityDetailTabManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			settings = settings ? settings : {};

			this._container = BX.prop.getElementNode(settings, "container");

			var tabsById = {};
			BX.prop.getArray(settings, "data").forEach(function(itemData) {
				if (!tabsById[itemData['id']])
				{
					tabsById[itemData['id']] = itemData;
				}
			}.bind(this));

			this._menuManager = BX.Main.interfaceButtonsManager.getById(
				BX.prop.getString(settings, "menuId")
			);
			var firstItem = null;
			this._items = [];
			this._menuManager.getAllItems().forEach(function(item) {
				if (firstItem === null)
				{
					firstItem = item;
				}

				var itemData = tabsById[item.dataset.id];
				if (!itemData)
				{
					return;
				}

				this._items.push(BX.Crm.EntityDetailTab.create(
					itemData['id'],
					{
						manager: this,
						data: itemData,
						container: this._container.querySelector('[data-tab-id="' + itemData['id'] + '"]'),
						menuContainer: item
					}
				))
			}.bind(this));

			var activeItem = this._menuManager.getActive();
			if (activeItem['DATA_ID'] === 'main' && firstItem.dataset.id !== activeItem['DATA_ID'])
			{
				const firstItemData = this._menuManager.getItemData(firstItem);
				const script = firstItemData['ON_CLICK'];
				if (BX.type.isNotEmptyString(script))
				{
					eval(script);
				}
			}
		},
		getId: function()
		{
			return this._id;
		},
		findItemById: function(id)
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				var currentItem = this._items[i];
				if(currentItem.getId() === id)
				{
					return currentItem;
				}
			}
			return null;
		},
		selectItem: function(item)
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				var currentItem = this._items[i];
				currentItem.setActive(currentItem === item);
			}
		},
		processItemSelect: function(item)
		{
			this.selectItem(item);
		},
		isVisibleItemMenu: function(item)
		{
			const itemMenuContainer = item.getMenuContainer();
			if (itemMenuContainer)
			{
				return !this._menuManager.isDisabled(itemMenuContainer)
					&& this._menuManager.isVisibleItem(itemMenuContainer);
			}
			return false;
		},
		getTabMenuContainer: function()
		{
			return this._menuManager.itemsContainer;
		},
		getMoreButton: function()
		{
			return this._menuManager.getMoreButton();
		},
		getMoreMenu: function()
		{
			const moreMenu = this._menuManager.getMoreMenu();

			return moreMenu ? moreMenu : null;
		}
	};
	BX.Crm.EntityDetailTabManager.items = {};
	BX.Crm.EntityDetailTabManager.create = function(id, settings)
	{
		var self = new BX.Crm.EntityDetailTabManager();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	}
}
//endregion

//region TAB
if(typeof BX.Crm.EntityDetailTab === "undefined")
{
	BX.Crm.EntityDetailTab = function()
	{
		this._id = "";
		this._data = {};
		this._manager = null;
		this._container = null;
		this._menuContainer = null;
		this.onMenuClick = this.onMenuClick.bind(this);

		this._isActive = false;
		this._isEnabled = false;
		this._loader = null;
	};
	BX.Crm.EntityDetailTab.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);

			settings = settings ? settings : {};
			this._data = BX.prop.getObject(settings, "data", {});
			this._manager = BX.prop.get(settings, "manager", null);
			this._container = BX.prop.getElementNode(settings, "container");
			this._menuContainer = BX.prop.getElementNode(settings, "menuContainer");

			this._isActive = BX.prop.getBoolean(this._data, "active", false);
			this._isEnabled = BX.prop.getBoolean(this._data, "enabled", true);

			BX.addCustomEvent(this._manager.getId() + '_click_' + this._id, this.onMenuClick);

			var loaderSettings = BX.prop.getObject(this._data, "loader", null);
			if(loaderSettings)
			{
				loaderSettings["tabId"] = this._id;
				loaderSettings["container"] = this._container;
				this._loader = BX.Crm.EditorTabLazyLoader.create(
					this._id,
					loaderSettings
				);
			}
		},
		getId: function()
		{
			return this._id;
		},
		getMenuContainer: function()
		{
			return this._menuContainer;
		},
		isEnabled: function()
		{
			return this._isEnabled;
		},
		isActive: function()
		{
			return this._isActive;
		},
		setActive: function(active)
		{
			active = !!active;
			if(this._isActive === active)
			{
				return;
			}

			this._isActive = active;

			if(this._isActive)
			{
				if(this._loader && !this._loader.isLoaded())
				{
					this._loader.load();
				}
				// setTimeout(BX.delegate(this.showTab, this), 10);
				this.showTab()
			}
			else
			{
				// setTimeout(BX.delegate(this.hideTab, this),220);
				this.hideTab()
			}
		},
		showTab: function ()
		{
			BX.addClass(this._container, "crm-entity-section-tab-content-show");
			BX.removeClass(this._container, "crm-entity-section-tab-content-hide");
			BX.addClass(this._menuContainer, "main-buttons-item-active");

			this._container.style.display = "";
			this._container.style.position = "absolute";
			this._container.style.top = 0;
			this._container.style.left = 0;
			this._container.style.width = "100%";

			var showTab = new BX.easing({
				duration : 350,
				start : { opacity: 0, translateX:100 },
				finish: { opacity: 100, translateX:0 },
				transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step: BX.delegate(
					function(state)
					{
						this._container.style.opacity = state.opacity / 100;
						this._container.style.transform = "translateX(" + state.translateX + "%)";
					},
					this
				),
				complete: BX.delegate(
					function()
					{
						BX.removeClass(this._container, "crm-entity-section-tab-content-show");
						this._container.style.cssText = "";

						BX.onCustomEvent(window, "onEntityDetailsTabShow", [ this ]);
					},
					this
				)
			});

			showTab.animate();

		},
		hideTab: function ()
		{
			BX.addClass(this._container, "crm-entity-section-tab-content-hide");
			BX.removeClass(this._container, "crm-entity-section-tab-content-show");
			BX.removeClass(this._menuContainer, "main-buttons-item-active");

			var hideTab = new BX.easing({
				duration : 350,
				start : { opacity: 100 },
				finish: { opacity: 0 },
				transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step: BX.delegate(function(state) { this._container.style.opacity = state.opacity / 100; }, this),
				complete: BX.delegate(
					function ()
					{
						this._container.style.display = "none";
						this._container.style.transform = "translateX(100%)";
						this._container.style.opacity = 0;
					},
					this
				)
			});

			hideTab.animate();

		},
		onMenuClick: function()
		{
			if (!this._isEnabled)
			{
				return;
			}
			if(this._loader && !this._loader.isLoaded())
			{
				this._loader.load();
			}
			this._manager.processItemSelect(this);
		}
	};
	BX.Crm.EntityDetailTab.create = function(id, settings)
	{
		var self = new BX.Crm.EntityDetailTab();
		self.initialize(id, settings);
		return self;
	}
}
//endregion

//region TAB LOADER
if(typeof(BX.Crm.EditorTabLazyLoader) === "undefined")
{
	BX.Crm.EditorTabLazyLoader = function()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
		this._serviceUrl = "";
		this._tabId = "";
		this._params = {};

		this._isRequestRunning = false;
		this._isLoaded = false;
	};

	BX.Crm.EditorTabLazyLoader.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_lf_disp_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._container = BX(BX.prop.get(this._settings, "container", ""));
			if(!this._container)
			{
				throw "Error: Could not find container.";
			}

			this._tabId = BX.prop.getString(this._settings, "tabId", "");
			if(this._tabId === "")
			{
				throw "Error: Could not find tab id.";
			}

			this._params = BX.prop.getObject(this._settings, "componentData", {});

			this._ajaxComponentActionParams = BX.prop.getObject(this._params, "ajaxComponentActionParams", {});
			this._useAjaxComponentAction = BX.type.isNotEmptyObject(this._ajaxComponentActionParams);

			if (this._useAjaxComponentAction)
			{
				this._componentName = BX.prop.getString(this._ajaxComponentActionParams, "componentName", "");
				if(this._componentName === "")
				{
					throw "Error. Could not find component name.";
				}

				this._actionName = BX.prop.getString(this._ajaxComponentActionParams, "actionName", "");
				if(this._actionName === "")
				{
					throw "Error. Could not find action name.";
				}

				this._signedParameters = BX.prop.getString(this._ajaxComponentActionParams, "signedParameters", "");
				if(this._signedParameters === "")
				{
					throw "Error. Could not find signed parameters.";
				}

				this._template = BX.prop.getString(this._params, "template", "");
				if(this._template === "")
				{
					throw "Error. Could not find template.";
				}
			}
			else
			{
				this._serviceUrl = BX.prop.getString(this._settings, "serviceUrl", "");
				if(this._serviceUrl === "")
				{
					throw "Error. Could not find service url.";
				}
			}
		},
		getId: function()
		{
			return this._id;
		},
		isLoaded: function()
		{
			return this._isLoaded;
		},
		load: function()
		{
			if(this._isLoaded)
			{
				return;
			}

			var params = this._params;
			params["TAB_ID"] = this._tabId;
			this._startRequest(params);
		},
		_startRequest: function(request)
		{
			if(this._isRequestRunning)
			{
				return false;
			}

			this._isRequestRunning = true;

			if (this._useAjaxComponentAction)
			{
				BX.ajax.runComponentAction(this._componentName, this._actionName, {
					signedParameters: this._signedParameters,
					data: {
						template: this._template
					}
				}).then(this._onRequestSuccess.bind(this), this._onRequestFailure.bind(this));
			}
			else
			{
				BX.ajax({
						url: this._serviceUrl,
						method: "POST",
						dataType: "html",
						data:
							{
								"LOADER_ID": this._id,
								"PARAMS": request
							},
						onsuccess: BX.delegate(this._onRequestSuccess, this),
						onfailure: BX.delegate(this._onRequestFailure, this)
				});
			}

			return true;
		},
		_onRequestSuccess: function(data)
		{
			this._isRequestRunning = false;
			if (this._useAjaxComponentAction)
			{
				data = BX.prop.getObject(data, "data", {});
				data = BX.prop.getString(data, "html", null);
			}

			BX.html(this._container, data);
			this._isLoaded = true;
		},
		_onRequestFailure: function(data)
		{
			this._isRequestRunning = false;
			this._isLoaded = true;
		}
	};

	BX.Crm.EditorTabLazyLoader.items = {};
	BX.Crm.EditorTabLazyLoader.create = function(id, settings)
	{
		var self = new BX.Crm.EditorTabLazyLoader();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}
//endregion
