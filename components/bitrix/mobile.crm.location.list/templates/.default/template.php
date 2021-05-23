<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

$UID = $arResult['UID'];
$searchContainerID = $UID.'_search';
$dispatcherData = array();
?>
<div id="<?=htmlspecialcharsbx($searchContainerID)?>" class="crm_search">
	<div class="crm_input_container">
		<span class="crm_lupe"></span>
		<input class="crm_search_input" type="text" placeholder="<?=htmlspecialcharsbx(GetMessage('M_CRM_LOCATION_LIST_SEARCH_PLACEHOLDER'))?>" />
	</div>
	<a class="crm_button"><?=htmlspecialcharsbx(GetMessage('M_CRM_LOCATION_LIST_SEARCH_BUTTON'))?></a>
	<span class="crm_clear"></span>
</div>
<div id="<?=htmlspecialcharsbx($UID)?>" class="crm_wrapper">
	<ul class="crm_location_list crm_itemcategory"><?
		foreach($arResult['ITEMS'] as &$item):
			$dispatcherData[] = $item;

			$name = isset($item['NAME']) ? $item['NAME'] : '';
			$regionName = isset($item['REGION_NAME']) ? $item['REGION_NAME'] : '';
			$countryName = isset($item['COUNTRY_NAME']) ? $item['COUNTRY_NAME'] : '';

			$title = '';
			$legend = '';

			if($name !== '')
			{
				$title = $name;
				if($regionName !== '')
				{
					$legend = $regionName;
				}
				if($countryName !== '')
				{
					if($legend !== '')
					{
						$legend .= ', ';
					}
					$legend .= $countryName;
				}
			}
			elseif($regionName !== '')
			{
				$title = $regionName;
				if($countryName !== '')
				{
					$legend = $countryName;
				}
			}
			elseif($countryName !== '')
			{
				$title = $countryName;
			}
		?><li class="crm_itemcategory_item" data-entity-id="<?=htmlspecialcharsbx($item['ID'])?>">
			<div class="crm_itemcategory_title"><?=htmlspecialcharsbx($title)?></div><?
			if($legend !== ''):
			?><div class="crm_category_desc">
				<span><?=htmlspecialcharsbx($legend)?></span>
			</div><?
			endif;
			?><div class="clb"></div>
		</li>
		<?endforeach;?>
		<?unset($item);?>
	</ul>
</div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmMobileContext.getCurrent().enableReloadOnPullDown(
				{
					pullText: "<?=GetMessage('M_CRM_LOCATION_LIST_PULL_TEXT')?>",
					downText: "<?=GetMessage('M_CRM_LOCATION_LIST_DOWN_TEXT')?>",
					loadText: "<?=GetMessage('M_CRM_LOCATION_LIST_LOAD_TEXT')?>"
				}
			);

			var uid = "<?=CUtil::JSEscape($UID)?>";
			var dispatcher = BX.CrmEntityDispatcher.create(
				uid,
				{
					typeName: "LOCATION",
					data: <?=CUtil::PhpToJSObject($dispatcherData)?>,
					serviceUrl: ""
				}
			);

			var view = BX.CrmLocationListView.create(
				uid,
				{
					dispatcher: dispatcher,
					contextId: "<?=CUtil::JSEscape($arResult['CONTEXT_ID'])?>",
					wrapperId: uid,
					searchContainerId: '<?=CUtil::JSEscape($searchContainerID)?>',
					searchPageUrl: "<?=CUtil::JSEscape($arResult['SEARCH_PAGE_URL'])?>",
					serviceUrl: "<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>",
					mode: "<?=CUtil::JSEscape($arResult['MODE'])?>"
				}
			);
			view.initializeFromExternalData();
		}
	);
</script>
