<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;

use Bitrix\Main\UI;

UI\Extension::load(
	[
		'ui.fonts.opensans',
		'ui.hint'
	]
);

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
/** $arResult["CONNECTION_STATUS"]; */
/** $arResult["REGISTER_STATUS"]; */
/** $arResult["ERROR_STATUS"]; */
/** $arResult["SAVE_STATUS"]; */

Loc::loadMessages(__FILE__);

Loc::loadMessages($_SERVER["DOCUMENT_ROOT"] . '/bitrix/components/bitrix/imconnector.settings/templates/.default/template.php');

CJSCore::Init(['popup', 'ui.fonts.opensans']);
$this->addExternalJs('/bitrix/components/bitrix/imconnector.connector.settings/templates/.default/script.js');
?>
<?if(empty($arResult['RELOAD'])):?>
<div class="im-connector-settings-wrapper">
	<div class="im-connector-settings-b24-logo">
		<?=Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_LOGO')?>
	</div>

	<div class="im-connector-settings-content">

		<div class="im-connector-settings-title">
			<div class="im-connector-settings-title-item"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_CONFIGURE_CHANNEL')?></div>
			<div class="im-connector-settings-title-border"></div>
		</div><!--im-connector-settings-title-->

		<div class="im-connector-settings-channel-container">
			<?if((!empty($arResult['LIST_LINE']) && (count($arResult['LIST_LINE'])>1 || !empty($arResult['PATH_TO_ADD_LINE']))) || (!empty($arResult['LIST_LINE']) && count($arResult['LIST_LINE'])==1) || (!empty($arResult['PATH_TO_ADD_LINE'])) || (!empty($arResult['ACTIVE_LINE']['URL_EDIT']))):?>
			<div class="im-connector-settings-channel-options">
				<span class="im-connector-settings-channel-options-name"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_OPEN_LINE')?></span>
				<?if(!empty($arResult['LIST_LINE']) && (count($arResult['LIST_LINE'])>1 || !empty($arResult['PATH_TO_ADD_LINE']))):?>
					<span class="im-connector-settings-channel-options-line" data-role="im-connector-select"><?=htmlspecialcharsbx($arResult['ACTIVE_LINE']['NAME'])?></span>
				<?elseif(!empty($arResult['LIST_LINE']) && count($arResult['LIST_LINE'])==1):?>
					<span class="im-connector-settings-channel-options-tune"><?=htmlspecialcharsbx($arResult['ACTIVE_LINE']['NAME'])?></span>
				<?elseif(!empty($arResult['PATH_TO_ADD_LINE'])):?>
					<a href="<?=$arResult['PATH_TO_ADD_LINE']?>" onclick="createLine(); return false;" class="im-connector-settings-channel-options-tune"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_CREATE_OPEN_LINE')?></a>
				<?endif;?>
			</div><!--im-connector-settings-channel-options-->
			<?endif;?>

			<div class="im-connector-settings-channel-inner">
			<?if(!empty($arResult['ACTIVE_LINE'])):?>
				<div class="imconnector-new" id="imconnector-new">
				<?$APPLICATION->IncludeComponent(
					$arResult['COMPONENT'],
					"mobile",
					[
						'LINE' => $arResult['ACTIVE_LINE']['ID'],
						'CONNECTOR' => $arResult['ID'],
						'AJAX_MODE' => 'Y',
						'AJAX_OPTION_ADDITIONAL' => '',
						'AJAX_OPTION_HISTORY' => 'N',
						'AJAX_OPTION_JUMP' => 'Y',
						'AJAX_OPTION_STYLE' => 'Y',
						'INDIVIDUAL_USE' => 'Y',
						'MOBILE' => 'Y'
					]
				);?>
				</div>
				<?=$arResult['LANG_JS_SETTING'];?>
			<?elseif(empty($arResult['ACTIVE_LINE']) && !empty($arResult['PATH_TO_ADD_LINE'])):?>
				<div class="imconnector-settings-message imconnector-settings-message-error">
				<?=Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_NO_OPEN_LINE')?>
				</div>
			<?else:?>
				<div class="imconnector-settings-message imconnector-settings-message-error">
				<?=Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_NO_OPEN_LINE_AND_NOT_ADD_OPEN_LINE')?>
				</div>
			<?endif;?>
			</div><!--im-connector-settings-channel-inner-->

		</div><!--im-connector-settings-channel-container-->

	</div><!--im-connector-settings-content-->


