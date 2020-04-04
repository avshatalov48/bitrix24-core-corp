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
		this.initialControl = this.settings["initialControl"];
	};

	BX.CrmProductCreateDialog.prototype = {
		show: function ()
		{
			var self = this;
			var zIndex = 996;
			var fieldsContainer = BX.create(
				"TABLE",
				{
					attrs: { id: this.random + "_table", "cellspacing": "7" }
				}
			);

			var fields = this.settings["fields"];
			var messages = this.settings["messages"];
			var valueControl, select, type, size1, size2, maxLength, value, checkCustomValue, customValues;
			var scriptPack = [], spIndex, spItem;
			
			customValues = {};
			checkCustomValue = (this.settings["customValues"] && typeof(this.settings["customValues"]) === "object");
			if (checkCustomValue)
			{
				customValues = this.settings["customValues"];
			}

			spIndex = 0;
			for (var i = 0; i < fields.length; i++)
			{
				value = fields[i]["value"];
				if (checkCustomValue && customValues.hasOwnProperty(fields[i]["textCode"]))
					value = customValues[fields[i]["textCode"]];

				if (fields[i]["skip"] === "Y")
					continue;

				type = fields[i]["type"];
				maxLength = parseInt(fields[i]["maxLength"]);
				size1 = 40;
				size2 = 4;
				switch (type)
				{
					case "select":
						var selectAttrs = {};
						if (fields[i]["params"] && typeof(fields[i]["params"]) === "object")
						{
							var params = fields[i]["params"];
							for (var paramName in params)
							{
								if (params.hasOwnProperty(paramName))
									selectAttrs[paramName] = params[paramName];
							}
						}
						selectAttrs["id"] = this.random + "_" + i;
						selectAttrs["className"] = "bx-crm-dialog-quick-create-field-select";
						selectAttrs["name"] = fields[i]["textCode"];
						valueControl = BX.create(
							"SELECT",
							{
								style: {marginLeft: "10px"},
								attrs: selectAttrs
							}
						);
						this.rebuildSelect(valueControl, fields[i]["items"], value);
						break;

					case "checkbox":
						valueControl = BX.create(
							"INPUT",
							{
								style: {marginLeft: "10px"},
								attrs: {
									id: this.random + "_" + i,
									type: type,
									"name": fields[i]["textCode"]
								}
							}
						);
						valueControl.checked = (value === "Y");
						break;

					case "textarea":
						valueControl = BX.create(
							"TEXTAREA",
							{
								style: {marginLeft: "10px"},
								attrs: {
									id: this.random + "_" + i,
									className: "bx-crm-dialog-quick-create-field-text-input",
									type: type,
									"name": fields[i]["textCode"],
									text: BX.util.htmlspecialchars(value),
									cols: size1,
									rows: size2,
									maxlength: (maxLength > 0) ? maxLength : 7500
								}
							}
						);
						break;

					case "custom":
						var parts = BX.processHTML(value);
						valueControl = BX.create(
							"DIV",
							{
								style: {marginLeft: "10px"},
								attrs: {
									id: this.random + "_" + i,
									className: "bx-crm-dialog-quick-create-field-custom"/*,
									type: type,
									text: BX.util.htmlspecialchars(value),
									cols: size1,
									rows: size2,
									maxlength: (maxLength > 0) ? maxLength : 7500*/
								},
								html: value
							}
						);
						var scripts = [], ii = 0;
						for (ii = 0; ii < parts["SCRIPT"].length; ii++)
						{
							scripts[ii] = {
								TYPE: (parts["SCRIPT"][ii].isInternal) ? "SCRIPT" : "SCRIPT_EXT",
								DATA: parts["SCRIPT"][ii].JS
							};
						}
						spItem = {
							"scripts": scripts,
							"styles": parts["STYLE"]
						};
						scriptPack[spIndex++] = spItem;
						break;

					default:
						valueControl = BX.create(
							"INPUT",
							{
								style: {marginLeft: "10px"},
								attrs: {
									id: this.random + "_" + i,
									className: "bx-crm-dialog-quick-create-field-text-input",
									type: type,
									"name": fields[i]["textCode"],
									value: value,
									size: size1,
									maxlength: (maxLength > 0) ? maxLength : 255
								}
							}
						);
						break;
				}

				var fieldTitle = BX.create("TD");
				if ("Y" === fields[i]["required"])
					fieldTitle.appendChild(BX.create("SPAN", {attrs: {style: "position: absolute; left: 18px; color: red;"}, text: "*"}));
				fieldTitle.appendChild(BX.create("SPAN", {"text": messages[fields[i]["textCode"]] + ":"}));

				fieldsContainer.appendChild(BX.create("TR", {
					children:
						[
							fieldTitle,
							BX.create("TD", {children: [valueControl]})
						]
				}));
			}

			var errorContainer = BX.create(
				"DIV",
				{
					attrs:
					{
						id: this.random + "_error",
						className: "bx-crm-dialog-quick-create-error-wrap"
					}
				}
			);
			var content = BX.create(
				"DIV",
				{
					style: {
						marginLeft: "12px",
						marginTop: "12px",
						marginRight: "12px",
						marginBottom: "12px",
						minWidth: "600px"/*,
						maxHeight: "800px",
						overflow: "auto"*/
					},
					attrs: {id: this.random + "_content"},
					children:
						[
							errorContainer, fieldsContainer
						]
				}
			);
			var actionUrl =
				this.settings["url"] ? BX.util.trim(this.settings["url"].toString()) : "/crm/product/edit/0/";
			var form = BX.create(
				"FORM",
				{
					attrs: {
						"id": this.settings["formId"],
						"name": this.settings["formId"],
						"method": "POST",
						"action": actionUrl,
						"enctype": "multipart/form-data"
					}
				}
			);

			var hidden = BX.create(
				"INPUT", {attrs: {type: "hidden", "name": "sessid", value: this.settings['sessid']}}
			);
			if (hidden)
				form.appendChild(hidden);
			hidden = BX.create(
				"INPUT", {attrs: {type: "hidden", "name": "ajaxSubmit", value: "Y"}}
			);
			if (hidden)
				form.appendChild(hidden);
			hidden = BX.create(
				"INPUT", {attrs: {type: "hidden", "name": "currencyTo", value: this.settings['ownerCurrencyId']}}
			);
			if (hidden)
				form.appendChild(hidden);
			hidden = null;

			form.appendChild(content);

			var popup = new BX.PopupWindow(
				"CrmProductCreateDialog_" + this.random,
				this.initialControl/*null*/,
				{
					overlay: {opacity: 10},
					autoHide: false,
					draggable: true,
					offsetLeft: 0,
					offsetTop: 0,
					zIndex: zIndex - 1100,
					bindOptions: {forceBindPosition: false},
					closeByEsc: true,
					closeIcon: { top: '10px', right: '15px' },
					titleBar: this.messages["dialogTitle"],
					events:
					{
						onPopupClose: function(){
							if(popup) {
								popup.destroy();
							}
						}
					},
					content: form,
					buttons: [
						new BX.PopupWindowButton(
							{
								text: this.messages["buttonCreateTitle"],
								className: "popup-window-button-accept",
								events:
								{
									"click": function()
									{
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
								text: this.messages["buttonCancelTitle"],
								className: "popup-window-button-link-cancel",
								events:
								{
									"click": function()
									{
										if (popup)
											popup.close();
									}
								}
							}
						)
					]
				}
			);
			for (spIndex = 0; spIndex < scriptPack.length; spIndex++)
			{
				if (scriptPack[spIndex]["scripts"].length > 0)
					BX.evalPack(scriptPack[spIndex]["scripts"]);
				if (scriptPack[spIndex]["styles"].length > 0)
					BX.loadCSS(scriptPack[spIndex]["styles"]);
			}
			popup.show();
			if (popup.popupContainer)
				BX.scrollToNode(popup.popupContainer);
		},
		createProduct: function()
		{
			var form = BX(this.settings["formId"]);
			BX.ajax.submitAjax(form, {
				method : "POST",
				processData : false,
				onsuccess: BX.delegate(function (response)
				{
					if (response === "")
					{
						BX.closeWait();
						this.showAjaxError("");
					}

					var data = BX.parseJSON(response, {});
					if (!data)
					{
						BX.closeWait();
						this.showAjaxError("");
					}

					var productId = 0, err = "";

					BX.closeWait();
					err = (data["err"] !== "") ? data["err"] : "";
					productId = (parseInt(data["productId"]) > 0) ? parseInt(data["productId"]) : 0;
					if (productId > 0)
					{
						if (this.popup)
						{
							this.popup.close();
							this.popup = null;
						}
						if (typeof(data["productData"]) !== "undefined")
						{
							var productData = data["productData"];
							if (typeof(productData["NAME"]) !== "undefined"
								&& typeof(productData["PRICE"]) !== "undefined"
								&& typeof(this.settings["productAdditionHandler"] === "function"))
							{
								var handler = this.settings["productAdditionHandler"];
								var productParams = {
									"product": [
										{
											"id": productId,
											"title": productData["NAME"],
											"customData": {
												"price": productData["PRICE"],
												"tax": {}
											}
										}
									]
								};
								if (typeof(productData["VAT_ID"]) !== "undefined")
									productParams["product"][0]["customData"]["tax"]["id"] = productData["VAT_ID"];
								if (typeof(productData["VAT_INCLUDED"]) !== "undefined")
									productParams["product"][0]["customData"]["tax"]["included"] = (productData["VAT_INCLUDED"] === "Y");
								if (data["measureData"] && typeof(data["measureData"]) === "object")
								{
									var measureData = data["measureData"];
									if (measureData["code"]
										&& parseInt(measureData["code"]) > 0
										&& measureData["name"])
									{
										productParams["product"][0]["customData"]["measure"] = {
											"code": measureData["code"],
											"name": measureData["name"]
										};
									}
								}
								handler(productParams);
							}
						}
					}
					else
					{
						this.showAjaxError(err);
					}
				}, this),
				onfailure: BX.delegate(function (response)
				{
					BX.closeWait();
					this.showAjaxError("");
				}, this)
			});
		},
		showAjaxError: function (err)
		{
			if (err === "")
				err = this.messages["ajaxError"];
			var errContainer = BX(this.errorContainerId);
			if (errContainer)
			{
				errContainer.innerHTML = err;
			}
			if (this.popup.popupContainer)
				BX.scrollToNode(this.popup.popupContainer);
		},
		setSelectValue: function (select, value)
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
		rebuildSelect: function (select, items, value)
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
if (typeof addNewTableRow === "undefined") {
	function addNewTableRow(tableID, row_to_clone)
	{
		var tbl = document.getElementById(tableID);
		var cnt = tbl.rows.length;
		if(row_to_clone == null)
			row_to_clone = cnt - 1;
		var sHTML = tbl.rows[row_to_clone].cells[0].innerHTML;
		var oRow = tbl.insertRow(row_to_clone+1);
		var oCell = oRow.insertCell(0);

		var s, e, n, p;
		p = 0;
		while(true)
		{
			s = sHTML.indexOf('[n',p);
			if(s<0)break;
			e = sHTML.indexOf(']',s);
			if(e<0)break;
			n = parseInt(sHTML.substr(s+2,e-s));
			sHTML = sHTML.substr(0, s)+'[n'+(++n)+']'+sHTML.substr(e+1);
			p=s+1;
		}
		p = 0;
		while(true)
		{
			s = sHTML.indexOf('__n',p);
			if(s<0)break;
			e = sHTML.indexOf('_',s+2);
			if(e<0)break;
			n = parseInt(sHTML.substr(s+3,e-s));
			sHTML = sHTML.substr(0, s)+'__n'+(++n)+'_'+sHTML.substr(e+1);
			p=e+1;
		}
		p = 0;
		while(true)
		{
			s = sHTML.indexOf('__N',p);
			if(s<0)break;
			e = sHTML.indexOf('__',s+2);
			if(e<0)break;
			n = parseInt(sHTML.substr(s+3,e-s));
			sHTML = sHTML.substr(0, s)+'__N'+(++n)+'__'+sHTML.substr(e+2);
			p=e+2;
		}
		p = 0;
		while(true)
		{
			s = sHTML.indexOf('xxn',p);
			if(s<0)break;
			e = sHTML.indexOf('xx',s+2);
			if(e<0)break;
			n = parseInt(sHTML.substr(s+3,e-s));
			sHTML = sHTML.substr(0, s)+'xxn'+(++n)+'xx'+sHTML.substr(e+2);
			p=e+2;
		}
		p = 0;
		while(true)
		{
			s = sHTML.indexOf('%5Bn',p);
			if(s<0)break;
			e = sHTML.indexOf('%5D',s+3);
			if(e<0)break;
			n = parseInt(sHTML.substr(s+4,e-s));
			sHTML = sHTML.substr(0, s)+'%5Bn'+(++n)+'%5D'+sHTML.substr(e+3);
			p=e+3;
		}

		var htmlObject = {'html': sHTML};
		BX.onCustomEvent(window, 'onAddNewRowBeforeInner', [htmlObject]);
		sHTML = htmlObject.html;

		oCell.innerHTML = sHTML;

		var patt = new RegExp ("<"+"script"+">[^\000]*?<"+"\/"+"script"+">", "ig");
		var code = sHTML.match(patt);
		if(code)
		{
			for(var i = 0; i < code.length; i++)
			{
				if(code[i] != '')
				{
					s = code[i].substring(8, code[i].length-9);
					jsUtils.EvalGlobal(s);
				}
			}
		}

		if (BX && BX.adminPanel)
		{
			BX.adminPanel.modifyFormElements(oRow);
			BX.onCustomEvent('onAdminTabsChange');
		}

		setTimeout(function() {
			var r = BX.findChildren(oCell, {tag: /^(input|select|textarea)$/i});
			if (r && r.length > 0)
			{
				for (var i=0,l=r.length;i<l;i++)
				{
					if (r[i].form && r[i].form.BXAUTOSAVE)
						r[i].form.BXAUTOSAVE.RegisterInput(r[i]);
					else
						break;
				}
			}
		}, 10);
	}
}
