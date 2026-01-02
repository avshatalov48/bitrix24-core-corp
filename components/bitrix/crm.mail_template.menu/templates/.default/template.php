<?php

use Bitrix\Main\Page\Asset;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\Icon;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\UI\Toolbar\Facade\Toolbar;

/** @global CMain $APPLICATION */
/** @var CBitrixComponent $component */
/** @var array $arParams */
/** @var array $arResult */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (empty($arResult['BUTTONS']))
	return;

if ($_REQUEST['IFRAME'] === 'Y')
{
	Toolbar::deleteFavoriteStar();
}

if ($arParams['TYPE'] === 'list')
{
	foreach ($arResult['BUTTONS'] as $item)
	{
		if ('btn-new' === $item['ICON'])
		{
			$button = new Button([
				"color" => Color::PRIMARY,
				"link" => htmlspecialcharsbx($item['LINK']),
				"text" => htmlspecialcharsbx($item['TEXT']),
				"icon" => Icon::ADD,
			]);
			$button->addAttribute('title', htmlspecialcharsbx($item['TITLE']));
			Toolbar::addButton($button, ButtonLocation::AFTER_TITLE);

			break;
		}
	}
	Asset::getInstance()->addString("
		<script>
			BX.SidePanel.Instance.bindAnchors({
				rules: [
					{
						condition: [
							'/crm/configs/mailtemplate/add/',
							'/crm/configs/mailtemplate/edit/',
						],
						options: {
							cacheable: false,
							width: 1080
						}
					}
				]
			});
		</script>
	");
}
else
{
	global $APPLICATION;
	$APPLICATION->includeComponent(
		'bitrix:main.interface.toolbar',
		'',
		array(
			'BUTTONS' => $arResult['BUTTONS']
		),
		$component,
		array(
			'HIDE_ICONS' => 'Y'
		)
	);
}
