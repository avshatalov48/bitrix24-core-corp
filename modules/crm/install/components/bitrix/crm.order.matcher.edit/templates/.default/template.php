<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Web\Json;

/**
 * @var CBitrixComponentTemplate $this
 * @var array $arParams
 * @var array $arResult
 */

if (!$arResult['FORM']['BUTTON_COLOR_BG'])
{
	$arResult['FORM']['BUTTON_COLOR_BG'] = '#00AEEF';
}
if (!$arResult['FORM']['BUTTON_COLOR_FONT'])
{
	$arResult['FORM']['BUTTON_COLOR_FONT'] = '#FFFFFF';
}
if (!$arResult['FORM']['BUTTON_CAPTION'])
{
	$arResult['FORM']['BUTTON_CAPTION'] = Loc::getMessage('CRM_ORDERFORM_EDIT_FORM_SUBMIT_BTN_CAPTION_DEFAULT');
}
if (!$arResult['FORM']['LICENCE_BUTTON_CAPTION'])
{
	$arResult['FORM']['LICENCE_BUTTON_CAPTION'] = Loc::getMessage('CRM_ORDERFORM_EDIT_LICENCE_BUTTON_CAPTION_DEFAULT');
}

\Bitrix\Main\UI\Extension::load([
	"ui.design-tokens",
	"ui.fonts.opensans",
	"ui.buttons",
]);

if (\Bitrix\Main\Loader::includeModule('socialnetwork'))
{
	CUtil::InitJSCore(array('socnetlogdest'));
}

if (\Bitrix\Main\Loader::includeModule('bitrix24'))
{
	CBitrix24::initLicenseInfoPopupJS();
}

\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/main/core/core_dragdrop.js');


$APPLICATION->SetPageProperty(
	'BodyClass',
	$APPLICATION->GetPageProperty('BodyClass').' no-paddings'
);

require 'php_templates.php';
require 'php_userblockcontroller.php';

global $USER;
$userBlockController = new CrmOrderPropsFormEditUserBlockController(
	$USER->GetID(),
	CrmOrderPropsFormEditTemplate::getUserBlock(),
	CrmOrderPropsFormEditTemplate::getUserBlockNav()
);

$jsEventsManagerId = 'PageEventsManager_'.$arResult['COMPONENT_ID'];

require 'js_templates.php';
?>

