<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */
/** @var CBitrixComponentTemplate $this */
/** @var \CCrmRequisiteFormEditorComponent $component */

\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/crm.css');
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/requisite.js');

$popupTitle = '';
switch ($arResult['REQUISITE_ENTITY_TYPE_ID'])
{
	case CCrmOwnerType::Contact:
		$popupTitle = GetMessage('CRM_REQUISITE_POPUP_TITLE_CONTACT');
		break;
	case CCrmOwnerType::Company:
		$popupTitle = GetMessage('CRM_REQUISITE_POPUP_TITLE_COMPANY');
		break;
}

$componentId =  $component->getComponentId();
$containerId = "crm-requisite-form-editor-{$componentId}";
$fieldNameTemplate =  $component->getFormFieldNameTemplate();
$presetSelectorTitle = ($arResult['REQUISITE_ENTITY_TYPE_MNEMO'] === 'COMPANY') ?
	GetMessage('CRM_REQUISITE_PRESET_SELECTOR_TITLE_COMPANY') :
	GetMessage('CRM_REQUISITE_PRESET_SELECTOR_TITLE_CONTACT');
$errPresetNotSelected = ($arResult['REQUISITE_ENTITY_TYPE_MNEMO'] === 'COMPANY') ?
	GetMessage('CRM_REQUISITE_POPUP_ERR_PRESET_NOT_SELECTED_COMPANY') :
	GetMessage('CRM_REQUISITE_POPUP_ERR_PRESET_NOT_SELECTED_CONTACT');

$newPseudoIdStartNumber = 0;
$isFormDataPresent = is_array($arResult['REQUISITE_FORM_DATA']);
$isRequisiteDataPresent = is_array($arResult['REQUISITE_DATA_LIST']);
if($isFormDataPresent)
{
	$newPseudoIdStartNumber = count($arResult['REQUISITE_FORM_DATA']);
}
else if ($isRequisiteDataPresent)
{
	$newPseudoIdStartNumber = count($arResult['REQUISITE_DATA_LIST']);
}

?><script type="text/javascript">
	BX.ready(
		function()
		{
			BX.namespace("BX.Crm");
			var componentId = "<?=CUtil::JSEscape($componentId)?>";

			BX.Crm.RequisiteFormManager.messages =
			{
				presetSelectorTitle: "<?=CUtil::JSEscape($presetSelectorTitle)?>",
				presetSelectorText: "<?=CUtil::JSEscape(GetMessage('CRM_REQUISITE_PRESET_SELECTOR_TEXT'))?>",
				errPresetNotSelected: "<?=CUtil::JSEscape($errPresetNotSelected)?>"
			};
			BX.Crm.RequisiteFormManager.create(componentId,
				{
					entityTypeId: <?=$arResult['REQUISITE_ENTITY_TYPE_ID']?>,
					entityId: <?=$arResult['REQUISITE_ENTITY_ID']?>,
					countryId: <?=$arResult['COUNTRY_ID']?>,
					presetList: <?=CUtil::PhpToJSObject($arResult['PRESET_LIST'])?>,
					presetLastSelectedId: <?=$arResult['PRESET_LAST_SELECTED_ID']?>,
					containerId: "<?=CUtil::JSEscape($containerId)?>",
					fieldNameTemplate: "<?=CUtil::JSEscape($arResult['FORM_FIELD_NAME_TEMPLATE'])?>",
					formLoaderUrl: "/bitrix/components/bitrix/crm.requisite.edit/innerform.ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
					serviceUrl: "/bitrix/components/bitrix/crm.requisite.edit/ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
					pseudoIdStartNumber: <?=$newPseudoIdStartNumber?>
				}
			);

			BX.Crm.ExternalRequisiteDialog.messages =
			{
				searchResultNotFound: "<?=GetMessageJS('CRM_REQUISITE_SERCH_RESULT_NOT_FOUND')?>"
			};
		}

	);
</script>
<div id="<?=$containerId?>" class="crm-offer-requisite-block-wrap"><?
	if($isFormDataPresent)
	{
		$entityTypeId = $arResult['REQUISITE_ENTITY_TYPE_ID'];
		$entityId = $arResult['REQUISITE_ENTITY_ID'];

		end($arResult['REQUISITE_FORM_DATA']);
		$lastKey = key($arResult['REQUISITE_FORM_DATA']);
		$n = 0;
		foreach($arResult['REQUISITE_FORM_DATA'] as $requisiteId => $formData)
		{
			$elementId = (int)$requisiteId;
			$pseudoId = $elementId > 0 ? $elementId : 'n'.$n++;

			$APPLICATION->IncludeComponent(
				'bitrix:crm.requisite.edit',
				'',
				array(
					'ENTITY_TYPE_ID' => $entityTypeId,
					'ENTITY_ID' => $entityId,
					'ELEMENT_ID' => $elementId,
					'PSEUDO_ID' => $pseudoId,
					'PRESET_ID' => isset($formData['PRESET_ID']) ? (int)$formData['PRESET_ID'] : 0,
					'REQUISITE_FORM_DATA' => $formData,
					'INNER_FORM_MODE' => 'Y',
					'FIELD_NAME_TEMPLATE' => str_replace('#ELEMENT_ID#', $pseudoId, $fieldNameTemplate),
					'IS_LAST_IN_FORM' => ($requisiteId === $lastKey ? 'Y' : 'N')
				),
				false
			);
		}
		unset($n);
	}
	else if ($isRequisiteDataPresent)
	{
		end($arResult['REQUISITE_DATA_LIST']);
		$lastKey = key($arResult['REQUISITE_DATA_LIST']);
		$n = 0;
		foreach($arResult['REQUISITE_DATA_LIST'] as $k => $data)
		{
			$requisiteId = $data['requisiteId'];
			$pseudoId = ($requisiteId > 0) ? $requisiteId : 'n'.$n++;
			$APPLICATION->IncludeComponent(
				'bitrix:crm.requisite.edit',
				'',
				array(
					'ENTITY_TYPE_ID' => $data['entityTypeId'],
					'ENTITY_ID' => $data['entityId'],
					'ELEMENT_ID' => $requisiteId,
					'PSEUDO_ID' => $pseudoId,
					'PRESET_ID' => $data['presetId'],
					'REQUISITE_DATA' => $data['requisiteData'],
					'REQUISITE_DATA_SIGN' => $data['requisiteDataSign'],
					'INNER_FORM_MODE' => 'Y',
					'FIELD_NAME_TEMPLATE' => str_replace('#ELEMENT_ID#', $pseudoId, $fieldNameTemplate),
					'IS_LAST_IN_FORM' => ($k === $lastKey ? 'Y' : 'N')
				),
				false
			);
		}
		unset($n);
	}
?></div><?
unset($newPseudoIdStartNumber, $isFormDataPresent, $isRequisiteDataPresent);
?>