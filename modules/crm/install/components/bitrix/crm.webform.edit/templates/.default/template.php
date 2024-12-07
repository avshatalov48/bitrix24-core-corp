<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\UI\Extension;
use Bitrix\Crm\UI\Webpack;

/** @var array $arResult Component result. */
/** @var array $arParams Component parameters. */

if(!$arResult['FORM']['BUTTON_COLOR_BG'])
{
	$arResult['FORM']['BUTTON_COLOR_BG'] = '#00AEEF';
}
if(!$arResult['FORM']['BUTTON_COLOR_FONT'])
{
	$arResult['FORM']['BUTTON_COLOR_FONT'] = '#FFFFFF';
}
if(!$arResult['FORM']['BUTTON_CAPTION'])
{
	$arResult['FORM']['BUTTON_CAPTION'] = Loc::getMessage('CRM_WEBFORM_EDIT_FORM_SUBMIT_BTN_CAPTION_DEFAULT');
}
if(!$arResult['FORM']['LICENCE_BUTTON_CAPTION'])
{
	$arResult['FORM']['LICENCE_BUTTON_CAPTION'] = Loc::getMessage('CRM_WEBFORM_EDIT_LICENCE_BUTTON_CAPTION_DEFAULT');
}

$isAvailableDesign = $arResult['IS_AVAILABLE_EMBEDDING'] && $arResult['FORM']['ID'];

Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.alerts',
	'ui.buttons',
	'color_picker',
	'date',
	'sidepanel',
]);

if(\Bitrix\Main\Loader::includeModule("socialnetwork"))
{
	CUtil::InitJSCore(array("socnetlogdest"));
}
if(\Bitrix\Main\Loader::includeModule("bitrix24"))
{
	CBitrix24::initLicenseInfoPopupJS();
}
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/main/core/core_dragdrop.js');
$APPLICATION->SetPageProperty(
	"BodyClass",
	$APPLICATION->GetPageProperty("BodyClass") . " no-paddings"
);


require 'php_templates.php';
require 'php_userblockcontroller.php';

global $USER;
$userBlockController = new CrmWebFormEditUserBlockController(
	$USER->GetID(),
	CrmWebFormEditTemplate::getUserBlock(),
	CrmWebFormEditTemplate::getUserBlockNav()
);
?>

<?$jsEventsManagerId = 'PageEventsManager_'.$arResult['COMPONENT_ID'];?>
<script>
	BX.ready(function()
	{
		BX.message['CRM_WEBFORM_EDIT_JS_PRODUCT_CHOICE'] = '<?=GetMessageJS('CRM_WEBFORM_EDIT_JS_PRODUCT_CHOICE')?>';
		BX.namespace("BX.Crm");
		BX.Crm["<?=$jsEventsManagerId?>"] = BX.Crm.PageEventsManagerClass.create({id: "<?=$arResult['COMPONENT_ID']?>"});

		new CrmFormEditor({
			context: BX('FORM_CONTAINER'),
			fields: <?=CUtil::PhpToJSObject($arResult['FORM']['FIELDS'])?>,
			fieldsDictionary: <?=CUtil::PhpToJSObject($arResult['AVAILABLE_FIELDS'])?>,
			schemesDictionary: <?=CUtil::PhpToJSObject($arResult['ENTITY_SCHEMES'])?>,
			entityDictionary: <?=CUtil::PhpToJSObject($arResult['AVAILABLE_ENTITIES'])?>,
			dynamicEntities: <?=CUtil::PhpToJSObject($arResult['DYNAMIC_ENTITIES'])?>,
			dependencies: <?=CUtil::PhpToJSObject($arResult['FORM']['DEPENDENCIES'])?>,
			presetFields: <?=CUtil::PhpToJSObject($arResult['FORM']['PRESET_FIELDS'])?>,
			booleanFieldItems: <?=CUtil::PhpToJSObject($arResult['BOOLEAN_FIELD_ITEMS'])?>,
			isFrame: <?=CUtil::PhpToJSObject($arParams['IFRAME'])?>,
			isSaved: <?=CUtil::PhpToJSObject($arParams['IS_SAVED'])?>,
			reloadList: <?=CUtil::PhpToJSObject($arParams['RELOAD_LIST'])?>,
			editorChoise: <?=CUtil::PhpToJSObject($arResult['EDITOR_CHOISE'])?>,
			templates: {
				field: 'tmpl_field_%type%',
				dependency: 'tmpl_field_dependency',
				presetField: 'tmpl_field_preset'
			},
			jsEventsManagerId: '<?=$jsEventsManagerId?>',
			userBlocks: {
				optionPinName: '<?=CrmWebFormEditUserBlockController::USER_OPTION?>'
			},
			'id': '<?=CUtil::JSEscape($arResult['FORM']['ID'])?>',
			'actionRequestUrl': '<?=CUtil::JSEscape($this->getComponent()->getPath())?>' + '/ajax.php',
			'detailPageUrlTemplate': '<?=CUtil::JSEscape($arParams['PATH_TO_WEB_FORM_EDIT'])?>',
			'canRemoveCopyright': <?=($arResult['CAN_REMOVE_COPYRIGHT'] ? 'true' : 'false')?>,
			currency: <?=CUtil::PhpToJSObject($arResult['CURRENCY'])?>,
			designPageUrl: '<?=CUtil::JSEscape($arParams['PATH_TO_WEB_FORM_DESIGN'])?>',
			isAvailableDesign: '<?=CUtil::JSEscape($isAvailableDesign)?>',
			showRestrictionPopup: function(){ <?=$arResult["RESTRICTION_POPUP"] ?> },
			mess: <?=CUtil::PhpToJSObject(array(
				'selectField' => Loc::getMessage('CRM_WEBFORM_EDIT_SELECT_FIELD'),
				'selectFieldOrSection' => Loc::getMessage('CRM_WEBFORM_EDIT_SELECT_FIELD_OR_SECTION'),
				'selectValue' => Loc::getMessage('CRM_WEBFORM_EDIT_SELECT_FIELD_VALUE'),
				'newFieldSectionCaption' => Loc::getMessage('CRM_WEBFORM_EDIT_NEW_FIELD_SECTION_CAPTION'),
				'newFieldPageCaption' => Loc::getMessage('CRM_WEBFORM_EDIT_NEW_FIELD_PAGE_CAPTION'),
				'newFieldProductsCaption' => Loc::getMessage('CRM_WEBFORM_EDIT_NEW_FIELD_PRODUCT_CAPTION'),
				'dlgContinue' => Loc::getMessage('CRM_WEBFORM_EDIT_CONTINUE'),
				'dlgCancel' => Loc::getMessage('CRM_WEBFORM_EDIT_CANCEL'),
				'dlgClose' => Loc::getMessage('CRM_WEBFORM_EDIT_CLOSE'),
				'dlgTitle' => Loc::getMessage('CRM_WEBFORM_EDIT_POPUP_SETTINGS_TITLE'),
				'dlgInvoiceEmptyProductTitle' => Loc::getMessage('CRM_WEBFORM_EDIT_POPUP_INVOICE_EMPTY_PRODUCT_ERROR_TITLE'),
				'dlgInvoiceEmptyProduct' => Loc::getMessage('CRM_WEBFORM_EDIT_POPUP_INVOICE_EMPTY_PRODUCT_ERROR2'),
				'defaultProductName' => Loc::getMessage('CRM_WEBFORM_EDIT_DEFAULT_PRODUCT_NAME'),
				'dlgChange' => Loc::getMessage('CRM_WEBFORM_EDIT_CHANGE'),
				'dlgChoose' => Loc::getMessage('CRM_WEBFORM_EDIT_CHOOSE'),
				'dlgTitleFieldCreate' => Loc::getMessage('CRM_WEBFORM_EDIT_FIELD_CREATE_TITLE'),
				'dlgFieldPresetRemoveConfirm' => Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_PRESET_DEL_CONFIRM'),
				'dlgEditorChoiseBtnApply' => Loc::getMessage('CRM_WEBFORM_EDIT_EDITOR_CHOISE_BTN_APPLY'),
				'dlgEditorChoiseBtnHoldOver' => Loc::getMessage('CRM_WEBFORM_EDIT_EDITOR_CHOISE_BTN_HOLD_OVER'),
				'dlgEditorChoiseNew' => Loc::getMessage('CRM_WEBFORM_EDIT_EDITOR_CHOISE_NEW'),
				'dlgEditorChoiseOld' => Loc::getMessage('CRM_WEBFORM_EDIT_EDITOR_CHOISE_OLD'),
				'dlgEditorChoiseH1' => Loc::getMessage('CRM_WEBFORM_EDIT_EDITOR_H1'),
				'dlgEditorChoiseH2' => Loc::getMessage('CRM_WEBFORM_EDIT_EDITOR_H2_MSGVER_1'),
				'dlgEditorChoiseH3' => Loc::getMessage('CRM_WEBFORM_EDIT_EDITOR_H3'),
				'dlgEditorChoiseNotice' => Loc::getMessage('CRM_WEBFORM_EDIT_EDITOR_NOTICE'),
			))?>
		});
	});
</script>

<?
	require 'js_templates.php';
?>

<?$this->SetViewTarget('pagetitle', 5);?>
	<a id="CRM_WEBFORM_EDIT_TO_LIST" href="<?=htmlspecialcharsbx($arResult['PATH_TO_WEB_FORM_LIST'])?>" class="crm-webform-edit-list-back"><?=Loc::getMessage('CRM_WEBFORM_EDIT_BACK_TO_LIST1')?></a>
<?$this->EndViewTarget();?>

<?
if (!empty($arResult['ERRORS']))
{
	?><div class="crm-webform-edit-top-block"><?
	foreach ($arResult['ERRORS'] as $error)
	{
		ShowError($error);
	}
	?></div><?
}
?>

<form id="crm_webform_edit_form" name="crm_webform_edit_form" method="POST" enctype="multipart/form-data" action="<?=htmlspecialcharsbx($arResult['FORM_ACTION'])?>">
<input type="hidden" name="ID" value="<?=$arResult['FORM']['ID']?>">
<?=bitrix_sessid_post();?>

