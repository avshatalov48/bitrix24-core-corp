<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Crm\UserField\Types\ElementType;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;

/**
 * @var ElementCrmUfComponent $component
 * @var array $arResult
 */

$component = $this->getComponent();

$fieldName = $arParams['userField']['FIELD_NAME'];
$formName = (isset($arParams['form_name']) ? (string)$arParams['form_name'] : '');

$randString = $this->randString();
if ($component->isAjaxRequest())
{
	$randString .= time();
}

$fieldUID = mb_strtolower(str_replace('_', '-', $fieldName)) . $randString;
if($formName !== '')
{
	$fieldUID = mb_strtolower(str_replace('_', '-', $formName)).'-' . $fieldUID;
}
$fieldUID = CUtil::JSescape($fieldUID);

$jsObject = 'CrmEntitySelector_' . $randString;

if($arResult['PERMISSION_DENIED'])
{
	?>
	<div
		id="crm-<?= $fieldUID ?>-box"
		data-has-input="no"
	>
		<div class="crm-element-button-open">
			<?= Loc::getMessage('CRM_SFE_ENTITY_NOT_SELECTED') ?>
		</div>
		<?php
		foreach($arResult['value'] as $value)
		{
			$name = HtmlFilter::encode($fieldName . ($arResult['MULTIPLE'] === 'Y' ? '[]' : ''));
			$value = HtmlFilter::encode($value);
			?>
			<input type="hidden" name="<?= $name ?>" value="<?= $value ?>">
			<?php
		}
		?>
	</div>
	<?php
}
else
{
	?>
	<div
		id="crm-<?= $fieldUID ?>-box"
		data-has-input="no"
	>
		<span id="crm-<?= $fieldUID ?>-open">
			<?php

			$selectorOptions = [
				'lazyLoad' => 'Y',
				'context' => (!empty($arParams['CONTEXT']) ? $arParams['CONTEXT'] : 'crmEntityCreate'),
				'contextCode' => '',
				'enableSonetgroups' => 'N',
				'enableUsers' => 'N',
				'useClientDatabase' => 'N',
				'enableAll' => 'N',
				'enableDepartments' => 'N',
				'enableCrm' => 'Y',
				'crmPrefixType' => 'SHORT'
			];

			$entityTypesSelectorOptions = ElementType::getEnableEntityTypesForSelectorOptions(
				$arParams['ENTITY_TYPE'],
				$arResult['DYNAMIC_TYPE_TITLES']
			);
			$selectorOptions = array_merge($selectorOptions, $entityTypesSelectorOptions);

			$APPLICATION->IncludeComponent(
				'bitrix:main.user.selector',
				'',
				[
					'ID' => $fieldUID,
					'LIST' => (!empty($arResult['SELECTED_LIST']) ? $arResult['SELECTED_LIST'] : []),
					'LAZYLOAD' => 'Y',
					'INPUT_NAME' => $fieldName . ($arResult['MULTIPLE'] === 'Y' ? '[]' : ''),
					'USE_SYMBOLIC_ID' => $arResult['USE_SYMBOLIC_ID'],
					'CONVERT_TO_SYMBOLIC_ID' => (!$arResult['USE_SYMBOLIC_ID'] ? 'N' : false),
//				"BUTTON_SELECT_CAPTION" => Loc::getMessage("CRM_SL_EVENT_EDIT_MPF_WHERE_1"),
					'API_VERSION' => 3,
					'SELECTOR_OPTIONS' => $selectorOptions,
					'CALLBACK_BEFORE' => $arParams['additionalParameters']['CALLBACK_BEFORE'] ?? [],
				]
			);
			?>
		</span>

		<?php
		if($arResult['canCreateNewEntity'])
		{
			?>
			<script>
				BX.ready(function ()
				{
					BX['<?=$jsObject?>'] = new BX.CrmElementEntitySelector({
						randomString: '<?=$randString?>',
						jsObject: '<?=$jsObject?>',
						fieldUid: '<?=$fieldUID?>',
						fieldName: '<?=$fieldName?>',
						usePrefix: '<?=$arResult['PREFIX']?>',
						multiple: '<?=$arResult['MULTIPLE']?>',
						context: '<?=!empty($arParams['CONTEXT']) ? $arParams['CONTEXT'] : 'crmEntityCreate'?>',
						listPrefix: <?=\Bitrix\Main\Web\Json::encode($arResult['LIST_PREFIXES'])?>,
						selectorEntityTypes: <?=\Bitrix\Main\Web\Json::encode($arResult['SELECTOR_ENTITY_TYPES'])?>,
						listElement: <?=\Bitrix\Main\Web\Json::encode($arResult['ELEMENT'] ?? [])?>,
						listEntityType: <?=\Bitrix\Main\Web\Json::encode($arParams['ENTITY_TYPE'])?>,
						listEntityCreateUrl: <?=\Bitrix\Main\Web\Json::encode($arResult['LIST_ENTITY_CREATE_URL'])?>,
						pluralCreation: '<?=!empty($arResult['PLURAL_CREATION']) ? 'true' : '' ?>',
						currentEntityType: '<?=!empty($arResult['CURRENT_ENTITY_TYPE']) ? $arResult['CURRENT_ENTITY_TYPE'] : null?>',
						dynamicTypeTitles: <?= \Bitrix\Main\Web\Json::encode($arResult['DYNAMIC_TYPE_TITLES']) ?>,
					});

					BX.message({
						CRM_ELEMENT_LEAD: '<?= \CUtil::JSEscape(Loc::getMessage('CRM_ELEMENT_LEAD')) ?>',
						CRM_ELEMENT_CONTACT: '<?= \CUtil::JSEscape(Loc::getMessage('CRM_ELEMENT_CONTACT')) ?>',
						CRM_ELEMENT_COMPANY: '<?= \CUtil::JSEscape(Loc::getMessage('CRM_ELEMENT_COMPANY')) ?>',
						CRM_ELEMENT_DEAL: '<?= \CUtil::JSEscape(Loc::getMessage('CRM_ELEMENT_DEAL')) ?>',
						CRM_ELEMENT_ORDER: '<?= \CUtil::JSEscape(Loc::getMessage('CRM_ELEMENT_ORDER')) ?>',
						CRM_ELEMENT_QUOTE: '<?= \CUtil::JSEscape(Loc::getMessage('CRM_ELEMENT_QUOTE_MSGVER_1')) ?>',
						CRM_ELEMENT_OK: '<?= \CUtil::JSEscape(Loc::getMessage('CRM_ELEMENT_OK')) ?>',
						CRM_ELEMENT_CANCEL: '<?= \CUtil::JSEscape(Loc::getMessage('CRM_ELEMENT_CANCEL')) ?>',
						CRM_ELEMENT_CLOSE: '<?= \CUtil::JSEscape(Loc::getMessage('CRM_ELEMENT_CLOSE')) ?>',
						CRM_ELEMENT_SEARCH: '<?= \CUtil::JSEscape(Loc::getMessage('CRM_ELEMENT_SEARCH')) ?>',
						CRM_ELEMENT_NO_RESULT: '<?= \CUtil::JSEscape(Loc::getMessage('CRM_ELEMENT_NO_RESULT')) ?>',
						CRM_ELEMENT_CHOISE: '<?= \CUtil::JSEscape(Loc::getMessage('CRM_ELEMENT_CHOISE')) ?>',
						CRM_ELEMENT_CHANGE: '<?= \CUtil::JSEscape(Loc::getMessage('CRM_ELEMENT_CHANGE')) ?>',
						CRM_ELEMENT_LAST: '<?= \CUtil::JSEscape(Loc::getMessage('CRM_ELEMENT_LAST')) ?>',
						CRM_ELEMENT_CREATE_LEAD: '<?= \CUtil::JSEscape(Loc::getMessage('CRM_ELEMENT_CREATE_LEAD')) ?>',
						CRM_ELEMENT_CREATE_CONTACT: '<?= \CUtil::JSEscape(Loc::getMessage('CRM_ELEMENT_CREATE_CONTACT')) ?>',
						CRM_ELEMENT_CREATE_COMPANY: '<?= \CUtil::JSEscape(Loc::getMessage('CRM_ELEMENT_CREATE_COMPANY')) ?>',
						CRM_ELEMENT_CREATE_DEAL: '<?= \CUtil::JSEscape(Loc::getMessage('CRM_ELEMENT_CREATE_DEAL')) ?>'
					});
				});
			</script>

			<div class="crm-element-button-open">
				<span onclick="BX['<?= $jsObject ?>'].createNewEntity(event);">
					<?= Loc::getMessage('CRM_ELEMENT_CREATE') ?>
				</span>
			</div>
			<?php
		}
		?>
	</div>
	<?php
}
