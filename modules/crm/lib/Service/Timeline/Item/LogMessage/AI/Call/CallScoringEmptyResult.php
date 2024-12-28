<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\AI\Call;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Main\Localization\Loc;

final class CallScoringEmptyResult extends Base
{
	public function getType(): string
	{
		return 'CallScoringEmptyResult';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_CALL_SCORING_EMPTY_RESULT_TITLE');
	}

	public function getContentBlocks(): ?array
	{
		$result = [
			'description' => (new Text())
				->setValue(Loc::getMessage('CRM_TIMELINE_LOG_CALL_SCORING_EMPTY_RESULT_DESCRIPTION'))
				->setFontSize(13)
				->setColor(Text::COLOR_BASE_70)
			,
		];

		$settings = $this->getModel()->getSettings();
		if (!empty($settings['RECOMMENDATIONS']))
		{
			$result['recommendations'] = (new Text())
				->setValue($settings['RECOMMENDATIONS'])
				->setFontSize(13)
				->setColor(Text::COLOR_BASE_70)
			;
		}

		return $result;
	}
}
