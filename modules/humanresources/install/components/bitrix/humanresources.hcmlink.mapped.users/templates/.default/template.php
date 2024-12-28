<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\UI\Buttons\JsCode;
use Bitrix\UI\Buttons\JsHandler;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\UI\Toolbar\Facade\Toolbar;

/**
 * @var array $arResult
 * @var array $arParams
 * @var CMain $APPLICATION
 */

global $APPLICATION;

\Bitrix\Main\UI\Extension::load([
	'humanresources.hcmlink.mapped-person',
	'humanresources.hcmlink.data-mapper',
	'ui.tour',
]);

$APPLICATION->SetTitle($arResult['PAGE_TITLE']);

$rows = [];
/** @var \Bitrix\HumanResources\Item\HcmLink\Person $person */
foreach ($arResult['MAPPED_PERSONS'] as $person)
{
	$user = $arResult['USERS'][$person->userId] ?? null;
	$rows[] = [
		'data' => [
			'ID' => $person->id,
			'PERSON' => $person->title,
			'LOCAL' => ($user)
				? $user->getName() . ' ' . $user->getLastName()
				: '',
		],
	];
}

//stub
ob_start();
?>
	<div class="humanresources_hcmlink_mapped_stub_wrapper">
		<div class="humanresources_hcmlink_mapped_stub_content">
			<div class="humanresources_hcmlink_mapped_stub_content__icon"></div>
			<div
				class="humanresources_hcmlink_mapped_stub_content__title"><?= \Bitrix\Main\Localization\Loc::getMessage(
					'HUMANRESOURCES_HCMLINK_MAPPED_USERS_STUB_TITLE'
				) ?></div>
			<div
				class="humanresources_hcmlink_mapped_stub_content__description"><?= \Bitrix\Main\Localization\Loc::getMessage(
					'HUMANRESOURCES_HCMLINK_MAPPED_USERS_STUB_DESCRIPTION'
				) ?></div>
		</div>
	</div>

<?php
$stub = ob_get_clean();
$stub = count($rows) > 0 ? null : $stub;
//endstub


$snippet = new \Bitrix\Main\Grid\Panel\Snippet();
$deleteBtn = $snippet->getRemoveButton();
$snippet->setButtonActions($deleteBtn, [
	[
		'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
		'DATA' => [
			[
				'JS' => 'BX.Humanresources.Hcmlink.MappedPerson.deleteLinkMappedPerson()',
			],
		],
	],
]);

Toolbar::addButton(
	new \Bitrix\UI\Buttons\Button(
		[
			'color' => \Bitrix\UI\Buttons\Color::SUCCESS,
			'size' => \Bitrix\UI\Buttons\Size::MEDIUM,
			'text' => \Bitrix\Main\Localization\Loc::getMessage('HUMANRESOURCES_HCMLINK_MAPPED_USERS_TOOLBAR_BUTTON_INFO'),
			'className' => 'humanresources_hcmlink_mapped_button_sync_count',
		]
	),
	ButtonLocation::AFTER_TITLE
);

$config = \Bitrix\Main\Web\Json::encode(['companyId' => (int)$arResult['COMPANY_ID'], 'title' => $arResult['PAGE_TITLE']]);
Toolbar::addButton(
	new \Bitrix\UI\Buttons\Button([
		'color' => \Bitrix\UI\Buttons\Color::LIGHT_BORDER,
		'size' => \Bitrix\UI\Buttons\Size::MEDIUM,
		'icon' => \Bitrix\UI\Buttons\Icon::INFO,
		'click' => new JsCode(
			"BX.Humanresources.Hcmlink.MappedPerson.openCompanyConfigSlider({$config})"
		),
	]),
	ButtonLocation::RIGHT
);

$arResult['FILTER_PRESETS'] = [];
$gridId = 'hcmlink_mapped_users';

Toolbar::addFilter([
	'GRID_ID' => $gridId,
	'FILTER_ID' => 'HCMLINK_FILTER_ID_MAPPED',
	'FILTER' => $arResult['FILTER'],
	'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
	'DISABLE_SEARCH' => false,
	'ENABLE_LIVE_SEARCH' => false,
	'ENABLE_LABEL' => true,
	'THEME' => Bitrix\Main\UI\Filter\Theme::MUTED,
]);

Toolbar::deleteFavoriteStar();

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	"",
	[
		'GRID_ID' => $gridId,
		'COLUMNS' => $arResult['COLUMNS'],
		'ROWS' => $rows,
		'NAV_OBJECT' => $arResult['NAVIGATION_OBJECT'],
		'SHOW_ROW_CHECKBOXES' => true,
		'SHOW_TOTAL_COUNTER' => true,
		'TOTAL_ROWS_COUNT' => $arResult['COUNT'],
		'ALLOW_COLUMNS_SORT' => true,
		'ACTION_PANEL' => [
			'GROUPS' => [
				[
					'ITEMS' =>
						[
							$deleteBtn,
						],
				],
			],
		],
		'ALLOW_SORT' => true,
		'ALLOW_COLUMNS_RESIZE' => true,
		'STUB' => $stub,
	]
);
?>

<script>
	BX.ready(function() {
		const button = document.querySelector('.humanresources_hcmlink_mapped_button_sync_count');
		if (button !== undefined)
		{
			const counter = BX.Tag.render `<i class="ui-btn-counter"><?= htmlspecialcharsbx($arResult["UNMAPPED_COUNT"]) ?></i>`;
			BX.Dom.append(counter, button);
			BX.Event.bind(button, 'click', (event) => {
				event.preventDefault();
				BX.Humanresources.Hcmlink.Mapper.openSlider({
					companyId: <?= $arResult['COMPANY_ID'] ?>,
					mode: BX.Humanresources.Hcmlink.Mapper.MODE_REVERSE,
				}, {
					onCloseHandler: () => {
						top.BX.SidePanel.Instance.getSliderByWindow(window)?.reload();
					},
				});
			});
		}

		BX.Humanresources.Hcmlink.MappedPerson.showGuide({
			title: "<?= \Bitrix\Main\Localization\Loc::getMessage('HUMANRESOURCES_HCMLINK_MAPPED_USERS_TOUR_TITLE') ?>",
			text: "<?= \Bitrix\Main\Localization\Loc::getMessage('HUMANRESOURCES_HCMLINK_MAPPED_USERS_TOUR_TEXT') ?>",
			selector: '.humanresources_hcmlink_mapped_button_sync_count',
			lastShowGuideDate: <?= \CUtil::JSEscape(\CUserOptions::GetOption('ui-tour', 'view_date_hr-guide-hcmlink-mapped-person', 'null')) ?>,
		});

		const container = document.getElementById('pagetitleContainer');
		if (container)
		{
			const title = container.querySelector('#pagetitle');
			if (title)
			{
				title.setAttribute('title', "<?= \CUtil::JSEscape($arResult['PAGE_TITLE']) ?>");
			}
		}
	});
</script>


