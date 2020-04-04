if(typeof(BX.CrmFormMode) === "undefined")
{
	BX.CrmFormMode =
	{
		undefined: 0,
		view: 1,
		edit: 2
	};
}
if(typeof(BX.CrmEditFormManager) === "undefined")
{
	BX.CrmEditFormManager = function()
	{
		this._id = "";
		this._settings = null;
		this._form = null;
		this._formId = "";
		this._tabId = "";
		this._mode = BX.CrmFormMode.edit;
		this._prefix = "";
		this._isModal = false;
		this._dragPriority = -1;
		this._inShortListOptionEnabled = false;
		this._settingsManager = null;
		this._userFieldManager = null;
	};
	BX.CrmEditFormManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(8);
			this._settings = settings ? settings : {};

			this._form = this.getSetting("form", null);
			this._formId = this.getSetting("formId", "");

			this._tabId = this.getSetting("tabId", "");
			if(!BX.type.isNotEmptyString(this._tabId))
			{
				this._tabId = "tab_1";
			}

			if(!BX.type.isNotEmptyString(this._formId))
			{
				throw "Error: The 'formId' parameter is not defined in settings or empty.";
			}

			this._mode = this.getSetting("mode", BX.CrmFormMode.edit);
			this._prefix = this.getSetting("prefix", "");
			this._isModal = (this.getSetting("isModal", false) === true);
			this._dragPriority = parseInt(this.getSetting("dragPriority", -1));

			this._inShortListOptionEnabled = (this.getSetting("enableInShortListOption", false) === true);

			this._settingsManager = BX.CrmFormSettingManager.create(
				this._id,
				{
					formId: this._formId,
					manager: this,
					form: this._form,
					prefix: this._prefix,
					sectionWrapperId: this.getSetting("sectionWrapperId", ""),
					undoContainerId: this.getSetting("undoContainerId", ""),
					tabId: this._tabId,
					metaData: this.getSetting("metaData", {}),
					hiddenMetaData: this.getSetting("hiddenMetaData", {}),
					isSettingsApplied: this.getSetting("isSettingsApplied", false),
					canCreateUserField: this.getSetting("canCreateUserField", false),
					canCreateSection: this.getSetting("canCreateSection", false),
					canSaveSettingsForAll: this.getSetting("canSaveSettingsForAll", false),
					inShortListOptionEnabled: this._inShortListOptionEnabled,
					isModal: this._isModal,
					enableFieldDrag: this.getSetting("enableFieldDrag", true),
					enableSectionDrag: this.getSetting("enableSectionDrag", true),
					dragPriority: this._dragPriority
				}
			);

			var userFieldEntityId = this.getSetting("userFieldEntityId", "");
			if(BX.type.isNotEmptyString(userFieldEntityId))
			{
				this._userFieldManager = BX.CrmFormUserFieldManager.create(
					this._id,
					{
						manager: this,
						serviceUrl: this.getSetting("userFieldServiceUrl"),
						serverTime: this.getSetting("serverTime"),
						//imagePath: this.getSetting("imagePath"),
						entityId: userFieldEntityId,
						canCreate: this.getSetting("canCreateUserField", false),
						addFieldButton: this._formId + "_add_field"
					}
				);
			}

			BX.addCustomEvent(
				window,
				"CrmQuickPanelViewExpanded",
				BX.delegate(this._onQuickPanelViewExpand, this)
			);
		},
		release: function(removeNode)
		{
			this._settingsManager.release(removeNode);
			if(this._userFieldManager.getManager() === this)
			{
				this._userFieldManager.release();
			}
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getMode: function()
		{
			return this._mode;
		},
		getSettingsManager: function()
		{
			return this._settingsManager;
		},
		getUserFieldManager: function()
		{
			return this._userFieldManager;
		},
		_onQuickPanelViewExpand: function(panel, isExpanded)
		{
			this._settingsManager.setViewModeVisibility(isExpanded);
		}
	};
	BX.CrmEditFormManager.items = {};
	BX.CrmEditFormManager.create = function(id, settings)
	{
		var self = new BX.CrmEditFormManager();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}
