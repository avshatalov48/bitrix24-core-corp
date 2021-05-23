BX.namespace("BX.Mobile.Crm.Product.Edit");

BX.Mobile.Crm.Product.Edit = {
	previewPhotoFile: "",
	previewPhotoDel: "",
	detailPhotoFile: "",
	detailPhotoDel: "",
	productViewPath: "",

	init: function(params)
	{
		if (params && typeof params === "object")
		{
			this.ajaxPath = params.ajaxPath || "";
			this.formActionUrl = params.formActionUrl || "";
			this.formId = params.formId || "";
			this.productViewPath = params.productViewPath || "";
			this.mode = params.mode || "";
		}

		BX.addCustomEvent("onCrmProductDetailDelete", function(){
			BXMobileApp.onCustomEvent("onCrmProductListUpdate", {}, true);
			BXMobileApp.UI.Page.close({drop:true});
		});

		if (this.mode == "EDIT")
		{
			BXMobileApp.addCustomEvent("onCrmProductEditUpdate", BX.proxy(function(data){
				if (data.formId == this.formId)
				{
					BXMobileApp.UI.Page.reload();
				}
			}, this));
		}

		if (this.mode == "VIEW")
		{
			BXMobileApp.addCustomEvent("onCrmProductViewUpdate", BX.proxy(function(data){
				if (data.formId == this.formId)
				{
					BXMobileApp.UI.Page.reload();
				}
			}, this));

			//to refresh edit page after editing view page
			BX.addCustomEvent("onSubmitAjaxSuccess", BX.proxy(function (data) {
				if (data.formId == this.formId)
					BXMobileApp.onCustomEvent("onCrmProductEditUpdate", {formId: this.formId}, true);
			}, this));
		}

		if (this.mode == "CREATE") // close create page after saving
		{
			var onProductFormCloseHandler = function()
			{
				BX.removeCustomEvent("onOpenPageAfter", onProductFormCloseHandler);
				app.closeModalDialog();
			};

			BX.addCustomEvent("onCrmProductClosePage", function () {
				if (!(window.isCurrentPage && window.isCurrentPage == "Y"))
				{
					BX.addCustomEvent("onOpenPageAfter", onProductFormCloseHandler);
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
					BXMobileApp.onCustomEvent("onCrmProductListUpdate", {}, true); // update list after editing on view page
				}
			}, this));
		}

		if (this.mode == "EDIT" || this.mode == "CREATE") // on change photo
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

	onChange : function(form, node, obj, fileObj)
	{
		if (!(typeof fileObj === 'object' && fileObj))
			return;

		if (fileObj.action == "add")
		{
			if (obj.controlName == "PREVIEW_PICTURE")
				this.previewPhotoFile = fileObj.file;
			else if (obj.controlName == "DETAIL_PICTURE")
				this.detailPhotoFile = fileObj.file;
		}
		else if (fileObj.action == "delete")
		{
			if (obj.controlName == "PREVIEW_PICTURE")
			{
				this.previewPhotoDel = "Y";
				this.previewPhotoFile = "";
			}
			else if (obj.controlName == "DETAIL_PICTURE")
			{
				this.detailPhotoDel = "Y";
				this.detailPhotoFile = "";
			}
		}
	},

	submit: function()
	{
		BXMobileApp.UI.Page.LoadingScreen.show();

		var form = BX(this.formId);
		if(form)
		{
			var dataFormValues = {save: "Y", sessid:BX.bitrix_sessid()};

			BX.Mobile.Crm.Detail.collectInterfaceFormData(form, dataFormValues);

			var formDataObj = new FormData(); // for sending photo file
			for (var i in dataFormValues)
			{
				if (dataFormValues.hasOwnProperty(i))
				{
					formDataObj.append(i, dataFormValues[i]);
				}
			}

			if (this.previewPhotoFile)
			{
				formDataObj.append('PREVIEW_PICTURE', this.previewPhotoFile, this.previewPhotoFile.name);
			}
			else if (this.previewPhotoDel == "Y")
			{
				formDataObj.append('PREVIEW_PICTURE_del', 'Y');
				formDataObj.append('PREVIEW_PICTURE', BX.UploaderUtils.dataURLToBlob("data:image/jpg;base64"), "");
			}

			if (this.detailPhotoFile)
			{
				formDataObj.append('DETAIL_PICTURE', this.detailPhotoFile, this.detailPhotoFile.name);
			}
			else if (this.previewPhotoDel == "Y")
			{
				formDataObj.append('DETAIL_PICTURE_del', 'Y');
				formDataObj.append('DETAIL_PICTURE', BX.UploaderUtils.dataURLToBlob("data:image/jpg;base64"), "");
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
						app.alert({text: json.error});
					}
					else
					{
						if (this.mode == "EDIT")
						{
							BXMobileApp.onCustomEvent("onCrmProductListUpdate", {}, true);
							BXMobileApp.onCustomEvent("onCrmProductViewUpdate", {formId: this.formId}, true);
							app.closeModalDialog({cache: false});
						}

						if (this.mode == "CREATE")
						{
							var viewPath = this.productViewPath.replace("#product_id#", json.itemId);
							BXMobileApp.onCustomEvent("onCrmProductLoadPageBlank", {path: viewPath}, true);
							app.closeModalDialog({cache: false});
						}
					}

					BXMobileApp.UI.Page.LoadingScreen.hide();
				}, this)
			});
		}
	}
};