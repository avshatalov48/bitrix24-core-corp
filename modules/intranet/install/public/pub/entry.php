<?php

define('SKIP_TEMPLATE_AUTH_ERROR', true);
define('NOT_CHECK_PERMISSIONS', true);

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';

\Bitrix\Main\Data\StaticHtmlCache::getInstance()->markNonCacheable();

$APPLICATION->setTitle(
	isModuleInstalled('bitrix24')
		? COption::getOptionString('bitrix24', 'site_title', '')
		: COption::getOptionString('main', 'site_name', '')
);

CModule::includeModule('mail');

?>

<div id="mail-entry-loader"></div>

<script type="text/javascript">

BX.ready(function()
{
	if (window.location.hash.length > 1)
	{
		BX.ajax({
			method: 'POST',
			url: '/bitrix/tools/mail_auth.php',
			data: { token: window.location.hash.substr(1) },
			dataType: 'json',
			onsuccess: function(json)
			{
				if (json.result != 'error')
				{
					var location = document.createElement('a');
					location.href = json.result;

					if (location.search.length > 0 || window.location.search.length > 0)
					{
						location.search = location.search.replace(/^\?*/ig, '?') + window.location.search.replace(/^\?*/ig, '&');
						location.search = location.search.replace(/&{2,}/ig, '&').replace(/^\?&/ig, '?').replace(/&$/ig, '');
					}

					window.location.replace(location.href);

					setTimeout(function()
					{
						BX.hide(BX('mail-entry-loader'), 'block');
						pubTemplate.showError(204, { target: json.backurl ? json.backurl : location.href });
					}, 2000);
				}
				else
				{
					BX.hide(BX('mail-entry-loader'), 'block');
					pubTemplate.showError(json.error);
				}
			}
		});
	}
	else
	{
		BX.hide(BX('mail-entry-loader'), 'block');
		pubTemplate.showError(400);
	}
});

</script>


<?

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';
