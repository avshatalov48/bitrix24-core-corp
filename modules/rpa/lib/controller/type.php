<?php

namespace Bitrix\Rpa\Controller;

use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Engine\CheckPermissions;
use Bitrix\Rpa\Model\TypeTable;
use Bitrix\Rpa\UserPermissions;

class Type extends Base
{
	public function configureActions(): array
	{
		$configureActions = parent::configureActions();
		$configureActions['add'] = [
			'+prefilters' => [
				new CheckPermissions(UserPermissions::ENTITY_TYPE, UserPermissions::ACTION_CREATE),
			],
		];
		$configureActions['delete'] = [
			'+prefilters' => [
				new CheckPermissions(UserPermissions::ENTITY_TYPE, UserPermissions::ACTION_DELETE),
			],
		];
		$configureActions['update'] = [
			'+prefilters' => [
				new CheckPermissions(UserPermissions::ENTITY_TYPE, UserPermissions::ACTION_MODIFY),
			],
		];
		$configureActions['fields'] =
		$configureActions['get'] = [
			'+prefilters' => [
				new CheckPermissions(UserPermissions::ENTITY_TYPE, UserPermissions::ACTION_VIEW),
			],
		];

		return $configureActions;
	}

	public function getAutoWiredParameters(): array
	{
		$params = parent::getAutoWiredParameters();

		$params[] = new \Bitrix\Main\Engine\AutoWire\ExactParameter(
			\Bitrix\Rpa\Model\Type::class,
			'type',
			static function($className, $id)
			{
				return TypeTable::getById($id)->fetchObject();
			}
		);

		return $params;
	}

	public function getAction(\Bitrix\Rpa\Model\Type $type): array
	{
		return [
			'type' => $this->prepareData($type),
		];
	}

	public function listAction(array $select = ['*'], array $order = null, array $filter = null, PageNavigation $pageNavigation = null): ?Page
	{
		$converter = new Converter(Converter::TO_UPPER | Converter::KEYS | Converter::TO_SNAKE);
		if(is_array($filter))
		{
			$filter = $this->removeDotsFromKeys($converter->process($filter));
		}
		if(is_array($order))
		{
			$order = $converter->process($order);
		}
		if(is_array($select))
		{
			$converter = new Converter(Converter::TO_UPPER | Converter::VALUES | Converter::TO_SNAKE);
			$select = $this->removeDotsFromValues($converter->process($select));
		}

		if(is_array($filter))
		{
			$filter = [
				$filter,
				Driver::getInstance()->getUserPermissions()->getFilterForViewableTypes(),
			];
		}
		else
		{
			$filter = Driver::getInstance()->getUserPermissions()->getFilterForViewableTypes();
		}

		$types = [];
		$list = TypeTable::getList([
			'select' => $select,
			'filter' => $filter,
			'order' => $order ?? [],
			'offset' => $pageNavigation ? $pageNavigation->getOffset() : null,
			'limit' => $pageNavigation ? $pageNavigation->getLimit(): null,
		]);
		while($type = $list->fetchObject())
		{
			$types[] = $this->prepareData($type);
		}

		return new Page('types', $types, static function() use ($filter)
		{
			return TypeTable::getCount($filter);
		});
	}

	public function addAction(array $fields, string $eventId = ''): ?array
	{
		if(Driver::getInstance()->getBitrix24Manager()->isCreateTypeRestricted())
		{
			$this->addError(new Error(Loc::getMessage('RPA_LIMIT_CREATE_TYPE_ERROR')));

			return null;
		}
		$fields['name'] = TypeTable::generateName();
		$type = new \Bitrix\Rpa\Model\Type();
		return $this->updateAction($type, $fields, $eventId);
	}

	public function updateAction(\Bitrix\Rpa\Model\Type $type, array $fields, string $eventId = ''): ?array
	{
		if(
			$type->getId() > 0
			&& Driver::getInstance()->getBitrix24Manager()->isTypeSettingsRestricted($type->getId())
		)
		{
			$this->addError(new Error(Loc::getMessage('RPA_LIMIT_SETTINGS_TYPE_ERROR')));

			return null;
		}
		$converter = new Converter(Converter::TO_UPPER | Converter::KEYS | Converter::TO_SNAKE);
		$fields = $converter->process($fields);
		unset($fields['ID']);
		foreach($fields as $name => $value)
		{
			if($type->entity->hasField($name))
			{
				$type->set($name, $value);
			}
		}
		$permissionResult = $this->processPermissions($type, $fields);
		if(!$permissionResult->isSaved() && empty($this->getModifyPermissions($permissionResult->getResultPermissions())))
		{
			$this->addError(new Error(Loc::getMessage('RPA_CONTROLLER_TYPE_MODIFY_PERMISSIONS_EMPTY')));
			return null;
		}
		$isNew = !($type->getId() > 0);
		$result = $type->save();
		if($result->isSuccess())
		{
			$permissionResult = $this->savePermissions($type, $permissionResult);
			if(!$permissionResult->isSuccess())
			{
				$this->addErrors($permissionResult->getErrors());
			}
			if(!$isNew)
			{
				Driver::getInstance()->getPullManager()->sendTypeUpdatedEvent($type, $eventId);
			}
			return $this->getAction($type);
		}

		$this->addErrors($result->getErrors());
		return null;
	}

	public function deleteAction(\Bitrix\Rpa\Model\Type $type): void
	{
		$result = $type->delete();
		if(!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}
	}

	public function prepareData(\Bitrix\Rpa\Model\Type $type): array
	{
		$data = $this->convertKeysToCamelCase($type->collectValues());
		unset($data['tableName'], $data['name']);

		$permissions = $type->getPermissions(false);
		$data['permissions'] = $this->convertKeysToCamelCase($permissions);

		return $data;
	}

	protected function getModifyPermissions(array $permissions): array
	{
		$result = [];
		foreach($permissions as $permission)
		{
			if($permission['ACTION'] === UserPermissions::ACTION_MODIFY)
			{
				$result[] = $permission;
			}
		}

		return $result;
	}
}