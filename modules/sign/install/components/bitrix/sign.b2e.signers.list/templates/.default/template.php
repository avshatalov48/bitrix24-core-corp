<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Service\Container;
use Bitrix\UI\Toolbar\ButtonLocation;

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
]);

$APPLICATION->SetTitle(Loc::getMessage('SIGN_B2E_SIGNERS_LIST_TITLE'));

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
	->setLink('#')
	->addClass('sign-b2e-signers-list-add-button')
;

if ($arResult['CAN_ADD_LIST'] ?? false)
{
	\Bitrix\UI\Toolbar\Facade\Toolbar::addButton($createButton, ButtonLocation::AFTER_TITLE);
}

$getUserInfoTemplate = static function (
	?int $listId,
	?int $userId,
	?string $fullName,
	string $imagePath,
): string {
	$containerId = 'sign-signers-list-edit-grid-avatar-' . (int)$listId . '-' . (int)$userId;
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
						renderSignersListAvatar(
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
	?\Bitrix\Sign\Type\DateTime $dateModify,
): string {
	ob_start();
	if ($dateModify)
	{
		$dateModifyTs = $dateModify->toUserTime()->getTimestamp();
		$formatDate = \Bitrix\Main\Context::getCurrent()?->getCulture()?->getLongDateFormat() ?? "j F Y";
		$formatTime = \Bitrix\Main\Context::getCurrent()?->getCulture()?->getLongTimeFormat() ?? "H:i";
		$dateModifyFormatted = FormatDate($formatDate, $dateModifyTs);
		$timeModifyFormatted = FormatDate($formatTime, $dateModifyTs);
	}
	else
	{
		$dateModifyFormatted = $timeModifyFormatted = '';
	}

	?>
	<div title="<?= $dateModifyFormatted . ' ' . $timeModifyFormatted ?>"><?= $dateModifyFormatted ?></div>
	<?php

	return (string)ob_get_clean();
};

$getLinkTemplate = static function (
	string $title,
	string $editLink,
): string {
	$editLinkObject = new \Bitrix\Main\Web\Uri($editLink);
	$isValidEditTemplateLink = str_starts_with($editLinkObject->getPath(), '/sign/b2e/signers/');

	$editLinkHref = (string)$editLinkObject;
	ob_start();?>

	<?php if ($isValidEditTemplateLink): ?>

		<a href="<?= htmlspecialcharsbx($editLinkHref) ?>" class="sign-signers-list-title">
			<?= htmlspecialcharsbx($title) ?>
		</a>
	<?php else: ?>
		<p class="sign-signers-list-title-without-link">
			<?= htmlspecialcharsbx($title) ?>
		</p>
	<?php endif; ?>

	<?php

	return (string)ob_get_clean();
};

$gridRows = [];
$signersLists = $arResult['SIGNERS_LISTS'] ?? [];
foreach ($signersLists as $listData)
{
	$dateModify = $listData['columns']['DATE_MODIFY'] ?? null;
	$modifiedBy = $listData['columns']['MODIFIED_BY'] ?? null;
	$listId = (int)$listData['columns']['ID'];
	$editLink = Container::instance()
		->getUrlGeneratorService()
		->makeEditSignersListUrl($listId)
	;
	$gridRow = [
		'data' => [
			'ID' => $listId,
			'TITLE' => $getLinkTemplate(
				$listData['columns']['TITLE'],
				$editLink,
			),
			'DATE_MODIFY' => $dateModify !== null ? $getDateTemplate($dateModify) : null,
			'RESPONSIBLE' => $getUserInfoTemplate(
				$listId,
				(int)$listData['columns']['RESPONSIBLE']['ID'],
				$listData['columns']['RESPONSIBLE']['FULL_NAME'],
				$listData['columns']['RESPONSIBLE']['AVATAR_PATH'],
			),
		],
	];

	if ($listData['access']['canEdit'] ?? false)
	{
        $oldTitle = CUtil::JSEscape($listData['columns']['TITLE']);
        $oldTitle = htmlspecialcharsbx($oldTitle);
		$gridRow['actions'][] = [
			'text' => (string)Loc::getMessage('SIGN_B2E_SIGNERS_LIST_ACTION_RENAME'),
			'onclick' => "listGrid.renameList({$listId}, '{$oldTitle}')",
		];
	}

	if ($listData['access']['canCopy'] ?? false)
	{
		$gridRow['actions'][] = [
			'text' => (string)Loc::getMessage('SIGN_B2E_SIGNERS_LIST_ACTION_COPY'),
			'onclick' => "listGrid.copyList({$listId})",
		];
	}

	if ($listData['access']['canDelete'] ?? false)
	{
		$gridRow['actions'][] = [
			'text' => (string)Loc::getMessage('SIGN_B2E_SIGNERS_LIST_ACTION_DELETE'),
			'onclick' => "listGrid.deleteList({$listId})",
		];
	}

	$gridRows[] = $gridRow;
}

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	[
		'GRID_ID' => $arParams['GRID_ID'] ?? '',
		'COLUMNS' => $arParams['COLUMNS'] ?? '',
		'ROWS' => $gridRows,
		'NAV_OBJECT' => $arResult['PAGE_NAVIGATION'] ?? null,
		'SHOW_ROW_CHECKBOXES' => false,
		'SHOW_TOTAL_COUNTER' => true,
		'TOTAL_ROWS_COUNT' => $arResult['TOTAL_COUNT'] ?? 0,
		'ALLOW_COLUMNS_SORT' => true,
		'ALLOW_SORT' => true,
		'ALLOW_COLUMNS_RESIZE' => true,
		'AJAX_MODE' => 'Y',
		'AJAX_OPTION_HISTORY' => 'N',
		'AJAX_OPTION_JUMP' => 'N',
		'SHOW_ACTION_PANEL' => false,
		'ACTION_PANEL' => [],
	]);
?>

<script>
	const listGrid = new BX.Sign.V2.Grid.B2e.Signers();

	BX.ready(function()
	{
		const addListButton = document.querySelector('.sign-b2e-signers-list-add-button');
		if (addListButton)
		{
			BX.bind(addListButton, 'click', function()
			{
				listGrid.createList();
			});
		}
	});

	function renderSignersListAvatar(container, userpicPath, userName) {
		new BX.UI.AvatarRound({
			size: 26,
			userpicPath: userpicPath,
			userName: userName,
		}).renderTo(container);
	}
</script>