<?
	use Bitrix\Main\Localization\Loc;
	/** @var CMain $APPLICATION */

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$rnd = "or".randString(4);
$mapTypesMenu = array();

foreach($arResult['MAP_TYPES_LIST'] as $type => $name)
{
	$mapTypesMenu[] = array(
		'TEXT' => $name,
		'ONCLICK' => 'BX.Sale.Store.Choose.setChangeMapType("'.$type.'");',
		'CHECKED' => $type == $arParams['MAP_TYPE']
	);
}

?>
<link rel="stylesheet" type="text/css" href="/bitrix/css/main/font-awesome.css">

<?if(strlen($arParams['TITLE']) > 0):?>
	<div style="font-size: 13px;color: #c0c0c0;line-height: 1.5em;">
		<span style="padding-left:3px;"><?=htmlspecialcharsbx($arParams['TITLE'])?></span>
		<?if($arParams['SHOW_MAP_TYPE_SETTINGS'] == 'Y'):?>
			<div id="change_map_type<?=$arParams['INDEX']?>" style="float:right;"><i class="fa fa-cog" aria-hidden="true" ></i></div>
		<?endif;?>
	</div>
<?endif;?>

<table class="data">
	<tr>
		<td class="map">
			<div class="view_map">
				<?if($arParams['MAP_TYPE'] == \CSaleStoreChooseComponent::MAP_TYPE_YANDEX):?>
					<?$APPLICATION->IncludeComponent(
						'bitrix:map.yandex.view',
						'.default',
						Array(
							'INIT_MAP_TYPE' => 'MAP',
							'MAP_DATA' => $arResult['LOCATION'],
							'MAP_WIDTH' => 230,
							'MAP_HEIGHT' => 230,
							'CONTROLS' => $arParams['MAP']['CONTROLS'],
							'OPTIONS' => $arParams['MAP']['OPTIONS'],
							'MAP_ID' => $rnd,
							'ONMAPREADY' => 'onMapReady'.$rnd,
							'DEV_MODE' => 'Y'
						)
					);?>
				<?elseif($arParams['MAP_TYPE'] == \CSaleStoreChooseComponent::MAP_TYPE_GOOGLE):?>
					<?$APPLICATION->IncludeComponent(
						'bitrix:map.google.view',
						'.default',
						Array(
							'INIT_MAP_TYPE' => 'ROADMAP',
							'MAP_DATA' => $arResult['LOCATION'],
							'MAP_WIDTH' => 230,
							'MAP_HEIGHT' => 230,
							'CONTROLS' => $arParams['MAP']['CONTROLS'],
							'OPTIONS' => $arParams['MAP']['OPTIONS'],
							'MAP_ID' => $rnd,
							'ONMAPREADY' => 'onMapReady'.$rnd,
							'DEV_MODE' => 'Y'
						)
					);?>
				<?endif;?>
			</div>
		</td>
	</tr>
	</table>
			<?
				$menu = array();
			?>
			<div class="ora-storelist">
				<table id="store_table<?=$arParams["INDEX"]?>" class="store_table">
					<?
					$i = 1;
					$countCount = count($arResult["STORES"]);
					$arDefaultStore = array_shift(array_values($arResult["STORES"]));

					foreach ($arResult["STORES"] as $val)
					{
						$result = '';
						$checked = ($val["ID"] != $arParams["SELECTED_STORE"]) ? "style='display:none;'" : "";
						?>
						<tr class="store_row" id="row<?=$arParams["INDEX"]?>_<?=$val["ID"]?>" <?=$checked?>>
							<?
							if ($showImages)
							{
								?>
								<td class="image_cell">
									<div class="image">
										<?
										if (intval($val["IMAGE_ID"]) > 0):
											?>
											<a href="<?=$val["IMAGE_URL"]?>" target="_blank"><?=$val["IMAGE"]?></a>
										<?
										else:
											?>
											<img src="<?=$templateFolder?>/images/no_store.png" />
										<?
										endif;
										?>
									</div>
								</td>
							<?
							}
							?>
							<td class="<?=($countCount != $i)?"lilne":"last"?>">
								<label for="store<?=$arParams["INDEX"]?>_<?=$val["ID"]?>">
									<div class="adres"><?=htmlspecialcharsbx($val["ADDRESS"])?></div>
									<div class="phone"><?=htmlspecialcharsbx($val["PHONE"])?></div>
									<?
										$result .= '<span class="adres"><b>'.htmlspecialcharsbx($val["TITLE"]).':</b> '.htmlspecialcharsbx($val["ADDRESS"]).'</span>';
										$menu[] = array(
											'HTML' => $result,
											'ONCLICK' => 'BX.Sale.Store.Choose.setChangeStore("'.$val["ID"].'", "'.$rnd.'");'
										);
									?>
									<div class="full_store_info" id="full_store_info" onclick="BX.Sale.Store.Choose.showFullInfo(this);"><?=Loc::getMessage('SALE_SSC_ADD_INFO')?></div>
									<div style="display: none;">
										<div class="email"><a href="mailto:<?=htmlspecialcharsbx($val["EMAIL"])?>"><?=htmlspecialcharsbx($val["EMAIL"])?></a></div>
										<div class="shud"><?=htmlspecialcharsbx($val["SCHEDULE"])?></div>
										<div class="desc"><?=GetMessage('SALE_SSC_DESC');?>: <?=htmlspecialcharsbx($val["DESCRIPTION"])?></div>
									</div>
								</label>
							</td>
						</tr>
						<?
						$i++;
					}
					?>
				</table>
				<div class="block_change_store">
					<div><b><?=Loc::getMessage('SALE_SSC_STORE_EXPORT')?>:</b></div>
					<?
						$selectedStoreId = $arParams["SELECTED_STORE"];
						if ((int)$selectedStoreId <= 0)
							$selectedStoreId = $arDefaultStore["ID"];
					?>
					<div id="store_name<?=$arParams["INDEX"]?>"><?=htmlspecialcharsbx($arResult["STORES"][$selectedStoreId]['TITLE'])?></div>
					<?
					if ($arParams["FORM"] !== "view")
					{
						?>
						<span id="change_store<?=$arParams["INDEX"]?>" class="change_store"><?=Loc::getMessage('SALE_SSC_CHANGE')?></span>
						<?
					}
					?>
				</div>
			</div>
			<input type="hidden" name="<?=$arParams["INPUT_NAME"]?>" id="<?=$arParams["INPUT_ID"]?>" value="<?=$arParams["SELECTED_STORE"]?>" />