<div class="crm-webform-edit-wrapper">

	<div class="task-info">
		<div class="task-info-panel">
			<div class="task-info-panel-title">
				<input id="NAME" name="NAME" value="<?=htmlspecialcharsbx($arResult['FORM']['NAME'])?>" type="text" placeholder="<?=Loc::getMessage('CRM_WEBFORM_EDIT_FORM_NAME')?>">
			</div>
		</div>
		<div class="task-info-editor"></div>
	</div>

	<div class="crm-webform-edit-about-container" style="<?=(!$arResult['IS_AVAILABLE_EMBEDDING_PORTAL'] ? 'margin-bottom: 17px;' : '')?>">
		<div class="crm-webform-edit-about-tune-container">
			<span id="CRM_WEBFORM_STICKER_ENTITY_SCHEME_NAV" class="crm-webform-edit-about-tune"><?=Loc::getMessage('CRM_WEBFORM_EDIT_EDIT')?></span>
		</div>
		<div class="crm-webform-edit-about-info-container">
			<span id="CRM_WEBFORM_STICKER_ENTITY_SCHEME_TEXT" class="crm-webform-edit-about-info"><?=Loc::getMessage('CRM_WEBFORM_EDIT_DOCUMENT_CREATE')?>:
				<span id="ENTITY_SCHEMES_TOP_DESCRIPTION" class="crm-webform-edit-about-info-deal">
					<?=htmlspecialcharsbx($arResult['ENTITY_SCHEMES']['SELECTED_DESCRIPTION'])?>
				</span>
			</span>
		</div>
	</div>

	<?if($arResult['IS_AVAILABLE_EMBEDDING_PORTAL']):?>
	<div class="crm-webform-edit-v2-settings">
		<span id="crm-webform-editor-choise-btn" class="ui-btn ui-btn-light-border ui-btn-xs">
			<?=Loc::getMessage('CRM_WEBFORM_EDIT_EDITOR_CHOISE_NEW')?>
		</span>
		<span id="crm-webform-edit-design-btn" class="ui-btn ui-btn-light-border ui-btn-xs">
			<?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_DESIGN_SETUP')?>
		</span>
		<span class="crm-webform-edit-v2-settings-item-label">
			<?if($arResult['FORM']['ID']):?>
				<?if($arResult['IS_AVAILABLE_EMBEDDING']):?>
					<?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_DESIGN_TEXT')?>
				<?else:?>
					<?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_DESIGN_TEXT_UNAVAILABLE')?>
				<?endif;?>
			<?else:?>
				<?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_DESIGN_TEXT_AFTER_CREATE')?>
			<?endif;?>
		</span>
	</div>
	<?endif;?>


	<div class="crm-webform-edit-constructor-container" id="FORM_CONTAINER">

		<div id="FIELD_SELECTOR" class="crm-webform-edit-constructor-right-container">

			<div data-bx-crm-wf-selector-search-btn="" class="crm-webform-edit-constructor-right-search">
				<span class="crm-webform-edit-right-search-icon" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_FORM_TREE_SEARCH_BUTTON')?>"></span>
				<span class="crm-webform-edit-right-search-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_FORM_ADD_FIELD')?>:</span>
				<input data-bx-crm-wf-selector-search="" type="text" placeholder="<?=Loc::getMessage('CRM_WEBFORM_EDIT_FORM_TREE_SEARCH')?>" class="crm-webform-edit-right-search-input">
			</div><!--crm-webform-edit-constructor-right-search-->

			<div class="crm-webform-edit-constructor-right-list-container">

				<div class="crm-webform-edit-right-list">
					<?foreach($arResult['AVAILABLE_FIELDS_TREE'] as $entityName => $entityFields):
						if(in_array($entityName, array('CATALOG', 'ACTIVITY')))
						{
							continue;
						}
					?>
					<span
						data-bx-crm-wf-selector-field-group="<?=$entityName?>"
						class="crm-webform-edit-right-list-item"
					>
						<div class="crm-webform-edit-right-list-head">
							<span class="crm-webform-edit-right-list-item-icon"></span>
							<span class="crm-webform-edit-right-list-item-element"><?=htmlspecialcharsbx($entityFields['CAPTION'])?></span>
						</div>

						<ul class="crm-webform-edit-right-inner-list">
							<?foreach($entityFields['FIELDS'] as $field):?>
							<li data-bx-crm-wf-selector-field-name="<?=htmlspecialcharsbx($field['name'])?>" class="crm-webform-edit-right-inner-list-item"
							><?=htmlspecialcharsbx($field['caption'])?></li>
							<?endforeach;?>
						</ul>
					</span>
					<?endforeach;?>
				</div>
			</div><!--crm-webform-edit-constructor-right-list-container-->

			<div class="crm-webform-edit-constructor-right-add-element-container">
				<span class="crm-webform-edit-right-add-element-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_FORM_TREE_ADD_BUTTON')?>:</span>
				<ul class="crm-webform-edit-right-add-element-list">
					<li class="crm-webform-edit-right-add-element-list-item">
						<span data-bx-crm-wf-selector-btn-add="product" class="crm-webform-element-list-header"><?=Loc::getMessage('CRM_WEBFORM_EDIT_FORM_TREE_ADD_FIELD_PRODUCT')?></span>
					</li>
					<li class="crm-webform-edit-right-add-element-list-item">
						<span data-bx-crm-wf-selector-btn-add="section" class="crm-webform-element-list-subtitle"><?=Loc::getMessage('CRM_WEBFORM_EDIT_FORM_TREE_ADD_FIELD_SECTION')?></span>
					</li>
					<li class="crm-webform-edit-right-add-element-list-item">
						<span data-bx-crm-wf-selector-btn-add="hr" class="crm-webform-element-list-separator"><?=Loc::getMessage('CRM_WEBFORM_EDIT_FORM_TREE_ADD_FIELD_HR')?></span>
					</li>
					<li class="crm-webform-edit-right-add-element-list-item">
						<span data-bx-crm-wf-selector-btn-add="br" class="crm-webform-element-list-line-break"><?=Loc::getMessage('CRM_WEBFORM_EDIT_FORM_TREE_ADD_FIELD_BR')?></span>
					</li>
					<?if($arResult['IS_AVAILABLE_EMBEDDING_PORTAL']):?>
					<li class="crm-webform-edit-right-add-element-list-item">
						<span data-bx-crm-wf-selector-btn-add="page" class="crm-webform-element-list-line-page"><?=Loc::getMessage('CRM_WEBFORM_EDIT_FORM_TREE_ADD_FIELD_PAGE')?></span>
					</li>
					<?endif;?>
				</ul>
			</div><!--crm-webform-edit-constructor-right-add-element-container-->

		</div><!--crm-webform-edit-constructor-right-block-->

		<div class="crm-webform-edit-constructor-left-container">

			<div class="crm-webform-edit-left-field-header-container">
				<div id="CAPTION_CONTAINER" class="crm-webform-edit-left-field-header-inner">
					<div data-bx-web-form-lbl-cont="">
						<span class="crm-webform-edit-left-field-header-title-edit-container">
							<input class="crm-webform-edit-left-inner-field-title-field-header" data-bx-web-form-btn-caption="" name="CAPTION" type="text" placeholder="<?=Loc::getMessage('CRM_WEBFORM_EDIT_FORM_CAPTION_DEFAULT')?>" value="<?=htmlspecialcharsbx($arResult['FORM']['CAPTION'])?>">
						</span>
					</div>
				</div>
			</div><!--crm-webform-edit-left-field-header-container-->

			<div class="crm-webform-edit-left-field-description-container">
				<div id="DESCRIPTION_EDITOR_BUTTON" class="crm-webform-edit-left-field-description-inner <?=$arResult['FORM']['DESCRIPTION'] ? 'crm-webform-display-none' : ''?>">
					<span class="crm-webform-edit-left-field-description-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_FORM_DESCRIPTION_DEFAULT')?></span>
				</div><!--crm-webform-edit-left-field-description-container-->
				<div id="DESCRIPTION_EDITOR_CONTAINER" style="position: relative;" class="crm-webform-edit-animate <?=$arResult['FORM']['DESCRIPTION'] ? 'crm-webform-edit-animate-show' : ''?>">
					<div style="color: #bfbfbf; font-size: 17px; padding: 0 0; position: relative;">
				<?
				$editor = new CHTMLEditor;
				$res = array_merge(
					array(
						'height' => 100,
						'minBodyWidth' => 350,
						'normalBodyWidth' => 555,
						'bAllowPhp' => false,
						'limitPhpAccess' => false,
						'showTaskbars' => false,
						'showNodeNavi' => false,
						'askBeforeUnloadPage' => true,
						'bbCode' => true,
						'siteId' => SITE_ID,
						'autoResize' => true,
						'autoResizeOffset' => 40,
						'saveOnBlur' => true,
						'controlsMap' => array(
							array('id' => 'Bold',  'compact' => true, 'sort' => 80),
							array('id' => 'Italic',  'compact' => true, 'sort' => 90),
							array('id' => 'Underline',  'compact' => true, 'sort' => 100),
							array('id' => 'Strikeout',  'compact' => true, 'sort' => 110),
							array('id' => 'RemoveFormat',  'compact' => true, 'sort' => 120),
							array('id' => 'Color',  'compact' => true, 'sort' => 130),
							array('id' => 'FontSelector',  'compact' => false, 'sort' => 135),
							array('id' => 'FontSize',  'compact' => false, 'sort' => 140),
							array('separator' => true, 'compact' => false, 'sort' => 145),
							array('id' => 'OrderedList',  'compact' => true, 'sort' => 150),
							array('id' => 'UnorderedList',  'compact' => true, 'sort' => 160),
							array('id' => 'AlignList', 'compact' => false, 'sort' => 190),
							array('separator' => true, 'compact' => false, 'sort' => 200),
							array('id' => 'InsertLink',  'compact' => true, 'sort' => 210, 'wrap' => 'bx-b-link-'.$arParams["FORM_ID"]),
							//array('id' => 'InsertImage',  'compact' => false, 'sort' => 220),
							//array('id' => 'InsertVideo',  'compact' => true, 'sort' => 230, 'wrap' => 'bx-b-video-'.$arParams["FORM_ID"]),
							//array('id' => 'InsertTable',  'compact' => false, 'sort' => 250),
							//array('id' => 'Code',  'compact' => true, 'sort' => 260),
							array('id' => 'Quote',  'compact' => true, 'sort' => 270, 'wrap' => 'bx-b-quote-'.$arParams["FORM_ID"]),
							//array('id' => 'Smile',  'compact' => false, 'sort' => 280),
							array('separator' => true, 'compact' => false, 'sort' => 290),
							array('id' => 'Fullscreen',  'compact' => false, 'sort' => 310),
							//array('id' => 'BbCode',  'compact' => true, 'sort' => 340),
							array('id' => 'More',  'compact' => true, 'sort' => 400)
						)
					),
					(is_array($arParams["LHE"]) ? $arParams["LHE"] : array()),
					array(
						'name' => 'DESCRIPTION',
						'id' => 'DESCRIPTION',
						'width' => '100%',
						'arSmilesSet' => array(),
						'arSmiles' => array(),
						'content' => htmlspecialcharsBack($arResult['FORM']['DESCRIPTION']),
						'fontSize' => '14px',
						'iframeCss' =>
							'.bx-spoiler {border:1px solid #cecece;background-color:#f6f6f6;padding: 8px 8px 8px 24px;color:#373737;border-radius:var(--ui-border-radius-sm, 2px);min-height:1em;margin: 0;}',
					)
				);
				$editor->Show($res);

				?>
					</div>
				</div>
			</div><!--crm-webform-edit-left-field-header-container-->

			<div id="FIELD_CONTAINER" class="crm-webform-edit-left-field-container">
				<?
				$sort = 0;
				foreach($arResult['FORM']['FIELDS'] as $field):
					$sort++;
					$field['sort'] = $sort;
					$field['name'] = $field['CODE'];
					$field['CURRENCY_SHORT_NAME'] = $arResult['CURRENCY']['SHORT_NAME'];
					echo CrmWebFormEditTemplate::getField($field);
				endforeach;
				?>
			</div><!--crm-webform-edit-left-field-container-->

			<?if($arResult['PERM_CAN_EDIT']):?>
			<div class="crm-webform-edit-left-field-add-element">
				<span class="crm-webform-edit-left-field-add-element-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_FORM_ADD_HELP_TEXT')?></span>
			</div><!--crm-webform-edit-left-field-add-element-->
			<?endif;?>

			<div id="FORM_BUTTON_CONTAINER" class="crm-webform-edit-left-field-button-container">
				<div class="crm-webform-edit-left-field-button-wrapper">
					<div id="FORM_BUTTON" class="crm-webform-edit-left-field-button"><?=htmlspecialcharsbx($arResult['FORM']['BUTTON_CAPTION'])?></div>
					<div style="display: none;">
						<div id="CRM_WEB_FORM_POPUP_BUTTON_NAME">
							<label for="" class="crm-webform-edit-popup-label">
								<div class="crm-webform-edit-popup-name"><?=Loc::getMessage('CRM_WEBFORM_EDIT_BUTTON_CAPTION')?></div>
								<div class="crm-webform-edit-popup-inner-container">
									<input id="FORM_BUTTON_INPUT" name="BUTTON_CAPTION" type="text" value="<?=htmlspecialcharsbx($arResult['FORM']['BUTTON_CAPTION'])?>" placeholder="<?=Loc::getMessage('CRM_WEBFORM_EDIT_FORM_SUBMIT_BTN_CAPTION_DEFAULT')?>" class="crm-webform-edit-popup-input">
								</div>
								<div id="FORM_BUTTON_INPUT_ERROR" class="crm-webform-edit-popup-name-error"><?=Loc::getMessage('CRM_WEBFORM_EDIT_POPUP_BUTTON_ERROR')?></div>
							</label>
						</div>
					</div>
				</div>
				<div class="crm-webform-edit-left-field-colorpick">
					<span class="crm-webform-edit-left-field-colorpick-text-container">
						<input size="7" id="BUTTON_COLOR_BG" data-web-form-color-picker="" type="hidden" name="BUTTON_COLOR_BG" value="<?=htmlspecialcharsbx($arResult['FORM']['BUTTON_COLOR_BG'])?>">
						<span class="crm-webform-edit-left-field-colorpick-background-circle"></span>
						<span class="crm-web-form-color-picker crm-webform-edit-left-field-colorpick-background"><?=Loc::getMessage('CRM_WEBFORM_EDIT_BUTTON_COLOR_BG')?></span>
					</span>

					<span class="crm-webform-edit-left-field-colorpick-text-container">
						<input size="7" id="BUTTON_COLOR_FONT" data-web-form-color-picker="" type="hidden" name="BUTTON_COLOR_FONT" value="<?=htmlspecialcharsbx($arResult['FORM']['BUTTON_COLOR_FONT'])?>">
						<span class="crm-webform-edit-left-field-colorpick-text-circle"></span>
						<span class="crm-web-form-color-picker crm-webform-edit-left-field-colorpick-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_BUTTON_COLOR_FONT')?></span>
					</span>
				</div>
			</div>
		</div><!--crm-webform-edit-constructor-left-block-->

	</div><!--crm-webform-edit-field-constructor-container-->

	<?
	$userBlockController->start('COPYRIGHT', Loc::getMessage('CRM_WEBFORM_EDIT_COPYRIGHT_SECTION'), Loc::getMessage('CRM_WEBFORM_EDIT_COPYRIGHT_SECTION_NAV'));
	?>
	<label id="COPYRIGHT_REMOVED_CONT" for="COPYRIGHT_REMOVED" class="task-field-label">
		<input id="COPYRIGHT_REMOVED" name="COPYRIGHT_REMOVED" <?=($arResult['FORM']['COPYRIGHT_REMOVED'] == 'Y' ? 'checked' : '')?> value="Y" type="checkbox" class="crm-webform-edit-task-options-checkbox">
		<span class="crm-webform-edit-task-options-checkbox-item-container">
			<span class="crm-webform-edit-task-options-checkbox-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_COPYRIGHT_REMOVE')?></span>
			<span class="crm-webform-edit-task-options-checkbox-element <?=($arResult['CAN_REMOVE_COPYRIGHT'] ? '' : 'crm-webform-copyright-disabled')?>"><?=Loc::getMessage('CRM_WEBFORM_EDIT_COPYRIGHT_DEMO')?></span>
		</span>
	</label>
	<?
	$userBlockController->start('DEPENDENCIES', Loc::getMessage('CRM_WEBFORM_EDIT_DEP_SECTION'), Loc::getMessage('CRM_WEBFORM_EDIT_DEP_SECTION_NAV'));
	?>
	<div class="crm-webform-edit-task-options-item-open-settings">
				<div id="DEPENDENCY_CONTAINER" class="crm-webform-ext-block-dep-list">
					<?
					foreach($arResult['FORM']['DEPENDENCIES'] as $dependency):
						GetCrmWebFormFieldDependencyTemplate($dependency);
					endforeach;
					?>
				</div>

				<span id="DEPENDENCY_BUTTON_ADD" class="crm-webform-edit-task-options-rule">&#43; <?=Loc::getMessage('CRM_WEBFORM_EDIT_DEP_BUTTON_ADD')?></span>
	</div>
	<?
	$userBlockController->start('ENTITY_SCHEME', Loc::getMessage('CRM_WEBFORM_EDIT_DOC_SECTION'), Loc::getMessage('CRM_WEBFORM_EDIT_DOC_SECTION_NAV'));
	?>
	<div class="crm-webform-edit-task-options-item-open-settings">
		<div class="crm-webform-edit-task-options-settings-title-cotainer">
			<div class="crm-webform-edit-task-options-settings-title"><?=Loc::getMessage('CRM_WEBFORM_EDIT_DOC_TITLE')?>:</div>
		</div>

		<div id="ENTITY_SCHEME_CONTAINER" class="crm-webform-edit-task-options-settings-container">
			<div class="crm-webform-edit-task-options-document-settings-radio-container">
				<input type="hidden" name="ENTITY_SCHEME" value="<?=htmlspecialcharsbx($arResult['ENTITY_SCHEMES']['SELECTED_ID'])?>">
				<?foreach($arResult['ENTITY_SCHEMES']['BY_NON_INVOICE'] as $searchSchemeId => $entityScheme):?>
					<label for="ENTITY_SCHEME_<?=htmlspecialcharsbx($entityScheme['ID'])?>">
						<input type="radio" id="ENTITY_SCHEME_<?=htmlspecialcharsbx($entityScheme['ID'])?>"
							data-bx-web-form-entity-scheme-value=""
							name="ENTITY_SCHEME_SELECTOR"
							class="crm-webform-edit-task-options-document-settings-radio"
							<?=($entityScheme['SELECTED'] ? 'checked' : '')?>
							<?=($entityScheme['DISABLED'] ? 'disabled' : '')?>
							value="<?=htmlspecialcharsbx($searchSchemeId)?>"
						>
						<span class="crm-webform-edit-task-options-document-settings-radio-element">
							<?=htmlspecialcharsbx($entityScheme['NAME'])?>
						</span>
					</label>
				<?endforeach;?>
				<label for="ENTITY_SCHEMES_ADD_INVOICE">
					<input data-bx-web-form-entity-scheme-invoice="" id="ENTITY_SCHEMES_ADD_INVOICE" type="checkbox" <?=($arResult['ENTITY_SCHEMES']['HAS_INVOICE'] ? 'checked' : '')?> class="crm-webform-edit-task-options-document-settings-radio">
					<span class="crm-webform-edit-task-options-document-settings-radio-element"><?=Loc::getMessage('CRM_WEBFORM_EDIT_DOC_ADD_INVOICE1')?></span>
				</label>
			</div>
			<div class="crm-webform-edit-task-options-document-settings-description">
				<span class="crm-webform-edit-task-options-document-settings-description-element">
					<?=Loc::getMessage('CRM_WEBFORM_EDIT_DOC_LIST_TITLE')?>:
					<span data-bx-webform-edit-scheme-desc="">
						<?=htmlspecialcharsbx($arResult['ENTITY_SCHEMES']['SELECTED_DESCRIPTION'])?>
					</span>
				</span>
			</div>
			<div class="crm-webform-edit-task-options-document-duplicate">

				<div
					data-bx-web-form-entity-scheme-deal-cat=""
					style="<?=(count($arResult['DEAL_CATEGORY_LIST']) > 1 ? '' : 'display: none;')?>"
					class="crm-webform-edit-animate <?=($arResult['ENTITY_SCHEMES']['HAS_DEAL'] ? 'crm-webform-edit-animate-show-120' : '')?> crm-webform-edit-task-options-document-duplicate-control"
				>
					<div class="crm-webform-edit-task-options-document-duplicate-list-element"><?=Loc::getMessage('CRM_WEBFORM_EDIT_DEAL_CATEGORY_LIST')?>:</div>
					<div class="crm-webform-edit-task-options-document-duplicate-list-container">
						<select id="DEAL_CATEGORY" name="DEAL_CATEGORY" class="crm-webform-edit-task-options-rule-select">
							<?foreach($arResult['DEAL_CATEGORY_LIST'] as  $dealCategory):?>
								<option value="<?=htmlspecialcharsbx($dealCategory['ID'])?>" <?=($dealCategory['SELECTED'] ? 'selected' : '')?>>
									<?=htmlspecialcharsbx($dealCategory['NAME'])?>
								</option>
							<?endforeach;?>
						</select>
					</div>
				</div>

				<div
					data-bx-web-form-entity-scheme-dyn-cat=""
					class="crm-webform-edit-animate crm-webform-edit-task-options-document-duplicate-control"
				>
					<div class="crm-webform-edit-task-options-document-duplicate-list-element">
						<?=Loc::getMessage('CRM_WEBFORM_EDIT_DYNAMIC_CATEGORY_LIST')?>:
					</div>
					<div class="crm-webform-edit-task-options-document-duplicate-list-container">
						<select
							id="DYNAMIC_CATEGORY"
							name="DYNAMIC_CATEGORY"
							class="crm-webform-edit-task-options-rule-select"
						>
							<?if(!empty($arResult['FORM']['FORM_SETTINGS']['DYNAMIC_CATEGORY'])):?>
								<option value="<?=htmlspecialcharsbx($arResult['FORM']['FORM_SETTINGS']['DYNAMIC_CATEGORY'])?>"></option>
							<?endif?>
						</select>
					</div>
				</div>

				<div
					data-bx-web-form-entity-scheme-deal-dc=""
					class="crm-webform-edit-animate <?=($arResult['ENTITY_SCHEMES']['HAS_DEAL'] ? 'crm-webform-edit-animate-show-120' : '')?> crm-webform-edit-task-options-document-duplicate-control"
				>
					<div class="crm-webform-edit-task-options-document-duplicate-list-element">
						<div class="crm-webform-edit-task-options-item-open-settings">
							<input id="DEAL_DC_ENABLED" name="DEAL_DC_ENABLED"
								value="Y" type="checkbox"
								<?=(isset($arResult['FORM']['FORM_SETTINGS']['DEAL_DC_ENABLED']) && $arResult['FORM']['FORM_SETTINGS']['DEAL_DC_ENABLED'] == 'Y' ? 'checked' : '')?>
							>
							<label for="DEAL_DC_ENABLED"><?=Loc::getMessage('CRM_WEBFORM_EDIT_DEAL_DC_ENABLE')?></label>
						</div>
					</div>
				</div>

				<div class="crm-webform-edit-task-options-document-duplicate-control">
					<div class="crm-webform-edit-task-options-document-duplicate-list-element"><?=Loc::getMessage('CRM_WEBFORM_EDIT_DOC_DUPLICATES')?>:</div>
					<div class="crm-webform-edit-task-options-document-duplicate-list-container">
						<?foreach($arResult['DUPLICATE_MODES'] as  $duplicateMode):
							$duplicateModeNodeId = 'DUPLICATE_MODE_' . htmlspecialcharsbx($duplicateMode['ID']);
						?>
							<label for="<?=$duplicateModeNodeId?>" class="task-option-duplicate-label">
								<input id="<?=$duplicateModeNodeId?>" name="DUPLICATE_MODE" value="<?=htmlspecialcharsbx($duplicateMode['ID'])?>" <?=($duplicateMode['SELECTED'] ? 'checked' : '')?> type="radio" class="crm-webform-edit-task-options-document-duplicate-list-input">
								<span class="crm-webform-edit-task-options-document-duplicate-list-input-element">
									<?=htmlspecialcharsbx($duplicateMode['CAPTION'])?>
								</span>
							</label>
						<?endforeach;?>
					</div>
				</div>
			</div>
			<div data-bx-crm-webform-invoice="" class="crm-webform-edit-task-options-account-setup <?=(!$arResult['ENTITY_SCHEMES']['HAS_INVOICE'] ? 'crm-webform-display-none' : '')?>">
				<div class="crm-webform-edit-task-options-settings-title-container">
					<h4 class="crm-webform-edit-task-options-settings-title"><?=Loc::getMessage('CRM_WEBFORM_EDIT_DOC_INVOICE_TITLE1')?>:</h4>
				</div>
				<div class="crm-webform-edit-task-options-account-setup-container">

					<div data-bx-crm-webform-invoice-payer="">
						<div class="crm-webform-edit-task-options-account-setup-info">
							<span data-bx-crm-webform-invoice-payer-text-no="">
								<?=Loc::getMessage('CRM_WEBFORM_EDIT_DOC_INVOICE_ADD_PAYER1')?>
							</span>
							<br>
							<?=Loc::getMessage('CRM_WEBFORM_EDIT_DOC_INVOICE_CHOOSE_PAYER')?>:
						</div>
						<?foreach($arResult['INVOICE_PAYER_TYPES'] as $payerCode => $payer):?>
							<label for="INVOICE_SETTINGS_PAYER_<?=htmlspecialcharsbx($payer['ID'])?>" class="crm-webform-edit-task-options-account-setup-label">
								<input id="INVOICE_SETTINGS_PAYER_<?=htmlspecialcharsbx($payer['ID'])?>" name="INVOICE_SETTINGS[PAYER]" type="radio" <?=($payer['SELECTED'] ? 'checked' : '')?> value="<?=htmlspecialcharsbx($payer['ID'])?>" class="crm-webform-edit-task-options-account-setup-input">
								<span class="crm-webform-edit-task-options-account-setup-element">
									<?=Loc::getMessage('CRM_WEBFORM_EDIT_DOC_INVOICE_PAYER_' . $payer['ID'])?>
								</span>
							</label>
						<?endforeach;?>
					</div>

					<div data-bx-crm-webform-invoice-product="">
						<div class="crm-webform-edit-task-options-account-setup-info-description">
							<?=Loc::getMessage('CRM_WEBFORM_EDIT_DOC_INVOICE_PRODUCT_CHOICE1')?>
						</div>

						<div>
							<div data-bx-crm-webform-invoice-product-draw=""></div>
							<!--
							<label class="crm-webform-edit-task-options-account-setup-goods">
								<input disabled value="<?=Loc::getMessage('CRM_WEBFORM_EDIT_DOC_INVOICE_PRODUCT_CHOICE_NAME1')?>" class="crm-webform-edit-task-options-account-setup-goods-name">
								<input disabled value="<?=Loc::getMessage('CRM_WEBFORM_EDIT_DOC_INVOICE_PRODUCT_CHOICE_PRICE1')?>" class="crm-webform-edit-task-options-account-setup-goods-price">
							</label>
							-->
						</div>
						<span data-bx-crm-webform-invoice-product-btn="" class="crm-webform-edit-task-options-account-setup-new-goods-add">
							<?=Loc::getMessage('CRM_WEBFORM_EDIT_DOC_INVOICE_PRODUCT_CHOICE_BTN')?>
						</span>
					</div>
					<br><br>
					<div class="crm-webform-edit-task-options-item-open-settings">
						<input name="IS_PAY" value="Y" id="IS_PAY" type="checkbox" <?=($arResult['FORM']['IS_PAY'] == 'Y' ? 'checked' : '')?>>
						<label for="IS_PAY"><?=Loc::getMessage('CRM_WEBFORM_EDIT_IS_PAY_TITLE')?></label>
					</div>
					<div id="PAY_SYSTEM_CONT" class="<?=($arResult['FORM']['IS_PAY'] == 'Y' ? '' : 'crm-webform-display-none')?>">
						<?
						$APPLICATION->IncludeComponent(
							'bitrix:crm.config.ps.list',
							'block',
							array(),
							null,
							array('HIDE_ICONS'=>true, 'ACTIVE_COMPONENT'=>'Y')
						);
						?>
					</div>
				</div>
			</div><!--crm-webform-edit-task-options-account-setup-->
		</div><!--crm-webform-edit-task-options-document-settings-container-->
	</div>
	<?
	$userBlockController->start('PRESET_FIELDS', Loc::getMessage('CRM_WEBFORM_EDIT_PRESET_FIELDS_SECTION'), Loc::getMessage('CRM_WEBFORM_EDIT_PRESET_FIELDS_SECTION_NAV'));
	?>
		<label for="USE_PRESET_FIELDS" class="task-field-label task-field-label-repeat">
			<input id="USE_PRESET_FIELDS" name="USE_PRESET_FIELDS" value="Y" type="checkbox" <?=($arResult['FORM']['HAS_PRESET_FIELDS'] ? 'checked' : '')?> class="crm-webform-edit-task-options-checkbox">
			<span class="crm-webform-edit-task-options-checkbox-item-container">
				<span class="crm-webform-edit-task-options-checkbox-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_PRESET_FIELDS_TITLE')?></span>
			</span>
		</label>
		<div class="crm-webform-edit-task-options-item-open-settings">
		<div id="PRESET_FIELDS" class="crm-webform-edit-animate <?=($arResult['FORM']['HAS_PRESET_FIELDS'] ? 'crm-webform-edit-animate-show' : '')?>">
			<div id="PRESET_FIELD_CONTAINER">
				<?
				foreach($arResult['FORM']['PRESET_FIELDS'] as $field):
					GetCrmWebFormPresetFieldTemplate(array(
						'CODE' => $field['CODE'],
						'ENTITY_NAME' => $field['ENTITY_NAME'],
						'ENTITY_FIELD_NAME' => $field['ENTITY_FIELD_NAME'],
						'ENTITY_CAPTION' => $field['ENTITY_CAPTION'],
						'ENTITY_FIELD_CAPTION' => $field['ENTITY_FIELD_CAPTION'],
						'VALUE' => $field['VALUE']
					));
				endforeach;
				?>
			</div>

			<div class="crm-webform-edit-task-edit-add-deal-stage">
				<span class="crm-webform-edit-task-edit-add-deal-input-container">
					<select id="PRESET_FIELD_SELECTOR" class="crm-webform-edit-task-edit-add-deal-select">
						<?foreach($arResult['PRESET_AVAILABLE_FIELDS_TREE'] as $entityName => $entityFields):?>
							<optgroup data-bx-crm-wf-entity="<?=$entityName?>" label="<?=$entityFields['CAPTION']?>" id="PRESET_FIELDS_OPTGROUP_<?=$entityName?>">
								<?foreach($entityFields['FIELDS'] as $field):?>
									<option value="<?=htmlspecialcharsbx($field['name'])?>"><?=htmlspecialcharsbx($field['caption'])?></option>
								<?endforeach;?>
							</optgroup>
						<?endforeach;?>
					</select>
				</span>
				<span id="PRESET_FIELD_SELECTOR_BTN" class="crm-webform-edit-task-edit-add-deal-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_PRESET_FIELDS_ADD_BUTTON')?></span>
			</div><!--crm-webform-edit-task-edit-deal-stage-->
		</div>
		</div>
	<?
	$userBlockController->start('EXTERNAL_ANALYTICS', Loc::getMessage('CRM_WEBFORM_EDIT_EXTERNAL_ANALYTICS_SECTION'), Loc::getMessage('CRM_WEBFORM_EDIT_EXTERNAL_ANALYTICS_SECTION_NAV'));
	?>
	<div class="crm-webform-edit-task-options-item-open-settings">
		<div class="crm-webform-edit-task-options-settings-container">
			<div class="crm-webform-edit-task-options-metrics-container">
				<div id="CRM_WEBFORM_EXTERNAL_ANALYTICS" class="crm-webform-edit-task-options-metric">

					<?if($arResult['IS_RU_ZONE']):?>
					<div data-bx-crm-webform-ext-an="ya" class="<?=($arResult['FORM']['YANDEX_METRIC_ID'] ? 'crm-webform-edit-task-options-metric-exist' : '')?>">
						<div class="crm-webform-edit-task-options-metric-option">
							<span class="crm-webform-edit-task-options-metric-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_EXTERNAL_ANALYTICS_YA')?>:</span>
							<span class="crm-webform-edit-task-options-metric-id">
								<input name="YANDEX_METRIC_ID" value="<?=htmlspecialcharsbx($arResult['FORM']['YANDEX_METRIC_ID'])?>"
									data-bx-crm-webform-ext-an-val=""
									placeholder="39952427"
									class="crm-webform-edit-task-options-metric-id-inner"
								>
							</span>
							<span data-bx-crm-webform-ext-an-close="" class="crm-webform-edit-task-options-metric-close"></span>
							<span data-bx-crm-webform-ext-an-add="" class="crm-webform-edit-task-options-metrics-add"><?=Loc::getMessage('CRM_WEBFORM_EDIT_EXTERNAL_ANALYTICS_BUTTON_ADD')?></span>
						</div><!--crm-webform-edit-task-options-metric-option-->

						<div class="crm-webform-edit-task-options-metric-create">
							<div class="crm-webform-edit-task-options-metric-show-event-button">
								<span onclick="BX.toggleClass('GOOGLE_ANALYTICS_EVENT_DATA', 'crm-webform-display-none');">
									<?=Loc::getMessage('CRM_WEBFORM_EDIT_EXTERNAL_ANALYTICS_YA_SHOW_STAT')?>
								</span>
							</div>
							<table id="GOOGLE_ANALYTICS_EVENT_DATA" class="bx-interface-grid crm-webform-edit-metric crm-webform-display-none">
								<tbody>
								<tr class="crm-webform-edit-metric-head">
									<td class="crm-webform-edit-metric-col"><?=Loc::getMessage('CRM_WEBFORM_EDIT_EXTERNAL_ANALYTICS_YA_STAT_STEP')?></td>
									<td class="crm-webform-edit-metric-col"><?=Loc::getMessage('CRM_WEBFORM_EDIT_EXTERNAL_ANALYTICS_YA_STAT_GOAL_ID')?></td>
								</tr>
								<?foreach($arResult['EXTERNAL_ANALYTICS_DATA']['STEPS'] as $step):?>
									<tr class="crm-webform-edit-metric-even">
										<td><?=htmlspecialcharsbx($step['NAME'])?></td>
										<td><?=htmlspecialcharsbx($step['EVENT'] ? $step['EVENT'] : $step['CODE'])?></td>
									</tr>
								<?endforeach;?>
								</tbody>
							</table>
						</div><!--crm-webform-edit-task-options-metric-create-->
					</div>
					<?endif;?>

					<div data-bx-crm-webform-ext-an="ga" class="<?=($arResult['FORM']['GOOGLE_ANALYTICS_ID'] ? 'crm-webform-edit-task-options-metric-exist' : '')?>">
						<div class="crm-webform-edit-task-options-metric-option">
							<span class="crm-webform-edit-task-options-metric-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_EXTERNAL_ANALYTICS_GA')?>:</span>
							<span class="crm-webform-edit-task-options-metric-id">
								<input name="GOOGLE_ANALYTICS_ID" value="<?=htmlspecialcharsbx($arResult['FORM']['GOOGLE_ANALYTICS_ID'])?>"
									data-bx-crm-webform-ext-an-val=""
									placeholder="UA-39952427-1"
									class="crm-webform-edit-task-options-metric-id-inner"
								>
							</span>
							<span data-bx-crm-webform-ext-an-close="" class="crm-webform-edit-task-options-metric-close"></span>
							<span data-bx-crm-webform-ext-an-add="" class="crm-webform-edit-task-options-metrics-add"><?=Loc::getMessage('CRM_WEBFORM_EDIT_EXTERNAL_ANALYTICS_BUTTON_ADD')?></span>
						</div>
						<div class="crm-webform-edit-task-options-metric-create">
							<h5 class="crm-webform-edit-task-options-metric-create-title">
								<input type="checkbox" name="GOOGLE_ANALYTICS_PAGE_VIEW" id="GOOGLE_ANALYTICS_PAGE_VIEW" value="Y"
									<?=($arResult['FORM']['GOOGLE_ANALYTICS_PAGE_VIEW'] == 'Y' ? 'checked' : '')?>
									onclick="BX.toggleClass('GOOGLE_ANALYTICS_PAGE_VIEW_DATA', 'crm-webform-display-none');"
								>
								<label for="GOOGLE_ANALYTICS_PAGE_VIEW"><?=Loc::getMessage('CRM_WEBFORM_EDIT_EXTERNAL_ANALYTICS_GA_STAT')?></label>
							</h5>
							<table id="GOOGLE_ANALYTICS_PAGE_VIEW_DATA" class="bx-interface-grid crm-webform-edit-metric <?=($arResult['FORM']['GOOGLE_ANALYTICS_PAGE_VIEW'] == 'Y' ? '' : 'crm-webform-display-none')?>">
								<tbody>
								<tr class="crm-webform-edit-metric-head">
									<td class="crm-webform-edit-metric-col"><?=Loc::getMessage('CRM_WEBFORM_EDIT_EXTERNAL_ANALYTICS_GA_STAT_EVENT')?></td>
									<td class="crm-webform-edit-metric-col"><?=Loc::getMessage('CRM_WEBFORM_EDIT_EXTERNAL_ANALYTICS_GA_STAT_PAGE')?></td>
								</tr>
								<?foreach($arResult['EXTERNAL_ANALYTICS_DATA']['STEPS'] as $step):?>
									<tr class="crm-webform-edit-metric-even">
										<td><?=htmlspecialcharsbx($step['NAME'])?></td>
										<td><?=htmlspecialcharsbx($step['CODE'])?></td>
									</tr>
								<?endforeach;?>
								</tbody>
							</table>
						</div><!--crm-webform-edit-task-options-metric-create-->
					</div><!--crm-webform-edit-task-options-metric-option-->

					<?if(!$arResult['IS_UA_ZONE_RU_LANG']):?>
					<div class="crm-webform-edit-task-options-document-settings-description">
						<span class="crm-webform-edit-task-options-document-settings-description-element">
							<?=Loc::getMessage('CRM_WEBFORM_EDIT_EXTERNAL_ANALYTICS_AUTO_EVENTS')?>
						</span>
					</div>
					<?endif;?>
				</div><!--crm-webform-edit-task-options-metric-->
			</div>
		</div><!--crm-webform-edit-task-options-settings-container-->
	</div>
	<?
	$userBlockController->start('THEME', Loc::getMessage('CRM_WEBFORM_EDIT_THEME_SECTION'), Loc::getMessage('CRM_WEBFORM_EDIT_THEME_SECTION_NAV'));
	?>
	<div class="crm-webform-edit-task-options-item-open-settings">
		<div class="crm-webform-edit-task-options-item-open-inner">
			<div class="crm-webform-edit-task-options-settings-title"><?=Loc::getMessage('CRM_WEBFORM_EDIT_THEME_TITLE')?>:</div>
		</div><!--crm-webform-edit-task-options-item-open-inner-->
		<div class="crm-webform-edit-form-type-container">
			<?foreach($arResult['TEMPLATES'] as $template):
				$templateId = htmlspecialcharsbx($template['ID']);
				?>
				<span class="crm-webform-edit-task-options-form-type">
					<label for="TEMPLATE_ID_<?=$templateId?>" class="crm-webform-edit-task-options-form-type-label">
						<input id="TEMPLATE_ID_<?=$templateId?>" type="radio" name="TEMPLATE_ID"
							value="<?=$templateId?>" class="crm-webform-edit-task-options-form-type-radio"
							<?=($template['SELECTED'] ? 'checked' : '')?>
						>
						<span class="crm-webform-edit-task-options-form-type-item crm-webform-edit-task-options-form-type-<?=$templateId?>">
							<?=htmlspecialcharsbx($template['CAPTION'])?>
						</span>
					</label>
				</span>
			<?endforeach;?>

			<div class="crm-webform-edit-task-edit-sent-options-redirect-container">
				<div class="crm-webform-edit-task-edit-sent-redirect-checkbox-container">
					<input id="NO_BORDERS" name="NO_BORDERS" value="Y" type="checkbox" <?=(isset($arResult['FORM']['FORM_SETTINGS']['NO_BORDERS']) && $arResult['FORM']['FORM_SETTINGS']['NO_BORDERS'] == 'Y' ? 'checked' : '')?> class="crm-webform-edit-task-edit-sent-redirect-checkbox">
					<label for="NO_BORDERS" class="task-field-label task-field-label-repeat">
						<div class="task-sent-redirect-checkbox-item-container">
							<span class="task-sent-redirect-checkbox-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_THEME_NO_BORDERS')?></span>
						</div>
					</label>
				</div>
			</div>

			<div class="crm-webform-edit-task-options-form-type-upload">
				<span class="crm-webform-edit-form-type-upload-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_THEME_UPLOAD_TITLE')?></span>
				<div class="crm-webform-edit-form-type-upload-inner">
					<button class="crm-webform-edit-form-type-upload-button"><?=Loc::getMessage('CRM_WEBFORM_EDIT_THEME_UPLOAD_LOAD')?></button>
					<?=CFile::InputFile('BACKGROUND_IMAGE', '', 0)?>
					<span id="BACKGROUND_IMAGE_TEXT" style="display: none;"><?=Loc::getMessage('CRM_WEBFORM_EDIT_THEME_UPLOAD_NO')?></span>
				</div>
				<?if($arResult['FORM']['BACKGROUND_IMAGE']):?>
				<label for="BACKGROUND_IMAGE_del" class="crm-webform-edit-form-type-upload-checkbox-container">
					<input id="BACKGROUND_IMAGE_del"  name="BACKGROUND_IMAGE_del" value="Y" type="checkbox" class="crm-webform-edit-form-type-upload-checkbox">
					<span class="crm-webform-edit-form-type-upload-checkbox-element"><?=Loc::getMessage('CRM_WEBFORM_EDIT_THEME_UPLOAD_DEL')?></span>
				</label>
				<div class="crm-webform-edit-form-type-upload-image-container">
					<img src="<?=htmlspecialcharsbx($arResult['FORM']['BACKGROUND_IMAGE_PATH'])?>" alt="" class="crm-webform-edit-form-type-upload-image">
				</div>
				<?endif;?>
			</div><!--crm-webform-edit-task-options-form-type-upload-->
		</div><!--crm-webform-edit-form-type-container-->
	</div>
	<?
	$userBlockController->start('CSS', Loc::getMessage('CRM_WEBFORM_EDIT_CSS_SECTION'), Loc::getMessage('CRM_WEBFORM_EDIT_CSS_SECTION_NAV'));
	$arResult['FORM']['USE_CSS_TEXT'] = ($arResult['FORM']['CSS_PATH'] || $arResult['FORM']['CSS_TEXT']);
	?>
	<div class="crm-webform-edit-task-options-stylesheet">
		<label for="USE_CSS_TEXT" class="crm-webform-edit-task-options-stylesheet-label">
			<input id="USE_CSS_TEXT" name="USE_CSS_TEXT" value="Y" type="checkbox" <?=($arResult['FORM']['USE_CSS_TEXT'] ? 'checked' : '')?> class="crm-webform-edit-task-options-stylesheet-checkbox">
			<span class="crm-webform-edit-task-options-stylesheet-element"><?=Loc::getMessage('CRM_WEBFORM_EDIT_CSS_TITLE')?></span>
		</label>
	</div><!--crm-webform-edit-task-options-payment-->

	<div id="CSS_TEXT_CONTAINER" class="crm-webform-edit-task-options-item-open-settings <?=($arResult['FORM']['USE_CSS_TEXT'] ? '' : 'crm-webform-display-none')?>">
		<div class="crm-webform-edit-task-edit-sent-options-redirect-container">
			<div class="crm-webform-edit-task-edit-sent-redirect-checkbox-container">
				<div class="task-sent-redirect-checkbox-item-container">
					<span class="task-sent-redirect-checkbox-item crm-webform-edit-task-fixed-width"><?=Loc::getMessage('CRM_WEBFORM_EDIT_CSS_URL')?>:</span>
				</div>
			</div>
			<div class="crm-webform-edit-task-edit-deal-sent-redirect-input-container">
				<input type="text" placeholder="https://" name="CSS_PATH" value="<?=htmlspecialcharsbx($arResult['FORM']['CSS_PATH'])?>" class="crm-webform-edit-task-edit-deal-sent-redirect-input">
			</div>
		</div>

		<div class="crm-webform-edit-task-edit-deal-license-input-container">
			<div class="task-sent-css-checkbox-item-container">
				<span class="task-sent-redirect-checkbox-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_CSS_TEXT')?>:</span>
			</div>
			<div class="task-sent-redirect-checkbox-item-container">
			<textarea name="CSS_TEXT" class="crm-webform-edit-task-edit-deal-license-input"><?=htmlspecialcharsbx($arResult['FORM']['CSS_TEXT'])?></textarea>
			</div>
		</div>
	</div>
	<?
	$userBlockController->start('LICENCE', Loc::getMessage('CRM_WEBFORM_EDIT_LICENCE_SECTION'), Loc::getMessage('CRM_WEBFORM_EDIT_LICENCE_SECTION_NAV'));
	?>
		<label for="USE_LICENCE" class="task-field-label">
			<input id="USE_LICENCE" name="USE_LICENCE" value="Y" type="checkbox" <?=($arResult['FORM']['USE_LICENCE'] == 'Y' ? 'checked' : '')?> class="crm-webform-edit-task-options-checkbox">
			<span class="crm-webform-edit-task-options-checkbox-item-container">
				<span class="crm-webform-edit-task-options-checkbox-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_LICENCE_TITLE')?></span>
			</span>
		</label>
		<div id="LICENCE_CONTAINER" class="crm-webform-edit-userconsent-container <?=($arResult['FORM']['USE_LICENCE'] == 'Y' ? '' : 'crm-webform-display-none')?>">

			<?$APPLICATION->IncludeComponent(
				"bitrix:intranet.userconsent.selector",
				"",
				array(
					'ID' => $arResult['FORM']['AGREEMENT_ID'],
					'INPUT_NAME' => 'AGREEMENT_ID'
				)
			);?>

			<div>
				<div class="crm-webform-edit-license-border"></div>

				<label for="LICENCE_BUTTON_IS_CHECKED" class="task-field-label">
					<input id="LICENCE_BUTTON_IS_CHECKED" name="LICENCE_BUTTON_IS_CHECKED" value="Y" type="checkbox" <?=($arResult['FORM']['LICENCE_BUTTON_IS_CHECKED'] == 'N' ? '' : 'checked')?> class="crm-webform-edit-task-options-checkbox">
					<span class="crm-webform-edit-task-options-checkbox-item-container">
							<span class="crm-webform-edit-task-options-checkbox-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_LICENCE_BUTTON_IS_CHECKED')?></span>
						</span>
				</label>
			</div>
		</div>

	<?
	$userBlockController->start('RESULT_ACTIONS', Loc::getMessage('CRM_WEBFORM_EDIT_RESULT_ACTIONS_SECTION'), Loc::getMessage('CRM_WEBFORM_EDIT_RESULT_ACTIONS_SECTION_NAV'));
	?>
	<div class="crm-webform-edit-task-options-item-open-settings">
		<div class="crm-webform-edit-task-edit-deal-sent-options">
			<input id="USE_RESULT_SUCCESS_TEXT" name="USE_RESULT_SUCCESS_TEXT" value="Y" type="checkbox" <?=($arResult['FORM']['RESULT_SUCCESS_TEXT'] ? 'checked' : '')?> class="crm-webform-edit-task-edit-sent-options-checkbox">
			<label for="USE_RESULT_SUCCESS_TEXT" class="task-field-label task-field-label-repeat">
				<span class="task-sent-options-checkbox-item-container">
					<span class="task-sent-options-checkbox-item">
						<?=Loc::getMessage(
							'CRM_WEBFORM_EDIT_RESULT_ACTIONS_SUCC_TEXT',
							array(
								'%COLOR_START%' => '<span class="sent-options-success">',
								'%COLOR_END%' => '</span>'
							)
						)?>
					</span>
				</span>
			</label>
			<div id="RESULT_SUCCESS_TEXT_CONT" class="crm-webform-edit-task-edit-deal-sent-options-input-container <?=(($arResult['FORM']['RESULT_SUCCESS_TEXT']) ? '' : 'crm-webform-display-none')?>">
				<input name="RESULT_SUCCESS_TEXT" value="<?=htmlspecialcharsbx($arResult['FORM']['RESULT_SUCCESS_TEXT'])?>" type="text" placeholder="" class="crm-webform-edit-task-edit-deal-sent-options-input">
			</div>
			<div class="crm-webform-edit-task-edit-sent-options-redirect-container">
				<div class="crm-webform-edit-task-edit-sent-redirect-checkbox-container">
					<input id="USE_RESULT_SUCCESS_URL" name="USE_RESULT_SUCCESS_URL" value="Y"  type="checkbox" <?=($arResult['FORM']['RESULT_SUCCESS_URL'] ? 'checked' : '')?> class="crm-webform-edit-task-edit-sent-redirect-checkbox">
					<label for="USE_RESULT_SUCCESS_URL" class="task-field-label task-field-label-repeat">
						<div class="task-sent-redirect-checkbox-item-container">
							<span class="task-sent-redirect-checkbox-item crm-webform-edit-task-fixed-width"><?=Loc::getMessage('CRM_WEBFORM_EDIT_RESULT_ACTIONS_SUCC_URL')?>:</span>
						</div>
					</label>
				</div>
				<div id="RESULT_SUCCESS_URL_CONT" class="crm-webform-edit-task-edit-deal-sent-redirect-input-container <?=(($arResult['FORM']['RESULT_SUCCESS_URL']) ? '' : 'crm-webform-display-none')?>">
					<input name="RESULT_SUCCESS_URL" value="<?=htmlspecialcharsbx($arResult['FORM']['RESULT_SUCCESS_URL'])?>" type="text" placeholder="http://" class="crm-webform-edit-task-edit-deal-sent-redirect-input">
				</div>
			</div>
		</div>
		<div class="crm-webform-edit-task-edit-deal-inner-border"></div>
		<div class="crm-webform-edit-task-edit-deal-sent-options">
			<input id="USE_RESULT_FAILURE_TEXT" name="USE_RESULT_FAILURE_TEXT" value="Y" type="checkbox" <?=($arResult['FORM']['RESULT_FAILURE_TEXT'] ? 'checked' : '')?> class="crm-webform-edit-task-edit-sent-options-checkbox">
			<label for="USE_RESULT_FAILURE_TEXT" class="task-field-label task-field-label-repeat">
				<span class="task-sent-options-checkbox-item-container">
					<span class="task-sent-options-checkbox-item">
						<?=Loc::getMessage(
							'CRM_WEBFORM_EDIT_RESULT_ACTIONS_ERR_TEXT',
							array(
								'%COLOR_START%' => '<span class="sent-options-alert">',
								'%COLOR_END%' => '</span>'
							)
						)?>
					</span>
				</span>
			</label>
			<div id="RESULT_FAILURE_TEXT_CONT" class="crm-webform-edit-task-edit-deal-sent-options-input-container <?=(($arResult['FORM']['RESULT_FAILURE_TEXT']) ? '' : 'crm-webform-display-none')?>">
				<input name="RESULT_FAILURE_TEXT" value="<?=htmlspecialcharsbx($arResult['FORM']['RESULT_FAILURE_TEXT'])?>" type="text" placeholder="" class="crm-webform-edit-task-edit-deal-sent-options-input">
			</div>
			<div class="crm-webform-edit-task-edit-sent-options-redirect-container">
				<div class="crm-webform-edit-task-edit-sent-redirect-checkbox-container">
					<input id="USE_RESULT_FAILURE_URL" name="USE_RESULT_FAILURE_URL" value="Y" type="checkbox" <?=($arResult['FORM']['RESULT_FAILURE_URL'] ? 'checked' : '')?> class="crm-webform-edit-task-edit-sent-redirect-checkbox">
					<label for="USE_RESULT_FAILURE_URL" class="task-field-label task-field-label-repeat">
						<div class="task-sent-redirect-checkbox-item-container">
							<span class="task-sent-redirect-checkbox-item crm-webform-edit-task-fixed-width"><?=Loc::getMessage('CRM_WEBFORM_EDIT_RESULT_ACTIONS_ERR_URL')?>:</span>
						</div>
					</label>
				</div>
				<div  id="RESULT_FAILURE_URL_CONT" class="crm-webform-edit-task-edit-deal-sent-redirect-input-container <?=(($arResult['FORM']['RESULT_FAILURE_URL']) ? '' : 'crm-webform-display-none')?>">
					<input name="RESULT_FAILURE_URL" value="<?=htmlspecialcharsbx($arResult['FORM']['RESULT_FAILURE_URL'])?>" type="text" placeholder="http://" class="crm-webform-edit-task-edit-deal-sent-redirect-input">
				</div>
			</div>
		</div><!--crm-webform-edit-task-options-item-open-inner-->
		<div class="crm-webform-edit-task-edit-deal-inner-border"></div>
		<div id="RESULT_REDIRECT_DELAY_CONTAINER" class="crm-webform-edit-task-edit-deal-sent-options">
			<label for="RESULT_REDIRECT_DELAY" class="task-field-label task-field-label-repeat">
				<span class="task-sent-options-checkbox-item-container">
					<span class="task-sent-options-checkbox-item">
						<?=Loc::getMessage('CRM_WEBFORM_EDIT_RESULT_ACTIONS_DELAY')?>:
					</span>
				</span>
			</label>
			<select id="RESULT_REDIRECT_DELAY" name="RESULT_REDIRECT_DELAY" class="crm-webform-edit-task-options-rule-select" style="max-width: 100px;">
				<?foreach($arResult['RESULT_REDIRECT_DELAY_LIST'] as $delay):?>
				<option value="<?=htmlspecialcharsbx($delay['VALUE'])?>" <?=($delay['SELECTED'] ? 'selected' : '')?>>
					<?=htmlspecialcharsbx($delay['NAME'])?>
				</option>
				<?endforeach;?>
			</select>
		</div><!--crm-webform-edit-task-options-item-open-inner-->
	</div>
	<?
	$userBlockController->start('SECURITY', Loc::getMessage('CRM_WEBFORM_EDIT_SECURITY_SECTION'), Loc::getMessage('CRM_WEBFORM_EDIT_SECURITY_SECTION_NAV'));
	?>
	<div class="crm-webform-edit-task-options-stylesheet">
		<label for="USE_CAPTCHA" class="crm-webform-edit-task-options-stylesheet-label">
			<input id="USE_CAPTCHA" name="USE_CAPTCHA" value="Y" type="checkbox" <?=($arResult['FORM']['USE_CAPTCHA'] == 'Y' ? 'checked' : '')?> class="crm-webform-edit-task-options-stylesheet-checkbox">
			<span class="crm-webform-edit-task-options-stylesheet-element"><?=Loc::getMessage('CRM_WEBFORM_EDIT_SECURITY_CAPTCHA_USE')?></span>
		</label>
	</div><!--crm-webform-edit-task-options-payment-->

	<div class="crm-webform-edit-task-options-metric-create">
		<div class="crm-webform-edit-task-options-metric-show-event-button <?=($arResult['CAPTCHA']['HAS_DEFAULT_KEY'] ? '' : 'crm-webform-display-none')?>">
			<span onclick="BX.toggleClass('RECAPTCHA_CUSTOM_CONTAINER', 'crm-webform-display-none');">
				<?=Loc::getMessage('CRM_WEBFORM_EDIT_CAPTCHA_USE_OWN_KEY')?>
			</span>
		</div>
		<div id="RECAPTCHA_CUSTOM_CONTAINER" class="crm-webform-edit-task-options-item-open-settings <?=(($arResult['CAPTCHA']['HAS_OWN_KEY'] || !$arResult['CAPTCHA']['HAS_DEFAULT_KEY']) ? '' : 'crm-webform-display-none')?>">
			<div class="crm-webform-edit-task-edit-sent-options-redirect-container">
				<div class="crm-webform-edit-task-edit-sent-redirect-checkbox-container">
					<div class="task-sent-redirect-checkbox-item-container">
						<span class="task-sent-redirect-checkbox-item crm-webform-edit-task-fixed-width"><?=Loc::getMessage('CRM_WEBFORM_EDIT_CAPTCHA_KEY')?>:</span>
					</div>
				</div>
				<div class="crm-webform-edit-task-edit-deal-sent-redirect-input-container">
					<input type="text" name="CAPTCHA_KEY" value="<?=htmlspecialcharsbx($arResult['CAPTCHA']['KEY'])?>" class="crm-webform-edit-task-edit-deal-sent-redirect-input">
				</div>
			</div>
			<div class="crm-webform-edit-task-edit-sent-options-redirect-container">
				<div class="crm-webform-edit-task-edit-sent-redirect-checkbox-container">
					<div class="task-sent-redirect-checkbox-item-container">
						<span class="task-sent-redirect-checkbox-item crm-webform-edit-task-fixed-width"><?=Loc::getMessage('CRM_WEBFORM_EDIT_CAPTCHA_SECRET')?>:</span>
					</div>
				</div>
				<div class="crm-webform-edit-task-edit-deal-sent-redirect-input-container">
					<input type="text" name="CAPTCHA_SECRET" value="<?=htmlspecialcharsbx($arResult['CAPTCHA']['SECRET'])?>" class="crm-webform-edit-task-edit-deal-sent-redirect-input">
				</div>
			</div>

			<a target="_blank" href="https://www.google.com/recaptcha/"><?=Loc::getMessage('CRM_WEBFORM_EDIT_CAPTCHA_HOW_TO_GET')?></a>
		</div>
	</div><!--crm-webform-edit-task-options-metric-create-->
	<?
	if (count($arResult['ADS_FORM']) > 0):
	$userBlockController->start('ADS', Loc::getMessage('CRM_WEBFORM_EDIT_ADS_SECTION'), Loc::getMessage('CRM_WEBFORM_EDIT_ADS_SECTION_NAV'));
	?>
	<div id="CRM_WEBFORM_ADS_FORM">
		<?foreach ($arResult['ADS_FORM'] as $adsForm):?>
			<div style="margin: 0 0 10px 0;">
				<a href="<?=htmlspecialcharsbx($adsForm['PATH_TO_ADS'])?>"
					data-bx-ads-button="<?=htmlspecialcharsbx($adsForm['TYPE'])?>"
					class="ui-btn <?=($adsForm['HAS_LINKS'] ? 'ui-btn-secondary' : 'ui-btn-light-border')?>"
				>
					<?=htmlspecialcharsbx($adsForm['NAME'])?>
				</a>
				<?=($adsForm['HAS_LINKS'] ? Loc::getMessage('CRM_WEBFORM_EDIT_ADS_LINKED') : '')?>
			</div>
		<?endforeach;?>
	</div><!--crm-webform-edit-task-options-metric-create-->
	<?
	endif;

	if($arResult['CALL_BACK_FORM']['CAN_USE']):
	$userBlockController->start('CALL_BACK_FORM', Loc::getMessage('CRM_WEBFORM_EDIT_CALL_BACK_FORM_SECTION'), Loc::getMessage('CRM_WEBFORM_EDIT_CALL_BACK_FORM_SECTION_NAV'));
	?>
		<?if(count($arResult['CALL_BACK_FORM']['CALL_FROM']) > 0):?>
		<div class="crm-webform-edit-task-options-stylesheet">
			<label for="IS_CALLBACK_FORM" class="crm-webform-edit-task-options-stylesheet-label">
				<input id="IS_CALLBACK_FORM" name="IS_CALLBACK_FORM" value="Y" type="checkbox" <?=($arResult['FORM']['IS_CALLBACK_FORM'] == 'Y' ? 'checked' : '')?> class="crm-webform-edit-task-options-stylesheet-checkbox">
				<span class="crm-webform-edit-task-options-stylesheet-element"><?=Loc::getMessage('CRM_WEBFORM_EDIT_CALL_BACK_FORM_TITLE')?></span>
			</label>
		</div><!--crm-webform-edit-task-options-payment-->

		<div id="CALLBACK_CONTAINER" class="crm-webform-edit-task-options-item-open-settings <?=($arResult['FORM']['IS_CALLBACK_FORM'] == 'Y' ? '' : 'crm-webform-display-none')?>">
			<div class="crm-webform-edit-task-edit-sent-options-redirect-container">
				<div class="crm-webform-edit-task-edit-sent-redirect-checkbox-container">
					<div class="task-sent-redirect-checkbox-item-container">
						<span class="task-sent-redirect-checkbox-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_CALL_BACK_FORM_CALL_FROM')?>:</span>
					</div>
				</div>
				<div class="crm-webform-edit-task-edit-deal-sent-redirect-input-container">
					<select name="CALL_FROM" class="crm-webform-edit-task-options-rule-select">
						<option value=""><?=Loc::getMessage('CRM_WEBFORM_EDIT_CALL_BACK_FORM_CALL_FROM_SELECT')?></option>
						<?foreach($arResult['CALL_BACK_FORM']['CALL_FROM'] as $callFrom):?>
							<option value="<?=htmlspecialcharsbx($callFrom['ID'])?>" <?=($callFrom['SELECTED'] ? 'selected' : '')?>>
								<?=htmlspecialcharsbx($callFrom['VALUE'])?>
							</option>
						<?endforeach?>
					</select>
				</div>
			</div>

			<div class="crm-webform-edit-task-edit-deal-license-input-container">
				<div class="task-sent-css-checkbox-item-container">
					<span class="task-sent-redirect-checkbox-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_CALL_BACK_FORM_CALL_TEXT')?>:</span>
				</div>
				<div class="task-sent-redirect-checkbox-item-container">
					<textarea name="CALL_TEXT" class="crm-webform-edit-task-edit-deal-license-input"><?=htmlspecialcharsbx($arResult['FORM']['CALL_TEXT'])?></textarea>
				</div>
			</div>
		</div>
		<?else:?>
			<?=Loc::getMessage('CRM_WEBFORM_EDIT_CALL_BACK_FORM_NO_NUMBERS')?>
		<?endif;?>
	<?
	endif;
	if($arResult['FORM']['ID']):
	$userBlockController->start('SCRIPTS', Loc::getMessage('CRM_WEBFORM_EDIT_SCRIPTS_SECTION'), Loc::getMessage('CRM_WEBFORM_EDIT_SCRIPTS_SECTION_NAV'));
	?>
	<div class="crm-webform-edit-task-options-item-open-settings">
		<?
		$APPLICATION->IncludeComponent(
			'bitrix:crm.webform.script',
			'',
			array(
				'FORM' => $arResult['FORM'],
				'IS_AVAILABLE_EMBEDDING' => $arResult['IS_AVAILABLE_EMBEDDING'],
				'PATH_TO_WEB_FORM_FILL' => $arParams['PATH_TO_WEB_FORM_FILL']
			),
			null,
			array('HIDE_ICONS'=>true, 'ACTIVE_COMPONENT'=>'Y')
		);
		?>
	</div>
	<?
	endif;
	$userBlockController->end();
	?>

	<div class="task-additional-block" id="CRM_WEBFORM_ADDITIONAL_OPTIONS">

		<div id="FIXED_OPTION_PLACE" class="crm-webform-edit-task-options task-openable-block">

			<?if($arResult['IS_AVAILABLE_EMBEDDING_PORTAL']):?>
			<div class="crm-webform-edit-task-options-item-destination-wrap">
				<div class="crm-webform-edit-task-options-item crm-webform-edit-task-options-item-destination">
					<span class="crm-webform-edit-task-options-item-param"><?=Loc::getMessage('CRM_WEBFORM_EDIT_LANGUAGE')?>:</span>
					<div class="crm-webform-edit-task-options-item-open-inner">
						<select name="LANGUAGE_ID" class="crm-webform-edit-task-options-rule-select">
							<?foreach($arResult['LANGUAGES'] as  $languageId => $language):?>
								<option value="<?=htmlspecialcharsbx($languageId)?>" <?=($language['SELECTED'] ? 'selected' : '')?>>
									<?=htmlspecialcharsbx($language['NAME'])?>
								</option>
							<?endforeach;?>
						</select>
					</div>
				</div>
			</div>
			<?endif;?>

			<div id="CRM_WEBFORM_RESPONSIBLE" class="crm-webform-edit-task-options-item-destination-wrap">

				<div class="crm-webform-edit-task-options-item crm-webform-edit-task-options-item-destination">
					<span class="crm-webform-edit-task-options-item-param"><?=Loc::getMessage('CRM_WEBFORM_EDIT_ASSIGNED_BY')?>:</span>
					<div class="crm-webform-edit-task-options-item-open-inner">
						<div id="crm-webform-edit-responsible" data-config="<?= htmlspecialcharsbx(Json::encode($arResult['CONFIG_ASSIGNED_BY'])) ?>"></div>

						<?if($arResult['ASSIGNED_BY']['IS_SUPPORTED_WORK_TIME']):?>
						<div style="margin: 15px 0 0 0;">
							<label for="ASSIGNED_WORK_TIME" class="crm-webform-edit-task-options-stylesheet-label">
								<input id="ASSIGNED_WORK_TIME" name="ASSIGNED_WORK_TIME" value="Y" type="checkbox" <?=($arResult['ASSIGNED_BY']['WORK_TIME'] ? 'checked' : '')?> class="crm-webform-edit-task-options-stylesheet-checkbox">
								<span class="crm-webform-edit-task-options-stylesheet-element"><?=Loc::getMessage('CRM_WEBFORM_EDIT_ASSIGNED_WORK_TIME')?></span>
							</label>
						</div>
						<?endif;?>
					</div>
				</div>

			</div>
			<?
			$userBlockController->showFixed();
			?>
		</div>

		<div class="task-additional-alt" id="ADDITIONAL_OPTION_BUTTON">
			<div class="task-additional-alt-more"><?=Loc::getMessage('CRM_WEBFORM_EDIT_NAV_TITLE')?></div>
			<div class="task-additional-alt-promo">
				<?
				$userBlockController->showNavigation();
				?>
			</div>
		</div><!--task-additional-alt-->


		<div id="ADDITIONAL_OPTION_CONTAINER" class="crm-webform-edit-task-options crm-webform-edit-task-options-more task-openable-block">
			<?
			$userBlockController->show();
			?>
		</div>
	</div>

	<?if($arResult['FORM']['HAS_ADS_FORM_LINKS'] == 'Y'):?>
		<div class="crm-webform-edit-system-warning-cont">
			<span class="crm-webform-edit-system-warning-text">
				<?=Loc::getMessage('CRM_WEBFORM_EDIT_SYSTEM_CANT_COPY_ADS')?>
			</span>
		</div>
	<?endif;?>

	<?$APPLICATION->IncludeComponent("bitrix:ui.button.panel", "", [
		'BUTTONS' => $arResult['PERM_CAN_EDIT']
			?
			$arResult['FORM']['IS_READONLY'] === 'Y'
				?
				[
					[
						'TYPE' => 'save',
						'ID' => 'CRM_WEBFORM_COPY_BUTTON',
						'CAPTION' => Loc::getMessage('CRM_WEBFORM_EDIT_COPY')
					],
					'cancel' => $arResult['PATH_TO_WEB_FORM_LIST']
				]
				:
				[
					[
						'ID' => 'CRM_WEBFORM_SUBMIT_APPLY',
						'TYPE' => 'save'
					],
					'cancel' => $arResult['PATH_TO_WEB_FORM_LIST']
				]
			:
			['close' => $arResult['PATH_TO_WEB_FORM_LIST']]
	]);?>
