BX.namespace("BX.Crm");

if(typeof BX.Crm.EntityFieldAttributeType === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.EntityFieldAttributeType = BX.UI.EntityFieldAttributeType;
}

if(typeof BX.Crm.EntityFieldAttributePhaseGroupType === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.EntityFieldAttributePhaseGroupType = BX.UI.EntityFieldAttributePhaseGroupType;
}

if(typeof BX.Crm.EntityFieldAttributeManager === "undefined")
{
	BX.Crm.EntityFieldAttributeManager = function()
	{
		this._id = "";
		this._settings = null;

		this._entityTypeId = BX.CrmEntityType.enumeration.undefined;
		this._entityScope = "";
	};
	BX.Crm.EntityFieldAttributeManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._entityTypeId = BX.prop.getInteger(this._settings, "entityTypeId", BX.CrmEntityType.enumeration.undefined);
			this._entityScope = BX.prop.getString(this._settings, "entityScope", "");
		},
		isPermitted: function()
		{
			return BX.prop.getBoolean(this._settings, "isPermitted", true);
		},
		isPhaseDependent: function()
		{
			return BX.prop.getBoolean(this._settings, "isPhaseDependent", true);
		},
		getEntityPhases: function()
		{
			var entityPhases = BX.prop.getArray(this._settings, 'entityPhases', null);
			if(entityPhases)
			{
				return entityPhases;
			}

			var progressManager;

			if (typeof(BX.CrmProgressManager) !== "undefined"
				&& BX.CrmProgressManager.hasOwnProperty("current"))
			{
				progressManager = BX.CrmProgressManager.current.resolve(this._entityTypeId);
			}

			return progressManager ? progressManager.getInfos(this._entityScope) : [];
		},
		areMultiTypePhasesEnabled: function()
		{
			if (typeof(BX.CrmProgressManager) !== "undefined"
				&& BX.CrmProgressManager.hasOwnProperty("current"))
			{
				return BX.CrmProgressManager.current.isMultiType(this._entityTypeId);
			}

			return false;
		},
		createFieldConfigurator: function(field, typeId)
		{
			return BX.Crm.EntityFieldAttributeConfigurator.create(
				this._id,
				{
					typeId: typeId,
					phases: this.getEntityPhases(),
					areMultiTypePhasesEnabled: this.areMultiTypePhasesEnabled(),
					captions: BX.prop.getObject(this._settings, "captions", {}),
					config: field ? field.getAttributeConfiguration(typeId) : null,
					isPermitted: this.isPermitted(),
					isPhaseDependent: this.isPhaseDependent(),
					isAttrConfigButtonHidden: BX.prop.getBoolean(this._settings, "isAttrConfigButtonHidden", false),
					lockScript: BX.prop.getString(this._settings, "lockScript", "")
				}
			);
		},
		saveConfiguration: function(config, fieldName)
		{
			if(!this.isPermitted())
			{
				return;
			}

			BX.ajax.runAction("crm.api.fieldAttribute.saveConfiguration",
				{
					data:
						{
							config: config,
							fieldName: fieldName,
							entityTypeName: BX.CrmEntityType.resolveName(this._entityTypeId),
							entityScope: this._entityScope
						}
				}
			);
		},
		removeConfiguration: function(typeId, fieldName)
		{
			if(!this.isPermitted())
			{
				return;
			}

			BX.ajax.runAction("crm.api.fieldAttribute.removeConfiguration",
				{
					data:
						{
							type: typeId,
							fieldName: fieldName,
							entityTypeName: BX.CrmEntityType.resolveName(this._entityTypeId),
							entityScope: this._entityScope
						}
				}
			);
		}
	};
	BX.Crm.EntityFieldAttributeManager.create = function(id, settings)
	{
		var self = new BX.Crm.EntityFieldAttributeManager();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityFieldAttributeConfigurator === "undefined")
{
	BX.Crm.EntityFieldAttributeConfigurator = function()
	{
		this._id = "";
		this._settings = null;

		this._typeId = BX.Crm.EntityFieldAttributeType.undefined;
		this._fieldName = "";

		this._config = null;
		this._captions = null;

		this._phases = null; //example: [ { id: "NEW", name: "New", sort: 10, color: "#E7D35D" semantics: "process" } ]

		this._groups = null;
		this._button = null;
		this._wrapper = null;
		this._popup = null;

		this._label = null;
		this._switchCheckBox = null;
		this._switchCheckBoxHandler = BX.delegate(this.onSwitchCheckBoxClick, this);

		this._areMultiTypePhasesEnabled = false;

		this._isPermitted = true;
		this._isPhaseDependent = true;
		this._isEnabled = true;
		this._isOpened = false;
		this._isChanged = false;

		this._closingNotifier = BX.CrmNotifier.create(this);
	};

	BX.Crm.EntityFieldAttributeConfigurator.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._isPermitted = BX.prop.getBoolean(this._settings, "isPermitted", true);
			this._isPhaseDependent = BX.prop.getBoolean(this._settings, "isPhaseDependent", true);
			this._typeId = BX.prop.getInteger(this._settings, "type", BX.Crm.EntityFieldAttributeType.required);
			this._fieldName = BX.prop.getString(this._settings, "fieldName", "");

			this._captions = BX.prop.getObject(this._settings, "captions", {});

			this._config = BX.prop.getObject(this._settings, "config", {});

			var configTypeId = BX.prop.getInteger(this._config, "typeId", BX.Crm.EntityFieldAttributeType.undefined);
			if(configTypeId === BX.Crm.EntityFieldAttributeType.undefined)
			{
				this._config.typeId = this._typeId;
			}
			else if(configTypeId !== this._typeId)
			{
				throw "EntityFieldAttributeConfigurator. Configuration type mismatch.";
			}

			this._areMultiTypePhasesEnabled = BX.prop.getBoolean(this._settings, "areMultiTypePhasesEnabled", false);

			this._phases = BX.prop.getArray(this._settings, "phases", []);

			var pipelinePhases = [];
			var junkPhases = [];

			for(var i = 0, length = this._phases.length; i< length; i++)
			{
				var phase = this._phases[i];
				var semantics = phase["semantics"];
				if(semantics === "process" || semantics === "success")
				{
					pipelinePhases.push(phase);
				}
				else
				{
					junkPhases.push(phase);
				}
			}

			this._groups = {};

			this.createGroup(
				"general",
				"",
				[ { id: "GENERAL", name: this.getCaption("GROUP_TYPE_GENERAL") } ],
				{ phaseGroupTypeId: BX.Crm.EntityFieldAttributePhaseGroupType.general }
			);

			if(pipelinePhases.length > 0)
			{
				this.createGroup(
					"pipeline",
					this.getCaption("GROUP_TYPE_PIPELINE"),
					pipelinePhases,
					{ phaseGroupTypeId: BX.Crm.EntityFieldAttributePhaseGroupType.pipeline }
				);
			}

			if(junkPhases.length > 0)
			{
				this.createGroup(
					"junk",
					this.getCaption("GROUP_TYPE_JUNK"),
					junkPhases,
					{ phaseGroupTypeId: BX.Crm.EntityFieldAttributePhaseGroupType.junk }
				);
			}

			this._isEnabled = !this.isEmpty();
		},
		getId: function ()
		{
			return this._id;
		},
		getTypeId: function()
		{
			return this._typeId;
		},
		getCaption: function(name)
		{
			return BX.prop.getString(this._captions, name, name);
		},
		getTitle:function()
		{
			if(this._typeId === BX.Crm.EntityFieldAttributeType.required)
			{
				var isAttrConfigButtonHidden = BX.prop.getBoolean(this._settings, "isAttrConfigButtonHidden", false);
				return (this.getCaption("REQUIRED_FULL") + ((isAttrConfigButtonHidden) ? "" : ":"));
			}
			return "";
		},
		getPhaseIndexById: function(id)
		{
			for(var i = 0, length = this._phases.length; i < length; i++)
			{
				if(this._phases[i]["id"] === id)
				{
					return i;
				}
			}
			return -1;
		},
		getPhaseInfoById: function(id)
		{
			var i = this.getPhaseIndexById(id);
			return i >= 0 ? this._phases[i] : null;
		},
		resolvePhaseName: function(phaseId)
		{
			if(phaseId === "")
			{
				return "";
			}
			var phase = this.getPhaseInfoById(phaseId);
			return phase ? BX.prop.getString(phase, "name", phaseId) : phaseId;
		},
		resolvePhaseGroupType: function(phaseId)
		{
			if(phaseId === "")
			{
				return BX.Crm.EntityFieldAttributePhaseGroupType.undefined;
			}

			var phase = this.getPhaseInfoById(phaseId);
			if(!phase)
			{
				return BX.Crm.EntityFieldAttributePhaseGroupType.undefined;
			}

			var semantics = BX.prop.getString(phase, "semantics", "");
			return (semantics === "process" || semantics === "success"
				? BX.Crm.EntityFieldAttributePhaseGroupType.pipeline
				: BX.Crm.EntityFieldAttributePhaseGroupType.junk
			);
		},
		prepareLegendData: function()
		{
			var layout = BX.Crm.EntityPhaseLayout.getCurrent();
			var groups = BX.prop.getArray(this._config, "groups", []);
			for(var i = 0, length = groups.length; i < length; i++)
			{
				var group = groups[i];
				var items = BX.prop.getArray(group, "items", []);
				if(items.length > 0)
				{
					var phaseId = BX.prop.getString(items[0], "startPhaseId", "");
					var phase = this.getPhaseInfoById(phaseId);
					if(phase)
					{
						var backgroundColor = BX.prop.getString(phase, "color", "");
						if(backgroundColor === "")
						{
							var semantics = BX.prop.getString(phase, "semantics", "");
							if(semantics !== "")
							{
								backgroundColor = layout.getBackgroundColor(semantics);
							}
						}
						var color = BX.Crm.EntityFieldAttributeConfigurator.calculateTextColor(backgroundColor);
						return(
							{
								text: BX.prop.getString(phase, "name", phaseId),
								backgroundColor: backgroundColor,
								color: color
							}
						);
					}
				}
			}

			return({ text: this.getCaption("GROUP_TYPE_GENERAL"), backgroundColor: "#CED4DC", color: "#333" });
		},
		adjust: function()
		{
			var isEnabled = this.isEnabled();
			var isEmpty = this.isEmpty();
			if(isEnabled && isEmpty)
			{
				this._config = this.getDefaultConfiguration();
			}
			else if(!isEnabled && !isEmpty)
			{
				this._config = this.getEmptyConfiguration();
			}

			if(this._label)
			{
				this._label.innerHTML = BX.util.htmlspecialchars(this.getTitle());
			}

			if(this._button)
			{
				this._button.adjust();
			}
		},
		setEnabled: function(enabled)
		{
			this._isEnabled = !!enabled;
		},
		isEnabled: function()
		{
			return this._isEnabled;
		},
		isChanged: function()
		{
			return this._isChanged;
		},
		isEmpty: function()
		{
			return(BX.prop.getArray(this._config, "groups", []).length === 0);
		},
		isCustomized: function()
		{
			var groups = BX.prop.getArray(this._config, "groups", []);
			for(var i = 0, length = groups.length; i < length; i++)
			{
				var phaseGroupTypeId = BX.prop.getInteger(
					groups[i],
					"phaseGroupTypeId",
					BX.Crm.EntityFieldAttributePhaseGroupType.undefined
				);

				if(phaseGroupTypeId === BX.Crm.EntityFieldAttributePhaseGroupType.undefined)
				{
					continue;
				}

				if(phaseGroupTypeId !== BX.Crm.EntityFieldAttributePhaseGroupType.general)
				{
					return true;
				}
			}
			return false;
		},
		isPermitted: function()
		{
			return this._isPermitted;
		},
		isPhaseDependent: function()
		{
			return this._isPhaseDependent;
		},
		runLockScript: function()
		{
			var lockScript = BX.prop.getString(this._settings, "lockScript", "");
			if(lockScript !== "")
			{
				eval(lockScript);
			}
		},
		getDefaultConfiguration: function()
		{
			return(
				{
					typeId: this._typeId,
					groups: [ { phaseGroupTypeId: BX.Crm.EntityFieldAttributePhaseGroupType.general } ]
				}
			);
		},
		getEmptyConfiguration: function()
		{
			return({ typeId: this._typeId, groups: [] });
		},
		getConfiguration: function()
		{
			return this._config;
		},
		acceptChanges: function()
		{
			if(!(this.isPermitted() && this.isChanged()))
			{
				return;
			}

			this._config = this.getEmptyConfiguration();
			if(this._groups["general"].isSelected()
				|| (this._groups["pipeline"].isFullySelected()
					&& !this._groups["junk"].isSelected()
					&& !this._areMultiTypePhasesEnabled)
			)
			{
				this._config["groups"].push({ phaseGroupTypeId: BX.Crm.EntityFieldAttributePhaseGroupType.general });
			}
			else
			{
				this._groups["pipeline"].acceptChanges(this._config);
				this._groups["junk"].acceptChanges(this._config);
			}

			this._isChanged = false;
		},
		applyConfiguration: function()
		{
			for(var key in this._groups)
			{
				if(!this._groups.hasOwnProperty(key))
				{
					continue;
				}

				this._groups[key].applyConfiguration(this._config);
			}
		},
		createGroup: function(id, title, phases, options)
		{
			if(!this._groups)
			{
				this._groups = {};
			}

			this._groups[id] = BX.Crm.EntityFieldAttributePhaseGroup.create(id,
				{
					title: title,
					configurator: this,
					isReadOnly: !this.isPhaseDependent(),
					phases: phases,
					phaseGroupTypeId: BX.prop.getInteger(
						options,
						"phaseGroupTypeId",
						BX.Crm.EntityFieldAttributePhaseGroupType.undefined
					)
				}
			);
			return this._groups[id];
		},
		hasSelectedGroup: function()
		{
			for(var key in this._groups)
			{
				if(!this._groups.hasOwnProperty(key))
				{
					continue;
				}

				if(this._groups[key].isSelected())
				{
					return true;
				}
			}
			return false;
		},
		processGroupChange: function(group)
		{
			if(!this.isPhaseDependent())
			{
				this.runLockScript();
				return;
			}

			var groupId = group.getId();
			if(groupId === "general")
			{
				if(group.isSelected())
				{
					this._groups["pipeline"].setSelected(true);
					this._groups["junk"].setSelected(false);
				}
				else if(!this._areMultiTypePhasesEnabled)
				{
					this._groups["pipeline"].setSelected(false);
				}
			}
			else if(groupId === "pipeline")
			{
				if(!this._areMultiTypePhasesEnabled)
				{
					this._groups["general"].setSelected(
						group.isFullySelected() && !this._groups["junk"].isSelected()
					);
				}
				else if(!group.isFullySelected())
				{
					this._groups["general"].setSelected(false);
				}
			}
			else if(groupId === "junk")
			{
				if(group.isSelected() && this._groups["general"].isSelected())
				{
					this._groups["general"].setSelected(false);
				}
				else if(!group.isSelected() && this._groups["pipeline"].isFullySelected() && !this._areMultiTypePhasesEnabled)
				{
					this._groups["general"].setSelected(true);
				}
			}

			this.setEnabled(this.hasSelectedGroup());

			if(!this.isChanged())
			{
				this._isChanged = true;
			}
		},
		addClosingListener: function(listener)
		{
			this._closingNotifier.addListener(listener);
		},
		removeClosingListener: function(listener)
		{
			this._closingNotifier.removeListener(listener);
		},
		open: function(anchor)
		{
			if(this._isOpened)
			{
				return;
			}

			this._popup = new BX.PopupWindow(
				this._id,
				anchor,
				{
					autoHide: true,
					draggable: false,
					closeByEsc: true,
					offsetLeft: 0,
					offsetTop: 0,
					zIndex: BX.prop.getInteger(this._settings, "zIndex", 0),
					bindOptions: { forceBindPosition: true },
					content: this.prepareContent(),
					events:
						{
							onPopupShow: BX.delegate(this.onPopupShow, this),
							onPopupClose: BX.delegate(this.onPopupClose, this),
							onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
						}
				}
			);
			this._popup.show();
		},
		close: function()
		{
			if(!this._isOpened)
			{
				return;
			}

			if(this._popup)
			{
				this._popup.close();
			}
		},
		prepareContent: function()
		{
			this._wrapper = BX.create("div", { props: { className: "crm-entity-popup-field-addiction-step" } });

			var innerWrapper = BX.create("div", { props: { className: "crm-entity-popup-field-addiction-steps-list-container" } });
			this._wrapper.appendChild(innerWrapper);

			for(var key in this._groups)
			{
				if(!this._groups.hasOwnProperty(key))
				{
					continue;
				}

				var group = this._groups[key];
				group.setContainer(innerWrapper);
				group.layout();
			}

			return this._wrapper;
		},
		onPopupShow: function()
		{
			this._isOpened = true;
			this.applyConfiguration();
		},
		onPopupClose: function()
		{
			if(this.isPermitted())
			{
				this.acceptChanges();

				if(this._switchCheckBox)
				{
					this._switchCheckBox.checked = this._isEnabled;
				}

				this.adjust();
			}

			if(this._popup)
			{
				this._popup.destroy();
			}

			this._closingNotifier.notify([ { config: this._config } ]);
		},
		onPopupDestroy: function()
		{
			this._isOpened = false;

			this._wrapper = null;
			this._innerWrapper = null;

			this._popup = null;
		},
		getButton: function()
		{
			if(!this._button)
			{
				this._button = BX.Crm.EntityFieldAttributeConfigButton.create(
					this._id,
					{
						configurator: this,
						isHidden: BX.prop.getBoolean(this._settings, "isAttrConfigButtonHidden", false)
					}
				);
			}
			return this._button;
		},
		getSwitchCheckBox: function ()
		{
			return this._switchCheckBox;
		},
		setSwitchCheckBox: function(checkBox)
		{
			if(this._switchCheckBox)
			{
				BX.unbind(this._switchCheckBox, "click", this._switchCheckBoxHandler);
			}

			this._switchCheckBox = checkBox;

			if(this._switchCheckBox)
			{
				BX.bind(this._switchCheckBox, "click", this._switchCheckBoxHandler);
			}
		},
		getLabel: function()
		{
			return this._label;
		},
		setLabel: function(label)
		{
			this._label = label;
		},
		onSwitchCheckBoxClick: function(e)
		{
			this.setEnabled(this._switchCheckBox.checked);
			this.adjust();
		}
	};
	BX.Crm.EntityFieldAttributeConfigurator.calculateTextColor = function(baseColor)
	{
		var r, g, b;
		if ( baseColor > 7 )
		{
			var hexComponent = baseColor.split("(")[1].split(")")[0];
			hexComponent = hexComponent.split(",");
			r = parseInt(hexComponent[0]);
			g = parseInt(hexComponent[1]);
			b = parseInt(hexComponent[2]);
		}
		else
		{
			if(/^#([A-Fa-f0-9]{3}){1,2}$/.test(baseColor))
			{
				var c = baseColor.substring(1).split('');
				if(c.length === 3)
				{
					c= [c[0], c[0], c[1], c[1], c[2], c[2]];
				}
				c = '0x'+c.join('');
				r = ( c >> 16 ) & 255;
				g = ( c >> 8 ) & 255;
				b =  c & 255;
			}
		}

		var y = 0.21 * r + 0.72 * g + 0.07 * b;
		return ( y < 145 ) ? "#fff" : "#333";
	};
	BX.Crm.EntityFieldAttributeConfigurator.create = function(id, settings)
	{
		var self = new BX.Crm.EntityFieldAttributeConfigurator();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityFieldAttributePhaseGroup === "undefined")
{
	BX.Crm.EntityFieldAttributePhaseGroup = function()
	{
		this._id = "";
		this._settings = null;
		this._phases = null;
		this._phaseGroupTypeId = BX.Crm.EntityFieldAttributePhaseGroupType.undefined;
		this._title = "";

		this._isReadOnly = false;
		this._configurator = null;

		this._container = null;
		this._phaseCheckBoxes = null;
	};

	BX.Crm.EntityFieldAttributePhaseGroup.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._phases = BX.prop.getArray(this._settings, "phases", []);
			this._phaseGroupTypeId = BX.prop.getInteger(
				this._settings,
				"phaseGroupTypeId",
				BX.Crm.EntityFieldAttributePhaseGroupType.undefined
			);

			this._isReadOnly = BX.prop.getBoolean(this._settings, "isReadOnly", false);
			this._title = BX.prop.getString(this._settings, "title", this._id);
			this._configurator = BX.prop.get(this._settings, "configurator", null);
			this._container = BX.prop.getElementNode(this._settings, "container", null);
		},
		getId: function ()
		{
			return this._id;
		},
		getPhaseGroupTypeId: function()
		{
			return this._phaseGroupTypeId;
		},
		isReadOnly: function()
		{
			return this._isReadOnly;
		},
		isSelected: function()
		{
			for(var i = 0, length = this._phaseCheckBoxes.length; i < length; i++)
			{
				if(this._phaseCheckBoxes[i].checked)
				{
					return true;
				}
			}
			return false;
		},
		isFullySelected: function()
		{
			for(var i = 0, length = this._phaseCheckBoxes.length; i < length; i++)
			{
				if(!this._phaseCheckBoxes[i].checked)
				{
					return false;
				}
			}
			return true;
		},
		setSelected: function(selected)
		{
			selected = !!selected;
			for(var i = 0, length = this._phaseCheckBoxes.length; i < length; i++)
			{
				this._phaseCheckBoxes[i].checked = selected;
			}
		},
		applyConfiguration: function(config)
		{
			this.setSelected(false);

			var groupConfigs = BX.prop.getArray(config, "groups", []);
			for(var i = 0, groupQty = groupConfigs.length; i < groupQty; i++)
			{
				var groupConfig = groupConfigs[i];
				var items = BX.prop.getArray(groupConfig, "items", []);
				var phaseGroupTypeId = BX.prop.getInteger(
					groupConfig,
					"phaseGroupTypeId",
					BX.Crm.EntityFieldAttributePhaseGroupType.undefined
				);

				if(phaseGroupTypeId === BX.Crm.EntityFieldAttributePhaseGroupType.undefined && items.length > 0)
				{
					phaseGroupTypeId = this._configurator.resolvePhaseGroupType(
						BX.prop.getString(items[0], "startPhaseId", "")
					);
				}

				if(phaseGroupTypeId === BX.Crm.EntityFieldAttributePhaseGroupType.general)
				{
					if(this._phaseGroupTypeId !== BX.Crm.EntityFieldAttributePhaseGroupType.junk)
					{
						this.setSelected(true);
					}
				}
				else if(phaseGroupTypeId === this._phaseGroupTypeId && items.length > 0)
				{
					var j;
					if(this._phaseGroupTypeId === BX.Crm.EntityFieldAttributePhaseGroupType.pipeline)
					{
						var phaseIds = [];
						for(j = 0, itemQty = items.length; j < itemQty; j++)
						{
							var startPhaseId = BX.prop.getString(items[j], "startPhaseId", "");
							var finishPhaseId = BX.prop.getString(items[j], "finishPhaseId", "");
							if (
								BX.Type.isStringFilled(startPhaseId)
								&& BX.Type.isStringFilled(finishPhaseId)
							)
							{
								var startPhaseFound = false;
								var finishPhaseFound = false;
								var itemPhaseIds = [];
								var k;
								for (k = 0; k < this._phases.length; k++)
								{
									if (!startPhaseFound && this._phases[k]["id"] === startPhaseId)
									{
										startPhaseFound = true;
									}
									if (startPhaseFound && !finishPhaseFound)
									{
										itemPhaseIds.push(this._phases[k]["id"]);
										if (this._phases[k]["id"] === finishPhaseId)
										{
											finishPhaseFound = true;
										}
									}
								}
								if (startPhaseFound && finishPhaseFound)
								{
									for (k = 0; k < itemPhaseIds.length; k++)
									{
										if (phaseIds.indexOf(itemPhaseIds[k]) < 0)
										{
											phaseIds.push(itemPhaseIds[k]);
										}
									}
								}
							}
						}
						for (k = 0; k < phaseIds.length; k++)
						{
							this.selectPhaseCheckBox(phaseIds[k]);
						}
					}
					else if(this._phaseGroupTypeId === BX.Crm.EntityFieldAttributePhaseGroupType.junk)
					{
						for(j = 0, itemQty = items.length; j < itemQty; j++)
						{
							this.selectPhaseCheckBox(BX.prop.getString(items[j], "startPhaseId", ""));
						}
					}
				}
			}
		},
		acceptChanges: function(config)
		{
			var i, length, phaseCheckBox;

			var items = [];
			if(this._phaseGroupTypeId === BX.Crm.EntityFieldAttributePhaseGroupType.pipeline)
			{
				var curState;
				var nextState;
				var startPhaseDetected = false;
				var startPhaseId = "";
				var finishPhaseId = "";
				var lastIndex = this._phaseCheckBoxes.length - 1;
				for(i = 0; i <= lastIndex; i++)
				{
					curState = this._phaseCheckBoxes[i].checked;
					nextState = (i === lastIndex) ? false : this._phaseCheckBoxes[i + 1].checked;
					if (!startPhaseDetected && curState)
					{
						startPhaseDetected = true;
						startPhaseId = this._phaseCheckBoxes[i]["id"];
					}
					if (startPhaseDetected && !nextState)
					{
						startPhaseDetected = false;
						finishPhaseId = this._phaseCheckBoxes[i]["id"];
						items.push({startPhaseId: startPhaseId, finishPhaseId: finishPhaseId});
					}
				}
			}
			else if(this._phaseGroupTypeId === BX.Crm.EntityFieldAttributePhaseGroupType.junk)
			{
				for(i = 0, length = this._phaseCheckBoxes.length; i < length; i++)
				{
					phaseCheckBox = this._phaseCheckBoxes[i];
					if(!phaseCheckBox.checked)
					{
						continue;
					}

					items.push(
						{
							startPhaseId: phaseCheckBox["id"],
							finishPhaseId: phaseCheckBox["id"]
						}
					);
				}
			}

			if(items.length > 0)
			{
				config["groups"].push({ phaseGroupTypeId: this._phaseGroupTypeId, items: items });
			}
		},
		getContainer: function()
		{
			return this._container;
		},
		setContainer: function(container)
		{
			this._container = container;
		},
		createPhaseCheckBox: function(phaseId)
		{
			if(!this._phaseCheckBoxes)
			{
				this._phaseCheckBoxes = [];
			}

			var checkBox = BX.create("input",
				{
					props: { id: phaseId, type: "checkbox" },
					events: { click: BX.delegate(this.onCheckBoxClick, this) }
				}
			);

			this._phaseCheckBoxes.push(checkBox);
			return checkBox;
		},
		findCheckBox: function(phaseId)
		{
			for(var i = 0, length = this._phaseCheckBoxes.length; i < length; i++)
			{
				var cb = this._phaseCheckBoxes[i];
				if(cb["id"] === phaseId)
				{
					return cb;
				}
			}
			return null;
		},
		selectPhaseCheckBox: function(phaseId)
		{
			var cb = this.findCheckBox(phaseId);
			if(!cb)
			{
				return;
			}

			cb.checked = true;
			this.processCheckBoxChange(cb, false);
		},
		layout: function()
		{
			if(!this._container)
			{
				return;
			}

			this._phaseCheckBoxes = [];

			if(this._title !== "")
			{
				this._container.appendChild(
					BX.create("div",
						{
							props: { className: "crm-entity-popup-field-addiction-steps-list-title" },
							children:
								[
									BX.create("div", { props: { className: "crm-entity-popup-field-addiction-steps-list-title-line" } }),
									BX.create("div",
										{
											props: { className: "crm-entity-popup-field-addiction-steps-list-title-text" },
											text: this._title
										}
									),
									BX.create("div", { props: { className: "crm-entity-popup-field-addiction-steps-list-title-line" } })
								]
						}
					)
				);
			}

			var layout = BX.Crm.EntityPhaseLayout.getCurrent();
			var phaseContainer = BX.create("div", { props: { className: "crm-entity-popup-field-addiction-steps-list" } });
			for(var i = 0, length = this._phases.length; i < length; i++)
			{
				var phase = this._phases[i];

				var label = BX.create("label",
					{
						props: { className: "crm-entity-popup-field-addiction-step-item" },
						children:
							[
								this.createPhaseCheckBox(phase["id"]),
								BX.create(
									"span",
									{
										props: { className: "crm-entity-popup-field-addiction-step-item-text" },
										text: phase["name"]
									}
								)
							]
					}
				);

				var backgroundColor = BX.prop.getString(phase, "color", "");
				if(backgroundColor === "")
				{
					var semantics = BX.prop.getString(phase, "semantics", "");
					if(semantics !== "")
					{
						backgroundColor = layout.getBackgroundColor(semantics);
					}
				}

				if(backgroundColor !== "")
				{
					label.style.backgroundColor = backgroundColor;
					label.style.color = layout.calculateTextColor(backgroundColor);
				}
				else
				{
					BX.addClass(label, "crm-entity-popup-field-addiction-step-item-default");
				}

				phaseContainer.appendChild(label);
			}
			this._container.appendChild(phaseContainer);
		},
		onCheckBoxClick: function(e)
		{
			this.processCheckBoxChange(BX.getEventTarget(e), true);
		},
		processCheckBoxChange: function(checkbox, notify)
		{
			if(this._isReadOnly)
			{
				checkbox.checked = !checkbox.checked;
			}

			if(notify)
			{
				this._configurator.processGroupChange(this);
			}
		}
	};
	BX.Crm.EntityFieldAttributePhaseGroup.create = function(id, settings)
	{
		var self = new BX.Crm.EntityFieldAttributePhaseGroup();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityFieldAttributeConfigButton === "undefined")
{
	BX.Crm.EntityFieldAttributeConfigButton = function()
	{
		this._id = "";
		this._settings = null;

		this._configurator = null;
		this._wrapper = null;
		this._icon = null;
	};

	BX.Crm.EntityFieldAttributeConfigButton.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._configurator = BX.prop.get(this._settings, "configurator", null);
		},
		isHidden: function()
		{
			return BX.prop.getBoolean(this._settings, "isHidden", false);
		},
		onClick: function(e)
		{
			if(this._configurator.isEnabled())
			{
				this._configurator.open(this._wrapper);
			}
		},
		adjust: function()
		{
			var layoutData = this._configurator.prepareLegendData();

			if(this._configurator.isEnabled())
			{
				BX.removeClass(this._wrapper, "crm-entity-new-field-addiction-step-disabled");
			}
			else
			{
				BX.addClass(this._wrapper, "crm-entity-new-field-addiction-step-disabled");
			}

			this._wrapper.style.backgroundColor = BX.prop.getString(layoutData, "backgroundColor", "#CED4DC");
			this._wrapper.style.color = BX.prop.getString(layoutData, "color", "#333");

			var arrow = this._wrapper.querySelector("span.crm-entity-new-field-addiction-step-arrow");
			if(arrow)
			{
				arrow.style.color = BX.prop.getString(layoutData, "color", "#333");
			}

			var label = this._wrapper.querySelector("span.crm-entity-new-field-addiction-step-name");
			if(label)
			{
				label.innerHTML = BX.util.htmlspecialchars(BX.prop.getString(layoutData, "text", "..."));
			}
		},
		prepareLayout: function()
		{
			var layoutData = this._configurator.prepareLegendData();

			this._wrapper = BX.create("span",
				{
					props: { className: "crm-entity-new-field-addiction-step" },
					style:
						{
							backgroundColor: BX.prop.getString(layoutData, "backgroundColor", "#CED4DC"),
							color: BX.prop.getString(layoutData, "color", "#333")
						},
					children:
						[
							BX.create("span",
								{
									props: { className: "crm-entity-new-field-addiction-step-name" },
									text: BX.prop.getString(layoutData, "text", "")
								}
							),
							BX.create("span",
								{
									props: { className: "crm-entity-new-field-addiction-step-arrow" },
									children:
										[
											BX.create("span",
												{
													props: { className: "crm-entity-new-field-addiction-step-arrow-inner" }
												}
											)
										]
								}
							)
						]
				}
			);

			if (this.isHidden())
			{
				this._wrapper.style.display = "none";
			}

			BX.bind(this._wrapper, "click", BX.delegate(this.onClick, this));
			return [ this._wrapper ];
		}
	};

	BX.Crm.EntityFieldAttributeConfigButton.create = function(id, settings)
	{
		var self = new BX.Crm.EntityFieldAttributeConfigButton();
		self.initialize(id, settings);
		return self;
	}
}

