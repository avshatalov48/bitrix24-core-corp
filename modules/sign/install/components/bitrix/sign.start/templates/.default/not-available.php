<?php

use Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}
\Bitrix\Main\UI\Extension::load([
    'ui.sidepanel-content',
]);

/** @var \CMain $APPLICATION */
/** @var array $arParams */

$APPLICATION->SetTitle($arParams['PAGE_TITLE'] ?? '');
\Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();


?>

<div id="stub-not-available"></div>
<script>
	const options = <?= Json::encode([
		'title' => $arParams['STUB_TITLE'] ?? '',
		'desc' => $arParams['STUB_DESC'] ?? '',
		'type' => 'notAvailable',
	]) ?>;
	BX.ready(() => {
		const stub = new BX.UI.Sidepanel.Content.StubNotAvailable(options);
		stub.renderTo(document.getElementById('stub-not-available'));
	});
</script>
