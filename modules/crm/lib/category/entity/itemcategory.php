<?php

namespace Bitrix\Crm\Category\Entity;

use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\Model\EO_ItemCategory;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use CCrmOwnerType;

class ItemCategory extends Category
{
	private const SYS_CAT_LANG_PHRASE_PREFIX = 'CRM_CATEGORY_ENTITY_ITEM_CATEGORY_NAME';
	private const SYS_CAT_LANG_PHRASE_PLURAL_SUFFIX = 'PLURAL';
	private const SYS_CAT_LANG_PHRASE_SINGLE_SUFFIX = 'SINGLE';

	protected $entityObject;

	public function __construct(EO_ItemCategory $entityObject)
	{
		$this->entityObject = $entityObject;

		Container::getInstance()->getLocalization()->loadMessages(); // TODO: separate file with system categories
	}

	public function getData(): array
	{
		if (in_array($this->getEntityTypeId(), [CCrmOwnerType::Contact, CCrmOwnerType::Company], true))
		{
			return array_merge(parent::getData(), [
				'IS_SYSTEM' => $this->getIsSystem(),
				'CODE' => $this->getCode(),
			]);
		}

		return parent::getData();
	}

	public function getId(): ?int
	{
		return $this->entityObject->getId();
	}

	public function getEntityTypeId(): int
	{
		return $this->entityObject->getEntityTypeId();
	}

	public function setEntityTypeId(int $entityTypeId): Category
	{
		$this->entityObject->setEntityTypeId($entityTypeId);

		return $this;
	}

	public function getName(): string
	{
		$code = $this->getCode();
		if (!empty($code))
		{
			$name = $this->getNameByCode($code, true);
			if (!empty($name))
			{
				return $name;
			}
		}

		return $this->entityObject->getName();
	}

	public function getSingleName(): ?string
	{
		$code = $this->getCode();
		if (!empty($code))
		{
			$name = $this->getNameByCode($code, false);
			if (!empty($name))
			{
				return $name;
			}
		}

		return $this->entityObject->getName();
	}

	public function setName(string $name): Category
	{
		$this->entityObject->setName($name);

		return $this;
	}

	private function getNameByCode(string $code, bool $isPlural): ?string
	{
		$name = Loc::getMessage(
			sprintf(
				'%s_%s_%s',
				self::SYS_CAT_LANG_PHRASE_PREFIX,
				$code,
				$isPlural
					? self::SYS_CAT_LANG_PHRASE_PLURAL_SUFFIX
					: self::SYS_CAT_LANG_PHRASE_SINGLE_SUFFIX
			)
		);

		$defaultName = Loc::getMessage(
			sprintf(
				'%s_DEFAULT_%s_%s',
				self::SYS_CAT_LANG_PHRASE_PREFIX,
				CCrmOwnerType::ResolveName($this->getEntityTypeId()),
				(
					$isPlural
						? self::SYS_CAT_LANG_PHRASE_PLURAL_SUFFIX
						: self::SYS_CAT_LANG_PHRASE_SINGLE_SUFFIX
				)
			)
		);

		return $name ?: $defaultName;
	}

	public function getSort(): int
	{
		return $this->entityObject->getSort();
	}

	public function setSort(int $sort): Category
	{
		$this->entityObject->setSort($sort);

		return $this;
	}

	public function setIsDefault(bool $isDefault): Category
	{
		$this->entityObject->setIsDefault($isDefault);

		return $this;
	}

	public function getIsDefault(): bool
	{
		return $this->entityObject->getIsDefault();
	}

	public function getIsSystem(): bool
	{
		return $this->entityObject->getIsSystem();
	}

	public function getCode(): string
	{
		return (string)$this->entityObject->getCode();
	}

	public function getDisabledFieldNames(): array
	{
		$settings = $this->entityObject->getSettings();

		return (isset($settings['disabledFieldNames']) && is_array($settings['disabledFieldNames']))
			? $settings['disabledFieldNames']
			: [];
	}

	public function isTrackingEnabled(): bool
	{
		$settings = $this->entityObject->getSettings();

		if (isset($settings['isTrackingEnabled']))
		{
			return (bool)$settings['isTrackingEnabled'];
		}

		return parent::isTrackingEnabled();
	}

	public function getUISettings(): array
	{
		$settings = $this->entityObject->getSettings();

		return (isset($settings['uiSettings']) && is_array($settings['uiSettings']))
			? $settings['uiSettings']
			: [];
	}

	public function getDefaultAddressType()
	{
		$result = EntityAddressType::Undefined;

		/** @noinspection PhpUndefinedMethodInspection */
		$settings = $this->entityObject->getSettings();

		if (is_array($settings) && isset($settings['defAddressType']))
		{
			$addressTypeId = (int)$settings['defAddressType'];
			if (EntityAddressType::isDefined($addressTypeId))
			{
				$result = $addressTypeId;
			}
		}

		return $result;
	}

	public function save(): Result
	{
		return $this->entityObject->save();
	}

	public function delete(): Result
	{
		return $this->entityObject->delete();
	}
}
