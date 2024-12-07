/* eslint-disable */

BX.namespace("BX.Crm");

if(typeof BX.Crm.EntityMerger === "undefined")
{
	BX.Crm.EntityMerger = function()
	{
		this._id = "";
		this._settings = {};
		this._entityTypeId = 0;

		this._entityIds = [];
		this._entityInfos = [];

		this._editorIds = [];
		this._editors = {};

		this._primaryEntityId = 0;
		this._primaryEditor = null;

		this._controls = null;
		this._primaryEditorWrapper = null;
		this._primaryEditorSwitchName = "";
		this._secondaryEditorContainer = null;
		this._secondaryEditorHeaderContainer = null;

		this._beforeEditorLayoutHandler = BX.delegate(this.onBeforeEditorLayout, this);
		this._afterEditorLayoutHandler = BX.delegate(this.onAfterEditorLayout, this);
		this._editorRefreshLayoutHandler = BX.delegate(this.onEditorRefreshLayout, this);
		this._editorResolveFieldLayoutHandler = BX.delegate(this.onEditorResolveFieldLayout, this);

		this._scroller = null;
		this._scrollerY = null;

		this._editorHeaders = null;
		this._editorColumns = null;
		this._editorWrappers = null;

		this._dedupeConfig = null;
		this._dedupeCriterionData = null;
		this._dedupeQueueInfo = null;
		this._isDedupeQueueEnabled = false;
		this._isDedupeQueueRequestRunning = false;
		this._isAutomatic = false;

		this._panel = null;
		this._externalContextId = "";
		this._externalEventHandler = null;
		this.isReceiveEntityEditorFromController = false;
	};

	BX.Crm.EntityMerger.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._primaryEditorWrapper = BX(BX.prop.getString(this._settings, "primaryEditorWrapperId"));
			this._primaryEditorSwitchName = BX.prop.getString(this._settings, "primaryEditorSwitchName");
			this._secondaryEditorContainer = BX(BX.prop.getString(this._settings, "secondaryEditorContainerId"));
			this._secondaryEditorHeaderContainer = BX(BX.prop.getString(this._settings, "secondaryEditorHeaderContainerId"));

			this._entityTypeId = BX.prop.getInteger(this._settings, "entityTypeId", 0);
			this._entityIds = BX.prop.getArray(this._settings, "entityIds", []);
			this._entityInfos = BX.prop.getArray(this._settings, "entityInfos", []);

			this._dedupeConfig = BX.prop.getObject(this._settings, "dedupeConfig", {});
			this._dedupeCriterionData = BX.prop.getObject(this._settings, "dedupeCriterionData", {});
			this._dedupeQueueInfo = BX.prop.getObject(this._settings, "dedupeQueueInfo", {});
			this._isDedupeQueueEnabled = BX.prop.getInteger(this._dedupeQueueInfo, "length", 0) > 0;

			this._isAutomatic = BX.prop.getBoolean(this._settings, 'isAutomatic', false);
			this.isReceiveEntityEditorFromController = BX.prop.getBoolean(this._settings, 'isReceiveEntityEditorFromController', false);

			this.entityWrapper = document.querySelector('.crm-entity-merger-wrapper');

			this._panel = BX.Crm.EntityMergerPanel.create(
				this._id,
				{
					merger: this,
					previouslyProcessedCount: BX.prop.getInteger(this._settings, "previouslyProcessedCount", 0)
				}
			);
			this._externalContextId = BX.prop.getString(this._settings, "externalContextId", "");
			this.loadAllEntityEditors();
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			return BX.prop.getString(BX.Crm.EntityMerger.messages, name, name);
		},
		getEntityTypeId: function()
		{
			return this._entityTypeId;
		},
		getEntityTypeName: function()
		{
			return BX.CrmEntityType.resolveName(this._entityTypeId);
		},
		containsEntityId: function(entityId)
		{
			for(var i = 0, length = this._entityIds.length; i < length; i++)
			{
				if(entityId == this._entityIds[i])
				{
					return true;
				}
			}
			return  false;
		},
		getScheme: function()
		{
			var keys = Object.keys(this._editors);
			return keys.length > 0 ? this._editors[keys[0]].getScheme() : null;
		},
		close: function()
		{
			this.animateColumns(function() {
				var slider = top.BX.SidePanel.Instance.getSliderByWindow(window);
				if(slider && slider.isOpen())
				{
					slider.close(false);
				}
				else
				{
					var pathToList = this.getEntityListUrl();
					if (pathToList)
					{
						location.href = pathToList;
					}
				}
			}.bind(this));
		},
		clearSecondaryEditorsLayout: function()
		{
			for(var key in this._editors)
			{
				if(!this._editors.hasOwnProperty(key))
				{
					continue;
				}

				var editor = this._editors[key];
				this.unregisterEntityEditor(editor);
				editor.release();
				BX.Crm.EntityEditor.items[editor.getId()];
			}

			this._entityIds = [];
			this._entityInfos = [];
			this._editorIds = [];
			this._editors = {};

			if(this._editorHeaders)
			{
				for(var editorKey in this._editorHeaders)
				{
					if(this._editorHeaders.hasOwnProperty(editorKey))
					{
						this._editorHeaders[editorKey].release();
					}
				}
			}

			this._editorHeaders = null;
			this._editorColumns = null;
			this._editorWrappers = null;

			BX.clean(this._secondaryEditorHeaderContainer);
			BX.clean(this._secondaryEditorContainer);
		},
		createSecondaryEditorLayout: function(entityId)
		{
			if(!this._editorHeaders)
			{
				this._editorHeaders = {};
			}

			if(!this._editorWrappers)
			{
				this._editorWrappers = {};
			}

			if(!this._editorHeaders.hasOwnProperty(entityId))
			{
				var headerWrapper = BX.create("div",
					{
						props: { className: "crm-entity-merger-column-btn" },
						attrs: { "data-entity-id": entityId }
					}
				);
				this._secondaryEditorHeaderContainer.appendChild(headerWrapper);
				this._editorHeaders[entityId] = BX.Crm.EntityMergerHeader.create(
					"header_" + entityId,
					{
						merger: this,
						container: headerWrapper,
						switchName: this._primaryEditorSwitchName,
						entityId: entityId,
						titleTemplate: BX.prop.getString(this._settings, "headerTitleTemplate", "")
					}
				);
			}

			if(!this._editorWrappers.hasOwnProperty(entityId))
			{
				var wrapper = BX.create("div",
					{
						props: { className: "crm-entity-merger-column-item" },
						attrs: { "data-entity-id": entityId }
					}
				);
				this._secondaryEditorContainer.appendChild(wrapper);
				this._editorWrappers[entityId] = wrapper;
			}

			return { header: this._editorHeaders[entityId], wrapper: this._editorWrappers[entityId] };
		},
		isQueueEnabled: function()
		{
			return this._isDedupeQueueEnabled;
		},
		getDedupeQueueLength: function()
		{
			return BX.prop.getInteger(this._dedupeQueueInfo, "length", 0);
		},
		getDedupeQueueOffset: function()
		{
			return BX.prop.getInteger(this._dedupeQueueInfo, "offset", 0);
		},
		addQueueOffset: function(delta)
		{
			var promise = new BX.Promise();
			if(this._isDedupeQueueRequestRunning)
			{
				window.setTimeout(function(){ promise.fulfill(false); }, 0);
				return promise;
			}

			var offset = BX.prop.getInteger(this._dedupeQueueInfo, "offset", 0);
			var length = BX.prop.getInteger(this._dedupeQueueInfo, "length", 0);

			offset += delta;
			if(offset >= length || offset < 0)
			{
				window.setTimeout(function(){ promise.fulfill(false); }, 0);
			}
			else
			{
				this._isDedupeQueueRequestRunning = true;
				this.loadDedupeQueueItem(offset).then(
					function(params){
						var queueInfo = BX.prop.getObject(params, "queueInfo", null);
						if(queueInfo)
						{
							this._dedupeQueueInfo = queueInfo;
						}

						var entityInfos = BX.prop.getArray(params, "entityInfos", []);
						if(entityInfos.length === 0)
						{
							if(delta < 0)
							{
								this._dedupeQueueInfo["offset"] = 0;
							}
							else if(this._dedupeQueueInfo["offset"] >= this._dedupeQueueInfo["length"])
							{
								this._dedupeQueueInfo["offset"] = this._dedupeQueueInfo["length"] - 1;
							}

							promise.fulfill(false);
							return;
						}

						this._dedupeCriterionData = BX.prop.getObject(params, "dedupeCriterionData", {});
						this.animateColumns(
							function()
							{
								this.animateSidebar(
									function(){ this.setupByEntityInfos(entityInfos); }.bind(this)
								);
							}.bind(this)
						);

						this._isDedupeQueueRequestRunning = false;
						promise.fulfill(true);
					}.bind(this)
				).catch(
					function(){ this._isDedupeQueueRequestRunning = false; }.bind(this)
				);
			}
			return promise;
		},
		moveToNextQueueItem: function()
		{
			return this.addQueueOffset(1);
		},
		moveToPreviousQueueItem: function()
		{
			return this.addQueueOffset(-1);
		},
		loadDedupeQueueItem: function(offset)
		{
			var promise = new BX.Promise();

			BX.ajax.runComponentAction(
				"bitrix:crm.entity.merger",
				"getDedupeQueueItem",
				{
					data:
						{
							entityTypeName: this.getEntityTypeName(),
							typeNames: BX.prop.getArray(this._dedupeConfig, "typeNames", []),
							scope: BX.prop.getString(this._dedupeConfig, "scope", ""),
							offset: offset,
							isAutomatic: this._isAutomatic ? 1 : 0
						}
				}
			).then(
				function(response)
				{
					this._dedupeQueueInfo["offset"] = offset;
					var data = BX.prop.getObject(response, "data", {});

					promise.fulfill(
						{
							entityIds: BX.prop.getArray(data, "ENTITY_IDS", []),
							entityInfos: BX.prop.getArray(data, "ENTITY_INFOS", []),
							dedupeCriterionData: BX.prop.getObject(data, "CRITERION_DATA", {}),
							queueInfo: BX.prop.getObject(data, "QUEUE_INFO", {})
						}
					)
				}.bind(this)
			);

			return promise;
		},
		setupByEntityInfos: function(entityInfos)
		{
			if(this._primaryEditor)
			{
				this.unregisterEntityEditor(this._primaryEditor);
				this._primaryEditor.release();
				this._primaryEditor = null;
			}

			if(this._primaryEntityId !== 0)
			{
				this._primaryEntityId = 0;
			}

			this.clearSecondaryEditorsLayout();

			if(entityInfos.length === 0)
			{
				return false;
			}

			var entityIds = [];
			for(var i = 0, length = entityInfos.length; i < length; i++)
			{
				var entityId = BX.prop.getInteger(entityInfos[i], "ENTITY_ID", 0);
				if(entityId > 0)
				{
					entityIds.push(entityId);
				}
			}

			this._entityIds = entityIds;
			this._entityInfos = entityInfos;

			this.loadAllEntityEditors();
			return true;
		},
		isDeduplicationMode: function()
		{
			return(!!BX.prop.getArray(this._dedupeConfig, "typeNames", null));
		},
		getDeduplicationCriterionData: function()
		{
			return this._dedupeCriterionData;
		},
		getDedupeListUrl: function()
		{
			return BX.prop.getString(this._settings, "dedupeListUrl", "");
		},
		getEntityListUrl: function()
		{
			return BX.prop.getString(this._settings, "entityListUrl", "");
		},
		openDedupeList: function()
		{
			var params = {
				scope: BX.prop.getString(this._dedupeConfig, "scope", ""),
				typeNames: BX.prop.getArray(this._dedupeConfig, "typeNames", [])
			};
			if (this._isAutomatic)
			{
				params['is_automatic'] = 'yes';
			}

			BX.Crm.Page.open(
				BX.util.add_url_param(
					this.getDedupeListUrl(),
					params
				)
			);
		},
		bindBeforeEditorLayout: function()
		{
			this.unbindBeforeEditorLayout();
			BX.addCustomEvent(
				window,
				"BX.Crm.EntityEditor:onBeforeLayout",
				this._beforeEditorLayoutHandler
			);
		},
		unbindBeforeEditorLayout: function()
		{
			BX.removeCustomEvent(
				window,
				"BX.Crm.EntityEditor:onBeforeLayout",
				this._beforeEditorLayoutHandler
			);
		},
		bindAfterEditorLayout: function()
		{
			this.unbindAfterEditorLayout();
			BX.addCustomEvent(
				window,
				"BX.Crm.EntityEditor:onLayout",
				this._afterEditorLayoutHandler
			);
		},
		unbindAfterEditorLayout: function()
		{
			BX.removeCustomEvent(
				window,
				"BX.Crm.EntityEditor:onLayout",
				this._afterEditorLayoutHandler
			);
		},
		loadAllEntityEditors: function()
		{
			var totalEntities = this._entityIds.length;
			var isProgressLoader = totalEntities > 3;

			if(!(BX.type.isArray(this._entityIds) && totalEntities > 0))
			{
				BX.localStorage.set(
					"onCrmEntityMergeComplete",
					{
						entityTypeId: this.getEntityTypeId(),
						entityTypeName: this.getEntityTypeName(),
						context: this._externalContextId,
						length: this._dedupeQueueInfo["length"],
						skipped: false
					},
					10
				);
				this.close();
				return;
			}

			this.bindBeforeEditorLayout();
			this.bindAfterEditorLayout();

			var loader;
			var sidebar = document.body.querySelector('.crm-entity-merger-sidebar');
			var columnContainer = document.querySelector('.crm-entity-merger-column');
			var container = document.querySelector('.crm-entity-merger-wrapper');

			sidebar.classList.remove('crm-entity-merger-sidebar-closing');
			columnContainer.classList.remove('crm-entity-merger-column-closing');
			container.classList.add('crm-entity-merger-wrapper-loading');

			if(isProgressLoader)
			{
				var step = 100 / totalEntities;

				loader = new BX.UI.ProgressRound({
					width: 100,
					lineSize: 3
				});
				loader.renderTo(document.body);
			}
			else
			{
				loader = new BX.Loader({
					target: document.body
				});
				loader.show();
			}

			var loaded = 0;
			var onEditorLoad = function()
			{
				loaded++;
				if (loaded === totalEntities)
				{
					if(isProgressLoader)
					{
						loader.finish();
					}
					loader.destroy();
					container.classList.remove('crm-entity-merger-wrapper-loading');
				}
				else
				{
					if(isProgressLoader)
					{
						loader.update(Math.floor(step * loaded));
					}
					this.doLoadEntityEditor(this._entityIds[loaded]).then(onEditorLoad);
				}
			}.bind(this);

			this.doLoadEntityEditor(this._entityIds[loaded]).then(onEditorLoad);
		},
		reloadEntityEditor: function(entityId)
		{
			if(this._primaryEditor)
			{
				this.unregisterEntityEditor(this._primaryEditor);
				this._primaryEditor.release();
				this._primaryEditor = null;
			}

			var secondaryEditor = this.getSecondaryEditorByEntityId(entityId);
			if(secondaryEditor)
			{
				this.releaseEntityEditor(secondaryEditor);
			}

			this.clearLayout();

			this.bindBeforeEditorLayout();
			this.bindAfterEditorLayout();

			this.doLoadEntityEditor(entityId);
		},
		doLoadEntityEditor: function(entityId)
		{
			var entityTypeName = this.getEntityTypeName();
			var editorConfigId = BX.prop.getString(this._settings, "editorConfigId", "");
			if(editorConfigId === "")
			{
				editorConfigId = "merger_" + entityTypeName.toLowerCase();
			}

			var editorId = editorConfigId + "_" + entityId;
			this._editorIds.push(editorId);

			var entityEditorUrl = BX.prop.getString(this._settings, "entityEditorUrl", "");
			if(entityEditorUrl === "")
			{
				entityEditorUrl = BX.Crm.EntityMerger.getEntityEditorUrl(entityTypeName);
			}

			const sharedParameters = {
				// force using of common scope
				SCOPE: 'C',
				ENABLE_CONFIG_SCOPE_TOGGLE: 'N',
				ENABLE_CONFIGURATION_UPDATE: 'N',
				ENABLE_FIELDS_CONTEXT_MENU: 'N',
				ENABLE_REQUIRED_USER_FIELD_CHECK: 'Y',
				ENABLE_AVAILABLE_FIELDS_INJECTION: 'Y',
				ENABLE_EXTERNAL_LAYOUT_RESOLVERS: 'Y',
				SHOW_EMPTY_FIELDS: 'Y',
				INITIAL_MODE: 'view',
				READ_ONLY: 'Y',
				IS_EMBEDDED: 'Y',
			};

			if (this.isReceiveEntityEditorFromController)
			{
				const promise = new BX.Promise();
				BX.ajax.runAction('crm.api.item.getEditor', {
					data: {
						entityTypeId: this.getEntityTypeId(),
						id: entityId,
						guid: editorId,
						configId: '',
						params: {
							enableSingleSectionCombining: 'N',
							forceDefaultConfig: 'N',
							ENABLE_VISIBILITY_POLICY: 'N',
							...sharedParameters,
						},
					},
				}).then((response) => {
					const html = response?.data?.html ?? '';

					this.createSecondaryEditorLayout(entityId);
					BX.Runtime.html(this._editorWrappers[entityId], html);

					promise.fulfill(entityId);
				}).catch((response) => {
					throw response.errors;
				});

				return promise;
			}

			if(entityEditorUrl === '')
			{
				throw 'Crm.EntityMerger: Could not resolve entity editor URL.';
			}

			var promise = new BX.Promise();
			BX.ajax.post(
				BX.util.add_url_param(entityEditorUrl, { sessid: BX.bitrix_sessid() }),
				{
					ACTION: "PREPARE_EDITOR_HTML",
					ACTION_ENTITY_TYPE_NAME: entityTypeName,
					ACTION_ENTITY_ID: entityId,
					GUID: editorId,
					CONFIG_ID: "",
					ENABLE_COMMUNICATION_CONTROLS: "N",
					//Disable entity editor config change depends on entity state (for example general or return customer lead)
					ENABLE_CONFIG_VARIABILITY: "N",
					FORCE_DEFAULT_CONFIG: 'N',
					PARAMS: {},
					TITLE: "",
					...sharedParameters,
				},
				function(result)
				{
					this.createSecondaryEditorLayout(entityId);
					this._editorWrappers[entityId].innerHTML = result;

					promise.fulfill(entityId);

				}.bind(this)
			);

			return promise;
		},
		areEditorsLoaded: function()
		{
			return Object.keys(this._editors).length === this._editorIds.length;
		},
		isReadyForLayout: function()
		{
			if(Object.keys(this._editors).length !== this._editorIds.length)
			{
				return false;
			}

			for(var key in this._editors)
			{
				if(!this._editors.hasOwnProperty(key))
				{
					continue;
				}

				if(!this._editors[key].hasLayout())
				{
					return false;
				}
			}

			return true;
		},
		getEditorColumns: function()
		{
			if (this._editorColumns !== null)
			{
				return this._editorColumns;
			}

			var buttons = this.getEditorButtons();
			var columns = this.getMergerColumns();
			var detailButtons = this.getDetailButtons();

			this._editorColumns = [];
			for (var i = 0; i < buttons.length; i++)
			{
				this._editorColumns.push({
					entityId: parseInt(buttons[i].getAttribute("data-entity-id")),
					button: buttons[i],
					column: columns[i],
					detailButton: detailButtons[i],
					label: buttons[i].querySelector(".crm-entity-merger-column-btn-label")
				});
			}

			return this._editorColumns;
		},
		getMergerColumns: function()
		{
			return document.querySelectorAll('.crm-entity-merger-column-item');
		},
		getDetailButtons: function()
		{
			return document.querySelectorAll('.ui-entity-editor-detail-btn');
		},
		getEditorButtons: function()
		{
			return document.querySelectorAll('.crm-entity-merger-column-btn');
		},
		getEntityDetailsUrl: function(entityId)
		{
			for(var i = 0, length = this._entityInfos.length; i < length; i++)
			{
				if(entityId === BX.prop.getInteger(this._entityInfos[i], "ENTITY_ID"))
				{
					return BX.prop.getString(this._entityInfos[i], "DETAILS_URL", "");
				}
			}
			return "";
		},
		getColumnItemByEntityId: function(entityId)
		{
			if(!BX.type.isInteger(entityId))
			{
				entityId = parseInt(entityId);
			}

			if(entityId <= 0)
			{
				return  null;
			}

			var columns = this.getEditorColumns();
			if(columns)
			{
				for(var i = 0, length = columns.length; i < length; i++)
				{
					var item = columns[i];
					if(item["entityId"] === entityId)
					{
						return item;
					}
				}
			}
			return null;
		},
		selectColumnItem: function(item)
		{
			item.button.classList.add('crm-entity-merger-column-btn-hover');
			item.column.classList.add('crm-entity-merger-column-item-hover');
		},
		unSelectColumnItem: function(item)
		{
			item.button.classList.remove('crm-entity-merger-column-btn-hover');
			item.column.classList.remove('crm-entity-merger-column-item-hover');
		},
		prepareControls: function()
		{
			var scheme = this.getScheme();
			if(!(scheme instanceof BX.UI.EntityScheme))
			{
				throw "Crm.EntityMerger. Could not find editor scheme.";
			}

			this._controls = [];
			var columns = scheme.getElements();
			for(var columnsIndex = 0, columnsLength = columns.length; columnsIndex < columnsLength; columnsIndex++)
			{
				var sections = columns[columnsIndex].getElements();
				for(var sectionsIndex = 0, sectionsLength = sections.length; sectionsIndex < sectionsLength; sectionsIndex++)
				{
					var controls = this.createControlsBySchemeElement(sections[sectionsIndex]);
					for(var j = 0; j < controls.length; j++)
					{
						this._controls.push(controls[j]);
					}
				}
			}
		},
		getEditorControlsById: function(id)
		{
			var results = [];
			for(var key in this._editors)
			{
				if(!this._editors.hasOwnProperty(key))
				{
					continue;
				}

				var editorControl = this._editors[key].getControlById(id);
				if(editorControl)
				{
					results.push(editorControl);
				}
			}
			return results;
		},
		createControl: function(controlId, controlType, editorControls)
		{
			if(controlType === "section")
			{
				return(
					BX.Crm.EntityMergerSection.create(
						controlId,
						{
							merger: this,
							editorControls: editorControls
						}
					)
				);
			}
			else if(controlType === "company")
			{
				return(
					BX.Crm.EntityMergerField.create(
						controlId,
						{
							merger: this,
							editorControls: editorControls,
							controller: BX.Crm.EntityMergerClientCompanyController.create(false)
						}
					)
				);
			}
			else if(controlType === "multiple_company")
			{
				return(
					BX.Crm.EntityMergerField.create(
						controlId,
						{
							merger: this,
							editorControls: editorControls,
							controller: BX.Crm.EntityMergerClientCompanyController.create(true)
						}
					)
				);
			}
			else if(controlType === "multiple_contact")
			{
				return(
					BX.Crm.EntityMergerField.create(
						controlId,
						{
							merger: this,
							editorControls: editorControls,
							controller: BX.Crm.EntityMergerClientContactController.create()
						}
					)
				);
			}
			else
			{
				return(
					BX.Crm.EntityMergerField.create(
						controlId,
						{
							merger: this,
							editorControls: editorControls,
							controller: BX.Crm.EntityMergerFieldController.create()
						}
					)
				);
			}
		},
		createControlsBySchemeElement: function(element)
		{
			var controlType = element.getType();
			var controlId = element.getName();

			var results = [];
			var control = null;
			var compoundInfos = element.getDataArrayParam("compound", null);
			if(compoundInfos === null)
			{
				control = this.createControl(
					controlId,
					controlType,
					this.getEditorControlsById(controlId)
				);

				if(control)
				{
					results.push(control);
				}
			}
			else
			{
				var editorControls = this.getEditorControlsById(controlId);
				for(var i = 0; i < compoundInfos.length; i++)
				{
					var compoundInfo = compoundInfos[i];
					control = this.createControl(
						compoundInfo["name"],
						compoundInfo["type"],
						editorControls.slice(0)
					);
					if(control)
					{
						if(i > 0)
						{
							control._enableAdjusting = false;
						}
						results.push(control);
					}
				}
			}
			return results;
		},
		layout: function()
		{
			if(this._editorHeaders)
			{
				for(var key in this._editorHeaders)
				{
					if(this._editorHeaders.hasOwnProperty(key))
					{
						this._editorHeaders[key].layout();
					}
				}
			}

			for(var i = 0, length = this._controls.length; i < length; i++)
			{
				this._controls[i].layout();
			}

			var columns = this.getEditorColumns();
			var state = this;

			for (var j = 0; j < columns.length; j++)
			{
				BX.bind(columns[j].column, 'mouseenter', function() {
					state.selectColumnItem(state.getColumnItemByEntityId(this.getAttribute("data-entity-id")));
				});

				BX.bind(columns[j].column, 'mouseleave', function() {
					state.unSelectColumnItem(state.getColumnItemByEntityId(this.getAttribute("data-entity-id")));
				});

				BX.bind(columns[j].column, 'click', function() {
					state.setCurrentActiveColumn(state.getColumnItemByEntityId(this.getAttribute("data-entity-id")));
				});
			}

			this.getFirstEntitySwitchClick();

			BX.removeCustomEvent(
				window,
				"BX.Crm.EntityEditor:onRefreshLayout",
				this._editorRefreshLayoutHandler
			);

			BX.addCustomEvent(
				window,
				"BX.Crm.EntityEditor:onRefreshLayout",
				this._editorRefreshLayoutHandler
			);

			this._scroller = BX.Crm.EntityMergerScroller.create();
			this._scroller.layout();

			this._scrollerY = BX.Crm.EntityMergerVerticalScroller.create();
			this._scrollerY.layout();

			this._panel.layout();

			var slider = top.BX.SidePanel.Instance.getSliderByWindow(window);
			if(slider)
			{
				document.body.style.overflow = 'hidden';
			}
		},
		clearLayout: function()
		{
			if(this._editorHeaders)
			{
				for(var key in this._editorHeaders)
				{
					if(this._editorHeaders.hasOwnProperty(key))
					{
						this._editorHeaders[key].clearLayout();
					}
				}
			}

			for(var i = 0, length = this._controls.length; i < length; i++)
			{
				this._controls[i].clearLayout();
			}

			BX.removeCustomEvent(
				window,
				"BX.Crm.EntityEditor:onRefreshLayout",
				this._editorRefreshLayoutHandler
			);
		},
		clearSecondaryEntityLayout: function(entityId)
		{
			var editor = this.getSecondaryEditorByEntityId(entityId);
			if(editor)
			{
				this.releaseEntityEditor(editor);
			}

			var column = this.getColumnItemByEntityId(entityId);
			if(column)
			{
				BX.remove(column["button"]);
				BX.remove(column["column"]);
				this._editorColumns = null;
			}
		},
		getEntityCreationDate(entityId)
		{
			const editor = this.getSecondaryEditorByEntityId(entityId);
			if (editor)
			{
				const model = editor.getModel();

				return model.getStringField('DATE_CREATE') ?? model.getStringField('CREATED_TIME');
			}

			return '';
		},
		registerEntityEditor: function(editor)
		{
			if(!this._controls)
			{
				return;
			}

			for(var i = 0, length = this._controls.length; i < length; i++)
			{
				this._controls[i].registerEntityEditor(editor);
			}
		},
		unregisterEntityEditor: function(editor)
		{
			if(!this._controls)
			{
				return;
			}

			for(var i = 0, length = this._controls.length; i < length; i++)
			{
				this._controls[i].unregisterEntityEditor(editor);
			}
		},
		releaseEntityEditor: function(editor)
		{
			if(!editor)
			{
				return;
			}

			this.removeEntityEditor(editor);
			this.unregisterEntityEditor(editor);

			editor.release();
		},
		removeEntityEditor: function(editor)
		{
			if(!editor)
			{
				return;
			}

			var editorId = editor.getId();

			var index = this._editorIds.indexOf(editorId);
			if(index >= 0)
			{
				this._editorIds.splice(index, 1);
			}

			if(this._editors.hasOwnProperty(editorId))
			{
				delete this._editors[editorId];
			}
		},
		getSecondaryEditor: function(editorId)
		{
			return this._editors.hasOwnProperty(editorId) ? this._editors[editorId] : null;
		},
		getSecondaryEditorByEntityId: function(entityId)
		{
			for(var key in this._editors)
			{
				if(!this._editors.hasOwnProperty(key))
				{
					continue;
				}

				var editor = this._editors[key];
				if(editor.getEntityId() === entityId)
				{
					return  editor;
				}
			}
			return null;
		},
		getSecondaryEntityIds: function()
		{
			var primaryEntityId = this.getPrimaryEntityId();
			var entityIds = [];
			for(var key in this._editors)
			{
				if(!this._editors.hasOwnProperty(key))
				{
					continue;
				}

				var entityId = this._editors[key].getEntityId();
				if(entityId !== primaryEntityId)
				{
					entityIds.push(entityId);
				}
			}
			return entityIds;
		},
		getSecondaryEntityCount: function()
		{
			return Object.keys(this._editors).length;
		},
		getPrimaryEntityId: function()
		{
			return this._primaryEntityId;
		},
		setPrimaryEntity: function(entityId, forced)
		{
			if(!BX.type.isInteger(entityId))
			{
				entityId = parseInt(entityId);
				if(isNaN(entityId))
				{
					throw "Crm.EntityMerger. Parameter 'entityId' must be integer.";
				}
			}

			if(!forced && this._primaryEntityId === entityId)
			{
				return;
			}

			var secondaryEditor = this.getSecondaryEditorByEntityId(entityId);
			if(!secondaryEditor)
			{
				throw "Crm.EntityMerger. Could not find entity editor.";
			}

			if(this._primaryEditor)
			{
				this.unregisterEntityEditor(this._primaryEditor);
				this._primaryEditor.release();
				this._primaryEditor = null;
			}

			this._primaryEntityId = entityId;
			this._primaryEditor = secondaryEditor.clone(
				{
					id: secondaryEditor.getId() + "_primary",
					wrapper: this._primaryEditorWrapper
				}
			);
			this.registerEntityEditor(this._primaryEditor);

			BX.onCustomEvent(this, "onPrimaryEntityChange");
		},
		checkIfPrimaryEditorControl: function(editorControl)
		{
			return this._primaryEditor && editorControl && this._primaryEditor === editorControl.getEditor();
		},
		checkIfSecondaryEditorControl: function(editorControl)
		{
			return !this.checkIfPrimaryEditorControl(editorControl);
		},
		getControlById: function(id, recursive)
		{
			recursive = !!recursive;

			if(this._controls)
			{
				for(var i = 0, length = this._controls.length; i < length; i++)
				{
					if(this._controls[i].getId() === id)
					{
						return this._controls[i];
					}

					if(recursive)
					{
						var childControl = this._controls[i].getChildControlById(id);
						if(childControl)
						{
							return childControl;
						}
					}
				}
			}
			return null;
		},
		setupPrimaryEditor: function()
		{
			BX.addClass(this._panel._mergeButton, "ui-btn-disabled");
			BX.addClass(this._panel._mergeButton, "ui-btn-wait");
			BX.addClass(this._panel._mergeAndEditButton, "ui-btn-disabled");
			BX.ajax.runComponentAction(
				"bitrix:crm.entity.merger",
				"prepareMergeData",
				{
					data:
						{
							entityTypeName: this.getEntityTypeName(),
							seedEntityIds: this.getSecondaryEntityIds(),
							targEntityId: this.getPrimaryEntityId()
						}
				}
			).then(
				function(response)
				{
					var data = BX.prop.getObject(response, "data", {}),
					 	updateModelData = {},
						currentModelData = this._primaryEditor.getModel().getData();

					for(var i = 0, length = this._controls.length; i < length; i++)
					{
						this._controls[i].applyMergeResults(data, updateModelData, currentModelData);
					}
					this._primaryEditor.getModel().updateData(updateModelData, { enableNotification: false });

					BX.addCustomEvent(
						window,
						"BX.Crm.EntityEditor:onResolveFieldLayoutOptions",
						this._editorResolveFieldLayoutHandler
					);

					this._primaryEditor.refreshLayout({ reset: true });

					BX.removeCustomEvent(
						window,
						"BX.Crm.EntityEditor:onResolveFieldLayoutOptions",
						this._editorResolveFieldLayoutHandler
					);

					BX.removeClass(this._panel._mergeButton, "ui-btn-wait");
					BX.removeClass(this._panel._mergeButton, "ui-btn-disabled");
					BX.removeClass(this._panel._mergeAndEditButton, "ui-btn-disabled");
				}.bind(this)
			);
		},
		setupPrimaryEditorControl: function(controlId, editorControlId, enabledEntityIds)
		{
			if(!BX.type.isArray(enabledEntityIds))
			{
				enabledEntityIds = [];
			}

			if(enabledEntityIds.length === 0)
			{
				enabledEntityIds.push(0);
			}

			BX.ajax.runComponentAction(
				"bitrix:crm.entity.merger",
				"prepareFieldMergeData",
				{
					data:
						{
							entityTypeName: this.getEntityTypeName(),
							seedEntityIds: this.getSecondaryEntityIds(),
							targEntityId: this.getPrimaryEntityId(),
							fieldId: controlId,
							options: { enabledIds: enabledEntityIds }
						}
				}
			).then(
				function(response)
				{
					var control = this.getControlById(controlId, true);
					if(!control)
					{
						return;
					}

					var updateModelData = {};
					control.applyMergeResults(
						BX.prop.getObject(response, "data", {}),
						updateModelData,
						this._primaryEditor.getModel().getData()
					);

					this._primaryEditor.getModel().updateData(updateModelData, { enableNotification: false });
					var editorControl = this._primaryEditor.getControlById(editorControlId);
					if(editorControl)
					{
						editorControl.refreshLayout({ reset: true });
					}
				}.bind(this)
			);
		},
		processSecondaryEntityRemoval: function()
		{
			if(this.getSecondaryEntityCount() < 2)
			{
				if(this.isQueueEnabled())
				{
					BX.localStorage.set(
						"onCrmEntityMergeSkip",
						{
							entityTypeId: this.getEntityTypeId(),
							entityTypeName: this.getEntityTypeName(),
							context: this._externalContextId,
							length: this._dedupeQueueInfo["length"],
							skipped: BX.prop.getInteger(this._dedupeQueueInfo, 'skipped', 0) + 1
						},
						10
					);

					//Just reload current queue position
					this.addQueueOffset(0).then(
						function(success)
						{
							if(!success)
							{
								this.close();
							}
						}.bind(this)
					);
				}
				else
				{
					BX.localStorage.set(
						"onCrmEntityMergeSkip",
						{
							entityTypeId: this.getEntityTypeId(),
							entityTypeName: this.getEntityTypeName(),
							context: this._externalContextId,
							seedEntityIds: this.getSecondaryEntityIds(),
							targEntityId: this.getPrimaryEntityId(),
							skipped: BX.prop.getInteger(this._dedupeQueueInfo, 'skipped', 0) + 1
						},
						100
					);

					window.setTimeout(
						function(){ this.close(); }.bind(this),
						0
					);
				}
			}
			else
			{
				this.setupPrimaryEditor();
			}
		},
		processFieldSourceEntityChange: function(field, editorControl)
		{
			if(!this._primaryEditor)
			{
				return;
			}

			var activeSwitches = field.getActiveSwitches();
			if(!field.isMultiple())
			{
				if(activeSwitches.length > 0)
				{
					var primaryEditorControl = this._primaryEditor.getControlById(editorControl.getId());
					if(primaryEditorControl)
					{
						field.adjustEditorControl(editorControl, primaryEditorControl);
					}
				}
			}
			else
			{
				if((editorControl instanceof BX.Crm.EntityEditorUserField)
					|| (editorControl instanceof BX.Crm.EntityEditorMultifield)
					|| (editorControl instanceof BX.Crm.EntityEditorClientLight)
				)
				{
					var enabledEntityIds = [];
					for(var i = 0, length = activeSwitches.length; i < length; i++)
					{
						enabledEntityIds.push(activeSwitches[i].getEntityId());
					}
					this.setupPrimaryEditorControl(field.getId(), editorControl.getId(), enabledEntityIds);
				}
			}
		},
		onBeforeEditorLayout: function(sender, eventArgs)
		{
			var editorId = sender.getId();
			if(this._editorIds.indexOf(editorId) < 0)
			{
				return;
			}

			this._editors[editorId] = sender;
			eventArgs["cancel"] = true;

			if(this.areEditorsLoaded())
			{
				this.unbindBeforeEditorLayout();

				window.setTimeout(
					function()
					{
						this.prepareControls();

						BX.addCustomEvent(
							window,
							"BX.Crm.EntityEditor:onResolveFieldLayoutOptions",
							this._editorResolveFieldLayoutHandler
						);

						for(var key in this._editors)
						{
							if(!this._editors.hasOwnProperty(key))
							{
								continue;
							}

							var editor = this._editors[key];
							if(!editor.hasLayout())
							{
								editor.layout();
							}
						}

						BX.removeCustomEvent(
							window,
							"BX.Crm.EntityEditor:onResolveFieldLayoutOptions",
							this._editorResolveFieldLayoutHandler
						);

					}.bind(this),
					0
				);
			}
		},
		onAfterEditorLayout: function(sender)
		{
			if(!this._editors.hasOwnProperty(sender.getId()))
			{
				return;
			}

			if(this.isReadyForLayout())
			{
				this.unbindAfterEditorLayout();

				var notFoundCounter = 0;
				for(var key in this._editors)
				{
					if (!this._editors.hasOwnProperty(key))
					{
						continue;
					}

					var editor = this._editors[key];
					if(editor.isPersistent())
					{
						continue;
					}

					notFoundCounter++;
					this.clearSecondaryEntityLayout(editor.getEntityId());
				}

				if(this.isQueueEnabled() && (this._entityIds.length - notFoundCounter) < 2)
				{
					//This queue item is outdated - move to the next item
					this.moveToNextQueueItem();
				}
				else
				{
					this.layout();
					if(this._primaryEntityId > 0)
					{
						//reload selected primary entity
						window.setTimeout(
							function()
								{
									this.removeOnloadState();
									this.setupPrimaryEntity(this._primaryEntityId, true);
								}.bind(this),
							0
						);
					}
				}
			}
		},
		onEditorRefreshLayout: function(sender)
		{
			if(!this._editors.hasOwnProperty(sender.getId()) && this._primaryEditor !== sender)
			{
				return;
			}

			for(var i = 0, length = this._controls.length; i < length; i++)
			{
				this._controls[i].refreshLayout();
			}
		},
		onEditorResolveFieldLayout: function(sender, eventArgs)
		{
			if(!this._editors.hasOwnProperty(sender.getId()) && this._primaryEditor !== sender)
			{
				return;
			}

			var controlId = eventArgs["field"].getId();
			var control = this.getControlById(controlId, true);
			if(control)
			{
				eventArgs["layoutOptions"]["isNeedToDisplay"] = control.checkIfNeedToDisplay();
			}
		},
		setCurrentActiveBtn: function(item)
		{
			if(!item)
			{
				return;
			}

			var columns = this.getEditorColumns();
			for(var i = 0, length = columns.length; i < length; i++)
			{
				var currentItem = columns[i];

				if(currentItem === item)
				{
					currentItem.button.classList.add('crm-entity-merger-column-btn-active');
					currentItem.column.classList.add('crm-entity-merger-column-item-active');
				}
				else
				{
					currentItem.button.classList.remove('crm-entity-merger-column-btn-active');
					currentItem.column.classList.remove('crm-entity-merger-column-item-active');
				}
			}

			this.removeOnloadState();
			this.setupPrimaryEntity(item["entityId"]);

		},
		setCurrentActiveColumn: function(item)
		{
			if(!item)
			{
				return;
			}

			if(!item.column.classList.contains('crm-entity-merger-column-onload-state'))
			{
				return;
			}

			item.button.querySelector('.crm-entity-merger-column-btn-radio').checked = true;
			this.setCurrentActiveBtn(item);
		},
		removeOnloadState: function()
		{
			var itemColumn = document.querySelectorAll('.crm-entity-merger-column-item');

			for (var i = 0; i < itemColumn.length; i++)
			{
				var itemColumnElement = itemColumn[i];
				itemColumnElement.classList.remove("crm-entity-merger-column-onload-state");
			}

			this.entityWrapper.classList.remove('crm-entity-merger-onload-sidebar-state');
		},
		getFirstEntitySwitchClick: function()
		{
			for(var item in this._editors)
			{
				if (!this._editors.hasOwnProperty(item))
				{
					continue;
				}

				var innerItem = this._editors[item].getContainer();
				innerItem.closest(".crm-entity-merger-column-item").classList.add("crm-entity-merger-column-onload-state");
				this.entityWrapper.classList.add('crm-entity-merger-onload-sidebar-state');
			}
		},
		setupPrimaryEntity: function(entityId, forced)
		{
			if(!BX.type.isInteger(entityId))
			{
				entityId = parseInt(entityId);
				if(isNaN(entityId))
				{
					throw "Crm.EntityMerger. Parameter 'entityId' must be integer.";
				}
			}

			if(!forced && this._primaryEntityId === entityId)
			{
				return;
			}

			this.setPrimaryEntity(entityId, forced);
			this.setupPrimaryEditor();
		},
		synchronizeVerticalScroll: function()
		{
			this._scrollerY.handleColumnScroll();
		},
		postpone: function()
		{
			if(!this.isQueueEnabled())
			{
				window.setTimeout(
					function(){ this.close(); }.bind(this),
					0
				);
				return;
			}

			BX.onCustomEvent(this, "onPostponeStart");
			var queueId = BX.prop.getString(this.getDeduplicationCriterionData(), 'queueId', null);
			if (queueId)
			{
				var action = "postponeDedupeItemById";
				var data = {
					queueId: queueId
				};
			}
			else
			{
				var action = "postponeDedupeItem";
				var data = {
					entityTypeName: this.getEntityTypeName(),
					typeId: BX.prop.getInteger(this._dedupeCriterionData, 'typeId', 0),
					matches: BX.prop.getObject(this._dedupeCriterionData, 'matches', {}),
					scope: BX.prop.getString(this._dedupeConfig, "scope", ""),
					isAutomatic: this._isAutomatic ? 1 : 0
				};
			}
			BX.ajax.runComponentAction(
				"bitrix:crm.entity.merger",
				action,
				{
					data: data
				}
			).then(
				function(response)
				{
					BX.onCustomEvent(this, "onPostponeComplete");

					this._dedupeQueueInfo["offset"]--;
					this.moveToNextQueueItem().then(
						function(success)
						{
							if(!success)
							{
								this.close();
							}
						}.bind(this)
					);
				}.bind(this)
			).catch(
				function(data)
				{
					if (this.hasQueueNotFoundError(data))
					{
						this.reloadPage();
						return;
					}
					BX.onCustomEvent(this, "onPostponeError");

					var messages = [];
					var errors = BX.prop.getArray(data, "errors", []);
					for(var i = 0, length = errors.length; i < length; i++)
					{
						messages.push(BX.prop.getString(errors[i], "message", ""));
					}

					BX.UI.Notification.Center.notify(
						{
							content: messages.join("\n"),
							position: "top-right",
							autoHideDelay: 5000
						}
					);
				}.bind(this)
			);
		},
		merge: function()
		{
			var primaryEntityId = this.getPrimaryEntityId();
			var secondaryEntityIds = this.getSecondaryEntityIds();

			if(primaryEntityId <= 0)
			{
				BX.UI.Notification.Center.notify(
					{
						content: this.getMessage("primaryEntityNotFound"),
						position: "top-right",
						autoHideDelay: 5000
					}
				);
				return;
			}

			if(secondaryEntityIds.length === 0)
			{
				BX.UI.Notification.Center.notify(
					{
						content: this.getMessage("entitiesNotFound"),
						position: "top-right",
						autoHideDelay: 5000
					}
				);
				return;
			}

			var map = {};
			var allConflictsResolved = true;
			var conflictCheckOptions = { scrollToConflict: true };
			for(var i = 0, length = this._controls.length; i < length; i++)
			{
				var control = this._controls[i];
				if(!(allConflictsResolved = control.checkIfConflictResolved(conflictCheckOptions)))
				{
					break;
				}
				control.saveMappedData(map);
			}

			if(!allConflictsResolved)
			{
				BX.UI.Notification.Center.notify(
					{
						content: this.getMessage("unresolvedConflictsFound"),
						position: "top-right",
						autoHideDelay: 5000
					}
				);
				return;
			}

			var promise;
			if(this.isQueueEnabled())
			{
				var queueId = BX.prop.getString(this.getDeduplicationCriterionData(), 'queueId', null);
				if (queueId)
				{
					var action = "mergeDedupeQueueById";
					var data = {
						queueId: queueId,
						offset: this._dedupeQueueInfo["offset"],
						seedEntityIds: secondaryEntityIds,
						targEntityId: primaryEntityId,
						map: map
					};
				}
				else
				{
					var action = "mergeDedupeQueueItem";
					var data = {
						entityTypeName: this.getEntityTypeName(),
						typeNames: BX.prop.getArray(this._dedupeConfig, "typeNames", []),
						scope: BX.prop.getString(this._dedupeConfig, "scope", ""),
						offset: this._dedupeQueueInfo["offset"],
						seedEntityIds: secondaryEntityIds,
						targEntityId: primaryEntityId,
						map: map
					};
				}

				promise = BX.ajax.runComponentAction(
					"bitrix:crm.entity.merger",
					action,
					{
						data: data
					}
				);
			}
			else
			{
				promise = BX.ajax.runComponentAction(
					"bitrix:crm.entity.merger",
					"merge",
					{
						data:
							{
								entityTypeName: this.getEntityTypeName(),
								seedEntityIds: secondaryEntityIds,
								targEntityId: primaryEntityId,
								map: map
							}
					}
				);
			}

			if(!promise)
			{
				return;
			}

			BX.onCustomEvent(this, "onMergeStart");
			promise.then(
				function(response)
				{
					BX.onCustomEvent(this, "onMergeComplete");

					var skipped = BX.prop.getInteger(this._dedupeQueueInfo, 'skipped', 0);
					var data = BX.prop.getObject(response, "data", {});
					if(this.isQueueEnabled())
					{
						var offsetDelta = 1;
						var queueInfo = BX.prop.getObject(data, "QUEUE_INFO", null);
						if(queueInfo)
						{
							//we received new queue info and must keep current offset position.
							this._dedupeQueueInfo = queueInfo;
							offsetDelta = 0;
						}

						BX.localStorage.set(
							"onCrmEntityMergeComplete",
							{
								entityTypeId: this.getEntityTypeId(),
								entityTypeName: this.getEntityTypeName(),
								context: this._externalContextId,
								length: this._dedupeQueueInfo["length"],
								skipped: skipped
							},
							10
						);

						this.addQueueOffset(offsetDelta).then(
							function(success)
							{
								if(!success)
								{
									this.close();
								}
							}.bind(this)
						);
					}
					else
					{
						BX.localStorage.set(
							"onCrmEntityMergeComplete",
							{
								entityTypeId: this.getEntityTypeId(),
								entityTypeName: this.getEntityTypeName(),
								context: this._externalContextId,
								seedEntityIds: secondaryEntityIds,
								targEntityId: primaryEntityId,
								skipped: skipped
							},
							100
						);

						window.setTimeout(
							function(){ this.close(); }.bind(this),
							0
						);
					}
				}.bind(this)
			).catch(
				function(response)
				{
					if (this.hasQueueNotFoundError(response))
					{
						this.reloadPage();
						return;
					}
					BX.onCustomEvent(this, "onMergeError");

					var messages = [];
					var errors = BX.prop.getArray(response, "errors", []);
					for(var i = 0, length = errors.length; i < length; i++)
					{
						messages.push(BX.prop.getString(errors[i], "message", ""));
					}

					BX.UI.Notification.Center.notify(
						{
							content: messages.join("\n"),
							position: "top-right",
							autoHideDelay: 5000
						}
					);
				}.bind(this)
			);
		},
		markEntityAsNonDuplicate: function(entityId)
		{
			var primaryEntityId = this.getPrimaryEntityId();
			if(primaryEntityId <= 0 || entityId <= 0)
			{
				return;
			}

			var criterionData = this.getDeduplicationCriterionData();
			if(!BX.type.isPlainObject(criterionData))
			{
				return;
			}

			var queueId = BX.prop.getString(criterionData, 'queueId', null);
			if (queueId)
			{
				var action = "markAsNonDuplicatesById";
				var data = {
					queueId: queueId,
					leftEntityID: primaryEntityId,
					rightEntityID: entityId,
					matches: BX.prop.getObject(criterionData, 'matches', {}),
					offset: this._dedupeQueueInfo["offset"]
				};
			}
			else
			{
				var action = "markAsNonDuplicates";
				var data = {
					entityTypeName: this.getEntityTypeName(),
					leftEntityID: primaryEntityId,
					rightEntityID: entityId,
					indexType: BX.prop.getInteger(criterionData, 'typeId', 0),
					matches: BX.prop.getObject(criterionData, 'matches', {}),
					queueInfoParams: {
						typeNames: BX.prop.getArray(this._dedupeConfig, "typeNames", []),
						scope: BX.prop.getString(this._dedupeConfig, "scope", ""),
						offset: this._dedupeQueueInfo["offset"],
						isAutomatic: this._isAutomatic ? 1 : 0
					}
				};
			}
			BX.ajax.runComponentAction(
				"bitrix:crm.entity.merger",
				action,
				{
					data: data
				}
			).then(
				function(response)
				{
					var skipped = BX.prop.getInteger(this._dedupeQueueInfo, 'skipped', 0);
					var data = BX.prop.getObject(response, "data", {});
					var queueInfo = BX.prop.getObject(data, "QUEUE_INFO", null);
					if (queueInfo)
					{
						this._dedupeQueueInfo = queueInfo;
					}
					this._dedupeQueueInfo['skipped'] = skipped + 1;
					this.clearSecondaryEntityLayout(entityId);
					this.processSecondaryEntityRemoval();
				}.bind(this)
			).catch(
				function(data)
				{
					if (this.hasQueueNotFoundError(data))
					{
						this.reloadPage();
					}
				}.bind(this)
			);
		},
		getPrimaryEntityUrl: function()
		{
			for (var editorId in this._editors)
			{
				if (this._editors[editorId].getEntityId() == this._primaryEntityId)
				{
					return BX.prop.getString(this._editors[editorId]._settings, "entityDetailsUrl", "");
				}
			}
			return '';
		},
		openEntityDetails: function(entityId)
		{
			var url = this.getEntityDetailsUrl(entityId);
			if(url === "")
			{
				return;
			}

			if(!this._externalEventHandler)
			{
				this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
				BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
			}

			BX.Crm.Page.open(url);
		},
		hasQueueNotFoundError: function(data)
		{
			var errors = BX.prop.getArray(data, "errors", []);
			return errors.reduce(
				function(hasQueueNotFoundError, error)
				{
					return hasQueueNotFoundError ||
						BX.prop.getString(error, "code", '') === 'QUEUE_NOT_FOUND';
				},
				false
			);
		},
		reloadPage: function()
		{
			var loader = new BX.Loader({ target: document.body });
			loader.show();
			location.reload();
		},
		onExternalEvent: function(params)
		{
			var key = BX.prop.getString(params, "key", "");
			if(key !== "onCrmEntityUpdate" && key !== "onCrmEntityDelete")
			{
				return;
			}

			var eventData = BX.prop.getObject(params, "value", {});
			var entityTypeId = BX.prop.getInteger(eventData, "entityTypeId", 0);
			var entityId = BX.prop.getInteger(eventData, "entityId", 0);
			if(entityTypeId === this.getEntityTypeId() && this.containsEntityId(entityId))
			{
				if(key === "onCrmEntityUpdate")
				{
					window.setTimeout(
						function(){ this.reloadEntityEditor(entityId); }.bind(this),
						0
					);
				}
				else //key === "onCrmEntityDelete"
				{
					window.setTimeout(
						function()
						{
							this.clearSecondaryEntityLayout(entityId);
							this.processSecondaryEntityRemoval();
						}.bind(this),
						0
					);
				}
			}
		},
		animateSidebar: function(fn)
		{
			var sidebar = document.body.querySelector('.crm-entity-merger-sidebar');
			sidebar.classList.add('crm-entity-merger-sidebar-closing');
			sidebar.addEventListener('transitionend', function handleTransitionEnd() {
				sidebar.removeEventListener('transitionend', handleTransitionEnd);
				fn();
			});
		},
		animateColumns: function(fn)
		{
			var columns = this.getEditorColumns();
			if (!columns.length)
			{
				fn();
				return;
			}

			var container = document.querySelector('.crm-entity-merger-column');
			var leftEar = container.querySelector('.crm-entity-merger-ear-left');
			var rightEar = container.querySelector('.crm-entity-merger-ear-right');

			container.classList.add('crm-entity-merger-column-closing');

			if (rightEar)
			{
				// rightEar.remove();
				rightEar.parentNode.removeChild(rightEar);
			}

			if (leftEar)
			{
				// leftEar.remove();
				leftEar.parentNode.removeChild(leftEar);
			}

			var delay = 0;
			var duration = 800;
			var delayDelta = Math.ceil(duration / columns.length);
			var marginRight = 20;

			for (var i = columns.length - 1; i >= 0; i--)
			{
				var columnButton = columns[i].button;
				var columnItem = columns[i].column;

				var columnBtnWidth = columnButton.offsetWidth + marginRight;
				var columnWidth = columnItem.offsetWidth + marginRight;

				columnButton.style.transitionDelay = delay + 'ms';
				columnButton.style.transitionDuration = duration - delay + 'ms';
				columnButton.style.transform = 'translateX(-' + columnBtnWidth * (i + 1) + 'px)';
				columnButton.style.opacity = 0;

				columnItem.style.transitionDelay = delay + 'ms';
				columnItem.style.transitionDuration = duration - delay + 'ms';
				columnItem.style.transform = 'translateX(-' + columnWidth * (i + 1) + 'px)';
				columnItem.style.opacity = 0;

				delay += delayDelta;
			}

			columnItem.addEventListener('transitionend', function handleTransitionEnd() {
				columnItem.removeEventListener('transitionend', handleTransitionEnd);
				fn();
			});
		},
	};

	if(typeof(BX.Crm.EntityMerger.entityEditorUrls) === "undefined")
	{
		BX.Crm.EntityMerger.entityEditorUrls = {};
	}
	BX.Crm.EntityMerger.registerEntityEditorUrl = function(entityTypeName, url)
	{
		this.entityEditorUrls[entityTypeName] = url;
	};
	BX.Crm.EntityMerger.getEntityEditorUrl = function(entityTypeName)
	{
		return BX.prop.getString(this.entityEditorUrls, entityTypeName, "");
	};
	BX.Crm.EntityMerger.items = {};
	BX.Crm.EntityMerger.get = function(id)
	{
		return this.items.hasOwnProperty(id) ? this.items[id] : null;
	};

	if(typeof(BX.Crm.EntityMerger.messages) === "undefined")
	{
		BX.Crm.EntityMerger.messages = {};
	}

	BX.Crm.EntityMerger.create = function(id, settings)
	{
		var self = new BX.Crm.EntityMerger();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

if(typeof BX.Crm.EntityMergerHeader === "undefined")
{
	BX.Crm.EntityMergerHeader = function()
	{
		this._id = "";
		this._settings = {};
		this._entityId = 0;
		this._merger = null;

		this._container = null;
		this._label = null;
		this._input = null;
		this._title = null;
		this._buttonWrapper = null;

		this._markAsNonDuplicateButton = null;
		this._markAsNonDuplicateButtonHandler = BX.delegate(this.onMarkAsNonDuplicateButtonClick, this);

		this._primaryEntityChangeHandler = BX.delegate(this.onPrimaryEntityChange, this);

		this._hasLayout = false;
	};
	BX.Crm.EntityMergerHeader.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._entityId = BX.prop.getInteger(this._settings, "entityId", 0);
			this._merger = BX.prop.get(this._settings, "merger");
			if(!this._merger)
			{
				throw "Crm.EntityMergerHeader: Could not find param 'merger'.";
			}
			this._container = BX.prop.getElementNode(this._settings, "container");
			if(!this._container)
			{
				throw "Crm.EntityMergerHeader: Could not find param 'container'.";
			}

			BX.addCustomEvent(this._merger, "onPrimaryEntityChange", this._primaryEntityChangeHandler);
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			return BX.prop.getString(BX.Crm.EntityMergerHeader.messages, name, name);
		},
		getEntityId: function()
		{
			return this._entityId;
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			var switchName = BX.prop.getString(this._settings, "switchName", "");
			var switchId = switchName + "_" + this._entityId;

			this._input = BX.create("input",
				{
					props:
						{
							id: switchId,
							name: switchName,
							type: "radio",
							className: "crm-entity-merger-column-btn-radio"
						},
					attrs: { "data-entity-id" : this._entityId }
				}
			);

			this.prepareTitle();

			this._label = BX.create("label",
				{
					props: { className: "crm-entity-merger-column-btn-label", for: switchId },
					children:
						[
							BX.create("span",
								{
									props: { className: "crm-entity-merger-column-btn-radio-container" },
									children:
										[
											this._input,
											BX.create("span", { props: { className: "crm-entity-merger-column-btn-radio-mark" } })
										]
								}
							),
							BX.create("span",
								{
									props: { className: "crm-entity-merger-column-btn-link-dark-box" },
									children:
										[
											BX.create("a",
												{
													props: { className: "crm-entity-merger-column-btn-link-dark", href: "#" },
													children: [ this._title ]
												}
											)
										],
								}
							)
						]
				}
			);

			BX.bind(this._label, "click", BX.delegate(this.onLabelClick, this));
			this._container.appendChild(this._label);

			this._markAsNonDuplicateButton = BX.create("a",
				{
					props: { className: "crm-entity-merger-column-btn-link", href: "#" },
					text: this.getMessage("markAsNonDuplicate"),
					events: { click: this._markAsNonDuplicateButtonHandler }
				}
			);
			this._buttonWrapper = BX.create("div",
				{
					props: { className: "crm-entity-merger-column-btn-link-box" },
					children: [ this._markAsNonDuplicateButton ]
				}
			);
			this._container.appendChild(this._buttonWrapper);

			BX.bind(this._container, "mouseenter", BX.delegate(this.onMouseEnter, this));
			BX.bind(this._container, "mouseleave", BX.delegate(this.onMouseLeave, this));

			this._hasLayout = true;
			this.adjustLayout();
		},
		prepareTitle: function()
		{
			const creationDate = this._merger.getEntityCreationDate(this._entityId);
			const date = BX.parseDate(creationDate);
			if (!date)
			{
				return;
			}

			const isCurrentYear = (date.getFullYear() === (new Date()).getFullYear());
			const defaultFormat = (
				isCurrentYear
					? BX.Main.DateTimeFormat.getFormat('DAY_MONTH_FORMAT')
					: BX.Main.DateTimeFormat.getFormat('LONG_DATE_FORMAT')
			);
			const formats = [
				['today', 'today'],
				['tommorow', 'tommorow'],
				['yesterday', 'yesterday'],
				['', defaultFormat],
			];

			const dateText = BX.Main.DateTimeFormat.format(formats, date);

			const formattedDate = BX.prop
				.getString(this._settings, 'titleTemplate', '')
				.replace(/#DATE_CREATE#/, dateText)
			;
			this._title = BX.Tag.render`<span class="crm-entity-merger-column-btn-text">${formattedDate}</span>`;
		},
		adjustLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			var primaryEntityId = this._merger.getPrimaryEntityId();
			if(this._entityId > 0 && this._entityId === primaryEntityId)
			{
				this._input.checked = true;
			}

			var enableDeduplication = this._merger.isDeduplicationMode();
			if(enableDeduplication)
			{
				enableDeduplication = this._entityId > 0 && primaryEntityId > 0 && this._entityId !== primaryEntityId;
			}
			this._markAsNonDuplicateButton.style.display = enableDeduplication ? "" : "none";
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			BX.cleanNode(this._container);
			this._hasLayout = false;
		},
		release: function()
		{
			BX.removeCustomEvent(this._merger, "onPrimaryEntityChange", this._primaryEntityChangeHandler);
		},
		onPrimaryEntityChange: function()
		{
			this.adjustLayout();
		},
		onHeaderButtonClick: function(e)
		{
			this._merger.openEntityDetails(this._entityId);
			e.preventDefault();
		},
		onMarkAsNonDuplicateButtonClick: function(e)
		{
			this._merger.markEntityAsNonDuplicate(this._entityId);
			e.preventDefault();
		},
		onMouseEnter: function(e)
		{
			this._merger.selectColumnItem(this._merger.getColumnItemByEntityId(this._entityId));
		},
		onMouseLeave: function(e)
		{
			this._merger.unSelectColumnItem(this._merger.getColumnItemByEntityId(this._entityId));
		},
		onLabelClick: function(e)
		{
			this._merger.setCurrentActiveBtn(this._merger.getColumnItemByEntityId(this._entityId));
		}
	};
	if(typeof(BX.Crm.EntityMergerHeader.messages) === "undefined")
	{
		BX.Crm.EntityMergerHeader.messages = {};
	}
	BX.Crm.EntityMergerHeader.create = function(id, settings)
	{
		var self = new BX.Crm.EntityMergerHeader();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityMergerVerticalScroller === "undefined")
{
	BX.Crm.EntityMergerVerticalScroller = function()
	{
		this.slider = document.querySelector('.ui-page-slider-wrapper');
		this.columnContainer = document.body.querySelector('.crm-entity-merger-column-container');
		this.sidebarColumn = document.body.querySelector('.crm-entity-merger-sidebar-inner');
		this.bottomPanel = document.body.querySelector('.ui-button-panel-wrapper');
		this.bottomPanelHeader = document.body.querySelector('#queueStatisticsWrapper');
		this.skeleton = document.body.querySelector('.crm-entity-merger-sidebar-skeleton');
	};
	BX.Crm.EntityMergerVerticalScroller.prototype =
	{
		initialize: function()
		{
			this.columnContainerHeight = this.columnContainer.offsetHeight;
			this.sidebarColumnHeight = this.sidebarColumn.offsetHeight;

			BX.bind(this.columnContainer, "mouseenter", this.handleColumnMouseEnter.bind(this));
			BX.bind(this.columnContainer, "mouseleave", this.handleColumnMouseLeave.bind(this));
			BX.bind(this.columnContainer, "mouseenter", this.handleColumnMouseClick.bind(this));
			BX.bind(this.sidebarColumn, "mouseenter", this.handleSidebarMouseEnter.bind(this));
			BX.bind(this.sidebarColumn, "mouseleave", this.handleSidebarMouseLeave.bind(this));

			this.handleSidebarScroll = this.handleSidebarScroll.bind(this);
			this.handleColumnScroll = this.handleColumnScroll.bind(this);

			BX.bind(window, "resize", this.adjustHeight.bind(this));
		},
		adjustHeight: function()
		{
			if(this.slider)
			{
				if(this.skeleton)
				{
					this.skeleton.style.height = this.columnContainer.scrollHeight + 'px';
				}
				this.sidebarColumn.style.height = window.innerHeight - (this.sidebarColumn.getBoundingClientRect().top + this.bottomPanel.offsetHeight) + 'px';
			}
			else
			{
				this.sidebarColumn.style.height = (this.columnContainer.getBoundingClientRect().top + this.columnContainer.getBoundingClientRect().bottom) - this.columnContainerHeight + 'px';
			}
		},

		handleColumnMouseClick: function()
		{
			BX.bind(this.columnContainer, "click", this.handleColumnScroll);
		},

		handleColumnMouseEnter: function()
		{
			BX.bind(this.columnContainer, "scroll", this.handleColumnScroll);
		},

		handleColumnMouseLeave: function()
		{
			BX.unbind(this.columnContainer, "scroll", this.handleColumnScroll);
		},

		handleSidebarMouseEnter: function()
		{
			BX.bind(this.sidebarColumn, "scroll", this.handleSidebarScroll);
		},

		handleSidebarMouseLeave: function()
		{
			BX.unbind(this.sidebarColumn, "scroll", this.handleSidebarScroll);
		},

		handleSidebarScroll: function()
		{
			this.columnContainer.scrollTop = this.sidebarColumn.scrollTop;
		},

		handleColumnScroll: function()
		{
			this.sidebarColumn.scrollTop = this.columnContainer.scrollTop;
		},

		layout: function()
		{
			this.adjustHeight();
		}
	};
	BX.Crm.EntityMergerVerticalScroller.create = function()
	{
		var self = new BX.Crm.EntityMergerVerticalScroller();
		self.initialize();
		return self;
	};
}