</div><!--im-connector-settings-wrapper-->

	<?if(!empty($arResult['LIST_LINE']) && (count($arResult['LIST_LINE'])>1 || !empty($arResult['PATH_TO_ADD_LINE']))):?>
		<div class="im-connector-select-popup" id="im-connector-select">
			<div class="im-connector-select-popup-wrapper">
				<?if(!empty($arResult['LIST_LINE'])):?>
					<?foreach ($arResult['LIST_LINE'] as $line):?>
						<div class="im-connector-select-popup-item">
							<?if(empty($line['ACTIVE'])):?>
								<a href="<?=$line['URL']?>" class="im-connector-select-link"><?=$line['NAME']?></a>
							<?else:?>
								<span class="im-connector-select-link"><?=$line['NAME']?></span>
							<?endif;?>
						</div>
					<?endforeach;?>
				<?endif;?>

				<?if(!empty($arResult['PATH_TO_ADD_LINE'])):?>
					<div class="im-connector-select-popup-item">
						<a href="<?=$arResult['PATH_TO_ADD_LINE']?>" onclick="createLine(); return false;" class="im-connector-select-link"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_CREATE_OPEN_LINE')?></a>
					</div>
				<?endif;?>
			</div>
			<div class="im-connector-select-popup-close" id="im-connector-select-popup-close"></div>
		</div>
		<div class="im-connector-overlay" id="im-connector-overlay"></div>
	<?endif;?>

<script>

	var connectorSelectLink = document.querySelector('[data-role="im-connector-select"]');
	if(connectorSelectLink)
	{
		var popupId = connectorSelectLink.getAttribute('data-role');
		var connectorSelectPopup = document.getElementById(popupId);
		var popupWrapper =  connectorSelectPopup.firstElementChild;
	}

	var overlayLayer = document.getElementById('im-connector-overlay');
	var overlayLayerClose = document.getElementById('im-connector-select-popup-close');

	function connectorSelect()
	{
		if(connectorSelectLink)
		{
			connectorSelectPopup.classList.toggle('im-connector-select-popup-show');
			overlayLayer.classList.toggle('im-connector-overlay-show');
			popupWrapper.style.height = connectorSelectPopup.offsetHeight + 'px';
		}
	}

	function connectorClear()
	{
		connectorSelectPopup.classList.toggle('im-connector-select-popup-show');
		overlayLayer.classList.toggle('im-connector-overlay-show');
		popupWrapper.style.height = '';
	}

	if(connectorSelectLink)
	{
		overlayLayer.addEventListener('click', connectorClear, false);
		connectorSelectLink.addEventListener('click', connectorSelect, false);
		overlayLayerClose.addEventListener('click', connectorClear, false);
	}

	function createLine() {
		var newLine = new BX.ImConnectorConnectorSettings();
		newLine.createLine("<?=str_replace('#ID#', $arResult['ID'], $arResult["PATH_TO_CONNECTOR_LINE"])?>");
		connectorSelect();
	}

	BX.ready(function ()
	{
		BX.message({
			IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_ERROR_ACTION: '<? echo GetMessageJS('IIMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_ERROR_ACTION') ?>',
			IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_CLOSE: '<? echo GetMessageJS('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_CLOSE') ?>'
		});
	})

</script>

<?else:?>
	<html>
	<body>
	<script>
		window.reloadAjaxImconnector = function(urlReload, idReload)
		{
			if(
				parent &&
				parent.window &&
				parent.window.opener &&
				parent.window.opener.BX &&
				parent.window.opener.BX.ajax
			)
			{
				parent.window.opener.BX.ajax.insertToNode(urlReload, idReload);
			}
			window.close();
		};
		reloadAjaxImconnector(<?=CUtil::PhpToJSObject($arResult['URL_RELOAD'])?>, <?=CUtil::PhpToJSObject('comp_' . $arResult['RELOAD'])?>);
	</script>
	</body>
	</html>
<?endif;?>