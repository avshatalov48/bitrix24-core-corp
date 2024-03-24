<?php

use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Security\PermissionToken;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

class CrmRequisiteDetailsSliderComponent extends Bitrix\Crm\Component\Base
{
	protected const AVAILABLE_MODES = ['create', 'edit', 'copy', 'delete',];

	protected int $requisiteId;
	protected int $entityId;
	protected ?int $categoryId;
	protected int $presetId;

	private bool $isOpenInEntityDetails = false;

	protected function init(): void
	{
		parent::init();

		$this->requisiteId = (int)$this->getFromArParamsOrRequest('REQUISITE_ID', 'requisite_id', 0);
		$this->entityTypeId = (int)$this->getFromArParamsOrRequest('ENTITY_TYPE_ID', 'etype', CCrmOwnerType::Undefined);

		$this->entityId = $this->getEntityId($this->requisiteId);
		$this->categoryId = $this->getCategoryId($this->entityId);
		$this->presetId = $this->getPresetId($this->entityTypeId);

		$this->isOpenInEntityDetails = $this->arParams['IS_OPENED_IN_ENTITY_DETAILS'] ?? false;
	}

	/**
	 * Try to fetch parameter from $arParams or $this->request <br>
	 * if $possibleRequestKeys is empty then it becomes equal $possibleArParamKeys
	 *
	 * $arParams takes precedence over $this->request
	 *
	 * @param array|string $possibleArParamKeys
	 * @param array|string|null $possibleRequestKeys
	 * @param mixed|null $defaultValue
	 * @return mixed
	 */
	protected function getFromArParamsOrRequest(
		array|string $possibleArParamKeys,
		array|string $possibleRequestKeys = null,
		mixed $defaultValue = null,
	): mixed
	{
		if (!is_array($possibleArParamKeys))
		{
			$possibleArParamKeys = [$possibleArParamKeys];
		}

		if ($possibleRequestKeys === null)
		{
			$possibleRequestKeys = $possibleArParamKeys;
		}
		elseif (!is_array($possibleRequestKeys))
		{
			$possibleRequestKeys = [$possibleRequestKeys];
		}

		foreach ($possibleArParamKeys as $possibleArParamKey)
		{
			if (isset($this->arParams[$possibleArParamKey]))
			{
				return $this->arParams[$possibleArParamKey];
			}
		}

		foreach ($possibleRequestKeys as $possibleRequestKey)
		{
			if ($this->request->get($possibleRequestKey) !== null)
			{
				return $this->request->get($possibleRequestKey);
			}
		}

		return $defaultValue;
	}

	private function getEntityId(int $requisiteId): int
	{
		if ($requisiteId <= 0)
		{
			return (int)$this->getFromArParamsOrRequest(
				'ENTITY_ID',
				['eid', 'itemId'],
				0,
			);
		}

		$requisiteOwner = EntityRequisite::getOwnerEntityById($requisiteId);

		return $requisiteOwner['ENTITY_ID'];
	}

	private function getCategoryId(int $entityId): ?int
	{
		$requestCategory = $this->getFromArParamsOrRequest('CATEGORY_ID', 'cid');
		if ($entityId <= 0 && $requestCategory !== null)
		{
			return (int)$requestCategory;
		}

		return null;
	}

	private function getPresetId(int $entityTypeId): int
	{
		$defaultPreset = EntityRequisite::getDefaultPresetId($entityTypeId);

		return (int)$this->getFromArParamsOrRequest('PRESET_ID', ['presetId', 'pid'], $defaultPreset);
	}

	public function executeComponent(): void
	{
		$this->init();

		$this->checkModules();
		if ($this->getErrors())
		{
			$this->includeComponentTemplate();

			return;
		}

		$this->checkPermissions();
		if ($this->getErrors())
		{
			$this->includeComponentTemplate();

			return;
		}

		$this->arResult['COMPONENT_PARAMS'] = $this->getSliderComponentParams();

		$this->includeComponentTemplate();
	}

	protected function checkModules(): void
	{
		if (!Loader::includeModule('crm'))
		{
			$this->errorCollection[] = new Error(
				Loc::getMessage('CRM_MODULE_NOT_INSTALLED'),
			);
		}
	}

