<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Tasks\UI;

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @var CBitrixComponent $component */
/** @var CBitrixComponentTemplate $this */

if (is_array($arResult['ERRORS']) && !empty($arResult['ERRORS']))
{
	$isUiIncluded = Loader::includeModule('ui');
	foreach ($arResult['ERRORS'] as $error)
	{
		$message = $error['MESSAGE'];
		if ($isUiIncluded)
		{
			?>
			<div class="ui-alert ui-alert-danger">
				<span class="ui-alert-message"><?= htmlspecialcharsbx($message) ?></span>
			</div>
			<?php
		}
		else
		{
			ShowError($message);
		}
	}
	return;
}

$fragment = UI::convertBBCodeToHtml(
	$arResult['FRAGMENT'],
	['PATH_TO_USER_PROFILE' => $arParams['PATH_TEMPLATE_TO_USER_PROFILE']]
);
echo $fragment;