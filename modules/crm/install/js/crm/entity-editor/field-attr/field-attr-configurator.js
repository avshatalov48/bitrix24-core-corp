/* eslint-disable */

BX.namespace("BX.Crm");

if(typeof BX.Crm.EntityFieldAttributeConfigurator === "undefined")
{
	BX.Crm.EntityFieldAttributeConfigurator = function()
	{
		this._id = "";
		this._settings = null;

		this._typeId = BX.UI.EntityFieldAttributeType.undefined;
		this._fieldName = "";

		this._config = null;
		this._captions = null;

		this._phases = null; //example: [ { id: "NEW", name: "New", sort: 10, color: "#E7D35D" semantics: "process" } ]

		this._groups = null;
		this._button = null;
		this._popup = null;

		this._label = null;
		this._switchCheckBox = null;
		this._switchCheckBoxHandler = BX.delegate(this.onSwitchCheckBoxClick, this);

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
				this._typeId = BX.prop.getInteger(this._settings, "type", BX.UI.EntityFieldAttributeType.required);
				this._fieldName = BX.prop.getString(this._settings, "fieldName", "");

				this._captions = BX.prop.getObject(this._settings, "captions", {});

				this._config = BX.prop.getObject(this._settings, "config", {});

				var configTypeId = BX.prop.getInteger(this._config, "typeId", BX.UI.EntityFieldAttributeType.undefined);
				if(configTypeId === BX.UI.EntityFieldAttributeType.undefined)
				{
					this._config.typeId = this._typeId;
				}
				else if(configTypeId !== this._typeId)
				{
					throw "EntityFieldAttributeConfigurator. Configuration type mismatch.";
				}

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
					{ phaseGroupTypeId: BX.UI.EntityFieldAttributePhaseGroupType.general }
				);

				if(pipelinePhases.length > 0)
				{
					this.createGroup(
						"pipeline",
						this.getCaption("GROUP_TYPE_PIPELINE"),
						pipelinePhases,
						{ phaseGroupTypeId: BX.UI.EntityFieldAttributePhaseGroupType.pipeline }
					);
				}

				if(junkPhases.length > 0)
				{
					this.createGroup(
						"junk",
						this.getCaption("GROUP_TYPE_JUNK"),
						junkPhases,
						{ phaseGroupTypeId: BX.UI.EntityFieldAttributePhaseGroupType.junk }
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
				if(this._typeId === BX.UI.EntityFieldAttributeType.required)
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
					return BX.UI.EntityFieldAttributePhaseGroupType.undefined;
				}

				var phase = this.getPhaseInfoById(phaseId);
				if(!phase)
				{
					return BX.UI.EntityFieldAttributePhaseGroupType.undefined;
				}

				var semantics = BX.prop.getString(phase, "semantics", "");
				return (semantics === "process" || semantics === "success"
						? BX.UI.EntityFieldAttributePhaseGroupType.pipeline
						: BX.UI.EntityFieldAttributePhaseGroupType.junk
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
						BX.UI.EntityFieldAttributePhaseGroupType.undefined
					);

					if(phaseGroupTypeId === BX.UI.EntityFieldAttributePhaseGroupType.undefined)
					{
						continue;
					}

					if(phaseGroupTypeId !== BX.UI.EntityFieldAttributePhaseGroupType.general)
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
						groups: [ { phaseGroupTypeId: BX.UI.EntityFieldAttributePhaseGroupType.general } ]
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

				if (this._groups["general"].isSelected())
				{
					this._config["groups"].push({ phaseGroupTypeId: BX.UI.EntityFieldAttributePhaseGroupType.general });
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
							BX.UI.EntityFieldAttributePhaseGroupType.undefined
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
				if (!this.isPhaseDependent())
				{
					this.runLockScript();

					return;
				}

				const groupId = group.getId();
				if (groupId === 'general')
				{
					const isSelected = group.isSelected();

					if (isSelected)
					{
						this._groups.pipeline?.setSelected(true);
						this._groups.junk?.setSelected(true);
					}
				}
				else if (!this.isChildrenGroupsFullySelected() && (groupId === 'pipeline' || groupId === 'junk'))
				{
					this._groups.general.setSelected(false);
				}

				this.setEnabled(this.hasSelectedGroup());

				if (!this.isChanged())
				{
					this._isChanged = true;
				}
			},

			isChildrenGroupsFullySelected: function()
			{
				return Object
					.keys(this._groups)
					.filter((key) => {
						return key !== 'general';
					})
					.every((key) => {
						return this._groups[key].isFullySelected();
					})
				;
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
				const innerWrapper = BX.Tag.render`
					<div class="crm-entity-popup-field-addiction-steps-list-container">
					</div>`
				;

				const wrapper = BX.Tag.render`
					<div class="crm-entity-popup-field-addiction-step">
						${innerWrapper}
					</div>`
				;

				Object.keys(this._groups).forEach((key) => {
					const group = this._groups[key];
					group.setContainer(innerWrapper);
					group.layout();
				})

				return wrapper;
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