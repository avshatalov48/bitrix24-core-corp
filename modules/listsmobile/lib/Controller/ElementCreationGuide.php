<?php

namespace Bitrix\ListsMobile\Controller;

use Bitrix\Lists\Api\Request\ServiceFactory\GetAverageIBlockTemplateDurationRequest;
use Bitrix\Lists\Api\Request\ServiceFactory\GetIBlockFieldsRequest;
use Bitrix\Lists\Api\Request\ServiceFactory\GetIBlockInfoRequest;
use Bitrix\Lists\Api\Service\AccessService;
use Bitrix\Lists\Api\Service\ServiceFactory\ProcessService;
use Bitrix\Lists\Api\Service\ServiceFactory\ServiceFactory;
use Bitrix\Lists\Api\Service\WorkflowService;
use Bitrix\Lists\Security\ElementRight;
use Bitrix\Lists\Service\Param;
use Bitrix\ListsMobile\Command\LoadEntityCommand;
use Bitrix\ListsMobile\Command\SaveEntityCommand;
use Bitrix\ListsMobile\EntityEditor\Converter;
use Bitrix\ListsMobile\EntityEditor\ElementField;
use Bitrix\ListsMobile\EntityEditor\Provider;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Security\Sign\BadSignatureException;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Mobile\UI\EntityEditor\FormWrapper;

class ElementCreationGuide extends Controller
{
	private const TOKEN_SALT = 'listsmobile_elementCreationGuide';

	protected function getDefaultPreFilters()
	{
		return [
			new ActionFilter\Authentication(),
			new ActionFilter\Csrf(),
			new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
			new ActionFilter\Scope(ActionFilter\Scope::NOT_REST),
			new ActionFilter\CloseSession(),
		];
	}

	public function loadCatalogStepAction(): ?array
	{
		if (!Loader::includeModule('lists'))
		{
			//todo: add error + loc
			return null;
		}

		$service = $this->getService();

		$result = $service->getCatalog();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		$items = [];
		$isBizprocEnabled = Loader::includeModule('bizproc');
		foreach ($result->getCatalog() as $process)
		{
			$time = null;
			if ($isBizprocEnabled)
			{
				$averageTimeResult = $service->getAverageIBlockTemplateDuration(
					new GetAverageIBlockTemplateDurationRequest(
						$process['ID'],
						\CBPDocumentEventType::Create,
						false,
					)
				);
				if ($averageTimeResult->isSuccess())
				{
					$time = $averageTimeResult->getAverageDuration();
				}
			}

			if ($this->canUserAddElement($service->getInnerIBlockTypeId(), (int)$process['ID']))
			{
				// todo: mobile DTO
				$items[] = [
					'key' => $process['ID'],
					'type' => 'process',
					'title' => $process['NAME'],
					'time' => $time,
					// 'iBlockTypeId' => $iBlockTypeId,
					'isSelected' => false,
				];
			}
		}

		return [
			'items' => $items,
		];
	}

	public function loadDescriptionStepAction(int $iBlockId, int $elementId): ?array
	{
		if (!Loader::includeModule('lists'))
		{
			//todo: add error + loc
			return null;
		}

		if ($iBlockId <= 0 || $elementId < 0)
		{
			// todo: localization
			$this->addError(new Error('incorrect iBlockId'));

			return null;
		}

		$service = $this->getService();
		if (!$this->canUserReadIBlockDescription($iBlockId, $service->getInnerIBlockTypeId()))
		{
			return null;
		}

		$result = $service->getIBlockInfo(new GetIBlockInfoRequest($iBlockId, false));
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		$iBlock = $result->getIBlock();
		$workflowService = new WorkflowService($iBlock);

		$description =  $iBlock['DESCRIPTION'] ?? '';
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

		// todo: mobile DTO
		return [
			'name' => $iBlock['NAME'] ?? '',
			'description' => htmlspecialcharsback($bbDescription),
			'hasFieldsToRender' =>
				$this->hasIBlockFieldsToRender($iBlockId, $elementId === 0)
				|| $workflowService->hasParameters($elementId)
			,
			'isConstantsTuned' => $workflowService->isConstantsTuned(),
			'signedIBlockIdAndElementId' => $this->signIBlockIdAndElementId($iBlockId, $elementId),
		];
	}

