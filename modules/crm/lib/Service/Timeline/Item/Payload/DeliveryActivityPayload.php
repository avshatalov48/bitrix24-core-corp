<?php

namespace Bitrix\Crm\Service\Timeline\Item\Payload;

use Bitrix\Crm\Service\Timeline\Item\Payload;

class DeliveryActivityPayload extends Payload
{
	public function addValueDeliveryRequest(string $name, int $deliveryRequestId, bool $isProcessed): self
	{
		$this->addValue(
			$name,
			[
				'id' => $deliveryRequestId,
				'isProcessed' => $isProcessed,
			]
		);

		return $this;
	}
}
