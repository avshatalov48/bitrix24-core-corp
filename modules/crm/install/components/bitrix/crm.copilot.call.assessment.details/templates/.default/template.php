<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

Loc::loadMessages(__FILE__);
Loc::loadLanguageFile(__FILE__);

Extension::load([
	'crm.copilot.call-assessment',
]);

/** @see \Bitrix\Crm\Component\Base::addToolbar() */
$this->getComponent()->addToolbar($this);

/** @var $arResult array */
$data = $arResult['data'] ?? [];
$config = [
	'titleId' => 'pagetitle',
	'titleEditButtonId' => 'callAssessmentDetailsTitleEdit',
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
