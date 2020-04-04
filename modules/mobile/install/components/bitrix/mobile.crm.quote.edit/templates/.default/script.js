BX.namespace("BX.Mobile.Crm.Quote.Edit");

BX.Mobile.Crm.Quote.Edit = {
	ajaxPath: "",
	formActionUrl: "",
	formId: "",
	quoteViewPath: "",
	mode: "",
	products: [],
	productDataFieldName: "",
	productsContainerNode: "",
	contactContainerNode: "",
	companyContainerNode: "",
	leadContainerNode: "",
	contactInfo: "",
	companyInfo: "",
	leadInfo: "",
	dealInfo: "",
	isRestrictedMode: false,
	convDealId: "",
	onDeleteCompanyEventName: "",
	onSelectCompanyEventName: "",
	onSelectContactEventName: "",
	onSelectDealEventName: "",
	onSelectLeadEventName: "",
	pageIdProductSelectorBack: "crmQuoteEditPage",

	init: function(params)
	{
		if (params && typeof params === "object")
		{
			this.ajaxPath = params.ajaxPath || "";
			this.formActionUrl = params.formActionUrl || "";
			this.formId = params.formId || "";
			this.quoteViewPath = params.quoteViewPath || "";
			this.mode = params.mode || "";
			this.isRestrictedMode = params.isRestrictedMode || false;
			this.products = params.products || [];
			this.productDataFieldName = params.productDataFieldName || "";
			this.contactInfo = params.contactInfo || "";
			this.companyInfo = params.companyInfo || "";
			this.leadInfo = params.leadInfo || "";
			this.dealInfo = params.dealInfo || "";
			this.convDealId = params.convDealId || "";
			this.productsContainerNode = document.querySelector("[data-role='mobile-crm-quote-edit-products']") || "";
			this.contactContainerNode = document.querySelector("[data-role='mobile-crm-quote-edit-contact']") || "";
			this.companyContainerNode = document.querySelector("[data-role='mobile-crm-quote-edit-company']") || "";
			this.leadContainerNode = document.querySelector("[data-role='mobile-crm-quote-edit-lead']") || "";
			this.dealContainerNode = document.querySelector("[data-role='mobile-crm-quote-edit-deal']") || "";

			this.onDeleteCompanyEventName = params.onDeleteCompanyEventName || "";
			this.onSelectCompanyEventName = params.onSelectCompanyEventName || "";
			this.onSelectContactEventName = params.onSelectContactEventName || "";
			this.onSelectDealEventName = params.onSelectDealEventName || "";
			this.onSelectLeadEventName = params.onSelectLeadEventName || "";
			this.pageIdProductSelectorBack = params.pageIdProductSelectorBack || "crmQuoteEditPage";
		}

		if (this.mode == "EDIT" || this.mode == "CREATE")
		{
			app.setPageID(this.pageIdProductSelectorBack);
		}

		if (this.mode == "EDIT")
		{
			BXMobileApp.addCustomEvent("onCrmQuoteEditUpdate", BX.proxy(function(data){
				if (data.formId == this.formId)
				{
					BXMobileApp.UI.Page.reload();
				}
			}, this));
		}

		if (this.mode == "VIEW")
		{
			BXMobileApp.addCustomEvent("onCrmQuoteViewUpdate", BX.proxy(function(data){
				if (data.formId == this.formId)
				{
					BXMobileApp.UI.Page.reload();
				}
			}, this));

			//to refresh edit page after editing view page
			BX.addCustomEvent("onSubmitAjaxSuccess", BX.proxy(function (data) {
				if (data.formId == this.formId)
					BXMobileApp.onCustomEvent("onCrmQuoteEditUpdate", {formId: this.formId}, true);
			}, this));
		}

		BX.addCustomEvent("onCrmQuoteDetailDelete", function(){
			BXMobileApp.onCustomEvent("onCrmQuoteListUpdate", {}, true);
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

		//generate current company info
		var companyParams = {
			entityContainerNode : this.companyContainerNode,
			entityInfo : this.companyInfo,
			isRestrictedMode: this.isRestrictedMode,
			onDeleteEventName: this.onDeleteCompanyEventName,
			onSelectEventName: this.onSelectCompanyEventName
		};
		this.companyEditor = new BX.Mobile.Crm.EntityEditor(companyParams);

		if (!this.isRestrictedMode)
		{
			//on select company
			BX.addCustomEvent(this.onSelectCompanyEventName, BX.proxy(function(data)
			{
				BX.onCustomEvent('CrmEntitySelectorChangeValue', ['COMPANY', data.id]);
			}, this));

			//on delete company
			BX.addCustomEvent(this.onDeleteCompanyEventName, BX.proxy(function()
			{
				BX.onCustomEvent('CrmEntitySelectorChangeValue', ['COMPANY', 0]);
			}, this));
		}

		//generate current lead info
		var leadParams = {
			entityContainerNode : this.leadContainerNode,
			entityInfo : this.leadInfo,
			isRestrictedMode: this.isRestrictedMode,
			onSelectEventName: this.onSelectLeadEventName
		};
		this.leadEditor = new BX.Mobile.Crm.EntityEditor(leadParams);

		//generate current deal info
		var dealParams = {
			entityContainerNode : this.dealContainerNode,
			entityInfo : this.dealInfo,
			isRestrictedMode: this.isRestrictedMode,
			onSelectEventName: this.onSelectDealEventName
		};
		this.dealEditor = new BX.Mobile.Crm.EntityEditor(dealParams);

		if (this.mode == "CONVERT" || this.mode == "CREATE")
		{
			var onQuoteFormCloseHandler = function()
			{
				BX.removeCustomEvent("onOpenPageAfter", onQuoteFormCloseHandler);
				app.closeModalDialog();
			};

			BX.addCustomEvent("onCrmQuoteClosePage", function () {
				if (!(window.isCurrentPage && window.isCurrentPage == "Y"))
				{
					BX.addCustomEvent("onOpenPageAfter", onQuoteFormCloseHandler);
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
					BXMobileApp.onCustomEvent("onCrmQuoteListUpdate", {}, true); // update list after editing on view page
				}
			}, this));
		}

		if (this.mode == "VIEW" || this.mode == "EDIT") // on change
		{
			var initFormForFile = BX.delegate(function(formId, gridId, obj) {
				if (formId == this.formId && obj)
				{
					BX.addCustomEvent(obj, 'onChange', BX.proxy(this.onChange, this));
				}
			}, this);
			BX.addCustomEvent("onInitialized", initFormForFile);
			var form = BX.Mobile.Grid.Form.getByFormId(this.formId);
			initFormForFile(this.formId, '', form);
		}
	},

	onChange : function(form, node, obj)
	{
		if (node && node.name && node.name == 'CURRENCY_ID')
		{
			if (typeof BX.Mobile.Crm.ProductEditor == 'object' && BX.Mobile.Crm.ProductEditor)
			{
				var prodEditor = BX.Mobile.Crm.ProductEditor;
				var currencyId = node.value;
				prodEditor.setCurrencyId(currencyId);
			}
		}
	},

	submit: function()
	{
		BXMobileApp.UI.Page.LoadingScreen.show();

		var form = BX(this.formId);
		if(form)
		{
			var dataFormValues = {sessid:BX.bitrix_sessid()};

			if (this.convDealId) // convertation from deal
			{
				dataFormValues["conv_deal_id"] = this.convDealId;
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

			if (this.contactEditor)
			{
				dataFormValues["CONTACT_ID"] = this.contactEditor.curEntityId;
			}

			if (this.companyEditor)
			{
				dataFormValues["COMPANY_ID"] = this.companyEditor.curEntityId;
			}

			if (this.leadEditor)
			{
				dataFormValues["LEAD_ID"] = this.leadEditor.curEntityId;
			}

			if (this.dealEditor)
			{
				dataFormValues["DEAL_ID"] = this.dealEditor.curEntityId;
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
						app.alert({text: json.error});
					}
					else
					{
						if (this.mode == "EDIT")
						{
							BXMobileApp.onCustomEvent("onCrmQuoteListUpdate", {}, true);
							BXMobileApp.onCustomEvent("onCrmQuoteViewUpdate", {formId: this.formId}, true);
							app.closeModalDialog({cache: false});
						}

						if (this.mode == "CREATE")
						{
							var viewPath = this.quoteViewPath.replace("#quote_id#", json.itemId);
							BXMobileApp.onCustomEvent("onCrmQuoteLoadPageBlank", {path: viewPath}, true);
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
								BXMobileApp.onCustomEvent("onCrmQuoteLoadPageBlank", {path: json.url, type: 'convert'}, true);
							}

							app.closeModalDialog({cache: false});
						}
					}
				}, this)
			});
		}
	}
};