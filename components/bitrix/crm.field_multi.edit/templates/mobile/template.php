<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!function_exists('__CrmFieldMultiEditRenderItem'))
{
	function __CrmFieldMultiEditRenderItem($item, $mnemonic, $typeID, $arReferenceTypes, $editorID)
	{
		$itemID = isset($item['ID']) ? $item['ID'] : '';
		$itemVal = isset($item['VALUE']) ? $item['VALUE'] : '';
	?>
		<div class="mobile-grid-field-contact-info" data-role="bx-crm-edit-fm-item">
			<div class="mobile-grid-field-select">
				<input type="hidden"
					   value="<?=$item['VALUE_TYPE']?>"
					   name="<?=CUtil::JSEscape($mnemonic).'['.htmlspecialcharsbx($typeID).']['.htmlspecialcharsbx($itemID).'][VALUE_TYPE]'?>"
					   id="<?=CUtil::JSEscape($mnemonic).'['.htmlspecialcharsbx($typeID).']['.htmlspecialcharsbx($itemID).'][VALUE_TYPE]'?>"
				/>
				<a href="javascript:void(0)" class="mobile-grid-field-contact-info-title" onclick="BX.CrmFieldMultiEditor.items['<?=CUtil::addslashes($editorID)?>'].showTypeSelector('<?=CUtil::JSEscape($mnemonic).'['.htmlspecialcharsbx($typeID).']['.htmlspecialcharsbx($itemID).'][VALUE_TYPE]'?>')"><?=$arReferenceTypes[$item['VALUE_TYPE']]?></a>
			</div>
			<div class="mobile-grid-field-text" style="padding-right: 50px">
				<input type="text"
					   data-role="entity-input-value"
					   name="<?=htmlspecialcharsbx($mnemonic)?>[<?=htmlspecialcharsbx($typeID)?>][<?=htmlspecialcharsbx($itemID)?>][VALUE]"
					   value="<?=htmlspecialcharsbx($itemVal)?>"
				/>
			</div>
			<del onclick="BX.CrmFieldMultiEditor.items['<?=CUtil::addslashes($editorID)?>'].deleteItem(this.parentNode);"></del>
		</div>
	<?
	}
}

global $APPLICATION;
$editorID = isset($arResult['EDITOR_ID']) ? $arResult['EDITOR_ID'] : uniqid("{$arResult['FM_MNEMONIC']}_{$arResult['TYPE_ID']}_");

$arReferenceNames = array();
$arReferenceTypes = array();
foreach($arResult['TYPE_BOX']["REFERENCE"] as $key=>$name)
{
	$arReferenceNames[$name] = $arResult['TYPE_BOX']["REFERENCE_ID"][$key];
	$arReferenceTypes[$arResult['TYPE_BOX']["REFERENCE_ID"][$key]] = $name;
}
?>
<div id="<?=htmlspecialcharsbx($editorID)?>">
	<?
	$valuCount = count($arResult['VALUES']);
	if(!empty($arResult['VALUES']))
	{
		foreach ($arResult['VALUES'] as &$arValue)
		{
			__CrmFieldMultiEditRenderItem($arValue, $arResult['FM_MNEMONIC'], $arResult['TYPE_ID'], $arReferenceTypes, $editorID);
		}
		unset($arValue);
	}
	?>
	<div class="mobile-grid-field-contact-info">
		<a href="javascript:void(0)" class="mobile-grid-button add" onclick="BX.CrmFieldMultiEditor.items['<?= CUtil::addslashes($editorID)?>'].createItem();"><?=htmlspecialcharsbx(GetMessage('CRM_STATUS_LIST_ADD'))?></a>
	</div>
</div>

<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmFieldMultiEditor.create(
				'<?= CUtil::addslashes($editorID)?>',
				'<?= CUtil::addslashes($arResult['FM_MNEMONIC'])?>',
				'<?= CUtil::addslashes($arResult['TYPE_ID'])?>',
				BX('<?= CUtil::addslashes($editorID)?>'),
				<?=CUtil::PhpToJSObject($arReferenceNames)?>,
				'<?=CUtil::JSEscape($arResult['TYPE_BOX']["REFERENCE_ID"][0])?>'
			);
		}
	);
</script>