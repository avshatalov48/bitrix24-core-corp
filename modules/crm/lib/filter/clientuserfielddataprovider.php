<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Filter\EntitySettings;

class ClientUserFieldDataProvider extends UserFieldDataProvider
{
	protected $clientEntityTypeId;
	protected $clientFieldHelper;

	function __construct(int $clientEntityTypeId, EntitySettings $settings)
	{
		parent::__construct($settings);
		$this->clientEntityTypeId = $clientEntityTypeId;
		$this->clientFieldHelper = new \Bitrix\Crm\Component\EntityList\ClientFieldHelper($this->clientEntityTypeId);
	}

	/**
	 * Prepare field list.
	 * @return Field[]
	 */
	public function prepareFields(): array
	{
		$entityTypeName = \CCrmOwnerType::ResolveName($this->clientEntityTypeId);
		$result = parent::prepareFields();
		foreach($result as $fieldName => $field)
		{
			$result[$fieldName]->setSectionId($entityTypeName);
			$field->setIconParams([
				'url' => '/bitrix/images/crm/grid_icons/' . strtolower($entityTypeName) . '.svg',
				'title' => $this->clientFieldHelper->getEntityTitle(),
			]);
		}

		return $result;
	}

	/**
	 * Get user fields from contact or company for entity
	 * @return array
	 */
	protected function getUserFields(): array
	{
		global $USER_FIELD_MANAGER;

		static $result = [];

		$entityId = $this->getUserFieldEntityID();
		$ufEntityId = $this->getUfEntityId();
		$hashKey = $entityId. '_' . $ufEntityId;
		if (!isset($result[$hashKey]))
		{
			$result[$hashKey] = [];

			if (\Bitrix\Crm\Security\EntityAuthorization::checkReadPermission($this->clientEntityTypeId, 0))
			{
				$fields = $USER_FIELD_MANAGER->getUserFields(
					$ufEntityId,
					0,
					LANGUAGE_ID,
					false
				);
				$fields = $this->postFilterFields($fields);
				$fields = $this->prepareNameAndTitle(
					$fields
				);

				$result[$hashKey] = $fields;
			}
		}

		return $result[$hashKey];
	}

	private function getUfEntityId(): string
	{
		$ufEntityIds = [
			\CCrmOwnerType::Contact => \CCrmContact::GetUserFieldEntityID(),
			\CCrmOwnerType::Company => \CCrmCompany::GetUserFieldEntityID(),
		];
		if (!isset($ufEntityIds[$this->clientEntityTypeId]))
		{
			throw new \Bitrix\Main\NotSupportedException();
		}

		return $ufEntityIds[$this->clientEntityTypeId];
	}

	private function prepareNameAndTitle(array $fields): array
	{
		$result = [];
		foreach ($fields as $field)
		{
			$fieldName = $this->clientFieldHelper->addPrefixToFieldId((string)$field['FIELD_NAME']);

			$field['FIELD_NAME'] = $fieldName;
			$result[$fieldName] = $field;
		}

		return $result;
	}
}
