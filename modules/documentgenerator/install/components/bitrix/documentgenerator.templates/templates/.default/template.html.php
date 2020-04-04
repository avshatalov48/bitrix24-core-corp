<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
\Bitrix\Main\UI\Extension::load("ui.buttons");
\Bitrix\Main\UI\Extension::load("ui.buttons.icons");
\CJSCore::init("sidepanel");
?>

<?
if (isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y")
{
$APPLICATION->RestartBuffer(); //сбрасываем весь вывод
?>

<!DOCTYPE html>
<html>
	<head>
		<?$APPLICATION->ShowHead(); ?>
	</head>
	<body>
		<div class="docs-template-wrap-slider">
			<div class="pagetitle-wrap">
				<div class="docs-template-pagetitle-wrap">
					<div class="docs-template-pagetitle-inner pagetitle-inner-container">
						<div class="pagetitle">
							<span class="docs-template-pagetitle-item pagetitle-item" id="pagetitle">Список шаблонов</span>
						</div>
						<div class="pagetitle-container pagetitle-flexible-space pagetitle-container-docs-template">
							<? $APPLICATION->IncludeComponent(
								"bitrix:main.ui.filter",
								"",
								array(
									"FILTER_ID" => "1",
								)
							); ?>
						</div>
						<div class="pagetitle-container pagetitle-align-right-container">
							<button class="ui-btn ui-btn-md ui-btn-light-border ui-btn-icon-setting"></button>
							<button class="ui-btn ui-btn-md ui-btn-primary ui-btn-primary-docs-template">Загрузить</button>
						</div>
					</div>
				</div>
			</div>
			<div class="docs-template-info-inner">
				<div class="docs-template-info-message">Узнайте как настроить и загрузить собственные шаблоны документов
					<a class="docs-template-info-link" href="#">подробнее</a>
				</div>
			</div>
			<div class="docs-template-grid">
				<div class="docs-template-grid-img"></div>
			</div>
		</div>
	</body>
</html><?
}
else
{ ?>
	<div class="docs-template-wrap">
		<div class="pagetitle-wrap">
			<div class="docs-template-pagetitle-wrap">
				<div class="docs-template-pagetitle-inner pagetitle-inner-container">
					<div class="pagetitle">
						<span class="docs-template-pagetitle-item pagetitle-item" id="pagetitle">Список шаблонов</span>
					</div>
					<div class="pagetitle-container pagetitle-flexible-space pagetitle-container-docs-template">
						<? $APPLICATION->IncludeComponent(
							"bitrix:main.ui.filter",
							"",
							array(
								"FILTER_ID" => "1",
							)
						); ?>
					</div>
					<div class="pagetitle-container pagetitle-align-right-container">
						<button class="ui-btn ui-btn-md ui-btn-light-border ui-btn-icon-setting"></button>
						<button class="ui-btn ui-btn-md ui-btn-primary ui-btn-primary-docs-template">Загрузить</button>
					</div>
				</div>
			</div>
		</div>
		<div class="docs-template-info-inner">
			<div class="docs-template-info-message">Узнайте как настроить и загрузить собственные шаблоны документов
				<a class="docs-template-info-link" href="#">подробнее</a>
			</div>
		</div>
		<div class="docs-template-grid">
			<div class="docs-template-grid-img"></div>
		</div>
	</div>
<? } ?>


