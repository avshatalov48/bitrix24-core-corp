<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$site = LANGUAGE_ID == 'de'
	? 'http://www.bitrix24.de'
	: (\Bitrix\Main\Localization\Loc::getDefaultLang(LANGUAGE_ID) == 'ru'
		? 'http://www.bitrix24.ru'
		: 'http://www.bitrix24.com'
	);
?>
<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html;" charset="<?=LANG_CHARSET?>">
	<title></title>
</head>
<body>
<div id="mailsub">
	<table width="100%">
		<tbody>
		<tr>
			<td bgcolor="#2fc7f7" align="center" style="background:#2fc7f7 url(<?=$site?>/mailimg/new/bg-clouds.png) 50% 58px no-repeat;">

				<table width="700" align="center" cellpadding="0" cellspacing="0" border="0">
					<tbody>
					<tr>
						<td width="700" colspan="3" style="height: 120px;">
							<table width="100%">
								<tbody>
								<tr>
									<td width="40%" align="left">
										<a href="<?=$site;?>/?utm_source=trigger_mail&utm_medium=email&utm_campaign=reg"><img src="<?=$site;?>/mailimg/new/logo-big<?=LANGUAGE_ID == 'ua' ? '-ua' : '';?>.png" style="border:none" alt=""></a>
									</td>
									<td width="60%" align="right" style="color: #ffffff; font-size: 32px; font-family: Verdana;" valign="middle"></td>
								</tr>
								</tbody>
							</table>

						</td>
					</tr>
					<tr>
						<td height="8" bgcolor="#ffffff" width="6" style="background:url(<?=$site?>/mailimg/new/tl.png)"></td>
						<td height="8" bgcolor="#ffffff" width="700"></td>
						<td height="8" bgcolor="#ffffff" width="6" style="background:url(<?=$site?>/mailimg/new/tr.png)"></td>
					</tr>
					<tr>
						<td width="700" bgcolor="#ffffff" colspan="3">
							<table width="100%" cellpadding="15">
								<tbody>
								<tr>
									<td>


