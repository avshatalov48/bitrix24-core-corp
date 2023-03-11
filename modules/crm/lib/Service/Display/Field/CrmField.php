<?php

namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\Integration\UI\EntitySelector\DynamicMultipleProvider;
use Bitrix\Crm\Item\Company;
use Bitrix\Crm\Item\Contact;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Display\Options;
use Bitrix\Crm\UserField\Types\ElementType;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Localization\Loc;

class CrmField extends BaseLinkedEntitiesField
{
	public const TYPE = 'crm';
	protected array $entityTypes = [];

	public function prepareLinkedEntities(
		array &$linkedEntities,
		$fieldValue,
		int $itemId,
		string $fieldId
	): void
	{
		$this->entityTypes = $this->getEntityTypes();
		$fieldType = $this->getType();
		foreach ((array)$fieldValue as $value)
		{
			if ($this->needExplodeValue($value))
			{
				$valueParts = explode('_', $value);
				$entityType = ElementType::getLongEntityType($valueParts[0]);
				$entityId = (int)$valueParts[1];
			}
			else
			{
				$entityType = $this->entityTypes[0];
				$entityId = (int)$value;
			}

			if ($entityId > 0)
			{
				$linkedEntities[$fieldType][$entityType][] = $entityId;
				$linkedEntities[$fieldType]['FIELD'][$itemId][$fieldId][$entityType][$entityId] = $entityId;
			}
		}
	}

	protected function getFormattedValueForKanban($fieldValue, int $itemId, Options $displayOptions)
	{
		$this->setWasRenderedAsHtml(true);
		$result = ($this->isMultiple() ? [] : '');

		$linkedEntitiesValues = $this->getLinkedEntitiesValues();

		$fieldValue = is_array($fieldValue) ? $fieldValue : [$fieldValue];
		foreach ($fieldValue as $entityElement)
		{
			if ($this->needExplodeValue($entityElement))
			{
				[$entityTypePrefix, $entityElementId] = explode('_', $entityElement);
				$entityElementId = (int)$entityElementId;
			}
			elseif (!empty($this->entityTypes))
			{
				$entityTypePrefix = \CCrmOwnerTypeAbbr::ResolveByTypeName($this->entityTypes[0]);
				$entityElementId = (int)$entityElement;
			}
			else
			{
				continue;
			}

			$entityTypeId = \CCrmOwnerTypeAbbr::ResolveTypeID($entityTypePrefix);
			if (!$entityTypeId)
			{
				continue;
			}

			$entityType = \CCrmOwnerTypeAbbr::ResolveName($entityTypePrefix);
			$link = Container::getInstance()->getRouter()->getItemDetailUrl($entityTypeId, $entityElementId);
			$title = null;
			$prefix = '';
			$tooltipLoader = null;
			$className = null;

			if ($entityTypePrefix === \CCrmOwnerTypeAbbr::Lead)
			{
				$title = ($linkedEntitiesValues[\CCrmOwnerType::LeadName][$entityElementId]['TITLE'] ?? null);
				$prefix = \CCrmOwnerTypeAbbr::Lead;
				$className = 'crm_balloon_no_photo';
			}
			elseif ($entityTypePrefix === \CCrmOwnerTypeAbbr::Contact)
			{
				if (isset($linkedEntitiesValues[\CCrmOwnerType::ContactName][$entityElementId]))
				{
					$isEntityElementId = isset($linkedEntitiesValues[\CCrmOwnerType::ContactName][$entityElementId]);
					$title = (
					$isEntityElementId
						? $linkedEntitiesValues[\CCrmOwnerType::ContactName][$entityElementId]->getFormattedName()
						: null
					);
				}
				$prefix = \CCrmOwnerTypeAbbr::Contact;
			}
			elseif ($entityTypePrefix === \CCrmOwnerTypeAbbr::Company)
			{
				$title = ($linkedEntitiesValues[\CCrmOwnerType::CompanyName][$entityElementId]['TITLE'] ?? null);
				$prefix = \CCrmOwnerTypeAbbr::Company;
			}
			elseif ($entityTypePrefix === \CCrmOwnerTypeAbbr::Deal)
			{
				$title = ($linkedEntitiesValues[\CCrmOwnerType::DealName][$entityElementId]['TITLE'] ?? null);
				$prefix = \CCrmOwnerTypeAbbr::Deal;
				$className = 'crm_balloon_no_photo';
			}
			elseif ($entityTypePrefix === \CCrmOwnerTypeAbbr::Order)
			{
				$title = $this->getOrderTitle($entityElementId);
				$prefix = \CCrmOwnerTypeAbbr::Order;
				$tooltipLoader = '/bitrix/components/bitrix/crm.order.details/card.ajax.php';
			}
			elseif ($entityTypePrefix === \CCrmOwnerTypeAbbr::Quote)
			{
				$className = 'crm_balloon_no_photo';
				$title = ($linkedEntitiesValues[\CCrmOwnerType::QuoteName][$entityElementId]['TITLE'] ?? null);
			}
			elseif (\CCrmOwnerType::isUseFactoryBasedApproach($entityTypeId))
			{
				$title = (
					$linkedEntitiesValues[$entityType][$entityElementId]
						? $linkedEntitiesValues[$entityType][$entityElementId]->getHeading()
						: null
				);
				$prefix = \CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId);
				$tooltipLoader = UrlManager::getInstance()->create(
					'bitrix:crm.controller.tooltip.card',
					[
						'sessid' => bitrix_sessid(),
					]
				);
				$className = 'crm_balloon_no_photo';
				$entityElementId = $entityTypeId . '-' . $entityElementId;
			}

			$formattedValue = '';
			if ($title !== null)
			{
				$formattedValue = htmlspecialcharsbx($title);
				if ($displayOptions && !$this->isExportContext())
				{
					\Bitrix\Main\UI\Extension::load('ui.tooltip');
					$tooltipLoader = (
						$tooltipLoader
						?? htmlspecialcharsbx(
							'/bitrix/components/bitrix/crm.' . mb_strtolower($entityType) . '.show/card.ajax.php'
						)
					);
					$className = ($className ?? 'crm_balloon_' . mb_strtolower($entityType));
					$formattedValue = $this->getHtmlLink($link, $entityElementId, $tooltipLoader, $className, $formattedValue);
				}
				elseif ($this->isUserField())
				{
					$formattedValue = "[$prefix]$formattedValue";
				}
			}

			if ($formattedValue !== '')
			{
				if (!$this->isMultiple())
				{
					return $formattedValue;
				}
				$result[] = $formattedValue;
			}
		}

