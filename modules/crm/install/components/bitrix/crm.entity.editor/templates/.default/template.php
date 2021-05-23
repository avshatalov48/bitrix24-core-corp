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

use \Bitrix\Main;
use \Bitrix\Crm;
Main\UI\Extension::load("crm.entity-editor");
Main\UI\Extension::load("crm.entity-editor.field.requisite");
Main\UI\Extension::load("ui.icons.b24");

if(Main\Loader::includeModule('calendar'))
{
	\Bitrix\Crm\Integration\Calendar::loadResourcebookingUserfieldExtention();
}

$guid = $arResult['GUID'];
$prefix = mb_strtolower($guid);
$containerID = "{$prefix}_container";
$buttonContainerID = "{$prefix}_buttons";
$createSectionButtonID = "{$prefix}_create_section";
$configMenuButtonID = "{$prefix}_config_menu";
$configIconID = "{$prefix}_config_icon";

if($arResult['REST_USE'])
{
	$restSectionButtonID = "{$prefix}_rest_section";
	$arResult['REST_PLACEMENT_TAB_CONFIG']['bottom_button_id'] = $restSectionButtonID;
}

$htmlEditorConfigs = array();
$htmlFieldNames = isset($arResult['ENTITY_HTML_FIELD_NAMES']) && is_array($arResult['ENTITY_HTML_FIELD_NAMES'])
	? $arResult['ENTITY_HTML_FIELD_NAMES'] : array();
foreach($htmlFieldNames as $fieldName)
{
	$fieldPrefix = $prefix.'_'.mb_strtolower($fieldName);
	$htmlEditorConfigs[$fieldName] = array(
		'id' => "{$fieldPrefix}_html_editor",
		'containerId' => "{$fieldPrefix}_html_editor_container"
	);
}

if(Main\Loader::includeModule('socialnetwork'))
{
	\CJSCore::init(array('socnetlogdest'));

	$destSort = CSocNetLogDestination::GetDestinationSort(
			array('DEST_CONTEXT' => \Bitrix\Crm\Entity\EntityEditor::getUserSelectorContext())
	);
	$last = array();
	CSocNetLogDestination::fillLastDestination($destSort, $last);

	$destUserIDs = array();
	if(isset($last['USERS']))
	{
		foreach($last['USERS'] as $code)
		{
			$destUserIDs[] = str_replace('U', '', $code);
		}
	}

	$dstUsers = CSocNetLogDestination::GetUsers(array('id' => $destUserIDs));
	$structure = CSocNetLogDestination::GetStucture(array('LAZY_LOAD' => true));
	$socnetGroups = CSocNetLogDestination::getSocnetGroup();

	$department = $structure['department'];
	$departmentRelation = $structure['department_relation'];
	$departmentRelationHead = $structure['department_relation_head'];
	?><script type="text/javascript">
		BX.ready(
			function()
			{
				BX.UI.EntityEditorUserSelector.users =  <?=CUtil::PhpToJSObject($dstUsers)?>;
				BX.UI.EntityEditorUserSelector.socnetGroups =  <?=CUtil::PhpToJSObject($socnetGroups)?>;
				BX.UI.EntityEditorUserSelector.department = <?=CUtil::PhpToJSObject($department)?>;
				BX.UI.EntityEditorUserSelector.departmentRelation = <?=CUtil::PhpToJSObject($departmentRelation)?>;
				BX.UI.EntityEditorUserSelector.last = <?=CUtil::PhpToJSObject(array_change_key_case($last, CASE_LOWER))?>;

				BX.Crm.EntityEditorCrmSelector.contacts = {};
				BX.Crm.EntityEditorCrmSelector.contactsLast = {};

				BX.Crm.EntityEditorCrmSelector.companies = {};
				BX.Crm.EntityEditorCrmSelector.companiesLast = {};

				BX.Crm.EntityEditorCrmSelector.leads = {};
				BX.Crm.EntityEditorCrmSelector.leadsLast = {};

				BX.Crm.EntityEditorCrmSelector.deals = {};
				BX.Crm.EntityEditorCrmSelector.dealsLast = {};
			}
		);
	</script><?
}

?><div class="crm-entity-card-container-content" id="<?=htmlspecialcharsbx($containerID)?>"></div>
<div class="crm-entity-card-widget-add-btn-container" id="<?=htmlspecialcharsbx($buttonContainerID)?>" style="display:none;">
	<span id="<?=htmlspecialcharsbx($createSectionButtonID)?>" class="crm-entity-add-widget-link">
		<?=GetMessage('CRM_ENTITY_ED_CREATE_SECTION')?>
	</span><?
if($arResult['REST_USE'])
{
	?><span id="<?=htmlspecialcharsbx($restSectionButtonID)?>" class="crm-entity-add-app-link">
		<?=GetMessage('CRM_ENTITY_ED_REST_SECTION_2')?>
	</span><?
}

$configIconClassName = $arResult['ENTITY_CONFIG_SCOPE'] === Crm\Entity\EntityEditorConfigScope::COMMON
	? 'crm-entity-card-common'
	: 'crm-entity-card-private';

$configCaption = Crm\Entity\EntityEditorConfigScope::getCaption(
	$arResult['ENTITY_CONFIG_SCOPE'],
	$arResult['CONFIG_ID'],
	$arResult['USER_SCOPE_ID'],
	($arParams['MODULE_ID'] ?? null)
);

	?><span id="<?=htmlspecialcharsbx($configIconID)?>" class="<?=$configIconClassName?>" title="<?=$configCaption?>">
	</span><?

	?><span id="<?=htmlspecialcharsbx($configMenuButtonID)?>" class="crm-entity-settings-link">
		<?=$configCaption?>
	</span>
</div><?
if(!empty($htmlEditorConfigs))
{
	CModule::IncludeModule('fileman');
	foreach($htmlEditorConfigs as $htmlEditorConfig)
	{
		?><div id="<?=htmlspecialcharsbx($htmlEditorConfig['containerId'])?>" style="display:none;"><?
			$editor = new CHTMLEditor();
			$editor->Show(
				array(
					'name' => $htmlEditorConfig['id'],
					'id' => $htmlEditorConfig['id'],
					'siteId' => SITE_ID,
					'width' => '100%',
					'minBodyWidth' => 230,
					'normalBodyWidth' => 530,
					'height' => 200,
					'minBodyHeight' => 200,
					'showTaskbars' => false,
					'showNodeNavi' => false,
					'autoResize' => true,
					'autoResizeOffset' => 10,
					'bbCode' => false,
					'saveOnBlur' => false,
					'bAllowPhp' => false,
					'lazyLoad' => true,
					'limitPhpAccess' => false,
					'setFocusAfterShow' => false,
					'askBeforeUnloadPage' => false,
					'useFileDialogs' => false,
					'controlsMap' => array(
						array('id' => 'Bold',  'compact' => true, 'sort' => 10),
						array('id' => 'Italic',  'compact' => true, 'sort' => 20),
						array('id' => 'Underline',  'compact' => true, 'sort' => 30),
						array('id' => 'Strikeout',  'compact' => true, 'sort' => 40),
						array('id' => 'RemoveFormat',  'compact' => false, 'sort' => 50),
						array('id' => 'Color',  'compact' => false, 'sort' => 60),
						array('id' => 'FontSelector',  'compact' => false, 'sort' => 70),
						array('id' => 'FontSize',  'compact' => true, 'sort' => 80),
						array('separator' => true, 'compact' => false, 'sort' => 90),
						array('id' => 'OrderedList',  'compact' => true, 'sort' => 100),
						array('id' => 'UnorderedList',  'compact' => true, 'sort' => 110),
						array('id' => 'AlignList', 'compact' => false, 'sort' => 120),
						array('separator' => true, 'compact' => false, 'sort' => 130),
						array('id' => 'InsertLink',  'compact' => true, 'sort' => 140),
						array('id' => 'Code',  'compact' => false, 'sort' => 180),
						array('id' => 'Quote',  'compact' => false, 'sort' => 190),
						array('separator' => true, 'compact' => false, 'sort' => 200),
						array('id' => 'Fullscreen',  'compact' => true, 'sort' => 210),
						array('id' => 'More',  'compact' => true, 'sort' => 400)
					)
				)
			);
		?></div><?
	}
}
?>

