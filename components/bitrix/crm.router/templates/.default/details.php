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
else
{
	Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/workareainvisible.css');

	$entityCategoryId = null;
	$entityId =
		(
			is_array($arResult['componentParameters'])
			&& isset($arResult['componentParameters']['ENTITY_ID'])
		)
			? (int)$arResult['componentParameters']['ENTITY_ID']
			: 0
	;
	if ($entityId <= 0)
	{
		$requestValues = Bitrix\Main\Context::getCurrent()->getRequest()->getValues();
		$entityCategoryId = isset($requestValues['categoryId']) ? (int)$requestValues['categoryId'] : null;
	}

	$script = CCrmViewHelper::getDetailFrameWrapperScript(
		$arResult['entityTypeId'],
		$entityId,
		$entityCategoryId
	);

	echo $script;
}
