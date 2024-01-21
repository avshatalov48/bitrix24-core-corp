<?php

namespace Bitrix\ListsMobile\Controller;

use Bitrix\Lists\Api\Request\ServiceFactory\GetIBlockFieldsRequest;
use Bitrix\Lists\Api\Request\ServiceFactory\GetIBlockInfoRequest;
use Bitrix\Lists\Api\Service\ServiceFactory\ListService;
use Bitrix\Lists\Api\Service\ServiceFactory\ProcessService;
use Bitrix\Lists\Api\Service\ServiceFactory\SocNetListService;
use Bitrix\ListsMobile\Command\LoadEntityCommand;
use Bitrix\ListsMobile\Command\SaveEntityCommand;
use Bitrix\ListsMobile\EntityEditor\Converter;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Lists\Api\Service\ServiceFactory\ServiceFactory;
use Bitrix\ListsMobile\EntityEditor\Provider;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\UI\EntityEditor\FormWrapper;

Loader::requireModule('lists');
Loader::requireModule('mobile');

class EntityDetails extends \Bitrix\Main\Engine\Controller
{
	protected function getDefaultPreFilters(): array
	{
		return [
			new ActionFilter\Authentication(),
			new ActionFilter\Csrf(),
			new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
			new ActionFilter\Scope(ActionFilter\Scope::NOT_REST),
			new ActionFilter\CloseSession(),
		];
	}

	public function loadIBlockAction(int $iBlockId): array
	{
		$iBlock = [];
		$service = $this->getService($iBlockId);
		if ($service)
		{
			$result = $service->getIBlockInfo(new GetIBlockInfoRequest($iBlockId, true));
			$this->addErrors($result->getErrors());
			$iBlock = $result->getIBlock();
		}

		// todo: DTO
		return [
			'iBlock' => [
				'id' => (int)($iBlock['ID'] ?? $iBlockId),
				'name' => $iBlock['NAME'] ?? '',
				'description' => $iBlock['DESCRIPTION'] ?? '',
				'elementName' => $iBlock['ELEMENT_NAME'] ?? '',
				'typeId' => $iBlock['IBLOCK_TYPE_ID'] ?? '',
			],
		];
	}

	public function loadEntityAction(int $id, int $sectionId, int $iBlockId): ?array
	{
		if ($id < 0 || $sectionId < 0 || $iBlockId <= 0)
		{
			$this->addError(new Error(Loc::getMessage('LISTSMOBILE_LIB_CONTROLLER_ENTITY_DETAILS_INCORRECT_FIELD_VALUE')));

			return null;
		}

		$service = $this->getService($iBlockId);
		if (!$service)
		{
			return null;
		}

		$command = new LoadEntityCommand($service, $id, $sectionId, $iBlockId);
		$result = $command->execute();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}
		$element = $result->getData()['entity'];

		$iBlockFieldsRequest = new GetIBlockFieldsRequest($iBlockId, true, true);
		$iBlockFieldsResult = $service->getIBlockFields($iBlockFieldsRequest);
		$fields = ['FIELDS' => $iBlockFieldsResult->getFields(), 'PROPS' => $iBlockFieldsResult->getProps()];

		return [
			'id' => $id,
			'editor' => $this->getEditor($element, $fields),
		];
	}

	public function updateEntityAction(int $id, array $fields = []): ?array
	{
		$iBlockId = (int)($fields['IBLOCK_ID'] ?? 0);
		$sectionId = isset($fields['IBLOCK_SECTION_ID']) ? (int)$fields['IBLOCK_SECTION_ID'] : 0;

		if ($id < 0 || $iBlockId <= 0|| $sectionId < 0)
		{
			$this->addError(new Error(Loc::getMessage('LISTSMOBILE_LIB_CONTROLLER_ENTITY_DETAILS_INCORRECT_FIELD_VALUE')));

			return null;
		}

		$service = $this->getService($iBlockId);
		if ($service)
		{
			$fields['MODIFIED_BY'] = $this->getCurrentUserId();
			$saveCommand = new SaveEntityCommand($service, $fields);
			$result = $saveCommand->execute();
			if (!$result->isSuccess())
			{
				$this->addErrors($result->getErrors());

				return null;
			}

			$newId = $result->getData()['id'];

			return $this->loadEntityAction($newId, $sectionId, $iBlockId);
		}

		return null;
	}

	private function getEditor(array $element, array $fields): array
	{
		$converter = (new Converter($element, array_merge($fields['FIELDS'], $fields['PROPS'])))->toMobile();
		$provider = new Provider($converter->getConvertedValues(), $converter->getConvertedFields());

		return (new FormWrapper($provider))->getResult();
	}

	private function getService(int $iBlockId): ProcessService|ListService|SocNetListService|null
	{
		$iBlockTypeId = $this->getIBlockTypeIdByIBlockId($iBlockId);
		$socNetGroupId = $this->getSocNetGroupIdByIBlockId($iBlockId);

		$service = ServiceFactory::getServiceByIBlockTypeId($iBlockTypeId, $this->getCurrentUserId(), $socNetGroupId);
		if ($service)
		{
			return $service;
		}

		$this->addError(
			new Error(Loc::getMessage('LISTSMOBILE_LIB_CONTROLLER_ENTITY_DETAILS_INCORRECT_IBLOCK_TYPE'))
		);

		return null;
	}

	private function getCurrentUserId(): int
	{
		return (int)($this->getCurrentUser()?->getId());
	}

	private function getIBlockTypeIdByIBlockId(int $iBlockId): ?string
	{
		if (Loader::includeModule('iblock'))
		{
			$iBlockTypeId = \CIBlock::GetArrayByID($iBlockId, 'IBLOCK_TYPE_ID');

			if (is_string($iBlockTypeId))
			{
				return $iBlockTypeId;
			}
		}

		return null;
	}

	private function getSocNetGroupIdByIBlockId(int $iBlockId): ?int
	{
		if (Loader::includeModule('iblock'))
		{
			$socNetGroupId = \CIBlock::GetArrayByID($iBlockId, 'SOCNET_GROUP_ID');

			return (int)$socNetGroupId;
		}

		return null;
	}
}
