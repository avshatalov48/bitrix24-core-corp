<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$this->addExternalCss(SITE_TEMPLATE_PATH."/css/sidebar.css");

$this->setFrameMode(true);

if (count($arResult["USERS"]) < 1)
	return;

$this->SetViewTarget("sidebar", 300);
?>

<div class="sidebar-widget sidebar-widget-birthdays">
	<div class="sidebar-widget-top">
		<div class="sidebar-widget-top-title"><?=GetMessage("WIDGET_BIRTHDAY_TITLE")?></div>
	</div>
	<?
	$i = 0;
	foreach ($arResult["USERS"] as $arUser):?>
	<a href="<?=$arUser["DETAIL_URL"]?>" class="sidebar-widget-item<?if(++$i == count($arResult["USERS"])):?> widget-last-item<?endif?><?if ($arUser["IS_BIRTHDAY"]):?> today-birth<?endif?>">
		<span class="user-avatar user-default-avatar"
			<?if (isset($arUser["PERSONAL_PHOTO"]["src"])):?>
				style="background: url('<?=$arUser["PERSONAL_PHOTO"]["src"]?>') no-repeat center; background-size: cover;"
			<?endif?>>
		</span>
		<span class="sidebar-user-info">
			<span class="user-birth-name"><?=CUser::FormatName($arParams['NAME_TEMPLATE'], $arUser, true);?></span>
			<span class="user-birth-date"><?
			if ($arUser["IS_BIRTHDAY"])
			{
				?><?=FormatDate("today"); ?>!<?
			}
			else
			{
				?><?=FormatDateEx(
					$arUser["PERSONAL_BIRTHDAY"],
					false,
					$arParams['DATE_FORMAT'.($arParams['SHOW_YEAR'] == 'Y' || $arParams['SHOW_YEAR'] == 'M' && $arUser['PERSONAL_GENDER'] == 'M' ? '' : '_NO_YEAR')]
				);
			}
			?></span>
		</span>
	</a>
	<?endforeach?>
</div>