BX.namespace("BX.Mobile.Crm.Company.Edit");

BX.Mobile.Crm.Company.Edit = {
	ajaxPath: "",
	formActionUrl: "",
	formId: "",
	companyViewPath: "",
	mode: "",
	contactContainerNode: "",
	contactInfo: "",
	isRestrictedMode: false,
	logoFile: "",
	logoDel: "",

	init: function(params)
	{
		if (params && typeof params === "object")
		{
			this.ajaxPath = params.ajaxPath || "";
			this.formActionUrl = params.formActionUrl || "";
			this.formId = params.formId || "";
			this.companyViewPath = params.companyViewPath || "";
			this.mode = params.mode || "";
			this.isRestrictedMode = params.isRestrictedMode || false;
			this.leadId = params.leadId || "";

			this.contactInfo = params.contactInfo || "";
			this.contactContainerNode = document.querySelector("[data-role='mobile-crm-company-edit-contact']") || "";
		}

		if (this.mode == "EDIT")
		{
			BXMobileApp.addCustomEvent("onCrmCompanyEditUpdate", BX.proxy(function(data){
				if (data.formId == this.formId)
				{
					BXMobileApp.UI.Page.reload();
				}
			}, this));
		}

		if (this.mode == "VIEW")
		{
			BXMobileApp.addCustomEvent("onCrmCompanyViewUpdate", BX.proxy(function(data){
				if (data.formId == this.formId)
				{
					BXMobileApp.UI.Page.reload();
				}
			}, this));

			//to refresh edit page after editing view page
			BX.addCustomEvent("onSubmitAjaxSuccess", BX.proxy(function (data) {
				if (data.formId == this.formId)
					BXMobileApp.onCustomEvent("onCrmCompanyEditUpdate", {formId: this.formId}, true);
			}, this));
		}

		if (this.mode == "CREATE")
		{
			BX.addCustomEvent("onCrmCompanyCreate", BX.proxy(function(){
				BX.Mobile.Crm.loadPageBlank(this.companyViewPath);
			}, this));
		}

		if (this.mode == "CONVERT" || this.mode == "CREATE")
		{
			var onCompanyFormCloseHandler = function()
			{
				BX.removeCustomEvent("onOpenPageAfter", onCompanyFormCloseHandler);
				app.closeModalDialog();
			};

			BX.addCustomEvent("onCrmCompanyClose", function () {
				if (!(window.isCurrentPage && window.isCurrentPage == "Y"))
				{
					BX.addCustomEvent("onOpenPageAfter", onCompanyFormCloseHandler);
				}
				else
				{
					window.isCurrentPage = "";
				}
			});
		}

		//generate contact's html
		var contactParams = {
			entityContainerNode : this.contactContainerNode,
			entityInfo : this.contactInfo,
			isRestrictedMode: this.isRestrictedMode,
			isMultiEntity: true
		};
		this.contactEditor = new BX.Mobile.Crm.EntityEditor(contactParams);// entity's editor

		BXMobileApp.addCustomEvent("onCrmContactSelectForCompany", BX.proxy(function(data){
			this.contactEditor.changeEntity(data);
		}, this));

		BX.addCustomEvent("onCrmCompanyDetailDelete", function(){
			BXMobileApp.onCustomEvent("onCrmCompanyListUpdate", {}, true);
			BXMobileApp.UI.Page.close({drop:true});
		});

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
					BXMobileApp.onCustomEvent("onCrmCompanyListUpdate", {}, true); // update list after editing on view page
				}
			}, this));
		}

		if (this.mode == "EDIT" || this.mode == "CREATE") // on change photo
		{
			var initFormForFile = BX.delegate(function(formId, gridId, obj) {
				if (formId == this.formId && obj)
				{
					BX.addCustomEvent(obj, 'onChange', BX.proxy(this.onChangeLogo, this));
				}
			}, this);
			BX.addCustomEvent("onInitialized", initFormForFile);
			var form = BX.Mobile.Grid.Form.getByFormId(this.formId);
			initFormForFile(this.formId, '', form);
		}
	},

	onChangeLogo : function(form, node, obj, fileObj)
	{
		if (!(typeof fileObj === 'object' && fileObj))
			return;

		if (fileObj.action == "add")
		{
			this.logoFile = fileObj.file;
		}
		else if (fileObj.action == "delete")
		{
			this.logoDel = "Y";
			this.logoFile = "";
		}
	},

	submit: function()
	{
		BXMobileApp.UI.Page.LoadingScreen.show();

		var form = BX(this.formId);
		if(form)
		{
			var dataFormValues = {sessid:BX.bitrix_sessid()};

			if (this.leadId) // convertation from lead
			{
				dataFormValues["lead_id"] = this.leadId;
				dataFormValues["continue"] = "Y";
			}
			else
			{
				dataFormValues["save"] = "Y";
			}

			if (this.contactEditor.curEntityId)
			{
				dataFormValues["CONTACT_ID"] = this.contactEditor.curEntityId;
			}

			BX.Mobile.Crm.Detail.collectInterfaceFormData(form, dataFormValues);

			var formDataObj = new FormData(); // for sending photo file
			for (var i in dataFormValues)
			{
				if (dataFormValues.hasOwnProperty(i))
				{
					formDataObj.append(i, dataFormValues[i]);
				}
			}

			if (this.logoFile)
			{
				formDataObj.append('LOGO', this.logoFile, this.logoFile.name);
			}
			else if (this.logoDel == "Y")
			{
				formDataObj.append('LOGO_del', 'Y');
				formDataObj.append('LOGO', BX.UploaderUtils.dataURLToBlob("data:image/jpg;base64"), "");
			}

			BX.ajax({
				method: 'POST',
				dataType: 'json',
				url: this.formActionUrl,
				data: formDataObj,
				preparePost: false,
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
							BXMobileApp.onCustomEvent("onCrmCompanyListUpdate", {}, true);
							BXMobileApp.onCustomEvent("onCrmCompanyViewUpdate", {formId: this.formId}, true);
							app.closeModalDialog({cache: false});
						}

						if (this.mode == "CREATE")
						{
							var viewPath = this.companyViewPath.replace("#company_id#", json.itemId);
							BXMobileApp.onCustomEvent("onCrmCompanyLoadPageBlank", {path: viewPath}, true);
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
								BXMobileApp.onCustomEvent("onCrmCompanyLoadPageBlank", {path: json.url, type: 'convert'}, true);
							}

							app.closeModalDialog({cache: false});
						}
					}
				}, this)
			});
		}
	}
};