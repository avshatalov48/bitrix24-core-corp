<?php
namespace Bitrix\Sign\Controllers\V1\Integration\Crm;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Main\Localization\Loc;;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute\Access\LogicOr;
use Bitrix\Sign\Attribute\ActionAccess;

class FieldSet extends Main\Engine\JsonController
{
	public function listAction(): array
	{
		return [];
	}

	#[LogicOr(
		new ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_ADD),
		new ActionAccess(ActionDictionary::ACTION_B2E_TEMPLATE_ADD),
		new ActionAccess(ActionDictionary::ACTION_DOCUMENT_ADD),
	)]
	public function loadAction(int $entityTypeId, int $entityId, ?int $presetId = null, ?string $documentUid = null): array
	{
		if (!Main\Loader::includeModule('crm'))
		{
			return [];
		}

		$item = Crm\Integration\Sign\Form::getFieldSet($entityTypeId, $presetId);
		if ($item === null)
		{
			$this->addError(
				new Main\Error(Loc::getMessage('CRM_CONTROLLER_FIELDSET_NOT_FOUND', ['#ENTITY_TYPE#' => $entityTypeId]))
			);

			return [];
		}

		return Crm\Service\Sign\Requisite::getBannerData($item, $entityId, $documentUid);
	}

	#[LogicOr(
		new ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_ADD),
		new ActionAccess(ActionDictionary::ACTION_B2E_TEMPLATE_ADD),
	)]
	public function getAction(int $id): array
	{
		if (!Main\Loader::includeModule('crm'))
		{
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

	#[LogicOr(
		new ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_ADD),
		new ActionAccess(ActionDictionary::ACTION_B2E_TEMPLATE_ADD),
	)]
	public function setAction(array $options): array
	{
		if (!Main\Loader::includeModule('crm'))
		{
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