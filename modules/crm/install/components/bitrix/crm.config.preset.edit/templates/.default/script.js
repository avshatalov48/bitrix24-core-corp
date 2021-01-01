BX.namespace("BX.Crm");

if (typeof(BX.Crm.rebuildSelect) === "undefined")
{
	BX.Crm.rebuildSelect = function (select, items, value)
	{
		var opt, optIndex, el, i, j;
		var setSelected = false;
		var bMultiple, bGroup;
		var curGroup = null;

		if (!(value instanceof Array))
			value = [value];
		if (select)
		{
			bMultiple = !!(select.getAttribute('multiple'));
			while (opt = select.lastChild)
				select.removeChild(opt);
			optIndex = 0;
			for (i = 0; i < items.length; i++)
			{
				bGroup = false;
				if (items[i]["type"])
				{
					if (items[i]["type"] === "group")
					{
						el = document.createElement("optgroup");
						el.label = items[i]['title'];
						bGroup = true;
					}
					else if (items[i]["type"] === "endgroup")
					{
						curGroup = null;
						continue;
					}
				}
				else
				{
					el = document.createElement("option");
					el.value = items[i]['id'];
					el.innerHTML = BX.util.htmlspecialchars(items[i]['title']);
				}

				if (!bGroup && curGroup)
					curGroup.appendChild(el);
				else
					select.appendChild(el);

				if (bGroup)
				{
					curGroup = el;
				}
				else
				{
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
									select.selectedIndex = optIndex;
								}
								break;
							}
						}
					}
					optIndex++;
				}
			}
		}
	};
}

