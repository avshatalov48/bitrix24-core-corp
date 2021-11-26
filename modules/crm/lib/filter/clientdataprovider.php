<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;

class ClientDataProvider extends Main\Filter\EntityDataProvider
{
	/** @var EntitySettings|null */
	protected $settings = null;
	protected $clientEntityTypeId;
	protected $clientDataProvider;
	protected $clientFieldHelper;

	function __construct(int $clientEntityTypeId, EntitySettings $settings)
	{
		$this->settings = $settings;
		$this->clientEntityTypeId = $clientEntityTypeId;
		$this->clientFieldHelper = new \Bitrix\Crm\Component\EntityList\ClientFieldHelper($this->clientEntityTypeId);

		$filterFactory = Crm\Service\Container::getInstance()->getFilterFactory();
		$settings = $filterFactory->getSettings($clientEntityTypeId, $settings->getID());
		$this->clientDataProvider = $filterFactory->getDataProvider($settings);
	}

	/**
	 * Get Settings
	 * @return EntitySettings
	 */
	public function getSettings()
	{
		return $this->settings;
	}

	/**
	 * Get specified entity field caption.
	 * @param string $fieldId Field ID.
	 * @return string
	 */
	protected function getFieldName($fieldId): string
	{
		$fieldId = (string)$fieldId;

		$name = $this->clientFieldHelper->getFieldName($this->clientFieldHelper->getFieldIdWithoutPrefix($fieldId));
		if ($name !== '')
		{
			return $name;
		}
		$name = Loc::getMessage("CRM_DEAL_FILTER_{$fieldId}");
		if ($name === null)
		{
			$name = \CCrmDeal::GetFieldCaption($fieldId);
		}

		return (string)$name;
	}

	/**
	 * Prepare field list.
	 * @return Field[]
	 */
	public function prepareFields(): array
	{
		if (!$this->hasPermissions())
		{
			return [];
		}

		return $this->prepareClientFields();
	}

	/**
	 * Prepare complete field data for specified field.
	 * @param string $fieldId Field ID.
	 * @return array|null
	 * @throws Main\NotSupportedException
	 */
	public function prepareFieldData($fieldId): ?array
	{
		if ($this->clientDataProvider)
		{
			return $this->clientDataProvider->prepareFieldData($this->clientFieldHelper->getFieldIdWithoutPrefix($fieldId));
		}

		return null;
	}

	protected function hasPermissions(): bool
	{
		return \Bitrix\Crm\Security\EntityAuthorization::checkReadPermission($this->clientEntityTypeId, 0);
	}

	protected function prepareClientFields(): array
	{
		$result = [];
		$entityFields = $this->getEntityFields();
		$filterFields = $this->clientDataProvider->prepareFields();
		$entityTypeName = \CCrmOwnerType::ResolveName($this->clientEntityTypeId);
		foreach ($filterFields as $field)
		{
			$fieldId = $this->clientFieldHelper->addPrefixToFieldId($field->getId());
			if (isset($entityFields[$fieldId]) && !$this->isIgnoredField($fieldId))
			{
				$field->setId($fieldId);
				$field->setName($this->getFieldName($fieldId));
				$field->setSectionId($entityTypeName);
				$field->setIconParams([
					'url' => '/bitrix/images/crm/grid_icons/' . strtolower($entityTypeName) . '.svg',
					'title' => $this->clientFieldHelper->getEntityTitle(),
				]);
				$field->markAsDefault(false);
				$field->setDataProvider($this);
				$result[$fieldId] = $field;
			}
		}

		return $result;
	}

	protected function getEntityFields(): array
	{
		if ($this->settings instanceof DealSettings)
		{
			return \CCrmDeal::GetFields();
		}
		throw new Main\NotImplementedException();
	}

	protected function isIgnoredField(string $fieldIdWithPrefix): bool{
		return in_array(
			$fieldIdWithPrefix,
			[
				'CONTACT_ID',
				'COMPANY_ID',
				'CONTACT_COMPANY_ID',
			],
			true
		);
	}
}
