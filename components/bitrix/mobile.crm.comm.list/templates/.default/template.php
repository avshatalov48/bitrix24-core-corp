<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

$UID = $arResult['UID'];

$typeID = $arResult['TYPE_ID'];
$typeInfos = CCrmFieldMulti::GetEntityTypes();
$typeInfo = isset($typeInfos[$typeID]) ? $typeInfos[$typeID] : null;

$imageUrl = isset($arResult['ENTITY_IMAGE_URL']) ? $arResult['ENTITY_IMAGE_URL'] : '';
if($imageUrl === '')
{
	$imageID = $arResult['ENTITY_IMAGE_ID'];
	$imageInfo = $imageID > 0
		? CFile::ResizeImageGet($imageID, array('width' => 55, 'height' => 55), BX_RESIZE_IMAGE_EXACT)
		: null;

	if($imageInfo && isset($imageInfo['src']))
	{
		$imageUrl = $imageInfo['src'];
	}
}

$commActionTitle = GetMessage($typeID === 'EMAIL' ? 'M_CRM_COMM_ACTION_MAIL' : 'M_CRM_COMM_ACTION_CALL');
?>
<div id="<?=htmlspecialcharsbx($UID)?>" class="crm_wrapper">
<div class="crm_block_container" style="padding:9px 11px;">
	<div style="crm_card_tel">
		<div class="crm_card_image">
			<?if($imageUrl !== ''):?>
				<img width="55px" height="55px" src="<?=htmlspecialcharsbx($imageUrl)?>" />
			<?endif;?>
		</div>
		<div class="crm_card_name_tel">
			<?=htmlspecialcharsbx($arResult['ENTITY_TITLE'])?>
		</div>
		<div class="crm_card_description_tel">
			<?=htmlspecialcharsbx($arResult['ENTITY_LEGEND'])?>
		</div>
		<div class="clb"></div>
		<hr/>
		<?$c = 0;?>
		<?foreach($arResult['ITEMS'] as &$item):?>
			<?if($c !== 0):?>
				<hr/>
			<?endif;?>
			<div class="crm_tel_block">
				<?if($item['URL'] !== ''):?>
				<a href="<?=htmlspecialcharsbx($item['URL'])?>" class="crm_tel_call"><?=htmlspecialcharsbx($commActionTitle)?></a>
				<?endif;?>
				<div class="crm_tel_value"><?=htmlspecialcharsbx($item['VALUE'])?></div>
				<div class="crm_tel_desc"><?=isset($typeInfo[$item['VALUE_TYPE']]['SHORT']) ? htmlspecialcharsbx($typeInfo[$item['VALUE_TYPE']]['SHORT']) : ''?></div>
				<div class="clb"></div>
			</div>
			<?$c++;?>
		<?endforeach;?>
		<?unset($item);?>
	</div>
	<div class="clb"></div>
</div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmMobileContext.getCurrent().enableReloadOnPullDown(
				{
					pullText: '<?=GetMessageJS('M_CRM_COMM_VIEW_PULL_TEXT')?>',
					downText: '<?=GetMessageJS('M_CRM_COMM_VIEW_DOWN_TEXT')?>',
					loadText: '<?=GetMessageJS('M_CRM_COMM_VIEW_LOAD_TEXT')?>'
				}
			);
		}
	);
</script>

