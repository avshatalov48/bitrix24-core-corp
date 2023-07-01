<?php

namespace Bitrix\CrmMobile\Controller\Document;

class Shipment extends Base
{
	public function setShippedAction(int $documentId, string $value)
	{
		return $this->forward(
			\Bitrix\Crm\Controller\Order\Shipment::class,
			'setShipped',
			[
				'id' => $documentId,
				'value' => $value,
			]
		);
	}

	public function deleteAction(int $documentId)
	{
		return $this->forward(
			\Bitrix\Crm\Controller\Order\Shipment::class,
			'delete',
			[
				'id' => $documentId,
			]
		);
	}
}