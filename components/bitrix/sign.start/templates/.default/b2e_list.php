<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Integration\Bitrix24\B2eTariff;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Toolbar\Facade\Toolbar;

/** @var \CMain $APPLICATION */
/** @var array $arParams */

Loc::loadLanguageFile(__DIR__ . '/template.php');
?>

<?php
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.item.list',
		'POPUP_COMPONENT_PARAMS' => [
			'entityTypeId' => \Bitrix\Sign\Document\Entity\SmartB2e::getEntityTypeId(),
			'categoryId' => '0'
		],
		'USE_UI_TOOLBAR' => 'Y'
	],
	$this->getComponent()
);

$isRestrictedInCurrentTariff = Loader::includeModule('sign')
	&& B2eTariff::instance()->isB2eRestrictedInCurrentTariff()
;

if ($isRestrictedInCurrentTariff)
{
	foreach (Toolbar::getButtons() as $button)
	{
		if ($button instanceof Button && str_contains($button->getLink(), 'sign/b2e/doc/'))
		{
			$button
				->addClass('ui-btn-icon-lock')
				->addClass('sign-b2e-js-tarriff-slider-trigger')
				->setTag('button')
			;

			break;
		}
	}
}

$APPLICATION->setTitle(Loc::getMessage('SIGN_KANBAN_TOOLBAR_TITLE_SIGN_B2E_DEFAULT') ?? '');


if ($isRestrictedInCurrentTariff):
	?>
	<script>
		BX.ready(function()
		{
			const el = document.getElementsByClassName('sign-b2e-js-tarriff-slider-trigger');
			if (el && el[0])
			{
				BX.bind(el[0], 'click', function()
				{
					top.BX.UI.InfoHelper.show('limit_office_e_signature');
				});
			}
		});
	</script>
<?php
endif;
?>