<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

/**
* @var array $arResult
*
*/

\Bitrix\Main\UI\Extension::load([
	'ui.buttons',
]);

?>


<div class="biconnector-create-superset-wrapper">
	<div class="biconnector-create-superset-content">
		<div class="biconnector-create-superset-error-svg"></div>
		<?php if ($arResult['CAN_CREATE']): ?>
			<div class="biconnector-create-superset-title"><?= Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_CONTROLLER_CREATE_SUPERSET_TITLE') ?></div>
			<div id="enable-superset-btn-section"></div>
		<?php else: ?>
			<div class="biconnector-create-superset-title"><?= Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_CONTROLLER_CREATE_SUPERSET_TITLE') ?></div>
			<div class="biconnector-create-superset-desc"><?= Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_CONTROLLER_CREATE_SUPERSET_NO_ACCESS_DESC') ?></div>
		<?php endif; ?>
	</div>
</div>

<?php if ($arResult['CAN_CREATE']): ?>
<script>
		BX.message(<?= Json::encode(Loc::loadLanguageFile(__file__)) ?>);

		BX.ready(function() {
			BX.BIConnector.SupersetEnabler.Instance = new BX.BIConnector.SupersetEnabler({
				enableButtonSectionId: 'enable-superset-btn-section',
				canEnable: <?= $arResult['IS_ENABLE_TIME_REACHED'] ? 'true' : 'false' ?>,
				enableDate: '<?= CUtil::JSEscape($arResult['ENABLE_DATE']) ?>',
			});
		});
</script>
<?php endif; ?>
