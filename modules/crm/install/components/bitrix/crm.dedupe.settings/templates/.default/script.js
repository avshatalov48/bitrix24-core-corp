BX.namespace("BX.Crm");

if(typeof(BX.Crm.DedupeWizardConfigurationDialog) === "undefined")
{
	BX.Crm.DedupeWizardConfigurationDialog = function()
	{
		this._id = "";
		this._settings = {};
		this._config = {};
		this._scope = "";
		this._selectedTypeNames = [];
		this._typeInfos = {};
		this._scopeInfos = {};
		this._componentName = '';

		this._container = null;
		this._wrapper = null;
		this._scopeSelector = null;
		this._criterionList = null;
		this._criterionCheckBoxes = null;

		this._saveButtonClickHandler = this.onSaveButtonClick.bind(this);
	};

	BX.Crm.DedupeWizardConfigurationDialog.prototype =
		{
			initialize: function (id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};

				this._componentName =  BX.prop.getString(this._settings, "componentName", "");
				this._config = BX.prop.getObject(this._settings, "config");
				this._scope = BX.prop.getString(this._config, "scope", "");
				this._selectedTypeNames = BX.prop.getArray(this._config, "typeNames", []);

				this._typeInfos =  BX.prop.getObject(this._settings, "typeInfos");
				this._scopeInfos =  BX.prop.getObject(this._settings, "scopeInfos");

				var containerId = BX.prop.getString(this._settings, "container", "");
				if (containerId)
				{
					this._container = BX(containerId);
					this.layout();
				}
			},
			getMessage: function(name)
			{
				return BX.prop.getString(BX.Crm.DedupeWizardConfigurationDialog.messages, name, name);
			},
			selectAll: function(selectAll)
			{
				if(!this._criterionCheckBoxes)
				{
					return;
				}

				var checked = !!selectAll;
				for(var i = 0, length = this._criterionCheckBoxes.length; i < length; i++)
				{
					this._criterionCheckBoxes[i].checked = checked;
				}
			},
			layout: function()
			{
				var scopeInfos = this._scopeInfos;

				this._wrapper = BX.create("div");
				this._content = BX.create("div", { attrs: {className: "crm-dedupe-wizard-search-settings-content"}});
				this._wrapper.appendChild(this._content);

				var scopes = Object.keys(scopeInfos);
				if (scopes.length > 1 || scopes[0] !== '')
				{
					this._content.appendChild(BX.create("h4", {
						attrs: {className: "crm-dedupe-wizard-search-settings-subtitle"},
						text: this.getMessage("scopeCaption")
					}));

					var scopeSelectorOptions = [];

					for (var scopeKey in scopeInfos)
					{
						if (!scopeInfos.hasOwnProperty(scopeKey))
						{
							continue;
						}

						scopeSelectorOptions.push(
							BX.create("option",
								{
									attrs: {
										value: scopeKey !== "" ? scopeKey : "-",
										selected: this._scope === scopeKey
									},
									text: scopeInfos[scopeKey]
								}
							)
						);
					}

					this._scopeSelector = BX.create("select",
						{
							attrs: {className: "ui-ctl-element"},
							children: scopeSelectorOptions,
							events: {click: BX.delegate(this.onScopeChange, this)}
						}
					);

					this._content.appendChild(
						BX.create("div",
							{
								props: {className: "ui-ctl ui-ctl-after-icon ui-ctl-dropdown crm-dedupe-wizard-search-settings-input"},
								children:
									[
										BX.create("div", {
											props: {className: "ui-ctl-after ui-ctl-icon-angle"}
										}),
										this._scopeSelector
									]
							}
						)
					);
				}
				this._content.appendChild(BX.create("h4", {
					attrs: {className: "crm-dedupe-wizard-search-settings-subtitle"},
					text: this.getMessage("criterionCaption")
				}));
				this._content.appendChild(
					BX.create("div",
						{
							props: { className: "crm-dedupe-wizard-search-settings-control-box" },
							children:
								[
									BX.create("button",
										{
											props: { className: "crm-dedupe-wizard-search-settings-control" },
											text: this.getMessage("selectAll"),
											events : { click: BX.delegate(this.onSelectAllButtonClick, this) }
										}
									),
									BX.create("button",
										{
											props: { className: "crm-dedupe-wizard-search-settings-control" },
											text: this.getMessage("unselectAll"),
											events : { click: BX.delegate(this.onUnselectAllButtonClick, this) }
										}
									)
								]
						}
					)
				);

				this._criterionList = BX.create("ul", { attrs: { className: "crm-dedupe-wizard-search-settings-list" } });
				this._content.appendChild(this._criterionList);

				this._criterionCheckBoxes = [];
				this.adjustCriterionList();

				BX.bind(BX("ui-button-panel-save"), "click", this._saveButtonClickHandler);

				this._container.appendChild(this._wrapper);
			},
			adjustCriterionList: function()
			{
				while(this._criterionList.firstChild)
				{
					this._criterionList.removeChild(this._criterionList.firstChild);
				}
				this._criterionCheckBoxes = [];

				var typeInfos = this._typeInfos;
				for(var extendedId in typeInfos)
				{
					if(!typeInfos.hasOwnProperty(extendedId))
					{
						continue;
					}

					var typeInfo = typeInfos[extendedId];
					if(BX.prop.getString(typeInfo, "SCOPE", "") !== this._scope)
					{
						continue;
					}

					var typeName = BX.prop.getString(typeInfo, "NAME", "");
					var criterionCheckBox = BX.create("input",
						{ attrs: { type: "checkbox" }, props: { id: extendedId, name: typeName, className: "crm-dedupe-wizard-search-settings-checkbox" } }
					);

					if(this._selectedTypeNames.indexOf(typeName) >= 0)
					{
						criterionCheckBox.checked = true;
					}

					this._criterionCheckBoxes.push(criterionCheckBox);

					var labelClassName = "crm-dedupe-wizard-search-settings-list-item";

					if (typeName.substr(0,9) === "VOLATILE_")
					{
						labelClassName += " volatile";
					}

					this._criterionList.appendChild(
						BX.create("li",
							{
								props: { className: "crm-dedupe-wizard-search-settings-list-item" },
								children:
									[
										BX.create("label",
											{
												attrs: { for: extendedId },
												props: { className: labelClassName },
												children:
													[
														criterionCheckBox,
														document.createTextNode(
															BX.prop.getString(typeInfo, "DESCRIPTION", "[" + typeName + "]")
														)
													]
											}
										)
									]
							}
						)
					);
				}
			},
			saveConfig: function()
			{
				return BX.ajax.runComponentAction(
					this._componentName,
					"saveConfiguration",
					{ data: { guid: this._id, config: this._config } }
				);
			},
			close: function()
			{
				var slider = top.BX.SidePanel.Instance.getSliderByWindow(window);
				if(slider && slider.isOpen())
				{
					slider.close();
				}
			},
			onScopeChange: function()
			{
				var val = this._scopeSelector.value === "-" ? "" : this._scopeSelector.value;
				if(this._scope !== val)
				{
					this._scope = val;
					this.adjustCriterionList();
				}
			},
			onSelectAllButtonClick: function(e)
			{
				this.selectAll(true);
			},
			onUnselectAllButtonClick: function(e)
			{
				this.selectAll(false);
			},
			onSaveButtonClick: function(e)
			{
				this._selectedTypeNames = [];
				for(var i = 0, length = this._criterionCheckBoxes.length; i < length; i++)
				{
					var criterionCheckBox = this._criterionCheckBoxes[i];
					if(criterionCheckBox.checked)
					{
						this._selectedTypeNames.push(criterionCheckBox.name);
					}
				}

				var config = this._config;
				config["typeNames"] = this._selectedTypeNames;
				config["scope"] = this._scope;

				this.saveConfig().then(function() {
					BX.SidePanel.Instance.postMessage(window, 'crm::onMergerSettingsChange', {
						config: config
					});
					this.close();
				}.bind(this));
			}
		};
	if(typeof(BX.Crm.DedupeWizardConfigurationDialog.messages) === "undefined")
	{
		BX.Crm.DedupeWizardConfigurationDialog.messages = {};
	}
	BX.Crm.DedupeWizardConfigurationDialog.create = function(id, settings)
	{
		var self = new BX.Crm.DedupeWizardConfigurationDialog();
		self.initialize(id, settings);
		return self;
	};
}