if (typeof BX.Crm.EntityFieldVisibilityConfigurator === "undefined")
{
	BX.Crm.EntityFieldVisibilityConfigurator = function()
	{
		this._id = "";
		this._settings = null;

		this._config = null;
		this._button = null;
		this._wrapper = null;

		this._label = null;
		this._switchCheckBox = null;
		this._switchCheckBoxHandler = BX.delegate(this.onSwitchCheckBoxClick, this);
		this._onSquareClick = BX.delegate(this.onSquareClick, this);

		this._isEnabled = false;
		this._isChanged = false;
		this._isPermitted = true;

		this._items = [];
		this._restriction = {};
	};

	BX.Crm.EntityFieldVisibilityConfigurator.prototype =
	{
		initialize: function (id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._config = BX.prop.getObject(this.getSettings(), "config", {});
			this._editor = BX.prop.get(this.getSettings(), "editor", {});
			this._field = BX.prop.get(this.getSettings(), "field", null);

			this._restriction = BX.prop.getObject(this.getSettings(), "restriction", {});
			this._isPermitted = BX.prop.getBoolean(this._restriction, "isPermitted", true);

			this._squares = [];
			this._isEnabled = !this.isEmpty();

			this.initializeItems();
			//BX.addCustomEvent(this._settings.userFieldConfigurator, "onSave", BX.delegate(this.onUserFieldConfigurationSave, this));
		},
		initializeItems: function()
		{
			this._items = [];
			var accessCodes = BX.prop.getObject(this._config, "accessCodes", []);

			for (var key in accessCodes) {
				this.addItem(this.createUserInfo(accessCodes[key]));
			}
		},
		addItem: function (data)
		{
			if (this._items === null)
			{
				this._items = [];
			}

			this._items.push(data);

			return data;
		},
		createUserInfo: function(item)
		{
			return {
				ID: item.id,
				FORMATTED_NAME: BX.util.htmlspecialcharsback(BX.prop.getString(item, "name", "")),
			};
		},
		onUserFieldConfigurationSave: function(fieldName, entityTypeId)
		{
			var field = {};
			fieldName = fieldName || null;

			if (
				this._isChanged
				&&
				(
					fieldName
					||
					(fieldName = this.getSettings().field.getId())
				)
			)
			{
				var visibilityConfig = (this._items || []);

				if (!this.isEnabled())
				{
					visibilityConfig = [];
					this.removeItems();
				}

				this.saveConfiguration(visibilityConfig, fieldName, entityTypeId);
			}
		},
		getTitle:function()
		{
			return BX.Crm.EntityFieldVisibilityConfigurator.messages['titleField'];
		},
		saveConfiguration:function(config, fieldName, entityTypeId)
		{
			entityTypeId = entityTypeId || this.getSettings().editor.getEntityTypeId();
			this._isChanged = BX.ajax.runAction("crm.api.userFieldVisibility.saveConfiguration",
				{
					data:
						{
							accessCodes: (config.length ? config : false),
							fieldName: fieldName,
							entityTypeId: entityTypeId
						}
				}
			).then(function (event, data){
				return true;
			});
		},
		formatAccessCodesFromConfig: function(config)
		{
			var accessCodes = {};
			config.forEach(function(item, i, arr) {
				var obj = {
					id: item.ID,
					name: item.FORMATTED_NAME
				};
				accessCodes[item.ID] = obj;
			});
			return accessCodes;
		},
		getId: function()
		{
			return this._id;
		},
		adjust: function()
		{
			this.removeSquares();
			this._items.forEach(function (currentUser, index, array) {
				this.setSquare(currentUser.FORMATTED_NAME, currentUser.ID);
			}, this);
			this.getButton().adjust();
		},
		getSquares: function()
		{
			return BX.Filter.Utils.getByClass(this.getButton().getSelectUserControl(), 'main-ui-square', true);
		},
		removeSquares: function()
		{
			this.getSquares().forEach(BX.remove);
		},
		setSquare: function(label, value)
		{
			var square = BX.decl(this.createSquareData(label, value));
			square.setAttribute('data-user-id', value);

			BX.bind(square, "click", BX.delegate(this._onSquareClick, this));

			var squares = this.getSquares();
			if(!squares.length)
			{
				BX.prepend(square, this.getButton().getSelectUserControl());
			}
			else
			{
				BX.insertAfter(square, squares[squares.length - 1]);
			}
		},
		createSquareData: function(label, value)
		{
			return {
				block: 'main-ui-square',
				name: label,
				item: {
					'_label': label,
					'_value': value
				},
			};
		},
		onSquareClick: function(e)
		{
			if (!this._isPermitted)
			{
				this.runLockScript();
			}
			else
			{
				var square = e.target.parentElement;
				var userId = square.getAttribute('data-user-id');
				this._isChanged = true;
				this.removeItem(userId);
				this.adjust();
			}
		},
		setEnabled: function (enabled)
		{
			this._isEnabled = !!enabled;
		},
		isEnabled: function()
		{
			return this._isEnabled;
		},
		isEmpty: function()
		{
			return (Object.keys(BX.prop.getObject(this.getConfiguration(), "accessCodes", {})).length === 0);
		},
		getConfiguration: function()
		{
			return this._config;
		},
		getSettings: function()
		{
			return this._settings;
		},
		open: function (anchor)
		{
			if (!this._isPermitted)
			{
				this.runLockScript();
			}
			else
			{
				this.getUserSelector().open(anchor);
			}
		},
		close: function()
		{
			if (this.getUserSelector())
			{
				this.getUserSelector().close();
			}
		},
		prepareContent: function()
		{
			this._wrapper = BX.create("div", {props: {className: "crm-entity-popup-field-addiction-step"}});
			var innerWrapper = BX.create("div", {props: {className: "crm-entity-popup-field-addiction-steps-list-container"}});
			this._wrapper.appendChild(innerWrapper);
			return this._wrapper;
		},
		getButton: function()
		{
			if (!this._button)
			{
				this._button = BX.Crm.EntityFieldVisibilityConfigButton.create(this._id, {configurator: this});
			}
			return this._button;
		},
		getSwitchCheckBox: function()
		{
			return this._switchCheckBox;
		},
		setSwitchCheckBox: function (checkBox)
		{
			if (this._switchCheckBox)
			{
				BX.unbind(this._switchCheckBox, "click", this._switchCheckBoxHandler);
			}
			this._switchCheckBox = checkBox;
			if (this._switchCheckBox)
			{
				BX.bind(this._switchCheckBox, "click", this._switchCheckBoxHandler);
			}
		},
		onSwitchCheckBoxClick: function (e)
		{
			if (!this._isPermitted)
			{
				this.runLockScript();
				this._switchCheckBox.checked = this._isEnabled;
			}
			else
			{
				this._isChanged = true;
				this.setEnabled(this._switchCheckBox.checked);

				if (!this._switchCheckBox.checked)
				{
					this._items = [];
				}

				this.adjust();
			}
		},
		runLockScript: function()
		{
			var lockScript = BX.prop.getString(this._restriction, "restrictionCallback", "");
			if(lockScript !== "")
			{
				eval(lockScript);
			}
		},
		getUserSelector: function()
		{
			if (!this._userSelector)
			{
				this._userSelector = BX.UI.EntityEditorUserSelector.create(
					this._id,
					{
						callback: BX.delegate(this.processItemSelect, this),
					}
				);
			}
			return this._userSelector;
		},
		processItemSelect: function (selector, item)
		{
			this._isChanged = true;

			var userId = BX.prop.getString(item, "id", '');
			if (this.findItemIndexById(userId) >= 0)
			{
				this.removeItem(userId);
				this.adjust();
				return;
			}

			this.addItem(this.createUserInfo(item));
			this.adjust();
		},
		findItemIndexById: function (id)
		{
			for (var i = 0, length = this._items.length; i < length; i++)
			{
				if (this._items[i].ID === id)
				{
					return i;
				}
			}
			return -1;
		},
		getItems: function()
		{
			return (this._items || []);
		},
		removeItems: function()
		{
			this._items = [];
		},
		removeItem: function (userId)
		{
			var id = this.findItemIndexById(userId);
			if (id >= 0)
			{
				this._items = BX.util.deleteFromArray(this._items, id);
			}
		},
		isCustomized: function()
		{
			var accessCodes = BX.prop.getObject(this._config, "accessCodes", []);
			return !!Object.keys(accessCodes).length;
		},
	};

	BX.Crm.EntityFieldVisibilityConfigurator.create = function (id, settings)
	{
		var self = new BX.Crm.EntityFieldVisibilityConfigurator();
		self.initialize(id, settings);
		return self;
	}
}

