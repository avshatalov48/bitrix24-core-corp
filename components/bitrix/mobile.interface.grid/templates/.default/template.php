<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Text\HtmlFilter;

CJSCore::Init(array('mobile_interface'));
?>

<?if ($arParams["SHOW_SEARCH"] == "Y"):?>
<div class="mobile-grid-field mobile-grid-field-search">
	<img src="<?=$this->GetFolder()?>/images/icon-search2x.png" srcset="<?=$this->GetFolder()?>/images/icon-search2x.png 2x" alt="">
	<form>
		<input class="mobile-grid-field-data" type="text" placeholder="<?=GetMessage("M_GRID_SEARCH")?>" data-role="search-input" value="">
		<span class="mobile-grid-field-search-close" data-role="search-cancel"></span>
	</form>
</div>
<?endif?>

<?
if (is_array($arResult["SECTIONS"]) && !empty($arResult["SECTIONS"]))
{
?>
	<div class="mobile-grid-field-folder-list" id="bx-mobile-sections" data-role="mobile-grid-sections">
	<?foreach ($arResult["SECTIONS"] as $item):?>
		<div class="mobile-grid-field-folder" <?if (isset($item["ONCLICK"])):?>onclick="<?=$item["ONCLICK"] ?>"<?endif?>>
			<div class="mobile-grid-field-item-icon-folder"><img src="<?=$this->GetFolder()?>/images/icon-folder.png" srcset="<?=$this->GetFolder()?>/images/icon-folder.png 2x" alt=""></div>
			<div class="mobile-grid-field-item-folder"><?=$item['TITLE']?></div>
		</div>
	<?endforeach?>
	</div>
<?
}
?>

<div class="mobile-grid<?=(!(is_array($arResult["ITEMS"]) && !empty($arResult["ITEMS"])) ? " mobile-grid-empty" : "")?>" data-role="mobile-grid" id="bx-mobile-grid">
<?
if (isset($_POST["ajax"]) && $_POST["ajax"] == "Y" || isset($_REQUEST["search"]) && $arParams["SHOW_SEARCH"] == "Y")
{
	$APPLICATION->RestartBuffer();
}

if (is_array($arResult["ITEMS"]) && !empty($arResult["ITEMS"]))
{
	foreach ($arResult["ITEMS"] as $item)
	{
		$itemType = isset($item["TYPE"]) ? $item["TYPE"] : "";

		switch ($itemType)
		{
			case "HR":
				?>
				<div class="mobile-grid-change" data-role="mobile-grid-item">
					<?=$item["VALUE"]?>
				</div>
				<?
				break;
			default:
				?>
				<div class="mobile-grid-item" data-role="mobile-grid-item" data-id="<?=$item["DATA_ID"]?>">
					<div class="mobile-grid-field" <?if (isset($item["ONCLICK"])):?>onclick="<?=$item["ONCLICK"] ?>"<?endif?>>
						<?if (isset($item["ICON_HTML"])):?>
							<?=$item["ICON_HTML"]?>
						<?endif?>
						<span class="mobile-grid-field-lead-title"><span class="mobile-grid-field-lead-title-arrow"></span><?=$item["TITLE"] ? $item["TITLE"] : "&nbsp;"?></span>
					</div>

					<?
					if (is_array($arResult["FIELDS"]) && !empty($arResult["FIELDS"]))
					{
						foreach ($arResult["FIELDS"] as $key => $field)
						{
							if (!isset($item["FIELDS"][$field["id"]]) || empty($item["FIELDS"][$field["id"]]))
								continue;

							switch ($field["type"])
							{
								case "PHONE":
									?>
									<div class="mobile-grid-field mobile-grid-field-phone" onclick="BX.MobileTools.phoneTo('<?=$item["FIELDS"][$field["id"]]?>')">
										<img class="mobile-grid-field-icon" src="<?=$this->GetFolder()?>/images/icon-phone2x.png" srcset="<?=$this->GetFolder()?>/images/icon-phone2x.png 2x" alt="">
										<a class="mobile-grid-field-data" href="javascript:void();"><?=$item["FIELDS"][$field["id"]]?></a>
										<span class="mobile-grid-field-textarea-title"><?=htmlspecialcharsbx($field["name"])?></span>
									</div>
									<?
									break;
								case "EMAIL":
									?>
									<a href="mailto:<?=$item["FIELDS"][$field["id"]]?>" class="mobile-grid-field mobile-grid-field-mail" style="display: block">
										<img class="mobile-grid-field-icon" src="<?= $this->GetFolder() ?>/images/icon-email2x.png" srcset="<?= $this->GetFolder() ?>/images/icon-email2x.png 2x" alt="">
										<span class="mobile-grid-field-data"><?=$item["FIELDS"][$field["id"]]?></span>
										<span class="mobile-grid-field-textarea-title"><?=htmlspecialcharsbx($field["name"])?></span>
									</a>
									<?
									break;
								case "HTML":
									?>
									<?=$item["FIELDS"][$field["id"]]?>
									<?
									break;
								default:
									?>
									<div class="mobile-grid-field mobile-grid-field-name">
										<span class="mobile-grid-field-data">
											<?php
												$isFirst = true;
												$fieldValue = $item['FIELDS'][$field['id']];
												if(!empty($field['USER_TYPE']['USE_FIELD_COMPONENT']))
												{
													$uf = [
														'USER_TYPE_ID' => $field['TYPE_ID'],
														'VALUE' => $fieldValue,
														'SETTINGS' => $field['SETTINGS']
													];

													$params = [
														'mediaType' => \Bitrix\Main\Component\BaseUfComponent::MEDIA_TYPE_MOBILE,
														'mode' => \Bitrix\Main\Component\BaseUfComponent::MODE_DEFAULT
													];

													print (new \Bitrix\Main\UserField\Renderer($uf, $params))->render();
												}
												elseif(is_array($fieldValue))
												{
													foreach ($fieldValue as $fieldValueItem)
													{
														if (!$isFirst){
															print '<br>';
														}
														$isFirst = false;
														print $fieldValueItem;
													}
												}
												else
												{
													print $fieldValue;
												}
											?>
										</span>
										<span class="mobile-grid-field-textarea-title">
											<?= HtmlFilter::encode($field['name']) ?>
										</span>
									</div>
									<?
									break;
							}
						}
					}
					?>

					<?if (isset($item["ACTIONS"]) && !empty($item["ACTIONS"])):?>
						<div class="mobile-grid-action-panel">
							<?foreach ($item["ACTIONS"] as $action):?>
								<a href="javascript:void(0)" <?if ($action["HIDDEN"]):?>style="display: none;" <?endif;?><?if ($action["ID"]): ?>id="<?=$action["ID"]?>" <?endif;?><?if ($action["DISABLE"]):?>class="mobile-grid-action-dis"<?else:?>onclick="<?=$action["ONCLICK"]?>"<?endif?>>
									<?=$action["TEXT"]?>
								</a>
							<?endforeach?>
						</div>
					<?endif?>
				</div>
		<?
		}
	}
}

