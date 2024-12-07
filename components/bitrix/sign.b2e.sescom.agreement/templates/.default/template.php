<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main;

\Bitrix\Main\UI\Extension::load([
	'ui.buttons',
	'sign.v2.b2e.sign-ses-com-agreement',
]);

$region = Main\Application::getInstance()->getLicense()->getRegion();
$region = in_array($region, ['ru', 'by', 'kz'], true) ? 'en' : $region;
$bitrixServiceDomain = \Bitrix\Sign\Config\Storage::instance()->getBitrixServiceAddress($region);

?>

<?php ob_start() ?>

<div class="sign-agreement-content-wrapper">
	<p><?= Loc::getMessage('SIGN_B2E_SESCOM_AGREEMENT_TPL_AGREEMENT_CONSENT_TEXT_1', language: 'en') ?></p>
	<p><?= Loc::getMessage('SIGN_B2E_SESCOM_AGREEMENT_TPL_AGREEMENT_CONSENT_TEXT_2', language: 'en') ?></p>
	<p>
		<?=
			Loc::getMessage(
					'SIGN_B2E_SESCOM_AGREEMENT_TPL_AGREEMENT_CONSENT_TEXT_3',
				language: 'en'
			)
		?>
	</p>
	<p><?= Loc::getMessage('SIGN_B2E_SESCOM_AGREEMENT_TPL_AGREEMENT_CONSENT_TEXT_4', language: 'en') ?> </p>
	<div>
		<p> 1. <?= Loc::getMessage('SIGN_B2E_SESCOM_AGREEMENT_TPL_AGREEMENT_CONSENT_TEXT_LIST_1', language: 'en') ?> </p>
		<p>
			2. <?= Loc::getMessage('SIGN_B2E_SESCOM_AGREEMENT_TPL_AGREEMENT_CONSENT_TEXT_LIST_2', language: 'en') ?>
			<div class="sign-agreement-content__list-wrapper">
				<p><?= Loc::getMessage('SIGN_B2E_SESCOM_AGREEMENT_TPL_AGREEMENT_CONSENT_TEXT_LIST_2_POINT_1', language: 'en') ?> </p>
				<p><?= Loc::getMessage('SIGN_B2E_SESCOM_AGREEMENT_TPL_AGREEMENT_CONSENT_TEXT_LIST_2_POINT_2', language: 'en') ?> </p>
				<p><?= Loc::getMessage('SIGN_B2E_SESCOM_AGREEMENT_TPL_AGREEMENT_CONSENT_TEXT_LIST_2_POINT_3', language: 'en') ?> </p>
				<p><?= Loc::getMessage('SIGN_B2E_SESCOM_AGREEMENT_TPL_AGREEMENT_CONSENT_TEXT_LIST_2_POINT_4', language: 'en') ?> </p>
			</div>
		</p>
		<p>
			3. <?= Loc::getMessage('SIGN_B2E_SESCOM_AGREEMENT_TPL_AGREEMENT_CONSENT_TEXT_LIST_3', language: 'en') ?>
			<div class="sign-agreement-content__list-wrapper">
				<p><?= Loc::getMessage('SIGN_B2E_SESCOM_AGREEMENT_TPL_AGREEMENT_CONSENT_TEXT_LIST_3_POINT_1', language: 'en') ?> </p>
				<p><?= Loc::getMessage('SIGN_B2E_SESCOM_AGREEMENT_TPL_AGREEMENT_CONSENT_TEXT_LIST_3_POINT_2', language: 'en') ?> </p>
			</div>
		</p>
		<p>4. <?= Loc::getMessage('SIGN_B2E_SESCOM_AGREEMENT_TPL_AGREEMENT_CONSENT_TEXT_LIST_4', language: 'en') ?> </p>
		<p>5. <?= Loc::getMessage('SIGN_B2E_SESCOM_AGREEMENT_TPL_AGREEMENT_CONSENT_TEXT_LIST_5', language: 'en') ?> </p>
		<p>6. <?= Loc::getMessage('SIGN_B2E_SESCOM_AGREEMENT_TPL_AGREEMENT_CONSENT_TEXT_LIST_6', language: 'en') ?> </p>
		<p>7. <?= Loc::getMessage('SIGN_B2E_SESCOM_AGREEMENT_TPL_AGREEMENT_CONSENT_TEXT_LIST_7', language: 'en') ?> </p>
		<p>
			8. <?= Loc::getMessage(
					'SIGN_B2E_SESCOM_AGREEMENT_TPL_AGREEMENT_CONSENT_TEXT_LIST_8',
					[
						'#LINK#' => '<a href="' . $bitrixServiceDomain . '/terms/esignature-for-hr-rules.php" target="_blank">'
						. Loc::getMessage('SIGN_B2E_SESCOM_AGREEMENT_TPL_AGREEMENT_CONSENT_TEXT_8_LINK', language: 'en')
						. '</a>'
					],
				'en'
				)
			?>
		</p>
	</div>
	<p>
		<?= Loc::getMessage(
				'SIGN_B2E_SESCOM_AGREEMENT_TPL_AGREEMENT_CONSENT_TEXT_5',
				[
					'#LINK#' => '<a href="' . $bitrixServiceDomain . '/terms/bitrix24sign-rules.php" target="_blank">'
						. Loc::getMessage('SIGN_B2E_SESCOM_AGREEMENT_TPL_AGREEMENT_CONSENT_TEXT_5_LINK', language: 'en')
						. '</a>',
				],
			'en'
		)
		?>
	</p>
</div>

<?php
	$content = ob_get_clean();
?>

<script>
	BX.ready(() => {
		new BX.Sign.V2.B2e.SignSesComAgreement(
			<?= \Bitrix\Main\Web\Json::encode([
				'body' => $content,
				'title' => Loc::getMessage('SIGN_B2E_SESCOM_AGREEMENT_TPL_AGREEMENT_CONSENT_TITLE', language: 'en'),
				'buttonText' => Loc::getMessage('SIGN_AGREEMENT_SUCCESS_BUTTON_TEXT', language: 'en'),
			]) ?>
		).show();
	});
</script>
