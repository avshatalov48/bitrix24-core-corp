<?php

use Bitrix\Intranet\Integration;
use Bitrix\Landing\Site\Type;
use Bitrix\Main\Loader;
use Bitrix\Main\HttpContext;
use Bitrix\Intranet\MainPage;

if (
	isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] == 'Y' ||
	isset($_REQUEST['landing_mode']) && $_REQUEST['landing_mode'] == 'edit'
)
{
	define('SITE_TEMPLATE_ID', 'landing24');
}
else
{
	define('SITE_TEMPLATE_ID', 'bitrix24');
}

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
?>

<?php
$reopenLocationInSlider = 'N';
$request = HttpContext::getCurrent()->getRequest();

$currentUrl = $request->getRequestUri();
if (preg_match('#^/vibe/edit/site/(\d+)/settings/(\d+)/$#', $currentUrl, $matches))
{
	$newUrl = "/vibe/edit/{$matches[1]}/view/{$matches[2]}/";
	LocalRedirect($newUrl);
}
else
{
	if ($request->get('IFRAME') !== 'Y')
	{
		$reopenLocationInSlider = 'Y';
		$APPLICATION->IncludeComponent(
			'bitrix:landing.mainpage.pub',
			'',
			[
				'DRAFT_MODE' => 'Y',
			],
			null,
			[
				'HIDE_ICONS' => 'Y',
			]
		);
		?>
		<script>
			BX.ready(function()
			{
				BX.SidePanel.Instance.open(
					window.location.href,
					{
						customLeftBoundary: 66,
						events: {
							onCloseComplete: () => {
								window.top.location =  window.location.origin + '/vibe/';
							}
						}
					}
				);
			});
		</script>
		<?php
	}
	else
	{
		if (Loader::includeModule('landing') && (new MainPage\Access())->canEdit(false))
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
					'REOPEN_LOCATION_IN_SLIDER' => $reopenLocationInSlider,
				],
				false
			);
		}
		else
		{
			ShowError('Access denied');
		}
	}
}
?>

<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>
