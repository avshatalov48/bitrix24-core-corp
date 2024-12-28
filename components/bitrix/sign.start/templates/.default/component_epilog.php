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

\Bitrix\Main\UI\Extension::load([
	'sign.tour',
]);

// top menu init

$menuItemIndex = $this->getMenuIndex();
$menuItems = $arParams['MENU_ITEMS'] ?? [];

$guidedItems = array_values(
	array_map(
		static fn(array $item) => $item['TOUR'],
		array_filter($menuItems, static fn(array $item) => isset($item['TOUR']))
	)
);

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
					{
						condition: [
							"/hr/hcmlink/companies/"
						],
						options: {
							width: 700,
							cacheable: false,
						},
					}
				]
		});

		const guidedItems = <?= \Bitrix\Main\Web\Json::encode($guidedItems) ?>;

		(guidedItems ?? []).forEach((item) => {
			if (!item.targetId)
			{
				return;
			}

			BX.UI.BannerDispatcher.normal.toQueue((onDone) => {
				const guide = new BX.Sign.Tour.Guide({
					id: item.id,
					autoSave: true,
					simpleMode: true,
					steps: [
						{
							target: item.targetId,
							title: item.title,
							text: item.description,
							article: item.articleCode,
						},
					],
					events: {
						onFinish: () => onDone(),
					},
					hideButton: true,
				});
				guide.startOnce();
			});
		});
	})
</script>

<?php
$this->getTemplate()->endViewTarget();