if(typeof(BX.CrmFormSettingManager) === "undefined")
{
	BX.CrmFormSettingManager = function()
	{
		this._id = "";
		this._settings = null;

		this._manager = null;
		this._form = null;
		this._formId = "";
		this._prefix = "";
		this._sectionWrapperId = "";
		this._undoContainerId = "";
		this._menuButton = null;
		this._menu = null;
		this._menuId = "";
		this._isMenuShown = false;

		this._tabId = "";
		this._metaData = null;
		this._hiddenMetaData = null;
		this._editData = null;
		this._isSettingsApplied = false;
		this._isModal = false;
		this._dragPriority = -1;
		this._inShortListOptionEnabled = false;

		this._enableSectionDrag = true;
		this._enableFieldDrag = true;

		this._draggableFieldContextId = "";
		this._draggableSectionContextId = "";

		this._dragDropUndoData = null;
		this._sectionSettings = {};
		this._fieldSettings = {};
		this._temporaryFields = {};
		this._temporaryFieldCounter = 0;
		this._temporarySection = null;

		this._placeHolder = null;
		this._dragContainer = null;
		this._canCreateUserField = false;
		this._canCreateSection = false;
		this._needToReload = false;
		this._fieldDropHandler = BX.delegate(this._onFieldDrop, this);
		this._sectionDropHandler = BX.delegate(this._onSectionDrop, this);
	};
	BX.CrmFormSettingManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(8);
			this._settings = settings ? settings : {};

			this._form = this.getSetting("form", null);
			this._formId = this.getSetting("formId", "");
			if(!BX.type.isNotEmptyString(this._formId))
			{
				throw "Error: The 'formId' parameter is not defined in settings or empty.";
			}

			this._prefix = this.getSetting("prefix", "");
			this._menuButton = BX(this._formId + "_menu");
			if(this._menuButton)
			{
				BX.bind(this._menuButton, "click", BX.delegate(this._onMenuButtonClick, this));
			}

			this._menuId = this._id.toLowerCase() + "_main_menu";
			this._sectionWrapperId = this.getSetting("sectionWrapperId", "");
			this._undoContainerId = this.getSetting("undoContainerId", "");

			this._tabId = this.getSetting("tabId", "");
			if(!BX.type.isNotEmptyString(this._tabId))
			{
				this._tabId = "tab_1";
			}

			this._metaData = this.getSetting("metaData", null);
			if(!BX.type.isPlainObject(this._metaData))
			{
				throw "Error: The 'metaData' parameter is not defined in settings or empty.";
			}

			this._hiddenMetaData = this.getSetting("hiddenMetaData", null);
			if(BX.type.isArray(this._hiddenMetaData))
			{
				this._hiddenMetaData = {};
			}
			else if(!BX.type.isPlainObject(this._hiddenMetaData))
			{
				throw "Error: The 'hiddenMetaData' parameter is not defined in settings or empty.";
			}

			this._manager = this.getSetting("manager", null);

			this._canCreateUserField = this.getSetting("canCreateUserField", false);
			this._canCreateSection = this.getSetting("canCreateSection", false);

			this._inShortListOptionEnabled = (this.getSetting("inShortListOptionEnabled", false) === true);

			this._dragPriority = parseInt(this.getSetting("dragPriority", -1));
			this._isModal = (this.getSetting("isModal", false) === true);
			if(this._isModal && this._dragPriority === -1)
			{
				this._dragPriority = 1;
			}

			this._isSettingsApplied = this.getSetting("isSettingsApplied", false);

			var draggableContextPrefix = this.getFormId().toLowerCase() + "_";
			this._draggableFieldContextId = draggableContextPrefix + BX.CrmFormFieldDragItem.contextId;
			this._draggableSectionContextId = draggableContextPrefix + BX.CrmFormSectionDragItem.contextId;

			this._enableSectionDrag = this.getSetting("enableSectionDrag", true);
			this._enableFieldDrag = this.getSetting("enableFieldDrag", true);

			this._initializeFromMetaData();

			this._dragContainer = BX.CrmFormSectionDragContainer.create(
				this._id,
				{
					manager: this,
					node: BX(this._sectionWrapperId),
					contextId: this._draggableSectionContextId
				}
			);
			this._dragContainer.addDragFinishListener(this._sectionDropHandler);
			BX.onCustomEvent(window, "CrmFormSettingManagerCreate", [this]);

			if(!this._isModal)
			{
				var bin = BX.CrmDragDropBin.getInstance();
				BX.addCustomEvent(bin, "CrmDragDropBinItemDrop", BX.delegate(this._onDragDropBinItemDrop, this));
			}
		},
		_initializeFromMetaData: function()
		{
			// Initialize edit data
			this._editData = [];
			for(var k in this._metaData)
			{
				if(!this._metaData.hasOwnProperty(k))
				{
					continue;
				}

				var tabInfo = BX.clone(this._metaData[k]);

				var fieldInfos = [];
				var fieldMetaData = BX.type.isPlainObject(this._metaData[k]["fields"]) ? this._metaData[k]["fields"] : {};
				for(var l in fieldMetaData)
				{
					if(fieldMetaData.hasOwnProperty(l))
					{
						fieldInfos.push(BX.clone(fieldMetaData[l]));
					}
				}

				tabInfo["fields"] = fieldInfos;
				this._editData.push(tabInfo);
			}

			// Initialize drag & drop for fields and sections
			var data = this._metaData.hasOwnProperty(this._tabId) ? this._metaData[this._tabId] : null;
			if(!(data && typeof(data) === "object"))
			{
				throw "Error: Could not find '" + this._tabId + "' in metaData.";
			}

			var fields = data["fields"];
			if(BX.type.isPlainObject(fields))
			{
				var enableDragDropBin = !this._isModal;
				for(var fieldId in fields)
				{
					if(!fields.hasOwnProperty(fieldId))
					{
						continue;
					}

					var info = fields[fieldId];
					var type = BX.type.isNotEmptyString(info["type"]) ? info["type"] : "";
					if(type === "section")
					{
						if(!this.getSectionNode(fieldId))
						{
							continue;
						}

						this._sectionSettings[fieldId] = BX.CrmFormSectionSetting.create(
							fieldId,
							{
								manager: this,
								data: info,
								draggableFieldContextId: this._draggableFieldContextId,
								draggableSectionContextId: this._draggableSectionContextId,
								enableDrag: this._enableSectionDrag,
								enableDragDropBin: enableDragDropBin
							}
						);
					}
					else
					{
						if(!this.getFieldNode(fieldId))
						{
							continue;
						}

						this._fieldSettings[fieldId] = BX.CrmFormFieldSetting.create(
							fieldId,
							{
								manager: this,
								data: info,
								draggableContextId: this._draggableFieldContextId,
								enableDrag: this._enableFieldDrag,
								enableDragDropBin: enableDragDropBin,
								inShortListOptionEnabled: this._inShortListOptionEnabled
							}
						);
					}
				}
			}

			if(!this.hasSections())
			{
				this.createTemporarySection();
			}
		},
		release: function(removeNode)
		{
			removeNode = !!removeNode;
			for(var j in this._fieldSettings)
			{
				if(this._fieldSettings.hasOwnProperty(j))
				{
					this._fieldSettings[j].release(removeNode);
				}
			}
			this._fieldSettings = {};

			for(var i in this._sectionSettings)
			{
				if(this._sectionSettings.hasOwnProperty(i))
				{
					this._sectionSettings[i].release(removeNode);
				}
			}
			this._sectionSettings = {};
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getFormId: function()
		{
			return this._formId;
		},
		getFormNodeId: function()
		{
			return "form_" + this._formId;
		},
		getForm: function()
		{
			return BX(this.getFormNodeId());
		},
		getFormMode: function()
		{
			return this._manager.getMode();
		},
		getTabId: function()
		{
			return this._tabId;
		},
		getManager: function()
		{
			return this._manager;
		},
		hasHiddenFields: function()
		{
			for(var k in this._hiddenMetaData)
			{
				if(!this._hiddenMetaData.hasOwnProperty(k))
				{
					continue;
				}

				var info = this._hiddenMetaData[k];
				var type = BX.type.isNotEmptyString(info["type"]) ? info["type"] : "";
				if(type !== "section")
				{
					return true;
				}
			}
			return false;
		},
		getHiddenFieldInfos: function()
		{
			var result = [];
			for(var k in this._hiddenMetaData)
			{
				if(!this._hiddenMetaData.hasOwnProperty(k))
				{
					continue;
				}

				var info = this._hiddenMetaData[k];
				var type = BX.type.isNotEmptyString(info["type"]) ? info["type"] : "";
				if(type === "section")
				{
					continue;
				}
				result.push(info);
			}
			return result;
		},
		canCreateUserField: function()
		{
			return this._canCreateUserField;
		},
		createTemporaryField: function(type, section)
		{
			this._temporaryFieldCounter++;
			var label = this.getMessage("newFieldName") + " " + this._temporaryFieldCounter.toString();
			var userField = this._manager.getUserFieldManager().createTemporaryField({ type: type, label: label });

			var table = section.getContentsNode();
			var index = table.rows.length - 1;
			var temporaryId = userField.getFieldName();

			var fieldType = "text";
			if(type === "boolean")
			{
				fieldType = "checkbox";
			}
			else if(type === "datetime")
			{
				fieldType = "date";
			}

			var field = BX.CrmFormFieldSetting.create(
				temporaryId,
				{
					manager: this,
					data: {
						id: temporaryId,
						type: fieldType,
						userFieldType: type,
						name: label,
						isRQ: (this._inShortListOptionEnabled) ? true : false
					},
					draggableContextId: this._draggableFieldContextId,
					inShortListOptionEnabled: this._inShortListOptionEnabled,
					isTemporary: true,
					editMode: true,
					node: BX.CrmFormFieldRenderer.renderUserFieldRow(
						userField,
						table,
						{
							index: index,
							prefix: this._prefix,
							enableDrag: this._enableFieldDrag
						}
					)
				}
			);
			this._temporaryFields[temporaryId] = { field: field, section: section };
		},
		canCreateSection: function()
		{
			return this._canCreateSection;
		},
		createSection: function(precedingSection)
		{
			var index = 0;
			if(this._temporarySection !== precedingSection)
			{
				var loc = this.getSectionLocation(precedingSection.getId());
				index = loc.index;
				if(index < 0)
				{
					return;
				}
				index += loc.length + 1;
			}

			var sectionId = "section_" + BX.util.getRandomString(8).toLocaleLowerCase();
			var data = { type: "section", id: sectionId, name: this.getMessage("newSectionName") };

			var tabQty = this._editData.length;
			for(var i = 0; i < tabQty; i++)
			{

				var tabInfo = this._editData[i];
				if(tabInfo["id"] !== this._tabId)
				{
					continue;
				}

				var fields = tabInfo["fields"];
				if(!BX.type.isArray(fields))
				{
					return;
				}

				if(index >= fields.length)
				{
					fields.push(data);
				}
				else
				{
					fields.splice(index, 0, data);
				}
			}

			var table = BX.CrmFormFieldRenderer.renderSectionTable(
				data,
				{
					canCreateSection: this.canCreateSection(),
					canCreateUserField: this.canCreateUserField(),
					containerNode: BX(this._sectionWrapperId),
					index: this._temporarySection !== precedingSection
						? (this.getSectionRootIndex(precedingSection.getId()) + 1) : 0,
					prefix: this._prefix
				}
			);

			this._sectionSettings[sectionId] = BX.CrmFormSectionSetting.create(
				sectionId,
				{
					manager: this,
					data: data,
					draggableFieldContextId: this._draggableFieldContextId,
					draggableSectionContextId: this._draggableSectionContextId,
					editMode: true
				}
			);

			var headRow = table.rows[0];
			headRow.draggable = true;
			headRow.setAttribute("data-dragdrop-id", sectionId);
			headRow.setAttribute("data-dragdrop-context", "field");

			var result = this.save(false);
			if(result && this._temporarySection)
			{
				this._temporarySection.release(true);
				this._temporarySection = null;
			}
			return result;
		},
		createTemporarySection: function()
		{
			if(!this.canCreateSection())
			{
				return;
			}

			var sectionId = "section_" + BX.util.getRandomString(8).toLocaleLowerCase();
			var data = { type: "section", id: sectionId };

			BX.CrmFormFieldRenderer.renderSectionTable(
				data,
				{
					canCreateSection: true,
					canCreateUserField: false,
					canRestoreField: false,
					containerNode: BX(this._sectionWrapperId),
					index: 0,
					prefix: this._prefix
				}
			);

			this._temporarySection = BX.CrmFormSectionSetting.create(
				sectionId,
				{
					manager: this,
					data: data,
					draggableFieldContextId: this._draggableFieldContextId,
					draggableSectionContextId: this._draggableSectionContextId,
					isTemporary: true
				}
			);
		},
		createDragEffectNode: function(item)
		{
			var titleHtml = item.getId();
			var label = BX.findChild(item.getNode(), { tagName: "SPAN", className: "crm-offer-info-label" }, true, false);
			if(label)
			{
				titleHtml = label.innerHTML.replace(/:\s*$/, "");
			}
			return BX.create("DIV",
				{
					attrs: { className: "crm-offer-draggable-item" },
					children:
					[
						BX.create("SPAN", { attrs: { className: "crm-offer-drg-btn" } }),
						BX.create("SPAN", { attrs: { className: "crm-offer-title-text" }, html: titleHtml })
					]
				}
			);
		},
		getTabFields: function()
		{
			var tabQty = this._editData.length;
			for(var i = 0; i < tabQty; i++)
			{

				var tabInfo = this._editData[i];
				if(tabInfo["id"] !== this._tabId)
				{
					continue;
				}

				return tabInfo["fields"];
			}
			return null;
		},
		getFields: function()
		{
			return this._fieldSettings;
		},
		getField: function(fieldId)
		{
			return this._fieldSettings.hasOwnProperty(fieldId) ? this._fieldSettings[fieldId] : null;
		},
		getFieldSection: function(fieldId)
		{
			if(!BX.type.isNotEmptyString(fieldId))
			{
				return null;
			}

			var tabQty = this._editData.length;
			for(var i = 0; i < tabQty; i++)
			{
				var tabInfo = this._editData[i];
				if(tabInfo["id"] !== this._tabId)
				{
					continue;
				}

				var fields = tabInfo["fields"];
				if(!BX.type.isArray(fields))
				{
					return -1;
				}

				var fieldQty = fields.length;
				var sectionId = "";
				for(var j = 0; j < fieldQty; j++)
				{
					var fieldInfo = fields[j];
					if(fieldInfo["type"] === "section")
					{
						sectionId = fieldInfo["id"];
						continue;
					}

					if(fieldInfo["id"] === fieldId)
					{
						return this._sectionSettings.hasOwnProperty(sectionId) ? this._sectionSettings[sectionId] : null;
					}
				}
			}
			return null;
		},
		getFieldIndex: function(fieldId)
		{
			if(!BX.type.isNotEmptyString(fieldId))
			{
				return -1;
			}

			var tabQty = this._editData.length;
			for(var i = 0; i < tabQty; i++)
			{

				var tabInfo = this._editData[i];
				if(tabInfo["id"] !== this._tabId)
				{
					continue;
				}

				var fields = tabInfo["fields"];
				if(!BX.type.isArray(fields))
				{
					return -1;
				}

				var fieldQty = fields.length;
				for(var j = 0; j < fieldQty; j++)
				{
					var fieldInfo = fields[j];
					if(fieldInfo["id"] === fieldId)
					{
						return j;
					}
				}
			}
			return -1;
		},
		setFieldIndex: function(fieldId, newIndex, oldIndex)
		{
			if(!BX.type.isNotEmptyString(fieldId)
				|| !BX.type.isNumber(newIndex)
				|| newIndex < 0)
			{
				return false;
			}

			oldIndex = parseInt(oldIndex);
			if(isNaN(oldIndex) || oldIndex < 0)
			{
				oldIndex = this.getFieldIndex(fieldId);
			}

			if(oldIndex < 0)
			{
				return false;
			}

			if(newIndex === oldIndex)
			{
				return true;
			}

			var tabQty = this._editData.length;
			for(var i = 0; i < tabQty; i++)
			{

				var tabInfo = this._editData[i];
				if(tabInfo["id"] !== this._tabId)
				{
					continue;
				}

				var fields = tabInfo["fields"];
				if(!BX.type.isArray(fields))
				{
					return false;
				}

				var fieldInfo = fields[oldIndex];
				fields.splice(oldIndex, 1);
				if((newIndex - oldIndex) > 1)
				{
					newIndex--;
				}

				if(newIndex >= fields.length)
				{
					fields.push(fieldInfo);
				}
				else
				{
					fields.splice(newIndex, 0, fieldInfo);
				}
			}

			return this.save(false);
		},
		hasField: function(fieldId)
		{
			return this._fieldSettings.hasOwnProperty(fieldId);
		},
		hasSection: function(sectionId)
		{
			return this._sectionSettings.hasOwnProperty(sectionId);
		},
		removeField: function(fieldId)
		{
			var result = this._removeField(fieldId) && this.save(false);
			if(result)
			{
				this._dragDropUndoData = null;
				BX.cleanNode(BX(this._undoContainerId), false);
			}
			return result;
		},
		restoreField: function(fieldId, sectionId)
		{
			if(!BX.type.isNotEmptyString(fieldId))
			{
				return false;
			}

			if(!BX.type.isNotEmptyString(sectionId))
			{
				return false;
			}

			if(!this._hiddenMetaData.hasOwnProperty(fieldId))
			{
				return false;
			}
			var fieldInfo = this._hiddenMetaData[fieldId];

			var loc = this.getSectionLocation(sectionId);
			var index = loc.index;
			if(index < 0)
			{
				return false;
			}

			index += loc.length + 1;
			var tabQty = this._editData.length;
			for(var i = 0; i < tabQty; i++)
			{

				var tabInfo = this._editData[i];
				if(tabInfo["id"] !== this._tabId)
				{
					continue;
				}

				var fields = tabInfo["fields"];
				if(!BX.type.isArray(fields))
				{
					return false;
				}

				if(index >= fields.length)
				{
					fields.push(fieldInfo);
				}
				else
				{
					fields.splice(index, 0, fieldInfo);
				}
			}

			this._needToReload = true;
			this.save(false);
			return true;
		},
		getSectionLocation: function(sectionId)
		{
			if(!BX.type.isNotEmptyString(sectionId))
			{
				return { index: -1, length: 0, fields: [] };
			}

			var tabQty = this._editData.length;
			for(var i = 0; i < tabQty; i++)
			{
				var tabInfo = this._editData[i];
				if(tabInfo["id"] !== this._tabId)
				{
					continue;
				}

				var fields = tabInfo["fields"];
				if(!BX.type.isArray(fields))
				{
					return { index: -1, length: 0, fields: [] };
				}

				var sectionFields = [];
				var sectionIndex = -1;
				var length = 0;
				var fieldQty = fields.length;
				for(var j = 0; j < fieldQty; j++)
				{
					var fieldInfo = fields[j];
					if(fieldInfo["type"] === "section")
					{
						if(sectionIndex >= 0)
						{
							break;
						}
						else if(fieldInfo["id"] === sectionId)
						{
							sectionIndex = j;
						}
					}
					else if(sectionIndex >= 0)
					{
						sectionFields.push(fieldInfo["id"]);
						length++;
					}
				}
				return { index: sectionIndex, length: length, fields: sectionFields };
			}
			return { index: -1, length: 0, fields: [] };
		},
		getSectionIndex: function(sectionId)
		{
			var loc = this.getSectionLocation(sectionId);
			return loc.index;
		},
		setSectionIndex: function(sectioId, newIndex, loc)
		{
			if(!BX.type.isNotEmptyString(sectioId))
			{
				return false;
			}

			if(!BX.type.isNumber(newIndex) || newIndex < 0)
			{
				return false;
			}

			if(!loc)
			{
				loc = this.getSectionLocation(sectioId);
			}

			var oldIndex = loc.index;
			if(oldIndex < 0)
			{
				return false;
			}

			if(newIndex === oldIndex)
			{
				return true;
			}

			var tabQty = this._editData.length;
			for(var i = 0; i < tabQty; i++)
			{

				var tabInfo = this._editData[i];
				if(tabInfo["id"] !== this._tabId)
				{
					continue;
				}

				var fields = tabInfo["fields"];
				if(!BX.type.isArray(fields))
				{
					return false;
				}

				var sectionInfo = fields[oldIndex];
				var sectionFieldInfos = [];
				var length  = loc.length;
				if(length > 0)
				{
					var lastFieldIndex = oldIndex + length;
					for(var j = (oldIndex + 1); j <= lastFieldIndex; j++)
					{
						sectionFieldInfos.push(fields[j]);
					}
					fields.splice(oldIndex + 1, length);
				}

				fields.splice(oldIndex, 1);
				if(newIndex >= oldIndex)
				{
					newIndex -= loc.length + 1;
				}

				if(newIndex >= fields.length)
				{
					fields.push(sectionInfo);
					for(var k = 0; k < sectionFieldInfos.length; k++)
					{
						fields.push(sectionFieldInfos[k]);
					}
				}
				else
				{
					fields.splice(newIndex, 0, sectionInfo);
					for(var l = 0; l < sectionFieldInfos.length; l++)
					{
						fields.splice(newIndex + l + 1, 0, sectionFieldInfos[l]);
					}
				}
			}

			return this.save(false);
		},
		getSectionFieldIds: function(sectionId)
		{
			var loc = this.getSectionLocation(sectionId);
			return loc.fields;
		},
		getSectionRootIndex: function(sectionId)
		{
			var tabQty = this._editData.length;
			var sectionQty = 0;
			for(var i = 0; i < tabQty; i++)
			{
				var tabInfo = this._editData[i];
				if(tabInfo["id"] !== this._tabId)
				{
					continue;
				}

				var fields = tabInfo["fields"];
				if(!BX.type.isArray(fields))
				{
					return -1;
				}

				var fieldQty = fields.length;
				for(var j = 0; j < fieldQty; j++)
				{
					var fieldInfo = fields[j];
					if(fieldInfo["type"] !== "section")
					{
						continue;
					}

					if(fieldInfo["id"] === sectionId)
					{
						return sectionQty;
					}
					sectionQty++;
				}
			}
			return -1;
		},
		getSectionIds: function()
		{
			var result = [];
			var tabQty = this._editData.length;
			for(var i = 0; i < tabQty; i++)
			{
				var tabInfo = this._editData[i];
				if(tabInfo["id"] !== this._tabId)
				{
					continue;
				}

				var fields = tabInfo["fields"];
				if(!BX.type.isArray(fields))
				{
					return result;
				}

				var fieldQty = fields.length;
				for(var j = 0; j < fieldQty; j++)
				{
					var fieldInfo = fields[j];
					if(fieldInfo["type"] !== "section")
					{
						continue;
					}

					result.push(fieldInfo["id"]);
				}
			}
			return result;
		},
		getSections: function()
		{
			var result = [];
			var ids = this.getSectionIds();
			for(var i = 0; i < ids.length; i++)
			{
				var id = ids[i];
				var section = this._sectionSettings.hasOwnProperty(id) ? this._sectionSettings[id] : null;
				if(section)
				{
					result.push(section);
				}
			}
			return result;
		},
		hasSections: function()
		{
			for(var k in this._sectionSettings)
			{
				if(this._sectionSettings.hasOwnProperty(k))
				{
					return true;
				}
			}
			return false;
		},
		setupField: function(fieldId, fieldData)
		{
			var index = this.getFieldIndex(fieldId);
			if(index < 0)
			{
				return false;
			}

			var fields = this.getTabFields();
			if(!BX.type.isArray(fields))
			{
				return false;
			}

			fields[index] = fieldData;
			return this.save(false);
		},
		save: function(forAllUsers)
		{
			if(!this._form)
			{
				return false;
			}

			BX.showWait();
			this._form.aTabsEdit = BX.clone(this._editData);

			var data = BX.clone(this._editData);
			var eventArgs = { "cancel": false, "data": data };
			BX.onCustomEvent(this, "CrmFormSettingManagerSave", [ this, eventArgs ]);

			if(eventArgs["cancel"])
			{
				return;
			}

			data = eventArgs["data"];
			if(!BX.type.isArray(data))
			{
				return false;
			}

			this._form.aTabsEdit = data;
			var options = { callback: BX.delegate(this._onSettingsSave, this) };
			if(!!forAllUsers && this.getSetting("canSaveSettingsForAll", false))
			{
				options["setDefaultSettings"] = true;
				options["deleteUserSettings"] = true;
			}

			this._form.SaveSettings(options);
			return true;
		},
		reset: function()
		{
			this._needToReload = true;
			this._form.EnableSettings(false, BX.delegate(this._onSettingsDisable, this));
		},
		setViewModeVisibility: function(visible)
		{
			this._form.SetViewModeVisibility(visible);
		},
		processFieldEditStart: function(field)
		{
		},
		processFieldEditEnd: function(field)
		{
			var fieldId =  field.getId();
			if(!field.isTemporary())
			{
				this.setupField(fieldId, field.getData());
				return true;
			}

			this._manager.getUserFieldManager().createField(
				{ type: field.getUserFieldType(), name: field.getName() },
				fieldId,
				BX.delegate(this._onUserFieldCreate, this)
			);
			return false;
		},
		processFieldEditCancelation: function(field)
		{
			if(!field.isTemporary())
			{
				return true;
			}

			// Forget it!
			var row = field.getNode();
			BX.findParent(row, { tagName: "TABLE" }).deleteRow(row.rowIndex);
			field.release(false);
			delete this._temporaryFields[field.getId()];
			return false;
		},
		processSectionEditStart: function(section)
		{
			var eventArgs = { section: section, cancel: false };
			BX.onCustomEvent(this, "CrmFormSettingManagerSectionEditStart", [this, eventArgs]);
			return !eventArgs.cancel;
		},
		processSectionEditEnd: function(section)
		{
			var eventArgs = { section: section, cancel: false };
			BX.onCustomEvent(this, "CrmFormSettingManagerSectionEditEnd", [this, eventArgs]);
			if(!eventArgs.cancel)
			{
				var sectionId =  section.getId();
				this.setupField(sectionId, section.getData());
			}

			return true;
		},
		processSectionRemove: function(section)
		{
			var eventArgs = { section: section, cancel: false };
			BX.onCustomEvent(this, "CrmFormSettingManagerSectionRemove", [this, eventArgs]);
			if(eventArgs.cancel === true)
			{
				return false;
			}

			var sectionId = section.getId();
			var loc = this.getSectionLocation(sectionId);
			if(loc.index < 0)
			{
				return false;
			}

			var fieldIds = loc.fields;
			var fieldId, field, i;
			for(i = 0; i < fieldIds.length; i++)
			{
				fieldId = fieldIds[i];
				if(typeof(this._fieldSettings[fieldId]) === "undefined")
				{
					continue;
				}

				field = this._fieldSettings[fieldId];
				if(field.isRequired() || field.isPersistent())
				{
					BX.NotificationPopup.show("form_setting_section_has_required_fields", { messages: [this.getMessage("sectionHasRequiredFields")] });
					return false;
				}
			}

			for(i = 0; i < fieldIds.length; i++)
			{
				fieldId = fieldIds[i];
				if(typeof(this._fieldSettings[fieldId]) === "undefined")
				{
					continue;
				}

				field = this._fieldSettings[fieldId];
				delete this._fieldSettings[fieldId];
				var fieldRow = field.getNode();
				BX.findParent(fieldRow, { tagName: "TABLE" }).deleteRow(fieldRow.rowIndex);
				field.release(false);

				this._removeField(fieldId);
			}

			delete this._sectionSettings[sectionId];
			var sectionTable = section.getContentsNode();
			BX.findParent(sectionTable, { tagName: "DIV", className: "crm-offer-main-wrap" }).removeChild(sectionTable);
			section.release(false);

			var result = this._removeField(sectionId) && this.save(false);
			if(result && !this.hasSections())
			{
				this.createTemporarySection();
			}

			return result;
		},
		processFieldRemove: function(field)
		{
			var fieldId = field.getId();
			delete this._fieldSettings[fieldId];

			var row = field.getNode();
			BX.findParent(row, { tagName: "TABLE" }).deleteRow(row.rowIndex);
			field.release(false);

			this.removeField(fieldId);

			for(var k in this._sectionSettings)
			{
				if(this._sectionSettings.hasOwnProperty(k))
				{
					this._sectionSettings[k].processFieldRemove(field);
				}
			}
			return true;
		},
		getMessage: function(name)
		{
			var m = BX.CrmFormSettingManager.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		isModal: function()
		{
			return this._isModal;
		},
		getDragPriority: function()
		{
			return this._dragPriority;
		},
		resolveFieldNodeId: function(fieldId)
		{
			return this.resolveNodeId(fieldId, "wrap");
		},
		getFieldNode: function(fieldId)
		{
			return BX(this.resolveNodeId(fieldId, "wrap"));
		},
		resolveSectionNodeId: function(sectionId)
		{
			return this.resolveNodeId(sectionId, "contents");
		},
		getSectionNode: function(sectionId)
		{
			return BX(this.resolveNodeId(sectionId, "contents"));
		},
		getSectionButtonWrapperNode: function(sectionId)
		{
			return BX(this.resolveNodeId(sectionId, "buttons"));
		},
		getSectionButtonNode: function(sectionId, buttonId)
		{
			return BX(this.resolveNodeId(sectionId, buttonId));
		},
		resolveNodeId: function(id, postfix)
		{
			var result = id.toLowerCase() + "_" + postfix;
			if(this._prefix !== "")
			{
				result = this._prefix + "_" + result;
			}
			return result;
		},
		_findNextDragDropFieldNode: function(node)
		{
			return BX.findNextSibling(node, { tagName: "TR", className: "crm-offer-row" });
		},
		_findNextDragDropSectionNode: function(node)
		{
			return BX.findNextSibling(node, { tagName: "TABLE", className: "crm-offer-info-table" });
		},
		_moveDragDropFieldNode: function(node, containerNode, insertBeforeNode)
		{
			if(!BX.type.isDomNode(insertBeforeNode))
			{
				insertBeforeNode = containerNode.rows[containerNode.rows.length - 1];
			}

			containerNode.tBodies[0].insertBefore(node, insertBeforeNode);
		},
		_moveDragDropSectionNode: function(node, insertBeforeNode)
		{
			var wrapper = BX(this._sectionWrapperId);

			if(BX.type.isDomNode(insertBeforeNode))
			{
				wrapper.insertBefore(node, insertBeforeNode);
			}
			else
			{
				wrapper.appendChild(node);
			}
		},
		_removeField: function(fieldId)
		{
			if(!BX.type.isNotEmptyString(fieldId))
			{
				return false;
			}

			var oldIndex = this.getFieldIndex(fieldId);
			if(oldIndex < 0)
			{
				return false;
			}

			var tabQty = this._editData.length;
			for(var i = 0; i < tabQty; i++)
			{

				var tabInfo = this._editData[i];
				if(tabInfo["id"] !== this._tabId)
				{
					continue;
				}

				var fields = tabInfo["fields"];
				if(!BX.type.isArray(fields))
				{
					return false;
				}

				this._hiddenMetaData[fieldId] = fields[oldIndex];
				fields.splice(oldIndex, 1);
			}

			return true;
		},
		_onUserFieldCreate: function(sender, temporaryId, userField)
		{
			if(!BX.type.isNotEmptyString(temporaryId))
			{
				return;
			}

			if(typeof(this._temporaryFields[temporaryId]) === "undefined")
			{
				return;
			}

			var temporaryField = this._temporaryFields[temporaryId]["field"];
			var section = this._temporaryFields[temporaryId]["section"];

			var data = temporaryField.getData();
			var sectionId = section.getId();
			var fieldId = data["id"] = userField.getFieldName();

			var loc = this.getSectionLocation(sectionId);
			if(loc.index < 0)
			{
				throw "Error: Could not find '" + sectionId + "' section location.";
			}
			var index = (loc.index + loc.length + 1);

			var table = section.getContentsNode();
			temporaryField.release(false);
			table.deleteRow(temporaryField.getNode().rowIndex);

			var tabQty = this._editData.length;
			for(var i = 0; i < tabQty; i++)
			{

				var tabInfo = this._editData[i];
				if(tabInfo["id"] !== this._tabId)
				{
					continue;
				}

				var fields = tabInfo["fields"];
				if(!BX.type.isArray(fields))
				{
					return;
				}

				if(index >= fields.length)
				{
					fields.push(data);
				}
				else
				{
					fields.splice(index, 0, data);
				}
				break;
			}

			var node = BX.CrmFormFieldRenderer.renderUserFieldRow(
				userField,
				table,
				{
					index: (table.rows.length - 1),
					prefix: this._prefix,
					enableDrag: this._enableFieldDrag
				}
			);
			this._fieldSettings[fieldId] = BX.CrmFormFieldSetting.create(
				fieldId,
				{
					manager: this,
					data: data,
					draggableContextId: this._draggableFieldContextId,
					inShortListOptionEnabled: this._inShortListOptionEnabled,
					node: node
				}
			);

			node.draggable = true;
			node.setAttribute("data-dragdrop-id", fieldId);
			node.setAttribute("data-dragdrop-context", "field");

			this.save(false);
		},
		_onDragDropBinItemDrop: function(sender, draggedItem)
		{
			if(draggedItem instanceof BX.CrmFormFieldDragItem)
			{
				var field = draggedItem.getField();
				if(field && this.hasField(field.getId()))
				{
					field.remove();
				}
			}
			else if(draggedItem instanceof BX.CrmFormSectionDragItem)
			{
				var section = draggedItem.getSection();
				if(section && this.hasSection(section.getId()))
				{
					section.remove();
				}
			}
		},
		//jsDD
		getDraggableFieldContextId: function()
		{
			return this._draggableFieldContextId;
		},
		resolveDraggableFieldId: function(contextData)
		{
			var contextId = BX.type.isNotEmptyString(contextData["contextId"]) ? contextData["contextId"] : "";
			if(contextId !== this._draggableFieldContextId)
			{
				return "";
			}

			var field = typeof(contextData["field"]) !== "undefined" ?  contextData["field"] : null;
			return field ? field.getId() : "";
		},
		processDraggedFieldDrop: function(dragContainer, draggedItem)
		{
			var section = dragContainer.getSection();
			var contextData = draggedItem.getContextData();
			var contextId = BX.type.isNotEmptyString(contextData["contextId"]) ? contextData["contextId"] : "";

			if(contextId !== this._draggableFieldContextId)
			{
				return;
			}

			var field = typeof(contextData["field"]) !== "undefined" ?  contextData["field"] : null;
			if(!field)
			{
				return;
			}

			var loc = this.getSectionLocation(section.getId());
			if(loc.index < 0)
			{
				throw "Error: Could not get section layout info.";
			}

			var fieldId = field.getId();
			var fieldNode = field.getNode();
			var fieldSection = this.getFieldSection(fieldId);
			var nextFieldNode = this._findNextDragDropFieldNode(fieldNode);

			var containerNode = section.getContentsNode();
			var placeHolder = section.getPlaceHolder();

			this._moveDragDropFieldNode(
				fieldNode,
				containerNode,
				placeHolder ? placeHolder.getNode() : null
			);

			var newIndex = -1;
			var anchorFieldId = placeHolder ? placeHolder.getFieldId() : "";
			if(anchorFieldId !== "")
			{
				newIndex = this.getFieldIndex(anchorFieldId);
			}
			if(newIndex < 0)
			{
				newIndex = loc.index + loc.length + 1;
			}

			var oldIndex = this.getFieldIndex(fieldId);
			if(oldIndex >= 0 && this.setFieldIndex(fieldId, newIndex, oldIndex))
			{
				newIndex = this.getFieldIndex(fieldId);
				if(newIndex <= oldIndex)
				{
					oldIndex++;
				}

				this._dragDropUndoData =
					{
						type: "field",
						id: fieldId,
						node: fieldNode,
						contentsNode: fieldSection ? fieldSection.getContentsNode() : null,
						anchorNode: nextFieldNode,
						oldIndex: oldIndex,
						newIndex: newIndex
					};

				var undoContainer = BX(this._undoContainerId);
				if(undoContainer)
				{
					BX.cleanNode(undoContainer, false);
					undoContainer.appendChild(
						BX.create("DIV",
							{
								props: { className: "crm-view-message" },
								children:
								[
									BX.create("SPAN", { text: this.getMessage("saved") + " " }),
									BX.create("A",
										{
											props: { href: "#" },
											events: { click: BX.delegate(this._onSettingsChangeUndo, this) },
											text: this.getMessage("undo")
										}
									)
								]
							}
						)
					);
				}
			}
		},
		processDraggedSectionDrop: function(draggedItem)
		{
			var contextData = draggedItem.getContextData();
			var contextId = BX.type.isNotEmptyString(contextData["contextId"]) ? contextData["contextId"] : "";

			if(contextId !== this._draggableSectionContextId)
			{
				return;
			}

			var section = typeof(contextData["section"]) !== "undefined" ?  contextData["section"] : null;
			if(!section)
			{
				return;
			}

			var sectionId = section.getId();
			var sectionNode = section.getContentsNode();
			var nextSectionNode = this._findNextDragDropSectionNode(sectionNode);
			var placeHolder = this.getPlaceHolder();

			this._moveDragDropSectionNode(sectionNode, placeHolder ? placeHolder.getNode() : null);

			var loc = this.getSectionLocation(sectionId);
			var newIndex = -1;
			var anchorSectionId = placeHolder ? placeHolder.getSectionId() : "";
			if(anchorSectionId !== "")
			{
				newIndex = this.getFieldIndex(anchorSectionId);
			}
			if(newIndex < 0)
			{
				var sectionIds = this.getSectionIds();
				if(sectionIds.length > 0)
				{
					var lastSectionLoc = this.getSectionLocation(sectionIds[sectionIds.length - 1]);
					newIndex = lastSectionLoc.index + lastSectionLoc.length + 1;
				}
				else
				{
					newIndex = loc.index;
				}
			}

			var oldIndex = loc.index;
			// Store settings
			if(oldIndex >= 0 && this.setSectionIndex(sectionId, newIndex, loc))
			{
				loc = this.getSectionLocation(sectionId);
				newIndex = loc.index;
				if(newIndex <= oldIndex)
				{
					oldIndex += loc.length + 1;
				}

				this._dragDropUndoData =
					{
						type: "section",
						id: sectionId,
						node: sectionNode,
						anchorNode: nextSectionNode,
						oldIndex: oldIndex,
						newIndex: newIndex
					};

				var undoContainer = BX(this._undoContainerId);
				if(undoContainer)
				{
					BX.cleanNode(undoContainer, false);
					undoContainer.appendChild(
						BX.create("DIV",
							{
								props: { className: "crm-view-message" },
								children:
								[
									BX.create("SPAN", { text: this.getMessage("saved") + " " }),
									BX.create("A",
										{
											props: { href: "#" },
											events: { click: BX.delegate(this._onSettingsChangeUndo, this) },
											text: this.getMessage("undo")
										}
									)
								]
							}
						)
					);
				}
			}
		},
		getFieldDropCallback: function()
		{
			return this._fieldDropHandler;
		},
		getSectionDropCallback: function()
		{
			return this._sectionDropHandler;
		},
		createPlaceHolder: function(info)
		{
			//var sections = info["sections"];
			var sectionId = info["id"];

			var sectionIndex = info["index"];
			var section = sectionId !== "" && this._sectionSettings.hasOwnProperty(sectionId)
				? this._sectionSettings[sectionId] : null;

			var wrapper = BX(this._sectionWrapperId);
			if(this._placeHolder)
			{
				if(this._placeHolder.getSectionIndex() === sectionIndex)
				{
					return this._placeHolder;
				}

				wrapper.removeChild(this._placeHolder.getNode());
				this._placeHolder = null;
			}

			var node = BX.create("TABLE", { attrs: { className: "crm-offer-info-table" } });
			if(section)
			{
				wrapper.insertBefore(node, section.getContentsNode());
			}
			else
			{
				wrapper.appendChild(node);
			}

			this._placeHolder = BX.CrmFormSectionPlaceholder.create(
				{
					manager: this,
					node: node,
					sectionId: sectionId,
					sectionIndex: sectionIndex
				}
			);
			this._placeHolder.layout();
			return this._placeHolder;
		},
		getPlaceHolder: function()
		{
			return this._placeHolder;
		},
		removePlaceHolder: function()
		{
			if(this._placeHolder)
			{
				var wrapper = BX(this._sectionWrapperId);
				wrapper.removeChild(this._placeHolder.getNode());
				this._placeHolder = null;
			}
		},
		reloadForm: function()
		{
			var eventArgs = { "cancel": false };
			BX.onCustomEvent(this, "CrmFormSettingManagerReloadForm", [ this, eventArgs ]);

			if(eventArgs["cancel"])
			{
				return;
			}

			if(this._form !== null)
			{
				this._form.Reload();
			}
		},
		_onFieldDrop: function(dragContainer, draggedItem, x, y)
		{
			this.processDraggedFieldDrop(dragContainer, draggedItem);
		},
		_onSectionDrop: function(dragContainer, draggedItem, x, y)
		{
			this.processDraggedSectionDrop(draggedItem);
		},
		_onSettingsSave: function()
		{
			if(this._isSettingsApplied)
			{
				BX.closeWait();
				if(this._needToReload)
				{
					this.reloadForm();
				}
				return;
			}

			this._form.EnableSettings(true, BX.delegate(this._onSettingsApply, this));
		},
		_onSettingsApply: function()
		{
			BX.closeWait();
			this._isSettingsApplied = true;

			if(this._needToReload)
			{
				this.reloadForm();
			}
		},
		_onSettingsDisable: function()
		{
			BX.closeWait();
			this._isSettingsApplied = false;

			if(this._needToReload)
			{
				this.reloadForm();
			}
		},
		_onResetMenuItemClick: function()
		{
			this._closeMenu();
			this.reset();
		},
		_onSaveForAllMenuItemClick: function()
		{
			this._closeMenu();
			this.save(true);
		},
		_onMenuButtonClick: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			this._openMenu();
			return BX.PreventDefault(e);
		},
		_openMenu: function()
		{
			if(this._isMenuShown)
			{
				return;
			}

			var menuItems =
			[
				{
					id: "reset",
					text: this.getMessage("resetMenuItem"),
					onclick: BX.delegate(this._onResetMenuItemClick, this)
				}
			];

			if(this.getSetting("canSaveSettingsForAll", false))
			{
				menuItems.push(
					{
						id: "saveForAll",
						text: this.getMessage("saveForAllMenuItem"),
						onclick: BX.delegate(this._onSaveForAllMenuItemClick, this)
					}
				);
			}

			if(typeof(BX.PopupMenu.Data[this._menuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._menuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._menuId];
			}

			this._menu = BX.PopupMenu.create(
				this._menuId,
				this._menuButton,
				menuItems,
				{
					autoHide: true,
					offsetTop: 0,
					offsetLeft: 0,
					angle:
					{
						position: "top",
						offset: 10
					},
					events:
					{
						onPopupClose : BX.delegate(this._onMenuClose, this)
					}
				}
			);

			this._menu.popupWindow.show();
			this._isMenuShown = true;
		},
		_closeMenu: function()
		{
			if(this._menu && this._menu.popupWindow)
			{
				this._menu.popupWindow.close();
			}
		},
		_onMenuClose: function()
		{
			this._menu = null;
			if(typeof(BX.PopupMenu.Data[this._menuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._menuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._menuId];
			}
			this._isMenuShown = false;
		},
		_onSettingsChangeUndo: function(e)
		{
			if(this._dragDropUndoData !== null)
			{
				var item = this._dragDropUndoData["item"];
				var id, oldIndex, newIndex, node, anchorNode;
				if(this._dragDropUndoData["type"] === "field")
				{
					id = this._dragDropUndoData["id"];
					oldIndex = this._dragDropUndoData["oldIndex"];
					newIndex = this._dragDropUndoData["newIndex"];
					node = this._dragDropUndoData["node"];
					anchorNode = this._dragDropUndoData["anchorNode"];
					var contentsNode = this._dragDropUndoData["contentsNode"];

					this._moveDragDropFieldNode(node, contentsNode, anchorNode);
					this.setFieldIndex(id, oldIndex, newIndex);
				}
				else if(this._dragDropUndoData["type"] === "section")
				{
					id = this._dragDropUndoData["id"];
					oldIndex = this._dragDropUndoData["oldIndex"];
					newIndex = this._dragDropUndoData["newIndex"];
					node = this._dragDropUndoData["node"];
					anchorNode = this._dragDropUndoData["anchorNode"];

					this._moveDragDropSectionNode(node, anchorNode);
					this.setSectionIndex(id, oldIndex);
				}

				this._dragDropUndoData = null;
			}

			BX.cleanNode(BX(this._undoContainerId), false);
			return BX.PreventDefault(e);
		}
	};
	BX.CrmFormSettingManager.replaceIdentity = function(data, name)
	{
		for(var i = 0; i < data.length; i++)
		{
			for(var j = 0; j < data[i]["fields"].length; j++)
			{
				if(BX.type.isNotEmptyString(data[i]["fields"][j][name]))
				{
					data[i]["fields"][j]["id"] = data[i]["fields"][j][name];
					delete data[i]["fields"][j][name];
				}
			}
		}
	};
	if(typeof(BX.CrmFormSettingManager.messages) === "undefined")
	{
		BX.CrmFormSettingManager.messages = {};
	}
	BX.CrmFormSettingManager.items = {};
	BX.CrmFormSettingManager.getItemById = function(id, ignoreCase)
	{
		id = BX.type.isNotEmptyString(id) ? id : "";
		if(id === "")
		{
			throw "CrmFormSettingManager: argument 'id' must be not empty string.";
		}

		if(typeof(this.items[id]) !== "undefined")
		{
			return this.items[id];
		}

		ignoreCase = !!ignoreCase;
		if(ignoreCase)
		{
			id = id.toUpperCase();
			if(typeof(this.items[id]) !== "undefined")
			{
				return this.items[id];
			}

			id = id.toLowerCase();
			if(typeof(this.items[id]) !== "undefined")
			{
				return this.items[id];
			}
		}

		return null;
	};
	BX.CrmFormSettingManager.create = function(id, settings)
	{
		var self = new BX.CrmFormSettingManager();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}
if(typeof(BX.CrmFormFieldSetting) === "undefined")
{
	BX.CrmFormFieldSetting = function()
	{
		this._id = "";
		this._settings = null;

		this._manager = null;
		this._data = null;

		this._type = "";
		this._userFieldType = "";
		this._isRQ = false;
		this._node = null;
		this._dragButton = null;
		this._editButton = null;
		this._delButton = null;
		this._labelWrapper = null;
		this._dataWrapper = null;
		this._nameInput = null;
		this._buttonWrapper = null;
		this._inShortListOptionWrapper = null;
		this._inShortListOptionEnabled = false;
		this._saveButton = null;
		this._cancelButton = null;
		this._cover = null;

		this._enableDrag = true;
		this._enableDragDropBin = true;
		this._draggableContextId = "";
		this._dragItem = null;

		this._editMode = false;
		this._isTemporary = false;

		this._editButtonClickHandler = BX.delegate(this._onEditButtonClick, this);
		this._deleteButtonClickHandler = BX.delegate(this._onDeleteButtonClick, this);
		this._saveButtonClickHandler = BX.delegate(this._onSaveButtonClick, this);
		this._cancelButtonClickHandler = BX.delegate(this._onCancelButtonClick, this);
		this._contextMenuHandler = BX.delegate(this._onContextMenu, this);

		this._nameKeyPressHandler = null;

		this._contextMenuId = "form_field_setting";
		this._isContextMenuShown = false;
	};
	BX.CrmFormFieldSetting.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(8);
			this._settings = settings ? settings : {};

			this._manager = this.getSetting("manager");
			if(!(this._manager instanceof BX.CrmFormSettingManager))
			{
				throw "Error: The 'manager' argument must be CrmFormSettingManager instance.";
			}

			this._data = this.getSetting("data");
			if(!BX.type.isPlainObject(this._data))
			{
				throw "Error: The 'data' parameter is not found in settings.";
			}

			this._type = BX.type.isNotEmptyString(this._data["type"]) ? this._data["type"] : "text";
			this._userFieldType = BX.type.isNotEmptyString(this._data["userFieldType"]) ? this._data["userFieldType"] : "";
			this._isRQ = (this._data["isRQ"] === true);
			this._inShortList = (this._data["inShortList"] === true);

			this._inShortListOptionEnabled =
				(this.getSetting("inShortListOptionEnabled", false) === true && this._isRQ);
			this._inShortListOptionWrapper = null;
			this._inShortListOptionCheckbox = null;

			this._node = this._manager.getFieldNode(id);
			if(!BX.type.isElementNode(this._node))
			{
				throw "Error: Could not find field node.";
			}

			var result = cssQuery('span.crm-offer-drg-btn', this._node);
			if(result.length > 0)
			{
				this._dragButton = result[0];
				BX.bind(this._dragButton, "contextmenu", this._contextMenuHandler);
			}

			if(this._type === "vertical_container" || this._type === "lhe")
			{
				result = cssQuery('span.crm-offer-edit-btn-wrap', this._node);
				if(result.length > 0)
				{
					var buttonWrapper = result[0];
					result = cssQuery('span.crm-offer-item-del', buttonWrapper);
					if(result.length > 0)
					{
						this._delButton = result[0];
					}

					result = cssQuery('span.crm-offer-item-edit', buttonWrapper);
					if(result.length > 0)
					{
						this._editButton = result[0];
					}
				}
			}
			else
			{
				result = cssQuery('span.crm-offer-item-del', this._node);
				if(result.length > 0)
				{
					this._delButton = result[0];
				}

				result = cssQuery('span.crm-offer-item-edit', this._node);
				if(result.length > 0)
				{
					this._editButton = result[0];
				}

				result = cssQuery('div.crm-offer-info-label-wrap', this._node);
				if(result.length > 0)
				{
					this._labelWrapper = result[0];
				}
			}

			this._enableEdit = !!this._editButton;

			result = cssQuery('div.crm-offer-info-data-wrap', this._node);
			if(result.length === 0)
			{
				throw "Error: Could not find data wrapper.";
			}
			this._dataWrapper = result[0];

			this._enableDrag = this.getSetting("enableDrag", true);
			this._enableDragDropBin = this.getSetting("enableDragDropBin", true);
			this._draggableContextId = this.getSetting("draggableContextId", "");

			this.initializeDragDropAbilities();
			this._bindEvents();

			this._isTemporary = this.getSetting("isTemporary", false);
			this._editMode = this.getSetting("editMode", false);
			if(this._editMode)
			{
				this.enableEditMode(true, true);
				this._manager.processFieldEditStart(this);
			}

			this._isVisible = this._node.style.display !== "none";
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getType: function()
		{
			return this._type;
		},
		getUserFieldType: function()
		{
			return this._userFieldType;
		},
		getName: function()
		{
			return BX.type.isNotEmptyString(this._data["name"]) ? this._data["name"] : this._id;
		},
		getManager: function()
		{
			return this._manager;
		},
		isInEditMode: function()
		{
			return this._editMode;
		},
		isTemporary: function()
		{
			return this._isTemporary;
		},
		isRequired: function()
		{
			return BX.type.isBoolean(this._data["required"]) && this._data["required"];
		},
		isPersistent: function()
		{
			return BX.type.isBoolean(this._data["persistent"]) && this._data["persistent"];
		},
		getNode: function()
		{
			return this._node;
		},
		getData: function()
		{
			return this._data;
		},
		enableEditMode: function(enable, forced)
		{
			enable = !!enable;
			forced = !!forced;
			if(this._editMode === enable && !forced)
			{
				return;
			}

			this._editMode = enable;

			if(this._type === "vertical_checkbox" || this._type === "checkbox")
			{
				this._processBooleanFieldModeChange();
			}
			else if(this._type === "vertical_container" || this._type === "lhe")
			{
				this._processContainerFieldModeChange();
			}
			else if(this._type === "address" || this._type === "multiple_address")
			{
				this._processAddressFieldModeChange();
			}
			else
			{
				this._processTextFieldModeChange();
			}
		},
		release: function(removeNode)
		{
			this.releaseDragDropAbilities();
			this._unbindEvents();

			if(this._nameKeyPressHandler)
			{
				BX.unbind(this._nameInput, "keydown", this._nameKeyPressHandler);
				this._nameKeyPressHandler = null;
			}

			this._dragButton = null;
			this._editButton = null;
			this._delButton = null;
			this._saveButton = null;
			this._cancelButton = null;

			if(removeNode && this._node)
			{
				this._node = BX.remove(this._node);
			}
		},
		remove: function()
		{
			var dlg = new BX.CDialog(
				{
					title: this.getMessage("fieldDeleteDlgTitle"),
					head: '',
					content: this.getMessage("fieldDeleteDlgContent"),
					resizable: false,
					draggable: true,
					height: 70,
					width: 300
				}
			);

			dlg.ClearButtons();
			dlg.SetButtons(
				[
					{
						title: this.getMessage("deleteButton"),
						id: 'delete',
						action: BX.delegate(this._onDeleteConfirmationButtonClick, this)
					},
					BX.CDialog.btnCancel
				]
			);
			dlg.Show();
		},
		getMessage: function(name)
		{
			var m = BX.CrmFormFieldSetting.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		//D&D abilities
		createGhostNode: function()
		{
			var node = BX.create("DIV", { attrs: { className: "crm-offer-draggable-item" } });
			node.appendChild(
				BX.create("SPAN", { attrs: { className: "crm-offer-drg-btn" } })
			);
			node.appendChild(
				BX.create("SPAN", { attrs: { className: "crm-offer-title-text" }, text: this.getName() })
			);
			return node;
		},
		initializeDragDropAbilities: function()
		{
			if(this._dragItem || !this._enableDrag)
			{
				return;
			}

			if(!this._dragButton)
			{
				throw "CrmFormFieldSetting: Could not find drag button.";
			}

			this._dragItem = BX.CrmFormFieldDragItem.create(
				this.getId(),
				{
					field: this,
					node: this._dragButton,
					contextId: this._draggableContextId,
					enableBin: this._enableDragDropBin,
					showFieldInDragMode: false,
					ghostOffset: { x: -8, y: -8 }
				}
			);
		},
		releaseDragDropAbilities: function()
		{
			if(this._dragItem)
			{
				this._dragItem.release();
				this._dragItem = null;
			}
		},
		isVisible: function()
		{
			return this._isVisible;
		},
		setVisible: function(visible)
		{
			visible = !!visible;
			if(this._isVisible === visible)
			{
				return;
			}

			this._isVisible = visible;
			this._node.style.display = visible ? "" : "none";
		},
		tryCompleteEdit: function(enableSaving)
		{
			if(!this._editMode)
			{
				return;
			}

			if(!!enableSaving)
			{
				var name = BX.util.trim(this._nameInput.value);
				if(name !== "")
				{
					this._data["name"] = name;
				}

				if (this._inShortListOptionEnabled)
				{
					if (this._inShortListOptionCheckbox)
					{
						this._data["inShortList"] = this._inShortList =
							(this._inShortListOptionCheckbox.checked === true);
					}
				}
			}

			if(this._manager.processFieldEditEnd(this))
			{
				this.enableEditMode(false);
			}
		},
		tryCancelEdit: function()
		{
			if(this._manager.processFieldEditCancelation(this))
			{
				this.enableEditMode(false);
			}
		},
		_bindEvents: function()
		{
			if(this._editButton)
			{
				BX.bind(this._editButton, "click", this._editButtonClickHandler);
			}

			if(this._delButton)
			{
				BX.bind(this._delButton, "click", this._deleteButtonClickHandler);
			}
		},
		_unbindEvents: function()
		{
			if(this._editButton)
			{
				BX.unbind(this._editButton, "click", this._editButtonClickHandler);
			}

			if(this._delButton)
			{
				BX.unbind(this._delButton, "click", this._deleteButtonClickHandler);
			}

			if(this._saveButton)
			{
				BX.unbind(this._saveButton, "click", this._saveButtonClickHandler);
			}

			if(this._cancelButton)
			{
				BX.unbind(this._cancelButton, "click", this._cancelButtonClickHandler);
			}
		},
		_processBooleanFieldModeChange: function()
		{
			if(this._editMode)
			{
				BX.addClass(this._node, "crm-offer-new-item");
				this._dataWrapper.style.display = "none";

				if(this._nameInput)
				{
					this._nameInput.style.display = "";
				}
				else
				{
					this._nameInput = BX.create(
						"INPUT",
						{
							props: { type: "text", className: "crm-offer-item-inp crm-offer-label-inp", placeholder: this.getMessage("fieldNamePlaceHolder") }
						}
					);
					this._dataWrapper.parentNode.insertBefore(this._nameInput, this._dataWrapper);
				}
				this._nameInput.value = BX.type.isNotEmptyString(this._data["name"]) ? this._data["name"] : this._id;

				this._displayInShortListOption();

				this._ensureEditButtonsCreated();
				if(this._buttonWrapper.style.display !== "")
				{
					this._buttonWrapper.style.display = "";
				}

				this._nameInput.focus();
				this._nameInput.setSelectionRange(0, this._nameInput.value.length);

				this._nameKeyPressHandler = BX.delegate(this._onNameKeyPress, this);
				BX.bind(this._nameInput, "keydown", this._nameKeyPressHandler);
			}
			else
			{
				BX.removeClass(this._node, "crm-offer-new-item");

				this._nameInput.style.display = "none";
				this._buttonWrapper.style.display = "none";
				this._dataWrapper.style.display = "";

				this._hideInShortListOption();

				var result = cssQuery('label.crm-offer-label', this._dataWrapper);
				if(result.length > 0)
				{
					result[0].innerHTML = BX.util.htmlspecialchars(BX.type.isNotEmptyString(this._data["name"]) ? this._data["name"] : this._id);
				}

				BX.unbind(this._nameInput, "keydown", this._nameKeyPressHandler);
				this._nameKeyPressHandler = null;
			}
		},
		_processAddressFieldModeChange: function()
		{
			var result = cssQuery('div.crm-offer-address-title', this._node);
			if(result.length === 0)
			{
				throw "Error: Could not find title container.";
			}
			var titleContainer = result[0];

			result = cssQuery('div.crm-offer-addres-title-contents-wrapper', titleContainer);
			if(result.length === 0)
			{
				throw "Error: Could not find title wrapper.";
			}
			var titleWrapper = result[0];

			if(this._editMode)
			{
				BX.addClass(this._node, "crm-offer-new-item");

				titleWrapper.style.display = "none";
				if(this._nameInput)
				{
					this._nameInput.style.display = "";
				}
				else
				{
					this._nameInput = BX.create(
						"INPUT",
						{
							props: { type: "text", className: "crm-offer-item-inp", placeholder: this.getMessage("fieldNamePlaceHolder") }
						}
					);
					titleContainer.appendChild(this._nameInput);
				}
				this._nameInput.value = BX.type.isNotEmptyString(this._data["name"]) ? this._data["name"] : this._id;

				this._displayInShortListOption();

				this._ensureEditButtonsCreated();
				if(this._buttonWrapper.style.display !== "")
				{
					this._buttonWrapper.style.display = "";
				}

				this._nameInput.focus();
				this._nameInput.setSelectionRange(0, this._nameInput.value.length);

				if(this._manager.getFormMode() === BX.CrmFormMode.edit)
				{
					if(this._cover)
					{
						this._cover.style.display = "";
					}
					else
					{
						this._cover = BX.create("DIV", { props: { className: "crm-offer-disable-cover" } });
						this._dataWrapper.appendChild(this._cover);
					}
				}
			}
			else
			{
				this._cover.style.display = "none";

				BX.removeClass(this._node, "crm-offer-new-item");

				this._nameInput.style.display = "none";
				this._buttonWrapper.style.display = "none";
				titleWrapper.style.display = "";

				this._hideInShortListOption();

				result = cssQuery('span.crm-offer-address-title-contents', titleWrapper);
				if(result.length === 0)
				{
					throw "Error: Could not find title content.";
				}
				result[0].innerHTML = BX.util.htmlspecialchars(BX.type.isNotEmptyString(this._data["name"]) ? this._data["name"] : this._id);
			}
		},
		_processContainerFieldModeChange: function()
		{
			var result = cssQuery('div.crm-offer-editor-title', this._node);
			if(result.length === 0)
			{
				throw "Error: Could not find title container.";
			}
			var titleContainer = result[0];

			result = cssQuery('div.crm-offer-editor-title-contents-wapper', titleContainer);
			if(result.length === 0)
			{
				throw "Error: Could not find title wrapper.";
			}
			var titleWrapper = result[0];

			if(this._editMode)
			{
				BX.addClass(this._node, "crm-offer-new-item");

				titleWrapper.style.display = "none";
				if(this._nameInput)
				{
					this._nameInput.style.display = "";
				}
				else
				{
					this._nameInput = BX.create(
						"INPUT",
						{
							props: { type: "text", className: "crm-offer-item-inp", placeholder: this.getMessage("fieldNamePlaceHolder") }
						}
					);
					titleContainer.appendChild(this._nameInput);
				}
				this._nameInput.value = BX.type.isNotEmptyString(this._data["name"]) ? this._data["name"] : this._id;

				this._displayInShortListOption();

				this._ensureEditButtonsCreated();
				if(this._buttonWrapper.style.display !== "")
				{
					this._buttonWrapper.style.display = "";
				}

				this._nameInput.focus();
				//this._nameInput.select();
				this._nameInput.setSelectionRange(0, this._nameInput.value.length);

				if(this._manager.getFormMode() === BX.CrmFormMode.edit)
				{
					if(this._cover)
					{
						this._cover.style.display = "";
					}
					else
					{
						this._cover = BX.create("DIV", {props: {className: "crm-offer-disable-cover"}});
						this._dataWrapper.appendChild(this._cover);
					}
				}
			}
			else
			{
				this._cover.style.display = "none";

				BX.removeClass(this._node, "crm-offer-new-item");

				this._nameInput.style.display = "none";
				this._buttonWrapper.style.display = "none";
				titleWrapper.style.display = "";

				this._hideInShortListOption();

				result = cssQuery('span.crm-offer-editor-title-contents', titleWrapper);
				if(result.length === 0)
				{
					throw "Error: Could not find title content.";
				}
				result[0].innerHTML = BX.util.htmlspecialchars(BX.type.isNotEmptyString(this._data["name"]) ? this._data["name"] : this._id);
			}
		},
		_processTextFieldModeChange: function()
		{
			if(this._editMode)
			{
				BX.addClass(this._node, "crm-offer-new-item");

				this._labelWrapper.style.display = "none";
				if(this._nameInput)
				{
					this._nameInput.style.display = "";
				}
				else
				{
					this._nameInput = BX.create(
						"INPUT",
						{
							props: { type: "text", className: "crm-offer-item-inp", placeholder: this.getMessage("fieldNamePlaceHolder") }
						}
					);
					this._labelWrapper.parentNode.insertBefore(this._nameInput, this._labelWrapper);
				}
				this._nameInput.value = BX.type.isNotEmptyString(this._data["name"]) ? this._data["name"] : this._id;

				this._displayInShortListOption();

				this._ensureEditButtonsCreated();
				if(this._buttonWrapper.style.display !== "")
				{
					this._buttonWrapper.style.display = "";
				}

				this._nameInput.focus();
				//this._nameInput.select();
				this._nameInput.setSelectionRange(0, this._nameInput.value.length);

				if(this._manager.getFormMode() === BX.CrmFormMode.edit)
				{
					if(this._cover)
					{
						this._cover.style.display = "";
					}
					else
					{
						this._cover = BX.create("DIV", {props: {className: "crm-offer-disable-cover"}});
						this._dataWrapper.appendChild(this._cover);
					}
				}

				this._nameKeyPressHandler = BX.delegate(this._onNameKeyPress, this);
				BX.bind(this._nameInput, "keydown", this._nameKeyPressHandler);
			}
			else
			{
				if(this._cover)
				{
					this._cover.style.display = "none";
				}

				BX.removeClass(this._node, "crm-offer-new-item");

				this._nameInput.style.display = "none";
				this._buttonWrapper.style.display = "none";
				this._labelWrapper.style.display = "";

				this._hideInShortListOption();

				var result = cssQuery('span.crm-offer-info-label', this._labelWrapper);
				if(result.length > 0)
				{
					result[0].innerHTML = BX.util.htmlspecialchars(BX.type.isNotEmptyString(this._data["name"]) ? this._data["name"] : this._id);
				}

				BX.unbind(this._nameInput, "keydown", this._nameKeyPressHandler);
				this._nameKeyPressHandler = null;
			}
		},
		_ensureEditButtonsCreated: function()
		{
			if(this._buttonWrapper)
			{
				return;
			}

			this._saveButton = BX.create(
				"SPAN",
				{
					props: { className: "webform-small-button" },
					children:
					[
						BX.create("SPAN", { props: { className: "webform-small-button-left" } }),
						BX.create(
							"SPAN",
							{
								props: { className: "webform-small-button-text" },
								text: this.getMessage("saveButton")
							}
						),
						BX.create("SPAN", { props: { className: "webform-small-button-right" } })
					]
				}
			);
			BX.bind(this._saveButton, "click", this._saveButtonClickHandler);

			this._cancelButton = BX.create(
				"SPAN",
				{
					props: { className: "crm-offer-cancel-link" },
					text:  this.getMessage("cancelButton")
				}
			);
			BX.bind(this._cancelButton, "click", this._cancelButtonClickHandler);

			this._buttonWrapper = BX.create(
				"DIV",
				{
					props: { className: "crm-offer-item-btn-wrap" },
					children: [ this._saveButton, this._cancelButton ]
				}
			);

			this._dataWrapper.parentNode.appendChild(this._buttonWrapper);
		},
		_displayInShortListOption: function()
		{
			if (!this._inShortListOptionEnabled)
				return;

			if (!this._inShortListOptionWrapper)
			{
				var checkBoxId = this._id + "_inShortListCheckbox";
				this._inShortListOptionWrapper = BX.create("DIV", {
					"children": [
						this._inShortListOptionCheckbox = BX.create("INPUT", {
							"attrs": {"id": checkBoxId, "type": "checkbox"},
							"props": {"checked": this._inShortList}
						}),
						BX.create("LABEL", {
							"attrs": {"for": checkBoxId},
							"html": BX.util.htmlspecialchars(this.getMessage("inShortListOptionTitle"))
						})
					]
				});
				this._dataWrapper.parentNode.appendChild(this._inShortListOptionWrapper);
			}

			if(this._inShortListOptionWrapper.style.display !== "")
			{
				this._inShortListOptionWrapper.style.display = "";
			}
		},
		_hideInShortListOption: function()
		{
			if (this._inShortListOptionEnabled)
			{
				if (this._inShortListOptionWrapper)
					this._inShortListOptionWrapper.style.display = "none";
				if (this._inShortListOptionCheckbox)
					this._inShortListOptionCheckbox.checked = this._inShortList;
			}
		},
		_onEditButtonClick: function(e)
		{
			this.enableEditMode(true);
			this._manager.processFieldEditStart(this);
		},
		_onEditMenuItemClick: function(e)
		{
			this._closeContextMenu();

			this.enableEditMode(true);
			this._manager.processFieldEditStart(this);
		},
		_onDeleteButtonClick: function(e)
		{
			this.remove();
		},
		_onDeleteMenuItemClick: function(e)
		{
			this._closeContextMenu();

			this.remove();
		},
		_onDeleteConfirmationButtonClick: function(e)
		{
			BX.WindowManager.Get().Close();
			this._manager.processFieldRemove(this);
		},
		_onSaveButtonClick: function(e)
		{
			this.tryCompleteEdit(true);
		},
		_onCancelButtonClick: function(e)
		{
			this.tryCancelEdit();
		},
		_onNameKeyPress: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			if(e.keyCode == 13)
			{
				//Enter
				this.tryCompleteEdit(true);
				return BX.eventReturnFalse(e);
			}
			else if(e.keyCode == 27)
			{
				//Esc
				this.tryCancelEdit();
				return BX.eventReturnFalse(e);
			}

			return true;
		},
		_onContextMenu: function(e)
		{
			this._openContextMenu();
			return BX.eventReturnFalse(e);
		},
		_openContextMenu: function()
		{
			if(this._isContextMenuShown)
			{
				return;
			}

			var currentMenu = BX.PopupMenu.getMenuById(this._contextMenuId);
			if(currentMenu)
			{
				currentMenu.popupWindow.close();
			}

			var menuItems = [];
			if(this._enableEdit)
			{
				menuItems.push(
					{
						id: "edit",
						text: this.getMessage("editMenuItem"),
						onclick: BX.delegate(this._onEditMenuItemClick, this)
					}
				);
			}

			menuItems.push(
				{
					id: "delete",
					text: this.getMessage("deleteMenuItem"),
					onclick: BX.delegate(this._onDeleteMenuItemClick, this)
				}
			);

			this._contextMenu = BX.PopupMenu.create(
				this._contextMenuId,
				this._dragButton,
				menuItems,
				{
					autoHide: true,
					offsetTop: 0,
					offsetLeft: 0,
					angle: { position: "top", offset: 10 },
					events: { onPopupClose : BX.delegate(this._onContextMenuClose, this) }
				}
			);

			this._contextMenu.popupWindow.show();
			this._isContextMenuShown = true;
		},
		_closeContextMenu: function()
		{
			if(this._contextMenu && this._contextMenu.popupWindow)
			{
				this._contextMenu.popupWindow.close();
			}
		},
		_onContextMenuClose: function()
		{
			this._contextMenu = null;
			if(typeof(BX.PopupMenu.Data[this._contextMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._contextMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._contextMenuId];
			}
			this._isContextMenuShown = false;
		}
	};
	if(typeof(BX.CrmFormFieldSetting.messages) === "undefined")
	{
		BX.CrmFormFieldSetting.messages = {};
	}
	BX.CrmFormFieldSetting.create = function(id, settings)
	{
		var self = new BX.CrmFormFieldSetting();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmFormSectionSetting) === "undefined")
{
	BX.CrmFormSectionSetting = function()
	{
		this._id = "";
		this._settings = null;

		this._manager = null;
		this._data = null;

		this._titleNode = null;
		this._contentsNode = null;
		this._buttonsNode = null;
		this._addSectionButton = null;
		this._addFieldButton = null;
		this._restoreFieldButton = null;
		this._addFieldMenuId = "";
		this._restoreFieldMenuId = "";

		this._enableDrag = true;
		this._enableDragDropBin = true;
		this._draggableFieldContextId = "";
		this._draggableSectionContextId = "";
		this._dragButton = null;
		this._editButton = null;
		this._editMode = false;
		this._titleLabel = null;
		this._titleInput = null;

		this._nodeMouseOverHandler = BX.delegate(this._onNodeMouseOver, this);
		this._nodeMouseOutHandler = BX.delegate(this._onNodeMouseOut, this);
		this._addSectionButtonClickHandler = BX.delegate(this._onAddSectionButtonClick, this);
		this._addFieldButtonClickHandler = BX.delegate(this._onAddFieldButtonClick, this);
		this._restoreFieldButtonClickHandler = BX.delegate(this._onRestoreFieldButtonClick, this);
		this._editButtonClickHandler = BX.delegate(this._onEditButtonClick, this);
		this._deleteButtonClickHandler = BX.delegate(this._onDeleteButtonClick, this);
		this._documentClickHandler = BX.delegate(this._onDocumentClick, this);
		this._titleKeyPressHandler = BX.delegate(this._onTitleKeyPress, this);

		this._addFieldMenu = null;
		this._isAddFieldMenuShown = false;
		this._restoreFieldMenu = null;
		this._isRestoreFieldMenuShown = false;
		this._mouseTimeoutId = 0;
		this._isMouseOver = false;

		this._isVisible = true;
		this._isFieldsVisible = true;
		this._isButtonsVisible = true;

		this._placeHolder = null;
		this._dragContainer = null;
		this._dragItem = null;

		this._contextMenuHandler = BX.delegate(this._onContextMenu, this);
		this._contextMenuId = "";
		this._isContextMenuShown = false;

		this._isTemporary = false;
		this._canCreateSection = false;
		this._canCreateUserField = false;
		this._canRestoreField = false;
	};
	BX.CrmFormSectionSetting.prototype =
	{
		initialize: function(id, settings)
		{
			if(!BX.type.isNotEmptyString(id))
			{
				throw "Error: The 'id' argument is not defined or empty.";
			}
			this._id = id;

			this._settings = settings ? settings : {};

			this._isTemporary = this.getSetting("isTemporary", false);

			this._manager = this.getSetting("manager");
			if(!(this._manager instanceof BX.CrmFormSettingManager))
			{
				throw "Error: The 'manager' argument must be CrmFormSettingManager instance.";
			}

			this._data = this.getSetting("data");
			if(!BX.type.isPlainObject(this._data))
			{
				throw "Error: The 'data' parameter is not found in settings.";
			}

			this._contentsNode = this._manager.getSectionNode(id);
			if(!BX.type.isElementNode(this._contentsNode))
			{
				throw "Error: Could not find section contents node.";
			}

			var result = cssQuery('div.crm-offer-title', this._contentsNode);
			if(result.length > 0)
			{
				this._titleNode = result[0];
			}
			else
			{
				throw "Error: Could not find section title node.";
			}

			if(this._isTemporary)
			{
				this._titleNode.style.display = "none";
			}

			this._canCreateSection = this.getSetting("canCreateSection", this._manager.canCreateSection());
			this._canCreateUserField = !this._isTemporary && this.getSetting("canCreateUserField", this._manager.canCreateUserField());
			this._canRestoreField = !this._isTemporary && this.getSetting("canRestoreField", this._manager.hasHiddenFields());

			this._buttonsNode = this._manager.getSectionButtonWrapperNode(id);
			if(!BX.type.isElementNode(this._buttonsNode))
			{
				throw "Error: Could not find section buttons node.";
			}

			this._addSectionButton = this._manager.getSectionButtonNode(id, "add_section");
			if(this._canCreateSection && !BX.type.isElementNode(this._addSectionButton))
			{
				throw "Error: Could not find section 'Add Section' button.";
			}

			this._addFieldButton = this._manager.getSectionButtonNode(id, "add_field");
			if(this._canCreateUserField && !BX.type.isElementNode(this._addFieldButton))
			{
				throw "Error: Could not find section 'Add Field' button.";
			}

			this._restoreFieldButton = this._manager.getSectionButtonNode(id, "restore_field");
			if(this._canRestoreField && !BX.type.isElementNode(this._restoreFieldButton))
			{
				throw "Error: Could not find section 'Show Field' button.";
			}

			this._addFieldMenuId = this._manager.resolveNodeId(id, "add_field_menu");
			this._restoreFieldMenuId = this._manager.resolveNodeId(id, "restore_field_menu");

			result = cssQuery('span.crm-offer-drg-btn', this._contentsNode);
			if(result.length > 0)
			{
				this._dragButton = result[0];
				this._contextMenuId = this._id.toLowerCase() + "_context_menu";
				BX.bind(this._dragButton, "contextmenu", this._contextMenuHandler);
			}
			if(!BX.type.isElementNode(this._dragButton))
			{
				throw "Error: Could not find section 'Drag' button.";
			}

			this._editButton = this._manager.getSectionButtonNode(id, "edit");
			this._enableEdit = !!this._editButton;

			this._deleteButton = this._manager.getSectionButtonNode(id, "delete");
			if(!BX.type.isElementNode(this._deleteButton))
			{
				throw "Error: Could not find section 'Delete' button.";
			}

			result = cssQuery('span.crm-offer-title-text', this._titleNode);
			if(result.length === 0)
			{
				throw "Error: Could not find title label.";
			}
			this._titleLabel = result[0];

			this._enableDrag = this.getSetting("enableDrag", true);
			this._enableDragDropBin = this.getSetting("enableDragDropBin", true);
			this._draggableFieldContextId = this.getSetting("draggableFieldContextId", "");
			this._draggableSectionContextId = this.getSetting("draggableSectionContextId", "");

			this.initializeDragDropAbilities();
			this._bindEvents();

			this._editMode = !this._isTemporary && this.getSetting("editMode", false);
			if(this._editMode)
			{
				if(this._editMode = this._manager.processSectionEditStart(this))
				{
					this.enableEditMode(true, true);
				}
			}
		},
		release: function(removeNode)
		{
			this._unbindEvents();

			if(this._editMode)
			{
				this._enableDocumentClick(false);
				BX.unbind(this._titleInput, "keydown", this._titleKeyPressHandler);
			}

			if(removeNode && this._contentsNode)
			{
				this._contentsNode = BX.remove(this._contentsNode);
			}
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getName: function()
		{
			return BX.type.isNotEmptyString(this._data["name"]) ? this._data["name"] : this._id;
		},
		getManager: function()
		{
			return this._manager;
		},
		getTitleNode: function()
		{
			return this._titleNode;
		},
		getContentsNode: function()
		{
			return this._contentsNode;
		},
		getData: function()
		{
			return this._data;
		},
		getAssociatedField: function()
		{
			return BX.type.isPlainObject(this._data['associatedField']) ? this._data['associatedField'] : null;
		},
		getMessage: function(name)
		{
			var m = BX.CrmFormSectionSetting.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		isTemporary: function()
		{
			return this._isTemporary;
		},
		enableEditMode: function(enable, forced)
		{
			if(this._isTemporary)
			{
				return;
			}

			enable = !!enable;
			forced = !!forced;
			if(this._editMode === enable && !forced)
			{
				return;
			}

			this._editMode = enable;
			if(this._editMode)
			{
				this._titleLabel.style.display = "none";
				if(this._titleInput)
				{
					this._titleInput.style.display = "";
				}
				else
				{
					this._titleInput = BX.create(
						"INPUT",
						{
							props: { type: "text", className: "crm-item-table-inp", placeholder: this.getMessage("sectionTitlePlaceHolder") }
						}
					);
					this._titleInput.value = BX.type.isNotEmptyString(this._data["name"]) ? this._data["name"] : this._id;
					this._titleNode.insertBefore(this._titleInput, this._titleLabel);
				}

				this._titleInput.focus();
				//this._titleInput.select();
				this._titleInput.setSelectionRange(0, this._titleInput.value.length);

				this._enableDocumentClick(true);
				BX.bind(this._titleInput, "keydown", this._titleKeyPressHandler);
			}
			else
			{
				var v = BX.type.isNotEmptyString(this._data["name"]) ? this._data["name"] : "";
				if(this._titleInput.value !== v)
				{
					this._titleInput.value = v;
				}
				this._titleLabel.innerHTML = BX.util.htmlspecialchars(v !== "" ? v : this._id);

				this._titleInput.style.display = "none";
				this._titleLabel.style.display = "";

				this._enableDocumentClick(false);
				BX.unbind(this._titleInput, "keydown", this._titleKeyPressHandler);
			}
		},
		getFields: function()
		{
			var result = [];
			var fieldIds = this._manager.getSectionFieldIds(this._id);
			for(var i = 0; i < fieldIds.length; i++)
			{
				var field = this._manager.getField(fieldIds[i]);
				if(field)
				{
					result.push(field);
				}
			}
			return result;
		},
		setVisible: function(visible)
		{
			visible = !!visible;
			if(this._isVisible === visible)
			{
				return;
			}
			this._isVisible = visible;
			this._contentsNode.style.display = visible ? "" : "none";
		},
		setFieldsVisible: function(visible)
		{
			visible = !!visible;
			if(this._isFieldsVisible === visible)
			{
				return;
			}
			this._isFieldsVisible = visible;

			var fieldIds = this._manager.getSectionFieldIds(this._id);
			for(var i = 0; i < fieldIds.length; i++)
			{
				var field = this._manager.getField(fieldIds[i]);
				if(field)
				{
					field.setVisible(visible);
				}
			}
		},
		setButtonsVisible: function(visible)
		{
			visible = !!visible;
			if(this._isButtonsVisible === visible)
			{
				return;
			}
			this._isButtonsVisible = visible;
			this._buttonsNode.style.display = visible ? "" : "none";
		},
		processFieldRemove: function(field)
		{
			if(!this._isTemporary)
			{
				this._canRestoreField = this.getSetting("canRestoreField", this._manager.hasHiddenFields());
			}
		},
		//D&D abilities
		createGhostNode: function()
		{
			var node = BX.create("DIV", { attrs: { className: "crm-offer-draggable-item" } });
			node.appendChild(
				BX.create("SPAN", { attrs: { className: "crm-offer-drg-btn" } })
			);
			node.appendChild(
				BX.create("SPAN", { attrs: { className: "crm-offer-title-text" }, text: this.getName() })
			);
			return node;
		},
		initializeDragDropAbilities: function()
		{
			if(!this._dragContainer)
			{
				this._dragContainer = BX.CrmFormFieldDragContainer.create(
					this.getId(),
					{
						section: this,
						node: this._contentsNode,
						contextId: this._draggableFieldContextId,
						priority: this._manager.getDragPriority()
					}
				);
				this._dragContainer.addDragFinishListener(this._manager.getFieldDropCallback());
			}

			if(this._enableDrag && !this._dragItem)
			{
				this._dragItem = BX.CrmFormSectionDragItem.create(
					this.getId(),
					{
						section: this,
						node: this._dragButton,
						contextId: this._draggableSectionContextId,
						enableBin: this._enableDragDropBin,
						showSectionInDragMode: false,
						showFieldsInDragMode: false
					}
				);
			}
		},
		releaseDragDropAbilities: function()
		{
			if(this._dragContainer)
			{
				this._dragContainer.removeDragFinishListener(this._itemDropHandler);
				this._dragContainer.release();
				this._dragContainer = null;
			}

			if(this._dragItem)
			{
				this._dragItem.release();
				this._dragItem = null;
			}
		},
		createPlaceHolder: function(info)
		{
			var fields = info["fields"];
			var fieldId = info["id"];
			var fieldIndex = info["index"];

			var qty = fields.length;
			var actualIndex = fieldIndex;
			if(fieldIndex >= 0)
			{
				//process first header row
				actualIndex = fieldIndex + 1;
			}
			else
			{
				//process last menu row
				actualIndex = qty + 1;
			}

			if(this._placeHolder)
			{
				if(this._placeHolder.getFieldIndex() === fieldIndex)
				{
					return this._placeHolder;
				}

				this._contentsNode.deleteRow(this._placeHolder.getNode().rowIndex);
				this._placeHolder = null;
			}

			this._placeHolder = BX.CrmFormFieldPlaceholder.create(
				{
					section: this,
					node: this._contentsNode.insertRow(actualIndex),
					fieldId: fieldId,
					fieldIndex: fieldIndex
				}
			);
			this._placeHolder.layout();
			return this._placeHolder;
		},
		getPlaceHolder: function()
		{
			return this._placeHolder;
		},
		removePlaceHolder: function()
		{
			if(this._placeHolder)
			{
				this._contentsNode.deleteRow(this._placeHolder.getNode().rowIndex);
				this._placeHolder = null;
			}
		},
		remove: function()
		{
			var dlg = new BX.CDialog(
				{
					title: this.getMessage("sectionDeleteDlgTitle"),
					head: '',
					content: this.getMessage("sectionDeleteDlgContent"),
					resizable: false,
					draggable: true,
					height: 70,
					width: 300
				}
			);

			dlg.ClearButtons();
			dlg.SetButtons(
				[
					{
						title: this.getMessage("deleteButton"),
						id: 'delete',
						action: BX.delegate(this._onDeleteConfirmationButtonClick, this)
					},
					BX.CDialog.btnCancel
				]
			);
			dlg.Show();
		},
		_bindEvents: function()
		{
			BX.bind(this._titleNode, "mouseover", this._nodeMouseOverHandler);
			BX.bind(this._titleNode, "mouseout", this._nodeMouseOutHandler);

			BX.bind(this._contentsNode, "mouseover", this._nodeMouseOverHandler);
			BX.bind(this._contentsNode, "mouseout", this._nodeMouseOutHandler);

			if(this._addSectionButton)
			{
				BX.bind(this._addSectionButton, "click", this._addSectionButtonClickHandler);
			}
			if(this._addFieldButton)
			{
				BX.bind(this._addFieldButton, "click", this._addFieldButtonClickHandler);
			}
			if(this._restoreFieldButton)
			{
				BX.bind(this._restoreFieldButton, "click", this._restoreFieldButtonClickHandler);
			}

			BX.bind(this._editButton, "click", this._editButtonClickHandler);
			BX.bind(this._deleteButton, "click", this._deleteButtonClickHandler);
		},
		_unbindEvents: function()
		{
			BX.unbind(this._titleNode, "mouseover", this._nodeMouseOverHandler);
			BX.unbind(this._titleNode, "mouseout", this._nodeMouseOutHandler);

			BX.unbind(this._contentsNode, "mouseover", this._nodeMouseOverHandler);
			BX.unbind(this._contentsNode, "mouseout", this._nodeMouseOutHandler);

			if(this._addSectionButton)
			{
				BX.unbind(this._addSectionButton, "click", this._addSectionButtonClickHandler);
			}
			if(this._addFieldButton)
			{
				BX.unbind(this._addFieldButton, "click", this._addFieldButtonClickHandler);
			}
			if(this._restoreFieldButton)
			{
				BX.unbind(this._restoreFieldButton, "click", this._restoreFieldButtonClickHandler);
			}

			BX.unbind(this._editButton, "click", this._editButtonClickHandler);
			BX.unbind(this._deleteButton, "click", this._deleteButtonClickHandler);
		},
		_enableDocumentClick: function(enable)
		{
			if(enable)
			{
				var self = this;
				window.setTimeout(function(){ BX.bind(document, "click", self._documentClickHandler); }, 0);
			}
			else
			{
				BX.unbind(document, "click", this._documentClickHandler);
			}
		},
		_onAddFieldMenuItemClick: function(event, item)
		{
			this._closeAddFieldMenu();

			var id = BX.type.isNotEmptyString(item["id"]) ? item["id"] : "";
			if(id === "addSection")
			{
				this._manager.createSection(this);
				return;
			}

			var type = "string";
			if(id === "addDoubleField")
			{
				type = "double";
			}
			else if(id === "addBooleanField")
			{
				type = "boolean";
			}
			else if(id === "addDatetimeField")
			{
				type = "datetime";
			}

			this._manager.createTemporaryField(type, this);
		},
		_openAddFieldMenu: function()
		{
			if(this._isAddFieldMenuShown)
			{
				return;
			}

			var canCreateUserField = this._manager.canCreateUserField();
			if(!canCreateUserField)
			{
				return;
			}

			var callback = BX.delegate(this._onAddFieldMenuItemClick, this);
			var menuItems = [];
			menuItems.push(
				{
					id: "addStringField",
					className: "crm-offer-popup-item menu-popup-no-icon",
					text: this.getMessage("createTextFiledMenuItem"),
					onclick: callback
				}
			);

			menuItems.push(
				{
					id: "addDoubleField",
					className: "crm-offer-popup-item menu-popup-no-icon",
					text: this.getMessage("createDoubleFiledMenuItem"),
					onclick: callback
				}
			);

			menuItems.push(
				{
					id: "addBooleanField",
					className: "crm-offer-popup-item menu-popup-no-icon",
					text: this.getMessage("createBooleanFiledMenuItem"),
					onclick: callback
				}
			);

			menuItems.push(
				{
					id: "addDatetimeField",
					className: "crm-offer-popup-item menu-popup-no-icon",
					text: this.getMessage("createDatetimeFiledMenuItem"),
					onclick: callback
				}
			);

			menuItems.push(
				{
					id: "addDatetimeField",
					className: "crm-offer-popup-item menu-popup-no-icon",
					text: this.getMessage("createDatetimeFiledMenuItem"),
					onclick: callback
				}
			);

			if(typeof(BX.PopupMenu.Data[this._addFieldMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._addFieldMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._addFieldMenuId];
			}

			if(typeof(BX.PopupMenu.Data[this._restoreFieldMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._restoreFieldMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._restoreFieldMenuId];
				this._restoreFieldMenu = null;
				this._isRestoreFieldMenuShown = false;
			}

			this._addFieldMenu = BX.PopupMenu.currentItem = BX.PopupMenu.create(
				this._addFieldMenuId,
				this._addFieldButton,
				menuItems,
				{
					closeByEsc: true,
					autoHide: true,
					offsetTop: 0,
					offsetLeft: 0,
					angle:
					{
						position: "top",
						offset: 10
					},
					events:
					{
						onPopupClose : BX.delegate(this._onAddFieldMenuClose, this)
					}
				}
			);

			this._addFieldMenu.popupWindow.show();
			this._isAddFieldMenuShown = true;
		},
		_closeAddFieldMenu: function()
		{
			if(this._addFieldMenu && this._addFieldMenu.popupWindow)
			{
				this._addFieldMenu.popupWindow.close();
			}
		},
		_openRestoreFieldMenu: function()
		{
			if(this._isRestoreFieldMenuShown)
			{
				return;
			}

			var menuItems = [];
			var infos = this._manager.getHiddenFieldInfos();

			if(infos.length === 0)
			{
				return;
			}

			for(var i = 0; i < infos.length; i++)
			{
				var info = infos[i];
				menuItems.push(
					{
						id: info["id"],
						className: "crm-offer-popup-item menu-popup-no-icon",
						text: info["name"],
						onclick: BX.delegate(this._onRestoreFieldMenuItemClick, this)
					}
				);
			}

			if(typeof(BX.PopupMenu.Data[this._addFieldMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._addFieldMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._addFieldMenuId];
				this._addFieldMenu = null;
				this._isAddFieldMenuShown = false;
			}

			if(typeof(BX.PopupMenu.Data[this._restoreFieldMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._restoreFieldMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._restoreFieldMenuId];
			}

			var currentMenu = BX.PopupMenu.getCurrentMenu();
			if(currentMenu)
			{
				BX.PopupMenu.destroy(currentMenu.id);
			}

			this._restoreFieldMenu = BX.PopupMenu.currentItem = BX.PopupMenu.create(
				this._restoreFieldMenuId,
				this._restoreFieldButton,
				menuItems,
				{
					offsetTop: 0,
					offsetLeft: 0,
					angle:
					{
						position: "top",
						offset: 10
					},
					events:
					{
						onPopupClose : BX.delegate(this._onRestoreFieldMenuClose, this)
					}
				}
			);

			this._restoreFieldMenu.popupWindow.show();
			this._isRestoreFieldMenuShown = true;
		},
		_onNodeMouseOver: function(e)
		{
			this._isMouseOver = true;

			if(this._mouseTimeoutId !== null)
			{
				window.clearInterval(this._mouseTimeoutId);
				this._mouseTimeoutId = null;
			}

			var enableAddField = this._canCreateSection || this._canCreateUserField;
			var enableRestoreField = this._canRestoreField;

			if(!enableAddField && !enableRestoreField)
			{
				return;
			}

			if(this._addFieldButton)
			{
				this._addFieldButton.style.display = enableAddField ? "" : "none";
			}
			if(this._restoreFieldButton)
			{
				this._restoreFieldButton.style.display = enableRestoreField ? "" : "none";
			}

			var self = this;
			this._mouseTimeoutId = window.setTimeout(
				function()
				{
					if(self._mouseTimeoutId != null)
					{
						self._mouseTimeoutId = null;

						var node = self._buttonsNode;
						if(node.style.visibility !== "visible")
						{
							node.style.visibility = "visible";
						}
					}
				},
				300
			);
		},
		_onNodeMouseOut: function(e)
		{
			this._isMouseOver = false;

			if(this._isAddFieldMenuShown || this._isRestoreFieldMenuShown)
			{
				return;
			}

			if(this._mouseTimeoutId !== null)
			{
				window.clearInterval(this._mouseTimeoutId);
				this._mouseTimeoutId = null;
			}

			var self = this;

			this._mouseTimeoutId = window.setTimeout(
				function()
				{
					if(self._mouseTimeoutId != null)
					{
						self._mouseTimeoutId = null;

						var node = self._buttonsNode;
						if(node.style.visibility !== "hidden")
						{
							node.style.visibility = "hidden";
						}
					}
				},
				300
			);
		},
		_onDocumentClick: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			var target = BX.getEventTarget(e);
			if(target && this._titleInput === target)
			{
				return;
			}

			if(!this._editMode)
			{
				BX.unbind(document, "click", this._documentClickHandler);
			}
			else
			{
				this.tryCompleteEdit(true);
			}
		},
		tryCompleteEdit: function(enableSaving)
		{
			if(!this._editMode)
			{
				return;
			}

			if(!!enableSaving)
			{
				var name = BX.util.trim(this._titleInput.value);
				if(name !== "")
				{
					this._data["name"] = name;
				}
			}

			if(this._manager.processSectionEditEnd(this))
			{
				this.enableEditMode(false);
			}
		},
		_onTitleKeyPress: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			if(e.keyCode == 13)
			{
				//Enter
				this.tryCompleteEdit(true);
				return BX.eventReturnFalse(e);
			}
			else if(e.keyCode == 27)
			{
				//Esc
				this.tryCompleteEdit(false);
				return BX.eventReturnFalse(e);
			}

			return true;
		},
		_onEditButtonClick: function(e)
		{
			if(!this._editMode && this._manager.processSectionEditStart(this))
			{
				this.enableEditMode(true);
			}
		},
		_onEditMenuItemClick: function(e)
		{
			this._closeContextMenu();

			this.enableEditMode(true);
			this._manager.processFieldEditStart(this);
		},
		_onDeleteButtonClick: function(e)
		{
			this.remove();
		},
		_onDeleteMenuItemClick: function(e)
		{
			this._closeContextMenu();

			this.remove();
		},
		_onDeleteConfirmationButtonClick: function(e)
		{
			BX.WindowManager.Get().Close();
			this._manager.processSectionRemove(this);
		},
		_onAddSectionButtonClick: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			this._manager.createSection(this);
			return BX.PreventDefault(e);
		},
		_onAddFieldButtonClick: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			this._openAddFieldMenu();
			return BX.PreventDefault(e);
		},
		_onRestoreFieldButtonClick: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			this._openRestoreFieldMenu();
			return BX.PreventDefault(e);
		},
		_onAddFieldMenuClose: function()
		{
			this._addFieldMenu = null;
			if(typeof(BX.PopupMenu.Data[this._addFieldMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._addFieldMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._addFieldMenuId];
			}
			this._isAddFieldMenuShown = false;

			if(!this._isMouseOver && this._buttonsNode.style.visibility !== "hidden")
			{
				this._buttonsNode.style.visibility = "hidden";
			}
		},
		_onRestoreFieldMenuItemClick: function(event, item)
		{
			var fieldId = BX.type.isNotEmptyString(item["id"]) ? item["id"] : "";
			if(fieldId !== "")
			{
				this._restoreFieldMenu.popupWindow.close();

				this._manager.restoreField(fieldId, this._id);
			}
		},
		_onRestoreFieldMenuClose: function()
		{
			this._restoreFieldMenu = null;
			if(typeof(BX.PopupMenu.Data[this._restoreFieldMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._restoreFieldMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._restoreFieldMenuId];
			}
			this._isRestoreFieldMenuShown = false;

			if(!this._isMouseOver && this._buttonsNode.style.visibility !== "hidden")
			{
				this._buttonsNode.style.visibility = "hidden";
			}
		},
		_onContextMenu: function(e)
		{
			this._openContextMenu();
			return BX.eventReturnFalse(e);
		},
		_openContextMenu: function()
		{
			if(this._isContextMenuShown)
			{
				return;
			}

			var menuItems = [];
			if(this._enableEdit)
			{
				menuItems.push(
					{
						id: "edit",
						text: this.getMessage("editMenuItem"),
						onclick: BX.delegate(this._onEditMenuItemClick, this)
					}
				);
			}

			menuItems.push(
				{
					id: "delete",
					text: this.getMessage("deleteMenuItem"),
					onclick: BX.delegate(this._onDeleteMenuItemClick, this)
				}
			);

			this._contextMenu = BX.PopupMenu.create(
				this._contextMenuId,
				this._dragButton,
				menuItems,
				{
					autoHide: true,
					offsetTop: 0,
					offsetLeft: 0,
					angle: { position: "top", offset: 10 },
					events: { onPopupClose : BX.delegate(this._onContextMenuClose, this) }
				}
			);

			this._contextMenu.popupWindow.show();
			this._isContextMenuShown = true;
		},
		_closeContextMenu: function()
		{
			if(this._contextMenu && this._contextMenu.popupWindow)
			{
				this._contextMenu.popupWindow.close();
			}
		},
		_onContextMenuClose: function()
		{
			this._contextMenu = null;
			if(typeof(BX.PopupMenu.Data[this._contextMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._contextMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._contextMenuId];
			}
			this._isContextMenuShown = false;
		}
	};
	if(typeof(BX.CrmFormSectionSetting.messages) === "undefined")
	{
		BX.CrmFormSectionSetting.messages = {};
	}
	BX.CrmFormSectionSetting.create = function(id, settings)
	{
		var self = new BX.CrmFormSectionSetting();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmFormUserFieldManager) === "undefined")
{
	BX.CrmFormUserFieldManager = function()
	{
		this._id = "";
		this._settings = null;

		this._canCreate = false;
		this._entityId = "";
		this._manager = null;

		this._pendingData = null;

		this._data = {};
		this._fields = {};
	};
	BX.CrmFormUserFieldManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(8);
			this._settings = settings ? settings : {};

			this._entityId = this.getSetting("entityId", "");
			if(!BX.type.isNotEmptyString(this._entityId))
			{
				throw "Error: The 'entityId' parameter is not defined in settings or empty.";
			}

			this._canCreate = this.getSetting("canCreate", false);
			this._manager = this.getSetting("manager", null);
		},
		release: function()
		{
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		canCreate: function()
		{
			return this._canCreate;
		},
		createField: function(params, temporaryId, callback)
		{
			if(!this._canCreate)
			{
				throw "Error: User is not authorized to create user fields.";
			}

			if(!BX.type.isPlainObject(params))
			{
				throw "Error: The 'params' argument must be a plain object.";
			}

			var name = BX.type.isNotEmptyString(params["name"]) ? params["name"] : "";
			if(name === "")
			{
				throw "Error: The 'name' parameter is not defined in params or empty.";
			}

			var type = BX.type.isNotEmptyString(params["type"]) ? params["type"] : "";
			if(type === "")
			{
				type = "string";
			}
			else if(type !=="string" && type !== "double" && type !== "boolean" && type !== "datetime")
			{
				throw "Error: Type '" + type + "' is not supported in current context.";
			}

			var fieldData =
			{
				"USER_TYPE_ID": type,
				"ENTITY_ID": this._entityId,
				"MULTIPLE": 'N',
				"MANDATORY": 'N',
				"SHOW_FILTER": 'Y',
				"EDIT_FORM_LABEL": name
			};

			this._pendingData =
				{
					fieldData: fieldData,
					temporaryId: BX.type.isNotEmptyString(temporaryId) ? temporaryId : "",
					callback: BX.type.isFunction(callback) ? callback : null
				};

			this._beginCreateField();
		},
		createTemporaryField: function(params)
		{
			if(!BX.type.isPlainObject(params))
			{
				throw "Error: The 'params' argument must be a plain object.";
			}

			var temporaryId = BX.util.getRandomString(8);
			params["name"] = temporaryId;
			var fieldData = this._prepareFieldData(params);

			return BX.CrmFormUserField.create(
				temporaryId,
				{
					manager: this,
					fieldData: fieldData
				}
			);
		},
		getImagePath: function()
		{
			return this.getSetting("imagePath", "/bitrix/js/main/core/images/");
		},
		getServerTime: function()
		{
			return this.getSetting("serverTime", "");
		},
		getManager: function()
		{
			return this._manager;
		},
		_prepareFieldData: function(params)
		{
			if(!BX.type.isPlainObject(params))
			{
				throw "Error: The 'params' argument must be a plain object.";
			}

			var type = BX.type.isNotEmptyString(params["type"]) ? params["type"] : "";
			if(type === "")
			{
				type = "string";
			}
			else if(type !== "string" && type !== "double" && type !== "boolean" && type !== "datetime")
			{
				throw "Error: Type '" + type + "' is not supported in current context.";
			}

			var name = BX.type.isNotEmptyString(params["name"]) ? params["name"] : "";
			if(name === "")
			{
				throw "Error: The 'name' parameter is not defined in params or empty.";
			}

			var label = BX.type.isNotEmptyString(params["label"]) ? params["label"] : "";
			if(label === "")
			{
				throw "Error: The 'label' parameter is not defined in params or empty.";
			}

			return(
				{
					"FIELD_NAME": name,
					"USER_TYPE_ID": type,
					"ENTITY_ID": this._entityId,
					"MULTIPLE": 'N',
					"MANDATORY": 'N',
					"SHOW_FILTER": 'Y',
					"EDIT_FORM_LABEL": label
				}
			);
		},
		_beginCreateField: function()
		{
			var serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(serviceUrl))
			{
				throw "Error: Could not find 'serviceUrl' parameter in settings.";
			}

			BX.ajax(
			{
				url: serviceUrl,
				method: 'POST',
				dataType: 'json',
				data:
				{
					'ACTION' : 'ADD_FIELD',
					'DATA': this._pendingData["fieldData"]
				},
				onsuccess: BX.delegate(this._onCreateFieldRequestSuccess, this),
				onfailure: BX.delegate(this._onCreateFieldRequestFailure, this)
			});

		},
		_onCreateFieldRequestSuccess: function(data)
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

			this._data[fieldName] = fieldData;

			var field = BX.CrmFormUserField.create(fieldName, { manager: this, fieldData: fieldData });
			this._fields[fieldName] = field;

			if(this._pendingData["callback"])
			{
				this._pendingData["callback"](this, this._pendingData["temporaryId"], field);
			}

			this._pendingData = null;
		},
		_onCreateFieldRequestFailure: function(data)
		{
			this._pendingData = null;
			alert("Could not create user field.");
		}
	};
	BX.CrmFormUserFieldManager.items = {};
	BX.CrmFormUserFieldManager.create = function(id, settings)
	{
		var self = new BX.CrmFormUserFieldManager();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}
if(typeof(BX.CrmFormUserField) === "undefined")
{
	BX.CrmFormUserField = function()
	{
		this._id = "";
		this._fieldData = null;
		this._settings = null;
		this._manager = this;
		this._userTypeId = "";
		this._fieldName = "";
		this._label = "";

		this._elements = {};
	};
	BX.CrmFormUserField.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(8);
			this._settings = settings ? settings : {};

			this._fieldData = this.getSetting("fieldData", null);
			if(!BX.type.isPlainObject(this._fieldData))
			{
				throw "Error: The 'fieldData' parameter is not found in settings.";
			}

			this._userTypeId = this.getParam("USER_TYPE_ID");
			if(!BX.type.isNotEmptyString(this._userTypeId))
			{
				throw "Error: The 'USER_TYPE_ID' parameter is not found in field data.";
			}

			this._fieldName = this.getParam("FIELD_NAME");
			if(!BX.type.isNotEmptyString(this._fieldName))
			{
				throw "Error: The 'FIELD_NAME' parameter is not found in field data.";
			}

			this._label = this.getParam("EDIT_FORM_LABEL");
			if(!BX.type.isNotEmptyString(this._label))
			{
				this._label = this._fieldName;
			}

			this._manager = this.getSetting("manager", null);
			if(!this._manager)
			{
				throw "Error: The 'manager' parameter is not found in settings.";
			}
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getParam: function(name)
		{
			return BX.type.isNotEmptyString(this._fieldData[name]) ? this._fieldData[name] : "";
		},
		getUserTypeId: function()
		{
			return this._userTypeId;
		},
		getFieldName: function()
		{
			return this._fieldName;
		},
		getFieldLabel: function()
		{
			return this._label;
		},
		isNeedTitle: function()
		{
			return this._userTypeId !== "boolean";
		},
		prepareLayout: function()
		{
			if(this._userTypeId === "string")
			{
				return this._prepareStringFieldLayout();
			}
			else if(this._userTypeId === "double")
			{
				return this._prepareDoubleFieldLayout();
			}
			else if(this._userTypeId === "boolean")
			{
				return this._prepareBooleanFieldLayout();
			}
			else if(this._userTypeId === "datetime")
			{
				return this._prepareDatetimeFieldLayout();
			}
			return null;
		},
		_prepareStringFieldLayout: function()
		{
			this._elements["value"] = BX.create("INPUT",
				{
					props:
					{
						className: "crm-offer-item-inp",
						type: "text",
						name: this._fieldName,
						size: 30,
						value: ""
					}
				}
			);
			return [ this._elements["value"] ];
		},
		_prepareDoubleFieldLayout: function()
		{
			this._elements["value"] = BX.create("INPUT",
				{
					props:
					{
						className: "crm-offer-item-inp",
						type: "text",
						name: this._fieldName,
						size: 30,
						value: ""
					}
				}
			);
			return [ this._elements["value"] ];
		},
		_prepareBooleanFieldLayout: function()
		{
			this._elements['value'] = BX.create("INPUT",
				{
					props:
					{
						type: "hidden",
						name: this._fieldName,
						value: "N"
					}
				}
			);

			var chbxId = this._fieldName.toLowerCase() + "_chbx";
			this._elements['checkbox'] = BX.create("INPUT",
				{
					props:
					{
						id: chbxId,
						name: this._fieldName,
						type: "checkbox",
						className: "crm-offer-checkbox",
						value: "Y"
					}
				}
			);

			this._elements["label"] =
				BX.create(
					"LABEL",
					{
						props: { className: "crm-offer-label" },
						attrs: { "for": chbxId },
						text: this._label
					}
				);

			return [this._elements["value"], this._elements["checkbox"], this._elements["label"] ];
		},
		_prepareDatetimeFieldLayout: function()
		{
			this._elements["value"] = BX.create("INPUT",
				{
					props:
					{
						className: "crm-offer-item-inp crm-item-table-date",
						type: "text",
						id: this._fieldName,
						name: this._fieldName,
						value: ""
					}
				}
			);

			BX.bind(this._elements["value"], "click", BX.delegate(this._onDateTimeIconClick, this));

			return [ this._elements["value"] ];
		},
		_onDateTimeIconClick: function(e)
		{
			BX.calendar(
				{
					node:this._elements["value"],
					field: this._fieldName,
					bTime: true,
					serverTime: this._manager.getServerTime(),
					bHideTimebar: false
				}
			);
		}
	};
	BX.CrmFormUserField.create = function(id, settings)
	{
		var self = new BX.CrmFormUserField();
		self.initialize(id, settings);
		return self;
	}
}
if(typeof(BX.CrmFormFieldRenderer) === "undefined")
{
	BX.CrmFormFieldRenderer = function() {};
	BX.CrmFormFieldRenderer.renderUserFieldRow = function(field, table, options)
	{
		if(!BX.type.isElementNode(table))
		{
			throw "Error: The 'table' argument must be DOM element.";
		}

		var prefix = BX.type.isNotEmptyString(options["prefix"]) ? options["prefix"] : "";
		var index = BX.type.isNumber(options["index"]) && options["index"] > 0 ? options["index"] : -1;
		var enableDrag = BX.type.isBoolean(options["enableDrag"]) ? options["enableDrag"] : true;

		var id = field.getFieldName();
		if(prefix !== "")
		{
			prefix += "_";
		}
		prefix += id.toLowerCase();

		var row = table.insertRow(index);
		row.id = prefix + "_wrap";
		row.className = "crm-offer-row";

		var cell = row.insertCell(-1);
		cell.className = "crm-offer-info-drg-btn";
		if(!enableDrag)
		{
			cell.style.display = "none";
		}

		cell.appendChild(
			BX.create("SPAN", { props: { className: "crm-offer-drg-btn" } })
		);

		cell = row.insertCell(-1);
		cell.className = "crm-offer-info-left";
		cell.appendChild(
			BX.create("DIV",
				{
					props: { className: "crm-offer-info-label-wrap" },
					children:
					[
						BX.create("SPAN", { props: { className: "crm-offer-info-label-alignment" } }),
						BX.create("SPAN",
							{
								props: { className: "crm-offer-info-label" },
								text: field.isNeedTitle() ? (field.getFieldLabel() + ":") : ""
							}
						)
					]
				}
			)
		);

		cell = row.insertCell(-1);
		cell.className = "crm-offer-info-right";
		cell.appendChild(
			BX.create("DIV",
				{
					props: { className: "crm-offer-info-data-wrap" },
					children: field.prepareLayout()
				}
			)
		);

		cell = row.insertCell(-1);
		cell.className = "crm-offer-info-right-btn";
		cell.appendChild(BX.create("SPAN", { props: { className: "crm-offer-item-del" } }));
		cell.appendChild(BX.create("SPAN", { props: { className: "crm-offer-item-edit" } }));

		cell = row.insertCell(-1);
		cell.className = "crm-offer-last-td";

		return row;
	};
	BX.CrmFormFieldRenderer.renderSectionTable = function(data, options)
	{
		var canCreateSection = BX.type.isBoolean(options["canCreateSection"]) ? options["canCreateSection"] : false;
		var canCreateUserField = BX.type.isBoolean(options["canCreateUserField"]) ? options["canCreateUserField"] : false;
		var canRestoreField = BX.type.isBoolean(options["canRestoreField"]) ? options["canRestoreField"] : true;
		var prefix = BX.type.isNotEmptyString(options["prefix"]) ? options["prefix"] : "";

		var id = data["id"];
		if(prefix !== "")
		{
			prefix += "_";
		}
		prefix += id.toLowerCase();

		var name = data["name"];
		var children = [];

		var table = BX.create(
			"TABLE",
			{ props: { id: prefix + "_contents", className: "crm-offer-info-table" } }
		);

		var row = table.insertRow(-1);
		row.id = id;

		var cell = row.insertCell(-1);
		cell.setAttribute("colspan", "5");
		cell.appendChild(
			BX.create(
				"DIV",
				{
					props: { className: "crm-offer-title" },
					children:
					[
						BX.create("SPAN", { props: { className: "crm-offer-drg-btn" } }),
						BX.create("SPAN", { props: { className: "crm-offer-title-text" }, text: name }),
						BX.create(
							"SPAN",
							{
								props: { className: "crm-offer-title-set-wrap" },
								text: name,
								children:
								[
									BX.create(
										"SPAN",
										{
											props:
											{
												id: prefix + "_edit",
												className: "crm-offer-title-edit"
											}
										}
									),
									BX.create(
										"SPAN",
										{
											props:
											{
												id: prefix + "_delete",
												className: "crm-offer-title-del"
											}
										}
									)
								]
							}
						)
					]
				}
			)
		);

		row = table.insertRow(-1);
		row.id = prefix + "_buttons";
		row.style.visibility = "hidden";

		cell = row.insertCell(-1);
		cell.className = "crm-offer-info-drg-btn";

		cell = row.insertCell(-1);
		cell.className = "crm-offer-info-left";

		cell = row.insertCell(-1);
		cell.className = "crm-offer-info-right";
		if (canCreateUserField)
		{
			children.push(BX.create(
				"SPAN",
				{
					props: { id: prefix + "_add_field", className: "crm-offer-info-link" },
					text: this.getMessage("addFieldButton")
				}
			));
		}
		if (canCreateSection)
		{
			children.push(BX.create(
				"SPAN",
				{
					props: { id: prefix + "_add_section", className: "crm-offer-info-link" },
					text: this.getMessage("addSectionButton")
				}
			));
		}
		if(canRestoreField)
		{
			children.push(BX.create(
				"SPAN",
				{
					props: { id: prefix + "_restore_field", className: "crm-offer-info-link" },
					text: this.getMessage("restoreFieldButton")
				}
			));
		}
		cell.appendChild(
			BX.create(
				"DIV",
				{
					props: { className: "crm-offer-item-link-wrap" },
					children: children
				}
			)
		);


		cell = row.insertCell(-1);
		cell.className = "crm-offer-info-right-btn";

		cell = row.insertCell(-1);
		cell.className = "crm-offer-last-td";

		var anchorNode = BX.type.isElementNode(options["anchorNode"]) ? options["anchorNode"] : null;
		var containerNode = BX.type.isElementNode(options["containerNode"]) ? options["containerNode"] : null;
		var index = BX.type.isNumber(options["index"]) && options["index"] > 0 ? options["index"] : -1;
		if(anchorNode)
		{
			var targetNode = BX.findNextSibling(anchorNode, { tagName: "TABLE", className: "crm-offer-info-table" });
			if(targetNode)
			{
				targetNode.parentNode.insertBefore(table, targetNode);
			}
			else
			{
				anchorNode.parentNode.appendChild(table);
			}
		}
		else if(containerNode)
		{
			if(index < 0)
			{
				containerNode.appendChild(table);
			}
			else
			{
				containerNode.insertBefore(table, containerNode.children[index]);
			}
		}
		else
		{
			throw "CrmFormFieldRenderer: Container node is not defined.";
		}

		return table;
	};
	BX.CrmFormFieldRenderer.getMessage = function(name)
	{
		var m = BX.CrmFormFieldRenderer.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	if(typeof(BX.CrmFormFieldRenderer.messages) === "undefined")
	{
		BX.CrmFormFieldRenderer.messages = {};
	}
}

//Placeholders
if(typeof(BX.CrmFormFieldPlaceholder) === "undefined")
{
	BX.CrmFormFieldPlaceholder = function()
	{
		this._settings = null;
		this._node = null;
		this._section = null;
		this._fieldId = "";
		this._fieldIndex = -1;
	};
	BX.CrmFormFieldPlaceholder.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
			this._node = this.getSetting("node", null);
			this._section = this.getSetting("section", null);
			this._fieldId = this.getSetting("fieldId", "");
			this._fieldIndex = parseInt(this.getSetting("fieldIndex", -1));
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getNode: function()
		{
			return this._node;
		},
		setNode: function(node)
		{
			this._node = node;
		},
		getFieldId: function()
		{
			return this._fieldId;
		},
		getFieldIndex: function()
		{
			return this._fieldIndex;
		},
		layout: function()
		{
			if(!this._node)
			{
				throw "CrmFormFieldPlaceholder: The 'node' is not assigned.";
			}

			var row = this._node;
			var cell = row.insertCell(-1);
			cell.className = "crm-offer-table-cap crm-offer-target-place";
			cell.colSpan = 5;
		}
	};
	BX.CrmFormFieldPlaceholder.create = function(settings)
	{
		var self = new BX.CrmFormFieldPlaceholder();
		self.initialize(settings);
		return self;
	};
}
if(typeof(BX.CrmFormSectionPlaceholder) === "undefined")
{
	BX.CrmFormSectionPlaceholder = function()
	{
		this._settings = null;
		this._node = null;
		this._manager = null;
		this._sectionId = "";
		this._sectionIndex = -1;
	};
	BX.CrmFormSectionPlaceholder.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
			this._node = this.getSetting("node", null);
			this._manager = this.getSetting("manager", null);
			this._sectionId = this.getSetting("sectionId", "");
			this._sectionIndex = parseInt(this.getSetting("sectionIndex", -1));
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getNode: function()
		{
			return this._node;
		},
		setNode: function(node)
		{
			this._node = node;
		},
		getSectionId: function()
		{
			return this._sectionId;
		},
		getSectionIndex: function()
		{
			return this._sectionIndex;
		},
		layout: function()
		{
			if(!this._node)
			{
				throw "CrmFormSectionPlaceholder: The 'node' is not assigned.";
			}

			var table = this._node;
			var row = table.insertRow(-1);
			var cell = row.insertCell(-1);
			cell.className = "crm-offer-table-cap crm-offer-target-place";
			cell.colSpan = 5;
		}
	};
	BX.CrmFormSectionPlaceholder.create = function(settings)
	{
		var self = new BX.CrmFormSectionPlaceholder();
		self.initialize(settings);
		return self;
	};
}

//D&D Items
if(typeof(BX.CrmFormFieldDragItem) === "undefined")
{
	BX.CrmFormFieldDragItem = function()
	{
		BX.CrmFormFieldDragItem.superclass.constructor.apply(this);
		this._field = null;
		this._showFieldInDragMode = true;
		this._contextId = "";
		this._enableBin = true;
	};
	BX.extend(BX.CrmFormFieldDragItem, BX.CrmCustomDragItem);
	BX.CrmFormFieldDragItem.prototype.doInitialize = function()
	{
		this._field = this.getSetting("field");
		if(!this._field)
		{
			throw "CrmFormFieldDragItem: The 'field' parameter is not defined in settings or empty.";
		}

		this._showFieldInDragMode = this.getSetting("showFieldInDragMode", true);
		var contextId = this.getSetting("contextId", "");
		this._contextId = contextId !== "" ? contextId : BX.CrmFormFieldDragItem.contextId;
		this._enableBin = this.getSetting("enableBin", true);
	};
	BX.CrmFormFieldDragItem.prototype.getField = function()
	{
		return this._field;
	};
	BX.CrmFormFieldDragItem.prototype.createGhostNode = function()
	{
		if(this._ghostNode)
		{
			return this._ghostNode;
		}

		this._ghostNode = this._field.createGhostNode();
		document.body.appendChild(this._ghostNode);
	};
	BX.CrmFormFieldDragItem.prototype.removeGhostNode = function()
	{
		if(this._ghostNode)
		{
			document.body.removeChild(this._ghostNode);
			this._ghostNode = null;
		}
	};
	BX.CrmFormFieldDragItem.prototype.getContextId = function()
	{
		return this._contextId;
	};
	BX.CrmFormFieldDragItem.prototype.getContextData = function()
	{
		return ({ contextId: this._contextId, field: this._field });
	};
	BX.CrmFormFieldDragItem.prototype.processDragStart = function()
	{
		BX.CrmFormSectionDragContainer.enable(false);
		if(!this._showFieldInDragMode)
		{
			this._field.getNode().style.display = "none";
		}
		BX.CrmFormFieldDragContainer.refresh();
	};
	BX.CrmFormFieldDragItem.prototype.processDragStop = function()
	{
		BX.CrmFormSectionDragContainer.enableAfter(true, 300);
		if(!this._showFieldInDragMode)
		{
			this._field.getNode().style.display = "";
		}
		BX.CrmFormFieldDragContainer.refreshAfter(300);
	};
	BX.CrmFormFieldDragItem.prototype.isDragDropBinEnabled = function()
	{
		return this._enableBin;
	};
	BX.CrmFormFieldDragItem.contextId = "form_field_item";
	BX.CrmFormFieldDragItem.create = function(id, settings)
	{
		var self = new BX.CrmFormFieldDragItem();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmFormSectionDragItem) === "undefined")
{
	BX.CrmFormSectionDragItem = function()
	{
		BX.CrmFormSectionDragItem.superclass.constructor.apply(this);
		this._section = null;
		this._showSectionInDragMode = true;
		this._showFieldsInDragMode = true;
		this._contextId = "";
		this._enableBin = true;
	};
	BX.extend(BX.CrmFormSectionDragItem, BX.CrmCustomDragItem);
	BX.CrmFormSectionDragItem.prototype.doInitialize = function()
	{
		this._section = this.getSetting("section");
		if(!this._section)
		{
			throw "CrmFormSectionDragItem: The 'section' parameter is not defined in settings or empty.";
		}

		this._showSectionInDragMode = this.getSetting("showSectionInDragMode", true);
		this._showFieldsInDragMode = this.getSetting("showFieldsInDragMode", true);
		var contextId = this.getSetting("contextId", "");
		this._contextId = contextId !== "" ? contextId : BX.CrmFormSectionDragItem.contextId;
		this._enableBin = this.getSetting("enableBin", true);
	};
	BX.CrmFormSectionDragItem.prototype.getSection = function()
	{
		return this._section;
	};
	BX.CrmFormSectionDragItem.prototype.createGhostNode = function()
	{
		if(this._ghostNode)
		{
			return this._ghostNode;
		}

		this._ghostNode = this._section.createGhostNode();
		document.body.appendChild(this._ghostNode);
	};
	BX.CrmFormSectionDragItem.prototype.removeGhostNode = function()
	{
		if(this._ghostNode)
		{
			document.body.removeChild(this._ghostNode);
			this._ghostNode = null;
		}
	};
	BX.CrmFormSectionDragItem.prototype.getContextId = function()
	{
		return this._contextId;
	};
	BX.CrmFormSectionDragItem.prototype.getContextData = function()
	{
		return ({ contextId: this._contextId, section: this._section });
	};
	BX.CrmFormSectionDragItem.prototype.processDragStart = function()
	{
		BX.CrmFormFieldDragContainer.enable(false);

		if(!this._showSectionInDragMode)
		{
			this._section.setVisible(false);
		}

		if(!this._showFieldsInDragMode)
		{
			var sections = this._section._manager.getSections();
			for(var i = 0; i < sections.length; i++)
			{
				var section = sections[i];
				section.setFieldsVisible(false);
				section.setButtonsVisible(false);
			}
		}

		BX.CrmFormSectionDragContainer.refresh();
	};
	BX.CrmFormSectionDragItem.prototype.processDragStop = function()
	{
		BX.CrmFormFieldDragContainer.enableAfter(true, 300);

		if(!this._showSectionInDragMode)
		{
			this._section.setVisible(true);
		}

		if(!this._showFieldsInDragMode)
		{
			if(!this._showFieldsInDragMode)
			{
				var sections = this._section._manager.getSections();
				for(var i = 0; i < sections.length; i++)
				{
					var section = sections[i];
					section.setFieldsVisible(true);
					section.setButtonsVisible(true);
				}
			}
		}

		BX.CrmFormSectionDragContainer.refreshAfter(300);
	};
	BX.CrmFormSectionDragItem.prototype.isDragDropBinEnabled = function()
	{
		return this._enableBin;
	};
	BX.CrmFormSectionDragItem.contextId = "form_section_item";
	BX.CrmFormSectionDragItem.create = function(id, settings)
	{
		var self = new BX.CrmFormSectionDragItem();
		self.initialize(id, settings);
		return self;
	};
}

//D&D Containers
if(typeof(BX.CrmFormFieldDragContainer) === "undefined")
{
	BX.CrmFormFieldDragContainer = function()
	{
		BX.CrmFormFieldDragContainer.superclass.constructor.apply(this);
		this._section = null;
		this._contextId = "";
		this._priority = -1;
	};
	BX.extend(BX.CrmFormFieldDragContainer, BX.CrmCustomDragContainer);
	BX.CrmFormFieldDragContainer.prototype.doInitialize = function()
	{
		this._section = this.getSetting("section");
		if(!this._section)
		{
			throw "CrmFormFieldDragContainer: The 'section' parameter is not defined in settings or empty.";
		}

		var contextId = this.getSetting("contextId", "");
		this._contextId = contextId !== "" ? contextId : BX.CrmFormFieldDragItem.contextId;

		var priority = parseInt(this.getSetting("priority", -1));
		this._priority = priority > 0 ? priority : BX.CrmCustomDragContainer.defaultPriority;
	};
	BX.CrmFormFieldDragContainer.prototype.getSection = function()
	{
		return this._section;
	};
	BX.CrmFormFieldDragContainer.prototype.getPriority = function()
	{
		return this._priority;
	};
	BX.CrmFormFieldDragContainer.prototype.createPlaceHolder = function(pos)
	{
		var rect;
		var placeholder = this._section.getPlaceHolder();
		if(placeholder)
		{
			rect = BX.pos(placeholder.getNode());
			if(pos.y >= rect.top && pos.y <= rect.bottom)
			{
				return;
			}
		}

		var fieldId = "";
		var fieldIndex = -1;
		var fields = this._section.getFields();
		for(var i = 0; i < fields.length; i++)
		{
			var field = fields[i];
			rect = BX.pos(field.getNode());
			if(pos.y >= rect.top && pos.y <= rect.bottom)
			{
				if((rect.top  + (rect.height / 2) - pos.y) >= 0)
				{
					fieldId = field.getId();
					fieldIndex = i;
				}
				else if(i < (fields.length - 1))
				{
					fieldId = fields[i + 1].getId();
					fieldIndex = i + 1;
				}
				break;
			}
		}
		this._section.createPlaceHolder({ id: fieldId, index: fieldIndex, fields: fields });
	};
	BX.CrmFormFieldDragContainer.prototype.removePlaceHolder = function()
	{
		this._section.removePlaceHolder();
	};
	BX.CrmFormFieldDragContainer.prototype.isAllowedContext = function(contextId)
	{
		return (contextId === this._contextId);
	};
	BX.CrmFormFieldDragContainer.enable = function(enable)
	{
		for(var k in this.items)
		{
			if(this.items.hasOwnProperty(k))
			{
				this.items[k].enable(enable);
			}
		}
	};
	BX.CrmFormFieldDragContainer.enableAfter = function(enable, interval)
	{
		interval = parseInt(interval);
		if(interval > 0)
		{
			window.setTimeout(function() { BX.CrmFormFieldDragContainer.enable(enable); });
		}
		else
		{
			this.enable(enable);
		}
	};
	BX.CrmFormFieldDragContainer.refresh = function()
	{
		for(var k in this.items)
		{
			if(this.items.hasOwnProperty(k))
			{
				this.items[k].refresh();
			}
		}
	};
	BX.CrmFormFieldDragContainer.refreshAfter = function(interval)
	{
		interval = parseInt(interval);
		if(interval > 0)
		{
			window.setTimeout(function() { BX.CrmFormFieldDragContainer.refresh(); }, interval);
		}
		else
		{
			this.refresh();
		}
	};
	BX.CrmFormFieldDragContainer.items = {};
	BX.CrmFormFieldDragContainer.create = function(id, settings)
	{
		var self = new BX.CrmFormFieldDragContainer();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}
if(typeof(BX.CrmFormSectionDragContainer) === "undefined")
{
	BX.CrmFormSectionDragContainer = function()
	{
		BX.CrmFormSectionDragContainer.superclass.constructor.apply(this);
		this._manager = null;
		this._contextId = "";
	};
	BX.extend(BX.CrmFormSectionDragContainer, BX.CrmCustomDragContainer);
	BX.CrmFormSectionDragContainer.prototype.doInitialize = function()
	{
		this._manager = this.getSetting("manager");
		if(!this._manager)
		{
			throw "CrmFormSectionDragContainer: The 'manager' parameter is not defined in settings or empty.";
		}

		var contextId = this.getSetting("contextId", "");
		this._contextId = contextId !== "" ? contextId : BX.CrmFormSectionDragItem.contextId;
	};
	BX.CrmFormSectionDragContainer.prototype.getManager = function()
	{
		return this._manager;
	};
	BX.CrmFormSectionDragContainer.prototype.createPlaceHolder = function(pos)
	{
		var rect;
		var placeholder = this._manager.getPlaceHolder();
		if(placeholder)
		{
			rect = BX.pos(placeholder.getNode());
			if(pos.y >= rect.top && pos.y <= rect.bottom)
			{
				return;
			}
		}

		var sectionId = "";
		var sectionIndex = -1;
		var sections = this._manager.getSections();
		for(var i = 0; i < sections.length; i++)
		{
			var section = sections[i];
			rect = BX.pos(section.getContentsNode());
			if(pos.y >= rect.top && pos.y <= rect.bottom)
			{
				if((rect.top + (rect.height / 2) - pos.y) >= 0)
				{
					sectionId = section.getId();
					sectionIndex = i;
				}
				else if(i < (sections.length - 1))
				{
					sectionId = sections[i + 1].getId();
					sectionIndex = i + 1;
				}
				break;
			}
		}
		this._manager.createPlaceHolder({ id: sectionId, index: sectionIndex, sections: sections });
	};
	BX.CrmFormSectionDragContainer.prototype.removePlaceHolder = function()
	{
		this._manager.removePlaceHolder();
	};
	BX.CrmFormSectionDragContainer.prototype.isAllowedContext = function(contextId)
	{
		return (contextId === this._contextId);
	};
	BX.CrmFormSectionDragContainer.enable = function(enable)
	{
		for(var k in this.items)
		{
			if(this.items.hasOwnProperty(k))
			{
				this.items[k].enable(enable);
			}
		}
	};
	BX.CrmFormSectionDragContainer.enableAfter = function(enable, interval)
	{
		interval = parseInt(interval);
		if(interval > 0)
		{
			window.setTimeout(function() { BX.CrmFormSectionDragContainer.enable(enable); });
		}
		else
		{
			this.enable(enable);
		}
	};
	BX.CrmFormSectionDragContainer.refresh = function()
	{
		for(var k in this.items)
		{
			if(this.items.hasOwnProperty(k))
			{
				this.items[k].refresh();
			}
		}
	};
	BX.CrmFormSectionDragContainer.refreshAfter = function(interval)
	{
		interval = parseInt(interval);
		if(interval > 0)
		{
			window.setTimeout(function() { BX.CrmFormSectionDragContainer.refresh(); }, interval);
		}
		else
		{
			this.refresh();
		}
	};
	BX.CrmFormSectionDragContainer.items = {};
	BX.CrmFormSectionDragContainer.create = function(id, settings)
	{
		var self = new BX.CrmFormSectionDragContainer();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}