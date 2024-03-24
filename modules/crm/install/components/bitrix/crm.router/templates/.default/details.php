<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */

if ($errors = $this->getComponent()->getErrors())
{
	ShowError(reset($errors)->getMessage());
	return;
}

Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/workareainvisible.css');

$entityTypeId = $arResult['entityTypeId'];
$entityId = (int)($arResult['componentParameters']['ENTITY_ID'] ?? 0);
$entityCategoryId = null;

if ($entityId <= 0)
{
	$requestValues = Bitrix\Main\Context::getCurrent()->getRequest()->getValues();
	$entityCategoryId = isset($requestValues['categoryId']) ? (int)$requestValues['categoryId'] : null;
}
else
{
	$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
	$entityCategoryId = $factory?->getItem($entityId)?->getCategoryIdForPermissions();
}

$viewCategoryId = $entityCategoryId;

$script = CCrmViewHelper::getDetailFrameWrapperScript(
	$entityTypeId,
	$entityId,
	$entityCategoryId,
	$viewCategoryId,
);

echo $script;
