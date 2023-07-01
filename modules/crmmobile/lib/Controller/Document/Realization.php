<?php

namespace Bitrix\CrmMobile\Controller\Document;

class Realization extends Base
{
	public function setShippedAction(int $documentId, string $value)
	{
		return $this->forward(
			\Bitrix\Crm\Controller\RealizationDocument::class,
			'setShipped',
			[
				'id' => $documentId,
				'value' => $value,
			]
		);
	}

	public function deleteAction(int $documentId, string $value)
	{
		return $this->forward(
			\Bitrix\Crm\Controller\RealizationDocument::class,
			'setRealization',
			[
				'id' => $documentId,
				'value' => $value,
			]
		);
	}
}