</div>

</form>

<script id="crm-webform-list-template-ads-popup" type="text/html">
	<div>
		<div data-bx-ads-content="" id="crm-webform-list-ads-popup"></div>
		<div data-bx-ads-loader="" style="display: none;" class="crm-webform-list-ads-popup">
			<div class="crm-circle-loader-item">
				<div class="crm-circle-loader">
					<svg class="crm-circle-loader-circular" viewBox="25 25 50 50">
						<circle class="crm-circle-loader-path" cx="50" cy="50" r="20" fill="none" stroke-width="1" stroke-miterlimit="10"/>
					</svg>
				</div>
			</div>
		</div>
	</div>
</script>

<div style="display: none;">
	<div class="crm-webform-edit-task-edit-deal-stage-macros-presets" id="CRM_WEB_FORM_POPUP_PRESET_MACROS">
		<div class="rm-webform-edit-task-edit-deal-stage-macros-presets-inner">
			<?foreach($arResult['PRESET_MACROS_LIST'] as $macros):?>
				<span class="crm-webform-edit-task-edit-deal-stage-macros-presets-item" data-bx-preset-macros="<?=htmlspecialcharsbx($macros['CODE'])?>" title="<?=htmlspecialcharsbx($macros['CODE'] . ' - ' . $macros['DESC'])?>">
					<?=htmlspecialcharsbx($macros['NAME'])?>
				</span>
				<br>
			<?endforeach;?>
		</div>
	</div>
	<div id="CRM_WEB_FORM_POPUP_SETTINGS">
		<div class="crm-webform-edit-popup-wrapper">
			<div id="CRM_WEB_FORM_POPUP_SETTINGS_CONTAINER" class="crm-webform-edit-popup-container"></div>
		</div>
	</div>
	<div id="CRM_WEB_FORM_POPUP_CONFIRM_FIELD_CREATE">
		<div class="crm-popup-setting-fields" style="display: block; padding-bottom: 10px;">
			<div class="crm-p-s-f-block-wrap crm-p-s-f-block-hide crm-p-s-f-block-open" style="height: auto;">
				<div class="crm-p-s-f-block-hide-inner">
					<div class="crm-p-s-f-text">
						<?=Loc::getMessage(
							'CRM_WEBFORM_EDIT_POPUP_CONFIRM_TITLE1',
							array(
								'%ENTITY%' => '<span id="CRM_WEB_FORM_POPUP_CONFIRM_FIELD_CREATE_ENTITY"></span>'
							)
						)?>
						<br><br>
						<?=Loc::getMessage('CRM_WEBFORM_EDIT_POPUP_CONFIRM_TITLE2')?>
					</div>
					<div class="crm-p-s-f-top-block">
						<div class="crm-p-s-f-title"><?=Loc::getMessage('CRM_WEBFORM_EDIT_POPUP_CONFIRM_FIELDS')?>:</div>
						<ul id="CRM_WEB_FORM_POPUP_CONFIRM_FIELD_CREATE_LIST" class="crm-p-s-f-items-list">
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>