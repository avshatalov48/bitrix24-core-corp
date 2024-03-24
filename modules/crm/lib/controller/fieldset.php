<?php
namespace Bitrix\Crm\Controller;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Main\Localization\Loc;

class FieldSet extends Main\Engine\JsonController
{
	public function listAction(): array
	{
		return [];
	}

	public function loadAction(int $entityTypeId, int $entityId, ?int $presetId = null): array
	{
		$hasReadAccessToEntity = Crm\Service\Container::getInstance()
			->getUserPermissions()
			->checkReadPermissions($entityTypeId, $entityId)
		;
		if (!$hasReadAccessToEntity)
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return [];
		}

		$item = Crm\Integration\Sign\Form::getFieldSet($entityTypeId, $presetId);
		if (!$item)
		{
			$this->addError(
				new Main\Error(Loc::getMessage('CRM_CONTROLLER_FIELDSET_NOT_FOUND', ['#ENTITY_TYPE#' => $entityTypeId]))
			);
			return [];
		}

		return Crm\Service\Sign\Requisite::getBannerData($item, $entityId);
	}

	public function getAction(int $id): array
	{
		$permissions = Crm\Service\Container::getInstance()->getUserPermissions();
		if (!$permissions->canReadConfig())
		{
			$this->addError(new Main\Error(Loc::getMessage('CRM_CONTROLLER_FIELDSET_READ_CONFIG_DENIED')));
			return [];
		}


		$item = (new Crm\FieldSet\Factory())->getItem($id);
		if (!$item)
		{
			$this->addError(new Main\Error('Unknown field set with id=' . $id));
			return [];
		}

		return [
			'options' => $item->getOptions(),
		];
	}

	public function setAction(array $options): array
	{
		$permissions = Crm\Service\Container::getInstance()->getUserPermissions();
		if (!$permissions->canWriteConfig())
		{
			$this->addError(new Main\Error(Loc::getMessage('CRM_CONTROLLER_FIELDSET_WRITE_CONFIG_DENIED')));
			return [];
		}

		$factory = new Crm\FieldSet\Factory();
		$item = (new Crm\FieldSet\Item())
			->setOptions($options)
		;
		$result = $factory->save($item);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return [];
		}

		return [
			'options' => $item->getOptions(),
		];
	}
}