<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\Crm\Binding\EntityContactTable;
use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Integration\DocumentGenerator\Value\Money;
use Bitrix\Crm\Item;
use Bitrix\Crm\Model\Dynamic\Type;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\StatusTable;
use Bitrix\DocumentGenerator\DataProvider\ArrayDataProvider;
use Bitrix\DocumentGenerator\DataProvider\Filterable;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\Main\Localization\Loc;

abstract class Dynamic extends ProductsDataProvider implements Filterable
{
	protected $contacts;
	protected $myCompanyId;

	public function getCrmOwnerType(): int
	{
		return static::getEntityTypeId();
	}

	abstract public static function getEntityTypeId(): int;

	public function getFields(): array
	{
		if ($this->fields !== null)
		{
			return $this->fields;
		}

		$this->fields = parent::getFields();

		$factory = $this->getFactory();
		if (!$factory)
		{
			return $this->fields;
		}

		if ($factory->isCategoriesSupported())
		{
			$this->fields['CATEGORY'] = [
				'TITLE' => Loc::getMessage('CRM_COMMON_CATEGORY'),
				'VALUE' => [$this, 'getCategory'],
			];
		}
		else
		{
			unset($this->fields[Item::FIELD_NAME_CATEGORY_ID]);
		}

		$fieldsWithStages = [
			'STAGE' => Item::FIELD_NAME_STAGE_ID,
			'PREVIOUS_STAGE' => Item::FIELD_NAME_PREVIOUS_STAGE_ID,
		];

		if ($factory->isStagesEnabled())
		{
			foreach ($fieldsWithStages as $providerFieldName => $valueFieldName)
			{
				$this->fields[$providerFieldName] = [
					'VALUE' => [$this, 'getStage'],
					'TITLE' => $factory->getFieldCaption($valueFieldName),
				];
			}
		}

		if ($factory->isClientEnabled() && !$this->isLightMode())
		{
			$this->fields['CONTACTS'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_DEAL_CONTACTS_TITLE'),
				'PROVIDER' => ArrayDataProvider::class,
				'OPTIONS' => [
					'ITEM_PROVIDER' => Contact::class,
					'ITEM_NAME' => 'CONTACT',
					'ITEM_TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_DEAL_CONTACT_TITLE'),
					'ITEM_OPTIONS' => [
						'DISABLE_MY_COMPANY' => true,
						'isLightMode' => true,
					],
				],
				'VALUE' => [$this, 'getContacts'],
			];
		}
		else
		{
			unset(
				$this->fields['COMPANY'],
				$this->fields['CONTACT'],
				$this->fields['CONTACTS'],
				$this->fields['CLIENT_PHONE'],
				$this->fields['CLIENT_EMAIL'],
				$this->fields['CLIENT_WEB']
			);
		}

		if(!$factory->isMyCompanyEnabled())
		{
			unset(
				$this->fields['MY_COMPANY'],
				$this->fields['REQUISITE'],
				$this->fields['BANK_DETAIL']
			);
		}

		if ($factory->isLinkWithProductsEnabled())
		{
			$this->fields[Item::FIELD_NAME_OPPORTUNITY]['TYPE'] = Money::class;
			$this->fields[Item::FIELD_NAME_OPPORTUNITY]['FORMAT'] = ['CURRENCY_ID' => $this->getCurrencyId()];
		}
		else
		{
			unset(
				$this->fields[Item::FIELD_NAME_OPPORTUNITY],
				$this->fields[Item::FIELD_NAME_IS_MANUAL_OPPORTUNITY],
				$this->fields['PRODUCTS'],
				$this->fields['TAXES'],
				$this->fields[Item::FIELD_NAME_CURRENCY_ID],
				$this->fields['CURRENCY_NAME']
			);

			foreach ($this->getTotalFields() as $name => $field)
			{
				unset($this->fields[$name]);
			}
		}

