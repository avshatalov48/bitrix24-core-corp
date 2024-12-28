<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Intranet;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
$isCompositeMode = defined("USE_HTML_STATIC_CACHE") ? true : false;
$arAllItemsCounters = array();

if (empty($arResult))
{
	return;
}

$getLink = function ($item): string
{
	if (isset($item["PARAMS"]["real_link"]) && is_string($item["PARAMS"]["real_link"]))
	{
		$curLink = $item["PARAMS"]["real_link"];
	}
	else
	{
		$curLink = isset($item["LINK"]) && is_string($item["LINK"]) ? $item["LINK"] : '';
	}

	if (preg_match("~^".SITE_DIR."index\\.php~i", $curLink))
	{
		$curLink = SITE_DIR;
	}
	elseif (isset($item["PARAMS"]["onclick"]) && !empty($item["PARAMS"]["onclick"]))
	{
		$curLink = "";
	}


	if (preg_match("~".SITE_DIR."online\/~i", $curLink))
	{
		$curLink = 'bx://v2/' . $_SERVER['SERVER_NAME'] . '/chat/';
	}

	return $curLink;
};

$getClass = function ($item, $counterId, $counter, $isCompositeMode): string
{
	$itemId = $item["PARAMS"]["menu_item_id"];
	$itemClass = "menu-item-block";
	$isCustomItem = preg_match("/^[0-9]+$/", $itemId) === 1;
	$isCustomSection =
		isset($item['PARAMS']['is_custom_section'])
			? (bool)$item['PARAMS']['is_custom_section']
			: false
	;
	if (!$isCustomItem)
	{
		$itemClass .= " ".str_replace("_", "-", $itemId);
	}
	if ($item["ITEM_TYPE"] !== "default" || $isCustomItem || $isCustomSection)
	{
		$itemClass .= " menu-item-no-icon-state";
	}
	if ($isCompositeMode === false && $counter > 0 && $counterId <> '')
	{
		$itemClass .= " intranet__desktop-menu_item_counters";
	}

	return $itemClass;
};

$getCounterId = function($item): string
{
	$counterId = "";
	if (array_key_exists("counter_id", $item["PARAMS"]) && $item["PARAMS"]["counter_id"] <> '')
	{
		switch ($item['PARAMS']['counter_id'])
		{
			case 'live-feed':
				$counterId = \CUserCounter::LIVEFEED_CODE;
				break;
			default:
				$counterId = $item['PARAMS']['counter_id'];
		}
	}

	return $counterId;
};

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.icon-set.main',
	'ui.icon-set.crm',
	'ui.icon-set.actions',
	'ui.icons',
	'main.popup',
	'ui.counter',
	'im.v2.lib.desktop-api',
	'intranet.theme_picker',
]);

Bitrix\Intranet\Integration\Templates\Bitrix24\ThemePicker::getInstance()->showHeadAssets();

$imBarExists =
	\Bitrix\Main\Loader::includeModule('im') &&
	CBXFeatures::IsFeatureEnabled('WebMessenger') &&
	!defined('BX_IM_FULLSCREEN');

//These settings are set in intranet.configs
$siteLogo = Intranet\Portal::getInstance()->getSettings()->getLogo();
$siteTitle = Intranet\Portal::getInstance()->getSettings()->getTitle();

$siteTitle = htmlspecialcharsbx($siteTitle);
$siteUrl = htmlspecialcharsbx(SITE_DIR);
$logo24 = Intranet\Util::getLogo24();

