/**
 * @module layout/ui/entity-editor/manager
 */
jn.define('layout/ui/entity-editor/manager', (require, exports, module) => {

	const { EntityEditor } = require('layout/ui/entity-editor');
	const { EntityModel } = require('layout/ui/entity-editor/model');
	const { EntityConfig } = require('layout/ui/entity-editor/config');
	const { EntityScheme } = require('layout/ui/entity-editor/scheme');

	/**
	 * @class EntityManager
	 */
	class EntityManager
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
					containerId: editorProps.GUID + '_container',
					buttonContainerId: editorProps.GUID + '_buttons',
					configMenuButtonId: editorProps.GUID + '_config_menu',
					configIconId: editorProps.GUID + '_config_icon',
					//htmlEditorConfigs: <?=CUtil::PhpToJSObject($htmlEditorConfigs),
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

		static getEditorModel(id, uid, editorProps)
		{
			return EntityModel.create(id, uid, {
				isIdentifiable: editorProps.IS_IDENTIFIABLE_ENTITY,
				data: editorProps.ENTITY_DATA,
			});
		}

		static getEditorConfig(id, editorProps)
		{
			return EntityConfig.create(id, {
				entityId: editorProps.ENTITY_ID,
				data: editorProps.ENTITY_CONFIG,
				scope: editorProps.ENTITY_CONFIG_SCOPE,
				enableScopeToggle: editorProps.ENABLE_CONFIG_SCOPE_TOGGLE,
				canUpdatePersonalConfiguration: editorProps.CAN_UPDATE_PERSONAL_CONFIGURATION,
				canUpdateCommonConfiguration: editorProps.CAN_UPDATE_COMMON_CONFIGURATION,
				options: editorProps.ENTITY_CONFIG_OPTIONS,
			});
		}

		static getEditorScheme(id, editorProps)
		{
			return EntityScheme.create(id, {
				current: editorProps.ENTITY_SCHEME,
				available: editorProps.ENTITY_AVAILABLE_FIELDS,
			});
		}
	}

	module.exports = { EntityManager };
});
