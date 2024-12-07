<?php

use Bitrix\Mobile\Feedback\FeedbackFormProvider;

require($_SERVER["DOCUMENT_ROOT"]."/mobile/headers.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$formData = FeedbackFormProvider::getFormData($_REQUEST["formId"]);
if (empty($formData)) : ?>
	<span>Form not found</span>
<?php else : ?>
	<script data-b24-form="<?=$formData["data-b24-form"]?>" data-skip-moving="true"> (function (w, d, u) {
			var s = d.createElement('script');
			s.async = false;
			s.src = u + '?' + (Date.now() / 180000 | 0);
			var h = d.getElementsByTagName('script')[0];
			h.parentNode.insertBefore(s, h);
		})(window, document, '<?=$formData["uri"]?>');
	</script>

	<script>
		window.addEventListener('b24:form:init', (event) => {
			window.webkit.messageHandlers.transport.postMessage({"event": "b24:form:init"});
			let form = event.detail.object;
			window.setHiddenFields = (fields) => {
				for (let field in fields)
				{
					form.setProperty(field, fields[field]);
				}
			}
		});
	</script>
<?php endif;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");