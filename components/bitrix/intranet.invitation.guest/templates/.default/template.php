<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\UI\Buttons\CloseButton;
use Bitrix\UI\Buttons\SaveButton;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Extension::load(['ui.forms', 'ui.buttons', 'ui.buttons.icons', 'ui.alerts', 'ui.fonts.opensans']);

$containerId = 'invitation-form-'.$this->randString();
?>

<div data-id="<?=$containerId?>"></div>

<?
$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:ui.button.panel',
	'',
	[
		'BUTTONS' => [
			[
				'TYPE' => 'custom',
				'LAYOUT' =>
					SaveButton::create()
					->addDataAttribute('id', $containerId.'-save-btn')
					->setText(Loc::getMessage('INTRANET_INVITATION_GUEST_INVITE_BUTTON'))
					->render(),
			],
			[
				'TYPE' => 'custom',
				'LAYOUT' =>
					CloseButton::create()
					->addDataAttribute('id', $containerId.'-cancel-btn')
					->render(),
			]
		]
	]
);
?>

<script>
	BX.message(<?=CUtil::phpToJsObject(Loc::loadLanguageFile(__FILE__))?>);

	new BX.Intranet.Invitation.Guest.Form({
		targetNode: document.querySelector('[data-id="<?=$containerId?>"]'),
		saveButtonNode: document.querySelector('[data-id="<?=$containerId?>-save-btn"]'),
		cancelButtonNode: document.querySelector('[data-id="<?=$containerId?>-cancel-btn"]'),
		userOptions: <?=CUtil::phpToJsObject($arParams['USER_OPTIONS'])?>,
		rows: <?=CUtil::phpToJsObject($arParams['ROWS'])?>
	});
</script>