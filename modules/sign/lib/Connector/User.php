<?php

namespace Bitrix\Sign\Connector;

use Bitrix\Main\EO_User;
use Bitrix\Main\UserTable;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Item\Connector\Field;
use Bitrix\Sign\Item\Connector\FieldCollection;
use Bitrix\Sign\Type\Member\ChannelType;
use Bitrix\Sign\Type\Field\ProfileField;

final class User implements Contract\Connector
{
	public function __construct(
		private int $userId
	) {}


	public function fetchFields(): FieldCollection
	{
		$fieldCollection = new FieldCollection();

		$userModel = UserTable::getById($this->userId)
			->fetchObject()
		;

		if ($userModel === null)
		{
			return $fieldCollection;
		}

		return $fieldCollection
			->add(new Field('NAME', $userModel->getName()))
			->add(new Field('LAST_NAME', $userModel->getLastName()))
			->add(new Field('FULL_NAME', $userModel->getName() . ' ' . $userModel->getLastName()))
			->add($this->getFilledCommunicationField($userModel))
		;
	}

	private function getFilledCommunicationField(EO_User $userModel): Field
	{
		$communicationField = new Field('COMMUNICATION_CHANNELS', [
			ChannelType::PHONE => [],
			ChannelType::EMAIL => [],
		]);

		if (!empty($userModel->getWorkPhone()))
		{
			$communicationField->data[ChannelType::PHONE][] = $userModel->getWorkPhone();
		}

		if (!empty($userModel->getPersonalPhone()))
		{
			$communicationField->data[ChannelType::PHONE][] = $userModel->getPersonalPhone();
		}

		if (!empty($userModel->getEmail()))
		{
			$communicationField->data[ChannelType::EMAIL][] = $userModel->getEmail();
		}

		return $communicationField;
	}

	public function getFieldValueByName(string $fieldName): string
	{
		$userModel = UserTable::getById($this->userId)
			->fetchObject()
		;

		if ($userModel === null)
		{
			return '';
		}

		return match ($fieldName)
		{
			ProfileField::EMAIL => $userModel->getEmail(),
			ProfileField::NAME => $userModel->getName(),
			ProfileField::LASTNAME => $userModel->getLastName(),
			ProfileField::SURNAME => $userModel->getSecondName(),
		};
	}

	public function getName(): string
	{
		return $this->fetchFields()->getFirstByName('FULL_NAME')?->data ?? '';
	}
}