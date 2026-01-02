<?php

use Bitrix\Intranet\Integration;
use Bitrix\Landing\Site\Type;
use Bitrix\Main\Loader;
use Bitrix\Main\HttpContext;
use Bitrix\Intranet\MainPage;

/** @var array $arParams */
/** @var array $arResult */
/** @var \CMain $APPLICATION */
/** @var \CBitrixComponent $component */

$isSlider =
	(isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] === 'Y')
	|| (isset($_REQUEST['landing_mode']) && $_REQUEST['landing_mode'] === 'edit');
if ($isSlider)
{
	define('SITE_TEMPLATE_ID', 'landing24');
}

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
?>

<?php
$request = HttpContext::getCurrent()->getRequest();

if (
	!Loader::includeModule('landing')
	|| !(new MainPage\Access())->canEdit()
)
{
	LocalRedirect('/vibe/');
}

if (!$isSlider)
{
	$APPLICATION->IncludeComponent(
		'bitrix:landing.mainpage.pub',
		'',
		[],
		null,
		[
			'HIDE_ICONS' => 'Y',
		]
	);

	CJSCore::init('sidepanel');
	?>
	<script>
		BX.ready(function () {
			BX.SidePanel.Instance.open(
				window.location.href,
				{
					customLeftBoundary: 66,
					events: {
						onCloseComplete: () => {
							window.top.location = window.location.origin + '/vibe/';
						},
					},
				},
			);
		});
	</script>
	<?php
}
else
{
	$APPLICATION->IncludeComponent(
		'bitrix:landing.start',
		'.default',
		[
			'COMPONENT_TEMPLATE' => '.default',
			'SEF_FOLDER' => (new Integration\Landing\MainPage\Manager)->getEditPath(),
			'STRICT_TYPE' => 'Y',
			'SEF_MODE' => 'Y',
			'TYPE' => Type::SCOPE_CODE_MAINPAGE,
			'DRAFT_MODE' => 'Y',
			'EDIT_FULL_PUBLICATION' => 'Y',
			'EDIT_PANEL_LIGHT_MODE' => 'Y',
			'EDIT_DONT_LEAVE_FRAME' => 'Y',
			'SEF_URL_TEMPLATES' => Integration\Landing\MainPage\Manager::SEF_EDIT_URL_TEMPLATES,
		],
		false
	);
}
?>

<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>
