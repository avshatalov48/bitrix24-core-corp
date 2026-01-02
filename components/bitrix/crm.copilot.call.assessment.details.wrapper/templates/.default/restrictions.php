<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

global $APPLICATION;

/** @var object $component */

use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;

if (!Bitrix24Manager::isFeatureEnabled(AIManager::AI_COPILOT_FEATURE_NAME)):
	?>
	<script>
		BX.ready(() => {
			<?= Bitrix24Manager::prepareLicenseInfoHelperScript(['ID' => AIManager::AI_COPILOT_FEATURE_RESTRICTED_SLIDER_CODE]) ?>;

			const slider = top?.BX?.SidePanel?.Instance.getSliderByWindow(window);
			if (slider)
			{
				slider.close();
			}
			else
			{
				BX.addCustomEvent('SidePanel.Slider:onCloseComplete', () => {
					location.href = '/crm/deal/';
				});
			}
		});
	</script>
<?php
endif;

Container::getInstance()->getLocalization()->loadMessages();

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	[
		'GRID_ID' => 'COPILOT_CALL_ASSESSMENT_RESTRICTED',
		'HEADERS' => [
			[
				'id' => 'ID',
				'name' => 'ID'
			],
		],
		'ROWS' => [],
		'STUB' => [
			'title' => Loc::getMessage('CRM_FEATURE_RESTRICTION_GRID_TITLE'),
			'description' => Loc::getMessage('CRM_FEATURE_RESTRICTION_GRID_TEXT'),
		],
	],
	$component,
	[
		'HIDE_ICONS' => 'Y',
	]
);