	private function hasIBlockFieldsToRender(int $iBlockId, bool $isCreateForm = true): bool
	{
		$service = $this->getService();

		$iBlockFieldsRequest = new GetIBlockFieldsRequest($iBlockId, false, false, false);
		$iBlockFieldsResult = $service->getIBlockFields($iBlockFieldsRequest);

		$hasField = false;

		$baseFieldsMap = Provider::getBaseFieldsMap();
		foreach ($iBlockFieldsResult->getFields() as $fieldId => $property)
		{
			if (array_key_exists($fieldId, $baseFieldsMap))
			{
				$modifiedProperty = array_merge($property, $baseFieldsMap[$fieldId]);
				if (isset($property['SETTINGS']) && is_array($property['SETTINGS']))
				{
					$modifiedProperty['SETTINGS'] = array_merge($modifiedProperty['SETTINGS'], $property['SETTINGS']);
				}

				if ($this->isNeedToShowField($isCreateForm, $modifiedProperty))
				{
					$hasField = true;

					break;
				}
			}
		}

		if (!$hasField)
		{
			foreach ($iBlockFieldsResult->getProps() as $property)
			{
				if ($this->isNeedToShowField($isCreateForm, $property))
				{
					$hasField = true;

					break;
				}
			}
		}

		return $hasField;
	}

	private function isNeedToShowField(bool $isCreateForm, array $property): bool
	{
		$elementField = new ElementField($property);
		if (
			($isCreateForm && $elementField->isShowInAddForm())
			|| (!$isCreateForm && $elementField->isShowInEditForm())
		)
		{
			return true;
		}

		return false;
	}

	public function loadDetailStepAction(int $iBlockId, int $elementId): ?array
	{
		if (!Loader::includeModule('lists'))
		{
			//todo: add error + loc
			return null;
		}

		if ($iBlockId <= 0 || $elementId < 0)
		{
			$this->addError(new Error('incorrect entity'));

			return null;
		}

		$service = $this->getService();
		if (!$this->canUserReadElement($service->getInnerIBlockTypeId(), $elementId, $iBlockId))
		{
			return null;
		}

		// todo: sectionId
		$command = new LoadEntityCommand($service, $elementId, 0, $iBlockId);
		$result = $command->execute();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		$element = $result->getData()['entity'];
		$iBlockFieldsRequest = new GetIBlockFieldsRequest($iBlockId, true, true, false);
		$iBlockFieldsResult = $service->getIBlockFields($iBlockFieldsRequest);
		$fields = ['FIELDS' => $iBlockFieldsResult->getFields(), 'PROPS' => $iBlockFieldsResult->getProps()];
		$converter = (new Converter($element, array_merge($fields['FIELDS'], $fields['PROPS'])))->toMobile();

		$workflowService = new WorkflowService(
			$service->getIBlockInfo(new GetIBlockInfoRequest($iBlockId, false))->getIBlock()
		);
		$states = [];
		if ($workflowService->canUserStartWorkflow((int)($this->getCurrentUser()?->getId()), 0))
		{
			$states = $workflowService->getDocumentStates($workflowService->getComplexDocumentId(0));
		}

		$convertedValues = $converter->getConvertedValues();
		$convertedValues['IBLOCK_TYPE_ID'] = $service->getInnerIBlockTypeId();

		$provider = new Provider($convertedValues, $converter->getConvertedFields(), $states);

		return [
			'id' => $elementId,
			'signedIBlockIdAndElementId' => $this->signIBlockIdAndElementId($iBlockId, $elementId),
			'editor' => (new FormWrapper($provider))->getResult(),
		];
	}

