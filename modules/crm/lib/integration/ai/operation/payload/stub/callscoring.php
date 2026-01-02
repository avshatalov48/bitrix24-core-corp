<?php

namespace Bitrix\Crm\Integration\AI\Operation\Payload\Stub;

use Bitrix\Crm\Integration\AI\Operation\Payload\StubInterface;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Web\Json;

final class CallScoring implements StubInterface
{
	public function makeStub(): mixed
	{
		$criteriaList = array_map(
			static fn(int $index) => [
				'criterion' => "name of criterion $index",
				'status' => (bool)Random::getInt(0, 1),
				'explanation' => "explanation of criterion $index",
			], [1, 2, 3, 4, 5, 6, 7]
		);
		
		$fields = [
			'call_review' => [
				'criteria' => $criteriaList,
			],
			'overall_summary' => 'The manager successfully met almost all criteria, providing quality service and attention to detail.',
			'recommendations' => "It is recommended to offer customers a 'gift wrap' promotion for a review on WhatsApp or Instagram to increase customer engagement and loyalty."
		];
		
		return Json::encode($fields);
	}
}
