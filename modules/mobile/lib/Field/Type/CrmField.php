<?php

namespace Bitrix\Mobile\Field\Type;

use Bitrix\Crm\Integration\UI\EntitySelector\DynamicMultipleProvider;
use Bitrix\Crm\Item\Company;
use Bitrix\Crm\Item\Contact;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\UserField\Types\ElementType;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Field\BoundEntitiesContainer;

class CrmField extends BaseField implements HasBoundEntities
{
	protected ?array $processedValue = null;

	public const TYPE = 'crm';

	/**
	 * @inheritDoc
	 */
	public function getFormattedValue()
	{
		if (!Loader::includeModule('crm'))
		{
			return null;
		}

		return $this->getProcessedValue()['value'];
	}

	public function getData(): array
	{
		$data = parent::getData();

		return array_merge($data, $this->getProcessedValue()['config']);
	}

	public function getBoundEntities(): array
	{
		$value = $this->value;
		if (!$value)
		{
			return [];
		}

		if (!$this->isMultiple())
		{
			$value = [$value];
		}

		return [
			'crm' => [
				'ids' => $value,
				'field' => $this,
			],
		];
	}

	protected function getProcessedValue()
	{
		if (!is_null($this->processedValue))
		{
			return $this->processedValue;
		}

		if (!Loader::includeModule('crm'))
		{
			return null;
		}

		$result = [];
		$boundEntities = BoundEntitiesContainer::getInstance()->getBoundEntities()['crm'] ?? [];

		$fieldValue = (is_array($this->getValue()) ? $this->getValue() : [$this->getValue()]);
		foreach ($fieldValue as $entityElement)
		{
			if ($entityElement === null)
			{
				continue;
			}

			[$entityTypePrefix, $entityElementId] = $this->explodeEntityElement((string)$entityElement);

			if ($entityTypePrefix === null)
			{
				continue;
			}

			$entityTypeId = \CCrmOwnerTypeAbbr::ResolveTypeID($entityTypePrefix);
			if (!$entityTypeId || !$entityElementId)
			{
				continue;
			}

			$entityTypeName = \CCrmOwnerTypeAbbr::ResolveName($entityTypePrefix);
			$entityValue = $boundEntities[$entityTypeName][$entityElementId];
			if (!$entityValue)
			{
				continue;
			}

			$title = null;
			$imageUrl = null;

			$hasReadPermission = $this->hasReadPermissions($entityTypeId, $entityElementId, $entityValue);
			if ($hasReadPermission)
			{
				$title = $this->getEntityTitle($entityTypeId, $entityElementId, $entityValue);

				if ($entityTypeId === \CCrmOwnerType::Contact)
				{
					$logo = $entityValue->get(Contact::FIELD_NAME_PHOTO);
					$imageUrl = \CFile::ResizeImageGet(
						$logo,
						['width' => 200, 'height' => 200],
						BX_RESIZE_IMAGE_EXACT,
						false,
						false,
						true
					);
					$imageUrl = $imageUrl['src'] ?? null;
				}
				elseif ($entityTypeId === \CCrmOwnerType::Company)
				{
					$title = $entityValue->getTitle();
					$logo = $entityValue->get(Company::FIELD_NAME_LOGO);
					$imageUrl = \CFile::ResizeImageGet(
						$logo,
						['width' => 300, 'height' => 300],
						BX_RESIZE_IMAGE_EXACT,
						false,
						false,
						true
					);
					$imageUrl = $imageUrl['src'] ?? null;
				}
			}

			if (
				\CCrmOwnerType::isUseFactoryBasedApproach($entityTypeId)
				&& \CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId)
			)
			{
				$entityElementId = DynamicMultipleProvider::prepareId($entityTypeId, $entityElementId);
				$entityTypeName = DynamicMultipleProvider::DYNAMIC_MULTIPLE_ID;
			}

			$result[] = [
				'id' => $entityElementId,
				'title' => $title,
				'type' => mb_strtolower($entityTypeName),
				'hidden' => !$hasReadPermission,
				'imageUrl' => $imageUrl,
				'subtitle' => \CCrmOwnerType::GetDescription($entityTypeId),
			];
		}

		[$entityIds, $providerOptions] = $this->getCrmUserFieldEntityOptions();

		$this->processedValue = [
			'value' => array_column($result, 'id'),
			'config' => [
				'castType' => 'string',
				'selectorTitle' => $this->getTitle(),
				'entityList' => $result,
				'entityIds' => $entityIds,
				'provider' => [
					'options' => $providerOptions,
				],
			],
		];

