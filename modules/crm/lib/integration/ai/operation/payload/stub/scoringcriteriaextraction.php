<?php

namespace Bitrix\Crm\Integration\AI\Operation\Payload\Stub;

use Bitrix\Crm\Integration\AI\Operation\Payload\StubInterface;
use Bitrix\Main\Web\Json;

final class ScoringCriteriaExtraction implements StubInterface
{
	public function makeStub(): mixed
	{
		$fields = [
			'status' => true,
			'criteria' => [
				'Mention the name of the factory',
				'Call the client by name',
				'Specify the order',
				'Ask what the order is for',
				'Ask if the cake needs customization',
			],
		];

		return Json::encode($fields);
	}
}
