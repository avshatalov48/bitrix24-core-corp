<?php

namespace Bitrix\Sign\Integration\CRM;

use Bitrix\Main\Loader;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Main\LoaderException;

class FieldCode
{
	protected string $code;

	public function __construct(string $code)
	{
		$this->code = $code;
	}

	public function getEntityTypeName(): ?string
	{
		if (!Loader::includeModule('crm'))
		{
			return null;
		}

		$allCrmOwnerTypeNames = \CCrmOwnerType::GetAllNames();
		foreach ($allCrmOwnerTypeNames as $crmOwnerTypeName)
		{
			if (mb_strpos($this->code, $crmOwnerTypeName) === 0)
			{
				return (string)$crmOwnerTypeName;
			}
		}

		return null;
	}

	public function getEntityFieldCode(): ?string
	{
		$entityTypeName = $this->getEntityTypeName();
		if ($entityTypeName === null)
		{
			return null;
		}

		return mb_substr($this->code, mb_strlen($entityTypeName) + 1);
	}

	public function getEntityTypeId(): ?int
	{
		$entityTypeName = $this->getEntityTypeName();
		if ($entityTypeName === null)
		{
			return null;
		}
		if (!Loader::includeModule('crm'))
		{
			return null;
		}

		return \CCrmOwnerType::ResolveID($entityTypeName);
	}

	/**
	 * @return string
	 */
	public function getCode(): string
	{
		return $this->code;
	}

	/**
	 *
	 * @param int|null $presetId
	 *
	 * @return null|array{CAPTION: string, TYPE: string, ITEMS?: array<array{ID: string, VALUE: string}>
	 */
	public function getDescription(?int $presetId = null): ?array
	{
		if (!Main\Loader::includeModule('crm'))
		{
			return null;
		}

		/** @var array<?int, array> $fieldDescriptionCacheByPreset */
		static $fieldDescriptionCacheByPreset = [];
		$fieldsDescriptionByPreset = $fieldDescriptionCacheByPreset[$presetId] ?? null;
		if ($fieldsDescriptionByPreset === null)
		{
			$fieldDescriptionCacheByPreset[$presetId] = Crm\WebForm\EntityFieldProvider::getAllFieldsDescription(
				$presetId
			);
		}

		foreach ($fieldDescriptionCacheByPreset[$presetId] ?? [] as $description)
		{
			if ($this->code === $description['CODE'])
			{
				return [
					'CAPTION' => $description['ENTITY_FIELD_CAPTION'],
					'TYPE' => $description['TYPE'],
					'ITEMS' => $description['ITEMS'] ?? null,
				];
			}
		}

		return null;
	}
}