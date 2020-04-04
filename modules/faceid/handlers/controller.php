<?php
if(!\Bitrix\Main\Loader::includeModule('faceid'))
	return false;

if (is_object($APPLICATION))
	$APPLICATION->RestartBuffer();

\Bitrix\FaceId\Log::write($_POST, 'PORTAL HIT');

$params = $_POST;
$hash = $params["BX_HASH"];
unset($params["BX_HASH"]);

// CLOUD HITS

if(
	$params['BX_TYPE'] == \Bitrix\FaceId\Http::TYPE_BITRIX24 && \Bitrix\FaceId\Http::requestSign($params['BX_TYPE'], md5(implode("|", $params)."|".BX24_HOST_NAME)) === $hash ||
	$params['BX_TYPE'] == \Bitrix\FaceId\Http::TYPE_CP && \Bitrix\FaceId\Http::requestSign($params['BX_TYPE'], md5(implode("|", $params))) === $hash
)
{
	$params = \Bitrix\Main\Text\Encoding::convertEncoding($params, 'UTF-8', SITE_CHARSET);

	$result = \Bitrix\FaceId\Controller::receiveCommand($params['BX_COMMAND'], $params);
	if (is_null($result))
	{
		echo "You don't have access to this page.";
	}
	else
	{
		echo \Bitrix\Main\Web\Json::encode($result);
	}
}
else
{
	echo "You don't have access to this page.";
}

CMain::FinalActions();
die();