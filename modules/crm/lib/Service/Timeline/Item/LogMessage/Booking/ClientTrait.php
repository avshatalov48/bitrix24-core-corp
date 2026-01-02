<?php

declare(strict_types=1);

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Booking;

use Bitrix\Crm\Service\Timeline\Item\Mixin\CalendarSharing\ContactTrait;
use Bitrix\Crm\Service\Timeline\Item\Model;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Client;
use Bitrix\Main\PhoneNumber\Parser;

trait ClientTrait
{
	use ContactTrait {
		getContactName as getContactNameTrait;
	}

	abstract public function getModel(): Model;
	abstract protected function getPrimaryClient(): ?\Bitrix\Booking\Entity\Client\Client;
	abstract protected function getPhoneNumber(): string;

	protected function buildClientBlock(int $options = 0, string $blockTitle = null): ?ContentBlock
	{
		$primaryClient = $this->getPrimaryClient();

		if (
			!$primaryClient
			|| !$primaryClient->getId()
			|| $primaryClient->getType()?->getModuleId() !== 'crm'
		)
		{
			return null;
		}

		$clientTypeCode = $primaryClient->getType()?->getCode();
		if (!in_array($clientTypeCode, [\CCrmOwnerType::ContactName, \CCrmOwnerType::CompanyName], true))
		{
			return null;
		}

		$clientEntityTypeId = \CCrmOwnerType::ResolveID($clientTypeCode);
		if ($clientEntityTypeId === \CCrmOwnerType::Undefined)
		{
			return null;
		}

		$phoneNumber = $this->getPhoneNumber();
		$parsedPhoneNumber =
			$phoneNumber
				? Parser::getInstance()?->parse($phoneNumber)
				: null
		;

		$client = (new Client(
			[
				'ENTITY_ID' => $primaryClient->getId(),
				'ENTITY_TYPE_ID' => $clientEntityTypeId,
				'TYPE' => 'PHONE',
				'VALUE' => $phoneNumber,
				'FORMATTED_VALUE' => $parsedPhoneNumber ? $parsedPhoneNumber->format() : '',
				'TITLE' => $this->getContactName($clientEntityTypeId, $primaryClient->getId()),
				'SHOW_NAME' => 1,
				'SHOW_URL' => $this->getContactUrl($clientEntityTypeId, $primaryClient->getId()),
			],
			$options
		));

		return $client
			->setTitle($blockTitle)
			->build()
		;
	}

	private function getContactName(int $contactTypeId, int $contactId): string
	{
		try
		{
			return $this->getContactNameTrait($contactTypeId, $contactId);
		}
		catch (\Throwable)
		{
			// trait code returns exception if client not found
			// client could be removed, so skip error and show default hidden message
		}

		$entityTypePrefix = \CCrmOwnerTypeAbbr::ResolveByTypeID($contactTypeId);
		$entityTypeName = \CCrmOwnerTypeAbbr::ResolveName($entityTypePrefix);

		return \CCrmEntitySelectorHelper::getHiddenTitle($entityTypeName);
	}
}
