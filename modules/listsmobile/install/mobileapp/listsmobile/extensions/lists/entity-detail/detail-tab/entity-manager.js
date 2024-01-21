/**
 * @module lists/entity-detail/entity-manager
 */
jn.define('lists/entity-detail/entity-manager', (require, exports, module) => {
	const { EntityManager: Extended } = require('layout/ui/entity-editor/manager');
	const { EntityEditor } = require('lists/entity-detail/entity-editor');

	class EntityManager extends Extended
	{
		static create(settings)
		{
			const {
				uid,
				refCallback,
				editorProps,
				loadFromModel,
				desktopUrl,
				payload,
				...restProps
			} = settings;

			const id = editorProps.GUID;

			return new EntityEditor({
				...restProps,
				id: editorProps.GUID,
				uid,
				ref: refCallback,
				settings: {
					loadFromModel,
					entityTypeName: editorProps.ENTITY_TYPE_NAME,
					entityId: editorProps.ENTITY_ID,
					model: this.getEditorModel(id, uid, editorProps),
					config: this.getEditorConfig(id, editorProps),
					scheme: this.getEditorScheme(id, editorProps),
					validators: editorProps.ENTITY_VALIDATORS,
					controllers: editorProps.ENTITY_CONTROLLERS,
					detailManagerId: editorProps.DETAIL_MANAGER_ID,
					fieldCreationPageUrl: editorProps.FIELD_CREATION_PAGE_URL,
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
					containerId: `${editorProps.GUID}_container`,
					buttonContainerId: `${editorProps.GUID}_buttons`,
					configMenuButtonId: `${editorProps.GUID}_config_menu`,
					configIconId: `${editorProps.GUID}_config_icon`,
					serviceUrl: editorProps.SERVICE_URL,
					externalContextId: editorProps.EXTERNAL_CONTEXT_ID,
					contextId: editorProps.CONTEXT_ID,
					contEditorToolbarext: editorProps.CONTEXT,
					options: editorProps.EDITOR_OPTIONS,
					ajaxData: editorProps.COMPONENT_AJAX_DATA,
					isEmbedded: Boolean(editorProps.IS_EMBEDDED),
					desktopUrl,
					entityDetailsUrl: editorProps.PATH_TO_ENTITY_DETAILS,
					payload,
				},
			});
		}
	}

	module.exports = { EntityManager };
});
