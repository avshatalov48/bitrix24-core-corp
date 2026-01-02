<?php

declare(strict_types=1);

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Booking;

use Bitrix\Booking\Entity\Client\ClientType;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Client;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class BookingCreationError extends LogMessage
{
	use ClientTrait;

	public function getType(): string
	{
		return 'BookingCreationError';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_BOOKING_CREATION_ERROR_TITLE');
	}

	public function getTags(): ?array
	{
		return [
			'error' => new Tag(
				Loc::getMessage('CRM_TIMELINE_LOG_BOOKING_CREATION_ERROR_TAG') ?? '',
				Tag::TYPE_FAILURE
			)
		];
	}

	public function getContentBlocks(): ?array
	{
		$result = [
			'bookingCreatedContent' => (new ContentBlock\Text())
				->setValue(Loc::getMessage('CRM_TIMELINE_LOG_BOOKING_CREATION_ERROR_TEXT')),
		];

		$clientBlock = $this->buildClientBlock(
			Client::BLOCK_WITH_FORMATTED_VALUE,
			Loc::getMessage('CRM_TIMELINE_LOG_BOOKING_CREATION_ERROR_CLIENT')
		);
		if ($clientBlock)
		{
			$result['clientBlock'] = $clientBlock;
		}

		return $result;
	}

	protected function getPrimaryClient(): ?\Bitrix\Booking\Entity\Client\Client
	{
		if (!Loader::includeModule('booking'))
		{
			return null;
		}

		$settings = $this->getModel()->getSettings();

		return (new \Bitrix\Booking\Entity\Client\Client())
			->setId(isset($settings['entityId']) ? (int)$settings['entityId'] : null)
			->setType(
				(new ClientType())
					->setCode(isset($settings['entityTypeId']) ? (string)$settings['entityTypeId'] : null)
					->setModuleId('crm')
			)
		;
	}

	protected function getPhoneNumber(): string
	{
		$settings = $this->getModel()->getSettings();

		return isset($settings['phoneNumber']) ? (string)$settings['phoneNumber'] : '';
	}
}
