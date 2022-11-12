(() => {
	/**
	 * @class EditorTab
	 */
	class EditorTab extends BaseTab
	{
		constructor(props)
		{
			super(props);

			/** @type {EntityEditor} */
			this.editorRef = null;

			this.on('EntityEditorField::onChangeState', this.handleFieldChange.bind(this));
			this.on('EntityEditorField::onFocusIn', this.handleFocusChange.bind(this, true));
			this.on('EntityEditorField::onFocusOut', this.handleFocusChange.bind(this, false));
		}

		handleFieldChange(eventArgs)
		{
			if (this.editorRef && eventArgs.editorId === this.editorRef.getId())
			{
				this.emit('DetailCard::onTabChange', [{id: this.id}]);
			}
		}

		handleFocusChange(focused, eventArgs)
		{
			if (this.editorRef && eventArgs.editorId === this.editorRef.getId())
			{
				this.emit('DetailCard::onTabEdit', [
					{id: this.id},
					focused
				]);
			}
		}

		getModelData()
		{
			if (this.editorRef)
			{
				return this.editorRef.getValuesFromModel();
			}

			return {};
		}

		/**
		 * @inheritDoc
		 */
		getData()
		{
			return new Promise((resolve) => {
				if (this.editorRef)
				{
					this.editorRef
						.getValuesToSave()
						.then((fields) => resolve(fields))
					;
				}
				else
				{
					resolve({});
				}
			});
		}

		/**
		 * @inheritDoc
		 */
		validate()
		{
			return new Promise((resolve, reject) => {
				if (this.editorRef)
				{
					resolve(this.editorRef.validate());
				}
				else
				{
					resolve(true);
				}
			});
		}

		getEditorModel(editorProps)
		{
			let entityModel = EntityModel.create(
				editorProps.GUID,
				{
					isIdentifiable: editorProps.IS_IDENTIFIABLE_ENTITY,
					data: editorProps.ENTITY_DATA
				}
			);

			this.emit('DetailCard::onEntityModelReady', [entityModel.data]);

			return entityModel;
		}

		getEditorScheme(editorProps)
		{
			return EntityScheme.create(
				editorProps.GUID,
				{
					current: editorProps.ENTITY_SCHEME,
					available: editorProps.ENTITY_AVAILABLE_FIELDS
				}
			);
		}

		getEditorConfig(editorProps)
		{
			return EntityConfig.create(
				editorProps.GUID,
				{
					entityTypeId: editorProps.ENTITY_ID,
					data: editorProps.ENTITY_CONFIG,
					scope: editorProps.ENTITY_CONFIG_SCOPE,
					enableScopeToggle: editorProps.ENABLE_CONFIG_SCOPE_TOGGLE,
					canUpdatePersonalConfiguration: editorProps.CAN_UPDATE_PERSONAL_CONFIGURATION,
					canUpdateCommonConfiguration: editorProps.CAN_UPDATE_COMMON_CONFIGURATION,
					options: editorProps.ENTITY_CONFIG_OPTIONS
				}
			);
		}

		getEntityEditor(editorProps, refresh = false)
		{
			const loadFromModel = refresh || !this.editorRef || this.editorRef.getEntityId() !== editorProps.ENTITY_ID;

			return new EntityEditor({
				id: editorProps.GUID,
				ref: (ref) => this.editorRef = ref,
				settings: {
					entityTypeName: editorProps.ENTITY_TYPE_NAME,
					entityId: editorProps.ENTITY_ID,
					loadFromModel,
					model: this.getEditorModel(editorProps),
					config: this.getEditorConfig(editorProps),
					scheme: this.getEditorScheme(editorProps),
					validators: editorProps.ENTITY_VALIDATORS,
					controllers: editorProps.ENTITY_CONTROLLERS,
					detailManagerId: editorProps.DETAIL_MANAGER_ID,
					fieldCreationPageUrl: editorProps.FIELD_CREATION_PAGE_URL,
					//userFieldManager: userFieldManager,
					initialMode: editorProps.INITIAL_MODE,
					enableModeToggle: Boolean(editorProps.ENABLE_MODE_TOGGLE),
					enableConfigControl: Boolean(editorProps.ENABLE_CONFIG_CONTROL),
					enableVisibilityPolicy: Boolean(editorProps.ENABLE_VISIBILITY_POLICY),
					enableToolPanel: Boolean(editorProps.ENABLE_TOOL_PANEL),
					isToolPanelAlwaysVisible: Boolean(editorProps.IS_TOOL_PANEL_ALWAYS_VISIBLE),
					enableBottomPanel: Boolean(editorProps.ENABLE_BOTTOM_PANEL),
					enableFieldsContextMenu: Boolean(editorProps.ENABLE_FIELDS_CONTEXT_MENU),
					enablePageTitleControls: Boolean(editorProps.ENABLE_PAGE_TITLE_CONTROLS),
					readOnly: Boolean(editorProps.READ_ONLY),
					enableAjaxForm: Boolean(editorProps.ENABLE_AJAX_FORM),
					enableRequiredUserFieldCheck: Boolean(editorProps.ENABLE_REQUIRED_USER_FIELD_CHECK),
					enableSectionEdit: Boolean(editorProps.ENABLE_SECTION_EDIT),
					enableSectionCreation: Boolean(editorProps.ENABLE_SECTION_CREATION),
					enableSectionDragDrop: Boolean(editorProps.ENABLE_SECTION_DRAG_DROP),
					enableFieldDragDrop: Boolean(editorProps.ENABLE_FIELD_DRAG_DROP),
					enableSettingsForAll: Boolean(editorProps.ENABLE_SETTINGS_FOR_ALL),
					containerId: editorProps.GUID + "_container",
					buttonContainerId: editorProps.GUID + "_buttons",
					configMenuButtonId: editorProps.GUID + "_config_menu",
					configIconId: editorProps.GUID + "_config_icon",
					//htmlEditorConfigs: <?=CUtil::PhpToJSObject($htmlEditorConfigs),
					serviceUrl: editorProps.SERVICE_URL,
					externalContextId: editorProps.EXTERNAL_CONTEXT_ID,
					contextId: editorProps.CONTEXT_ID,
					context: editorProps.CONTEXT,
					options: editorProps.EDITOR_OPTIONS,
					ajaxData: editorProps.COMPONENT_AJAX_DATA,
					isEmbedded: Boolean(editorProps.IS_EMBEDDED),
					desktopUrl: this.desktopUrl
				}
			});
		}

		render(result, refresh)
		{
			const editorResult = result.editor || {};

			return ScrollView(
				{
					style: {
						flex: 1,
						backgroundColor: '#eef2f4'
					},
					showsVerticalScrollIndicator: false,
					showsHorizontalScrollIndicator: false,
				},
				this.getEntityEditor(editorResult, refresh)
			);
		}
	}
	
	this.EditorTab = EditorTab;
})();
