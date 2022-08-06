<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\UI\Extension;

Loc::loadMessages(__DIR__ . '/template.php');
Extension::load(['marketplace', 'ui.fonts.opensans']);

$id = 'intranet_binding_menu_' . $arParams['SECTION_CODE'];
$frequency = $arResult['FREQUENCY_MENU_ITEM'];
$isSwitcher = in_array($this->getPageName(), ['crm_switcher', 'tasks_switcher']);
?>

<div class="ui-btn-split ui-btn-light-border ui-btn-themes intranet-binding-menu-btn <?= $isSwitcher ? ' intranet-binding-menu-btn-round' : '';?>">
	<a href="<?= $frequency ? \htmlspecialcharsbx($frequency['href']) : '#';?>" <?
	?><?if (!$frequency){?>data-slider-ignore-autobinding="true"<?}?> <?
	   ?>id="<?= $id;?>_top" class="ui-btn-main" <?if (isset($frequency['onclick'])){?>onclick="<?= htmlspecialcharsbx($frequency['onclick']);?>; return false;"<?}?>>
		<?= $arResult['FREQUENCY_MENU_ITEM']
			? TruncateText($arResult['FREQUENCY_MENU_ITEM']['text'], 50)
			: Loc::getMessage('INTRANET_CMP_BIND_MENU_BUTTON_NAME');
		?>
	</a>
	<span class="ui-btn-menu" id="<?= $id;?>"></span>
</div>

<script type="text/javascript">
	BX.ready(function()
	{
		(new BX.Intranet.Binding.Menu(
			'<?= $id;?>',
			<?= \CUtil::phpToJSObject($arResult['ITEMS']);?>,
			{
				bindingId: '<?= \CUtil::jsEscape($arResult['BINDING_ID']);?>',
				ajaxPath: '<?= \CUtil::jsEscape($this->getComponent()->getPath());?>/ajax.php',
				frequencyItem: <?= \CUtil::phpToJSObject($frequency);?>
			}
		)).binding();
	});
</script>
