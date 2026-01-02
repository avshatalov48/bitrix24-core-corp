<?php

use Bitrix\Main\Web\Uri;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\Main\Grid\Panel;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** @var array $arParams */
/** @var array $arResult */

/** @var $APPLICATION */

\CJSCore::Init("loader");
\Bitrix\Main\UI\Extension::load([
	'sign.v2.grid.b2e.signers',
	'sign.v2.ui.tokens',
	'ui.avatar',
]);

$APPLICATION->SetTitle($arResult['LIST_TITLE'] ?? '');

\Bitrix\UI\Toolbar\Facade\Toolbar::addFilter([
	'GRID_ID' => $arParams['GRID_ID'] ?? '',
	'FILTER_ID' => $arParams['FILTER_ID'] ?? '',
	'FILTER' => $arParams['FILTER_FIELDS'] ?? [],
	'FILTER_PRESETS' => $arParams['FILTER_PRESETS'] ?? [],
	'DISABLE_SEARCH' => false,
	'ENABLE_LIVE_SEARCH' => true,
	'ENABLE_LABEL' => true,
	'THEME' => Bitrix\Main\UI\Filter\Theme::MUTED,
]);

$createButton = (new \Bitrix\UI\Buttons\CreateButton([]))
	->setText(Loc::getMessage('SIGN_B2E_SIGNERS_LIST_ADD_NEW_TITLE') ?? '')
	->setLink($arParams['ADD_NEW_SIGNER_LINK'] ?? '#')
;

if ($arResult['CAN_ADD_SIGNER'] ?? false)
{
	\Bitrix\UI\Toolbar\Facade\Toolbar::addButton($createButton, ButtonLocation::AFTER_TITLE);
}

$getUserInfoTemplate = static function (
	?int $userId,
	?string $fullName,
	string $imagePath,
)
{
	$containerId = 'sign-signers-list-edit-grid-avatar-' . (int)$userId;
	ob_start();
	?>
	<div style="display: flex; align-items: center">
		<a
			class="sign-personal-grid-user"
			target="_top"
			onclick="event.stopPropagation();"
			href="/company/personal/user/<?= (int)$userId ?>/">
			<div id="<?= htmlspecialcharsbx($containerId) ?>" style="display: inline-flex; align-items: center;"></div>
			<span class="sign-personal-grid-user-name">
				<?= htmlspecialcharsbx($fullName) ?>
			</span>
			<script>
				BX.ready(function() {
					var container = document.getElementById('<?= CUtil::JSEscape($containerId) ?>');
					if (container && !container.hasChildNodes()) {
						renderSignersListSignerAvatar(
							container,
							'<?= CUtil::JSEscape($imagePath) ?>',
							'<?= CUtil::JSEscape($fullName) ?>'
						);
					}
				});
			</script>
		</a>
	</div>
	<?php
	return (string)ob_get_clean();
};

$getDateTemplate = static function (
	?\Bitrix\Sign\Type\DateTime $dateCreate,
): string {
	ob_start();
	if ($dateCreate)
	{
		$dateCreateTs = $dateCreate->toUserTime()->getTimestamp();
		$formatDate = \Bitrix\Main\Context::getCurrent()?->getCulture()?->getLongDateFormat() ?? "j F Y";
		$formatTime = \Bitrix\Main\Context::getCurrent()?->getCulture()?->getLongTimeFormat() ?? "H:i";
		$dateCreateFormatted = FormatDate($formatDate, $dateCreateTs);
		$timeCreateFormatted = FormatDate($formatTime, $dateCreateTs);
	}
	else
	{
		$dateCreateFormatted = $timeCreateFormatted = '';
	}

	?>
	<div title="<?= $dateCreateFormatted . ' ' . $timeCreateFormatted ?>"><?= $dateCreateFormatted ?></div>
	<?php

	return (string)ob_get_clean();
};

