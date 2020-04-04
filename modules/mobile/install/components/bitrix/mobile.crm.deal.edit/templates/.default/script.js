BX.namespace("BX.Mobile.Crm.Deal.Edit");

BX.Mobile.Crm.Deal.Edit = {
	ajaxPath: "",
	formActionUrl: "",
	formId: "",
	dealViewPath: "",
	mode: "",
	products: [],
	productDataFieldName: "",
	productsContainerNode: "",
	contactContainerNode: "",
	companyContainerNode: "",
	contactInfo: "",
	companyInfo: "",
	isRestrictedMode: false,
	leadId: "",
	quoteId: "",
	onSelectCompanyEventName: "",
	onDeleteCompanyEventName: "",
	onSelectContactEventName: "",
	pageIdProductSelectorBack: "crmDealEditPage",

	init: function(params)
	{
		if (params && typeof params === "object")
		{
			this.ajaxPath = params.ajaxPath || "";
			this.formActionUrl = params.formActionUrl || "";
			this.formId = params.formId || "";
			this.dealViewPath = params.dealViewPath || "";
			this.mode = params.mode || "";
			this.isRestrictedMode = params.isRestrictedMode || false;
			this.leadId = params.leadId || "";
			this.quoteId = params.quoteId || "";

			this.products = params.products || [];
			this.productDataFieldName = params.productDataFieldName || "";
			this.contactInfo = params.contactInfo || "";
			this.companyInfo = params.companyInfo || "";
			this.productsContainerNode = document.querySelector("[data-role='mobile-crm-deal-edit-products']") || "";
			this.contactContainerNode = document.querySelector("[data-role='mobile-crm-deal-edit-contact']") || "";
			this.companyContainerNode = document.querySelector("[data-role='mobile-crm-deal-edit-company']") || "";

			this.onSelectContactEventName = params.onSelectContactEventName || "";
			this.onSelectCompanyEventName = params.onSelectCompanyEventName || "";
			this.onDeleteCompanyEventName = params.onDeleteCompanyEventName || "";
			this.pageIdProductSelectorBack = params.pageIdProductSelectorBack || "crmDealEditPage";
		}

		if (this.mode == "EDIT" || this.mode == "CREATE")
		{
			app.setPageID(this.pageIdProductSelectorBack);
		}

		if (this.mode == "EDIT")
		{
			BXMobileApp.addCustomEvent("onCrmDealEditUpdate", BX.proxy(function(data){
				if (data.formId == this.formId)
				{
					BXMobileApp.UI.Page.reload();
				}
			}, this));
		}

		if (this.mode == "VIEW")
		{
			BXMobileApp.addCustomEvent("onCrmDealViewUpdate", BX.proxy(function(data){
				if (data.formId == this.formId)
				{
					BXMobileApp.UI.Page.reload();
				}
			}, this));

			//to refresh edit page after editing view page
			BX.addCustomEvent("onSubmitAjaxSuccess", BX.proxy(function (data) {
				if (data.formId == this.formId)
					BXMobileApp.onCustomEvent("onCrmDealEditUpdate", {formId: this.formId}, true);
			}, this));
		}

		if (this.mode == "CREATE")
		{
			BX.addCustomEvent("onCrmDealCreate", BX.proxy(function(){
				BX.Mobile.Crm.loadPageBlank(this.dealViewPath);
			}, this));
		}

		BX.addCustomEvent("onCrmDealDetailDelete", function(){
			BXMobileApp.onCustomEvent("onCrmDealListUpdate", {}, true);
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
			onSelectEventName: this.onSelectCompanyEventName,
			onDeleteEventName: this.onDeleteCompanyEventName
		};
		this.companyEditor = new BX.Mobile.Crm.EntityEditor(companyParams);
		
		if (this.mode !== "VIEW")
		{
			//on delete company
			BX.addCustomEvent(this.onDeleteCompanyEventName, BX.proxy(function()
			{
				BX.onCustomEvent('CrmEntitySelectorChangeValue', ['COMPANY', 0]); // recalculate totals with new payer type
			}, this));

			//on change company
			BX.addCustomEvent(this.onSelectCompanyEventName, BX.proxy(function()
			{
				BX.onCustomEvent('CrmEntitySelectorChangeValue', ['COMPANY', data.id]); // recalculate totals with new payer type
			}, this));
		}

		if (this.mode == "CONVERT" || this.mode == "CREATE")
		{
			var onDealFormCloseHandler = function()
			{
				BX.removeCustomEvent("onOpenPageAfter", onDealFormCloseHandler);
				app.closeModalDialog();
			};

			BX.addCustomEvent("onCrmDealClosePage", function () {
				if (!(window.isCurrentPage && window.isCurrentPage == "Y"))
				{
					BX.addCustomEvent("onOpenPageAfter", onDealFormCloseHandler);
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
					BXMobileApp.onCustomEvent("onCrmDealListUpdate", {}, true); // update list after editing on view page
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

			if (this.leadId || this.quoteId) // convertation
			{
				if (this.leadId)
					dataFormValues["lead_id"] = this.leadId;
				else if (this.quoteId)
					dataFormValues["conv_quote_id"] = this.quoteId;

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
							BXMobileApp.onCustomEvent("onCrmDealListUpdate", {}, true);
							BXMobileApp.onCustomEvent("onCrmDealViewUpdate", {formId: this.formId}, true);
							app.closeModalDialog({cache: false});
						}

						if (this.mode == "CREATE")
						{
							var viewPath = this.dealViewPath.replace("#deal_id#", json.itemId);
							BXMobileApp.onCustomEvent("onCrmDealLoadPageBlank", {path: viewPath}, true);
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
								BXMobileApp.onCustomEvent("onCrmDealLoadPageBlank", {path: json.url, type: 'convert'}, true);
							}

							app.closeModalDialog({cache: false});
						}
					}
				}, this)
			});
		}
	}
};