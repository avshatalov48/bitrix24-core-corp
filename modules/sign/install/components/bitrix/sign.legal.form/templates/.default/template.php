<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 * @var string $templateName
 * @var string $templateFile
 * @var string $templateFolder
 * @var string $componentPath
 */

\Bitrix\Main\UI\Extension::load('ui.entity-editor');

$guid = $arResult['GUID'];
$prefix = mb_strtolower($guid);
$containerID = "{$prefix}_container";
$buttonContainerID = "{$prefix}_buttons";
$configMenuButtonID = "{$prefix}_config_menu";
$configIconID = "{$prefix}_config_icon";

$APPLICATION->SetTitle($arResult['TITLE']);
?>

<div class="sign-entity-editor-container">
	<div class="ui-entity-editor-container" id="<?=htmlspecialcharsbx($containerID)?>"></div>
	<div class="ui-entity-editor-section-add-btn-container" id="<?=htmlspecialcharsbx($buttonContainerID)?>"></div>
</div>
<script>
	BX.ready(
		function()
		{
			BX.UI.EntityEditorField.messages = {
				add: "<?=GetMessageJS('UI_FORM_ENTITY_FIELD_ADD')?>",
				isEmpty: "<?=GetMessageJS('UI_FORM_ENTITY_FIELD_EMPTY')?>"
			};

			const config = BX.UI.EntityConfig.create(
				"<?=CUtil::JSEscape($arResult['CONFIG_ID'])?>",
				{
					data: <?=\Bitrix\Main\Web\Json::encode($arResult['ENTITY_CONFIG'])?>,
					scope: "<?=CUtil::JSEscape($arResult['ENTITY_CONFIG_SCOPE'])?>",
					enableScopeToggle: <?=$arResult['ENABLE_CONFIG_SCOPE_TOGGLE'] ? 'true' : 'false'?>,
					canUpdatePersonalConfiguration: <?=$arResult['CAN_UPDATE_PERSONAL_CONFIGURATION'] ? 'true' : 'false'?>,
					canUpdateCommonConfiguration: <?=$arResult['CAN_UPDATE_COMMON_CONFIGURATION'] ? 'true' : 'false'?>,
					options: <?=\Bitrix\Main\Web\Json::encode($arResult['ENTITY_CONFIG_OPTIONS'])?>,
					categoryName: "<?=CUtil::JSEscape($arResult['ENTITY_CONFIG_CATEGORY_NAME'])?>",
					signedParams: "<?=CUtil::JSEscape($arResult['ENTITY_CONFIG_SIGNED_PARAMS'])?>",
				}
			);

			const userFieldManager = BX.UI.EntityUserFieldManager.create(
				"<?=CUtil::JSEscape($guid)?>",
				{
					entityId: <?=$arResult['ENTITY_ID']?>,
					enableCreation: <?=$arResult['ENABLE_USER_FIELD_CREATION'] ? 'true' : 'false'?>,
					enableSelection: <?=$arResult['ENABLE_USER_FIELD_SELECTION'] ? 'true' : 'false'?>,
					enableMandatoryControl: <?=$arResult['ENABLE_USER_FIELD_MANDATORY_CONTROL'] ? 'true' : 'false'?>,
					fieldEntityId: "<?=CUtil::JSEscape($arResult['USER_FIELD_ENTITY_ID'])?>",
					fieldPrefix: "<?=CUtil::JSEscape($arResult['USER_FIELD_PREFIX'])?>",
					creationSignature: "<?=CUtil::JSEscape($arResult['USER_FIELD_CREATE_SIGNATURE'])?>",
					creationPageUrl: "<?=CUtil::JSEscape($arResult['USER_FIELD_CREATE_PAGE_URL'])?>",
					languages: <?=\Bitrix\Main\Web\Json::encode($arResult['LANGUAGES'])?>,
				}
			);

			BX.Event.EventEmitter.subscribe(
				'BX.UI.EntityUserFieldManager:getTypes',
				function(event)
				{
					const types = event.getData().types;
					if (!BX.Type.isArray(types))
					{
						return;
					}

					const result = types.filter((type) => <?= \Bitrix\Main\Web\Json::encode($arResult['USER_FIELD_AVAILABLE_TYPE_FIELDS']) ?>.includes(type.name));

					event.setData({
						types: result
					});
				}
			);

			const scheme = BX.UI.EntityScheme.create(
				"<?=CUtil::JSEscape($guid)?>",
				{
					current: <?=\Bitrix\Main\Web\Json::encode($arResult['ENTITY_SCHEME'])?>,
					available: <?=\Bitrix\Main\Web\Json::encode($arResult['ENTITY_AVAILABLE_FIELDS'])?>
				}
			);

			const model = BX.UI.EntityEditorModelFactory.create(
				"<?=CUtil::JSEscape($arResult['ENTITY_TYPE_NAME'])?>",
				"",
				{
					isIdentifiable: <?=$arResult['IS_IDENTIFIABLE_ENTITY'] ? 'true' : 'false'?>,
					data: <?=\Bitrix\Main\Web\Json::encode($arResult['ENTITY_DATA'])?>
				}
			);

			BX.UI.EntityEditor.setDefault(
				BX.UI.EntityEditor.create(
					"<?=CUtil::JSEscape($guid)?>",
					{
						entityTypeName: "<?=CUtil::JSEscape($arResult['ENTITY_TYPE_NAME'])?>",
						entityId: <?=$arResult['ENTITY_ID']?>,
						model: model,
						config: config,
						scheme: scheme,
						validators: <?=\Bitrix\Main\Web\Json::encode($arResult['ENTITY_VALIDATORS'])?>,
						controllers: <?=\Bitrix\Main\Web\Json::encode($arResult['ENTITY_CONTROLLERS'])?>,
						detailManagerId: "<?=CUtil::JSEscape($arResult['DETAIL_MANAGER_ID'])?>",
						fieldCreationPageUrl: "<?=CUtil::JSEscape($arResult['FIELD_CREATION_PAGE_URL'] ?? '')?>",
						userFieldManager: userFieldManager,
						initialMode: "<?=CUtil::JSEscape($arResult['INITIAL_MODE'])?>",
						enableModeToggle: <?=$arResult['ENABLE_MODE_TOGGLE'] ? 'true' : 'false'?>,
						enableConfigControl: <?=$arResult['ENABLE_CONFIG_CONTROL'] ? 'true' : 'false'?>,
						enableShowAlwaysFeauture: <?=$arResult['ENABLE_SHOW_ALWAYS_FEATURE'] ? 'true' : 'false'?>,
						canHideField: <?=$arResult['CAN_HIDE_FIELD'] ? 'true' : 'false'?>,
						canBeMultipleFields: 'false',
						enableVisibilityPolicy: <?=$arResult['ENABLE_VISIBILITY_POLICY'] ? 'true' : 'false'?>,
						enableToolPanel: <?=$arResult['ENABLE_TOOL_PANEL'] ? 'true' : 'false'?>,
						isToolPanelAlwaysVisible: <?=$arResult['IS_TOOL_PANEL_ALWAYS_VISIBLE'] ? 'true' : 'false'?>,
						enableBottomPanel: <?=$arResult['ENABLE_BOTTOM_PANEL'] ? 'true' : 'false'?>,
						enableFieldsContextMenu: <?=$arResult['ENABLE_FIELDS_CONTEXT_MENU'] ? 'true' : 'false'?>,
						enablePageTitleControls: <?=$arResult['ENABLE_PAGE_TITLE_CONTROLS'] ? 'true' : 'false'?>,
						readOnly: <?=$arResult['READ_ONLY'] ? 'true' : 'false'?>,
						enableAjaxForm: <?=$arResult['ENABLE_AJAX_FORM'] ? 'true' : 'false'?>,
						enableRequiredUserFieldCheck: <?=$arResult['ENABLE_REQUIRED_USER_FIELD_CHECK'] ? 'true' : 'false'?>,
						enableSectionEdit: <?=$arResult['ENABLE_SECTION_EDIT'] ? 'true' : 'false'?>,
						enableSectionCreation: <?=$arResult['ENABLE_SECTION_CREATION'] ? 'true' : 'false'?>,
						enableSectionDragDrop: <?=$arResult['ENABLE_SECTION_DRAG_DROP'] ? 'true' : 'false'?>,
						enableFieldDragDrop: <?=$arResult['ENABLE_FIELD_DRAG_DROP'] ? 'true' : 'false'?>,
						enableSettingsForAll: <?=$arResult['ENABLE_SETTINGS_FOR_ALL'] ? 'true' : 'false'?>,
						containerId: "<?=CUtil::JSEscape($containerID)?>",
						buttonContainerId: "<?=CUtil::JSEscape($buttonContainerID)?>",
						configMenuButtonId: "<?=CUtil::JSEscape($configMenuButtonID)?>",
						configIconId: "<?=CUtil::JSEscape($configIconID)?>",
						serviceUrl: "<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>",
						externalContextId: "<?=CUtil::JSEscape($arResult['EXTERNAL_CONTEXT_ID'])?>",
						contextId: "<?=CUtil::JSEscape($arResult['CONTEXT_ID'])?>",
						context: <?=\Bitrix\Main\Web\Json::encode($arResult['CONTEXT'])?>,
						options: <?=\Bitrix\Main\Web\Json::encode($arResult['EDITOR_OPTIONS'])?>,
						ajaxData: <?=\Bitrix\Main\Web\Json::encode($arResult['COMPONENT_AJAX_DATA'])?>,
						customToolPanelButtons: <?=\Bitrix\Main\Web\Json::encode($arResult['CUSTOM_TOOL_PANEL_BUTTONS'])?>,
						toolPanelButtonsOrder: <?=\Bitrix\Main\Web\Json::encode($arResult['TOOL_PANEL_BUTTONS_ORDER'])?>,
						isEmbedded: <?=$arResult['IS_EMBEDDED'] ? 'true' : 'false'?>,
					}
				)
			);
		}
	);
</script>