if(typeof BX.Crm.EntityMergerScroller === "undefined")
{
	BX.Crm.EntityMergerScroller = function()
	{
		this.earTimer = null;
		this.columnLayout = {
			earLeft: null,
			earRight: null,
		};
		this.outerColumnContainer = document.body.querySelector('.crm-entity-merger-column');
		this.columnInner = document.body.querySelector('.crm-entity-merger-column-inner');
	};

	BX.Crm.EntityMergerScroller.prototype =
	{
		initialize: function()
		{
			BX.bind(window, "resize", this.adjustEars.bind(this));
			BX.bind(this.columnInner, "scroll", this.adjustEars.bind(this));
			BX.bind(window, "resize", this.centerEarsByContainerHeight.bind(this));
			BX.bind(this.outerColumnContainer, "scroll", this.getColumnContainerWidth.bind(this));
			BX.bind(window, "resize", this.getColumnContainerWidth.bind(this));
			setTimeout(function(){this.getColumnContainerWidth()}.bind(this),100);
			// this.getColumnContainerWidth();
		},
		getColumnContainerWidth: function()
		{
			var columnContainer = this.outerColumnContainer.querySelector('.crm-entity-merger-column-head');
			var headerContainer = this.outerColumnContainer.querySelector('.crm-entity-merger-column-container');

			if(!this.outerColumnContainer.classList.contains('crm-entity-merger-right-ear-shown'))
			{
				columnContainer.style.width = '100' + '%';
				headerContainer.style.width = '100' + '%';
			}
			else
			{
				columnContainer.style.width = 'auto';
				headerContainer.style.width = 'auto';
			}
		},
		adjustEars: function()
		{
			var wrapper = this.columnInner;
			var scroll = wrapper.scrollLeft;

			var isLeftVisible = scroll > 0;
			var isRightVisible = wrapper.scrollWidth > (Math.round(scroll + wrapper.offsetWidth));

			this.outerColumnContainer.classList[isLeftVisible ? "add" : "remove"]("crm-entity-merger-left-ear-shown");
			this.outerColumnContainer.classList[isRightVisible ? "add" : "remove"]("crm-entity-merger-right-ear-shown");
		},
		/**
		 *
		 * @returns {Element}
		 */
		getLeftEar: function()
		{
			if (this.columnLayout.earLeft)
			{
				return this.columnLayout.earLeft;
			}

			this.columnLayout.earLeft = BX.create("div", {
				attrs: {
					className: "crm-entity-merger-ear-left"
				},
				events: {
					mouseenter: this.scrollToLeft.bind(this),
					mouseleave: this.stopAutoScroll.bind(this)
				}
			});

			return this.columnLayout.earLeft;
		},
		/**
		 *
		 * @returns {Element}
		 */
		getRightEar: function()
		{
			if (this.columnLayout.earRight)
			{
				return this.columnLayout.earRight;
			}

			this.columnLayout.earRight = BX.create("div", {
				attrs: {
					className: "crm-entity-merger-ear-right"
				},
				events: {
					mouseenter: this.scrollToRight.bind(this),
					mouseleave: this.stopAutoScroll.bind(this)
				}
			});

			return this.columnLayout.earRight;
		},
		scrollToRight: function()
		{
			this.earTimer = setInterval(function() {
				this.columnInner.scrollLeft += 10;
			}.bind(this), 20)
		},
		scrollToLeft: function()
		{
			this.earTimer = setInterval(function() {
				this.columnInner.scrollLeft -= 10;
			}.bind(this), 20)
		},
		stopAutoScroll: function()
		{
			clearInterval(this.earTimer);

			//?
			jsDD.refreshDestArea();
		},
		centerEarsByContainerHeight: function()
		{
			var slider = document.querySelector('.ui-page-slider-wrapper');
			var columnContainer = document.body.querySelector('.crm-entity-merger-column-container');
			var columnContainerHeight = columnContainer.offsetHeight;
			var bottomPanel = document.body.querySelector('.ui-button-panel-wrapper');

			if(slider)
			{
				columnContainer.style.height = window.innerHeight - (columnContainer.getBoundingClientRect().top + bottomPanel.offsetHeight) + 'px';
			}
			else
			{
				columnContainer.style.height = (columnContainer.getBoundingClientRect().top + columnContainer.getBoundingClientRect().bottom) - columnContainerHeight + 'px';
			}
		},
		layout: function()
		{
			this.outerColumnContainer.appendChild(this.getLeftEar());
			this.outerColumnContainer.appendChild(this.getRightEar());

			this.centerEarsByContainerHeight();
			this.adjustEars();
		}
	};
	BX.Crm.EntityMergerScroller.create = function()
	{
		var self = new BX.Crm.EntityMergerScroller();
		self.initialize();
		return self;
	};
}

