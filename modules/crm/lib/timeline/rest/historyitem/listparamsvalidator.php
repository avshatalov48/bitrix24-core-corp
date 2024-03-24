<?php

namespace Bitrix\Crm\Timeline\Rest\HistoryItem;

use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Timeline\Rest\HistoryItem\ListParams\Params;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use CCrmOwnerType;

final class ListParamsValidator
{
	public static function make(UserPermissions $userPermissions): ListParamsValidator
	{
		return new self($userPermissions);
	}

	public function __construct(
		private UserPermissions $userPermissions
	)
	{
	}

	public function validate(Params $params): ErrorCollection
	{
		$errors = new ErrorCollection();

		$error = $this->checkBindingRequiredForNotAdmin($params);
		if ($error)
		{
			$errors->setError($error);
		}

		$error = $this->checkIsBindingsValid($params);
		if ($error)
		{
			$errors->setError($error);
		}

		return $errors;
	}

	private function checkBindingRequiredForNotAdmin(Params $params): ?Error
	{
		if ($this->userPermissions->isAdmin())
		{
			return null;
		}

		if ($params->getFilter()->hasBindingsFilter())
		{
			return null;
		}

		return new Error('Bindings filter is required for users without administrator rights');
	}

	private function checkIsBindingsValid(Params $params): ?Error
	{
		$bindings = $params->getFilter()->getBindingsFilter();

		if (empty($bindings))
		{
			return null;
		}

		if (!is_array($bindings))
		{
			return new Error('Bindings filter is required for users without administrator rights');
		}

		if (count($bindings) > 100)
		{
			return new Error('Bindings filter limit is 100');
		}

		foreach ($bindings as $binding)
		{
			$error = $this->checkIsBindingValid((array)$binding);
			if ($error !== null)
			{
				return $error;
			}
		}

		return null;
	}

	private function checkIsBindingValid(array $binding): ?Error
	{
		$requiredFields = [
			'ENTITY_TYPE_ID' => 'entityTypeId',
			'ENTITY_ID' => 'entityId',
		];

		foreach ($requiredFields as $requiredField => $requiredFieldCamelCase)
		{
			if (!isset($binding[$requiredField]))
			{
				return new Error('Missing required field: ' . $requiredFieldCamelCase);
			}

			if (!is_numeric($binding[$requiredField]))
			{
				return new Error('Field ' . $requiredFieldCamelCase . ' must be numeric');
			}
		}

		$entityTypeId = (int)$binding['ENTITY_TYPE_ID'];
		if (!CCrmOwnerType::isCorrectEntityTypeId($entityTypeId))
		{
			return new Error('entityTypeId is invalid');
		}

		return null;
	}
}