<?php

namespace Bitrix\Crm\Copilot\CallAssessment\EntitySelector;

use Bitrix\Crm\Badge\ValueItemOptions;
use Bitrix\Crm\Copilot\CallAssessment\CallAssessmentItem;
use Bitrix\Crm\Copilot\CallAssessment\Enum\ClientType;
use Bitrix\Crm\Integration\AI\Model\QueueTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\EntitySelector\Item;

final class ItemAdapter extends Item
{
	public function __construct(CallAssessmentItem $item)
	{
		$badges = [];
		if ($item->getStatus() !== QueueTable::EXECUTION_STATUS_SUCCESS)
		{
			$badges[] = [
				'title' => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_ENTITY_SELECTOR_STATUS_NEED_EDIT_SCRIPT'),
				'bgColor' => ValueItemOptions::BG_COLOR_SECONDARY,
				'textColor' => ValueItemOptions::TEXT_COLOR_SECONDARY,
			];
		}

		$options = [
			'id' => $item->getId(),
			'title' => $item->getTitle(),
			'supertitle' => ClientType::implodeTitles($item->getClientTypeIds()),
			'entityId' => CallScriptProvider::ENTITY_ID,
			'customData' => $item->toArray(),
			'badges' => $badges,
		];

		parent::__construct($options);
	}
}