<script type="text/javascript">
	BX.loadScript('<?=$templateFolder.'/script.js?'.mktime()?>', function () {
		BX.ready(function () {
			BX.message({
				"SALE_SSC_GOOGLE_MAP_INFO": "<?=Loc::getMessage(
					"SALE_SSC_GOOGLE_MAP_INFO",
					array(
						'#A1#' => '<a href=\"https://developers.google.com/maps/documentation/javascript/get-api-key\"> https://developers.google.com/maps/documentation/javascript/get-api-key</a>',
						'#A2#' => '<a href=\"/bitrix/admin/settings.php?lang='.LANGUAGE_ID.'&mid=fileman\">',
						'#A3#' => '</a>'
					)
				)?>",
				"SALE_SSC_GOOGLE_MAP_INFO_TITLE": "<?=Loc::getMessage("SALE_SSC_GOOGLE_MAP_INFO_TITLE")?>",
				"SALE_SSC_DIALOG_CLOSE": "<?=Loc::getMessage("SALE_SSC_DIALOG_CLOSE")?>",
				"SALE_SSC_MAP_TYPE_CHANGE_ERROR": "<?=Loc::getMessage("SALE_SSC_MAP_TYPE_CHANGE_ERROR")?>"
			});

			BX.Sale.Store.Choose.ajaxUrl = '<?=$arResult['AJAX_URL']?>';
			BX.Sale.Store.Choose.deliveryStores = <?=CUtil::PhpToJSObject($arResult["STORES"]);?>;
			BX.Sale.Store.Choose.index = "<?=$arParams["INDEX"]?>";
			BX.Sale.Store.Choose.mapType = "<?=$arParams['MAP_TYPE']?>";
			BX.Sale.Store.Choose.inputId = "<?=$arParams["INPUT_ID"]?>";
		});
	});

	new BX.COpener({
		DIV: 'change_store<?=$arParams["INDEX"]?>',
		MENU: <?=CUtil::PhpToJSObject($menu);?>
	});

	new BX.COpener({
		DIV: 'change_map_type<?=$arParams['INDEX']?>',
		MENU: <?=CUtil::PhpToJSObject($mapTypesMenu);?>
	});

	function onMapReady<?=$rnd;?>()
	{
		setTimeout(function () {
				<?if ($arParams["SELECTED_STORE"] > 0):?>
					BX.Sale.Store.Choose.setChangeStore('<?=$arParams["SELECTED_STORE"];?>', '<?=$rnd?>');
				<?else:?>
					var keysStores = Object.keys(BX.Sale.Store.Choose.deliveryStores),
						selectedStore = keysStores[0];
					BX.Sale.Store.Choose.setChangeStore(selectedStore, '<?=$rnd?>');
				<?endif;?>
			},
			1000
		);
	}

</script>