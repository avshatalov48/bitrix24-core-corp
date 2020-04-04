if (typeof BX.CrmProductCreateDialog === "undefined")
{
	BX.CrmProductCreateDialog = function (settings)
	{
		this.settings = settings;
		this.messages = settings["messages"];
		this.random = Math.random().toString().substring(2);
		this.popupContentId = "";
		this.errorContainerId = "";
		this.popup = null;

		var self = this;
		var createProductLink = BX(this.settings["createProductBtnId"]);
		if (createProductLink)
		{
			this.createLink = createProductLink;
			var span = BX.findChild(createProductLink, {"tag": "span"});
			BX.bind(span, "click", function(){self.show()});
		}
	};

	BX.CrmProductCreateDialog.prototype = {
		"show": function ()
		{
			var self = this;
			var fieldsContainer = BX.create(
				"TABLE",
				{
					"attrs": {"id": this.random + "_table", "cellspacing": "7"}
				}
			);
			var errorContainer = BX.create(
				"DIV",
				{
					"attrs": {
						"id": this.random + "_error",
						"className": "bx-crm-dialog-quick-create-error-wrap"
					}
				}
			);
			var content = BX.create(
				"DIV",
				{
					"style": {"marginLeft": "12px", "marginTop": "12px", "marginRight": "12px", "marginBottom": "12px"},
					"attrs": {"id": this.random + "_content"},
					"children":
						[
							errorContainer, fieldsContainer
						]
				}
			);

			var itemParams = null;
			var fields = this.settings["fields"];
			var messages = this.settings["messages"];
			var valueControl, select, type, size1, size2, maxLength;
			for (var i = 0; i < fields.length; i++)
			{
				if (fields[i]["skip"] === "Y")
					continue;

				itemParams = {
					"children":
						[
							BX.create("TD", {"children": [BX.create("SPAN", {"text": messages[fields[i]["textCode"]] + ":"})]})
						]
				};
				type = fields[i]["type"];
				maxLength = parseInt(fields[i]["maxLength"]);
				size1 = 40;
				size2 = 4;
				switch (type)
				{
					case "select":
						valueControl = BX.create(
							"SELECT",
							{
								"style": {"marginLeft": "10px"},
								"attrs": {
									"id": this.random + "_" + i,
									"className": "bx-crm-dialog-quick-create-field-select"
								}
							}
						);
						this.rebuildSelect(valueControl, fields[i]['items'], fields[i]['value']);
						break;
					case "checkbox":
						valueControl = BX.create(
							"INPUT",
							{
								"style": {"marginLeft": "10px"},
								"attrs": {
									"id": this.random + "_" + i,
									"type": type
								}
							}
						);
						valueControl.checked = fields[i]["value"] === 'Y';
						break;
					case 'textarea':
						valueControl = BX.create(
							"TEXTAREA",
							{
								"style": {"marginLeft": "10px"},
								"attrs": {
									"id": this.random + "_" + i,
									"className": "bx-crm-dialog-quick-create-field-text-input",
									"type": type,
									"text": BX.util.htmlspecialchars(fields[i]["value"]),
									"cols": size1,
									"rows": size2,
									"maxlength": (maxLength > 0) ? maxLength : 7500
								}
							}
						);
						break;
					default:
						valueControl = BX.create(
							"INPUT",
							{
								"style": {"marginLeft": "10px"},
								"attrs": {
									"id": this.random + "_" + i,
									"className": "bx-crm-dialog-quick-create-field-text-input",
									"type": type,
									"value": fields[i]["value"],
									"size": size1,
									"maxlength": (maxLength > 0) ? maxLength : 255
								}
							}
						);
						break;
				}
				itemParams.children.push(BX.create("TD", {"children": [valueControl]}));
				fieldsContainer.appendChild(BX.create("TR", itemParams));
			}

			var popup = new BX.PopupWindow(
				"CrmProductCreateDialog_" + this.random,
				/*this.createLink*/null,
				{
					overlay: {opacity: 10},
					autoHide: false,
					draggable: true,
					offsetLeft: 0,
					offsetTop: 0,
					bindOptions: { forceBindPosition: false },
					closeByEsc: true,
					closeIcon: { top: '10px', right: '15px' },
					titleBar: this.messages["dialogTitle"],
					events:
					{
						onPopupClose: function(){ if(popup) popup.destroy(); }
					},
					content: content,
					buttons: [
						new BX.PopupWindowButton(
							{
								"text": this.messages["buttonCreateTitle"],
								"className": "popup-window-button-accept",
								"events":
								{
									"click": function() {
										if (popup)
										{
											if (content)
											{
												self.popupContentId = content.id;
												self.errorContainerId = errorContainer.id;
												self.popup = popup;
												self.createProduct();
											}
										}
									}
								}
							}
						),
						new BX.PopupWindowButtonLink(
							{
								"text": this.messages["buttonCancelTitle"],
								"className": "popup-window-button-link-cancel",
								"events":
								{
									"click": function() {
										if (popup) popup.close();
									}
								}
							}
						)
					]
				}
			);
			popup.show();
		},
		"createProduct": function()
		{
			var i, inp, postData = {};
			var fields = this.settings['fields'];
			for (i = 0; i < fields.length; i++)
			{
				if (fields[i]["skip"] === "Y")
					continue;

				inp = BX(this.random + "_" + i);
				if (inp)
				{
					postData[fields[i]["textCode"]] = ((fields[i]['type'] !== 'checkbox') ? inp.value : (inp.checked ? "Y" : "N"));
				}
			}
			if (i > 0)
			{
				var self = this;
				var currencyId = this.settings['ownerCurrencyId'];
				postData["sessid"] = this.settings['sessid'];
				postData["ajax"] = "Y";
				if (currencyId.length > 0 && postData["CURRENCY"].length > 0 && currencyId !== postData["CURRENCY"])
				{
					postData["currencyTo"] = currencyId;
				}
				BX.showWait(this.popupContentId, this.messages['waitMessage']);
				BX.ajax({
					"url": this.settings['url'],
					"method": "POST",
					"dataType": "json",
					"data": postData,
					"onsuccess": function (data)
					{
						var productId = 0, err = "";

						BX.closeWait();
						err = (data["err"] !== "") ? data["err"] : "";
						productId = (parseInt(data["productId"]) > 0) ? parseInt(data["productId"]) : 0;
						if (productId > 0)
						{
							if (self.popup)
							{
								self.popup.close();
								self.popup = null;
							}
							if (typeof(data["productData"]) !== "undefined")
							{
								var productData = data["productData"];
								if (typeof(productData["NAME"]) !== "undefined"
									&& typeof(productData["PRICE"]) !== "undefined"
									&& typeof(self.settings["productAdditionHandler"] === "function"))
								{
									var handler = self.settings["productAdditionHandler"];
									var productParams = {
										"product": [
											{
												"id": productId,
												"title": productData["NAME"],
												"customData": {
													"price": productData["PRICE"]
												}
											}
										]
									};
									handler(productParams);
								}
							}
						}
						else
						{
							self.showAjaxError(err);
						}
					},
					"onfailure": function (data)
					{
						BX.closeWait();
						self.showAjaxError("");
					}
				});
			}
		},
		"showAjaxError": function (err)
		{
			if (err === "")
				err = this.messages["ajaxError"];
			var errContainer = BX(this.errorContainerId);
			if (errContainer)
			{
				errContainer.innerHTML = err;
			}
		},
		"setSelectValue": function (select, value)
		{
			var i, j;
			var bFirstSelected = false;
			var bMultiple = !!(select.getAttribute('multiple'));
			if (!(value instanceof Array)) value = [value];
			for (i=0; i<select.options.length; i++)
			{
				for (j in value)
				{
					if (select.options[i].value == value[j])
					{
						if (!bFirstSelected) {bFirstSelected = true; select.selectedIndex = i;}
						select.options[i].selected = true;
						break;
					}
				}
				if (!bMultiple && bFirstSelected) break;
			}
		},
		"rebuildSelect": function (select, items, value)
		{
			var opt, el, i, j;
			var setSelected = false;
			var bMultiple;

			if (!(value instanceof Array))
				value = [value];
			if (select)
			{
				bMultiple = !!(select.getAttribute('multiple'));
				while (opt = select.lastChild)
					select.removeChild(opt);
				for (i = 0; i < items.length; i++)
				{
					el = document.createElement("option");
					el.value = items[i]['id'];
					el.innerHTML = BX.util.htmlspecialchars(items[i]['title']);
					try
					{
						// for IE earlier than version 8
						select.add(el,select.options[null]);
					}
					catch (e)
					{
						el = document.createElement("option");
						el.text = items[i]['title'];
						select.add(el,null);
					}
					if (!setSelected || bMultiple)
					{
						for (j = 0; j < value.length; j++)
						{
							if (items[i]['id'] == value[j])
							{
								el.selected = true;
								if (!setSelected)
								{
									setSelected = true;
									select.selectedIndex = i;
								}
								break;
							}
						}
					}
				}
			}
		}
	};
}
