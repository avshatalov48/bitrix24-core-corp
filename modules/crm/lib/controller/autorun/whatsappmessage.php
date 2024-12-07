<?php

namespace Bitrix\Crm\Controller\Autorun;

use Bitrix\Crm\Controller\Autorun\Dto\PreparedData;
use Bitrix\Crm\Controller\Autorun\Dto\WhatsappMessageData;
use Bitrix\Crm\Item;
use Bitrix\Crm\MessageSender\MassWhatsApp\SendItem;
use Bitrix\Crm\MessageSender\MassWhatsApp\TemplateParams;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Result;

final class WhatsAppMessage extends Base
{
	protected function prepareData(
		string $hash,
		string $gridId,
		int $entityTypeId,
		array $filter,
		array $params,
		Factory $factory
	): Dto\PreparedData
	{
		return new WhatsappMessageData([
			'hash' => $hash,
			'gridId' => $gridId,
			'entityTypeId' => $entityTypeId,
			'filter' => $filter,
			'messageBody' => $params['extras']['messageBody'] ?? null,
			'messageTemplate' => $params['extras']['messageTemplate'] ?? null,
			'fromPhone' => $params['extras']['fromPhone'] ?? null,
		]);
	}

	protected function getPreparedDataDtoClass(): string
	{
		return WhatsappMessageData::class;
	}

	/**
	 * @param Factory $factory
	 * @param Item $item
	 * @param WhatsappMessageData $data
	 * @return Result
	 */
	protected function processItem(Factory $factory, Item $item, PreparedData $data): Result
	{
		$sendItemService = SendItem::getInstance();

		return $sendItemService->execute(
			$item,
			new TemplateParams($data->messageBody, $data->messageTemplate, $data->fromPhone)
		);
	}
}
