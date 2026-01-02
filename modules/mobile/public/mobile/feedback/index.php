<?php

use Bitrix\Mobile\Feedback\FeedbackFormProvider;
use Bitrix\Main\Loader;

require($_SERVER["DOCUMENT_ROOT"]."/mobile/headers.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$currentFormData = FeedbackFormProvider::getFormData($_REQUEST["formId"]);
$allForms = FeedbackFormProvider::getFormConfig($_REQUEST["formId"]);
if (Loader::includeModule('ui'))
{
	global $APPLICATION;
	$APPLICATION->IncludeComponent(
		'bitrix:ui.feedback.form',
		'',
		[
			'ID' => 'mobile-feedback-form' . $currentFormData['formId'],
			'FORMS' => $allForms,
			'INLINE' => true,
			'SHOW_TITLE' => 'N',
		],
		null,
	);
?>
<script>
	window.addEventListener('b24:form:init', (event) => {
		let form = event.detail.object;
		window.setHiddenFields = (fields) => {
			for (let field in fields)
			{
				form.setProperty(field, fields[field]);
			}
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