<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title></title>
<?
/*
This is commented to avoid Project Quality Control warning
$APPLICATION->ShowHead();
$APPLICATION->ShowTitle();
$APPLICATION->ShowPanel();
*/
$actionDescDomain = '<a href="'.htmlspecialcharsbx($arParams['TEMPLATE_WIDGET_URL']).'" style="color: #ffffff; font-weight: 300; font-size: 13px; line-height: 15px; font-family: Helvetica, Arial, sans-serif;">'.htmlspecialcharsbx($arParams['TEMPLATE_WIDGET_DOMAIN']).'</a>';
$arParams['TEMPLATE_ACTION_DESC'] = str_replace("#SITE_URL#", $actionDescDomain, htmlspecialcharsbx($arParams['TEMPLATE_ACTION_DESC']));
?>
</head>
<body style="padding: 0;margin: 0;">

	<table border="0" cellpadding="0" cellspacing="0" bgcolor="#337e96" style="background: #337e96; margin:0; padding:0 10px" width="100%">
		<tr>
			<td align="center" style="background: #337e96;">
				<center style="max-width: 600px; width: 100%;">
					<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; max-width: 600px; width: 100%;" width="100%">
						<tr>
							<td style="text-align: center;padding-top: 45px;padding-bottom: 16px;" align="center">
								<div style="padding-bottom:32px; text-align: center;"><span      style="color: #ffffff; font-weight: 300; font-size: 34px; line-height: 41px; font-family: Helvetica, Arial, sans-serif;"><?=htmlspecialcharsbx($arParams['TEMPLATE_ACTION_TITLE'])?></span></div>
								<div style="padding-bottom: 16px;text-align: center;"><a href="<?=htmlspecialcharsbx($arParams['TEMPLATE_WIDGET_URL'])?>" style="color: #CFDBDF; font-weight: 300; font-size: 15px; line-height: 18px; font-family: Helvetica, Arial, sans-serif;"><?=htmlspecialcharsbx($arParams['TEMPLATE_WIDGET_DOMAIN'])?></a></div>
								<div style="padding-bottom: 16px;text-align: center;"><span      style="color: #ffffff; font-weight: 300; font-size: 13px; line-height: 15px; font-family: Helvetica, Arial, sans-serif;"><?=$arParams['TEMPLATE_ACTION_DESC']?></span></div>
								<div style="padding: 40px 20px 10px;border-radius: 10px;background: #FFFFFF;;">