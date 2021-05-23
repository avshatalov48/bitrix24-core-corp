BX.namespace("BX.Mobile.Crm.Invoice.Edit");

BX.CrmActivityStorageType =
{
	undefined: 0,
	file: 1,
	webdav: 2,
	disk: 3
};

BX.Mobile.Crm.Invoice.Edit = {
	ajaxPath: "",
	formActionUrl: "",
	formId: "",
	invoiceViewPath: "",
	mode: "",
	products: [],
	productDataFieldName: "",
	productsContainerNode: "",
	contactContainerNode: "",
	companyContainerNode: "",
	dealContainerNode: "",
	quoteContainerNode: "",
	contactInfo: "",
	clientInfo: "",
	clientPrefix: "",
	clientType: "",
	quoteInfo: "",
	dealInfo: "",
	isRestrictedMode: false,
	convDealId: "",
	convQuoteId: "",
	pageId: "",
	onDeleteClientEventName: "",
	onSelectClientEventName: "",
	onSelectContactEventName: "",
	onSelectQuoteEventName: "",
	onSelectDealEventName: "",
	emailEditUrl: "",
	emailSubject: "",
	statusSort: {},
	pageIdProductSelectorBack: "crmInvoiceEditPage",

	init: function(params)
	{
		if (params && typeof params === "object")
		{
			this.ajaxPath = params.ajaxPath || "";
			this.formActionUrl = params.formActionUrl || "";
			this.formId = params.formId || "";
			this.invoiceViewPath = params.invoiceViewPath || "";
			this.mode = params.mode || "";
			this.products = params.products || [];
			this.productDataFieldName = params.productDataFieldName || "";
			this.contactInfo = params.contactInfo || "";
			this.clientInfo = params.clientInfo || "";
			this.clientPrefix = params.clientPrefix || "";
			this.clientType = params.clientType || "";
			this.dealInfo = params.dealInfo || "";
			this.quoteInfo = params.quoteInfo || "";
			this.convDealId = params.convDealId || "";
			this.convQuoteId = params.convQuoteId || "";
			this.isRestrictedMode = params.isRestrictedMode || "";
			this.productsContainerNode = document.querySelector("[data-role='mobile-crm-invoice-edit-products']") || "";
			this.contactContainerNode = document.querySelector("[data-role='mobile-crm-invoice-edit-contact']") || "";
			this.clientContainerNode = document.querySelector("[data-role='mobile-crm-invoice-edit-client']") || "";
			this.quoteContainerNode = document.querySelector("[data-role='mobile-crm-invoice-edit-quote']") || "";
			this.dealContainerNode = document.querySelector("[data-role='mobile-crm-invoice-edit-deal']") || "";

			this.pageId = params.pageId || "";
			this.onDeleteClientEventName = params.onDeleteClientEventName || "";
			this.onSelectClientEventName = params.onSelectClientEventName || "";
			this.onSelectContactEventName = params.onSelectContactEventName || "";
			this.onSelectQuoteEventName = params.onSelectQuoteEventName || "";
			this.onSelectDealEventName = params.onSelectDealEventName || "";
			this.emailEditUrl = params.emailEditUrl || "";
			this.emailSubject = params.emailSubject || "";
			this.statusSort = params.statusSort || {};
			this.pageIdProductSelectorBack = params.pageIdProductSelectorBack || "crmInvoiceEditPage";
		}

		if (this.mode == "EDIT" || this.mode == "CREATE")
		{
			app.setPageID(this.pageIdProductSelectorBack);
		}

		if (this.mode == "EDIT")
		{
			BXMobileApp.addCustomEvent("onCrmInvoiceEditUpdate",BX.proxy(function(data){
				if (data.formId == this.formId)
				{
					BXMobileApp.UI.Page.reload();
				}
			}, this));
		}

		if (this.mode == "VIEW")
		{
			BXMobileApp.addCustomEvent("onCrmInvoiceViewUpdate", BX.proxy(function(data){
				if (data.formId == this.formId)
				{
					BXMobileApp.UI.Page.reload();
				}
			}, this));

			//to refresh edit page after editing view page
			BX.addCustomEvent("onSubmitAjaxSuccess", BX.proxy(function (data) {
				if (data.formId == this.formId)
					BXMobileApp.onCustomEvent("onCrmInvoiceEditUpdate", {formId: this.formId}, true);
			}, this));
		}

		if (this.mode == "CREATE")
		{
			BX.addCustomEvent("onCrmInvoiceCreate", BX.proxy(function(){
				BX.Mobile.Crm.loadPageBlank(this.invoiceViewPath);
			}, this));
		}

		BX.addCustomEvent("onCrmInvoiceDetailDelete", function(){
			BXMobileApp.onCustomEvent("onCrmInvoiceListUpdate", {}, true);
			BXMobileApp.UI.Page.close({drop:true});
		});

		//generate current contact info
		var contactParams = {
			entityContainerNode : this.contactContainerNode,
			entityInfo : this.contactInfo,
			isRestrictedMode: this.isRestrictedMode,
			onSelectEventName: this.onSelectContactEventName
		};
		this.contactEditor = new BX.Mobile.Crm.EntityEditor(contactParams);

		//generate current client info
		var clientParams = {
			entityContainerNode : this.clientContainerNode,
			entityInfo : this.clientInfo,
			isRestrictedMode : this.isRestrictedMode,
			onDeleteEventName: this.onDeleteClientEventName,
			onSelectEventName: this.onSelectClientEventName
		};
		this.clientEditor = new BX.Mobile.Crm.EntityEditor(clientParams);

		//generate current deal info
		var dealParams = {
			entityContainerNode : this.dealContainerNode,
			entityInfo : this.dealInfo,
			isRestrictedMode : this.isRestrictedMode,
			onSelectEventName: this.onSelectDealEventName
		};
		this.dealEditor = new BX.Mobile.Crm.EntityEditor(dealParams);

		//generate current quote info
		var quoteParams = {
			entityContainerNode : this.quoteContainerNode,
			entityInfo : this.quoteInfo,
			isRestrictedMode: this.isRestrictedMode,
			onSelectEventName: this.onSelectQuoteEventName
		};
		this.quoteEditor = new BX.Mobile.Crm.EntityEditor(quoteParams);

		if (this.clientType == 'CONTACT')
		{
			this.showHideContactBlock('N');
		}

		if (this.mode !== "VIEW")
		{
			//on delete client
			BX.addCustomEvent(this.onDeleteClientEventName, BX.proxy(function()
			{
				BX.onCustomEvent('CrmEntitySelectorChangeValue', ['COMPANY', 0]);
				this.refreshPaySystemList('');
			}, this));

			//on change client
			BX.addCustomEvent(this.onSelectClientEventName, BX.proxy(function(data)
			{
				if (data.entity == "contact")
				{
					//clean pay system list
					if (this.clientPrefix !== "C_")
						BX('bx_PAY_SYSTEM_ID').innerHTML = "";

					this.clientPrefix = "C_";
					this.clientType = "CONTACT";

					BX.onCustomEvent('CrmEntitySelectorChangeValue', ['COMPANY', 0]); //for recalculating totals in products

					this.refreshPaySystemList('CONTACT');

					//hide contact block
					this.showHideContactBlock('N');

				}
				else if (data.entity == "company")
				{
					//clean pay system list
					if (this.clientPrefix !== "CO_")
						BX('bx_PAY_SYSTEM_ID').innerHTML = "";

					this.clientPrefix = "CO_";
					this.clientType = "COMPANY";

					BX.onCustomEvent('CrmEntitySelectorChangeValue', ['COMPANY', data.id]); //for recalculating totals in products

					this.refreshPaySystemList('COMPANY');

					//show contact block
					this.showHideContactBlock('Y');
				}
			}, this));

			//on status change
			this.onCrmInvoiceEditStatusChange();

			var initFormForFile = BX.delegate(function(formId, gridId, obj) {
				if (formId == this.formId && obj)
				{
					BX.addCustomEvent(obj, 'onChange', BX.proxy(this.onChangeField, this));
				}
			}, this);
			BX.addCustomEvent("onInitialized", initFormForFile);
			var form = BX.Mobile.Grid.Form.getByFormId(this.formId);
			initFormForFile(this.formId, '', form);
		}

		if (this.mode == "CONVERT" || this.mode == "CREATE") // close page after saving
		{
			var onInvoiceFormCloseHandler = function()
			{
				BX.removeCustomEvent("onOpenPageAfter", onInvoiceFormCloseHandler);
				app.closeModalDialog();
			};

			BX.addCustomEvent("onCrmInvoiceClosePage", function () {
				if (!(window.isCurrentPage && window.isCurrentPage == "Y"))
				{
					BX.addCustomEvent("onOpenPageAfter", onInvoiceFormCloseHandler);
				}
				else
				{
					window.isCurrentPage = "";
				}
			});
		}

		if (this.mode == "VIEW")
		{
			BXMobileApp.addCustomEvent("onSubmitForm", BX.proxy(function(data, formNode, inputNode) {
				if (data.formId == this.formId)
				{
					//onChange checkboxes from the view mode
					if (inputNode.checked == false || !inputNode.hasAttribute("checked"))
					{
						var newInputNode = BX.create("INPUT", {
							attrs: {
								type: "hidden",
								name: inputNode.name,
								value: ""
							}
						});
						inputNode.parentNode.insertBefore(newInputNode, inputNode);
					}
				}
			}, this));

			BX.addCustomEvent("onSubmitAjaxSuccess", BX.proxy(function(data) {
				if (data.hasOwnProperty("formId") && data.formId == this.formId)
				{
					BXMobileApp.onCustomEvent("onCrmInvoiceListUpdate", {}, true); // update list after editing on view page
				}
			}, this));
		}
	},

	showHideContactBlock: function(isShowed)
	{
		if (isShowed != "Y" && isShowed != "N")
			return;

		var contactBlock = document.querySelector("[data-role='mobile-crm-invoice-edit-contact']");

		if (contactBlock)
		{
			contactBlock.parentNode.parentNode.parentNode.style.display = (isShowed == "Y" ? 'block' : 'none');
		}
	},

	refreshPaySystemList: function(clientType)
	{
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid: BX.bitrix_sessid(),
				action: "getPaySystemItems",
				clientType: clientType
			},
			onsuccess: BX.proxy(function(json)
			{
				var paySystemSelect = BX('bx_PAY_SYSTEM_ID');
				if (paySystemSelect)
					paySystemSelect.innerHTML = "";

				if (BX("bx_PAY_SYSTEM_ID_select"))
					BX("bx_PAY_SYSTEM_ID_select").innerHTML = BX.message("M_CRM_INVOICE_SELECT");

				if (paySystemSelect && typeof json == "object" && json.hasOwnProperty("PAY_SYSTEMS"))
				{
					var newPaySystems = json.PAY_SYSTEMS;
				//	BX("bx_PAY_SYSTEM_ID_select").innerHTML = BX.message("M_CRM_INVOICE_SELECT");
					var isFirst = true;

					for(var i in newPaySystems)
					{
						if (!newPaySystems.hasOwnProperty(i))
							continue;

						var attrs = {value: i};
						if (isFirst)
						{
							attrs["selected"] = "selected";
							BX("bx_PAY_SYSTEM_ID_select").innerHTML = newPaySystems[i];//BX.message("M_CRM_INVOICE_SELECT");
							isFirst = false;
						}

						paySystemSelect.appendChild(
							BX.create("option", {
								html: newPaySystems[i],
								attrs: attrs
							})
						);
					}
				}

				BX.onCustomEvent(paySystemSelect, "onChange");
			}, this)
		});
	},

	onChangeField : function(form, node)
	{
		if (typeof node !== 'object')
			return;

		if (node.name && node.name == "STATUS_ID")
		{
			this.onCrmInvoiceEditStatusChange();
		}
	},

	onCrmInvoiceEditStatusChange: function()
	{
		var statusSort = this.statusSort;
		var form = BX(this.formId);
		if (form)
		{
			var payVoucherNum = BX.findChild(form, {"tag": "input", "attr": {"type": "text", "name": "PAY_VOUCHER_NUM"}}, true, false);
			var statusSelect = BX.findChild(form, {"tag": "select", "attr": {"name": "STATUS_ID"}}, true, false);
			var payVoucherDate = BX.findChild(form, {"tag": "input", "attr": {"type": "hidden", "name": "PAY_VOUCHER_DATE"}}, true, false);
			var reasonMarkedSuccess = BX.findChild(form, {"tag": "textarea", "attr": {"name": "REASON_MARKED_SUCCESS"}}, true, false);
			var dateMarked = BX.findChild(form, {"tag": "input", "attr": {"type": "hidden", "name": "DATE_MARKED"}}, true, false);
			var reasonMarked = BX.findChild(form, {"tag": "textarea", "attr": {"name": "REASON_MARKED"}}, true, false);
			var statusId = null, isSuccess = false, isFailed = false, block = null;
			if (
				statusSelect &&
				payVoucherDate && payVoucherNum && reasonMarkedSuccess &&
				dateMarked && reasonMarked
			)
			{
				statusId = statusSelect.value;
				if (typeof(statusId) === "string" && statusId.length > 0)
				{
					isSuccess = (statusId === "P");
					if (isSuccess)
						isFailed = false;
					else
						isFailed = (statusSort[statusId] >= statusSort["D"]);

					var successElements = [payVoucherDate, payVoucherNum, reasonMarkedSuccess];
					var failedElements = [dateMarked, reasonMarked];
					for (var i in successElements)
					{
						if (successElements.hasOwnProperty(i))
						{
							block =  BX.findParent(successElements[i], {"tag": "div", "attr": {"class": "mobile-grid-section "}});
							if (block)
								block.style.display = isSuccess ? "" : "none";
						}
					}

					for (i in failedElements)
					{
						if (failedElements.hasOwnProperty(i))
						{
							block =  BX.findParent(failedElements[i], {"tag": "div", "attr": {"class": "mobile-grid-section "}});
							if (block)
								block.style.display = isFailed ? "" : "none";

						}
					}
				}
			}
		}
	},

	getInvoicePdfContent: function(invoiceId, invoiceAccountNumber)
	{
		if (!invoiceId || !invoiceAccountNumber)
			return;

		data = {
			'INVOICE_ID': invoiceId,
			'INVOICE_NUM': invoiceAccountNumber,
			'action': 'savePdf',
			'pdf': 1,
			'GET_CONTENT': 'Y',
			'sessid': BX.bitrix_sessid()
		};

		BXMobileApp.UI.Page.LoadingScreen.show();
		BX.ajax({
			data: data,
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			onsuccess: BX.delegate(function(result)
			{
				BXMobileApp.UI.Page.LoadingScreen.hide();
				if(result)
				{
					if(!result.ERROR)
						this.crmInvoiceOpenEmailDialog(result);
					else
						BX.Mobile.Crm.showErrorAlert(result.ERROR);
				}
			}, this),
			onfailure: function() {BX.debug('onfailure: getPdfContent');}
		});
	},

	crmInvoiceOpenEmailDialog: function(data)
	{
		if (typeof data !== "object" || !data.hasOwnProperty('diskfile'))
			return;

		var emailEditorSettings =
		{
		//	contextId: this.getContextId(),
			timestamp: (new Date()).getTime(),
			subject: this.emailSubject,
			//description: "",
		//	communication: this._clientEmailComm,
			storageTypeId: BX.CrmActivityStorageType.disk,
			storageElements: [{
				id: data.diskfile.ID,
				name: data.diskfile.NAME,
				url: data.diskfile.VIEW_URL
			}]
		};

		app.loadPageBlank({
			url: this.emailEditUrl,
			cache: false,
			data: emailEditorSettings
		});

		//BX.Mobile.Crm.loadPageModal(this.emailEditUrl);
	},

	submit: function()
	{
		BXMobileApp.UI.Page.LoadingScreen.show();

		var form = BX(this.formId);
		if(form)
		{
			var dataFormValues = {sessid:BX.bitrix_sessid()};

			if (this.convDealId || this.convQuoteId) // convertation
			{
				if (this.convDealId)
					dataFormValues["conv_deal_id"] = this.convDealId;
				else if (this.convQuoteId)
					dataFormValues["conv_quote_id"] = this.convQuoteId;

				dataFormValues["continue"] = "Y";
			}
			else
			{
				dataFormValues["save"] = "Y";
			}

			if (BX.Mobile.Crm.ProductEditor && BX.Mobile.Crm.ProductEditor.products)
			{
				dataFormValues[this.productDataFieldName] = BX.Mobile.Crm.ProductEditor.products;
			}

			if (this.clientEditor && this.clientEditor.curEntityId)
			{
				dataFormValues["PRIMARY_ENTITY_TYPE"] = this.clientType;
				dataFormValues["PRIMARY_ENTITY_ID"] = this.clientEditor.curEntityId;
				//dataFormValues["CLIENT_ID"] = this.clientPrefix + this.clientEditor.curEntityId;
				/*dataFormValues["CLIENT_ID_NEW"] = "";
				dataFormValues["CLIENT_REQUISITE_ID"] = "0";
				dataFormValues["CLIENT_BANK_DETAIL_ID"] = "0";*/
			}

			if (this.contactEditor)
			{
				dataFormValues["SECONDARY_ENTITY_IDS"] = this.contactEditor.curEntityId;
			}

			if (this.dealEditor)
			{
				dataFormValues["UF_DEAL_ID"] = this.dealEditor.curEntityId;
			}

			if (this.quoteEditor)
			{
				dataFormValues["UF_QUOTE_ID"] = this.quoteEditor.curEntityId;
			}

			BX.Mobile.Crm.Detail.collectInterfaceFormData(form, dataFormValues);

			BX.ajax({
				method: 'POST',
				dataType: 'json',
				url: this.formActionUrl,
				data: dataFormValues,
				onsuccess: BX.proxy(function(json)
				{
					BXMobileApp.UI.Page.LoadingScreen.hide();

					if (json.error)
					{
						BX.Mobile.Crm.showErrorAlert(json.error);
					}
					else
					{
						if (this.mode == "EDIT")
						{
							BXMobileApp.onCustomEvent("onCrmInvoiceListUpdate", {}, true);
							BXMobileApp.onCustomEvent("onCrmInvoiceViewUpdate", {formId: this.formId}, true);
							app.closeModalDialog({cache: false});
						}

						if (this.mode == "CREATE")
						{
							var viewPath = this.invoiceViewPath.replace("#invoice_id#", json.itemId);
							BXMobileApp.onCustomEvent("onCrmInvoiceLoadPageBlank", {path: viewPath}, true);
							app.closeModalDialog({cache: false});
						}

						if (this.mode == "CONVERT" && json.url)
						{
							if (json.url.indexOf("page=edit") > 0)
							{
								BXMobileApp.PageManager.loadPageModal({
									url: json.url
								});
							}
							else
							{
								BXMobileApp.onCustomEvent("onCrmInvoiceLoadPageBlank", {path: json.url, type: 'convert'}, true);
							}

							app.closeModalDialog({cache: false});
						}
					}
				}, this)
			});
		}
	}
};