<?
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
?>

<?
$APPLICATION->IncludeComponent("bitrix:mobile.crm.convert.sync", "", array());
?>

<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');?>