if (typeof BX.Crm.EntityFieldVisibilityConfigButton === "undefined")
{
	BX.Crm.EntityFieldVisibilityConfigButton = function()
	{
		this._id = "";
		this._settings = null;
		this._configurator = null;
		this._wrapper = null;
		this._selectUserControl = null;
		this._addButton = null;
	};

	BX.Crm.EntityFieldVisibilityConfigButton.prototype =
	{
		initialize: function (id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};
			this._configurator = BX.prop.get(this._settings, "configurator", null);
			this.adjust();
		},
		onClick: function (e)
		{
			e.preventDefault();
			if (this._configurator.isEnabled())
			{
				this._configurator.open(this._wrapper);
			}
		},
		adjust: function()
		{
			if (this._configurator.isEnabled())
			{
				BX.removeClass(this._wrapper, "crm-entity-new-field-visibility-disabled");
				BX.Dom.show(this._wrapper);
			}
			else
			{
				BX.addClass(this._wrapper, "crm-entity-new-field-visibility-disabled");
				BX.Dom.hide(this._wrapper);
			}
		},
		prepareLayout: function()
		{
			this._addButton = BX.create('a',
			{
				props:{
					className:'feed-add-destination-link'
				},
				attrs:{
					href:'#'
				},
				text: BX.Crm.EntityFieldVisibilityConfigurator.messages['addUserButton']
			});

			BX.bind(this._addButton, "click", BX.delegate(this.onClick, this));

			this._selectUserControl = BX.create('div',
			{
				props:{
					className:'main-ui-control-entity main-ui-control userfieldemployee-control'
				},
				dataset:{
					multiple: true
				},
				children: [this._addButton]
			});

			var title = BX.create('label',{
				text: BX.Crm.EntityFieldVisibilityConfigurator.messages['labelField']
			});

			this._wrapper = BX.create("div",
			{
				props: {
					className: 'crm-entity-new-field-visibility'
				},
				children: [title, this.getSelectUserControl()]
			});

			this.adjust();

			return [this._wrapper];
		},
		getSelectUserControl: function()
		{
			return this._selectUserControl;
		}
	};

	BX.Crm.EntityFieldVisibilityConfigButton.create = function (id, settings)
	{
		var self = new BX.Crm.EntityFieldVisibilityConfigButton();
		self.initialize(id, settings);
		return self;
	}
}