$APPLICATION->ShowViewContent('im');
$APPLICATION->ShowViewContent('im-fullscreen');
?>

	<div class="intranet__desktop-menu_wrapper">
		<div class="intranet__desktop-menu_inner">
			<div class="intranet__desktop-menu_header">
				<a href="<?=$siteUrl?>" title="<?=GetMessage("BITRIX24_LOGO_TOOLTIP")?>" class="intranet__desktop-menu_logo-link">
					<?
					if (isset($siteLogo['src']))
					{
					?>
						<span class="intranet__desktop-menu_img">
							<img class="intranet__desktop-menu_img-value" src="<?=$siteLogo['src']?>"
								 <? if (isset($siteLogo['srcset']))
								 {?>
									 srcset="<?=$siteLogo['srcset']?> 2x"
								 <?}?>
								 alt="Logo">
						</span>
					<?}
					else
					{?>
						<span class="intranet__desktop-menu_logo-inner">
							<span class="intranet__desktop-menu_logo-text"><?=$siteTitle?></span>
							<?
							if ($logo24)
							{?>
							<span class="intranet__desktop-menu_logo-num"><?=$logo24?></span>
							<?}?>
						</span>
					<?}?>
				</a>
				<div class="intranet__desktop-menu_user">
					<span class="intranet__desktop-menu_user-add" onclick="BXDesktopSystem.AccountAddForm()"></span>
				</div>
			</div>
			<div class="intranet__desktop-menu_main">
				<div class="intranet__desktop-menu_section menu-items-block" id="menu-items-block">
					<div class="intranet__desktop-menu_section">
						<div class="intranet__desktop-menu_subject">
							<div class="intranet__desktop-menu_title"><?=Loc::getMessage("MENU_YOUR_BITRIX24")?></div>
							<div class="intranet__desktop-menu_show-more" data-block-hidden=""><?=Loc::getMessage("MENU_SHOW_ALL")?></div>
						</div>
						<ul class="intranet__desktop-menu_list menu-items">
							<?
							foreach ($arResult["ITEMS"]['open'] as $item)
							{
								$itemId = $item["PARAMS"]["menu_item_id"];
								$counterId = $getCounterId($item);
								$counter = array_key_exists($counterId, $arResult["COUNTERS"]) ? $arResult["COUNTERS"][$counterId] : 0;
								$itemClass = $getClass($item, $counterId, $counter, $isCompositeMode);
								$curLink = $getLink($item);
							?>
								<li
									class="intranet__desktop-menu_item <?= $itemClass ?>"
									title="<?= $item["TEXT"] ?>"
									data-id="<?= $itemId ?>"
									data-type="<?=$item["ITEM_TYPE"]?>"
									data-role="item"
									data-storage="<?= $item['PARAMS']['storage'] ?? '' ?>"
									data-counter-id="<?=$counterId?>"
									data-link="<?=$curLink?>"
								>
									<a class="intranet__desktop-menu_item-link" href="<?= $curLink ?>">
										<div class="intranet__desktop-menu_item-icon">
											<span class="menu-item-icon"></span>
										</div>
										<span class="intranet__desktop-menu_item-title menu-item-link-text"><?= $item["TEXT"] ?></span>
										<?
										if ($counterId <> '')
										{
											if ($counter > 0)
											{
												$arAllItemsCounters[$counterId] = $counter;
											}

											$valueCounter = "";
											$badgeCounter = "";
											if ($isCompositeMode === false)
											{
												$valueCounter = intval($counter);
												$badgeCounter =  $counter > 99 ? "99+" : $counter;
											}
										?>
											<div class="ui-counter ui-counter-md ui-counter-danger">
												<div
													class="ui-counter-inner"
													data-role="counter"
													data-counter-value="<?=$valueCounter?>"
													id="menu-counter-<?= mb_strtolower($item["PARAMS"]["counter_id"])?>"><?=$badgeCounter?></div>
											</div>
										<?
										}
										?>
									</a>
								</li>
							<?
							}
							?>
							<ul class="intranet__desktop-menu_list menu-items --toggle">
								<?
								foreach ($arResult["ITEMS"]['hidden'] as $item)
								{
									$counterId = $getCounterId($item);
									$counter = array_key_exists($counterId, $arResult["COUNTERS"]) ? $arResult["COUNTERS"][$counterId] : 0;
									$itemClass = $getClass($item, $counterId, $counter, $isCompositeMode);
									$curLink = $getLink($item);
								?>
									<li
										class="intranet__desktop-menu_item <?= $itemClass ?>"
										title="<?= $item["TEXT"] ?>"
										data-type="<?=$item["ITEM_TYPE"]?>"
										data-role="item"
									>
										<a class="intranet__desktop-menu_item-link" href="<?= $curLink ?>">
											<div class="intranet__desktop-menu_item-icon">
												<span class="menu-item-icon"></span>
											</div>
											<span class="intranet__desktop-menu_item-title menu-item-link-text"><?= $item["TEXT"] ?></span>
											<?
											if ($counterId <> '')
											{
												if ($counter > 0)
												{
													$arAllItemsCounters[$counterId] = $counter;
												}

												$valueCounter = "";
												$badgeCounter = "";
												if ($isCompositeMode === false)
												{
													$valueCounter = intval($counter);
													$badgeCounter =  $counter > 99 ? "99+" : $counter;
												}
												?>
												<div class="ui-counter ui-counter-md ui-counter-danger">
													<div
														class="ui-counter-inner"
														data-role="counter"
														data-counter-value="<?=$valueCounter?>"
														id="menu-counter-<?= mb_strtolower($item["PARAMS"]["counter_id"])?>"><?=$badgeCounter?></div>
												</div>
												<?
											}
											?>
										</a>
									</li>
								<?
								}
								?>
							</ul>
						</ul>
					</div>
				</div>
				<div class="intranet__desktop-menu_section">
					<div class="intranet__desktop-menu_subject">
						<div class="intranet__desktop-menu_title"><?=Loc::getMessage("MENU_NEWLY_OPENED")?></div>
					</div>
					<ul class="intranet__desktop-menu_list" id="history-items">

					</ul>
				</div>
			</div>
		</div>
	</div>

	<div class="intranet__desktop-menu_popup">
		<ul class="intranet__desktop-menu_popup-list">
			<li class="intranet__desktop-menu_popup-item --add" onclick="BXDesktopSystem.AccountAddForm()">
				<span class="intranet__desktop-menu_user-add"></span>
				<span class="intranet__desktop-menu_popup-text"><?=Loc::getMessage("MENU_ADD")?></span>
			</li>
		</ul>
	</div>

