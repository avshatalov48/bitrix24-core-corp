<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

/** @var CMain $APPLICATION*/
/** @var array $arResult*/
/** @var array $arParams*/

global $APPLICATION;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

\Bitrix\Main\UI\Extension::load(['ui.icon-set, ui.icon-set.main', 'ui.hint', 'ui.forms', 'ui.analytics']);

$APPLICATION->setTitle(Loc::getMessage('ROLE_LIBRARY_TITLE'));
?>

<div>
<?php

$userId = Bitrix\AI\Facade\User::getCurrentUserId();

$grid = $arResult['GRID'];

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	$grid,
);

$optionName = 'role_share_grid_add_offer';
$userListInOption = CUserOptions::GetOption('ai', $optionName);
if (empty($userListInOption))
{
	$userListInOption = [];
}
$showSimpleTour = false;
if (!in_array($userId, $userListInOption))
{
	$userListInOption[] = $userId;
	CUserOptions::SetOption("ai", $optionName, $userListInOption);
	$showSimpleTour = true;
}
?>
</div>

<script>
	BX.ready(() => {
		BX.UI.Hint.init(BX('main-grid-table'));
		BX.message(<?=Json::encode(Loc::loadLanguageFile(__FILE__))?>);
		BX.AI.ShareRole.Library.Controller.init('<?= $grid['GRID_ID'] ?>', <?=Json::encode($showSimpleTour)?>);
	});
</script>
