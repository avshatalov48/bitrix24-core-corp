BX.namespace("BX.Mobile.Crm.Lead.Edit");

BX.Mobile.Crm.Lead.Edit = {
	ajaxPath: "",
	formActionUrl: "",
	formId: "",
	leadViewPath: "",
	mode: "",
	products: [],
	productDataFieldName: "",
	productsContainerNode: "",
	pageIdProductSelectorBack: "crmLeadEditPage",

	init: function(params)
	{
		if (params && typeof params === "object")
		{
			this.ajaxPath = params.ajaxPath || "";
			this.formActionUrl = params.formActionUrl || "";
			this.formId = params.formId || "";
			this.leadViewPath = params.leadViewPath || "";
			this.mode = params.mode || "";
			this.products = params.products || [];
			this.productDataFieldName = params.productDataFieldName || "";
			this.productsContainerNode = document.querySelector("[data-role='mobile-crm-lead-edit-products']") || "";
			this.pageIdProductSelectorBack = params.pageIdProductSelectorBack || "crmLeadEditPage";
		}

		if (this.mode == "EDIT" || this.mode == "CREATE")
		{
			app.setPageID(this.pageIdProductSelectorBack);
		}

		if (this.mode == "EDIT")
		{
			BXMobileApp.addCustomEvent("onCrmLeadEditUpdate", BX.proxy(function(data){
				if (data.formId == this.formId)
				{
					BXMobileApp.UI.Page.reload();
				}
			}, this));
		}

		if (this.mode == "VIEW")
		{
			BXMobileApp.addCustomEvent("onCrmLeadViewUpdate", BX.proxy(function(data){
				if (data.formId == this.formId)
				{
					BXMobileApp.UI.Page.reload();
				}
			}, this));

			//to refresh edit page after editing view page
			BX.addCustomEvent("onSubmitAjaxSuccess", BX.proxy(function (data) {
				if (data.formId == this.formId)
					BXMobileApp.onCustomEvent("onCrmLeadEditUpdate", {formId: this.formId}, true);
			}, this));
		}

		BX.addCustomEvent("onCrmLeadDetailDelete", function(){
			BXMobileApp.onCustomEvent("onCrmLeadListUpdate", {}, true);
			BXMobileApp.UI.Page.close({drop:true});
		});

		/*if (this.mode == "CREATE") // close create page after saving
		{
			var onLeadFormCloseHandler = function()
			{
				BX.removeCustomEvent("onOpenPageAfter", onLeadFormCloseHandler);
				app.closeModalDialog();
			};

			BX.addCustomEvent("onCrmLeadClosePage", function () {
				if (!(window.isCurrentPage && window.isCurrentPage == "Y"))
				{
					BX.addCustomEvent("onOpenPageAfter", onLeadFormCloseHandler);
				}
				else
				{
					window.isCurrentPage = "";
				}
			});
		}*/

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
					BXMobileApp.onCustomEvent("onCrmLeadListUpdate", {}, true); // update list after editing on view page
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
			var dataFormValues = {save: "Y", sessid: BX.bitrix_sessid()};

			if (BX.Mobile.Crm.ProductEditor && BX.Mobile.Crm.ProductEditor.products)
			{
				dataFormValues[this.productDataFieldName] = BX.Mobile.Crm.ProductEditor.products;
			}

			BX.Mobile.Crm.Detail.collectInterfaceFormData(form, dataFormValues);

			BX.ajax({
				method: 'POST',
				dataType: 'json',
				url: this.formActionUrl,
				data: dataFormValues,
				onsuccess: BX.proxy(function(json)
				{
					if (json.error)
					{
						app.alert({text: json.error});
					}
					else
					{
						if (this.mode == "EDIT")
						{
							BXMobileApp.onCustomEvent("onCrmLeadListUpdate", {}, true);
							BXMobileApp.onCustomEvent("onCrmLeadViewUpdate", {formId: this.formId}, true);
							app.closeModalDialog({cache: false});
						}

						if (this.mode == "CREATE")
						{
							var viewPath = this.leadViewPath.replace("#lead_id#", json.itemId);
							BXMobileApp.onCustomEvent("onCrmLeadLoadPageBlank", {path: viewPath}, true);
							app.closeModalDialog({cache: false});
						}
					}

					BXMobileApp.UI.Page.LoadingScreen.hide();
				}, this)
			});
		}
	}
};