BX.Crm.PresetFieldListManagerClass = (function ()
{
	var PresetFieldListManagerClass = function (settings)
	{
		this.settings = settings ? settings : {};
		this._form = BX(this.settings["formId"]);
	};

	PresetFieldListManagerClass.prototype = {
		getMessage: function(name)
		{
			return typeof(this.settings.messages[name]) != 'undefined' ? this.settings.messages[name] : '';
		},
		addField: function ()
		{
			this.editField(0);
		},
		editField: function (fieldId)
		{
			var fieldKey, fieldNameValue, parts;

			fieldKey = fieldId;
			if (BX.Type.isStringFilled(fieldId))
			{
				parts = fieldId.split("_");
				if (parts.length > 1)
				{
					fieldId = parts[1];
				}
			}
			fieldId = parseInt(fieldId);
			if (fieldId < 0)
				fieldId = 0;

			if (this.dlg && this.dlg.popup)
				return;

			fieldNameValue = "";

			this.dlg = {
				popupId: this.settings['id'] + ((fieldId === 0) ? '_FieldAdd' : '_FieldEdit'),
				popup: null,
				elements: {
					createNew: null,
					fieldName: null,
					fieldNameWrapper: null,
					fieldType: null,
					fieldTypeWrapper: null,
					fieldTitle: null,
					fieldTitleWrapper: null,
					inShortList: null,
					sort: null
				},
				fieldId: fieldId,
				fieldData: {
					"FIELD_NAME":    fieldNameValue,
					"FIELD_TITLE":   this.getMessage('defaultFieldTitle'),
					"FIELD_ETITLE":  "",
					"SORT":          this.getMessage('defaultSort'),
					"IN_SHORT_LIST": this.getMessage('defaultInShortList')
				}
			};
			if (fieldId > 0 && this.settings.fieldData[fieldKey])
				this.dlg.fieldData = this.settings.fieldData[fieldKey];


			this.dlg.popup = new BX.PopupWindow(
				this.dlg.popupId,
				null,
				{
					overlay: {opacity: 10},
					autoHide: false,
					draggable: true,
					offsetLeft: 0,
					offsetTop: 0,
					bindOptions: { forceBindPosition: false },
					closeByEsc: true,
					closeIcon: { top: '10px', right: '15px' },
					titleBar: this.getMessage(this.dlg.fieldId > 0 ? 'fieldEditDialogTitle' : 'fieldAddDialogTitle'),
					events:
					{
						onPopupShow: function()
						{
						},
						onPopupClose: BX.delegate(
							function()
							{
								this.dlg.popup.destroy();
							},
							this
						),
						onPopupDestroy: BX.delegate(
							function()
							{
								delete(this.dlg);
							},
							this
						)
					},
					content: this._prepareFieldEditDialogContent(),
					"buttons": [
						new BX.PopupWindowButton(
							{
								"text": (this.dlg.fieldId === 0) ?
									this.getMessage("addBtnText") : this.getMessage("editBtnText"),
								"className": "popup-window-button-accept",
								"events":
								{
									"click": BX.delegate(this._hanleFieldEditDialogSave, this)
								}
							}
						),
						new BX.PopupWindowButtonLink(
							{
								"text": this.getMessage("cancelBtnText"),
								"className": "popup-window-button-link-cancel",
								"events":
								{
									"click": BX.delegate(this._hanleFieldEditDialogCancel, this)
								}
							}
						)
					]
				}
			);

			this.dlg.popup.show();
			BX.focus(this.dlg.elements.fieldName);
		},
		_prepareFieldEditDialogContent: function()
		{
			var i, selectElements, defaultValue;

			//table
			var tab = BX.create(
				'TABLE',
				{
					"style": { "marginLeft": "12px", "marginTop": "12px", "marginRight": "12px", "marginBottom": "12px" },
					"attrs": {"cellspacing": "7"}
				}
			);

			// CREATE_NEW
			if (this.dlg.fieldId <= 0)
			{
				var radioGroupId = this.dlg.popupId + "_CREATE_NEW";
				tab.appendChild(BX.create("TR", {
					children: [
						BX.create("TD", {"attrs": {"colspan": "2"}, "children": [
							BX.create("TABLE", {"attrs": {"style": "width: 100%; text-align: center;"}, "children": [
								BX.create("TR", {"children": [
									BX.create("TD", {"children": [
										this.dlg.elements.createNew = BX.create("INPUT", {
											"attrs": {
												"id": radioGroupId + "_1",
												"type": "radio",
												"name": radioGroupId,
												"value": "Y"
											},
											"props": {"checked": true},
											"events": {
												"click": BX.delegate(this._hanleCreateNewFlagSwitch, this)
											}
										}),
										BX.create("LABEL", {
											"attrs": {"for": radioGroupId + "_1"},
											"text": this.getMessage('createNewTitle')
										})
									]}),
									BX.create("TD", {"children": [
										BX.create("INPUT", {
											"attrs": {
												"id": radioGroupId + "_2",
												"type": "radio",
												"name": radioGroupId,
												"value": "N"
											},
											"events": {
												"click": BX.delegate(this._hanleCreateNewFlagSwitch, this)
											}
										}),
										BX.create("LABEL", {
											"attrs": {"for": radioGroupId + "_2"},
											"text": this.getMessage('createSelectedTitle')
										})
									]})
								]})
							]})
						]})
					]
				}));
			}

			// FIELD_NAME
			if (this.dlg.fieldId === 0)
			{
				tab.appendChild(this.dlg.elements.fieldNameWrapper = BX.create("TR", {
					"attrs": {"style": "display: none;"},
					children: [
						BX.create("TD", {"children": [BX.create(
							"LABEL", {
								html: this.getMessage('fieldNameSelectFieldTitle') + ':'
							}
						)]}),
						BX.create("TD", {"children": [this.dlg.elements.fieldName = BX.create('SELECT')]})
					]
				}));
				BX.Crm.rebuildSelect(
					this.dlg.elements.fieldName,
					this.settings["entityFieldsForSelect"],
					""
				);
			}
			else
			{
				tab.appendChild(this.dlg.elements.fieldNameWrapper = BX.create("TR", {
					children: [
						BX.create("TD", {"children": [BX.create(
							"LABEL", {
								html: this.getMessage('fieldNameFieldTitle') + ':'
							}
						)]}),
						BX.create("TD", {"children": [this.dlg.elements.fieldName = BX.create('SPAN', {
							html: BX.util.htmlspecialchars(this.dlg.fieldData.FIELD_ETITLE)
						})]})
					]
				}));
			}

			// FIELD_TYPE
			if (this.dlg.fieldId === 0)
			{
				tab.appendChild(this.dlg.elements.fieldTypeWrapper = BX.create("TR", {
					children: [
						BX.create("TD", {"children": [BX.create(
							"LABEL", {
								html: this.getMessage('fieldTypeFieldTitle') + ':'
							}
						)]}),
						BX.create("TD", {"children": [this.dlg.elements.fieldType = BX.create('SELECT')]})
					]
				}));
				defaultValue = "_new_string";
				selectElements = [
					{
						"id": "_new_string",
						"title": this.getMessage("newStringFieldTitle")
					},
					{
						"id": "_new_double",
						"title": this.getMessage("newDoubleFieldTitle")
					},
					{
						"id": "_new_boolean",
						"title": this.getMessage("newBooleanFieldTitle")
					},
					{
						"id": "_new_datetime",
						"title": this.getMessage("newDatetimeFieldTitle")
					}
				];
				BX.Crm.rebuildSelect(
					this.dlg.elements.fieldType,
					selectElements,
					defaultValue
				);
			}

			if (!this.dlg.fieldData.OPTIONS || !BX.prop.getBoolean(this.dlg.fieldData.OPTIONS, 'disableTitleEdit', false))
			{
				// FIELD_TITLE
				tab.appendChild(this.dlg.elements.fieldTitleWrapper = BX.create("TR", {
					children: [
						BX.create("TD", {
							"children": [BX.create(
								"LABEL", {
									html: this.getMessage('fieldTitleTitle') + ':'
								}
							)]
						}),
						BX.create("TD", {
							"children": [this.dlg.elements.fieldTitle = BX.create(
								'INPUT',
								{
									attrs: {
										"class": "bx-crm-edit-input"
									},
									props:
										{
											type: "text",
											value: this.dlg.fieldData.FIELD_TITLE
										}
								}
							)]
						})
					]
				}));
			}

			// SORT
			tab.appendChild(BX.create("TR", {
				children: [
					BX.create("TD", {"children": [BX.create(
						"LABEL", {
							html: this.getMessage('sortFieldTitle') + ':'
						}
					)]}),
					BX.create("TD", {"children": [this.dlg.elements.sort = BX.create(
						'INPUT',
						{
							attrs: {
								"class": "bx-crm-edit-input"
							},
							props:
							{
								type: "text",
								value: this.dlg.fieldData.SORT
							}
						}
					)]})
				]
			}));

			// IN_SHORT_LIST
			tab.appendChild(BX.create("TR", {
				attrs: {style: "display: none;"},
				children: [
					BX.create("TD", {"children": [BX.create(
						"LABEL", {
							html: this.getMessage('inShortListFieldTitle') + ':'
						}
					)]}),
					BX.create("TD", {"children": [this.dlg.elements.inShortList = BX.create(
						'INPUT',
						{
							props:
							{
								type: "checkbox",
								checked: (this.dlg.fieldData.IN_SHORT_LIST === "Y")
							}
						}
					)]})
				]
			}));

			return tab;
		},
		_hanleFieldEditDialogSave: function()
		{
			if (!this._form)
				return;

			var actionField = BX.findChild(this._form, {"tag": "input", attr: {"name": "action"}});
			var fieldIdField = BX.findChild(this._form, {"tag": "input", attr: {"name": "ID"}});
			var fieldNameField = BX.findChild(this._form, {"tag": "input", attr: {"name": "FIELD_NAME"}});
			var fieldTitleField = BX.findChild(this._form, {"tag": "input", attr: {"name": "FIELD_TITLE"}});
			var inShortListField = BX.findChild(this._form, {"tag": "input", attr: {"name": "IN_SHORT_LIST"}});
			var sortField = BX.findChild(this._form, {"tag": "input", attr: {"name": "SORT"}});

			var fieldNameSelect = this.dlg.elements.fieldName;
			var fieldTypeSelect = this.dlg.elements.fieldType;
			var fieldTitleInput = this.dlg.elements.fieldTitle;
			var inShortListCheckbox = this.dlg.elements.inShortList;
			var sortInput = this.dlg.elements.sort;
			var fieldName, fieldType, fieldTitle;

			if (this.dlg.fieldId === 0)
			{
				if (this.dlg.elements.createNew.checked)
					fieldName = fieldTypeSelect.value;
				else
					fieldName = fieldNameSelect.value;
				if (fieldName.length <= 0)
				{
					alert(this.getMessage(
						(this.dlg.elements.createNew.checked) ? 'emptyFieldTypeError' : 'emptyFieldNameError'
					));
					return;
				}
			}

			var hasTitle = !!fieldTitleInput;
			fieldTitle = hasTitle ? fieldTitleInput.value : '';
			if (fieldTitle.length > 255)
			{
				alert(this.getMessage('longFieldTitleError'));
				return;
			}

			if (this.dlg.fieldId === 0)
			{
				actionField.value = "ADD_FIELD";
				fieldIdField.value = 0;
				fieldNameField.value = fieldNameSelect.value;
				fieldTitleField.value = hasTitle ? fieldTitleInput.value : '';
				inShortListField.value = (inShortListCheckbox.checked ? "Y" : "N");
				sortField.value = sortInput.value;

				if (BX.type.isNotEmptyString(fieldName) && fieldName.substr(0, 5) === "_new_")
				{
					fieldType = fieldName.substr(5);

					if (!BX.type.isNotEmptyString(fieldTitle))
					{
						alert(this.getMessage('emptyNewFieldTitleError'));
						return;
					}

					switch (fieldType)
					{
						case "string":
						case "double":
						case "boolean":
						case "datetime":
							this._addUserField(
								{
									"type": fieldType,
									"title": fieldTitle
								},
								BX.delegate(this._continueHanleFieldEditDialogSave, this)
							);
							return;
					}
				}
			}
			else
			{
				actionField.value = "edit";
				fieldIdField.value = this.dlg.fieldId;
				fieldNameField.value = "";
				if (hasTitle)
				{
					fieldTitleField.value = this.dlg.fieldData.FIELD_TITLE = fieldTitleInput.value;
				}
				inShortListField.value = this.dlg.fieldData.IN_SHORT_LIST = (inShortListCheckbox.checked ? "Y" : "N");
				sortField.value = this.dlg.fieldData.SORT = sortInput.value;
			}

			this._continueHanleFieldEditDialogSave();
		},
		_continueHanleFieldEditDialogSave: function()
		{
			if (this._form)
			{
				var submitButton = BX(this._form.id + "_save");
				if (submitButton)
				{
					setTimeout(function () { submitButton.click(); }, 0);
					this.dlg.popup.close();
				}
			}
		},
		_hanleFieldEditDialogCancel: function()
		{
			this.dlg.popup.close();
		},
		_addUserField: function(fieldParams, callback)
		{
			var ufId = this.settings["userFieldEntityId"];
			var serviceUrl = this.settings["userFieldServiceUrl"];
			if(!BX.type.isNotEmptyString(ufId))
			{
				throw "Error: The 'userFieldEntityId' parameter is not defined in settings or empty.";
			}
			if(!BX.type.isNotEmptyString(serviceUrl))
			{
				throw "Error: Could not find 'userFieldServiceUrl' parameter in settings.";
			}

			var fieldData =
			{
				"USER_TYPE_ID": fieldParams["type"],
				"ENTITY_ID": ufId,
				"MULTIPLE": 'N',
				"MANDATORY": 'N',
				"SHOW_FILTER": 'Y',
				"EDIT_FORM_LABEL": fieldParams["title"]
			};

			this._pendingData =
			{
				fieldData: fieldData,
				callback: BX.type.isFunction(callback) ? callback : null
			};

			BX.ajax({
				url: serviceUrl,
				method: 'POST',
				dataType: 'json',
				data:
				{
					'ACTION' : 'ADD_FIELD',
					'DATA': this._pendingData["fieldData"]
				},
				onsuccess: BX.delegate(this._onCreateUserFieldRequestSuccess, this),
				onfailure: BX.delegate(this._onCreateUserFieldRequestFailure, this)
			});
		},
		_onCreateUserFieldRequestSuccess: function(data)
		{
			var error = BX.type.isNotEmptyString(data["ERROR"]) ? data["ERROR"] : "";
			if(error !== "")
			{
				alert(error);
				return;
			}

			var result = BX.type.isPlainObject(data['RESULT']) ? data['RESULT'] : {};
			var fieldData = this._pendingData["fieldData"];
			var fieldId = fieldData["ID"] = BX.type.isNotEmptyString(result["ID"]) ? result["ID"] : "";
			if(!BX.type.isNotEmptyString(fieldId))
			{
				throw "Error: Could not find 'ID' in action result.";
			}

			var fieldName = fieldData["FIELD_NAME"] = BX.type.isNotEmptyString(result["FIELD_NAME"]) ? result["FIELD_NAME"] : "";
			if(!BX.type.isNotEmptyString(fieldName))
			{
				throw "Error: Could not find 'FIELD_NAME' in action result.";
			}

			var fieldNameField = BX.findChild(this._form, {"tag": "input", attr: {"name": "FIELD_NAME"}});
			fieldNameField.value = fieldName;

			if(typeof(this._pendingData["callback"]) === "function")
				this._pendingData["callback"]();

			this._pendingData = null;
		},
		_onCreateUserFieldRequestFailure: function(data)
		{
			this._pendingData = null;
			alert("Could not create user field.");
		},
		_hanleCreateNewFlagSwitch: function()
		{
			if (this.dlg.elements.createNew.checked)
			{
				this.dlg.elements.fieldNameWrapper.style.display = "none";
				this.dlg.elements.fieldTypeWrapper.style.display =
					this.dlg.elements.fieldTitleWrapper.style.display = "";
			}
			else
			{
				this.dlg.elements.fieldNameWrapper.style.display = "";
				this.dlg.elements.fieldTypeWrapper.style.display =
					this.dlg.elements.fieldTitleWrapper.style.display = "none";
			}
		}
	};

	return PresetFieldListManagerClass;
})();