if(typeof BX.Crm.EntityMergerControl === "undefined")
{
	BX.Crm.EntityMergerControl = function ()
	{
		this._id = "";
		this._settings = {};
		this._merger = null;
		this._editorControls = null;
		this._controls = null;

		this._layoutOptions = null;
	};
	BX.Crm.EntityMergerControl.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};
			this._merger = BX.prop.get(this._settings, "merger");
			this._editorControls = BX.prop.getArray(this._settings, "editorControls", []);
			this._controls = [];
			this.doInitialize();
		},
		doInitialize: function()
		{
		},
		getId: function()
		{
			return this._id;
		},
		getSchemeElement: function()
		{
			return this._editorControls.length > 0 ? this._editorControls[0].getSchemeElement() : null;
		},
		getAllEntityIds: function()
		{
			var results = [];
			for(var i = 0, length = this._editorControls.length; i < length; i++)
			{
				results.push(this._editorControls[i].getEditor().getEntityId());
			}
			return results;
		},
		getEditorControlIndex: function(editorControl)
		{
			for(var i = 0, length = this._editorControls.length; i < length; i++)
			{
				if(editorControl === this._editorControls[i])
				{
					return i;
				}
			}
			return  -1;
		},
		getChildControlById: function(id)
		{
			for(var i = 0, length = this._controls.length; i < length; i++)
			{
				if(this._controls[i].getId() === id)
				{
					return this._controls[i];
				}
			}
			return null;
		},
		addEditorControl: function(editorControl)
		{
			if(!editorControl)
			{
				return;
			}

			this._editorControls.push(editorControl);
			this.doAddEditorControl(editorControl);
		},
		doAddEditorControl: function(editorControl)
		{
		},
		removeEditorControl: function(editorControl)
		{
			if(!editorControl)
			{
				return;
			}

			var index = this.getEditorControlIndex(editorControl);
			if(index >= 0)
			{
				this._editorControls.splice(index, 1);
			}
			this.doRemoveEditorControl(editorControl);
		},
		doRemoveEditorControl: function(editorControl)
		{
		},
		getLayoutOptions: function()
		{
			return this._layoutOptions;
		},
		setLayoutOptions: function(options)
		{
			this._layoutOptions = options;
		},
		layout: function()
		{
		},
		clearLayout: function()
		{
		},
		refreshLayout: function()
		{
			this.clearLayout();
			this.layout();
		},
		adjust: function()
		{
		},
		saveMappedData: function(data)
		{
			throw "Crm.EntityMergerControl: Method 'saveMappedData' is not implemented";
		},
		hasConflict: function()
		{
			return false;
		},
		setHasConflict: function(hasConflict)
		{
			throw "Crm.EntityMergerControl: Method 'setHasConflict' is not implemented";
		},
		checkIfConflictResolved: function(options)
		{
			return true;
		},
		applyMergeResults: function(resultData, updateModelData, currentModelData)
		{
		},
		registerEntityEditor: function(editor)
		{
		},
		unregisterEntityEditor: function(editor)
		{
		},
		scrollInToView: function()
		{
			if(this._editorControls.length === 0)
			{
				return;
			}

			var wrapper = this._editorControls[0].getWrapper();
			if(wrapper)
			{
				wrapper.scrollIntoView();
				window.setTimeout(
					function(){ this._merger.synchronizeVerticalScroll(); }.bind(this),
					0
				);
			}
		}
	};
}

