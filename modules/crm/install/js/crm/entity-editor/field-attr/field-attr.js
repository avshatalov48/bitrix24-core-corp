/* eslint-disable */

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
		createFieldConfigurator: function(field, typeId)
		{
			return BX.Crm.EntityFieldAttributeConfigurator.create(
				this._id,
				{
					typeId: typeId,
					phases: this.getEntityPhases(),
					captions: BX.prop.getObject(this._settings, "captions", {}),
					config: field ? field.getAttributeConfiguration(typeId) : null,
					isPermitted: this.isPermitted(),
					isPhaseDependent: this.isPhaseDependent(),
					isAttrConfigButtonHidden: BX.prop.getBoolean(this._settings, "isAttrConfigButtonHidden", false),
					lockScript: BX.prop.getString(this._settings, "lockScript", "")
				}
			);
		},
		prepareFieldName: function(entityTypeId, fieldName)
		{
			if (
				entityTypeId === BX.CrmEntityType.enumeration.bankDetail
				&& BX.Type.isStringFilled(fieldName)
			)
			{
				const matches = fieldName.match(/\[(\w+)]$/);
				if (matches && BX.Type.isStringFilled(matches[1]) && matches.length > 1)
				{
					fieldName = matches[1];
				}
			}

			return fieldName;
		},
		saveConfiguration: function(config, fieldName)
		{
			if(!this.isPermitted())
			{
				return;
			}

			fieldName = this.prepareFieldName(this._entityTypeId, fieldName);

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

			fieldName = this.prepareFieldName(this._entityTypeId, fieldName);

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

		this.useHumanResourcesModule = true;
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
			this.useHumanResourcesModule = BX.prop.getBoolean(this.getSettings(), 'useHumanResourcesModule', true);

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
				let selectorImplementation = BX.UI.EntityEditorEntitySelector;
				if (!selectorImplementation)
				{
					selectorImplementation = BX.UI.EntityEditorUserSelector;
				}

				this._userSelector = selectorImplementation.create(
					this._id,
					{
						callback: BX.delegate(this.processItemSelect, this),
						useHumanResourcesModule: this.useHumanResourcesModule,
					}
				)
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