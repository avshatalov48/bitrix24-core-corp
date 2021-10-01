<?require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');?>
<?
if(\Bitrix\Main\Loader::includeModule('imopenlines'))
{
	$url = \Bitrix\ImOpenLines\Common::getContactCenterPublicFolder();
	$url .= 'connector/';
	$queryString = \Bitrix\Main\Context::getCurrent()->getServer()->getValues()['QUERY_STRING'];
	if(!empty($queryString))
	{
		$url .= '?' . $queryString;
	}

	LocalRedirect($url);
}
?>
<?require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');?>