		return $this->processedValue;
	}

	protected function needExplodeValue($entityElement): bool
	{
		return (count($this->getEntityTypes()) > 1 || !is_numeric($entityElement));
	}

	protected function explodeEntityElement(string $entityElement): ?array
	{
		if ($this->needExplodeValue($entityElement))
		{
			[$entityTypePrefix, $entityElementId] = explode('_', $entityElement);
			$elementWasExploded = true;
		}
		elseif (empty($this->getEntityTypes()[0]))
		{
			return null;
		}
		else
		{
			$entityTypePrefix = \CCrmOwnerTypeAbbr::ResolveByTypeName($this->getEntityTypes()[0]);
			$entityElementId = $entityElement;
			$elementWasExploded = false;
		}

		return [
			$entityTypePrefix,
			(int)$entityElementId,
			$elementWasExploded,
		];
	}

	protected function getEntityTypes(): array
	{
		$entityTypes = array_flip(ElementType::getEntityTypeNames());
		$fieldSettings = (array)($this->getUserFieldInfo()['SETTINGS'] ?? []);

		$crmEntityTypes = [];

		foreach ($fieldSettings as $settingsEntityTypeId => $value)
		{
			if (
				$value === 'Y'
				&& (
					array_key_exists($settingsEntityTypeId, $entityTypes)
					|| \CCrmOwnerType::isPossibleDynamicTypeId(\CCrmOwnerType::ResolveID($settingsEntityTypeId))
				)
			)
			{
				$crmEntityTypes[] = $settingsEntityTypeId;
			}
		}

		return $crmEntityTypes;
	}

	private function hasReadPermissions(int $entityTypeId, int $entityId, $entityValue): bool
	{
		$categoryId = 0;

		$factory = Container::getInstance()->getFactory($entityTypeId);
		if ($factory && $factory->isCategoriesSupported())
		{
			$categoryId = $entityValue->getCategoryId();
		}

		$userPermissions = Container::getInstance()->getUserPermissions();

		if ($entityTypeId === \CCrmOwnerType::Company && \CCrmCompany::isMyCompany($entityId))
		{
			return $userPermissions->getMyCompanyPermissions()->canReadBaseFields();
		}

		return $userPermissions->checkReadPermissions($entityTypeId, $entityId, $categoryId);
	}

	private function getEntityTitle(int $entityTypeId, int $entityId, $entityValue): ?string
	{
		if (!$entityValue)
		{
			return null;
		}

		if (
			$entityTypeId === \CCrmOwnerType::Lead
			|| $entityTypeId === \CCrmOwnerType::Deal
			|| $entityTypeId === \CCrmOwnerType::Company
		)
		{
			return $entityValue->getTitle();
		}

		if ($entityTypeId === \CCrmOwnerType::Contact)
		{
			return $entityValue->getFormattedName();
		}

		if ($entityTypeId === \CCrmOwnerType::Order)
		{
			return $this->getOrderTitle($entityId);
		}

		if (
			$entityTypeId === \CCrmOwnerType::Quote
			|| \CCrmOwnerType::isUseFactoryBasedApproach($entityTypeId)
		)
		{
			return $entityValue->getHeading();
		}

		return null;
	}

	private function getCrmUserFieldEntityOptions(): array
	{
		$entityTypeNames = [];
		$options = [];

		foreach ($this->getEntityTypes() as $entityName)
		{
			$entityName = mb_strtolower($entityName);

			if (mb_strpos($entityName, 'dynamic_') === 0)
			{
				$entityTypeId = (int)mb_substr($entityName, 8);
				$entityName = DynamicMultipleProvider::DYNAMIC_MULTIPLE_ID;

				$options[$entityName]['dynamicTypeIds'][] = $entityTypeId;
			}

			$entityTypeNames[] = mb_strtolower($entityName);
		}

		$entityTypeNames = array_values(array_unique($entityTypeNames));

		return [$entityTypeNames, $options];
	}

	/**
	 * @param string $entityElementId
	 * @return string|null
	 */
	protected function getOrderTitle(string $entityElementId): ?string
	{
		$linkedEntitiesValues = $this->getBoundEntities();
		$order = $linkedEntitiesValues[\CCrmOwnerType::OrderName][$entityElementId];
		if ($order)
		{
			$orderTitle = $order->getField('ORDER_TOPIC');
			if (empty($orderTitle))
			{
				$orderTitle = Loc::getMessage(
					'CRM_FIELD_OWNER_TYPE_ORDER_TITLE',
					[
						'#ACCOUNT_NUMBER#' => $order->getField('ACCOUNT_NUMBER'),
					]
				);
			}
		}
		return ($orderTitle ?? null);
	}
}