	public function createElementAction(string $sign, array $fields = [], ?int $timeToStart = null): ?array
	{
		if (!Loader::includeModule('lists'))
		{
			//todo: add error + loc
			return null;
		}

		$iBlockId = $fields['IBLOCK_ID'] ?? 0;
		if ($iBlockId <= 0)
		{
			$this->addError(new Error('incorrect iBlockId'));

			return null;
		}

		if (!$this->unSignIBlockIdAndElementId($sign, $iBlockId, 0))
		{
			return null;
		}

		$service = $this->getService();
		$fields['ID'] = 0;
		$fields['MODIFIED_BY'] = (int)($this->getCurrentUser()?->getId());
		$fields['IBLOCK_SECTION_ID'] = $fields['IBLOCK_SECTION_ID'] ?? 0;
		if (isset($timeToStart))
		{
			$fields['timeToStart'] = $timeToStart;
		}
		$saveCommand = new SaveEntityCommand($service, $fields);
		$result = $saveCommand->execute();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return [
			'success' => true,
		];
	}

	private function getService(): ProcessService
	{
		$currentUserId = (int)($this->getCurrentUser()?->getId());
		$iBlockTypeId = Option::get('lists', 'livefeed_iblock_type_id', 'bitrix_processes');

		return ServiceFactory::getServiceByIBlockTypeId($iBlockTypeId, $currentUserId);
	}

	private function signIBlockIdAndElementId(int $iBlockId, int $elementId): string
	{
		return  (new Signer())->sign($iBlockId . '|' . $elementId, self::TOKEN_SALT);
	}

	private function unSignIBlockIdAndElementId(string $sign, int $iBlockId, int $elementId = 0): bool
	{
		$signer = new Signer();
		try
		{
			$unsigned = $signer->unsign($sign, self::TOKEN_SALT);
			if ($iBlockId . '|' . $elementId !== $unsigned)
			{
				// todo: localization
				$this->addError(new Error('incorrect sign'));

				return false;
			}
		}
		catch (BadSignatureException $e)
		{
			// todo: localization
			$this->addError(new Error('incorrect sign'));

			return false;
		}

		return true;
	}

	private function canUserReadIBlockDescription(int $iBlockId, string $iBlockTypeId): bool
	{
		$accessService = $this->getAccessService($iBlockTypeId, $iBlockId);

		if (!Loader::includeModule('iblock'))
		{
			$this->addError($accessService::getAccessDeniedError());

			return false;
		}

		$checkPermissionsResponse = $accessService->checkPermissions();
		if (!$checkPermissionsResponse->isSuccess())
		{
			$this->addErrors($checkPermissionsResponse->getErrors());

			return false;
		}

		if (
			!$accessService->isCanReadPermission($checkPermissionsResponse->getPermission())
			&& !\CIBlockSectionRights::UserHasRightTo($iBlockId, 0, 'section_element_bind')
		)
		{
			$this->addError($accessService::getAccessDeniedError());

			return false;
		}

		return true;
	}

	private function canUserReadElement(string $iBlockTypeId, int $elementId, int $iBlockId): bool
	{
		$accessService = $this->getAccessService($iBlockTypeId, $iBlockId);

		$method = $elementId === 0 ? ElementRight::ADD : ElementRight::READ;
		$response = $accessService->checkElementPermission($elementId, 0, $method, $iBlockId);

		if (!$response->isSuccess())
		{
			$this->addErrors($response->getErrors());

			return false;
		}

		return true;
	}

	private function canUserAddElement(string $iBlockTypeId, int $iBlockId): bool
	{
		$accessService = $this->getAccessService($iBlockTypeId, $iBlockId);
		$response = $accessService->checkElementPermission(0, 0, ElementRight::ADD, $iBlockId);

		return $response->isSuccess();
	}

	private function getAccessService(string $iBlockTypeId, int $iBlockId): AccessService
	{
		return new AccessService(
			(int)($this->getCurrentUser()?->getId()),
			new Param([
				'IBLOCK_TYPE_ID' => $iBlockTypeId,
				'IBLOCK_ID' => $iBlockId,
				'SOCNET_GROUP_ID' => 0,
			])
		);
	}
}