		return $result;
	}

	protected function getEntityTypes(): array
	{
		$entityTypes = array_flip(ElementType::getEntityTypeNames());
		$fieldSettings = (array)($this->getUserFieldParams()['SETTINGS'] ?? []);
		$displayParams = array_merge($fieldSettings, $this->getDisplayParams());

		$crmEntityTypes = [];

		foreach ($displayParams as $settingsEntityTypeId => $value)
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

	protected function needExplodeValue($entityElement): bool
	{
		return (count($this->entityTypes) > 1 || !is_numeric($entityElement));
	}

	protected function getHtmlLink(
		string $link,
		string $id,
		string $tooltipLoader,
		string $className,
		string $content
	): string
	{
		$result = '<a target="_blank"';
		$result .= ' href="' . $link . '"';
		$result .= ' bx-tooltip-user-id="' . $id . '"';
		$result .= ' bx-tooltip-loader="' . $tooltipLoader . '"';
		$result .= ' bx-tooltip-classname="' . $className . '"';
		$result .= ' >' . $content . '</a>';

		return $result;
	}

	/**
	 * @param $fieldValue
	 * @param int $itemId
	 * @param Options $displayOptions
	 * @return array
	 * @todo get rid of code duplication with getFormattedValueForKanban method
	 *
	 */
	protected function getFormattedValueForMobile($fieldValue, int $itemId, Options $displayOptions): array
	{
		$result = [];
		$linkedEntitiesValues = $this->getLinkedEntitiesValues();
		$fieldValue = (is_array($fieldValue) ? $fieldValue : [$fieldValue]);

		foreach ($fieldValue as $entityElement)
		{
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
			$categoryId = 0;
			$entityType = \CCrmOwnerTypeAbbr::ResolveName($entityTypePrefix);
			$factory = Container::getInstance()->getFactory($entityTypeId);
			if ($factory && $factory->isCategoriesSupported())
			{
				$entityValue = $linkedEntitiesValues[$entityType][$entityElementId];
				if ($entityValue)
				{
					$categoryId = $entityValue->getCategoryId();
				}
			}
			$title = null;
			$hidden = !Container::getInstance()->getUserPermissions()->checkReadPermissions(
				$entityTypeId,
				$entityElementId,
				$categoryId
			);
			$imageUrl = null;

			if(!$hidden)
			{
				if ($entityTypePrefix === \CCrmOwnerTypeAbbr::Lead)
				{
					$title = ($linkedEntitiesValues[\CCrmOwnerType::LeadName][$entityElementId]['TITLE'] ?? null);
				}
				elseif ($entityTypePrefix === \CCrmOwnerTypeAbbr::Contact)
				{
					if (isset($linkedEntitiesValues[\CCrmOwnerType::ContactName][$entityElementId]))
					{
						$entityElementObject = $linkedEntitiesValues[\CCrmOwnerType::ContactName][$entityElementId] ?? null;
						if ($entityElementObject)
						{
							$title = $entityElementObject->getFormattedName();
							$logo = $entityElementObject->get(Contact::FIELD_NAME_PHOTO);
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
					}
				}
				elseif ($entityTypePrefix === \CCrmOwnerTypeAbbr::Company)
				{
					if (isset($linkedEntitiesValues[\CCrmOwnerType::CompanyName][$entityElementId]))
					{
						$entityElementObject = $linkedEntitiesValues[\CCrmOwnerType::CompanyName][$entityElementId] ?? null;
						if ($entityElementObject)
						{
							$title = $entityElementObject->getTitle();
							$logo = $entityElementObject->get(Company::FIELD_NAME_LOGO);
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
				}
				elseif ($entityTypePrefix === \CCrmOwnerTypeAbbr::Deal)
				{
					$title = ($linkedEntitiesValues[\CCrmOwnerType::DealName][$entityElementId]['TITLE'] ?? null);
				}
				elseif ($entityTypePrefix === \CCrmOwnerTypeAbbr::Order)
				{
					$title = $this->getOrderTitle($entityElementId);
				}
				elseif ($entityTypePrefix === \CCrmOwnerTypeAbbr::Quote)
				{
					$title = ($linkedEntitiesValues[\CCrmOwnerType::QuoteName][$entityElementId]['TITLE'] ?? null);
				}
				elseif (\CCrmOwnerType::isUseFactoryBasedApproach($entityTypeId))
				{
					$title = (
					$linkedEntitiesValues[$entityType][$entityElementId]
						? $linkedEntitiesValues[$entityType][$entityElementId]->getHeading()
						: null
					);
				}
			}

			if (
				\CCrmOwnerType::isUseFactoryBasedApproach($entityTypeId)
				&& \CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId)
			)
			{
					$entityElementId = DynamicMultipleProvider::prepareId($entityTypeId, $entityElementId);
					$entityType = DynamicMultipleProvider::DYNAMIC_MULTIPLE_ID;
			}

			$result[] = [
				'id' => $entityElementId,
				'title' => $title,
				'type' => mb_strtolower($entityType),
				'hidden' => $hidden,
				'imageUrl' => $imageUrl,
				'subtitle' => \CCrmOwnerType::GetDescription($entityTypeId),
			];
		}

		[$entityIds, $providerOptions] = $this->getCrmUserFieldEntityOptions();

		return [
			'value' => array_column($result, 'id'),
			'config' => [
				'castType' => 'string',
				'entityList' => $result,
				'entityIds' => $entityIds,
				'provider' => [
					'options' => $providerOptions,
				],
			],
		];
	}

	/**
	 * @param string $entityElement
	 * @return array|null
	 */
	protected function explodeEntityElement(string $entityElement): ?array
	{
		if ($this->needExplodeValue($entityElement))
		{
			[$entityTypePrefix, $entityElementId] = explode('_', $entityElement);
			$elementWasExploded = true;
		}
		elseif(empty($this->entityTypes[0]))
		{
			return null;
		}
		else
		{
			$entityTypePrefix = \CCrmOwnerTypeAbbr::ResolveByTypeName($this->entityTypes[0]);
			$entityElementId = $entityElement;
			$elementWasExploded = false;
		}

		return [
			$entityTypePrefix,
			(int)$entityElementId,
			$elementWasExploded,
		];
	}

	private function getCrmUserFieldEntityOptions(): array
	{
		$entityTypeNames = [];
		$options = [];

		foreach ($this->entityTypes as $entityName)
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
		$linkedEntitiesValues = $this->getLinkedEntitiesValues();
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

	public function loadLinkedEntities(array &$linkedEntitiesValues, array $linkedEntity): void
	{
		$fieldType = $this->getType();

		if (
			isset($linkedEntity[\CCrmOwnerType::LeadName])
			&& !empty($linkedEntity[\CCrmOwnerType::LeadName])
		)
		{
			$linkedEntitiesValues[$fieldType][\CCrmOwnerType::LeadName] = Container::getInstance()
				->getLeadBroker()
				->getBunchByIds($linkedEntity[\CCrmOwnerType::LeadName])
			;
		}

		if (
			isset($linkedEntity[\CCrmOwnerType::ContactName])
			&& !empty($linkedEntity[\CCrmOwnerType::ContactName])
		)
		{
			$linkedEntitiesValues[$fieldType][\CCrmOwnerType::ContactName] = Container::getInstance()
				->getContactBroker()
				->getBunchByIds($linkedEntity[\CCrmOwnerType::ContactName])
			;
		}

		if (
			isset($linkedEntity[\CCrmOwnerType::CompanyName])
			&& !empty($linkedEntity[\CCrmOwnerType::CompanyName])
		)
		{
			$linkedEntitiesValues[$fieldType][\CCrmOwnerType::CompanyName] = Container::getInstance()
				->getCompanyBroker()
				->getBunchByIds($linkedEntity[\CCrmOwnerType::CompanyName])
			;
		}

		if (
			isset($linkedEntity[\CCrmOwnerType::DealName])
			&& !empty($linkedEntity[\CCrmOwnerType::DealName])
		)
		{
			$linkedEntitiesValues[$fieldType][\CCrmOwnerType::DealName] = Container::getInstance()
				->getDealBroker()
				->getBunchByIds($linkedEntity[\CCrmOwnerType::DealName])
			;
		}

		if (
			isset($linkedEntity[\CCrmOwnerType::OrderName])
			&& !empty($linkedEntity[\CCrmOwnerType::OrderName])
		)
		{
			$linkedEntitiesValues[$fieldType][\CCrmOwnerType::OrderName] = Container::getInstance()
				->getOrderBroker()
				->getBunchByIds($linkedEntity[\CCrmOwnerType::OrderName])
			;
		}

		foreach ($linkedEntity as $entityTypeName => $entityIds)
		{
			if (in_array($entityTypeName, [
				\CCrmOwnerType::LeadName,
				\CCrmOwnerType::DealName,
				\CCrmOwnerType::ContactName,
				\CCrmOwnerType::CompanyName,
				\CCrmOwnerType::OrderName,
			]))
			{
				continue;
			}
			$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
			if ($entityTypeId)
			{
				if (!is_array($linkedEntitiesValues[$fieldType]))
				{
					$linkedEntitiesValues[$fieldType] = [];
				}

				$linkedEntitiesValues[$fieldType][$entityTypeName] = Container::getInstance()
					->getDynamicBroker()
					->setEntityTypeId($entityTypeId)
					->getBunchByIds($entityIds)
				;
			}
		}
	}

	public function getPreparedEntityValue(array $linkedEntitiesValues, string $fieldValueType, $fieldValueId)
	{
		$fieldType = $this->getType();
		$results = [];
		foreach ($fieldValueId as $id)
		{
			if (array_key_exists($id, $linkedEntitiesValues[$fieldType][$fieldValueType]))
			{
				$results[$id] = $linkedEntitiesValues[$fieldType][$fieldValueType][$id];
			}
		}

		return [
			$fieldValueType => $results,
		];
	}
}
