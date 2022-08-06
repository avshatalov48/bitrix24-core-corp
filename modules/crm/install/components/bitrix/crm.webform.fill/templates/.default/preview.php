<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

\Bitrix\Main\UI\Extension::load("ui.buttons");
\Bitrix\Main\UI\Extension::load("ui.alerts");

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var \CMain $APPLICATION */

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$arResult['PREVIEW']['SCRIPT'] = str_replace('(Date.now()/180000|0)', '(Date.now())', $arResult['PREVIEW']['SCRIPT']);

?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/html">
	<head>
		<?$APPLICATION->ShowHead();?>

		<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
		<meta http-equiv="Pragma" content="no-cache" />
		<meta http-equiv="Expires" content="0" />

		<style type="text/css">
			.content-wrap {
				height: 100%;
				display: flex;
				flex-direction: column;
				justify-content: center;
			}
			.content {
				margin: 0 0 30px 0;
				background-color: transparent;
			}
		</style>
	</head>
	<body class="<?$APPLICATION->showProperty("BodyClass")?> crm-webform-iframe crm-webform-preview">
		<div class="content-wrap">
			<div class="content">
				<?if(!empty($arResult['ERRORS'])):
					foreach($arResult['ERRORS'] as $error)
					{
						ShowError($error);
					}
					?>
					<script>
						BX.ready(function(){
							BX.CrmWebForm = new CrmWebForm({});
						});
					</script>
					<?
				else:
					switch ($arResult['PREVIEW']['TYPE'])
					{
						case 'click':
							$generateButton = 
								isset($arResult['PREVIEW']['VIEWS']['click']['button']['use'])
								&& ($arResult['PREVIEW']['VIEWS']['click']['button']['use'] === '1')
							;
							$previewButton = $generateButton
								? ''
								: '<button class="ui-btn" style="margin: 30px auto; display: block; font-size: 1.3em;">Preview</button>'
							;
							echo '
								<div style="border: 3px solid lightgray; margin: 0 auto; border-radius: 10px; padding: 30px;">
									'.$arResult['PREVIEW']['SCRIPT'].'
									'.$previewButton.'
								</div>
							';
							break;

						case 'auto':
							$countdown = $arResult['PREVIEW']['VIEWS']['auto']['delay'] ?? 5;
							echo '
								<div class="ui-alert ui-alert-warning">
									<span class="ui-alert-message">Form preview after: <span id="countdown">'.htmlspecialcharsbx($countdown).'s</span><span>
								</div>
								<script>
									var countDownDate = new Date().getTime() + '.htmlspecialcharsbx($countdown).' * 1000;
									var previewInterval = setInterval(function() {
										var now = new Date().getTime();
										var distance = countDownDate - now;
										var seconds = Math.floor(distance / 1000); // Math.floor((distance % (1000 * 60)) / 1000);
										document.getElementById("countdown").innerHTML = seconds + "s";
										if (distance < 0) {
											clearInterval(previewInterval);
											document.getElementById("countdown").innerHTML = "now";
										}
									}, 1000);
								</script>
							';
							echo $arResult['PREVIEW']['SCRIPT'];
							break;

						case 'inline':
						default:
							echo $arResult['PREVIEW']['SCRIPT'];
							break;
					}
				endif;?>
			</div>
		</div>
	</body>
</html>
<?