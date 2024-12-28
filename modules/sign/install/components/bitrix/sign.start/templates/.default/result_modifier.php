<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var SignStartComponent $component */
/** @var array $arParams */

$component = $this->getComponent();
$component->setMenuIndex('index');

if ($component->isFrame())
{
	return;
}

$component->registerSidePanels(
	[$arParams['PAGE_URL_DOCUMENT']],
	['width' => 1250]
);

$component->registerSidePanels(
	[$arParams['PAGE_URL_B2E_DOCUMENT']],
	['width' => 1250]
);

$component->registerSidePanels(
	[$arParams['PAGE_URL_EDIT']],
	['width' => 1200]
);

$component->registerSidePanels(
	[
		\Bitrix\Sign\Integration\CRM::getContactUrl('\d'),
		\Bitrix\Sign\Integration\CRM::getCompanyUrl('\d')
	]
);
?>
