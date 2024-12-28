<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

/** @var SignStartComponent $component */
/** @var array $arResult */
$component->setMenuIndex('sign_b2e_kanban');
\Bitrix\Main\UI\Extension::load([
	'sign.v2.b2e.kanban-entity-footer',
	'sign.v2.b2e.kanban-entity-create-group-chat',
]);
?>
<script>
	const KanbanEntityFooter = new BX.Sign.V2.B2e.KanbanEntityFooter();
	KanbanEntityFooter.init();

	const KanbanEntityCreateGroupChat = new BX.Sign.V2.B2e.KanbanEntityCreateGroupChat();
	KanbanEntityCreateGroupChat.init();
</script>
<?php
/** @var CMain $APPLICATION */
/** @var array $arParams */

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:sign.b2e.kanban',
		'POPUP_COMPONENT_PARAMS' => [],
		'USE_UI_TOOLBAR' => 'Y',
	],
	$this->getComponent(),
);

$APPLICATION->setTitle(Loc::getMessage('SIGN_KANBAN_TOOLBAR_TITLE_SIGN_B2E_DEFAULT') ?? '');
?>
