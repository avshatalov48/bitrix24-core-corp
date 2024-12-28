<?php

namespace Bitrix\Tasks\Flow\Internal\Entity;

use Bitrix\Tasks\Flow\Integration\AI\Provider\AdviceData;
use Bitrix\Tasks\Flow\Internal\EO_FlowCopilotAdvice;

class FlowCopilotAdvice extends EO_FlowCopilotAdvice
{
	/**
	 * @return AdviceData[]
	 */
	public function getAdvicesData(): array
	{
		$positiveHighlights = $this->getAdvice()['positive_highlights'] ?? [];
		$advices = $this->getAdvice()['advices'] ?? [];

		$advicesData = [];

		foreach ($positiveHighlights as $positiveHighlight)
		{
			if (!is_string($positiveHighlight))
			{
				continue;
			}

			$advicesData[] = new AdviceData(
				$this->getFlowId(),
				$positiveHighlight,
				'',
			);
		}

		foreach ($advices as $advice)
		{
			$factor = $advice['factor'] ?? '';
			$advice = $advice['advice'] ?? '';

			if (!is_string($factor) || !is_string($advice))
			{
				continue;
			}

			$advicesData[] = new AdviceData(
				$this->getFlowId(),
				$factor,
				$advice,
			);
		}

		return $advicesData;
	}
}