<?if (!empty($arResult['BIZPROC_MANAGER_CONFIG'])):
	$arResult['BIZPROC_MANAGER_CONFIG']['containerId'] = "{$prefix}_bizproc_manager_container";
?><div id="<?=htmlspecialcharsbx($arResult['BIZPROC_MANAGER_CONFIG']['containerId'])?>" style="display:none;"><?
	\CJSCore::init(array('bp_starter'));
	$APPLICATION->IncludeComponent("bitrix:bizproc.workflow.start",
		'modern',
		array(
			"MODULE_ID" => $arResult['BIZPROC_MANAGER_CONFIG']['moduleId'],
			"ENTITY" => $arResult['BIZPROC_MANAGER_CONFIG']['entity'],
			"DOCUMENT_TYPE" => $arResult['BIZPROC_MANAGER_CONFIG']['documentType'],
			"AUTO_EXECUTE_TYPE" => $arResult['BIZPROC_MANAGER_CONFIG']['autoExecuteType'],
		)
	);
?></div>
<?endif?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmEntityType.setCaptions(<?=CUtil::PhpToJSObject(CCrmOwnerType::GetJavascriptDescriptions())?>);
			BX.CrmEntityType.setNotFoundMessages(<?=CUtil::PhpToJSObject(CCrmOwnerType::GetNotFoundMessages())?>);
			<?php if (
				!empty($arResult['USERFIELD_TYPE_REST_CREATE_URL'])
				|| \Bitrix\Crm\Integration\Calendar::isResourceBookingAvailableForEntity($arResult['USER_FIELD_ENTITY_ID'])
			):?>
			BX.Event.EventEmitter.subscribe(
				'BX.UI.EntityUserFieldManager:getTypes',
				function(event)
				{
					var types = event.getData().types;
					if (!BX.Type.isArray(types))
					{
						return;
					}
					<?php if (\Bitrix\Crm\Integration\Calendar::isResourceBookingAvailableForEntity($arResult['USER_FIELD_ENTITY_ID'])):?>
						var index = 0;
						var length = types.length;
						for (; index < length; index++)
						{
							if (types[index].name === 'address')
							{
								break;
							}
						}
						types.splice(index, 0, {
							name: "resourcebooking",
							title: "<?=GetMessageJS('CRM_ENTITY_ED_UF_RESOURCEBOOKING_TITLE')?>",
							legend: "<?=GetMessageJS('CRM_ENTITY_ED_UF_RESOURCEBOOKING_LEGEND')?>"
						});
					<?php endif;?>
					<?php if (!empty($arResult['USERFIELD_TYPE_REST_CREATE_URL'])):?>
						types.push({
							name: "rest_field_type",
							title: "<?=GetMessageJS('CRM_ENTITY_ED_UF_REST_TITLE')?>",
							legend: "<?=GetMessageJS('CRM_ENTITY_ED_UF_REST_LEGEND')?>",
							callback: function()
							{
								BX.SidePanel.Instance.open(
									'<?=$arResult['USERFIELD_TYPE_REST_CREATE_URL']?>',
									{
										cacheable: false,
										allowChangeHistory: false
									}
								);
							}
						});
					<?php endif;?>
					event.setData({
						types: types
					});
				}
			);
			<?php endif;?>

			var userFieldManager = BX.UI.EntityUserFieldManager.create(
				"<?=CUtil::JSEscape($guid)?>",
				{
					entityId: <?=$arResult['ENTITY_ID']?>,
					enableCreation: <?=$arResult['ENABLE_USER_FIELD_CREATION'] ? 'true' : 'false'?>,
					fieldEntityId: "<?=CUtil::JSEscape($arResult['USER_FIELD_ENTITY_ID'])?>",
					creationSignature: "<?=CUtil::JSEscape($arResult['USER_FIELD_CREATE_SIGNATURE'])?>",
					creationPageUrl: "<?=CUtil::JSEscape($arResult['USER_FIELD_CREATE_PAGE_URL'])?>",
					languages: <?=CUtil::PhpToJSObject($arResult['LANGUAGES'])?>,
					fieldPrefix: "<?=CUtil::JSEscape($arResult['USER_FIELD_PREFIX'])?>",
				}
			);

			var config = BX.UI.EntityConfig.create(
				"<?=CUtil::JSEscape($arResult['CONFIG_ID'])?>",
				{
					data: <?=CUtil::PhpToJSObject($arResult['ENTITY_CONFIG'])?>,
					scope: "<?=CUtil::JSEscape($arResult['ENTITY_CONFIG_SCOPE'])?>",
					userScopes: <?=CUtil::PhpToJSObject($arResult['USER_SCOPES'])?>,
					userScopeId: "<?=CUtil::JSEscape($arResult['USER_SCOPE_ID'])?>",
					enableScopeToggle: <?=$arResult['ENABLE_CONFIG_SCOPE_TOGGLE'] ? 'true' : 'false'?>,
					canUpdatePersonalConfiguration: <?=$arResult['CAN_UPDATE_PERSONAL_CONFIGURATION'] ? 'true' : 'false'?>,
					canUpdateCommonConfiguration: <?=$arResult['CAN_UPDATE_COMMON_CONFIGURATION'] ? 'true' : 'false'?>,
					options: <?=CUtil::PhpToJSObject($arResult['ENTITY_CONFIG_OPTIONS'])?>,
                    categoryName: "<?=CUtil::JSEscape($arResult['ENTITY_CONFIG_CATEGORY_NAME'])?>"
				}
			);

			var scheme = BX.UI.EntityScheme.create(
				"<?=CUtil::JSEscape($guid)?>",
				{
					current: <?=CUtil::PhpToJSObject($arResult['ENTITY_SCHEME'])?>,
					available: <?=CUtil::PhpToJSObject($arResult['ENTITY_AVAILABLE_FIELDS'])?>
				}
			);

			BX.UI.EntitySchemeElement.userFieldFileUrlTemplate = "<?=CUtil::JSEscape($arResult['USER_FIELD_FILE_URL_TEMPLATE'])?>";

			var model = BX.Crm.EntityEditorModelFactory.create(
				<?=$arResult['ENTITY_TYPE_ID']?>,
				"",
				{ data: <?=CUtil::PhpToJSObject($arResult['ENTITY_DATA'])?> }
			);

			BX.CrmDuplicateSummaryPopup.messages =
			{
				title: "<?=GetMessageJS("CRM_ENTITY_ED_DUP_CTRL_SHORT_SUMMARY_TITLE")?>"
			};

			BX.CrmDuplicateWarningDialog.messages =
			{
				title: "<?=GetMessageJS("CRM_ENTITY_ED_DUP_CTRL_WARNING_DLG_TITLE")?>",
				acceptButtonTitle: "<?=GetMessageJS("CRM_ENTITY_ED_DUP_CTRL_WARNING_ACCEPT_BTN_TITLE")?>",
				cancelButtonTitle: "<?=GetMessageJS("CRM_ENTITY_ED_DUP_CTRL_WARNING_CANCEL_BTN_TITLE")?>"
			};

			BX.CrmEntityType.categoryCaptions = <?=CUtil::PhpToJSObject(\CCrmOwnerType::GetAllCategoryCaptions(true))?>;

			BX.Crm.EntityEditor.messages =
			{
				newSectionTitle: "<?=GetMessageJS('CRM_ENTITY_ED_NEW_SECTION_TITLE')?>",
				inlineEditHint: "<?=GetMessageJS('CRM_ENTITY_ED_INLINE_EDIT_HINT')?>",
				resetConfig: "<?=GetMessageJS('CRM_ENTITY_ED_RESET_CONFIG_2')?>",
				forceCommonConfigForAllUsers: "<?=GetMessageJS('CRM_ENTITY_ED_FORCE_COMMON_CONFIG_FOR_ALL')?>",
				switchToPersonalConfig: "<?=GetMessageJS('CRM_ENTITY_ED_SWITCH_TO_PERSONAL_CONFIG')?>",
				switchToCommonConfig: "<?=GetMessageJS('CRM_ENTITY_ED_SWITCH_TO_COMMON_CONFIG')?>",
				couldNotFindEntityIdError: "<?=GetMessageJS('CRM_ENTITY_ED_COULD_NOT_FIND_ENTITY_ID')?>",
				titleEdit: "<?=GetMessageJS('CRM_ENTITY_ED_TITLE_EDIT')?>",
				titleEditUnsavedChanges: "<?=GetMessageJS('CRM_ENTITY_ED_TITLE_EDIT_UNSAVED_CHANGES')?>",
				checkScope: "<?=GetMessageJS('CRM_ENTITY_ED_CHECK_SCOPE')?>",
				createScope: "<?=GetMessageJS('CRM_ENTITY_ED_CREATE_SCOPE')?>",
				updateScope: "<?=GetMessageJS('CRM_ENTITY_ED_UPDATE_SCOPE')?>",
			};

			BX.Crm.EntityEditorScopeConfig.messages =
			{
				createScope: "<?=GetMessageJS('CRM_ENTITY_ED_CREATE_SCOPE')?>",
				scopeName: "<?=GetMessageJS('CRM_ENTITY_ED_CONFIG_SCOPE_NAME')?>",
				scopeNamePlaceholder: "<?=GetMessageJS('CRM_ENTITY_ED_CONFIG_SCOPE_NAME_PLACEHOLDER')?>",
				scopeMembers: "<?=GetMessageJS('CRM_ENTITY_ED_CONFIG_SCOPE_MEMBERS')?>",
				scopeSave: "<?=GetMessageJS('CRM_ENTITY_ED_CONFIG_SCOPE_SAVE')?>",
				scopeCancel: "<?=GetMessageJS('CRM_ENTITY_ED_CONFIG_SCOPE_CANCEL')?>",
				scopeSaved: "<?=GetMessageJS('CRM_ENTITY_ED_CONFIG_SCOPE_SAVED')?>",
			};

			BX.UI.EntityUserFieldManager.messages =
			{
				stringLabel: "<?=GetMessageJS('CRM_ENTITY_ED_UF_STRING_LABEL')?>",
				doubleLabel: "<?=GetMessageJS('CRM_ENTITY_ED_UF_DOUBLE_LABEL')?>",
				moneyLabel: "<?=GetMessageJS('CRM_ENTITY_ED_UF_MONEY_LABEL')?>",
				datetimeLabel: "<?=GetMessageJS('CRM_ENTITY_ED_UF_DATETIME_LABEL')?>",
				enumerationLabel: "<?=GetMessageJS('CRM_ENTITY_ED_UF_ENUMERATION_LABEL')?>",
				fileLabel: "<?=GetMessageJS('CRM_ENTITY_ED_UF_FILE_LABEL')?>",
				label: "<?=GetMessageJS('CRM_ENTITY_ED_UF_LABEL')?>",
				stringTitle: "<?=GetMessageJS('CRM_ENTITY_ED_UF_STRING_TITLE')?>",
				stringLegend: "<?=GetMessageJS('CRM_ENTITY_ED_UF_STRING_LEGEND')?>",
				doubleTitle: "<?=GetMessageJS('CRM_ENTITY_ED_UF_DOUBLE_TITLE')?>",
				doubleLegend: "<?=GetMessageJS('CRM_ENTITY_ED_UF_DOUBLE_LEGEND')?>",
				moneyTitle: "<?=GetMessageJS('CRM_ENTITY_ED_UF_MONEY_TITLE')?>",
				moneyLegend: "<?=GetMessageJS('CRM_ENTITY_ED_UF_MONEY_LEGEND')?>",
				booleanTitle: "<?=GetMessageJS('CRM_ENTITY_ED_UF_BOOLEAN_TITLE')?>",
				booleanLegend: "<?=GetMessageJS('CRM_ENTITY_ED_UF_BOOLEAN_LEGEND')?>",
				datetimeTitle: "<?=GetMessageJS('CRM_ENTITY_ED_UF_DATETIME_TITLE')?>",
				datetimeLegend: "<?=GetMessageJS('CRM_ENTITY_ED_UF_DATETIME_LEGEND')?>",
				enumerationTitle: "<?=GetMessageJS('CRM_ENTITY_ED_UF_ENUM_TITLE')?>",
				enumerationLegend: "<?=GetMessageJS('CRM_ENTITY_ED_UF_ENUM_LEGEND')?>",
				urlTitle: "<?=GetMessageJS('CRM_ENTITY_ED_UF_URL_TITLE')?>",
				urlLegend: "<?=GetMessageJS('CRM_ENTITY_ED_UF_URL_LEGEND')?>",
				addressTitle: "<?=GetMessageJS('CRM_ENTITY_ED_UF_ADDRESS_TITLE')?>",
				addressLegend: "<?=GetMessageJS('CRM_ENTITY_ED_UF_ADDRESS_LEGEND')?>",
				resourcebookingTitle: "<?=GetMessageJS('CRM_ENTITY_ED_UF_RESOURCEBOOKING_TITLE')?>",
				resourcebookingLegend: "<?=GetMessageJS('CRM_ENTITY_ED_UF_RESOURCEBOOKING_LEGEND')?>",
				fileTitle: "<?=GetMessageJS('CRM_ENTITY_ED_UF_FILE_TITLE')?>",
				fileLegend: "<?=GetMessageJS('CRM_ENTITY_ED_UF_FILE_LEGEND')?>",
				customTitle: "<?=GetMessageJS('CRM_ENTITY_ED_UF_CUSTOM_TITLE')?>",
				customLegend: "<?=GetMessageJS('CRM_ENTITY_ED_UF_CUSTOM_LEGEND')?>"
			};

			BX.UI.EntityUserFieldManager.additionalTypeList = <?=\CUtil::PhpToJSObject($arResult['USERFIELD_TYPE_ADDITIONAL'])?>;

			BX.Crm.EntityEditorMoneyPay.messages =
			{
				payButtonLabel: "<?=GetMessageJS('CRM_ENTITY_EM_BUTTON_PAY')?>",
				showPayButton: "<?=GetMessageJS('CRM_ENTITY_EM_SHOW_BUTTON_PAY')?>",
				hidePayButton: "<?=GetMessageJS('CRM_ENTITY_EM_HIDE_BUTTON_PAY')?>",
			};

			BX.UI.EntityEditorFieldConfigurator.messages =
			{
				labelField: "<?=GetMessageJS('CRM_ENTITY_ED_FIELD_TITLE')?>",
				showAlways: "<?=GetMessageJS('CRM_ENTITY_ED_SHOW_ALWAYS')?>",
				useTimezone: "<?=GetMessageJS('CRM_ENTITY_ED_USE_TIMEZONE')?>",
			};

			BX.Crm.EntityFieldVisibilityConfigurator.messages =
			{
				titleField: "<?=GetMessageJS('CRM_VISIBILITY_ATTR_TITLE')?>",
				labelField: "<?=GetMessageJS('CRM_VISIBILITY_ATTR_LABEL')?>",
				addUserButton: "<?=GetMessageJS('CRM_VISIBILITY_ADD_USER_BUTTON')?>",
			};

			BX.Crm.EntityEditorUserFieldConfigurator.messages =
			{
				labelField: "<?=GetMessageJS('CRM_ENTITY_ED_FIELD_TITLE')?>",
				isRequiredField: "<?=GetMessageJS('CRM_ENTITY_ED_UF_REQUIRED_FIELD')?>",
				isMultipleField: "<?=GetMessageJS('CRM_ENTITY_ED_UF_MULTIPLE_FIELD')?>",
				showAlways: "<?=GetMessageJS('CRM_ENTITY_ED_SHOW_ALWAYS')?>",
				enableTime: "<?=GetMessageJS('CRM_ENTITY_ED_UF_ENABLE_TIME')?>",
				enumItems: "<?=GetMessageJS('CRM_ENTITY_ED_UF_ENUM_ITEMS')?>",
				add: "<?=GetMessageJS('CRM_ENTITY_ED_ADD')?>"
			};

			BX.UI.EntityEditorField.messages =
			{
				hideButtonHint: "<?=GetMessageJS('CRM_ENTITY_ED_HIDE_BUTTON_HINT')?>",
				hideButtonDisabledHint: "<?=GetMessageJS('CRM_ENTITY_ED_HIDE_BUTTON_DISABLED_HINT')?>",
				requiredFieldError: "<?=GetMessageJS('CRM_ENTITY_ED_REQUIRED_FIELD_ERROR')?>",
				add: "<?=GetMessageJS('CRM_ENTITY_ED_ADD')?>",
				hide: "<?=GetMessageJS('CRM_ENTITY_ED_HIDE')?>",
				showAlways: "<?=GetMessageJS('CRM_ENTITY_ED_SHOW_ALWAYS')?>",
				configure: "<?=GetMessageJS('CRM_ENTITY_ED_CONFIGURE')?>",
				isEmpty: "<?=GetMessageJS('CRM_ENTITY_ED_FIELD_EMPTY')?>",
				hideDeniedDlgTitle: "<?=GetMessageJS('CRM_ENTITY_ED_HIDE_TITLE')?>",
				hideDeniedDlgContent: "<?=GetMessageJS('CRM_ENTITY_ED_HIDE_DENIED')?>",
				hiddenInViewMode: "<?=GetMessageJS('CRM_ENTITY_ED_FIELD_HIDDEN_IN_VIEW_MODE')?>",
				isHiddenDueToShowAlwaysChanged: "<?=GetMessageJS('CRM_ENTITY_ED_FIELD_HIDDEN_DUE_TO_SHOW_ALWAYS_CHANGED')?>"
			};

			BX.Crm.EntityEditorUserField.messages =
			{
				moveAddrToRequisite: "<?=GetMessageJS('CRM_ENTITY_ED_MOVE_ADDR_TO_REQUISITE')?>",
				moveAddrToRequisiteHtml: "<?=GetMessageJS('CRM_ENTITY_ED_MOVE_ADDR_TO_REQUISITE_HTML')?>",
				moveAddrToRequisiteBtnStart: "<?=GetMessageJS('CRM_ENTITY_ED_MOVE_ADDR_TO_REQUISITE_BTN_START')?>",
				moveAddrToRequisiteBtnCancel: "<?=GetMessageJS('CRM_ENTITY_ED_MOVE_ADDR_TO_REQUISITE_BTN_CANCEL')?>",
				moveAddrToRequisiteStartSuccess: "<?=GetMessageJS('CRM_ENTITY_ED_MOVE_ADDR_TO_REQUISITE_START_SUCCESS')?>"
			};

			BX.Crm.EntityEditorSection.messages =
			{
				change: "<?=GetMessageJS('CRM_ENTITY_ED_CHANGE')?>",
				cancel: "<?=GetMessageJS('CRM_ENTITY_ED_CANCEL')?>",
				createField: "<?=GetMessageJS('CRM_ENTITY_ED_CREATE_FIELD')?>",
				selectField: "<?=GetMessageJS('CRM_ENTITY_ED_SELECT_FIELD')?>",
				deleteSection: "<?=GetMessageJS('CRM_ENTITY_ED_DELETE_SECTION')?>",
				deleteSectionConfirm: "<?=GetMessageJS('CRM_ENTITY_ED_DELETE_SECTION_CONFIRM')?>",
				selectFieldFromOtherSection: "<?=GetMessageJS('CRM_ENTITY_ED_SELECT_FIELD_FROM_OTHER_SECTION')?>",
				transferDialogTitle: "<?=GetMessageJS('CRM_ENTITY_ED_FIELD_TRANSFER_DIALOG_TITLE')?>",
				nothingSelected: "<?=GetMessageJS('CRM_ENTITY_ED_NOTHIG_SELECTED')?>",
				deleteSectionDenied: "<?=GetMessageJS('CRM_ENTITY_ED_DELETE_SECTION_DENIED')?>",
				openDetails: "<?=GetMessageJS('CRM_ENTITY_ED_SECTION_OPEN_DETAILS')?>"
			};

			BX.UI.EntityEditorBoolean.messages =
			{
				yes: "<?=GetMessageJS('MAIN_YES')?>",
				no: "<?=GetMessageJS('MAIN_NO')?>"
			};

			BX.Crm.EntityEditorUser.messages =
			{
				change: "<?=GetMessageJS('CRM_ENTITY_ED_CHANGE_USER')?>"
			};

			BX.Crm.EntityEditorMultipleUser.messages =
				{
					change: "<?=GetMessageJS('CRM_ENTITY_ED_CHANGE_USER')?>"
				};

			BX.Crm.EntityEditorFileStorage.messages =
			{
				diskAttachFiles: "<?=GetMessageJS('CRM_ENTITY_ED_DISK_ATTACH_FILE')?>",
				diskAttachedFiles: "<?=GetMessageJS('CRM_ENTITY_ED_DISK_ATTACHED_FILES')?>",
				diskSelectFile: "<?=GetMessageJS('CRM_ENTITY_ED_DISK_SELECT_FILE')?>",
				diskSelectFileLegend: "<?=GetMessageJS('CRM_ENTITY_ED_DISK_SELECT_FILE_LEGEND')?>",
				diskUploadFile: "<?=GetMessageJS('CRM_ENTITY_ED_DISK_UPLOAD_FILE')?>",
				diskUploadFileLegend: "<?=GetMessageJS('CRM_ENTITY_ED_DISK_UPLOAD_FILE_LEGEND')?>"
			};

			BX.UI.EntityEditorHtml.messages =
			{
				expand: "<?=GetMessageJS('CRM_ENTITY_ED_EXPAND_COMMENT')?>",
				collapse: "<?=GetMessageJS('CRM_ENTITY_ED_COLLAPSE_COMMENT')?>"
			};

			BX.Crm.PrimaryClientEditor.messages =
			{
				select: "<?=GetMessageJS('CRM_ENTITY_ED_SELECT')?>",
				bind: "<?=GetMessageJS('CRM_ENTITY_ED_BIND')?>",
				create: "<?=GetMessageJS('CRM_ENTITY_ED_CREATE')?>"
			};

			BX.Crm.SecondaryClientEditor.messages =
			{
				select: "<?=GetMessageJS('CRM_ENTITY_ED_SELECT')?>",
				create: "<?=GetMessageJS('CRM_ENTITY_ED_CREATE')?>",
				bind: "<?=GetMessageJS('CRM_ENTITY_ED_BIND')?>",
				addParticipant: "<?=GetMessageJS('CRM_ENTITY_ED_ADD_PARTICIPANT')?>"
			};

			BX.Crm.EntityEditorClientSearchBox.messages =
			{
				contactToCreateTag: "<?=GetMessageJS('CRM_ENTITY_ED_NEW_CONTACT')?>",
				companyToCreateTag: "<?=GetMessageJS('CRM_ENTITY_ED_NEW_COMPANY')?>",
				contactToCreateLegend: "<?=GetMessageJS('CRM_ENTITY_ED_NEW_CONTACT_LEGEND')?>",
				companyToCreateLegend: "<?=GetMessageJS('CRM_ENTITY_ED_NEW_COMPANY_LEGEND')?>",
				contactChangeButtonHint: "<?=GetMessageJS('CRM_ENTITY_ED_CONTACT_CHANGE_BUTTON_HINT')?>",
				companyChangeButtonHint: "<?=GetMessageJS('CRM_ENTITY_ED_COMPANY_CHANGE_BUTTON_HINT')?>",
				entityEditTag: "<?=GetMessageJS('CRM_ENTITY_ED_EDIT_TAG')?>",
				notFound: "<?=GetMessageJS('CRM_ENTITY_ED_NOT_FOUND')?>",
				unnamed: "<?=CUtil::JSEscape(\CCrmContact::GetDefaultName())?>",
				untitled: "<?=CUtil::JSEscape(\CCrmCompany::GetDefaultTitle())?>"
			};

			BX.Crm.ClientEditorCommunicationButton.messages =
			{
				telephonyNotSupported: "<?=GetMessageJS('CRM_ENTITY_ED_TELEPHONY_NOT_SUPPORTED')?>",
				messagingNotSupported: "<?=GetMessageJS('CRM_ENTITY_ED_MESSAGING_NOT_SUPPORTED')?>"
			};

			BX.Crm.EntityEditorEntity.messages =
			{
				select: "<?=GetMessageJS('CRM_ENTITY_ED_SELECT')?>"
			};

			BX.Crm.EntityEditorClientLight.messages =
			{
				addParticipant: "<?=GetMessageJS('CRM_ENTITY_ED_ADD_PARTICIPANT')?>",
				companySearchPlaceholder: "<?=GetMessageJS('CRM_ENTITY_ED_COMPANY_SEARCH_PLACEHOLDER_2')?>",
				contactSearchPlaceholder: "<?=GetMessageJS('CRM_ENTITY_ED_CONTACT_SEARCH_PLACEHOLDER_2')?>",
				enableCompany: "<?=GetMessageJS('CRM_ENTITY_ED_ENABLE_CLIENT_COMPANY')?>",
				disableCompany: "<?=GetMessageJS('CRM_ENTITY_ED_DISABLE_CLIENT_COMPANY')?>",
				enableContact: "<?=GetMessageJS('CRM_ENTITY_ED_ENABLE_CLIENT_CONTACT')?>",
				disableContact: "<?=GetMessageJS('CRM_ENTITY_ED_DISABLE_CLIENT_CONTACT')?>",
				enableAddress: "<?=GetMessageJS('CRM_ENTITY_ED_ENABLE_CLIENT_ADDRESS')?>",
				disableAddress: "<?=GetMessageJS('CRM_ENTITY_ED_DISABLE_CLIENT_ADDRESS')?>",
				enableRequisites: "<?=GetMessageJS('CRM_ENTITY_ED_ENABLE_CLIENT_REQUISITES')?>",
				disableRequisites: "<?=GetMessageJS('CRM_ENTITY_ED_DISABLE_CLIENT_REQUISITES')?>",
				displayContactAtFirst: "<?=GetMessageJS('CRM_ENTITY_ED_DISPLAY_CONTACT_AT_FIRST')?>",
				displayCompanyAtFirst: "<?=GetMessageJS('CRM_ENTITY_ED_DISPLAY_COMPANY_AT_FIRST')?>",
				enableQuickEdit: "<?=GetMessageJS('CRM_ENTITY_ED_ENABLE_QUICK_EDIT')?>",
				disableQuickEdit: "<?=GetMessageJS('CRM_ENTITY_ED_DISABLE_QUICK_EDIT')?>"
			};

			BX.Crm.EntityEditorMoney.messages =
			{
				manualOpportunitySetAutomatic: "<?=GetMessageJS('CRM_EDITOR_MANUAL_OPPORTUNITY_SET_AUTOMATIC')?>"
			}

			BX.Crm.EntityEditorProductRowProxy.messages =
			{
				manualOpportunityConfirmationTitle: "<?=GetMessageJS('CRM_EDITOR_MANUAL_OPPORTUNITY_CONFIRMATION_TITLE')?>",
				manualOpportunityConfirmationText: "<?=GetMessageJS('CRM_EDITOR_MANUAL_OPPORTUNITY_CONFIRMATION_TEXT')?>",
				manualOpportunityConfirmationYes: "<?=GetMessageJS('MAIN_YES')?>",
				manualOpportunityConfirmationNo: "<?=GetMessageJS('MAIN_NO')?>",
				manualOpportunityChangeModeTitle: "<?=GetMessageJS('CRM_EDITOR_MANUAL_OPPORTUNITY_CHANGE_TITLE_'.$arResult['ENTITY_TYPE_ID'])?>",
				manualOpportunityChangeModeText: "<?=GetMessageJS('CRM_EDITOR_MANUAL_OPPORTUNITY_CHANGE_TEXT_'.$arResult['ENTITY_TYPE_ID'])?>",
				manualOpportunityChangeModeYes: "<?=GetMessageJS('CRM_EDITOR_MANUAL_OPPORTUNITY_CHANGE_VALUE_AUTO')?>",
				manualOpportunityChangeModeNo: "<?=GetMessageJS('CRM_EDITOR_MANUAL_OPPORTUNITY_CHANGE_VALUE_MANUAL')?>"
			};

			BX.Crm.EntityProductListController.messages = BX.Crm.EntityEditorProductRowProxy.messages;

			BX.Crm.EntityEditorProductRowSummary.messages =
			{
				notShown: "<?=GetMessageJS('CRM_ENTITY_ED_PRODUCT_NOT_SHOWN')?>",
				total: "<?=GetMessageJS('CRM_ENTITY_ED_TOTAL')?>"
			};

			BX.Crm.ClientEditorEntityRequisitePanel.messages =
			{
				toggle: "<?=GetMessageJS('CRM_ENTITY_ED_TOGGLE_REQUISITES')?>"
			};

			BX.Crm.RequisiteNavigator.messages =
			{
				next: "<?=GetMessageJS('CRM_ENTITY_ED_NAVIGATION_NEXT')?>",
				toggle: "<?=GetMessageJS('CRM_ENTITY_ED_TOGGLE_REQUISITES')?>",
				legend: "<?=GetMessageJS('CRM_ENTITY_ED_NAVIGATION_LEGEND')?>",
				stub: "<?=GetMessageJS('CRM_ENTITY_ED_NO_REQUISITE_STUB')?>"
			};

			BX.Crm.EntityEditorRequisiteSelector.messages =
			{
				bankDetails: "<?=GetMessageJS('CRM_ENTITY_ED_BANK_DETAILS')?>"
			};

			BX.Crm.EntityEditorRequisiteListItem.messages =
			{
				deleteTitle: "<?=GetMessageJS("CRM_ENTITY_ED_REQUISITE_DELETE_DLG_TITLE")?>",
				deleteConfirm: "<?=GetMessageJS("CRM_ENTITY_ED_REQUISITE_DELETE_DLG_CONTENT")?>"
			};

			BX.Crm.EntityEditorRequisiteList.messages =
			{
				deleteTitle: "<?=GetMessageJS("CRM_ENTITY_ED_REQUISITE_DELETE_DLG_TITLE")?>",
				deleteConfirm: "<?=GetMessageJS("CRM_ENTITY_ED_REQUISITE_DELETE_DLG_CONTENT")?>"
			};

			BX.Crm.EntityEditorRecurring.messages =
			{
				notRepeat: "<?=GetMessageJS('CRM_ENTITY_ED_RECURRING_NOT_REPEAT')?>",
				modeTitle: "<?=GetMessageJS('CRM_ENTITY_ED_RECURRING_MODE_TITLE')?>",
				hide: "<?=GetMessageJS('CRM_ENTITY_ED_HIDE')?>"
			};

			BX.Crm.EntityEditorRecurringSingleField.messages =
			{
				until: "<?=GetMessageJS('CRM_ENTITY_ED_RECURRING_UNTIL')?>"
			};

			BX.Crm.EntityEditorPayment.messages =
			{
				paymentWasPaid: "<?=GetMessageJS('CRM_ENTITY_ED_PAYMENT_WAS_PAID')?>",
				paymentWasNotPaid: "<?=GetMessageJS('CRM_ENTITY_ED_PAYMENT_WAS_NOT_PAID')?>",
				paymentCancel: "<?=GetMessageJS('CRM_ENTITY_ED_PAYMENT_CANCEL')?>",
				paymentReturn: "<?=GetMessageJS('CRM_ENTITY_ED_PAYMENT_RETURN')?>",
				documentTitle: "<?=GetMessageJS('CRM_ENTITY_ED_PAYMENT_DOCUMENT_TITLE')?>",
				addDocument: "<?=GetMessageJS('CRM_ENTITY_ED_PAYMENT_ADD_DOCUMENT')?>",
				sum: "<?=GetMessageJS('CRM_ENTITY_ED_PAYMENT_SUM')?>",
			};

			BX.Crm.EntityEditorOrderController.messages =
			{
				saveChanges: "<?=GetMessageJS('CRM_ENTITY_ED_PAYMENT_SAVE_CHANGES')?>",
				saveConfirm: "<?=GetMessageJS('CRM_ENTITY_ED_PAYMENT_SAVE_CONFIRM')?>",
				save: "<?=GetMessageJS('CRM_ENTITY_ED_PAYMENT_SAVE')?>",
				notSave: "<?=GetMessageJS('CRM_ENTITY_ED_PAYMENT_NOT_SAVE')?>"
			};

			BX.Crm.EntityEditorPaymentStatus.messages =
			{
				paymentWasPaid: "<?=GetMessageJS('CRM_ENTITY_ED_PAYMENT_WAS_PAID')?>",
				paymentWasNotPaid: "<?=GetMessageJS('CRM_ENTITY_ED_PAYMENT_WAS_NOT_PAID')?>",
				paymentCancel: "<?=GetMessageJS('CRM_ENTITY_ED_PAYMENT_CANCEL')?>",
				paymentReturn: "<?=GetMessageJS('CRM_ENTITY_ED_PAYMENT_RETURN')?>"
			};

			BX.Crm.EntityEditorShipment.messages =
			{
				deliveryAllowed: "<?=GetMessageJS('CRM_ENTITY_ED_SHIPMENT_DELIVERY_ALLOWED')?>",
				deducted: "<?=GetMessageJS('CRM_ENTITY_ED_SHIPMENT_DEDEUCTED')?>",
				trackingNumberTitle: "<?=GetMessageJS('CRM_ENTITY_ED_SHIPMENT_TRACKING_NUMBER_TITLE')?>",
				documentTitle: "<?=GetMessageJS('CRM_ENTITY_ED_SHIPMENT_DOCUMENT_TITLE')?>",
				addDocument: "<?=GetMessageJS('CRM_ENTITY_ED_SHIPMENT_ADD_DOCUMENT')?>",
				deliveryService: "<?=GetMessageJS('CRM_ENTITY_ED_SHIPMENT_DELIVERY_SERVICE')?>",
				deliveryProfile: "<?=GetMessageJS('CRM_ENTITY_ED_SHIPMENT_DELIVERY_PROFILE')?>",
				price: "<?=GetMessageJS('CRM_ENTITY_ED_SHIPMENT_DELIVERY_PRICE')?>",
				profile: "<?=GetMessageJS('CRM_ENTITY_ED_SHIPMENT_PROFILE')?>",
				deliveryPriceCalculated: "<?=GetMessageJS('CRM_ENTITY_ED_SHIPMENT_DELIVERY_PRICE_CALCULATED')?>",
				deliveryPriceCalculatedHint: "<?=GetMessageJS('CRM_ENTITY_ED_SHIPMENT_DELIVERY_PRICE_CALCULATED_HINT')?>",
				deliveryStore: "<?=GetMessageJS('CRM_ENTITY_ED_SHIPMENT_DELIVERY_STORE')?>",
				refresh: "<?=GetMessageJS('CRM_ENTITY_ED_SHIPMENT_REFRESH')?>",
			};

			BX.Crm.EntityEditorDeliverySelector.messages =
			{
				notSelected: "<?=GetMessageJS('CRM_ENTITY_ED_DELIVERY_SELECTOR_NOT_SELECTED')?>",
				deliveryStore: "<?=GetMessageJS('CRM_ENTITY_ED_SHIPMENT_DELIVERY_STORE')?>",
				deliveryProfile: "<?=GetMessageJS('CRM_ENTITY_ED_SHIPMENT_DELIVERY_PROFILE')?>",
			};

			BX.Crm.EntityEditorOrderPropertySubsection.messages =
			{
				linkToSettings: "<?=GetMessageJS('CRM_ENTITY_ED_CHILD_ENTITY_MENU_SETTINGS_LINK')?>"
			};
			BX.Crm.EntityEditorOrderPropertyWrapper.messages =
			{
				createField: "<?=GetMessageJS('CRM_ENTITY_ED_ORDER_PROPERTY_CREATE')?>",
				insertField: "<?=GetMessageJS('CRM_ENTITY_ED_ORDER_PROPERTY_INSERT')?>",
				selectField: "<?=GetMessageJS('CRM_ENTITY_ED_ORDER_PROPERTY_SELECT')?>",
				disabledBlockTitle: "<?=GetMessageJS('CRM_ENTITY_ED_ORDER_PROPERTY_UNKNOWN_GROUP')?>"
			};

			BX.Crm.EntityEditorPaymentCheck.messages =
			{
				titleFieldSum: "<?=GetMessageJS('CRM_ENTITY_ED_ORDER_PAYMENT_CHECK_SUM')?>",
				titleFieldDateCreate: "<?=GetMessageJS('CRM_ENTITY_ED_ORDER_PAYMENT_CHECK_DATE_CREATE')?>",
				titleFieldType: "<?=GetMessageJS('CRM_ENTITY_ED_ORDER_PAYMENT_CHECK_TYPE')?>",
				titleFieldCashBoxName: "<?=GetMessageJS('CRM_ENTITY_ED_ORDER_PAYMENT_CHECK_CASHBOX_NAME')?>",
				titleFieldStatus: "<?=GetMessageJS('CRM_ENTITY_ED_ORDER_PAYMENT_CHECK_STATUS')?>",
				titleFieldLink: "<?=GetMessageJS('CRM_ENTITY_ED_ORDER_PAYMENT_CHECK_LINK')?>",
				emptyCheckList: "<?=GetMessageJS('CRM_ENTITY_ED_ORDER_PAYMENT_CHECK_EMPTY')?>"
			};

			BX.Crm.EntityEditorOrderProductProperty.messages =
			{
				addProductProperty: "<?=GetMessageJS('CRM_ENTITY_ED_ORDER_ADD_PRODUCT_PROPERTY_LINK')?>",
				fieldBlockTitle: "<?=GetMessageJS('CRM_ENTITY_ED_ORDER_PRODUCT_FIELD_BLOCK_TITLE')?>",
				fieldTitleName: "<?=GetMessageJS('CRM_ENTITY_ED_ORDER_PRODUCT_FIELD_NAME')?>",
				fieldTitleValue: "<?=GetMessageJS('CRM_ENTITY_ED_ORDER_PRODUCT_FIELD_VALUE')?>",
				fieldTitleCode: "<?=GetMessageJS('CRM_ENTITY_ED_ORDER_PRODUCT_FIELD_CODE')?>",
				fieldTitleSort: "<?=GetMessageJS('CRM_ENTITY_ED_ORDER_PRODUCT_FIELD_SORT')?>"
			};
			BX.Crm.EntityEditorOrderUser.messages =
			{
				change: "<?=GetMessageJS('CRM_ENTITY_ED_CHANGE_USER')?>",
				searchPlaceholder: "<?=GetMessageJS('CRM_ENTITY_ED_ORDER_USER_SEARCH_PLACEHOLDER')?>",
			};

			BX.Crm.EntityEditorOrderClientSearchBox.messages =
			{
				notFound: "<?=GetMessageJS('CRM_ENTITY_ED_NOT_FOUND')?>"
			};

			BX.message(
				{
					"CRM_EDITOR_SAVE": "<?=GetMessageJS('CRM_ENTITY_ED_SAVE')?>",
					"CRM_EDITOR_CONTINUE": "<?=GetMessageJS('CRM_ENTITY_ED_CONTINUE')?>",
					"CRM_EDITOR_CANCEL": "<?=GetMessageJS('CRM_ENTITY_ED_CANCEL')?>",
					"CRM_EDITOR_DELETE": "<?=GetMessageJS('CRM_ENTITY_ED_DELETE')?>",
					"CRM_EDITOR_ADD": "<?=GetMessageJS('CRM_ENTITY_ED_ADD')?>",
					"CRM_EDITOR_CONFIRMATION": "<?=GetMessageJS('CRM_EDITOR_CONFIRMATION')?>",
					"CRM_EDITOR_CLOSE_CONFIRMATION": "<?=GetMessageJS('CRM_EDITOR_CLOSE_CONFIRMATION')?>",
					"CRM_EDITOR_SAVE_ERROR_TITLE": "<?=GetMessageJS('CRM_EDITOR_SAVE_ERROR_TITLE')?>",
					"CRM_EDITOR_SAVE_ERROR_CONTENT": "<?=GetMessageJS('CRM_EDITOR_SAVE_ERROR_CONTENT')?>",
					"CRM_EDITOR_PAYMENT_PAID": "<?=GetMessageJS('CRM_EDITOR_PAYMENT_PAID')?>",
					"CRM_EDITOR_PAYMENT_NOT_PAID": "<?=GetMessageJS('CRM_EDITOR_PAYMENT_NOT_PAID')?>",
					"CRM_EDITOR_CANCEL_CONFIRMATION": "<?=GetMessageJS('CRM_EDITOR_CANCEL_CONFIRMATION')?>",
					"CRM_EDITOR_YES": "<?=GetMessageJS('MAIN_YES')?>",
					"CRM_EDITOR_NO": "<?=GetMessageJS('MAIN_NO')?>",
					"CRM_EDITOR_PHONE": "<?=GetMessageJS('CRM_EDITOR_PHONE')?>",
					"CRM_EDITOR_EMAIL": "<?=GetMessageJS('CRM_EDITOR_EMAIL')?>",
					"CRM_EDITOR_ADDRESS": "<?=GetMessageJS('CRM_EDITOR_ADDRESS')?>",
					"CRM_EDITOR_REQUISITES": "<?=GetMessageJS('CRM_EDITOR_REQUISITES')?>",
					"CRM_EDITOR_PLACEMENT_CAUTION": "<?=GetMessageJS('CRM_EDITOR_PLACEMENT_CAUTION')?>",

				}
			);

			BX.Crm.EntityPhaseLayout.colors =
				{
					process: "<?=Bitrix\Crm\Color\PhaseColorScheme::PROCESS_COLOR?>",
					success: "<?=Bitrix\Crm\Color\PhaseColorScheme::SUCCESS_COLOR?>",
					failure: "<?=Bitrix\Crm\Color\PhaseColorScheme::FAILURE_COLOR?>",
					apology: "<?=Bitrix\Crm\Color\PhaseColorScheme::FAILURE_COLOR?>"
				};

			var bizprocManager = null;
			var restPlacementTabManager = null;
			<?if(!$arResult['IS_EMBEDDED']){?>
			bizprocManager = BX.Crm.EntityBizprocManager.create(
				"<?=CUtil::JSEscape($guid)?>",
				<?=\Bitrix\Main\Web\Json::encode($arResult['BIZPROC_MANAGER_CONFIG'])?>
			);

			restPlacementTabManager = BX.Crm.EntityRestPlacementManager.create(
				"<?=CUtil::JSEscape($guid)?>",
				<?=\CUtil::PhpToJSObject($arResult['REST_PLACEMENT_TAB_CONFIG'])?>
			);
			<?}?>

			BX.Crm.EntityEditor.setDefault(
				BX.Crm.EntityEditor.create(
					"<?=CUtil::JSEscape($guid)?>",
					{
						entityTypeName: "<?=CUtil::JSEscape($arResult['ENTITY_TYPE_NAME'])?>",
						entityTypeId: <?=$arResult['ENTITY_TYPE_ID']?>,
						entityId: <?=$arResult['ENTITY_ID']?>,
						model: model,
						config: config,
						moduleId: 'crm',
						scheme: scheme,
						validators: <?=CUtil::PhpToJSObject($arResult['ENTITY_VALIDATORS'])?>,
						controllers: <?=CUtil::PhpToJSObject($arResult['ENTITY_CONTROLLERS'])?>,
						detailManagerId: "<?=CUtil::JSEscape($arResult['DETAIL_MANAGER_ID'])?>",
						userFieldManager: userFieldManager,
						bizprocManager: bizprocManager,
						restPlacementTabManager: restPlacementTabManager,
						canCreateContact: <?=CUtil::PhpToJSObject($arResult['CAN_CREATE_CONTACT'])?>,
						canCreateCompany: <?=CUtil::PhpToJSObject($arResult['CAN_CREATE_COMPANY'])?>,
						duplicateControl: <?=CUtil::PhpToJSObject($arResult['DUPLICATE_CONTROL'])?>,
						initialMode: "<?=CUtil::JSEscape($arResult['INITIAL_MODE'])?>",
						enableModeToggle: <?=$arResult['ENABLE_MODE_TOGGLE'] ? 'true' : 'false'?>,
						enableVisibilityPolicy: <?=$arResult['ENABLE_VISIBILITY_POLICY'] ? 'true' : 'false'?>,
						enableToolPanel: <?=$arResult['ENABLE_TOOL_PANEL'] ? 'true' : 'false'?>,
						enableBottomPanel: <?=$arResult['ENABLE_BOTTOM_PANEL'] ? 'true' : 'false'?>,
						enableFieldsContextMenu: <?=$arResult['ENABLE_FIELDS_CONTEXT_MENU'] ? 'true' : 'false'?>,
						enablePageTitleControls: <?=$arResult['ENABLE_PAGE_TITLE_CONTROLS'] ? 'true' : 'false'?>,
						enableCommunicationControls: <?=$arResult['ENABLE_COMMUNICATION_CONTROLS'] ? 'true' : 'false'?>,
						enableExternalLayoutResolvers: <?=$arResult['ENABLE_EXTERNAL_LAYOUT_RESOLVERS'] ? 'true' : 'false'?>,
						readOnly: <?=$arResult['READ_ONLY'] ? 'true' : 'false'?>,
						enableAjaxForm: <?=$arResult['ENABLE_AJAX_FORM'] ? 'true' : 'false'?>,
						enableRequiredUserFieldCheck: <?=$arResult['ENABLE_REQUIRED_USER_FIELD_CHECK'] ? 'true' : 'false'?>,
						enableSectionEdit: <?=$arResult['ENABLE_SECTION_EDIT'] ? 'true' : 'false'?>,
						enableSectionCreation: <?=$arResult['ENABLE_SECTION_CREATION'] ? 'true' : 'false'?>,
						enableSettingsForAll: <?=$arResult['ENABLE_SETTINGS_FOR_ALL'] ? 'true' : 'false'?>,
						inlineEditLightingHint: "<?=CUtil::JSEscape($arResult['INLINE_EDIT_LIGHTING_HINT'])?>",
						inlineEditSpotlightId: "<?=CUtil::JSEscape($arResult['INLINE_EDIT_SPOTLIGHT_ID'])?>",
						enableInlineEditSpotlight: <?=$arResult['ENABLE_INLINE_EDIT_SPOTLIGHT'] ? 'true' : 'false'?>,
						containerId: "<?=CUtil::JSEscape($containerID)?>",
						buttonContainerId: "<?=CUtil::JSEscape($buttonContainerID)?>",
						createSectionButtonId: "<?=CUtil::JSEscape($createSectionButtonID)?>",
						configMenuButtonId: "<?=CUtil::JSEscape($configMenuButtonID)?>",
						configIconId: "<?=CUtil::JSEscape($configIconID)?>",
						htmlEditorConfigs: <?=CUtil::PhpToJSObject($htmlEditorConfigs)?>,
						serviceUrl: "<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>",
						externalContextId: "<?=CUtil::JSEscape($arResult['EXTERNAL_CONTEXT_ID'])?>",
						contextId: "<?=CUtil::JSEscape($arResult['CONTEXT_ID'])?>",
						context: <?=CUtil::PhpToJSObject($arResult['CONTEXT'])?>,
						entityDetailsUrl: "<?=CUtil::JSEscape($arResult['PATH_TO_ENTITY_DETAILS'])?>",
						contactCreateUrl: "<?=CUtil::JSEscape($arResult['PATH_TO_CONTACT_CREATE'])?>",
						contactEditUrl: "<?=CUtil::JSEscape($arResult['PATH_TO_CONTACT_EDIT'])?>",
						contactRequisiteSelectUrl: "<?=CUtil::JSEscape($arResult['PATH_TO_CONTACT_REQUISITE_SELECT'])?>",
						companyCreateUrl: "<?=CUtil::JSEscape($arResult['PATH_TO_COMPANY_CREATE'])?>",
						companyEditUrl: "<?=CUtil::JSEscape($arResult['PATH_TO_COMPANY_EDIT'])?>",
						companyRequisiteSelectUrl: "<?=CUtil::JSEscape($arResult['PATH_TO_COMPANY_REQUISITE_SELECT'])?>",
						requisiteEditUrl: "<?=CUtil::JSEscape($arResult['PATH_TO_REQUISITE_EDIT'])?>",
						options: <?=CUtil::PhpToJSObject($arResult['EDITOR_OPTIONS'])?>,
						attributeConfig: <?=CUtil::PhpToJSObject($arResult['ATTRIBUTE_CONFIG'])?>,
						showEmptyFields: <?=$arResult['SHOW_EMPTY_FIELDS'] ? 'true' : 'false'?>,
						isEmbedded: <?=$arResult['IS_EMBEDDED'] ? 'true' : 'false'?>,
						ufAccessRights: <?=CUtil::PhpToJSObject($arResult['USER_FIELD_ACCESS_RIGHTS'])?>
					}
				)
			);
		}
	);
</script>