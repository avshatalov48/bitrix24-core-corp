<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->setTitle('Bitrix24.Sign');

\CJSCore::Init("loader");
\Bitrix\Main\UI\Extension::load('sign.v2.b2e.sign-link');

$memberId = (int)($_GET['memberId'] ?? 0);
?>
	<div id="sign-signing-link-container"></div>
	<script defer>
		BX.ready(function () {
			(new BX.Sign.V2.B2e.SignLink({memberId: <?= $memberId ?>}))
				.renderTo(document.getElementById('sign-signing-link-container'), false)
			;
		});
	</script>
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
