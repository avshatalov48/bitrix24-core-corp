<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\UI\Toolbar\Facade\Toolbar;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

Loc::loadMessages(__FILE__);
Loc::loadLanguageFile(__FILE__);

Extension::load([
	'crm.copilot.call-assessment',
]);

$this->SetViewTarget('inside_pagetitle');
?>
<div class="copilot-call-assessment-pagetitle-description">
	<?= Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_DETAILS_SUBTITLE') ?>
</div>
<?php
$this->EndViewTarget();

$this->SetViewTarget('in_pagetitle');
?>
	<span id="pagetitle_btn_wrapper" class="pagetitile-button-container">
		<span id="pagetitle_edit" class="pagetitle-edit-button"></span>
		<input id="pagetitle_input" type="text" class="pagetitle-item" style="display: none;">
	</span>
<?php
$this->EndViewTarget();

Toolbar::deleteFavoriteStar();

/** @var $arResult array */
$data = $arResult['data'] ?? [];
$config = [
	'titleId' => 'pagetitle',
	'titleEditButtonId' => 'pagetitle_edit',
	'copilotSettings' => $arResult['copilotSettings'] ?? [],
	'baasSettings' => $arResult['baasSettings'] ?? [],
	'readOnly' => $arResult['readOnly'] ?? true,
	'isEnabled' => $arResult['isEnabled'] ?? true,
	'isCopy' => $arResult['isCopy'] ?? false,
];
?>
<script>
	BX.ready(() => {
		new BX.Crm.Copilot.CallAssessment(
			'callAssessmentDetails',
			{
				data: <?= \Bitrix\Main\Web\Json::encode($data) ?>,
				config: <?= \Bitrix\Main\Web\Json::encode($config) ?>,
				events: {
					onSave: () => {
						if (top.BX.Main && top.BX.Main.gridManager)
						{
							const grid = top.BX.Main.gridManager.getInstanceById('crm_copilot_call_assessment_grid');
							if (grid)
							{
								grid.reload();
							}
						}
					}
				}
			},
		);
	});
</script>
<div id="callAssessmentDetails" class="crm-call-assessment-details"></div>
