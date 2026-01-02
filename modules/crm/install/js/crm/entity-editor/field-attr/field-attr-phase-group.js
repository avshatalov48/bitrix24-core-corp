/* eslint-disable */

BX.namespace("BX.Crm");

if(typeof BX.Crm.EntityFieldAttributePhaseGroup === "undefined")
{
	BX.Crm.EntityFieldAttributePhaseGroup = function()
	{
		this._id = "";
		this._settings = null;
		this._phases = null;
		this._phaseGroupTypeId = BX.UI.EntityFieldAttributePhaseGroupType.undefined;
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
					BX.UI.EntityFieldAttributePhaseGroupType.undefined
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
						BX.UI.EntityFieldAttributePhaseGroupType.undefined
					);

					if(phaseGroupTypeId === BX.UI.EntityFieldAttributePhaseGroupType.undefined && items.length > 0)
					{
						phaseGroupTypeId = this._configurator.resolvePhaseGroupType(
							BX.prop.getString(items[0], "startPhaseId", "")
						);
					}

					if (phaseGroupTypeId === BX.UI.EntityFieldAttributePhaseGroupType.general)
					{
						this.setSelected(true)
					}
					else if (phaseGroupTypeId === this._phaseGroupTypeId && items.length > 0)
					{
						var j;

						if (this._phaseGroupTypeId === BX.UI.EntityFieldAttributePhaseGroupType.pipeline)
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
						else if(this._phaseGroupTypeId === BX.UI.EntityFieldAttributePhaseGroupType.junk)
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
				if (this._phaseGroupTypeId === BX.UI.EntityFieldAttributePhaseGroupType.pipeline)
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
				else if (this._phaseGroupTypeId === BX.UI.EntityFieldAttributePhaseGroupType.junk)
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