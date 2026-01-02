<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** @var array $arParams */
/** @var array $arResult */

/** @var $APPLICATION */

\CJSCore::Init("loader");
\Bitrix\Main\UI\Extension::load([
	'sign.v2.grid.b2e.signers',
	'sign.v2.b2e.user-party-signers-list',
	'ui.entity-selector',
	'ui.buttons',
]);

$APPLICATION->SetTitle($arResult['LIST_TITLE'] ?? '');
?>

<div class="sign-b2e-signers-list-add-signer-container">
	<div class="sign-b2e-signers-list-add-signer-selector"></div>
</div>

<script>
	BX.ready(function () {
		// render user-party
		var userParty = new BX.Sign.V2.B2e.UserPartySignersList();
		document
			.querySelector('.sign-b2e-signers-list-add-signer-selector')
			.appendChild(userParty.getLayout())
		;

		// handler add signers button click
		document.querySelector('#sign-b2e-signers-signer-edit-save-btn')
			.addEventListener('click', function(event) {
				(new BX.Sign.V2.Grid.B2e.Signers())
					.handleAddSignersButtonClick(<?= (int)$arResult['LIST_ID'] ?>, userParty)
					.then(function () {
						setTimeout(function () {
							event.target.classList.remove('ui-btn-wait');
						}, 500);
					});
			})
		;
	});
</script>