<script>
	BX.message({
		"MENU_ACCOUNT_POPUP_CONNECT": '<?=GetMessageJS("MENU_ACCOUNT_POPUP_CONNECT")?>',
		"MENU_ACCOUNT_POPUP_DISCONNECT": '<?=GetMessageJS("MENU_ACCOUNT_POPUP_DISCONNECT")?>',
		"MENU_ACCOUNT_POPUP_REMOVE": '<?=GetMessageJS("MENU_ACCOUNT_POPUP_REMOVE")?>',
	});

	BX.ready(function() {
		const node = document.querySelector('[data-block-hidden]');
		const SHOW_CLASS = '--show';

		BX.bind(node, 'click', (event) => {
			const parentNode = event.target.closest('.intranet__desktop-menu_section');
			const hiddenBlock = parentNode.querySelector('.--toggle');
			let height = hiddenBlock.scrollHeight;

			if (!hiddenBlock.classList.contains('--show')) {
				hiddenBlock.style.height = height + 'px';
				hiddenBlock.classList.add(SHOW_CLASS);
				node.classList.add(SHOW_CLASS);
			} else {
				hiddenBlock.style.height = 0;
				hiddenBlock.classList.remove(SHOW_CLASS);
				node.classList.remove(SHOW_CLASS);
			}
		});

		<?
		$counters = $isCompositeMode ? \CUtil::PhpToJSObject($arAllItemsCounters) : '{}';
		?>
		BX.Intranet.DescktopLeftMenu = new BX.Intranet.DesktopMenu(<?=\CUtil::PhpToJSObject($arAllItemsCounters)?>);
		BX.Intranet.DescktopLeftMenu.updateCounters(<?=$counters?>);
	});
</script>

<?
if ($imBarExists)
{
	//This component changes user counters on the page.
	//User counters can be changed in the left menu (left_vertical template).
	$APPLICATION->IncludeComponent(
		'bitrix:im.messenger',
		'',
		[
			'CONTEXT' => 'POPUP-FULLSCREEN',
			'RECENT' => 'Y',
			'PATH_TO_SONET_EXTMAIL' => \Bitrix\Main\Config\Option::get('intranet', 'path_mail_client', SITE_DIR . 'mail/'),
		],
		false,
		['HIDE_ICONS' => 'Y']
	);
}
?>