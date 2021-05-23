BX.namespace("BX.Crm");

BX.Crm.PresetListManagerClass = (function ()
{

	var PresetListManagerClass = function (settings)
	{
		var i, presetNames, presetName;
		this.settings = settings ? settings : {};
		this.presetNames = [];
		this._afterGridInitHandler = BX.delegate(this._handleAfterGridInit, this);

		BX.addCustomEvent("BXInterfaceGridAfterInitTable", this._afterGridInitHandler);

		if (BX.type.isArray(this.settings["fixedPresetSelectItems"]))
		{
			presetNames = this.settings["fixedPresetSelectItems"];
			for (i = 0; i < presetNames.length; i++)
			{
				if (!presetNames[i]["type"] || presetNames[i]["type"] !== "group")
				{
					presetName = (presetNames[i]["id"] > 0) ? presetNames[i]["title"] : "";
					this.presetNames[presetNames[i]["id"]] = presetName;
				}
			}
		}
	};

	PresetListManagerClass.prototype = {
		getMessage: function(name)
		{
			return typeof(this.settings.messages[name]) !== 'undefined' ? this.settings.messages[name] : '';
		},
		addPreset: function ()
		{
			this.editPreset(0);
		},
		editPreset: function (presetId)
		{
			presetId = parseInt(presetId);
			if (presetId < 0)
				presetId = 0;

			if (this.dlg && this.dlg.popup)
				return;

			this.dlg = {
				popupId: this.settings['id'] + ((presetId === 0) ? '_PresetAdd' : '_PresetEdit'),
				popup: null,
				elements: {
					createNew: null,
					fixedPresetSelectWrapper: null,
					fixedPresetId: null,
					name: null,
					active: null,
					sort: null
				},
				handlers: {
					fixedPresetSelectChange: null
				},
				presetId: presetId,
				presetData: {
					"NAME": this.getMessage('defaultName'),
					"ACTIVE": this.getMessage('defaultActive'),
					"SORT": this.getMessage('defaultSort'),
					"COUNTRY_ID": this.getMessage("defaultCountryId")
				}
			};
			if (presetId > 0 && this.settings.presetData[presetId])
				this.dlg.presetData = this.settings.presetData[presetId];
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
					titleBar: this.getMessage(this.dlg.presetId > 0 ? 'presetEditDialogTitle' : 'presetAddDialogTitle'),
					events:
					{
						onPopupShow: function()
						{
						},
						onPopupClose: BX.delegate(
							function()
							{
								if (this.dlg.elements.fixedPresetId)
								{
									BX.unbind(
										this.dlg.elements.fixedPresetId,
										"change",
										this.dlg.handlers.fixedPresetSelectChange
									);
								}
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
					content: this._preparePresetEditDialogContent(),
					"buttons": [
						new BX.PopupWindowButton(
							{
								"text": (this.dlg.presetId === 0) ?
									this.getMessage("addBtnText") : this.getMessage("editBtnText"),
								"className": "popup-window-button-accept",
								"events":
								{
									"click": BX.delegate(this._hanlePresetEditDialogSave, this)
								}
							}
						),
						new BX.PopupWindowButtonLink(
							{
								"text": this.getMessage("cancelBtnText"),
								"className": "popup-window-button-link-cancel",
								"events":
								{
									"click": BX.delegate(this._hanlePresetEditDialogCancel, this)
								}
							}
						)
					]
				}
			);

			this.dlg.popup.show();
			BX.focus(this.dlg.elements.name);
		},
		_preparePresetEditDialogTitle: function()
		{
			return BX.create(
				'SPAN',
				{
					html: BX.util.htmlspecialchars(
						(this.dlg.presetId > 0) ?
							this.getMessage('presetEditDialogTitle') : this.getMessage('presetAddDialogTitle')
					),
					props: { className: 'bx-crm-popup-title' }
				}
			);
		},
		_preparePresetEditDialogContent: function()
		{
			//table
			var tab = BX.create(
				'TABLE',
				{
					"style": { "marginLeft": "12px", "marginTop": "12px", "marginRight": "12px", "marginBottom": "12px", "width": "470px" },
					"attrs": {"cellspacing": "7"}
				}
			);

			// CREATE_NEW
			if ((this.dlg.presetId <= 0))
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

			// FIXED_PRESET_ID
			if ((this.dlg.presetId <= 0))
			{
				this.dlg.elements.fixedPresetSelectWrapper = tab.appendChild(BX.create("TR", {
					"attrs": {"style": "display: none;"},
					children: [
						BX.create("TD", {"children": [BX.create(
							"LABEL", {
								html: this.getMessage('fixedPresetFieldTitle') + ':'
							}
						)]}),
						BX.create("TD", {"children": [this.dlg.elements.fixedPresetId = BX.create('SELECT')]})
					]
				}));
				BX.Crm.rebuildSelect(
					this.dlg.elements.fixedPresetId,
					this.settings["fixedPresetSelectItems"],
					0
				);
				this.dlg.handlers.fixedPresetSelectChange = BX.delegate(this._hanleChangePresetSelection, this);
				BX.bind(this.dlg.elements.fixedPresetId, "change", this.dlg.handlers.fixedPresetSelectChange);
			}

			// NAME
			tab.appendChild(BX.create("TR", {
				children: [
					BX.create("TD", {"children": [BX.create(
						"LABEL", {
							html: '<span class="required">*</span>' + this.getMessage('nameFieldTitle') + ':'
						}
					)]}),
					BX.create("TD", {"children": [this.dlg.elements.name = BX.create(
						'INPUT',
						{
							attrs: {
								"class": "bx-crm-edit-input"
							},
							props:
							{
								type: "text",
								value: this.dlg.presetData.NAME
							}
						}
					)]})
				]
			}));

			// ACTIVE
			tab.appendChild(BX.create("TR", {
				children: [
					BX.create("TD", {"children": [BX.create(
						"LABEL", {
							html: this.getMessage('activeFieldTitle') + ':'
						}
					)]}),
					BX.create("TD", {"children": [this.dlg.elements.active = BX.create(
						'INPUT',
						{
							props:
							{
								type: "checkbox",
								checked: this.dlg.presetData.ACTIVE === "Y"
							}
						}
					)]})
				]
			}));

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
								value: this.dlg.presetData.SORT
							}
						}
					)]})
				]
			}));

			return tab;
		},
		_hanlePresetEditDialogSave: function()
		{
			var form = BX(this.settings["formId"]);
			var actionField = BX.findChild(form, {"tag": "input", attr: {"name": "action"}});
			var idField = BX.findChild(form, {"tag": "input", attr: {"name": "ID"}});
			var createNewField = BX.findChild(form, {"tag": "input", attr: {"name": "CREATE_NEW"}});
			var fixedPresetIdField = BX.findChild(form, {"tag": "input", attr: {"name": "FIXED_PRESET_ID"}});
			var nameField = BX.findChild(form, {"tag": "input", attr: {"name": "NAME"}});
			var activeField = BX.findChild(form, {"tag": "input", attr: {"name": "ACTIVE"}});
			var sortField = BX.findChild(form, {"tag": "input", attr: {"name": "SORT"}});
			var countryIdField = BX.findChild(form, {"tag": "input", attr: {"name": "COUNTRY_ID"}});

			var createNewInput = this.dlg.elements.createNew;
			var fixedPresetIdInput = this.dlg.elements.fixedPresetId;
			var nameInput = this.dlg.elements.name;
			var activeInput = this.dlg.elements.active;
			var sortInput = this.dlg.elements.sort;

			if(form && actionField && nameField)
			{
				var name = nameInput.value;
				if(!BX.type.isNotEmptyString(name))
				{
					alert(this.getMessage('emptyNameError'));
					return;
				}
				else if (name.length > 255)
				{
					alert(this.getMessage('longNameError'));
					return;
				}

				/*var gridObject = null;
				if (this.settings["gridId"])
				{
					if (typeof(window["bxGrid_" + this.settings["gridId"]]) === "object")
						gridObject = window["bxGrid_" + this.settings["gridId"]];
				}

				if (this.settings.gridAjaxMode === "Y")
				{
					var params =
						"action=" + "ADD_PRESET" +
						"&NAME=" + BX.util.urlencode(name) +
						"&ACTIVE=" + (activeInput.checked ? "Y" : "N") +
						"&SORT=" + BX.util.urlencode(sortInput.value) +
						"&sessid=" + BX.bitrix_sessid();

					var url = this.settings["gridUrl"] + (this.settings["gridUrl"].indexOf('?') == -1? '?':'&') + params;

					gridObject.Reload(url);
					this.dlg.popup.close();
				}
				else
				{*/
					if (this.dlg.presetId === 0)
					{
						createNewField.value = (createNewInput.checked) ? "Y" : "N";
						fixedPresetIdField.value = fixedPresetIdInput.value;
						actionField.value = "ADD_PRESET";
						idField.value = 0;
						nameField.value = name;
						activeField.value = (activeInput.checked ? "Y" : "N");
						sortField.value = sortInput.value;
						countryIdField.value = this.dlg.presetData.COUNTRY_ID;
					}
					else
					{
						createNewField.value = "N";
						fixedPresetIdField.value = 0;
						actionField.value = "edit";
						idField.value = this.dlg.presetId;
						nameField.value = this.dlg.presetData.NAME = name;
						activeField.value = this.dlg.presetData.ACTIVE = (activeInput.checked ? "Y" : "N");
						sortField.value = this.dlg.presetData.SORT = sortInput.value;
						countryIdField.value = this.dlg.presetData.COUNTRY_ID;
					}

					BX.showWait();
					form.submit();
				/*}*/
			}
		},
		_hanlePresetEditDialogCancel: function()
		{
			this.dlg.popup.close();
		},
		_hanleCreateNewFlagSwitch: function()
		{
			if (this.dlg.elements.createNew.checked)
				this.dlg.elements.fixedPresetSelectWrapper.style.display = "none";
			else
				this.dlg.elements.fixedPresetSelectWrapper.style.display = "";
		},
		_hanleChangePresetSelection: function()
		{
			var presetId = parseInt(this.dlg.elements.fixedPresetId.value);
			if (presetId < 0 || isNaN(presetId))
				presetId = 0;
			if (BX.type.isString(this.presetNames[presetId]))
				this.dlg.elements.name.value = this.presetNames[presetId];
			else
				this.dlg.elements.name.value = "";
		},
		_handleAfterGridInit(params)
		{
			var eventParams = {}, i;
			if (BX.type.isPlainObject(params) && BX.type.isPlainObject(params["initEventParams"]))
			{
				eventParams = params["initEventParams"];
				if (BX.type.isNotEmptyString(eventParams["GRID_ID"])
					&& BX.type.isNotEmptyString(this.settings["gridId"])
					&& this.settings["gridId"] === eventParams["GRID_ID"])
				{
					if (BX.type.isNotEmptyString(eventParams["ERRORS_CONTAINER_ID"]))
					{
						var errorsContainer = BX(eventParams["ERRORS_CONTAINER_ID"]);
						if (BX.type.isElementNode(errorsContainer))
						{
							errorsContainer.style.display = "none";
							BX.cleanNode(errorsContainer);
						}
						if (BX.type.isArray(eventParams["ERRORS"]) && eventParams["ERRORS"].length > 0)
						{
							var showErrors = false;
							for (i = 0; i < eventParams["ERRORS"].length; i++)
							{
								if (BX.type.isNotEmptyString(eventParams["ERRORS"][i]))
								{
									errorsContainer.appendChild(
										BX.create('P', {
											"style": {"color": "red"},
											"html": BX.util.htmlspecialchars(eventParams["ERRORS"][i])
										})
									);
									showErrors = true;
								}
							}
							if (showErrors)
							{
								errorsContainer.style.display = "";
								errorsContainer.parentNode.scrollIntoView(true);
							}
						}
					}
				}
			}
		}
	};

	return PresetListManagerClass;
})();

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
				if (items[i]["type"] && items[i]["type"] === "group")
				{
					el = document.createElement("optgroup");
					el.label = items[i]['title'];
					bGroup = true;
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