		if ($factory->isSourceEnabled())
		{
			$this->fields['SOURCE'] = [
				'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_DEAL_SOURCE_TITLE'),
				'VALUE' => [$this, 'getSourceValue'],
			];
		}
		else
		{
			unset($this->fields[Item::FIELD_NAME_SOURCE_DESCRIPTION]);
		}

		if (!$factory->isBeginCloseDatesEnabled())
		{
			unset(
				$this->fields[Item::FIELD_NAME_BEGIN_DATE],
				$this->fields[Item::FIELD_NAME_CLOSE_DATE]
			);
		}

		return $this->fields;
	}

	protected function fetchData()
	{
		parent::fetchData();

		$this->getFields();
		foreach ($this->userFieldDescriptions as $name => $description)
		{
			// we should purge values or they will not be processed in self::getUserFieldValue()
			unset($this->data[$name]);
		}
	}

	protected function getFactory(): ?Factory\Dynamic
	{
		if(\CCrmOwnerType::isUseDynamicTypeBasedApproach($this->getCrmOwnerType()))
		{
			/** @noinspection PhpIncompatibleReturnTypeInspection */
			return Container::getInstance()->getFactory($this->getCrmOwnerType());
		}

		return null;
	}

	protected function getUserFieldEntityID(): string
	{
		$factory = $this->getFactory();
		if($factory)
		{
			return $factory->getUserFieldEntityID();
		}

		return '';
	}

	protected function getTableClass(): string
	{
		$factory = $this->getFactory();
		if($factory)
		{
			return $factory->getDataClass();
		}

		return '';
	}

	public static function getClassForEntity(int $entityTypeId): string
	{
		$className = __NAMESPACE__ . '\Dynamic' . $entityTypeId;
		if(!class_exists($className))
		{
			$classCode = 'namespace ' . __NAMESPACE__ . ' {';
			$classCode .= 'class Dynamic' . $entityTypeId . ' extends Dynamic {';
			$classCode .= 'public static function getEntityTypeId(): int { return ' . $entityTypeId . ';}';
			$classCode .= '}}';

			eval($classCode);
		}

		return $className;
	}

	public static function getProviderCode(int $entityTypeId, int $categoryId = 0): string
	{
		$code = static::getClassForEntity($entityTypeId);

		if($categoryId > 0)
		{
			$code .= '_' . $categoryId;
		}

		return mb_strtolower($code);
	}

	public static function getExtendedList(): array
	{
		$result = [];

		$typesMap = Container::getInstance()->getDynamicTypesMap()->load([
			'isLoadStages' => false,
		]);
		$types = array_filter($typesMap->getTypes(), static function($type) {
			return (
				$type->getEntityTypeId() === static::getEntityTypeId()
				&& $type->getIsDocumentsEnabled()
			);
		});
		if(count($types) <= 0)
		{
			return $result;
		}

		foreach($types as $type)
		{
			static::extendProvidersListForType($result, $type, $typesMap->getCategories($type->getEntityTypeId()));
		}

		return $result;
	}

	/**
	 * @param Type $type
	 * @param \Bitrix\Crm\Model\EO_ItemCategory[]|Category[] $categories
	 */
	protected static function extendProvidersListForType(array &$providers, Type $type, array $categories): void
	{
		foreach($categories as $category)
		{
			if ($type->getIsCategoriesEnabled())
			{
				$name = Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_DYNAMIC_PROVIDER_WITH_CATEGORY_TITLE', [
					'#TYPE#' => static::getLangName(),
					'#CATEGORY#' => $category->getName(),
				]);
			}
			else
			{
				$name = static::getLangName();
			}
			$providers[] = [
				'NAME' => $name,
				'PROVIDER' => static::getProviderCode($type->getEntityTypeId(), $category->getId())
			];
		}
	}

	public function hasAccess($userId): bool
	{
		if($this->isLoaded())
		{
			return Container::getInstance()->getUserPermissions($userId)->checkReadPermissions(
				$this->getCrmOwnerType(),
				(int) $this->source,
				(int) $this->data['CATEGORY_ID']
			);
		}

		return false;
	}

	public function getFilterString(): string
	{
		if(!$this->isLoaded())
		{
			return static::class . '_' . '%';
		}

		$factory = $this->getFactory();
		if(!$factory)
		{
			return static::class . '_' . '%';
		}

		$categoryId = (int)$this->getRawValue('CATEGORY_ID');
		if ($categoryId <= 0)
		{
			return static::class . '_' . '%';
		}

		return static::getProviderCode($factory->getEntityTypeId(), $categoryId);
	}

	public static function getLangName(): string
	{
		$type = Container::getInstance()->getTypeByEntityTypeId(static::getEntityTypeId());
		if($type)
		{
			return $type->getTitle();
		}

		return Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_DYNAMIC_TITLE');
	}

	public function getCategory(): ?string
	{
		$factory = $this->getFactory();
		if ($factory && $this->isLoaded())
		{
			$categoryId = $this->data['CATEGORY_ID'] ?? null;
			if($categoryId > 0)
			{
				$category = $factory->getCategory($categoryId);
				if($category)
				{
					return $category->getName();
				}
			}
		}

		return null;
	}

	public function getStage($placeholder): ?string
	{
		$statusId = $this->data[$placeholder . '_ID'];
		if (empty($statusId))
		{
			return null;
		}
		$factory = $this->getFactory();
		if ($factory)
		{
			$stage = $factory->getStage($statusId);
			if ($stage)
			{
				return $stage->getName();
			}
		}

		return null;
	}

	protected function getHiddenFields(): array
	{
		return array_merge(parent::getHiddenFields(), [
			Item::FIELD_NAME_CREATED_BY,
			Item::FIELD_NAME_MOVED_BY,
			Item::FIELD_NAME_UPDATED_BY,
			Item::FIELD_NAME_CATEGORY_ID,
			Item::FIELD_NAME_PREVIOUS_STAGE_ID,
			Item::FIELD_NAME_STAGE_ID,
			Item::FIELD_NAME_COMPANY_ID,
			Item::FIELD_NAME_CONTACT_ID,
			Item::FIELD_NAME_TAX_VALUE,
			Item::FIELD_NAME_OPPORTUNITY_ACCOUNT,
			Item::FIELD_NAME_TAX_VALUE_ACCOUNT,
			Item::FIELD_NAME_ACCOUNT_CURRENCY_ID,
			Item::FIELD_NAME_MYCOMPANY_ID,
			'MYCOMPANY',
		]);
	}

	public function getContacts(): array
	{
		if ($this->contacts === null)
		{
			$this->contacts = [];
			if ($this->isLoaded())
			{
				$contactIds = EntityContactTable::getContactIds($this->getCrmOwnerType(), (int)$this->source);
				foreach($contactIds as $contactId)
				{
					$contact = DataProviderManager::getInstance()->getDataProvider(
						Contact::class,
						$contactId, [
							'isLightMode' => true,
							'DISABLE_MY_COMPANY' => true,
						],
						$this
					);
					$this->contacts[] = $contact;
				}
			}
		}

		return $this->contacts;
	}

	public function getMyCompanyId($defaultMyCompanyId = null)
	{
		$defaultMyCompanyId = (int) $defaultMyCompanyId;
		if (!$defaultMyCompanyId && $this->isLoaded())
		{
			$defaultMyCompanyId = $this->data['MYCOMPANY_ID'];
		}

		return parent::getMyCompanyId($defaultMyCompanyId);
	}

	public function getSourceValue(): ?string
	{
		if ($this->isLoaded() && !empty($this->data['SOURCE_ID']))
		{
			$status = StatusTable::getList([
				'select' => ['NAME'],
				'filter' => [
					'=ENTITY_ID' => 'SOURCE',
					'=STATUS_ID' => $this->data['SOURCE_ID'],
				],
				'limit' => 1,
			])->fetchObject();
			if($status)
			{
				return $status->getName();
			}
		}

		return null;
	}
}
