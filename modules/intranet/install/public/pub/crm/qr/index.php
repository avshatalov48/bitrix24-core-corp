<?php

define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC", "Y");
define('SKIP_TEMPLATE_AUTH_ERROR', true);
define('NOT_CHECK_PERMISSIONS', true);
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main;
use Bitrix\UI\Barcode\Barcode;

if (!Main\Loader::includeModule("crm"))
{
	LocalRedirect("/");
}

global $APPLICATION;

if (isset($_GET['img']) && $_GET['img'] === 'y')
{
	Main\Loader::includeModule("ui");
	$uri = new Main\Web\Uri(Main\Application::getInstance()->getContext()->getRequest()->getRequestUri());
	$uri->deleteParams(['img']);
	$hostUrl = Main\Engine\UrlManager::getInstance()->getHostUrl();

	(new Barcode())
		->option('w', 300)
		->option('h', 300)
		->print($hostUrl . $uri->getLocator());

	Main\Application::getInstance()->end();
}
?>
<!DOCTYPE html>
<html class="crm-automation-pub-qr--modifier" <?php
	  if (LANGUAGE_ID == "tr"): ?>lang="<?= LANGUAGE_ID ?>"<?php
endif ?>>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php
	$APPLICATION->showHead();
	?>
</head>
<body>
<?php

$APPLICATION->IncludeComponent(
	'bitrix:crm.automation.pub.qr',
	"",
	[
		'QR_ID' => array_key_first($_GET),
		'VIEW' => !empty($_GET['code']) ? 'code' : 'page',
	]
);
?>
</body>
</html>
