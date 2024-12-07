<?php

namespace Bitrix\Sign\Connector;

use Bitrix\Sign\Connector\Crm\Company;
use Bitrix\Sign\Connector\Crm\Contact;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Type;

final class MemberDataPicker
{
	private const AVAILABLE_CONNECTORS = [User::class, Contact::class, Company::class];

	private Item\Connector\FieldCollection $fetchedFields;
	private function __construct(
		private Contract\Connector $connector
	)
	{
		$this->fetchedFields = $this->connector->fetchFields();
	}

	public static function createByMember(Item\Member $member): self
	{
		$connector = (new MemberConnectorFactory())->create($member);
		foreach (self::AVAILABLE_CONNECTORS as $availableConnector)
		{
			if ($connector instanceof $availableConnector)
			{
				return new self($connector);
			}
		}

		return new self(new NullConnector());
	}

	/**
	 * @return array [Type\Member\ChannelType::PHONE => [string], Type\Member\ChannelType::EMAIL => [string]]
	 */
	public function getCommunications(): array
	{
		$communicationTypes = [Type\Member\ChannelType::PHONE, Type\Member\ChannelType::EMAIL];

		$communications = [];
		if ($this->connector instanceof User)
		{
			$field = $this->fetchedFields->getFirstByName('COMMUNICATION_CHANNELS');
			if (!$field)
			{
				return [];
			}

			foreach ($field->data as $type => $multipleField)
			{
				if (!in_array($type, $communicationTypes))
				{
					continue;
				}

				foreach ($multipleField as $communication)
				{
					if (!isset($communications[$type]))
					{
						$communications[$type] = [];
					}
					$communications[$type][] = $communication;
				}
			}

			return $communications;
		}

		$field = $this->fetchedFields->getFirstByName('FM');
		if (!$field)
		{
			return [];
		}

		foreach ($field->data as $type => $multipleField)
		{
			if (!in_array($type, $communicationTypes))
			{
				continue;
			}

			foreach ($multipleField as $communication)
			{
				if (!isset($communications[$type]))
				{
					$communications[$type] = [];
				}
				$communications[$type][] = $communication['VALUE'] ?? '';
			}
		}

		return $communications;
	}

	/**
	 * @return string|null
	 */
	public function getName(): ?string
	{
		$name = $this->connector->getName();

		return $name !== '' ? $name : null;
	}
}