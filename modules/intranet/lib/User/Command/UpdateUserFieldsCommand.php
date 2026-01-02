<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Command;

use Bitrix\Intranet\Exception\UpdateFailedException;
use Bitrix\Intranet\Internal\Entity\User\Field\Field;
use Bitrix\Intranet\Internal\Entity\User\Field\FieldCollection;
use Bitrix\Intranet\Internal\Repository\User\Profile\ProfileRepository;
use Bitrix\Main\Command\AbstractCommand;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class UpdateUserFieldsCommand extends AbstractCommand
{
	public function __construct(
		public int $userId,
		public FieldCollection $userFieldCollection,
	)
	{}

	protected function beforeRun(): ?Result
	{
		$userFieldCollectionToSave = new FieldCollection();
		$userFieldCollectionInvalid = new FieldCollection();
		$userFieldCollectionUnchangeable = new FieldCollection();

		/** @var Field $userField */
		foreach ($this->userFieldCollection as $userField)
		{
			if (!$userField->isEditable())
			{
				$userFieldCollectionUnchangeable->add($userField);
			}
			elseif (!$userField->isValid())
			{
				$userFieldCollectionInvalid->add($userField);
			}
			else
			{
				$userFieldCollectionToSave->add($userField);
			}
		}

		$result = new Result();

		if (!$userFieldCollectionInvalid->isEmpty())
		{
			$userFieldCollectionInvalid->map(
				fn (Field $userField) => $result->addError(
					new Error('Invalid type of field ' . $userField->getId()),
				),
			);
		}

		if (!$userFieldCollectionUnchangeable->isEmpty())
		{
			$userFieldCollectionUnchangeable->map(
				fn (Field $userField) => $result->addError(
					new Error('Field ' . $userField->getId() . ' is not editable'),
				),
			);
		}

		if (!$result->isSuccess())
		{
			return $result;
		}

		if ($userFieldCollectionToSave->isEmpty())
		{
			return $result->addError(
				new Error('No available fields to update'),
			);
		}

		$this->userFieldCollection = $userFieldCollectionToSave;

		return null;
	}

	protected function execute(): Result
	{
		$result = new Result();

		try
		{
			$profileRepository = ProfileRepository::createByDefault();
			$handler = new UpdateUserFieldsHandler($profileRepository);
			$handler($this);
		}
		catch (UpdateFailedException $e)
		{
			$result->addErrors($e->getErrors()->toArray());
		}

		return $result;
	}

	public function toArray(): array
	{
		return [];
	}
}
