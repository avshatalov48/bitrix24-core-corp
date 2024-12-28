<?php

use Bitrix\Main\Web\Uri;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */

/** @var $APPLICATION */

\Bitrix\Main\Loader::includeModule("ui");
\Bitrix\Main\UI\Extension::load([
	'ui.icons.disk',
	'ui.label',
]);

if ($arParams['NEED_SET_TITLE'])
{
	$APPLICATION->SetTitle($arParams['TITLE']);
}

$getValidatedUrl = static function(string $validatedUrl, bool $needValidateOpenRedirect = true): string
{
	$urlParseResult = parse_url($validatedUrl);

	if (!$urlParseResult)
	{
		return "";
	}

	if (!isset($urlParseResult['host']) || !$needValidateOpenRedirect)
	{
		return htmlspecialcharsbx($validatedUrl);
	}

	$server = \Bitrix\Main\Application::getInstance()->getContext()->getServer();
	$portalHost = $server->getHttpHost();

	if ($portalHost !== $urlParseResult['host'])
	{
		return '';
	}

	return htmlspecialcharsbx($validatedUrl);
};

$getTextWithHoverTemplate = static function(string $text, string $hoverText): string
{
	ob_start();
	?>
	<span title="<?= htmlspecialcharsbx($hoverText) ?>">
		<?= htmlspecialcharsbx($text) ?>
	</span>
	<?php
	return (string)ob_get_clean();
};

$getOpenSliderLinkTemplate = static function(string $templateText, string $link) use ($getValidatedUrl): string
{
	$validatedLink = $getValidatedUrl($link);

	ob_start();
	?>
	<a target="_top" href="<?= $validatedLink ?>">
		<?= htmlspecialcharsbx($templateText) ?>
	</a>
	<?php
	return ob_get_clean();
};

$getUserInfoTemplate = static function (
	?string $fullName,
	?string $linkPath,
	?string $iconPath
) use (
	$getValidatedUrl
): string {
	$userLinkPath = $getValidatedUrl((string)$linkPath);
	$userImagePath = $getValidatedUrl((string)$iconPath, false);
	$userFullName = htmlspecialcharsbx($fullName);

	ob_start();
	?>
	<a
		class="sign-mysafe-grid-user"
		target="_top"
		onclick="event.stopPropagation();"
		href="<?= $userLinkPath ?>">
		<span class="ui-icon ui-icon-common-user">
			<i style=" <?= ($iconPath !== null ? "background-image: url('". Uri::urnEncode($userImagePath) . "');" : '') ?>">
			</i>
		</span>
		<span class="sign-mysafe-grid-user-name">
				<?= $userFullName ?>
		</span>
	</a>
	<?php
	return (string)ob_get_clean();
};

$getDownloadResultFileTemplate = static function (?string $extensionName, ?string $downloadUrl): string {
	ob_start();
	?>
	<a href="<?= htmlspecialcharsbx($downloadUrl) ?>" class="ui-label ui-label-light sign-mysafe-download-result-file-wrapper">
		<div class="ui-label-inner sign-mysafe-download-result-file-inner">
			<div class="ui-icon ui-icon-file-<?= htmlspecialcharsbx($extensionName) ?> sign-mysafe-download-result-icon"><i></i></div>
			<div class="sign-mysafe-download-result-file-text">
				<?= htmlspecialcharsbx($extensionName) ?>
			</div>
		</div>
	</a>
	<?php
	return (string)ob_get_clean();
};

$prepareGridData = static function (array $documentData) use (
	$getDownloadResultFileTemplate,
	$getUserInfoTemplate,
	$getOpenSliderLinkTemplate,
	$getTextWithHoverTemplate
) {
	$data = [];

	$data['ID'] = htmlspecialcharsbx($documentData['ID']);

	$dateSignInfo = $documentData['DATE_SIGN_INFO'];
	$data['DATE_SIGN'] = $getTextWithHoverTemplate((string)$dateSignInfo['TEXT'], (string)$dateSignInfo['DETAIL']);

	$dateCreateInfo = $documentData['DATE_CREATE_INFO'];
	$data['DATE_CREATE'] = $getTextWithHoverTemplate((string)$dateCreateInfo['TEXT'], (string)$dateCreateInfo['DETAIL']);

	$titleInfo = $documentData['TITLE_INFO'];
	$data['TITLE'] = $getOpenSliderLinkTemplate((string)$titleInfo['TEXT'], (string)$titleInfo['DOCUMENT_LINK']);

	$signWithInfo = $documentData['SIGN_WITH_INFO'] ?? [];
	$data['SIGN_WITH'] = $getOpenSliderLinkTemplate((string) ($signWithInfo['TEXT'] ?? '') , (string)($signWithInfo['LINK'] ?? ''));

	$creatorUserInfo = $documentData['DOCUMENT_CREATOR_INFO'];
	$data['CREATED_BY'] = $getUserInfoTemplate(
		$creatorUserInfo['FULL_NAME'],
		$creatorUserInfo['LINK'],
		$creatorUserInfo['ICON']
	);

	$downloadResultFileInfo = $documentData['RESULT_FILE_INFO'];
	$data['DOWNLOAD_DOCUMENT'] = !$downloadResultFileInfo['ID'] ? '' : $getDownloadResultFileTemplate(
		$downloadResultFileInfo['EXTENSION'],
		$downloadResultFileInfo['DOWNLOAD_URL']
	);

	return $data;
};

$gridRows = [];
foreach ($arResult["DOCUMENTS"] as $documentData)
{
	$gridRows[] = [
		'data' => $prepareGridData($documentData),
	];
}

$stub = null;
if (!$arResult['USE_DEFAULT_STUB'] && count($gridRows) === 0)
{
	$stub = $arParams['STUB'];
}

\Bitrix\UI\Toolbar\Facade\Toolbar::addFilter([
	"GRID_ID" => $arParams["GRID_ID"],
	"FILTER_ID" => $arParams["FILTER_ID"],
	"FILTER" => $arResult["FILTER"],
	"FILTER_PRESETS" => $arResult['FILTER_PRESETS'],
	"DISABLE_SEARCH" => false,
	"ENABLE_LIVE_SEARCH" => true,
	"ENABLE_LABEL" => true,
	'THEME' => Bitrix\Main\UI\Filter\Theme::MUTED,
]);

$APPLICATION->IncludeComponent("bitrix:main.ui.grid",
	"",
	[
		"GRID_ID" => $arParams["GRID_ID"],
		"COLUMNS" => $arParams["COLUMNS"],
		"ROWS" => $gridRows,
		"NAV_OBJECT" => $arResult["NAVIGATION_OBJECT"],
		'SHOW_ROW_CHECKBOXES' => false,
		"SHOW_TOTAL_COUNTER" => true,
		"TOTAL_ROWS_COUNT" => $arResult["TOTAL_COUNT"],
		"ALLOW_COLUMNS_SORT" => true,
		"ALLOW_SORT" => true,
		"ALLOW_COLUMNS_RESIZE" => true,
		"AJAX_MODE" => "Y",
		"AJAX_OPTION_HISTORY" => "N",
		'STUB' => $stub,
	]);
?>
