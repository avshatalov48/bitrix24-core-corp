<?php
namespace Bitrix\ImConnector\InteractiveMessage\Connectors\Facebook;

use Bitrix\ImConnector\InteractiveMessage;
use Bitrix\ImConnector\Status;

class Output extends InteractiveMessage\Output
{
	private const FACEBOOK_CONNECTOR = 'facebook';

	private $productIds = [];

	/**
	 * Checks if catalog native messages are available.
	 * @param int $lineId Open line ID.
	 *
	 * @return bool
	 */
	public function isAvailable(int $lineId): bool
	{
		$status = Status::getInstance(self::FACEBOOK_CONNECTOR, $lineId);
		$statusData = $status->getData();

		return isset($statusData['CATALOG']) && $statusData['CATALOG'] === true;
	}

	/**
	 * Sets an array of catalog products external (facebook) ID's for a message.
	 *
	 * @param array $ids External (facebook) ids of catalog products.
	 */
	public function setProductIds(array $ids = []): void
	{
		$this->productIds = $ids;
	}

	/**
	 * Process native message (adds catalog products ids to the message params).
	 *
	 * @param mixed $message Message params.
	 *
	 * @return array
	 */
	public function nativeMessageProcessing($message): array
	{
		if (count($this->productIds) !== 0)
		{
			$message['catalog_products'] = $this->productIds;
		}

		return $message;
	}
}