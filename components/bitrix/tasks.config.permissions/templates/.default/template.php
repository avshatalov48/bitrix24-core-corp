<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$APPLICATION->SetTitle(GetMessage('TASKS_CONFIG_PERMISSIONS'));
$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'no-background no-all-paddings');

\Bitrix\Main\Loader::includeModule('ui');
\Bitrix\Main\UI\Extension::load(['ui.buttons', 'ui.icons', 'ui.notification', 'ui.accessrights']);

Loc::loadMessages(__FILE__);

$componentId = 'bx-access-group';
$initPopupEvent = 'tasks:onComponentLoad';
$openPopupEvent = 'tasks:onComponentOpen';
\Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();

$hasFatals = false;


$isBitrix24Template = SITE_TEMPLATE_ID === 'bitrix24';
if ($isBitrix24Template)
{
	$this->SetViewTarget("pagetitle", 100);
}
?>

<a href="<?= \Bitrix\UI\Util::getArticleUrlByCode('11705476'); ?>" class="tasks-permission-header-link"><?= Loc::getMessage('TASKS_CONFIG_PERMISSIONS_HELP_LINK'); ?></a>

<?php
if($isBitrix24Template)
{
	$this->EndViewTarget();
}

$APPLICATION->IncludeComponent(
	'bitrix:ui.feedback.form',
	'',
	[
		'ID' => 'tasks-permissions',
		'VIEW_TARGET' => 'pagetitle',
		'FORMS' => [
			['zones' => ['com.br'], 'id' => '160','lang' => 'br', 'sec' => 'c20k6f'],
			['zones' => ['es'], 'id' => '158','lang' => 'la', 'sec' => 'fwr2pn'],
			['zones' => ['de'], 'id' => '156','lang' => 'de', 'sec' => '2lx0w0'],
			['zones' => ['ua'], 'id' => '152','lang' => 'ua', 'sec' => 'edxnql'],
			['zones' => ['ru', 'kz', 'by'], 'id' => '150','lang' => 'ru', 'sec' => '01beqf'],
			['zones' => ['en'], 'id' => '154','lang' => 'en', 'sec' => 'f4dk6p'],
		],
	]
);
?>

<?if(!empty($arResult['ERROR'])):?>
	<?foreach($arResult['ERROR'] as $error):?>
		<?if($error['TYPE'] == 'FATAL'):?>
			<div class="task-message-label error"><?=htmlspecialcharsbx($error['MESSAGE'])?></div>
			<? $hasFatals = true; ?>
		<?endif?>
	<?endforeach?>
<?endif?>

<span id="bx-access-group"></span>

<?php
$APPLICATION->IncludeComponent(
	"bitrix:main.ui.selector",
	".default",
	[
		'API_VERSION' => 3,
		'ID' => $componentId,
		'BIND_ID' => $componentId,
		'ITEMS_SELECTED' => [],
		'CALLBACK' => [
			'select' => "AccessRights.onMemberSelect",
			'unSelect' => "AccessRights.onMemberUnselect",
			'openDialog' => 'function(){}',
			'closeDialog' => 'function(){}',
		],
		'OPTIONS' => [
			'eventInit' => $initPopupEvent,
			'eventOpen' => $openPopupEvent,
			'useContainer' => 'Y',
			'lazyLoad' => 'Y',
			'context' => 'TASKS_PERMISSION',
			'contextCode' => '',
			'useSearch' => 'Y',
			'useClientDatabase' => 'Y',
			'allowEmailInvitation' => 'N',
			'enableAll' => 'N',
			'enableUsers' => 'Y',
			'enableDepartments' => 'Y',
			'enableGroups' => 'Y',
			'departmentSelectDisable' => 'N',
			'allowAddUser' => 'Y',
			'allowAddCrmContact' => 'N',
			'allowAddSocNetGroup' => 'N',
			'allowSearchEmailUsers' => 'N',
			'allowSearchCrmEmailUsers' => 'N',
			'allowSearchNetworkUsers' => 'N',
			'useNewCallback' => 'Y',
			'multiple' => 'Y',
			'enableSonetgroups' => 'Y',
			'showVacations' => 'Y',
		]
	],
	false,
	["HIDE_ICONS" => "Y"]
);
?>

<?php if (!$hasFatals): ?>
	<div id="bx-tasks-config-permissions"></div>
	<script>
		let AccessRights = new BX.UI.AccessRights({
			component: 'bitrix:tasks.config.permissions',
			renderTo: document.getElementById('bx-tasks-config-permissions'),
			userGroups: <?= CUtil::PhpToJSObject($arResult['USER_GROUPS']) ?>,
			accessRights: <?= CUtil::PhpToJSObject($arResult['ACCESS_RIGHTS']); ?>,
			initPopupEvent: '<?= $initPopupEvent ?>',
			openPopupEvent: '<?= $openPopupEvent ?>',
			popupContainer: '<?= $componentId ?>',
		});

		AccessRights.draw();

		BX.ready(function() {
			setTimeout(function() {
				BX.onCustomEvent('<?= $initPopupEvent ?>', [{openDialogWhenInit: false}])
			});
		});

	</script>

	<?php
		$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
			'HIDE'    => true,
			'BUTTONS' => [
				[
					'TYPE'    => 'save',
					'ONCLICK' => 'AccessRights.sendActionRequest()',

				],
				[
					'TYPE'    => 'cancel',
					'ONCLICK' => 'AccessRights.fireEventReset()'
				],
			],
		]);
	?>
<?php endif; ?>