<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

/** @var CMain $APPLICATION*/
/** @var array $arResult*/
/** @var array $arParams*/

global $APPLICATION;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

\Bitrix\Main\UI\Extension::load(['ui.icon-set, ui.icon-set.main', 'ui.hint', 'ui.forms', 'ui.analytics']);

$APPLICATION->setTitle(Loc::getMessage('ROLE_LIBRARY_TITLE'));
?>

<div>
<?php

/** @var \Bitrix\AI\Guard\ShowCopilotGuard $showCopilotGuard */
$showCopilotGuard = \Bitrix\AI\Container::init()->getItem(\Bitrix\AI\Guard\ShowCopilotGuard::class);
if (!$showCopilotGuard->hasAccess(CurrentUser::get()->getId()))
{
	\Bitrix\Main\Application::getInstance()->end();
}

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
$userId = Bitrix\AI\Facade\User::getCurrentUserId();
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
