<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

/** @var \CMain $APPLICATION */
/** @var array $arParams */
/** @var SignStartComponent $this */

Loc::loadMessages(dirname(__FILE__) . '/template.php');

// top menu init

$menuItemIndex = $this->getMenuIndex();
$menuItems = $arParams['MENU_ITEMS'] ?? [];

foreach ($menuItems as &$menuItem)
{
	$menuItem['IS_ACTIVE'] = isset($menuItem['ID']) && $menuItemIndex === $menuItem['ID'];
}

// top menu insert

$APPLICATION->clearViewContent('above_pagetitle');

$this->getTemplate()->setViewTarget('above_pagetitle', 100);
if ($menuItems)
{
	$APPLICATION->includeComponent(
		'bitrix:main.interface.buttons',
		'',
		array(
			'ID' => 'sign',
			'ITEMS' => $menuItems
		)
	);		
}
else
{
	$APPLICATION->SetTitle($arParams['PAGE_TITLE'] ?? '');
}

?>
<script>
	BX.ready(function ()
	{
		BX.SidePanel.Instance.bindAnchors({
			rules:
				[
					{
						condition: [
							"/sign/config/permission/",
						],
					},
				]
		});
	})
</script>

<?php
$this->getTemplate()->endViewTarget();
