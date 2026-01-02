<?php

namespace Bitrix\Crm\Integration\AI\Function\UserField;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Entity\EntityEditorConfig;
use Bitrix\Crm\Integration\AI\Contract\AIFunction;
use Bitrix\Crm\Integration\AI\Function\UserField\Dto\CreateUserFieldParameters;
use Bitrix\Crm\Integration\AI\Function\UserField\Enum\UserFieldType;
use Bitrix\Crm\Integration\UI\EntityEditor\Configuration\Element;
use Bitrix\Crm\Integration\UI\EntityEditor\Enum\MarkTarget;
use Bitrix\Crm\Integration\UI\EntityEditor\MartaAIMarksRepository;
use Bitrix\Crm\Result;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\UserField\FieldNameGenerator;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use CCrmOwnerType;
use CLanguage;
use CUserTypeEntity;

abstract class AbstractCreateUserField implements AIFunction
{
	protected FieldNameGenerator $fieldNameGenerator;
	protected UserPermissions $userPermissions;

	public function __construct(
		private readonly int $currentUserId,
	)
	{
		$this->fieldNameGenerator = new FieldNameGenerator();
		$this->userPermissions = Container::getInstance()->getUserPermissions($this->currentUserId);
	}

	public function isAvailable(): bool
	{
		return true;
	}

	abstract protected function isMultiple(): bool;

	abstract protected function getType(): UserFieldType;

	protected function settings(): array
	{
		return [];
	}

	final public function invoke(...$args): Result
	{
		$parameters = $this->parseParameters($args);
		if ($parameters->hasValidationErrors())
		{
			return Result::fail($parameters->getValidationErrors());
		}

		if (!$this->userPermissions->isAdminForEntity($parameters->entityTypeId, $parameters->categoryId))
		{
			return Result::fail(ErrorCode::getAccessDeniedError());
		}

		$result = $this->save($parameters);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$fieldName = $result->getData()['fields']['FIELD_NAME'];
		$this->addFieldToAdditionalOrFirstSectionIntoCurrentUserView($parameters, $fieldName);

		return $result;
	}

	protected function parseParameters(array $args): CreateUserFieldParameters
	{
		return new CreateUserFieldParameters($args);
	}

	protected function save(CreateUserFieldParameters $parameters): Result
	{
		$entityId = CCrmOwnerType::ResolveUserFieldEntityID($parameters->entityTypeId);
		if (empty($entityId))
		{
			return Result::fail(ErrorCode::getEntityTypeNotSupportedError($parameters->entityTypeId));
		}

		$fields = [
			'ENTITY_ID' => $entityId,
			'FIELD_NAME' => $this->fieldNameGenerator->generate($entityId),
			'MULTIPLE' => $this->isMultiple() ? 'Y' : 'N',
			'USER_TYPE_ID' => $this->getType()->id(),
			'SETTINGS' => $this->settings(),
			...$this->getLabelFields($parameters->label),
		];

		$compatibilityMode = !method_exists(\CAllUserTypeEntity::class, 'syncColumnsAgent');
		$connection = Application::getConnection();

		$lockKey = 'crm_uf_add_' . $entityId;
		if ($compatibilityMode)
		{
			$connection->lock($lockKey, -1);
		}

		$userTypeEntity = new CUserTypeEntity();
		$id = $userTypeEntity->Add($fields);

		$this->cleanUfCache($entityId);
		if ($compatibilityMode)
		{
			$connection->unlock($lockKey);
		}

		if ($id === false)
		{
			return Result::failFromApplication();
		}


		return Result::success(id: $id, fields: $fields);
	}

	protected function addFieldToAdditionalOrFirstSectionIntoCurrentUserView(
		CreateUserFieldParameters $parameters,
		string $fieldName,
	): void
	{
		if (!Loader::includeModule('ui'))
		{
			return;
		}

		$extras = [
			'USER_ID' => $this->currentUserId,
			'CATEGORY_ID' => $parameters->categoryId,
		];

		$config = EntityEditorConfig::createWithCurrentScope($parameters->entityTypeId, $extras);
		$configuration = $config->getConfiguration(useDefaultIfNotExists: true);
		if ($configuration === null || !$configuration->hasSections())
		{
			return;
		}

		$element = $configuration->getElement($fieldName);
		if ($element !== null)
		{
			$element->setShowAlways(true);
		}
		else
		{
			$section = $configuration->getSection('additional') ?? $configuration->getSectionFirst();
			$section?->addElement(new Element(name: $fieldName, isShowAlways: true));
		}

		$configuration->save();

		MartaAIMarksRepository::fromEntityEditorConfig($configuration->entityEditorConfig())
			->mark(MarkTarget::Field, [$fieldName]);
	}

	protected function getLabelFields(string $label): array
	{
		$result = [];
		$fields = [
			'EDIT_FORM_LABEL',
			'LIST_COLUMN_LABEL',
			'LIST_FILTER_LABEL',
		];

		$lids = $this->getLanguageIds();
		foreach ($fields as $field)
		{
			foreach ($lids as $lid)
			{
				$result[$field][$lid] = $label;
			}
		}

		return $result;
	}

	protected function getLanguageIds(): array
	{
		$ids = [];

		$languageResult = CLanguage::GetList();
		while ($language = $languageResult->Fetch())
		{
			$ids[] = $language['LID'];
		}

		return $ids;
	}

	private function cleanUfCache(string $entityId): void
	{
		$filter = [
			'ENTITY_ID' => $entityId,
		];
		$sort = [];
		$cacheId = 'b_user_type' . md5(serialize($sort) . "." . serialize($filter));
		$GLOBALS['CACHE_MANAGER']->Clean($cacheId, 'b_user_field');

		$filter = [
			'ENTITY_ID' => $entityId,
			'LANG' => LANGUAGE_ID,
		];
		$cacheId = 'b_user_type' . md5(serialize($sort) . "." . serialize($filter));
		$GLOBALS['CACHE_MANAGER']->Clean($cacheId, 'b_user_field');

		$GLOBALS['CACHE_MANAGER']->CleanDir('b_user_field');
	}
}
