<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

/** @var \CAllMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

if ($arResult)
{
	$manager = \Bitrix\BIConnector\Manager::getInstance();
	if ($manager->isExternalDashboardUrl($arResult['URL']))
	{
		?>
		<script>
			top.window.open('<?php echo CUtil::JSEscape($arResult['URL'])?>', '_blank');
		</script>
		<?php
	}
	else
	{
		?>
		<div class="biconnector-dashboard-container">
			<iframe frameborder="0" class="biconnector-dashboard-frame" height="100%" width="100%" src="<?php echo htmlspecialcharsBx($arResult['URL'])?>"></iframe>
		</div>
		<?php
	}
}
else
{
	ShowError(Loc::getMessage('CT_BBD_ERROR_NOT_FOUND'));
}