if(typeof BX.Crm.EntityMergerField === "undefined")
{
	/**
	 * @extends BX.Crm.EntityMergerControl
	 * @constructor
	 */
	BX.Crm.EntityMergerField = function ()
	{
		this._controller = null;

		this._hasConflict = false;
		this._isMultiple = false;
		this._sourceEntityIds = [];
		this._fieldSwitches = null;

		this._editorControlLayoutHandler = this.onEditorControlLayout.bind(this);
		this._enableAdjusting = true;
		this._hasLayout = false;

		BX.Crm.EntityMergerField.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.EntityMergerField, BX.Crm.EntityMergerControl);

	BX.Crm.EntityMergerField.prototype.doInitialize = function()
	{
		this._controller = BX.prop.get(this._settings, "controller", null);
	};
	BX.Crm.EntityMergerField.prototype.layout = function()
	{
		if(this._hasLayout)
		{
			return;
		}

		var i, length;
		for(i = 0, length = this._editorControls.length; i < length; i++)
		{
			if(!this._editorControls[i].hasLayout())
			{
				BX.removeCustomEvent(window, "BX.Crm.EntityEditorField:onLayout", this._editorControlLayoutHandler);
				BX.removeCustomEvent(window, "BX.UI.EntityEditorField:onLayout", this._editorControlLayoutHandler);
				BX.addCustomEvent(window, "BX.Crm.EntityEditorField:onLayout", this._editorControlLayoutHandler);
				BX.addCustomEvent(window, "BX.UI.EntityEditorField:onLayout", this._editorControlLayoutHandler);
				return;
			}
		}

		this._fieldSwitches = [];
		var interlacingIndex = BX.prop.getInteger(this._layoutOptions, "interlacingIndex", 0);
		for(i = 0, length = this._editorControls.length; i < length; i++)
		{
			var editorControl = this._editorControls[i];

			if(interlacingIndex > 0)
			{
				var wrapper = editorControl.getWrapper();
				if(interlacingIndex % 2 > 0)
				{
					BX.addClass(wrapper, "crm-entity-merger-row-odd");
					BX.removeClass(wrapper, "crm-entity-merger-row-even");
				}
				else
				{
					BX.addClass(wrapper, "crm-entity-merger-row-even");
					BX.removeClass(wrapper, "crm-entity-merger-row-odd");
				}
			}

			if(!this._merger.checkIfPrimaryEditorControl(editorControl) && (this._hasConflict || this._isMultiple))
			{
				var editor = editorControl.getEditor();
				var entityTypeName = editor.getEntityTypeName();
				var entityId = editor.getEntityId();

				var fieldSwitch = BX.Crm.EntityMergerFieldSwitch.create(
					(entityTypeName + "_" + entityId + "_" + editorControl.getId()),
					{
						control: this,
						editorControl: editorControl,
						entityId: entityId,
						isSelected: this.findSourceEntityIndex(entityId) >= 0
					}
				);
				this._fieldSwitches.push(fieldSwitch);
				fieldSwitch.layout();
			}
		}

		this._hasLayout = true;

		window.setTimeout(
			this.adjust.bind(this),
			500
		);
	};
	BX.Crm.EntityMergerField.prototype.clearLayout = function()
	{
		if(!this._hasLayout)
		{
			return;
		}

		for(var i = 0, length = this._fieldSwitches.length; i < length; i++)
		{
			this._fieldSwitches[i].clearLayout();
		}
		this._fieldSwitches = null;

		this._hasLayout = false;
	};
	BX.Crm.EntityMergerField.prototype.checkIfHasLayout = function()
	{
		for(var i = 0, length = this._editorControls.length; i < length; i++)
		{
			if(!this._editorControls[i].hasLayout())
			{
				return false;
			}
		}
		return true;
	};
	BX.Crm.EntityMergerField.prototype.checkIfNeedToDisplay = function()
	{
		var schemeElement = this.getSchemeElement();
		if(schemeElement && !schemeElement.isMergeable())
		{
			return false;
		}

		for(var i = 0, length = this._editorControls.length; i < length; i++)
		{
			if(this._editorControls[i].isNeedToDisplay({ enableLayoutResolvers: false }))
			{
				return true;
			}
		}
		return false;
	};
	BX.Crm.EntityMergerField.prototype.onEditorControlLayout = function(sender)
	{
		if(this.getEditorControlIndex(sender) >= 0 && this.checkIfHasLayout())
		{
			BX.removeCustomEvent(window, "BX.Crm.EntityEditorField:onLayout", this._editorControlLayoutHandler);
			BX.removeCustomEvent(window, "BX.UI.EntityEditorField:onLayout", this._editorControlLayoutHandler);
			window.setTimeout(
				function(){ this.layout(); }.bind(this),
				0
			);
		}
	};
	BX.Crm.EntityMergerField.prototype.adjust = function()
	{
		if(!this._enableAdjusting)
		{
			return;
		}

		var i, length;
		var height = 0;
		for(i = 0, length = this._editorControls.length; i < length; i++)
		{
			var editorControl = this._editorControls[i];
			var editorControlWrapper = editorControl.getWrapper();

			if(this._merger.checkIfPrimaryEditorControl(editorControl))
			{
				editorControlWrapper.classList[this._hasConflict ? "add" : "remove"]("crm-entity-merger-column-cell-conflict");
			}

			var currentHeight = BX.pos(editorControlWrapper).height;
			if(currentHeight > height)
			{
				height = currentHeight;
			}
		}

		if(height > 0)
		{
			for(i = 0; i < length; i++)
			{
				this._editorControls[i].getWrapper().style.minHeight = height + "px";
			}
		}
	};
	BX.Crm.EntityMergerField.prototype.isMultiple = function()
	{
		return this._isMultiple
	};
	BX.Crm.EntityMergerField.prototype.setIsMultiple = function(isMultiple)
	{
		this._isMultiple = !!isMultiple;
	};
	BX.Crm.EntityMergerField.prototype.hasConflict = function()
	{
		return this._hasConflict
	};
	BX.Crm.EntityMergerField.prototype.setHasConflict = function(hasConflict)
	{
		this._hasConflict = !!hasConflict;
	};
	BX.Crm.EntityMergerField.prototype.checkIfConflictResolved = function(options)
	{
		var isResolved = !this._hasConflict || this._sourceEntityIds.length > 0;
		if(!isResolved && BX.prop.getBoolean(options, "scrollToConflict", false))
		{
			window.setTimeout(
				function() { this.scrollInToView(); }.bind(this),
				0
			);

			//Prevent scrolling of next field with unresolved conflict
			options["scrollToConflict"] = false;
		}
		return isResolved;
	};
	BX.Crm.EntityMergerField.prototype.getSourceEntityIds = function()
	{
		return this._sourceEntityIds;
	};
	BX.Crm.EntityMergerField.prototype.setSourceEntityIds = function(entityIds)
	{
		this._sourceEntityIds = BX.type.isArray(entityIds) ? entityIds : [];
	};
	BX.Crm.EntityMergerField.prototype.findSourceEntityIndex = function(entityId)
	{
		if(this._sourceEntityIds === null)
		{
			return -1;
		}

		for(var i = 0, length = this._sourceEntityIds.length; i < length; i++ )
		{
			if(this._sourceEntityIds[i] === entityId)
			{
				return i;
			}
		}
		return -1;
	};
	BX.Crm.EntityMergerField.prototype.addSourceEntityId = function(entityId)
	{
		if(!BX.type.isInteger(entityId))
		{
			entityId = parseInt(entityId);
			if(isNaN(entityId))
			{
				return;
			}
		}

		if(this.findSourceEntityIndex(entityId) >= 0)
		{
			return;
		}

		if(this._sourceEntityIds === null)
		{
			this._sourceEntityIds = [];
		}

		this._sourceEntityIds.push(entityId);
	};
	BX.Crm.EntityMergerField.prototype.removeSourceEntityId = function(entityId)
	{
		if(this._sourceEntityIds === null)
		{
			return;
		}

		if(!BX.type.isInteger(entityId))
		{
			entityId = parseInt(entityId);
			if(isNaN(entityId))
			{
				return;
			}
		}

		var index = this.findSourceEntityIndex(entityId);
		if(index >= 0)
		{
			this._sourceEntityIds.splice(index, 1);
		}
	};
	BX.Crm.EntityMergerField.prototype.getActiveSwitches = function()
	{
		var result = [];
		for(var i = 0, length = this._fieldSwitches.length; i < length; i++)
		{
			var fieldSwitch = this._fieldSwitches[i];
			if(fieldSwitch.isSelected())
			{
				result.push(fieldSwitch);
			}
		}
		return result;
	};
	BX.Crm.EntityMergerField.prototype.onSwitchChange = function(fieldSwitch)
	{
		var i, length;
		if(!this._isMultiple)
		{
			if(!fieldSwitch.isSelected())
			{
				fieldSwitch.setSelected(true);
			}
			else
			{
				for(i = 0, length = this._fieldSwitches.length; i < length; i++)
				{
					if(this._fieldSwitches[i] === fieldSwitch || !this._fieldSwitches[i].isSelected())
					{
						continue;
					}

					this._fieldSwitches[i].setSelected(false);
					this.removeSourceEntityId(this._fieldSwitches[i].getEntityId());
				}
			}
		}
		else if(!fieldSwitch.isSelected())
		{
			var hasActiveSwitches = false;
			for(i = 0, length = this._fieldSwitches.length; i < length; i++)
			{
				if(this._fieldSwitches[i].isSelected())
				{
					hasActiveSwitches = true;
					break;
				}
			}

			if(!hasActiveSwitches)
			{
				fieldSwitch.setSelected(true);
			}
		}

		if(fieldSwitch.isSelected())
		{
			this.addSourceEntityId(fieldSwitch.getEntityId());
		}
		else
		{
			this.removeSourceEntityId(fieldSwitch.getEntityId());
		}
		this._merger.processFieldSourceEntityChange(this, fieldSwitch.getEditorControl());
	};
	BX.Crm.EntityMergerField.prototype.saveMappedData = function(data)
	{
		if(this._controller)
		{
			this._controller.saveMappedData(this, data);
		}
	};
	BX.Crm.EntityMergerField.prototype.applyMergeResults = function(resultData, updateModelData, currentModelData)
	{
		if(this._controller)
		{
			this._controller.applyMergeResults(this, resultData, updateModelData, currentModelData);
		}
	};
	BX.Crm.EntityMergerField.prototype.adjustEditorControl = function(srcEditorControl, dstEditorControl)
	{
		if(this._controller)
		{
			this._controller.adjustEditorControl(srcEditorControl, dstEditorControl);
		}
	};
	BX.Crm.EntityMergerField.prototype.registerEntityEditor = function(editor)
	{
		if(this._editorControls.length === 0)
		{
			return;
		}

		var editorControl = editor.getControlById(this._editorControls[0].getId());
		if(editorControl)
		{
			this.addEditorControl(editorControl);
		}
	};
	BX.Crm.EntityMergerField.prototype.unregisterEntityEditor = function(editor)
	{
		if(this._editorControls.length === 0)
		{
			return;
		}

		var editorControl = editor.getControlById(this._editorControls[0].getId());
		if(editorControl)
		{
			this.removeEditorControl(editorControl);
		}
	};
	BX.Crm.EntityMergerField.prototype.getDataTagName = function()
	{
		return this._controller.resolveDataTagName(this);
	};
	BX.Crm.EntityMergerField.create = function(id, settings)
	{
		var self = new BX.Crm.EntityMergerField();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityMergerSection === "undefined")
{
	/**
	 * @extends BX.Crm.EntityMergerControl
	 * @constructor
	 */
	BX.Crm.EntityMergerSection = function ()
	{
		BX.Crm.EntityMergerSection.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.EntityMergerSection, BX.Crm.EntityMergerControl);

	BX.Crm.EntityMergerSection.prototype.doInitialize = function()
	{
		this._controls = [];

		var schemeElement = this.getSchemeElement();
		if(!schemeElement)
		{
			throw "Crm.EntityMergerSection. Could not find editor scheme element.";
		}

		var elements = schemeElement.getElements();
		for(var i = 0; i < elements.length; i++)
		{
			var controls = this._merger.createControlsBySchemeElement(elements[i]);
			for(var j = 0; j < controls.length; j++)
			{
				this._controls.push(controls[j]);
			}
		}
	};
	BX.Crm.EntityMergerSection.prototype.doAddEditorControl = function(editorControl)
	{
		for(var i = 0, length = this._controls.length; i < length; i++)
		{
			this._controls[i].addEditorControl(editorControl.getChildById(this._controls[i].getId()));
		}
	};
	BX.Crm.EntityMergerSection.prototype.doRemoveEditorControl = function(editorControl)
	{
		for(var i = 0, length = this._controls.length; i < length; i++)
		{
			this._controls[i].removeEditorControl(editorControl.getChildById(this._controls[i].getId()));
		}
	};
	BX.Crm.EntityMergerSection.prototype.layout = function()
	{
		var interlacingIndex = 0;
		for(var i = 0, length = this._controls.length; i < length; i++)
		{
			var control = this._controls[i];
			var options = {};
			if(control.checkIfNeedToDisplay() && control._enableAdjusting)
			{
				options["interlacingIndex"] = ++interlacingIndex;
			}

			control.setLayoutOptions(options);
			control.layout();
		}
	};
	BX.Crm.EntityMergerSection.prototype.clearLayout = function()
	{
		for(var i = 0, length = this._controls.length; i < length; i++)
		{
			this._controls[i].clearLayout();
		}
	};
	BX.Crm.EntityMergerSection.prototype.refreshLayout = function()
	{
		for(var i = 0, length = this._controls.length; i < length; i++)
		{
			this._controls[i].clearLayout();
			this._controls[i].layout();
		}
	};
	BX.Crm.EntityMergerSection.prototype.adjust = function()
	{
		for(var i = 0, length = this._controls.length; i < length; i++)
		{
			this._controls[i].adjust();
		}
	};
	BX.Crm.EntityMergerSection.prototype.hasConflict = function()
	{
		for(var i = 0, length = this._controls.length; i < length; i++)
		{
			if(this._controls[i].hasConflict())
			{
				return true;
			}
		}
		return false;
	};
	BX.Crm.EntityMergerSection.prototype.setHasConflict = function(hasConflict)
	{
		for(var i = 0, length = this._controls.length; i < length; i++)
		{
			this._controls[i].setHasConflict(hasConflict)
		}
	};
	BX.Crm.EntityMergerSection.prototype.checkIfConflictResolved = function(options)
	{
		for(var i = 0, length = this._controls.length; i < length; i++)
		{
			if(!this._controls[i].checkIfConflictResolved(options))
			{
				return false;
			}
		}
		return true;
	};
	BX.Crm.EntityMergerSection.prototype.saveMappedData = function(data)
	{
		for(var i = 0, length = this._controls.length; i < length; i++)
		{
			this._controls[i].saveMappedData(data)
		}
	};
	BX.Crm.EntityMergerSection.prototype.applyMergeResults = function(resultData, updateModelData, currentModelData)
	{
		for(var i = 0, length = this._controls.length; i < length; i++)
		{
			this._controls[i].applyMergeResults(resultData, updateModelData, currentModelData)
		}
	};
	BX.Crm.EntityMergerSection.prototype.registerEntityEditor = function(editor)
	{
		for(var i = 0, length = this._controls.length; i < length; i++)
		{
			this._controls[i].registerEntityEditor(editor)
		}
	};
	BX.Crm.EntityMergerSection.prototype.unregisterEntityEditor = function(editor)
	{
		for(var i = 0, length = this._controls.length; i < length; i++)
		{
			this._controls[i].unregisterEntityEditor(editor)
		}
	};
	BX.Crm.EntityMergerSection.create = function(id, settings)
	{
		var self = new BX.Crm.EntityMergerSection();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityMergerFieldController === "undefined")
{
	BX.Crm.EntityMergerFieldController = function ()
	{
	};

	BX.Crm.EntityMergerFieldController.prototype =
	{
		applyMergeResults: function(field, resultData, updateModelData, currentModelData)
		{
			var result = BX.prop.getObject(resultData, field.getId(), null);
			if(!result)
			{
				return;
			}

			field.setIsMultiple(BX.prop.getBoolean(result, "IS_MULTIPLE", false));
			field.setSourceEntityIds(BX.prop.getArray(result, "SOURCE_ENTITY_IDS", []));
			field.setHasConflict(!BX.prop.getBoolean(result, "IS_MERGED", true));

			updateModelData[field.getId()] = result.hasOwnProperty("VALUE") ? result["VALUE"] : "";
		},
		adjustEditorControl: function(srcEditorControl, dstEditorControl)
		{
			dstEditorControl.setupFromModel(
				srcEditorControl.getModel(),
				{ enableNotification : false }
			);
			dstEditorControl.refreshLayout({ reset: true });
		},
		saveMappedData: function(field, data)
		{
			var sourceIds = field.getSourceEntityIds();

			if(field.isMultiple())
			{
				if(sourceIds.length === 0)
				{
					sourceIds.push(0);
				}
				data[field.getId()] = { "SOURCE_ENTITY_IDS": sourceIds };
			}
			else if(sourceIds.length > 0)
			{
				data[field.getId()] = { "SOURCE_ENTITY_IDS": sourceIds };
			}
		},
		resolveDataTagName: function(field)
		{
			return field.getId();
		},
		getDataTagNameByEntityType: function(field, entityTypeName)
		{
			var schemeElement = field.getSchemeElement();
			if(!schemeElement)
			{
				return "";
			}

			var compoundInfos = schemeElement.getDataArrayParam("compound", null);
			if(BX.type.isArray(compoundInfos))
			{
				for(var i = 0, length = compoundInfos.length; i < length; i++)
				{
					if(BX.prop.getString(compoundInfos[i], "entityTypeName", "") === entityTypeName)
					{
						return BX.prop.getString(compoundInfos[i], "tagName", "");
					}
				}
			}
			return "";
		}
	};
	BX.Crm.EntityMergerFieldController.create = function()
	{
		return new BX.Crm.EntityMergerFieldController();
	};
}

if(typeof BX.Crm.EntityMergerClientCompanyController === "undefined")
{
	BX.Crm.EntityMergerClientCompanyController = function(isMultiple)
	{
		this._isMultiple = !!isMultiple;
		BX.Crm.EntityMergerClientCompanyController.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.EntityMergerClientCompanyController, BX.Crm.EntityMergerFieldController);
	BX.Crm.EntityMergerClientCompanyController.prototype.applyMergeResults = function(field, resultData, updateModelData, currentModelData)
	{
		var companyResult = BX.prop.getObject(resultData, field.getId(), null);
		if(!companyResult)
		{
			companyResult = BX.prop.getObject(
				resultData,
				this._isMultiple ? "COMPANY_IDS" : "COMPANY_ID",
				null
			);
		}

		if(!companyResult)
		{
			return;
		}

		if(!updateModelData.hasOwnProperty("CLIENT_INFO"))
		{
			updateModelData["CLIENT_INFO"] = BX.prop.getObject(currentModelData, "CLIENT_INFO", {});
		}

		var companyData = BX.prop.getArray(BX.prop.getObject(companyResult, "EXTRAS", {}), "INFOS", null);
		if(companyData)
		{
			updateModelData["CLIENT_INFO"]["COMPANY_DATA"] = companyData;
		}

		field.setIsMultiple(BX.prop.getBoolean(companyResult, "IS_MULTIPLE", this._isMultiple));
		field.setHasConflict(!BX.prop.getBoolean(companyResult, "IS_MERGED", true));
		field.setSourceEntityIds(BX.prop.getArray(companyResult, "SOURCE_ENTITY_IDS", []));
	};
	BX.Crm.EntityMergerClientCompanyController.prototype.adjustEditorControl = function(srcEditorControl, dstEditorControl)
	{
		var dstEditorModel = dstEditorControl.getModel();
		var srcEditorModel = srcEditorControl.getModel();

		var srcData = srcEditorModel.getField("CLIENT_INFO");
		var modelData = { "COMPANY_DATA": srcData["COMPANY_DATA"] };

		dstEditorModel.updateDataObject("CLIENT_INFO", modelData, { enableNotification : false });
		dstEditorControl.refreshLayout({ reset: true });
	};
	BX.Crm.EntityMergerClientCompanyController.prototype.saveMappedData = function(field, data)
	{
		var sourceIds = field.getSourceEntityIds();
		if(!this._isMultiple)
		{
			if(sourceIds.length > 0)
			{
				data[field.getId()] = { "SOURCE_ENTITY_IDS": sourceIds };
			}
		}
		else
		{
			if(sourceIds.length === 0)
			{
				sourceIds.push(0);
			}
			data[field.getId()] = { "SOURCE_ENTITY_IDS": sourceIds };
		}
	};
	BX.Crm.EntityMergerClientCompanyController.prototype.resolveDataTagName = function(field)
	{
		return this.getDataTagNameByEntityType(field, BX.CrmEntityType.names.company);
	};
	BX.Crm.EntityMergerClientCompanyController.create = function(isMultiple)
	{
		return new BX.Crm.EntityMergerClientCompanyController(isMultiple);
	};
}

if(typeof BX.Crm.EntityMergerClientContactController === "undefined")
{
	BX.Crm.EntityMergerClientContactController = function ()
	{
		BX.Crm.EntityMergerClientContactController.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.EntityMergerClientContactController, BX.Crm.EntityMergerFieldController);
	BX.Crm.EntityMergerClientContactController.prototype.applyMergeResults = function(field, resultData, updateModelData, currentModelData)
	{
		var contactResult = BX.prop.getObject(resultData, field.getId(), null);
		if(!contactResult)
		{
			contactResult = BX.prop.getObject(resultData, "CONTACT_IDS", null);
		}

		if(!contactResult)
		{
			return;
		}

		if(!updateModelData.hasOwnProperty("CLIENT_INFO"))
		{
			updateModelData["CLIENT_INFO"] = BX.prop.getObject(currentModelData, "CLIENT_INFO", {});
		}

		var contactData = BX.prop.getArray(BX.prop.getObject(contactResult, "EXTRAS", {}), "INFOS", null);
		if(contactData)
		{
			updateModelData["CLIENT_INFO"]["CONTACT_DATA"] = contactData;
		}

		field.setIsMultiple(BX.prop.getBoolean(contactResult, "IS_MULTIPLE", true));
		field.setHasConflict(!BX.prop.getBoolean(contactResult, "IS_MERGED", true));
		field.setSourceEntityIds(BX.prop.getArray(contactResult, "SOURCE_ENTITY_IDS", []));
	};
	BX.Crm.EntityMergerClientContactController.prototype.adjustEditorControl = function(srcEditorControl, dstEditorControl)
	{
		var dstEditorModel = dstEditorControl.getModel();
		var srcEditorModel = srcEditorControl.getModel();

		var srcData = srcEditorModel.getField("CLIENT_INFO");
		var modelData = { "CONTACT_DATA": srcData["CONTACT_DATA"] };

		dstEditorModel.updateDataObject("CLIENT_INFO", modelData, { enableNotification : false });
		dstEditorControl.refreshLayout({ reset: true });
	};
	BX.Crm.EntityMergerClientContactController.prototype.saveMappedData = function(field, data)
	{
		var sourceIds = field.getSourceEntityIds();
		if(sourceIds.length === 0)
		{
			sourceIds.push(0);
		}
		data[field.getId()] = { "SOURCE_ENTITY_IDS": sourceIds };
	};
	BX.Crm.EntityMergerClientContactController.prototype.resolveDataTagName = function(field)
	{
		return this.getDataTagNameByEntityType(field, BX.CrmEntityType.names.contact);
	};
	BX.Crm.EntityMergerClientContactController.create = function()
	{
		return new BX.Crm.EntityMergerClientContactController();
	};
}

if(typeof BX.Crm.EntityMergerFieldSwitch === "undefined")
{
	BX.Crm.EntityMergerFieldSwitch = function ()
	{
		this._id = "";
		this._settings = {};

		this._control = null;
		this._editorControl = null;
		this._entityId = 0;
		this._wrapper = null;
		this._checkbox = null;
		this._clickHandler = BX.delegate(this.onClick, this);

		this._isSelected = false;
		this._hasLayout = false;
	};
	BX.Crm.EntityMergerFieldSwitch.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._control = BX.prop.get(this._settings, "control");
			this._editorControl = BX.prop.get(this._settings, "editorControl");
			this._entityId = BX.prop.getInteger(this._settings, "entityId", 0);
			this._isSelected = BX.prop.getBoolean(this._settings, "isSelected", false);
		},
		getId: function()
		{
			return this._id;
		},
		getEntityId: function()
		{
			return this._entityId;
		},
		isSelected: function()
		{
			return this._isSelected;
		},
		setSelected: function(selected)
		{
			selected = !!selected;
			this._isSelected = selected;

			if(this._checkbox)
			{
				this._checkbox.checked = selected;
			}
		},
		getEditorControl: function()
		{
			return this._editorControl;
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			var container = this._editorControl.getWrapper();
			var actionWrapper = null;
			var actionWrappers = container.querySelectorAll(".ui-entity-editor-block-before-action");
			for(var i = 0; i < actionWrappers.length; ++i)
			{
				if(actionWrappers[i].getAttribute("data-field-tag") === this._control.getDataTagName())
				{
					actionWrapper = actionWrappers[i];
					break;
				}
			}

			if(actionWrapper)
			{
				BX.cleanNode(actionWrapper);
				BX.addClass(container, "crm-entity-merger-column-cell-conflict-option");

				this._checkbox = BX.create("input", {
					props: { className: "crm-entity-merger-column-cell-switch-checkbox", type: "checkbox" }
				});

				if(this._isSelected)
				{
					this._checkbox.checked = true;
				}

				BX.bind(this._checkbox, "click", this._clickHandler);

				var switcherContainer = BX.create("label", {
					props: { className: "crm-entity-merger-column-cell-switch-container" },
					children: [
						this._checkbox,
						BX.create('div', {
							props: { className: "crm-entity-merger-column-cell-switch-checkbox-btn" }
						})
					]
				});
				actionWrapper.appendChild(switcherContainer);
			}
			this._hasLayout = true;
		},
		clearLayout: function()
		{

			if(!this._hasLayout)
			{
				return;
			}

			BX.removeClass(this._editorControl.getWrapper(), "crm-entity-merger-column-cell-conflict-option");
			BX.unbind(this._checkbox, "click", this._clickHandler);
			this._checkbox = null;
			this._wrapper = BX.remove(this._wrapper);

			this._hasLayout = false;
		},
		onClick: function(e)
		{
			this._isSelected = this._checkbox.checked;
			this._control.onSwitchChange(this);
		}
	};
	BX.Crm.EntityMergerFieldSwitch.create = function(id, settings)
	{
		var self = new BX.Crm.EntityMergerFieldSwitch();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityMergerPanel === "undefined")
{
	BX.Crm.EntityMergerPanel = function ()
	{
		this._id = "";
		this._settings = {};

		this._merger = null;

		this._initialQueueLength = 0;

		this._processedElement = null;
		this._remainingElement = null;

		this._mergeButton = null;
		this._mergeAndEditButton = null;
		this._postponeButton = null;

		this._editAfterMerge = false;

		this._isLocked = false;

		this._mergeHandler = BX.delegate(this.onMergeButtonClick, this);
		this._mergeAndEditHandler = BX.delegate(this.onMergeAndEditButtonClick, this);
		this._postponeHandler = BX.delegate(this.onPostponeButtonClick, this);
		this._previousButtonHandler = BX.delegate(this.onPreviousButtonClick, this);
		this._nextButtonHandler = BX.delegate(this.onNextButtonClick, this);
		this._dedupeListButtonHandler = BX.delegate(this.onDedupeListButtonClick, this);
	};
	BX.Crm.EntityMergerPanel.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._merger = BX.prop.get(this._settings, "merger");

			this._initialQueueLength = this._merger.getDedupeQueueLength();

			BX.addCustomEvent(this._merger, "onMergeStart", BX.delegate(this.onMergeStart, this));
			BX.addCustomEvent(this._merger, "onPostponeStart", BX.delegate(this.onPostponeStart, this));

			BX.addCustomEvent(this._merger, "onMergeComplete", BX.delegate(this.onMergeComplete, this));
			BX.addCustomEvent(this._merger, "onPostponeComplete", BX.delegate(this.onPostponeComplete, this));

			BX.addCustomEvent(this._merger, "onMergeError", BX.delegate(this.onMergeComplete, this));
			BX.addCustomEvent(this._merger, "onPostponeError", BX.delegate(this.onPostponeError, this));

			window.setTimeout(function() {this.adjustVisibility();}.bind(this),0);
		},
		layout: function()
		{
			this._processedElement = BX("processedAmount");
			this._remainingElement = BX("remainingAmount");

			this._mergeButton = BX("mergeButton");
			BX.bind(this._mergeButton, "click", this._mergeHandler);

			this._mergeAndEditButton = BX("mergeWithEditButton");
			BX.bind(this._mergeAndEditButton, "click", this._mergeAndEditHandler);

			this._postponeButton = BX("postponeButton");
			BX.bind(this._postponeButton, "click", this._postponeHandler);

			var previousButton = BX("previousButton");
			if(previousButton)
			{
				BX.bind(previousButton, "click", this._previousButtonHandler);
			}

			var nextButton = BX("nextButton");
			if(nextButton)
			{
				BX.bind(nextButton, "click", this._nextButtonHandler);
			}

			var duplicateListButton = BX("duplicateListButton");
			if(duplicateListButton)
			{
				BX.bind(duplicateListButton, "click", this._dedupeListButtonHandler);
			}

			this.adjustLayout();
		},
		adjustVisibility: function()
		{
			var isQueueEnabled = this._merger.isQueueEnabled();
			var isDeduplicationMode = this._merger.isDeduplicationMode();

			var duplicateListButton = BX("duplicateListButton");
			if(duplicateListButton)
			{
				duplicateListButton.style.display = isDeduplicationMode ? "" : "none";
			}

			var queueStatisticsWrapper = BX("queueStatisticsWrapper");
			if(queueStatisticsWrapper)
			{
				queueStatisticsWrapper.style.display = isQueueEnabled ? "" : "none";
			}

			window.setTimeout(function() {
				BX.UI.ButtonPanel.pinner.onChange();
			},0);

			var queueNavigationButton = BX("queueNavigationButton");
			if(queueNavigationButton)
			{
				queueNavigationButton.style.display = isQueueEnabled ? "" : "none";
			}

		},
		adjustLayout: function()
		{
			this.adjustVisibility();
			var isQueueEnabled = this._merger.isQueueEnabled();
			if(isQueueEnabled)
			{
				var currentQueueLength = this._merger.getDedupeQueueLength();
				if(this._processedElement)
				{
					this._processedElement.innerHTML = this._initialQueueLength - currentQueueLength +
						BX.prop.getInteger(this._settings, 'previouslyProcessedCount', 0);
				}

				if(this._remainingElement)
				{
					this._remainingElement.innerHTML = currentQueueLength;
				}
			}
		},
		onMergeButtonClick: function(e)
		{
			if(!this._isLocked)
			{
				this._editAfterMerge = false;
				this._merger.merge();
			}
		},
		onMergeAndEditButtonClick: function(e)
		{
			if(!this._isLocked)
			{
				this._editAfterMerge = true;
				this._merger.merge();
			}
		},
		onPostponeButtonClick: function(e)
		{
			if(!this._isLocked)
			{
				this._merger.postpone();
			}
		},
		onPreviousButtonClick: function(e)
		{
			if(this._merger.isQueueEnabled())
			{
				this._merger.moveToPreviousQueueItem()
			}
		},
		onNextButtonClick: function(e)
		{
			if(this._merger.isQueueEnabled())
			{
				this._merger.moveToNextQueueItem();
			}
		},
		onDedupeListButtonClick: function(e)
		{
			if(this._merger.isDeduplicationMode())
			{
				this._merger.openDedupeList();
			}
		},
		onMergeStart: function()
		{
			this.setLocked(true);

			BX.addClass(this._editAfterMerge ? this._mergeAndEditButton : this._mergeButton, "ui-btn-clock");
			BX.addClass(this._editAfterMerge ? this._mergeButton : this._mergeAndEditButton, "ui-btn-disabled");
			BX.addClass(this._postponeButton, "ui-btn-disabled");
		},
		onPostponeStart: function()
		{
			this.setLocked(true);

			BX.addClass(this._mergeButton, "ui-btn-disabled");
			BX.addClass(this._mergeAndEditButton, "ui-btn-disabled");
			BX.addClass(this._postponeButton, "ui-btn-clock");
		},
		onMergeComplete: function()
		{
			this.setLocked(false);

			BX.removeClass(this._editAfterMerge ? this._mergeAndEditButton : this._mergeButton, "ui-btn-clock");
			BX.removeClass(this._editAfterMerge ? this._mergeButton : this._mergeAndEditButton, "ui-btn-disabled");
			BX.removeClass(this._postponeButton, "ui-btn-disabled");

			if (this._editAfterMerge)
			{
				var url = this._merger.getPrimaryEntityUrl();
				if (url.length)
				{
					BX.SidePanel.Instance.open(url);
				}
			}

			this.adjustLayout();
		},
		onPostponeComplete: function()
		{
			this.setLocked(false);

			BX.removeClass(this._mergeButton, "ui-btn-disabled");
			BX.removeClass(this._mergeAndEditButton, "ui-btn-disabled");
			BX.removeClass(this._postponeButton, "ui-btn-clock");
		},
		onMergeError: function()
		{
			this.setLocked(false);

			BX.removeClass(this._mergeButton, "ui-btn-clock");
			BX.removeClass(this._mergeButton, "ui-btn-disabled");
			BX.removeClass(this._mergeAndEditButton, "ui-btn-clock");
			BX.removeClass(this._mergeAndEditButton, "ui-btn-disabled");
			BX.removeClass(this._postponeButton, "ui-btn-disabled");
		},
		onPostponeError: function()
		{
			this.setLocked(false);

			BX.removeClass(this._mergeButton, "ui-btn-disabled");
			BX.removeClass(this._mergeAndEditButton, "ui-btn-disabled");
			BX.removeClass(this._postponeButton, "ui-btn-clock");
		},
		setLocked: function(locked)
		{
			locked = !!locked;
			if(this._isLocked !== locked)
			{
				this._isLocked = locked;
			}
		}
	};
	BX.Crm.EntityMergerPanel.create = function(id, settings)
	{
		var self = new BX.Crm.EntityMergerPanel();
		self.initialize(id, settings);
		return self;
	};
}
