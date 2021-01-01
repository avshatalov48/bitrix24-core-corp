<?
define("NEED_AUTH", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

if (is_string($_REQUEST["backurl"]) && mb_strpos($_REQUEST["backurl"], "/") === 0)
{
	LocalRedirect($_REQUEST["backurl"]);
}

$APPLICATION->SetTitle("Anmeldung");
?>
<p class="notetext"><font >Sie haben sich erfolgreich registriert und angemeldet.</font></p>
<p><a href="<?=SITE_DIR?>">Zurück zur Homepage</a></p>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>