<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!function_exists('__CrmFieldMultiEditRenderItem'))
{
	function __CrmFieldMultiEditRenderItem($item, $mnemonic, $typeID, $referenceData, $editorID)
	{
		$itemID = isset($item['ID']) ? $item['ID'] : '';
		$itemVal = isset($item['VALUE']) ? $item['VALUE'] : '';
		?><div class="bx-crm-edit-fm-item">
		<input type="text" class="bx-crm-edit-input" name="<?=htmlspecialcharsbx($mnemonic)?>[<?=htmlspecialcharsbx($typeID)?>][<?=htmlspecialcharsbx($itemID)?>][VALUE]" value="<?=htmlspecialcharsbx($itemVal)?>"><?
		echo SelectBoxFromArray(
			CUtil::JSEscape($mnemonic).'['.htmlspecialcharsbx($typeID).']['.htmlspecialcharsbx($itemID).'][VALUE_TYPE]',
			$referenceData,
			isset($item['VALUE_TYPE']) ? $item['VALUE_TYPE'] : '',
			'',
			"class='bx-crm-edit-input bx-crm-edit-input-small'"
		);
		?><div class="delete-action" onclick="BX.CrmFieldMultiEditor.items['<?=CUtil::addslashes($editorID)?>'].deleteItem('<?=CUtil::addslashes($itemID)?>');" title="<?=GetMessage('CRM_STATUS_LIST_DELETE')?>"></div>
		</div><?
	}
}

global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$editorID = isset($arResult['EDITOR_ID']) ? $arResult['EDITOR_ID'] : uniqid("{$arResult['FM_MNEMONIC']}_{$arResult['TYPE_ID']}_");
?><div id="<?=htmlspecialcharsbx($editorID)?>" class="bx-crm-edit-fm-container"><?
$valuCount = count($arResult['VALUES']);
if(!empty($arResult['VALUES'])):
	foreach($arResult['VALUES'] as &$arValue):
		__CrmFieldMultiEditRenderItem($arValue, $arResult['FM_MNEMONIC'], $arResult['TYPE_ID'], $arResult['TYPE_BOX'], $editorID);
	endforeach;
	unset($arValue);
else:
	__CrmFieldMultiEditRenderItem(array('ID'=> 'n1'), $arResult['FM_MNEMONIC'], $arResult['TYPE_ID'], $arResult['TYPE_BOX'], $editorID);
	if ($arResult['TYPE_ID'] === 'WEB'):
		__CrmFieldMultiEditRenderItem(array('ID'=> 'n2', 'VALUE_TYPE' => 'FACEBOOK'), $arResult['FM_MNEMONIC'], $arResult['TYPE_ID'], $arResult['TYPE_BOX'], $editorID);
		__CrmFieldMultiEditRenderItem(array('ID'=> 'n3', 'VALUE_TYPE' => 'TWITTER'), $arResult['FM_MNEMONIC'], $arResult['TYPE_ID'], $arResult['TYPE_BOX'], $editorID);
	endif;
endif;
?>
<span class="bx-crm-edit-fm-add bx-crm-edit-label" onclick="BX.CrmFieldMultiEditor.items['<?= CUtil::addslashes($editorID)?>'].createItem();"><?=htmlspecialcharsbx(GetMessage('CRM_STATUS_LIST_ADD'))?></span>
</div>

<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmFieldMultiEditor.create(
				'<?= CUtil::addslashes($editorID)?>',
				'<?= CUtil::addslashes($arResult['FM_MNEMONIC'])?>',
				'<?= CUtil::addslashes($arResult['TYPE_ID'])?>',
				<?= CUtil::PhpToJSObject($arResult['TYPE_BOX']) ?>,
				BX('<?= CUtil::addslashes($editorID)?>')
			);
		}
	);
</script>