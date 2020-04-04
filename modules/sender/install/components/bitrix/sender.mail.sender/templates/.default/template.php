<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CAllMain $APPLICATION */
/** @global CAllUser $USER */
/** @global CAllDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

use Bitrix\Main\Web\Json;
use Bitrix\Main\Localization\Loc;

$GLOBALS['APPLICATION']->IncludeComponent('bitrix:main.mail.confirm', '', array());

$containerId = 'sender-ui-mailbox-selector';
?>
<script type="text/javascript">
	BX.ready(function () {
		BX.Sender.UI.Mailbox.Selector.init(<?=Json::encode(array(
			'containerId' => $containerId,
			'list' => $arResult['LIST'],
			'mess' => array(
				'addAddress' => Loc::getMessage('SENDER_UI_MAILBOX_SELECTOR_ADD')
			)
		))?>);
	});
</script>
<div id="<?=htmlspecialcharsbx($containerId)?>" class="sender-ui-mailbox-selector-wrap">
	<span class="sender-ui-mailbox-icon"<?if ($arResult['CURRENT']['icon']):?> style="background-image: url(<?=htmlspecialcharsbx($arResult['CURRENT']['icon'])?>)<?endif?>"></span>
	<span class="sender-ui-mailbox-dropdown"  data-role="mailbox-wrap">
		<span data-role="mailbox" class="sender-ui-mailbox-name">
			<?=htmlspecialcharsbx($arResult['CURRENT']['name'])?>
			<?=htmlspecialcharsbx(empty($arResult['CURRENT']['email']) ? '' : '<' . $arResult['CURRENT']['email'] . '>')?>
			<?=(empty($arResult['LIST']) && empty($arResult['CURRENT']['email']) ? Loc::getMessage('SENDER_UI_MAILBOX_SELECTOR_SETUP') : '')?>
		</span>
	</span>
	<input data-role="mailbox-input" type="hidden" name="<?=htmlspecialcharsbx($arParams['INPUT_NAME'])?>" value="<?=htmlspecialcharsbx($arResult['CURRENT']['sender'])?>">
</div>