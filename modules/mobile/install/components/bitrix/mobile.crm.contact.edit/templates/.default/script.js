BX.namespace("BX.Mobile.Crm.Contact.Edit");

BX.Mobile.Crm.Contact.Edit = {
	ajaxPath: "",
	formActionUrl: "",
	formId: "",
	contactViewPath: "",
	mode: "",
	companyInfo: "",
	isRestrictedMode: false,
	onSelectCompanyEventName: "",
	photoFile: "",
	photoDel: "",

	init: function(params) {
		if (params && typeof params === "object") {

			this.ajaxPath = params.ajaxPath || "";
			this.formActionUrl = params.formActionUrl || "";
			this.formId = params.formId || "";
			this.contactViewPath = params.contactViewPath || "";
			this.mode = params.mode || "";
			this.isRestrictedMode = params.isRestrictedMode || false;
			this.leadId = params.leadId || "";

			this.companyInfo = params.companyInfo || "";
			this.companyContainerNode = document.querySelector("[data-role='mobile-crm-contact-edit-company']") || "";
			this.onSelectCompanyEventName = params.onSelectCompanyEventName || "";
		}

		if (this.mode == "EDIT")
		{
			BXMobileApp.addCustomEvent("onCrmContactEditUpdate", BX.proxy(function(data){
				if (data.formId == this.formId)
				{
					BXMobileApp.UI.Page.reload();
				}
			}, this));
		}

		if (this.mode == "VIEW")
		{
			BXMobileApp.addCustomEvent("onCrmContactViewUpdate", BX.proxy(function(data){
				if (data.formId == this.formId)
				{
					BXMobileApp.UI.Page.reload();
				}
			}, this));

			//to refresh edit page after editing view page
			BX.addCustomEvent("onSubmitAjaxSuccess", BX.proxy(function (data) {
				if (data.formId == this.formId)
					BXMobileApp.onCustomEvent("onCrmContactEditUpdate", {formId: this.formId}, true);
			}, this));
		}

		if (this.mode == "CREATE" || this.mode == "CONVERT") {
			var onContactFormCloseHandler = function () {
				BX.removeCustomEvent("onOpenPageAfter", onContactFormCloseHandler);
				app.closeModalDialog();
			};

			BX.addCustomEvent("onCrmContactClose", function () {
				if (!(window.isCurrentPage && window.isCurrentPage == "Y")) {
					BX.addCustomEvent("onOpenPageAfter", onContactFormCloseHandler);
				}
				else {
					window.isCurrentPage = "";
				}
			});
		}

		//generate company's html
		var companyParams = {
			entityContainerNode: this.companyContainerNode,
			entityInfo: this.companyInfo,
			isRestrictedMode: this.isRestrictedMode,
			isMultiEntity: true,
			onSelectEventName: this.onSelectCompanyEventName
		};
		this.companyEditor = new BX.Mobile.Crm.EntityEditor(companyParams);// entity's editor

		BX.addCustomEvent("onCrmContactDetailDelete", function(){
			BXMobileApp.onCustomEvent("onCrmContactListUpdate", {}, true);
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
					BXMobileApp.onCustomEvent("onCrmContactListUpdate", {}, true); // update list after editing on view page
				}
			}, this));
		}

		if (this.mode == "EDIT" || this.mode == "CREATE") // on change photo
		{
			var initFormForFile = BX.delegate(function(formId, gridId, obj) {
				if (formId == this.formId && obj)
				{
					BX.addCustomEvent(obj, 'onChange', BX.proxy(this.onChangePhoto, this));
				}
			}, this);
			BX.addCustomEvent("onInitialized", initFormForFile);
			var form = BX.Mobile.Grid.Form.getByFormId(this.formId);
			initFormForFile(this.formId, '', form);
		}
	},

	onChangePhoto : function(form, node, obj, fileObj)
	{
		if (!(typeof fileObj === 'object' && fileObj))
			return;

		if (fileObj.action == "add")
		{
			this.photoFile = fileObj.file;
		}
		else if (fileObj.action == "delete")
		{
			this.photoDel = "Y";
			this.photoFile = "";
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

			if (this.companyEditor)
			{
				dataFormValues["COMPANY_IDS"] = this.companyEditor.curEntityId;

				//searching for the new company which will be created with contact
				for(var key in this.companyEditor.curEntityId)
				{
					if(this.companyEditor.curEntityId[key] == 0)
					{
						dataFormValues["NEW_COMPANY_TITLE"] = this.companyEditor.entityInfo[key]["name"];
						break;
					}
				}

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

			if (this.photoFile)
			{
				formDataObj.append('PHOTO', this.photoFile, this.photoFile.name);
			}
			else if (this.photoDel == "Y")
			{
				formDataObj.append('PHOTO_del', 'Y');
				formDataObj.append('PHOTO', BX.UploaderUtils.dataURLToBlob("data:image/jpg;base64"), "");
			}

			BX.ajax({
				method: 'POST',
				dataType: 'json',
				url: this.formActionUrl,
				data: formDataObj,
				preparePost: false,
				onsuccess: BX.proxy(function(json)
				{
					if (json.error)
					{
						BX.Mobile.Crm.showErrorAlert(json.error);
					}
					else
					{
						if (this.mode == "EDIT")
						{
							BXMobileApp.onCustomEvent("onCrmContactListUpdate", {}, true);
							BXMobileApp.onCustomEvent("onCrmContactViewUpdate", {formId: this.formId}, true);
							app.closeModalDialog({cache: false});
						}

						if (this.mode == "CREATE")
						{
							var viewPath = this.contactViewPath.replace("#contact_id#", json.itemId);
							BXMobileApp.onCustomEvent("onCrmContactLoadPageBlank", {path: viewPath}, true);
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
								BXMobileApp.onCustomEvent("onCrmContactLoadPageBlank", {path: json.url, type: 'convert'}, true);
							}

							app.closeModalDialog({cache: false});
						}
					}

					BXMobileApp.UI.Page.LoadingScreen.hide();
				}, this)
			});
		}
	}
};