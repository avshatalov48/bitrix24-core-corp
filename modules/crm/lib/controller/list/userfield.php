<?php

namespace Bitrix\Crm\Controller\List;

use Bitrix\Crm\Component\EntityList\UserField\GridHeaders;
use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Grid\Column\Column;
use Bitrix\Main\Grid\Column\Factory\ColumnFactory;

class UserField extends Base
{
	private ?ColumnFactory $columnFactory = null;

	public function getDataAction(int $entityTypeId, array $fieldNames, int $categoryId = 0): ?array
	{
		if (empty($fieldNames))
		{
			return [];
		}

		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			$this->addError(ErrorCode::getEntityTypeNotSupportedError());

			return null;
		}

		if (!Container::getInstance()->getUserPermissions()->canReadTypeInCategory($entityTypeId, $categoryId))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		global $USER_FIELD_MANAGER;
		$userType = new \CCrmUserType($USER_FIELD_MANAGER, $factory->getUserFieldEntityId());
		$gridHeaders = (new GridHeaders($userType))
			->setWithEnumFieldValues(true)
			->setWithHtmlSpecialchars(false)
		;

		$fields = [];
		$gridHeaders->append($fields, $fieldNames);

		$data = [];
		foreach ($fields as $fieldName => $fieldData)
		{
			$column = $this->createColumn($fieldData);
			$data[$fieldName] = \CUtil::PhpToJSObject($column?->getEditable()?->toArray());
		}

		return [
			'fields' => $data,
		];
	}

	private function createColumn(array $data): ?Column
	{
		return $this->getColumnFactory()->createFromArray($data);
	}

	private function getColumnFactory(): ColumnFactory
	{
		if (!$this->columnFactory)
		{
			$this->columnFactory = new ColumnFactory();
		}

		return $this->columnFactory;
	}
}