<div class="crm-orderform-edit-wrapper">
	<div id="crm-orderform-error" <?=(!empty($arResult['ERRORS']) ? '' : ' style="display: none;"')?>>
		<?
		foreach ((array)$arResult['ERRORS'] as $error)
		{
			?>
			<div class="crm-entity-widget-content-error-text">
				<?=$error?>
			</div>
			<?
		}
		?>
	</div>
	<div class="crm-orderform-edit-left-inner-field-container" style="margin-top: 10px;">
		<label for="" class="crm-orderform-edit-left-inner-field-label" title="<?=Loc::getMessage('CRM_ORDERFORM_PERSON_TYPE')?>">
			<?=Loc::getMessage('CRM_ORDERFORM_PERSON_TYPE')?>:
			<select class="crm-orderform-edit-left-inner-field-select" id="PERSON_TYPE_SELECTOR" name="personTypeId"
					style="width: initial;">
				<?
				$personTypeName = '';

				foreach ($arResult['PERSON_TYPES'] as $item)
				{
					$selected = '';

					if ((int)$item['ID'] === $arResult['SELECTED_PERSON_TYPE_ID'])
					{
						$selected = 'selected';
						$personTypeName = $item['NAME'];
					}
					?>
					<option value="<?=$item['ID']?>" <?=$selected?>><?=HtmlFilter::encode($item['NAME'])?></option>
					<?
				}
				?>
			</select>
		</label>
	</div>

	<form id="crm_orderform_edit_form" name="crm_orderform_edit_form" method="POST" enctype="multipart/form-data" action="<?=$APPLICATION->GetCurPageParam()?>">
		<!-- form-reload-container -->
		<input type="hidden" name="ID" value="<?=$arResult['FORM']['ID']?>">
		<input type="hidden" name="PERSON_TYPE_ID" value="<?=$arResult['SELECTED_PERSON_TYPE_ID']?>">
		<input type="hidden" name="action" value="save">
		<?=bitrix_sessid_post(); ?>

		<div class="crm-orderform-edit-about-container">

			<div class="crm-orderform-edit-about-tune-container">
				<span id="CRM_ORDERFORM_STICKER_ENTITY_SCHEME_NAV" class="crm-orderform-edit-about-tune">
					<?=Loc::getMessage('CRM_ORDERFORM_EDIT_EDIT')?>
				</span>
			</div><!--crm-orderform-edit-about-tune-container-->
			<div class="crm-orderform-edit-about-info-container">
			<span id="CRM_ORDERFORM_STICKER_ENTITY_SCHEME_TEXT" class="crm-orderform-edit-about-info">
				<?=Loc::getMessage('CRM_ORDERFORM_EDIT_DOCUMENT_CREATE')?>:
				<span id="ENTITY_SCHEMES_TOP_DESCRIPTION" class="crm-orderform-edit-about-info-deal"></span>
			</span>
			</div><!--crm-orderform-edit-about-info-container-->

		</div><!--crm-orderform-edit-about-container-->

		<div class="crm-orderform-edit-constructor-container" id="FORM_CONTAINER">

			<div id="FIELD_SELECTOR" class="crm-orderform-edit-constructor-right-container">

				<div data-bx-crm-wf-selector-search-btn="" class="crm-orderform-edit-constructor-right-search">
					<span class="crm-orderform-edit-right-search-icon" title="<?=Loc::getMessage('CRM_ORDERFORM_EDIT_FORM_TREE_SEARCH_BUTTON')?>"></span>
					<span class="crm-orderform-edit-right-search-item"><?=Loc::getMessage('CRM_ORDERFORM_EDIT_FORM_ADD_FIELD')?>:</span>
					<input data-bx-crm-wf-selector-search="" type="text" placeholder="<?=Loc::getMessage('CRM_ORDERFORM_EDIT_FORM_TREE_SEARCH')?>" class="crm-orderform-edit-right-search-input">
				</div><!--crm-orderform-edit-constructor-right-search-->

				<div class="crm-orderform-edit-constructor-right-list-container">

					<div class="crm-orderform-edit-right-list">
						<? crmOrderPropsFormDrawFieldsTree($arResult['AVAILABLE_FIELDS_TREE']); ?>
					</div>
				</div><!--crm-orderform-edit-constructor-right-list-container-->

			</div><!--crm-orderform-edit-constructor-right-block-->

			<div class="crm-orderform-edit-constructor-left-container">

				<div id="FIELD_CONTAINER" class="crm-orderform-edit-left-field-container">
					<?
					$sort = 0;
					foreach ($arResult['FORM']['FIELDS'] as $field):
						$sort++;
						$field['sort'] = $sort;
						$field['CURRENCY_SHORT_NAME'] = $arResult['CURRENCY']['SHORT_NAME'];
						echo CrmOrderPropsFormEditTemplate::getField($field);
					endforeach;
					?>
				</div><!--crm-orderform-edit-left-field-container-->

				<? if ($arResult['PERM_CAN_EDIT']): ?>
					<div class="crm-orderform-edit-left-field-add-element">
						<span class="crm-orderform-edit-left-field-add-element-item">
							<?=Loc::getMessage('CRM_ORDERFORM_EDIT_FORM_ADD_HELP_TEXT')?>
						</span>
					</div><!--crm-orderform-edit-left-field-add-element-->
				<? endif; ?>

			</div><!--crm-orderform-edit-constructor-left-block-->

		</div><!--crm-orderform-edit-field-constructor-container-->

		<?
		$userBlockController->start('DEPENDENCIES', Loc::getMessage('CRM_ORDERFORM_EDIT_DEP_SECTION'), Loc::getMessage('CRM_ORDERFORM_EDIT_DEP_SECTION_NAV'));
		?>
		<div class="crm-orderform-edit-task-options-item-open-settings">
			<div id="DEPENDENCY_CONTAINER" class="crm-orderform-ext-block-dep-list">
				<?
				foreach ($arResult['FORM']['RELATIONS'] as $relation):
					GetCrmOrderPropsFormFieldRelationTemplate($relation);
				endforeach;
				?>
			</div>

			<span id="DEPENDENCY_BUTTON_ADD" class="crm-orderform-edit-task-options-rule">&#43; <?=Loc::getMessage('CRM_ORDERFORM_EDIT_DEP_BUTTON_ADD')?></span>
		</div>
		<?
		$userBlockController->start('ENTITY_SCHEME', Loc::getMessage('CRM_ORDERFORM_EDIT_DOC_SECTION'), Loc::getMessage('CRM_ORDERFORM_EDIT_DOC_SECTION_NAV'));
		?>
		<div class="crm-orderform-edit-task-options-item-open-settings">
			<div class="crm-orderform-edit-task-options-settings-title-cotainer">
				<div class="crm-orderform-edit-task-options-settings-title">
					<?=Loc::getMessage('CRM_ORDERFORM_EDIT_DOC_TITLE')?>:
				</div>
			</div>

			<div id="ENTITY_SCHEME_CONTAINER" class="crm-orderform-edit-task-options-settings-container">
				<div class="crm-orderform-edit-task-options-document-settings-radio-container" style="display: none;">
					<input type="hidden" name="ENTITY_SCHEME" value="<?=htmlspecialcharsbx($arResult['ENTITY_SCHEMES']['SELECTED_ID'])?>">
					<? foreach ($arResult['ENTITY_SCHEMES']['BY_NON_INVOICE'] as $searchSchemeId => $entityScheme): ?>
						<label for="ENTITY_SCHEME_<?=htmlspecialcharsbx($entityScheme['ID'])?>">
							<input type="radio" id="ENTITY_SCHEME_<?=htmlspecialcharsbx($entityScheme['ID'])?>"
								   data-bx-order-form-entity-scheme-value=""
								   name="ENTITY_SCHEME_SELECTOR"
								   class="crm-orderform-edit-task-options-document-settings-radio"
								<?=($entityScheme['SELECTED'] ? 'checked' : '')?>
								<?=($entityScheme['DISABLED'] ? 'disabled' : '')?>
								   value="<?=htmlspecialcharsbx($searchSchemeId)?>"
							>
							<span class="crm-orderform-edit-task-options-document-settings-radio-element">
							<?=htmlspecialcharsbx($entityScheme['NAME'])?>
						</span>
						</label>
					<? endforeach; ?>
				</div>
				<div class="crm-orderform-edit-task-options-document-settings-description">
				<span class="crm-orderform-edit-task-options-document-settings-description-element">
					<?=Loc::getMessage('CRM_ORDERFORM_EDIT_DOC_LIST_TITLE')?>:
					<span data-bx-orderform-edit-scheme-desc="">
						<?=htmlspecialcharsbx($arResult['ENTITY_SCHEMES']['SELECTED_DESCRIPTION'])?>
					</span>
				</span>
				</div>
				<div class="crm-orderform-edit-task-options-document-duplicate">
					<div class="crm-orderform-edit-task-options-document-duplicate-control">
						<div class="crm-orderform-edit-task-options-document-duplicate-list-element"><?=Loc::getMessage('CRM_ORDERFORM_EDIT_DOC_DUPLICATES')?>:</div>
						<div class="crm-orderform-edit-task-options-document-duplicate-list-container">
							<? foreach ($arResult['DUPLICATE_MODES'] as  $duplicateMode):
								$duplicateModeNodeId = 'DUPLICATE_MODE_' . htmlspecialcharsbx($duplicateMode['ID']);
								?>
								<label for="<?=$duplicateModeNodeId?>" class="task-option-duplicate-label">
									<input id="<?=$duplicateModeNodeId?>" name="DUPLICATE_MODE" value="<?=htmlspecialcharsbx($duplicateMode['ID'])?>" <?=($duplicateMode['SELECTED'] ? 'checked' : '')?> type="radio" class="crm-orderform-edit-task-options-document-duplicate-list-input">
									<span class="crm-orderform-edit-task-options-document-duplicate-list-input-element">
									<?=htmlspecialcharsbx($duplicateMode['CAPTION'])?>
								</span>
								</label>
							<? endforeach; ?>
						</div>
					</div>
				</div>
			</div><!--crm-orderform-edit-task-options-document-settings-container-->
		</div>
		<?
		$userBlockController->end();
		?>

		<div class="task-additional-block" id="CRM_ORDERFORM_ADDITIONAL_OPTIONS">

			<div id="FIXED_OPTION_PLACE" class="crm-orderform-edit-task-options task-openable-block">

				<div id="CRM_ORDERFORM_RESPONSIBLE" class="crm-orderform-edit-task-options-item-destination-wrap">

					<div class="crm-orderform-edit-task-options-item crm-orderform-edit-task-options-item-destination">
						<span class="crm-orderform-edit-task-options-item-param"><?=Loc::getMessage('CRM_ORDERFORM_EDIT_ASSIGNED_BY1')?>:</span>
						<div class="crm-orderform-edit-task-options-item-open-inner">
							<div id="crm-orderform-edit-responsible" data-config="<?=htmlspecialcharsbx(Json::encode($arResult['CONFIG_ASSIGNED_BY']))?>"></div>

							<? if ($arResult['ASSIGNED_BY']['IS_SUPPORTED_WORK_TIME']): ?>
								<div style="margin: 15px 0 0 0;">
									<label for="ASSIGNED_WORK_TIME" class="crm-orderform-edit-task-options-stylesheet-label">
										<input id="ASSIGNED_WORK_TIME" name="ASSIGNED_WORK_TIME" value="Y" type="checkbox" <?=($arResult['ASSIGNED_BY']['WORK_TIME'] ? 'checked' : '')?> class="crm-orderform-edit-task-options-stylesheet-checkbox">
										<span class="crm-orderform-edit-task-options-stylesheet-element"><?=Loc::getMessage('CRM_ORDERFORM_EDIT_ASSIGNED_WORK_TIME')?></span>
									</label>
								</div>
							<? endif; ?>
						</div>
					</div>

				</div>
				<?
				$userBlockController->showFixed();
				?>
			</div>

			<div class="task-additional-alt" id="ADDITIONAL_OPTION_BUTTON">
				<div class="task-additional-alt-more"><?=Loc::getMessage('CRM_ORDERFORM_EDIT_NAV_TITLE')?></div>
				<div class="task-additional-alt-promo">
					<?
					$userBlockController->showNavigation();
					?>
				</div>
			</div><!--task-additional-alt-->


			<div id="ADDITIONAL_OPTION_CONTAINER" class="crm-orderform-edit-task-options crm-orderform-edit-task-options-more task-openable-block">
				<?
				$userBlockController->show();
				?>
			</div>
		</div>

		<? if ($arResult['FORM']['HAS_ADS_FORM_LINKS'] == 'Y'): ?>
			<div class="crm-orderform-edit-system-warning-cont">
			<span class="crm-orderform-edit-system-warning-text">
				<?=Loc::getMessage('CRM_ORDERFORM_EDIT_SYSTEM_CANT_COPY_ADS')?>
			</span>
			</div>
		<? endif; ?>

		<div class="crm-orderform-edit-button-container">
			<?
			if ($arResult['PERM_CAN_EDIT'])
			{
				if ($arParams['IFRAME'])
				{
					?>
					<span id="CRM_ORDERFORM_SUBMIT_APPLY" class="ui-btn ui-btn-success">
						<?=Loc::getMessage('CRM_ORDERFORM_EDIT_BUTTON_SAVE')?>
					</span>
					<?
				}
				else
				{
					?>
					<span id="CRM_ORDERFORM_SUBMIT_BUTTON" class="ui-btn ui-btn-success">
						<?=Loc::getMessage('CRM_ORDERFORM_EDIT_BUTTON_APPLY')?>
					</span>
					<?
				}
			}

			if ($arParams['IFRAME'])
			{
				?>
				<span id="CRM_ORDERFORM_EDIT_TO_LIST_BOTTOM" class="ui-btn ui-btn-link">
					<?=Loc::getMessage('CRM_ORDERFORM_EDIT_BACK_TO_LIST')?>
				</span>
				<?
			}
			?>
		</div>

		<?
		$signer = new \Bitrix\Main\Security\Sign\Signer;
		$signedParams = $signer->sign(base64_encode(serialize($arParams)), 'crm.order.matcher.edit');
		?>
		<script>
			jsColorPickerMess = window.jsColorPickerMess = {
				DefaultColor: '<?echo GetMessageJS('CRM_ORDERFORM_EDIT_COLOR_BUTTON_DEFAULT'); ?>'
			};

			BX.ready(function()
			{
				BX.message['CRM_ORDERFORM_EDIT_JS_PRODUCT_CHOICE'] = '<?=GetMessageJS('CRM_ORDERFORM_EDIT_JS_PRODUCT_CHOICE')?>';
				BX.message['CRM_ORDERFORM_EDIT_TMPL_FILE_DOWNLOAD'] = '<?=GetMessageJS('CRM_ORDERFORM_EDIT_TMPL_FILE_DOWNLOAD')?>';
				BX.message['CRM_ORDERFORM_EDIT_TMPL_FILE_NOT_SELECTED'] = '<?=GetMessageJS('CRM_ORDERFORM_EDIT_TMPL_FILE_NOT_SELECTED')?>';
				BX.namespace("BX.Crm");
				BX.Crm["<?=$jsEventsManagerId?>"] = BX.Crm.PageEventsManagerClass.create({id: "<?=$arResult['COMPONENT_ID']?>"});

				new CrmFormEditor({
					context: BX('FORM_CONTAINER'),
					actionRequestUrl: '<?=CUtil::JSEscape($this->getComponent()->getPath())?>/ajax.php',
					signedParamsString: '<?=CUtil::JSEscape($signedParams)?>',
					personType: '<?=CUtil::JSEscape($arResult['SELECTED_PERSON_TYPE_ID'])?>',
					fields: <?=CUtil::PhpToJSObject($arResult['FORM']['FIELDS'])?>,
					fieldsDictionary: <?=CUtil::PhpToJSObject($arResult['AVAILABLE_FIELDS'])?>,
					schemesDictionary: <?=CUtil::PhpToJSObject($arResult['ENTITY_SCHEMES'])?>,
					entityDictionary: <?=CUtil::PhpToJSObject($arResult['AVAILABLE_ENTITIES'])?>,
					presetFields: <?=CUtil::PhpToJSObject($arResult['FORM']['PRESET_FIELDS'])?>,
					booleanFieldItems: <?=CUtil::PhpToJSObject($arResult['BOOLEAN_FIELD_ITEMS'])?>,
					isFramePopup: <?=CUtil::PhpToJSObject($arParams['IFRAME'])?>,
					isItemWasAdded: <?=CUtil::PhpToJSObject($arResult['IS_ITEM_WAS_ADDED'])?>,
					relations: <?=CUtil::PhpToJSObject($arResult['FORM']['RELATIONS'])?>,
					allRelations: <?=CUtil::PhpToJSObject($arResult['FORM']['ALL_RELATIONS'])?>,
					relationEntities: <?=CUtil::PhpToJSObject($arResult['RELATION_ENTITIES'])?>,
					templates: {
						field: 'tmpl_field_%type%',
						dependency: 'tmpl_field_dependency',
						presetField: 'tmpl_field_preset'
					},
					path: {
						property: '<?=CUtil::JSEscape($arParams['PATH_TO_ORDER_PROPERTY_EDIT'])?>'
					},
					jsEventsManagerId: '<?=$jsEventsManagerId?>',
					userBlocks: {
						optionPinName: '<?=CrmOrderPropsFormEditUserBlockController::USER_OPTION?>'
					},
					id: '<?=CUtil::JSEscape($arResult['FORM']['ID'])?>',
					detailPageUrlTemplate: '<?=CUtil::JSEscape($arParams['PATH_TO_ORDER_FORM_EDIT'])?>',
					canRemoveCopyright: <?=($arResult['CAN_REMOVE_COPYRIGHT'] ? 'true' : 'false')?>,
					currency: <?=CUtil::PhpToJSObject($arResult['CURRENCY'])?>,
					mess: <?=CUtil::PhpToJSObject(array(
						'selectField' => Loc::getMessage('CRM_ORDERFORM_EDIT_SELECT_FIELD'),
						'selectFieldOrSection' => Loc::getMessage('CRM_ORDERFORM_EDIT_SELECT_FIELD_OR_SECTION'),
						'selectValue' => Loc::getMessage('CRM_ORDERFORM_EDIT_SELECT_FIELD_VALUE'),
						'newFieldSectionCaption' => Loc::getMessage('CRM_ORDERFORM_EDIT_NEW_FIELD_SECTION_CAPTION'),
						'newFieldProductsCaption' => Loc::getMessage('CRM_ORDERFORM_EDIT_NEW_FIELD_PRODUCT_CAPTION'),
						'dlgContinue' => Loc::getMessage('CRM_ORDERFORM_EDIT_CONTINUE'),
						'dlgCreateFields' => Loc::getMessage('CRM_ORDERFORM_EDIT_CREATE_FIELDS'),
						'dlgCancel' => Loc::getMessage('CRM_ORDERFORM_EDIT_CANCEL'),
						'dlgClose' => Loc::getMessage('CRM_ORDERFORM_EDIT_CLOSE'),
						'dlgTitle' => Loc::getMessage('CRM_ORDERFORM_EDIT_POPUP_SETTINGS_TITLE'),
						'dlgRemoveCopyrightTitle' => Loc::getMessage('CRM_ORDERFORM_EDIT_POPUP_LIMITED_TITLE'),
						'dlgRemoveCopyrightText' => Loc::getMessage('CRM_ORDERFORM_EDIT_POPUP_LIMITED_TEXT'),
						'dlgInvoiceEmptyProductTitle' => Loc::getMessage('CRM_ORDERFORM_EDIT_POPUP_INVOICE_EMPTY_PRODUCT_ERROR_TITLE'),
						'dlgInvoiceEmptyProduct' => Loc::getMessage('CRM_ORDERFORM_EDIT_POPUP_INVOICE_EMPTY_PRODUCT_ERROR1'),
						'defaultProductName' => Loc::getMessage('CRM_ORDERFORM_EDIT_DEFAULT_PRODUCT_NAME'),
						'dlgChange' => Loc::getMessage('CRM_ORDERFORM_EDIT_CHANGE'),
						'dlgChoose' => Loc::getMessage('CRM_ORDERFORM_EDIT_CHOOSE'),
						'dlgTitleFieldCreate' => Loc::getMessage('CRM_ORDERFORM_EDIT_FIELD_CREATE_TITLE'),
					))?>
				});
			});
		</script>
		<!-- form-reload-container -->
	</form>

	<script id="crm-orderform-list-template-ads-popup" type="text/html">
		<div>
			<div data-bx-ads-content="" id="crm-orderform-list-ads-popup"></div>
			<div data-bx-ads-loader="" style="display: none;" class="crm-orderform-list-ads-popup">
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
		<div id="CRM_ORDER_FORM_POPUP_SETTINGS">
			<div class="crm-orderform-edit-popup-wrapper">
				<div id="CRM_ORDER_FORM_POPUP_SETTINGS_CONTAINER" class="crm-orderform-edit-popup-container"></div>
				<!--
				<div class="crm-orderform-edit-popup-button-container">
					<button class="orderform-small-button orderform-small-button-accept crm-orderform-edit-popup-button">Ok</button>
				</div>
				-->
			</div>
		</div>
		<div id="CRM_ORDER_FORM_POPUP_CONFIRM_FIELD_CREATE">
			<div class="crm-popup-setting-fields" style="display: block; padding-bottom: 10px;">
				<div class="crm-p-s-f-block-wrap crm-p-s-f-block-hide crm-p-s-f-block-open" style="height: auto;">
					<div class="crm-p-s-f-block-hide-inner">
						<div class="crm-p-s-f-text">
							<?=Loc::getMessage(
								'CRM_ORDERFORM_EDIT_POPUP_CONFIRM_TITLE1',
								array(
									'%ENTITY%' => '<span id="CRM_ORDER_FORM_POPUP_CONFIRM_FIELD_CREATE_ENTITY"></span>'
								)
							)?>
						</div>
						<div class="crm-p-s-f-top-block">
							<div class="crm-p-s-f-title"><?=Loc::getMessage('CRM_ORDERFORM_EDIT_POPUP_CONFIRM_FIELDS')?>:</div>
							<ul id="CRM_ORDER_FORM_POPUP_CONFIRM_FIELD_CREATE_LIST" class="crm-p-s-f-items-list">
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script>
		BX.bind(BX('PERSON_TYPE_SELECTOR'), 'change', function(event){
			var selector = BX.getEventTarget(event);
			var personTypeId = selector.options[selector.selectedIndex].value;
			var url = '<?=CUtil::JSEscape($arParams['PATH_TO_ORDER_FORM_WITH_PT'])?>'.replace('#person_type_id#', personTypeId);

			if (<?=($arParams['IFRAME'] ? 'true' : 'false')?>)
			{
				url += (url.indexOf('?') !== -1 ? '&' : '?') + BX.ajax.prepareData({IFRAME: 'Y'});
			}

			document.location.href = url;
		})
	</script>
</div>