if (isset($_POST["ajax"]) && $_POST["ajax"] == "Y" || isset($_REQUEST["search"]) && $arParams["SHOW_SEARCH"] == "Y")
{
	?>
	<script>
		BX.Mobile.Grid.pagesNum = '<?=$arParams["NAV_PARAMS"]["PAGE_NAVCOUNT"] ? $arParams["NAV_PARAMS"]["PAGE_NAVCOUNT"] : 1?>';
	</script>
	<?
	CMain::FinalActions();
	die();
}

if (empty($arResult["ITEMS"]) && (!isset($arResult["SECTIONS"]) || empty($arResult["SECTIONS"])))
{
	?>
	<div class="mobile-grid-empty-search">
		<div class="mobile-grid-empty-search-box">
			<div class="mobile-grid-empty-search-text"><?=GetMessage("M_GRID_EMPTY_LIST")?></div>
		</div>
	</div>
	<?
}
?>

</div>
<?
$arJsParams = array(
	"pagerName" => $arParams["NAV_PARAMS"]["PAGER_PARAM"],
	"pagesNum" => $arParams["NAV_PARAMS"]["PAGE_NAVCOUNT"],
	"ajaxUrl" => $arParams["AJAX_PAGE_PATH"],
	"sortEventName" => $arParams["SORT_EVENT_NAME"],
	"fieldsEventName" => $arParams["FIELDS_EVENT_NAME"],
	"filterEventName" => $arParams["FILTER_EVENT_NAME"],
	"reloadGridAfterEvent" => $arParams["RELOAD_GRID_AFTER_EVENT"]
);
?>

<script>
	app.pullDown({
		enable:   true,
		pulltext: '<?=GetMessageJS('M_GRID_PULL_TEXT');?>',
		downtext: '<?=GetMessageJS('M_GRID_DOWN_TEXT');?>',
		loadtext: '<?=GetMessageJS('M_GRID_LOAD_TEXT');?>',
		callback: function()
		{
			app.reload();
		}
	});

	BX.message({
		"M_GRID_EMPTY_SEARCH" : "<?=GetMessageJS("M_GRID_EMPTY_SEARCH")?>"
	});

	BX.Mobile.Grid.init(<?=CUtil::PhpToJSObject($arJsParams)?>);

	<?if ($arParams["SHOW_SEARCH"] == "Y"):?>
		BX.Mobile.Grid.searchInit();
	<?endif?>
</script>