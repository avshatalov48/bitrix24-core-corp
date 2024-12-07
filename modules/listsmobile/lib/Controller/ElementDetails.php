<?php

namespace Bitrix\ListsMobile\Controller;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\EO_Element;
use Bitrix\Lists\Api\Request\ServiceFactory\GetIBlockFieldsRequest;
use Bitrix\Lists\Api\Request\ServiceFactory\GetIBlockInfoRequest;
use Bitrix\Lists\Api\Service\ServiceFactory\AccessService;
use Bitrix\Lists\Api\Service\ServiceFactory\ServiceFactory;
use Bitrix\Lists\Api\Service\WorkflowService;
use Bitrix\Lists\Service\Param;
use Bitrix\ListsMobile\Command\LoadEntityCommand;
use Bitrix\ListsMobile\Command\SaveEntityCommand;
use Bitrix\ListsMobile\EntityEditor\Converter;
use Bitrix\ListsMobile\EntityEditor\Provider;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\UI\EntityEditor\FormWrapper;

class ElementDetails extends \Bitrix\Main\Engine\Controller
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

	public function loadAction(int $elementId): ?array
	{
		$currentUserId = $this->getCurrentUserId();
		if ($elementId <= 0 || $currentUserId <= 0 || !Loader::includeModule('lists'))
		{
			$this->addError(new Error(Loc::getMessage('M_LISTS_LIB_CONTROLLER_ELEMENT_DETAILS_ELEMENT_NOT_FOUND')));

			return null;
		}

		$elementObject = $this->getElementObject($elementId);
		if (!$elementObject)
		{
			$this->addError(new Error(Loc::getMessage('M_LISTS_LIB_CONTROLLER_ELEMENT_DETAILS_ELEMENT_NOT_FOUND')));

			return null;
		}

		$iBlock = $elementObject->getIblock();
		if (!$iBlock)
		{
			$this->addError(new Error(Loc::getMessage('M_LISTS_LIB_CONTROLLER_ELEMENT_DETAILS_IBLOCK_NOT_FOUND')));

			return null;
		}

		$service = $this->getServiceByElementObject($elementObject);
		if (!$service)
		{
			$this->addError(new Error(Loc::getMessage('M_LISTS_LIB_CONTROLLER_ELEMENT_DETAILS_INCORRECT_IBLOCK_TYPE_ID')));

			return null;
		}

		$canRead = $this->canUserReadElement($elementObject);
		$canEdit = $this->canUserEditElement($elementObject);

		$description = $iBlock->getDescription() ?? '';
		$parser = new \CTextParser();
		$bbDescription = $parser->convertHTMLToBB(
			$description,
			[
				'ANCHOR' => 'Y',
				'BIU' => 'Y',
				'FONT' => 'Y',
				'LIST' => 'Y',
				'NL2BR' => 'Y',

				'HTML' => 'N',
				'IMG' => 'N',
				'QUOTE' => 'N',
				'CODE' => 'N',
				'SMILES' => 'N',
				'VIDEO' => 'N',
				'TABLE' => 'N',
				'ALIGN' => 'N',
				'P' => 'N',
			]
		);

		$result = [
			'perms' => [
				'canRead' => $canRead,
				'canEdit' => $canEdit,
			],
			'iBlockName' => $iBlock->getName(),
			'elementName' => $elementObject->getName(),
			'iBlockDescription' => htmlspecialcharsback($bbDescription),
			'editor' => $canRead ? $this->getEditorConfig($service, $elementObject, $iBlock) : null,
			'hasBPParametersOnStartUp' => false,
			'signedBpDocument' => null,
		];

		if ($canEdit)
		{
			$iBlock =
				($service->getIBlockInfo(new GetIBlockInfoRequest($elementObject->getIblockId(), false)))
					->getIBlock()
			;
			$workflowService = new WorkflowService($iBlock);

			$result['hasBPParametersOnStartUp'] = $workflowService->hasParameters($elementId);
			$result['signedBpDocument'] = $workflowService->getSignedDocument($elementId);
		}

		return $result;
	}

	public function saveAction(int $elementId, array $fields): ?array
	{
		$currentUserId = (int)($this->getCurrentUser()?->getId());
		if ($elementId <= 0 || $currentUserId <= 0 || !Loader::includeModule('lists'))
		{
			$this->addError(new Error(Loc::getMessage('M_LISTS_LIB_CONTROLLER_ELEMENT_DETAILS_ELEMENT_NOT_FOUND')));

			return null;
		}

		$elementObject = $this->getElementObject($elementId);
		if (!$elementObject)
		{
			$this->addError(new Error(Loc::getMessage('M_LISTS_LIB_CONTROLLER_ELEMENT_DETAILS_ELEMENT_NOT_FOUND')));

			return null;
		}

		$iBlock = $elementObject->getIblock();
		if (!$iBlock)
		{
			$this->addError(new Error(Loc::getMessage('M_LISTS_LIB_CONTROLLER_ELEMENT_DETAILS_IBLOCK_NOT_FOUND')));

			return null;
		}

		$service = $this->getServiceByElementObject($elementObject);
		if (!$service)
		{
			$this->addError(new Error(Loc::getMessage('M_LISTS_LIB_CONTROLLER_ELEMENT_DETAILS_INCORRECT_IBLOCK_TYPE_ID')));

			return null;
		}

		$iBlockId = $iBlock->getId();
		$iBlockSectionId = $fields['IBLOCK_SECTION_ID'] ?? $elementObject->getIblockSectionId();

		$fields['ID'] = $elementId;
		$fields['IBLOCK_ID'] = $iBlockId;
		$fields['IBLOCK_SECTION_ID'] = $iBlockSectionId;
		$fields['MODIFIED_BY'] = $currentUserId;

		$saveCommand = new SaveEntityCommand($service, $fields);
		$result = $saveCommand->execute();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return [
			'elementName' => $elementObject->unsetName()->fillName(),
			'editor' => $this->getEditorConfig($service, $elementObject, $iBlock),
		];
	}

	private function getElementObject(int $elementId): ?EO_Element
	{
		$select = [
			'ID',
			'NAME',
			'IBLOCK_SECTION_ID',
			'IBLOCK_ID',

			'IBLOCK.NAME',
			'IBLOCK.DESCRIPTION',
			'IBLOCK.IBLOCK_TYPE_ID',
			'IBLOCK.SOCNET_GROUP_ID',
		];

		return ElementTable::getByPrimary($elementId, ['select' => $select])->fetchObject();
	}

	private function getServiceByElementObject(EO_Element $elementObject): ?ServiceFactory
	{
		$iBlock = $elementObject->getIblock();

		return ServiceFactory::getServiceByIBlockTypeId(
			$iBlock->getIblockTypeId(),
			$this->getCurrentUserId(),
			$iBlock->getSocnetGroupId(),
		);
	}

	private function getEditorConfig(ServiceFactory $service, EO_Element $element, \Bitrix\Iblock\Iblock $iBlock): ?array
	{
		$iBlockId = $iBlock->getId() ?? 0;
		$socNetGroupId = $iBlock->getSocnetGroupId() ?? 0;

		$elementId = $element->getId();
		$sectionId = $element->getIblockSectionId() ?? 0;

		$command = new LoadEntityCommand($service, $elementId, $sectionId, $iBlockId);
		$result = $command->execute();
		if (!$result->isSuccess())
		{
			return null;
		}

		$elementData = $result->getData()['entity'];

		$iBlockFieldsRequest = new GetIBlockFieldsRequest($iBlockId, true, true, false);
		$iBlockFieldsResult = $service->getIBlockFields($iBlockFieldsRequest);

		$fields = ['FIELDS' => $iBlockFieldsResult->getFields(), 'PROPS' => $iBlockFieldsResult->getProps()];
		$converter = (new Converter($elementData, array_merge($fields['FIELDS'], $fields['PROPS'])))->toMobile();

		$convertedValues = $converter->getConvertedValues();
		$convertedValues['IBLOCK_TYPE_ID'] = $service->getInnerIBlockTypeId();
		$convertedValues['SOCNET_GROUP_ID'] = $socNetGroupId;

		$provider = new Provider($convertedValues, $converter->getConvertedFields(), []);
		$provider->useSectionBorder();

		return $provider->getEntityFields() ? (new FormWrapper($provider))->getResult() : null;
	}

	private function getCurrentUserId(): int
	{
		return (int)($this->getCurrentUser()?->getId());
	}

	private function canUserReadElement(EO_Element $elementObject): bool
	{
		$accessService = $this->getAccessService($elementObject);

		$response = $accessService->canUserReadElement(
			$elementObject->getId(),
			$elementObject->getIblockSectionId(),
			$elementObject->getIblockId()
		);

		return $response->isSuccess();
	}

	private function canUserEditElement(EO_Element $elementObject): bool
	{
		$accessService = $this->getAccessService($elementObject);

		$response = $accessService->canUserEditElement(
			$elementObject->getId(),
			$elementObject->getIblockSectionId(),
			$elementObject->getIblockId()
		);

		return $response->isSuccess();
	}

	private function getAccessService(EO_Element $elementObject): AccessService
	{
		$iBlock = $elementObject->getIblock();

		return new AccessService(
			$this->getCurrentUserId(),
			new Param([
				'IBLOCK_TYPE_ID' => $iBlock->getIblockTypeId(),
				'IBLOCK_ID' => $elementObject->getIblockId(),
				'SOCNET_GROUP_ID' => $iBlock->getSocnetGroupId(),
			])
		);
	}
}
