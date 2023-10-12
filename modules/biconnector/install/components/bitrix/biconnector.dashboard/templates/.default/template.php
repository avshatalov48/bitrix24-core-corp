<?php
/**
 * Bitrix vars
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CUser $USER
 * @var CDatabase $DB
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $templateFile
 * @var string $templateFolder
 * @var string $componentPath
 * @var CBitrixComponent $component
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

if ($arResult)
{
	$manager = \Bitrix\BIConnector\Manager::getInstance();
	if ($manager->isExternalDashboardUrl($arResult['URL']))
	{
		?>
		<script>
			top.window.open('<?php echo CUtil::JSEscape($arResult['URL'])?>', '_blank');
			top.BX.SidePanel.Instance.close();
		</script>
		<?php
	}
	else
	{
		?>
		<div class="biconnector-dashboard-container">
			<iframe frameborder="0" class="biconnector-dashboard-frame" height="100%" width="100%" src="<?php echo htmlspecialcharsbx($arResult['URL'])?>"></iframe>
		</div>
		<?php
	}
}
else
{
	ShowError(Loc::getMessage('CT_BBD_ERROR_NOT_FOUND'));
}