$getLinkTemplate = static function (
	string $title,
	string $profileUrl,
): string {
	$profileLinkObject = new \Bitrix\Main\Web\Uri($profileUrl);
	ob_start();?>
	<a href="<?= htmlspecialcharsbx((string)$profileLinkObject) ?>" class="sign-signers-list-title">
		<?= htmlspecialcharsbx($title) ?>
	</a>
	<?php

	return (string)ob_get_clean();
};

$gridRows = [];
$signers = $arResult['SIGNERS'] ?? [];
foreach ($signers as $listData)
{
	$dateCreate = $listData['columns']['DATE_CREATE'] ?? null;
	$createdBy = $listData['columns']['CREATED_BY'] ?? null;
	$listId = (int)$arResult['LIST_ID'];
	$userId = (int)$listData['columns']['SIGNER']['ID'];
	$gridRow = [
		'data' => [
			'ID' => $userId,
			'SIGNER' => $getUserInfoTemplate(
				(int)$listData['columns']['SIGNER']['ID'],
				$listData['columns']['SIGNER']['FULL_NAME'],
				$listData['columns']['SIGNER']['AVATAR_PATH'],
			),
			'DATE_CREATE' => $dateCreate !== null ? $getDateTemplate($dateCreate) : null,
		],
	];

	if ($arResult['CAN_DELETE_SIGNER'] ?? false)
	{
		$gridRow['actions'][] = [
			'text' => (string)Loc::getMessage('SIGN_B2E_SIGNERS_LIST_ACTION_DELETE'),
			'onclick' => "(new BX.Sign.V2.Grid.B2e.Signers()).deleteSigners({$listId}, [{$userId}])",
			'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_remove.svg',
		];
	}

	$gridRows[] = $gridRow;
}

$gridComponentParams = [
	'GRID_ID' => $arParams['GRID_ID'] ?? '',
	'COLUMNS' => $arParams['COLUMNS'] ?? '',
	'ROWS' => $gridRows,
	'NAV_OBJECT' => $arResult['PAGE_NAVIGATION'] ?? null,
	'SHOW_ROW_CHECKBOXES' => true,
	'SHOW_TOTAL_COUNTER' => true,
	'TOTAL_ROWS_COUNT' => $arResult['TOTAL_COUNT'] ?? 0,
	'ALLOW_COLUMNS_SORT' => true,
	'ALLOW_SORT' => true,
	'ALLOW_COLUMNS_RESIZE' => true,
	'AJAX_MODE' => 'Y',
	'AJAX_OPTION_HISTORY' => 'N',
	'AJAX_OPTION_JUMP' => 'N',
	'SHOW_ACTION_PANEL' => false,
	'TOP_ACTION_PANEL_RENDER_TO' => '.main-grid-container',
	'SHOW_NAVIGATION_PANEL' => true,
];

if ($arResult['CAN_DELETE_SIGNER'] ?? false)
{
	$gridComponentParams['ACTION_PANEL'] = [
		'GROUPS' => [
			[
				'ITEMS' => [
					[
						'ID' => 'sign-b2e-signers-edit-grid-delete-button',
						'TYPE' => Panel\Types::BUTTON,
						'TEXT' => Loc::getMessage('SIGN_B2E_SIGNERS_LIST_ACTION_DELETE'),
						'ICON' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_remove.svg',
						'ONCHANGE' => [
							[
								'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
								'DATA' => [
									[
										'JS' => "(new BX.Sign.V2.Grid.B2e.Signers()).deleteSelectedSigners(" . ((int)$arResult['LIST_ID']) . ")",
									]
								]
							]
						]
					]
				]
			]
		]
	];
}

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	$gridComponentParams,
);
?>
<script>
    function renderSignersListSignerAvatar(container, userpicPath, userName) {
		new BX.UI.AvatarRound({
			size: 26,
			userpicPath: userpicPath,
			userName: userName,
		}).renderTo(container);
    }
</script>