<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

$this->addExternalCss(SITE_TEMPLATE_PATH . '/css/sidebar.css');

$this->setFrameMode(true);

if (count($arResult['USERS']) < 1)
{
	return;
}

$this->SetViewTarget('sidebar', 300);
$frame = $this->createFrame()->begin();

?><div class="sidebar-widget sidebar-widget-birthdays">
	<div class="sidebar-widget-top">
		<div class="sidebar-widget-top-title"><?= Loc::getMessage('WIDGET_BIRTHDAY_TITLE') ?></div>
	</div>
	<div class="sidebar-widget-content">
	<?php

	$i = 0;

	foreach ($arResult['USERS'] as $arUser)
	{
		$classList = [
			'sidebar-widget-item',
			'--row',
		];

		if (++$i === count($arResult['USERS']))
		{
			$classList[] = 'widget-last-item';
		}

		if ($arUser['IS_BIRTHDAY'])
		{
			$classList[] = 'today-birth';
		}

		$avatarStyle = (isset($arUser['PERSONAL_PHOTO']['src']) ? "background: url('" . Uri::urnEncode($arUser['PERSONAL_PHOTO']['src']) . "') no-repeat center; background-size: cover;" : '');

		?><a href="<?= $arUser['DETAIL_URL'] ?>" class="<?= implode(' ', $classList) ?>">
			<span class="user-avatar user-default-avatar" style="<?= $avatarStyle ?>"></span>
			<span class="sidebar-user-info">
				<span class="user-birth-name"><?= CUser::FormatName($arParams['NAME_TEMPLATE'], $arUser, true); ?></span>
				<span class="user-birth-date"><?php
					if ($arUser['IS_BIRTHDAY'])
					{
						?><?= FormatDate('today'); ?>!<?
					}
					else
					{
						?><?= FormatDateEx(
							$arUser['PERSONAL_BIRTHDAY'],
							false,
							$arParams['DATE_FORMAT' . ($arParams['SHOW_YEAR'] === 'Y' || ($arParams['SHOW_YEAR'] === 'M' && $arUser['PERSONAL_GENDER'] === 'M') ? '' : '_NO_YEAR')]
						);
					}
				?></span>
			</span>
		</a><?php
	}
?>
	</div>
</div>
<?php

$frame->end();
$this->EndViewTarget();
