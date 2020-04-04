if (typeof BX.CrmExch1cInvManager === "undefined")
{
	BX.CrmExch1cInvManager = function (settings)
	{
		this.settings = settings;
		this.settings["nextRekvNumber"] = {};
	};

	BX.CrmExch1cInvManager.prototype = {
		"PropertyTypeChange": function (fieldType, tabNumber)
		{
			var typeControl = BX("TYPE_" + fieldType + "_" + tabNumber);
			var valueControlPrefix = null;
			var arHideControlPrefix = {"VALUE1": true, "VALUE2": true, "VALUE3": true};
			if (typeControl)
			{
				if (typeControl.value == "")
					arHideControlPrefix["VALUE1"] = false;
				else if (typeControl.value == "ORDER")
					arHideControlPrefix["VALUE2"] = false;
				else if (typeControl.value == "PROPERTY")
					arHideControlPrefix["VALUE3"] = false;

				for (var i in arHideControlPrefix)
				{
					var control = BX(i + "_" + fieldType + "_" + tabNumber);
					if (control)
					{
						if (arHideControlPrefix[i] === true)
							control.style.display = "none";
						else
							control.style.display = "";
					}
				}
			}
		},
		"BuildRekvRow": function (rowId, rowInfo, tabNumber)
		{
			var self = this;
			var tr = BX.create("TR");
			var tdTitle = BX.create("TD", {"attrs": {"class": "bx-field-name bx-padding crm-exch-va"}});
			var fieldTitleId = rowId, fieldId, newoption;
			fieldTitleId = fieldId = fieldTitleId.toString();
			fieldTitleId = fieldTitleId.replace("REKV_n", "REKV_" + tabNumber + "_n");
			var inputTitle = BX.create("INPUT", {
				"attrs": {
					"id": fieldTitleId,
					"class": "crm-exch1c-rekv-name",
					"type": "text",
					"name": fieldTitleId,
					"value": BX.util.htmlspecialchars(rowInfo["NAME"]),
					"maxlength": 180
				}
			});
			tdTitle.appendChild(inputTitle);
			tdTitle.appendChild(BX.create("SPAN", {"text": ":", "attrs": {"style": "margin-left: 2px;"}}));
			var tdValue = BX.create("TD", {"attrs": {"class": "bx-field-value crm-exch-va"}});
			var fieldType = rowInfo['TYPE'];
			var param1 = fieldId, param2 = tabNumber;
			var typeSelect = BX.create("SELECT", {
				"attrs": {
					"id": "TYPE_" + fieldId + "_" + tabNumber,
					"name": "TYPE_" + fieldId + "_" + tabNumber
				},
				"events": {"change": function () {self.PropertyTypeChange(param1, param2)}}
			});

			newoption = new Option(this.settings["fieldTypes"]["other"], "", false, false);
			typeSelect.options[0] = newoption;
			newoption = new Option(this.settings["fieldTypes"]["order"], "ORDER", false, false);
			typeSelect.options[1] = newoption;
			newoption = new Option(this.settings["fieldTypes"]["property"], "PROPERTY", false, false);
			typeSelect.options[2] = newoption;
			if (fieldType !== "ORDER" && fieldType !== "PROPERTY")
				typeSelect.selectedIndex = 0;
			else if (fieldType === "ORDER")
				typeSelect.selectedIndex = 1;
			else if (fieldType === "PROPERTY")
				typeSelect.selectedIndex = 2;

			tdValue.appendChild(typeSelect);
			var fieldValue = rowInfo['VALUE'], f;
			var orderFieldsList = this.settings["arOrderFieldsList"];
			var orderFieldsNameList = this.settings["arOrderFieldsNameList"];
			var value2Select = BX.create("SELECT", {
				"attrs": {
					"id": "VALUE2_" + fieldId + "_" + tabNumber,
					"class": "crm-exch1c-val-ctrl",
					"name": "VALUE2_" + fieldId + "_" + tabNumber,
					"style": (fieldType !== "ORDER") ? "display: none;" : ""
				}
			});
			for (f = 0; f < orderFieldsList.length; f++)
			{
				newoption = new Option(BX.util.htmlspecialchars(orderFieldsNameList[f]), orderFieldsList[f], false, false);
				value2Select.options[f] = newoption;
				if (fieldType === "ORDER" && orderFieldsList[f] == fieldValue)
					value2Select.selectedIndex = f;
			}
			tdValue.appendChild(value2Select);
			var personTypeId = this.settings["tabNumberPersonType"][tabNumber];
			var propertyFieldsList = this.settings["arPropFieldsList"][personTypeId];
			var propertyFieldsNameList = this.settings["arPropFieldsNameList"][personTypeId];
			var value3Select = BX.create("SELECT", {
				"attrs": {
					"id": "VALUE3_" + fieldId + "_" + tabNumber,
					"class": "crm-exch1c-val-ctrl",
					"name": "VALUE3_" + fieldId + "_" + tabNumber,
					"style": (fieldType !== "PROPERTY") ? "display: none;" : ""
				}
			});
			for (f = 0; f < propertyFieldsList.length; f++)
			{
				newoption = new Option(BX.util.htmlspecialchars(propertyFieldsNameList[f]), propertyFieldsList[f], false, false);
				value3Select.options[f] = newoption;
				if (fieldType === "PROPERTY" && propertyFieldsList[f] == fieldValue)
					value3Select.selectedIndex = f;
			}
			tdValue.appendChild(value3Select);
			var value1Input = BX.create("INPUT", {
				"attrs": {
					"id": "VALUE1_" + fieldId + "_" + tabNumber,
					"class": "crm-exch1c-val-ctrl",
					"type": "text",
					"name": "VALUE1_" + fieldId + "_" + tabNumber,
					"value": (fieldType !== "ORDER" && fieldType !== "PROPERTY") ? fieldValue : "",
					"style": (fieldType !== "ORDER" && fieldType !== "PROPERTY") ? "" : "display: none",
					"maxlength": 180
				}
			});
			tdValue.appendChild(value1Input);
			tr.appendChild(tdTitle);
			tr.appendChild(tdValue);

			return tr;
		},
		"addRekv": function (tabNumber)
		{
			var field = BX(this.settings["arLastFieldInfo"][tabNumber]);
			if (field)
			{
				var container = field.parentNode.parentNode.parentNode;
				if (container)
				{
					var nextNumber = this.settings["nextRekvNumber"][tabNumber];
					var tr = this.BuildRekvRow("REKV_n" + nextNumber, {"NAME": "", "TYPE": "", "VALUE": ""}, tabNumber);
					container.appendChild(tr);
					this.settings["nextRekvNumber"][tabNumber] = nextNumber + 1;
				}
			}
		},
		"BuildRekvBlocks": function ()
		{
			var self = this;
			var lastFieldInfo = this.settings["arLastFieldInfo"];
			var tabNumber, fieldId, field;
			var container, tr, td, block, title;
			var param1;
			for (var i in lastFieldInfo)
			{
				tabNumber = i;
				fieldId = lastFieldInfo[i];
				field = BX(fieldId);
				if (field)
				{
					container = field.parentNode.parentNode.parentNode;
					if (container)
					{
						// title
						tr = BX.create("TR");
						td = BX.create("TD", {"attrs": {"colspan": "2"}});
						block = BX.create("DIV", {
							"attrs": {"id": "REKV_BLOCK_" + tabNumber, "class": "crm-exch1c-rekv-title"},
							"html": BX.util.htmlspecialchars(this.settings['rekvTitle'])
						});
						td.appendChild(block);
						tr.appendChild(td);
						container.appendChild(tr);

						// rekv fields
						var rekvFields = this.settings["expRekvParams"];
						var fieldName, j, k;
						var fields = rekvFields[tabNumber];
						var nRekv = 0;
						for (k in fields)
						{
							tr = this.BuildRekvRow(k, fields[k], tabNumber);
							container.appendChild(tr);
							nRekv++;
						}
						this.settings["nextRekvNumber"][tabNumber] = nRekv;

						for (var n = 0; n < 3; n++)
							this.addRekv(tabNumber)

						var bContainer = container.parentNode.parentNode;
						if (bContainer)
						{
							var buttonAddRekv = BX.create("INPUT", {
								"attrs": {
									"id": "ADD_REKV_BTN_" + tabNumber,
									"class": "crm-exch1c-more-button",
									"type": "button",
									"value": this.settings["addRekvButtonTitle"]
								},
								"events": {"click": function () {self.addRekv(this.id.substring(13));}}
							});
							bContainer.appendChild(BX.create("BR"));
							bContainer.appendChild(buttonAddRekv);
						}
					}
				}
			}
		},
		"HideEmptyFields": function ()
		{
			var arEmptyFields = this.settings["arEmptyFields"];
			if (arEmptyFields)
			{
				var tabFields, field, container, i;
				for (var tabNumber in arEmptyFields)
				{
					tabFields = arEmptyFields[tabNumber];
					for (i = 0; i < tabFields.length; i++)
					{
						field = BX(tabFields[i]);
						if (field)
						{
							container = field.parentNode.parentNode;
							if (container)
								container.style.display = "none";
						}
					}
					if (i > 0)    // empty fields exists
					{
						this.AddShowEmptyFieldsButton(tabNumber);
					}
				}
			}
		},
		"AddShowEmptyFieldsButton": function (tabNumber)
		{
			var self = this;
			var lastFieldInfo = this.settings["arLastFieldInfo"];
			var field = BX(lastFieldInfo[tabNumber]);
			var fieldContainer, fieldSibling, container, button;
			if (field)
			{
				fieldContainer = field.parentNode.parentNode;
				if (fieldContainer)
				{
					container = fieldContainer.parentNode;
					if (container)
					{
						fieldSibling = BX.findNextSibling(fieldContainer, {"tag": "TR"});
						button = BX.create("INPUT", {
							"attrs": {
								"id": "SHOW_ALL_BTN_" + tabNumber,
								"class": "crm-exch1c-more-button",
								"type": "button",
								"value": this.settings["showEmptyFieldsButtonTitle"]
							},
							"events": {"click": function () {this.style.display = "none"; self.ShowEmptyFields(this.id.substring(13));}}
						});
						if (fieldSibling)
							container.insertBefore(button, fieldSibling);
						else
							container.appendChild(button);
					}
				}
			}
		},
		"ShowEmptyFields": function (tabNumber)
		{
			var arEmptyFields = this.settings["arEmptyFields"];
			var tabFields = arEmptyFields[tabNumber];
			if (tabFields)
			{
				for (i = 0; i < tabFields.length; i++)
				{
					field = BX(tabFields[i]);
					if (field)
					{
						container = field.parentNode.parentNode;
						if (container)
							container.style.display = "";
					}
				}
			}
		},
		"ShowAccountNumberWarning": function ()
		{
			var title = this.settings["accountNumberWarningTitle"];
			var tabId = this.settings["tabInvoiceExportId"];
			var inpName = this.settings["accountNumberInputName"];
			if (title.length > 0 && tabId.length > 0 && inpName.length > 0)
			{
				var tab = BX(tabId);
				if (tab)
				{
					var inp = BX.findChild(tab, {"tag": "input", "attr": {"name": inpName}}, true, false);
					if (inp)
					{
						var span = BX.create("DIV", {
							"attrs": {
								"class": "bx-crm-edit-content-location-description"
							},
							"html": title //BX.tools.htmlspecialchars(title)
						});
						inp.parentNode.appendChild(span);
					}
				}
			}
		}
	};
}
