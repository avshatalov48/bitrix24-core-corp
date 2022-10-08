<?php

define("NEED_AUTH", true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

if (is_string($_REQUEST["backurl"]) && mb_strpos($_REQUEST["backurl"], "/") === 0)
{
	LocalRedirect($_REQUEST["backurl"]);
}

$APPLICATION->SetTitle(GetMessage('EXTRANET_AUTH_PAGE_TITLE'));
?>
<p class="notetext"><font ><?= GetMessage('EXTRANET_AUTH_PAGE_TEXT1')?></font></p>
<p><a href="<?= SITE_DIR ?>"><?= GetMessage('EXTRANET_AUTH_PAGE_TEXT2')?></a></p>

<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");