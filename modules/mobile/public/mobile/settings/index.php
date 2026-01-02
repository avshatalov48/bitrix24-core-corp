<?php

use Bitrix\Mobile\Feedback\FeedbackFormProvider;
use Bitrix\Main\Loader;

require($_SERVER["DOCUMENT_ROOT"]."/mobile/headers.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$allForms = FeedbackFormProvider::getFormConfig($_REQUEST["formId"]);
$currentFormData = FeedbackFormProvider::getFormData($_REQUEST["formId"]);
$hiddenFields = FeedbackFormProvider::getHiddenFieldsParams($_REQUEST['hiddenFields']);

if (Loader::includeModule('ui'))
{
	global $APPLICATION;
	$APPLICATION->IncludeComponent(
		'bitrix:ui.feedback.form',
		'',
		[
			'ID' => 'mobile-feedback-form' . $currentFormData['formId'],
			'FORMS' => $allForms,
			'PRESETS' => $hiddenFields,
			'INLINE' => true,
			'SHOW_TITLE' => 'N',
		],
		null,
	);
?>
	<script data-skip-moving="true">
		window.addEventListener('b24:form:submit', function(event) {
			let form = event.detail.object;
			const senderPage = 'mobile_rating_drawer';
			if (
				Number(form.identification.id) === <?= $currentFormData['formId'] ?>
				&& '<?= $hiddenFields['sender_page'] ?? '' ?>' === senderPage
			) {
				BXMobileApp.Events.postToComponent('app-feedback:onFeedbackSend', [], 'background');
			}
		});

		window.addEventListener('b24:form:send:success', function(event) {
			let form = event.detail.object;
			if (Number(form.identification.id) === <?= $currentFormData['formId'] ?>) {
				setTimeout(() => {
					app.closeModalDialog({ drop: true });
				}, 1000);
			}
		});
	</script>
<?php
}
else
{?>
		<span>Form not found</span>
<?php
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
