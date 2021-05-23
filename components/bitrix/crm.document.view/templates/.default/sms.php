<?

use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load("ui.buttons");
\Bitrix\Main\UI\Extension::load("ui.buttons.icons");
\Bitrix\Main\UI\Extension::load("ui.alerts");
\CJSCore::init(["sidepanel"]);
$this->IncludeLangFile();

if($arParams['IS_SLIDER'])
{
	$APPLICATION->RestartBuffer();
	?>
	<!DOCTYPE html>
	<html>
	<head>
		<script data-skip-moving="true">
			// Prevent loading page without header and footer
			if (window === window.top)
			{
				window.location = "<?=CUtil::JSEscape((new \Bitrix\Main\Web\Uri(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getRequestUri()))->deleteParams(['IFRAME', 'IFRAME_TYPE']));?>" + window.location.hash;
			}
		</script>
		<?$APPLICATION->ShowHead(); ?>
	</head>
	<body class="docs-preview-slider-wrap">
	<div class="docs-preview-title">
		<div class="pagetitle-wrap">
			<div class="pagetitle-inner-container">
				<div class="pagetitle">
				<span id="pagetitle" class="pagetitle-item docs-preview-pagetitle-item"><?= Loc::getMessage('CRM_DOCUMENT_VIEW_COMPONENT_SMS_TITLE');?>
				</span>
				</div>
			</div>
		</div>
	</div>
<?}

if(isset($arResult['ERRORS']) && !empty($arResult['ERRORS']))
{
?>
<div class="ui-alert ui-alert-danger ui-alert-icon-danger ui-alert-text-center">
<span class="ui-alert-message"><?
	if(is_array($arResult['ERRORS']))
	{
		foreach($arResult['ERRORS'] as $error)
		{
			echo htmlspecialcharsbx($error);
			echo '<br />';
		}
	}
	else
	{
		echo htmlspecialcharsbx($arResult['ERRORS']);
	}
	?></span>
</div>
<?
}
else
{
	$APPLICATION->IncludeComponent('bitrix:crm.sms.send', '', $arResult['smsConfig'], $this->getComponent(), ['HIDE_ICONS' => 'Y']);
}

if($arParams['IS_SLIDER'])
{
	?></body>
	</html><?
	\CMain::FinalActions();
}