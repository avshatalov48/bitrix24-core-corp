<?php

/**
 * @global CMain $APPLICATION
 */

use Bitrix\Main\Config\Option;

if(!check_bitrix_sessid())
{
	return;
}

IncludeModuleLangFile(__FILE__);

function checkIsLocalUrl(string $url): bool
{
	$parsedUrl = parse_url($url);
	$host = $parsedUrl['host'] ?? '';

	$isLocalhost = strtolower($host) === 'localhost';
	$isDefaultIP = $host === '0.0.0.0';
	$isLocalIP = (
		preg_match('#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#', $host)
		&& preg_match('#^(127|10|172\.16|192\.168)\.#', $host)
	);

	return $isLocalhost || $isDefaultIP || $isLocalIP;
}

$ex = $APPLICATION->GetException();
if ($ex)
{
	CAdminMessage::ShowMessage(Array(
		'TYPE' => 'ERROR',
		'MESSAGE' => GetMessage('MOD_INST_ERR'),
		'DETAILS' => $ex->GetString(),
		'HTML' => true,
	));
}
else
{
	CAdminMessage::ShowNote(GetMessage('MOD_INST_OK'));

	$portalUrl = Option::get('voximplant', 'portal_url');
	$errorMessageList = [];
	if ($portalUrl === '')
	{
		$errorMessageList[] = GetMessage('VOXIMPLANT_PUBLIC_URL_EMPTY_ERROR');
	}
	else
	{
		if (mb_strpos($portalUrl, 'http://') === false && mb_strpos($portalUrl, 'https://') === false)
		{
			$errorMessageList[] = GetMessage('VOXIMPLANT_PUBLIC_URL_WITHOUT_PROTOCOL_ERROR');
		}

		if (checkIsLocalUrl($portalUrl))
		{
			$errorMessageList[] = GetMessage('VOXIMPLANT_PUBLIC_URL_LOCAL_ERROR');
		}
	}

	foreach ($errorMessageList as $errorMessage)
	{
		?>
			<b><?=GetMessage('MOD_INST_ERR')?></b>
			<br>
			<b style="color:red"><?=$errorMessage?></b>
		<?php
	}
}
?>
<div style="font-size: 12px;"></div>
<br>
<form action="<?=$APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?=LANG?>">
	<input type="submit" name="" value="<?=GetMessage('MOD_BACK')?>">
</form>