	protected function checkPermissions(): void
	{
		$canRead = $this->userPermissions->checkReadPermissions(
			$this->entityTypeId,
			$this->entityId,
			$this->entityId <= 0 ? $this->categoryId : null,
		);

		$permissionToken = $this->request->get('permissionToken') ?? '';
		$canEdit = PermissionToken::canEditRequisites(
			$permissionToken,
			$this->entityTypeId,
			$this->entityId,
		);

		$session = !$this->request->isPost() || check_bitrix_sessid();

		if ($session && ($canRead || $canEdit))
		{
			return;
		}

		$this->errorCollection[] = new Error(
			Loc::getMessage('CRM_COMMON_ERROR_ACCESS_DENIED'),
		);
	}

	protected function getSliderComponentParams(): array
	{
		$sliderParams = [
			'POPUP_COMPONENT_NAME' => 'bitrix:crm.requisite.details',
			'POPUP_COMPONENT_PARAMS' => $this->getRequisiteComponentParams(),
			'EDITABLE_TITLE_DEFAULT' => '',
			'EDITABLE_TITLE_SELECTOR' => "[data-cid='NAME']",
		];

		if (!$this->isOpenInEntityDetails)
		{
			$sliderParams['PAGE_MODE_OFF_BACK_URL'] = $this->getSliderOffBackUrl();
			$sliderParams['PAGE_MODE'] = false;
		}

		return $sliderParams;
	}

	protected function getSliderOffBackUrl(): string
	{
		$url = CCrmOwnerType::GetListUrl($this->entityTypeId, true);
		if ($this->entityId > 0)
		{
			$url = CCrmOwnerType::GetDetailsUrl(
				$this->entityTypeId,
				$this->entityId,
				true,
			);
		}

		return $url;
	}

	protected function getRequisiteComponentParams(): array
	{
		$action = $this->request->getPost('ACTION') ?? '';
		$isAction =
			$this->request->getRequestMethod() === 'POST'
			&& check_bitrix_sessid()
		;

		$isSave = ($isAction && $action === 'SAVE');
		$isReload = ($isAction && $action === 'RELOAD');

		$useFormData = $this->request->get('useFormData') ?? '';
		$useFormData =
			mb_strtoupper($useFormData) === 'Y'
			|| $isSave
			|| $isReload
		;

		$mode = $this->getMode();
		$pseudoId = $this->request->get('pseudoId') ?? '';
		$doSave = $this->isDoSave() ? 'Y' : 'N';
		$addBankDetailsItem = $this->isAddBankDetailsItem() ? 'Y' : 'N';

		$externalData = $this->request->get('externalData') ?? [];
		$useExternalData = empty($externalData) ? 'N' : 'Y';

		$post = $this->request->getPostList()->toArray();
		$externalContextId = $this->request->get('external_context_id') ?? '';

		$permissionToken = $this->request->get('permissionToken') ?? '';

		return [
			'ENTITY_TYPE_ID' => $this->entityTypeId,
			'CATEGORY_ID' => $this->categoryId,
			'ENTITY_ID' => $this->entityId,
			'REQUISITE_ID' => $this->requisiteId,
			'PRESET_ID' => $this->presetId,
			'PSEUDO_ID' => $pseudoId,
			'MODE' => $mode,
			'DO_SAVE' => $doSave,
			'USE_EXTERNAL_DATA' => $useExternalData,
			'EXTERNAL_DATA' => $externalData,
			'USE_FORM_DATA' => $useFormData ? 'Y' : 'N',
			'FORM_DATA' => $useFormData ? $post : [],
			'EXTERNAL_CONTEXT_ID' => $externalContextId,
			'IS_SAVE' => $isSave ? 'Y' : 'N',
			'IS_RELOAD' => $isReload ? 'Y' : 'N',
			'ADD_BANK_DETAILS_ITEM' => $addBankDetailsItem,
			'PERMISSION_TOKEN' => $permissionToken,
		];
	}

	private function getMode(): string
	{
		$mode = $this->request->get('mode') ?? '';
		if (!in_array($mode, static::AVAILABLE_MODES, true))
		{
			$copy = $this->request->get('copy') ?? '';
			if ($this->requisiteId > 0)
			{
				$mode = !empty($copy) ? 'copy' : 'edit';
			}
			else
			{
				$mode = 'create';
			}
		}

		return $mode;
	}

	private function isDoSave(): bool
	{
		$requestDoSave = $this->request->get('doSave') ?? '';

		return (mb_strtoupper($requestDoSave) === 'Y') || !$this->isOpenInEntityDetails;
	}

	private function isAddBankDetailsItem(): bool
	{
		$addBankDetailsItem = $this->request->get('addBankDetailsItem') ?? '';

		return mb_strtoupper($addBankDetailsItem) === 'Y';
	}
}
