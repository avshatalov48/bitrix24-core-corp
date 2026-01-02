<?php

use Bitrix\Main\HttpContext;
use Bitrix\Main\Loader;
use Bitrix\Landing\Mainpage\Manager;
use Bitrix\Landing\Site\Type;
use Bitrix\Intranet\MainPage;
use Bitrix\Main\UI\Extension;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @var \CMain $APPLICATION */
/** @var \CBitrixComponent $component */

$request = HttpContext::getCurrent()->getRequest();

$arParams['PAGE_URL_SITE_EDIT'] = str_replace(
	'#site_edit#',
	0,
	$arParams['PAGE_URL_SITE_EDIT']
);

$canCreateVibe = Loader::includeModule('landing') && (new MainPage\Access())->canEdit();
if ($canCreateVibe)
{
	$template = $request->get('tpl');
	$notRedirectToEdit = ($request->get('no_redirect') === 'Y') ? 'Y' : 'N';
	if ($template)
	{
		$manager = new Manager();
		if ($manager->getConnectedSiteId())
		{
			$APPLICATION->includeComponent(
				'bitrix:ui.sidepanel.wrapper',
				'',
				[
					'POPUP_COMPONENT_NAME' => 'bitrix:landing.demo_preview',
					'POPUP_COMPONENT_TEMPLATE_NAME' => '.default',
					'POPUP_COMPONENT_PARAMS' => [
						'CODE' => $template,
						'TYPE' => Type::SCOPE_CODE_MAINPAGE,
						'SITE_ID' => $manager->getConnectedSiteId(),
						'DISABLE_REDIRECT' => $notRedirectToEdit,
						'DONT_LEAVE_FRAME' => 'N',
					],
					'USE_PADDING' => false,
					'PLAIN_VIEW' => true,
					'PAGE_MODE' => false,
					'CLOSE_AFTER_SAVE' => false,
					'RELOAD_GRID_AFTER_SAVE' => false,
					'RELOAD_PAGE_AFTER_SAVE' => true,
				]
			);
		}
	}
	else
	{
		ShowError('Template not found');
	}
}
else
{
	if ($request->get('IFRAME') === 'Y')
	{
		$APPLICATION->ShowHead();
	}
	Extension::load([
		'sidepanel',
		'ui.info-helper',
	]);
	?>
	<script>
		if (typeof BX.SidePanel !== 'undefined')
		{
			BX.UI.InfoHelper.show("limit_office_vibe");
		}
	</script>
	<?php

	if ($request->get('IFRAME') !== 'Y')
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
	}
	else
	{
		?>
		<script>
			if (typeof BX.SidePanel !== 'undefined')
			{
				const previous = BX.SidePanel.Instance.getPreviousSlider();
				if (previous)
				{
					previous.close();
				}
			}
		</script>
		<?php
	}
}

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
