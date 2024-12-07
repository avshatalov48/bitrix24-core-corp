<?php

namespace Bitrix\Crm\Integration\Catalog\Contractor;

use Bitrix\Catalog\ContractorTable;
use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Catalog\AgentContractTable;
use Bitrix\Crm\EntityPreset;
use Bitrix\Main\Application;
use Bitrix\Main\DB\PgsqlConnection;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Main\Config\Option;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Item;
use Bitrix\Main\Update\Stepper;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Multifield\Collection;
use Bitrix\Crm\Multifield\Type\Phone;
use Bitrix\Crm\Multifield\Type\Email;
use Bitrix\Crm\Multifield;
use Bitrix\Main\ORM\Query;
use Bitrix\Crm\Service\Operation\Add;
use Bitrix\Main\DB\SqlExpression;

if (
	!Loader::includeModule('catalog')
)
{
	class Converter extends Stepper
	{
		public function execute(&$option): bool
		{
			return self::FINISH_EXECUTION;
		}
	}

	return;
}

/**
 * Class Converter
 *
 * @package Bitrix\Crm\Integration\Catalog\Contractor
 */
class Converter extends Stepper implements \Bitrix\Catalog\v2\Contractor\IConverter
{
	private const COMPLETED_OPTION_NAME = 'catalog_contractors_converted';
	private const STEP_LIMIT = 50;

	protected static $moduleId = 'crm';

	private static array $categoryIds = [
		\CCrmOwnerType::Contact => null,
		\CCrmOwnerType::Company => null,
	];

	/**
	 * @inheritDoc
	 */
	public static function getTitle()
	{
		return Loc::getMessage('CONTRACTORS_CONVERTER_STEPPER_TITLE');
	}

	/**
	 * @inheritDoc
	 */
	public function execute(&$option): bool
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();

		if (
			self::isCompleted()
			|| !Loader::includeModule('catalog')
		)
		{
			return self::FINISH_EXECUTION;
		}

		if (empty($option))
		{
			$option['steps'] = 0;
			$option['count'] = self::getUnprocessedContractorsCnt();
		}

		self::loadCategoryIds();

		foreach (self::getUnprocessedContractors() as $contractor)
		{
			$contractorId = (int)$contractor['ID'];

			Application::getConnection()->query(
				$sqlHelper->prepareMerge(
					'b_crm_contractor_conversion',
					['CONTRACTOR_ID'],
					[
						'CONTRACTOR_ID' => $contractorId,
					],
					[
						'ENTITY_ID' => null,
						'ENTITY_TYPE_ID' => null,
						'SUCCESS' => null,
						'ERRORS' => null,
					],
				)[0]
			);

			Application::getConnection()->startTransaction();
			$result = self::processContractor($contractor);
			if ($result->isSuccess())
			{
				Application::getConnection()->commitTransaction();
			}
			else
			{
				Application::getConnection()->rollbackTransaction();
			}

			$errors = (
				$result->getErrorMessages()
				|| $result->getWarnings()
			) ? sprintf(
				'%s;%s',
				self::getMessage($result->getErrorMessages()),
				self::getMessage($result->getWarnings())
			) : null;

			Application::getConnection()->query(
				sprintf(
					'
						UPDATE b_crm_contractor_conversion
						SET
							ENTITY_ID = %s,
							ENTITY_TYPE_ID = %s,
							SUCCESS = %s,
							ERRORS = %s
						WHERE
							CONTRACTOR_ID = %d
					',
					($result->getEntityId() ? $sqlHelper->convertToDbInteger($result->getEntityId()) : 'NULL'),
					($result->getEntityTypeId() ? $sqlHelper->convertToDbInteger($result->getEntityTypeId()) : 'NULL'),
					$sqlHelper->convertToDbString($result->isSuccess() ? 'Y' : 'N'),
					($errors ? $sqlHelper->convertToDbString($errors) : 'NULL'),
					$sqlHelper->convertToDbInteger($contractorId)
				)
			);

			$option['steps']++;
		}

		if (self::getUnprocessedContractorsCnt() > 0)
		{
			return self::CONTINUE_EXECUTION;
		}

		self::setCompleted();

		return self::FINISH_EXECUTION;
	}

	/**
	 * @param $contractor
	 * @return ConversionResult
	 */
	private static function processContractor($contractor): ConversionResult
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();

		$result = new ConversionResult();

		$entityTypeId = null;
		if ($contractor['PERSON_TYPE'] === ContractorTable::TYPE_INDIVIDUAL)
		{
			$entityTypeId = \CCrmOwnerType::Contact;
		}
		elseif ($contractor['PERSON_TYPE'] === ContractorTable::TYPE_COMPANY)
		{
			$entityTypeId = \CCrmOwnerType::Company;
		}

		if (!$entityTypeId)
		{
			$result->addError(new Error(sprintf('Unexpected person type - %s', $contractor['PERSON_TYPE'])));
			return $result;
		}

		$addContractorResult = self::addContractorEntity($entityTypeId, $contractor);
		if (!$addContractorResult->isSuccess())
		{
			$result->addErrors($addContractorResult->getErrors());
			return $result;
		}
		$entityId = (int)$addContractorResult->getId();

		if (
			$entityTypeId === \CCrmOwnerType::Company
			&& !empty($contractor['PERSON_NAME'])
		)
		{
			$addContact = self::addCompanyContact($entityId, $contractor['PERSON_NAME'], $contractor);
			if (!$addContact->isSuccess())
			{
				$result->addWarnings($addContact->getErrorMessages());
			}
		}

		$requisitePreset = self::getRequisitePreset($entityTypeId);
		if ($requisitePreset)
		{
			$createRequisiteResult = self::addRequisite(
				$entityTypeId,
				$entityId,
				$contractor,
				$requisitePreset
			);
			if (!$createRequisiteResult->isSuccess())
			{
				$result->addWarnings($createRequisiteResult->getErrorMessages());
			}
		}

		Application::getConnection()->query(
			$sqlHelper->prepareMergeSelect(
				'b_crm_store_document_contractor',
				['DOCUMENT_ID', 'ENTITY_ID', 'ENTITY_TYPE_ID'],
				['DOCUMENT_ID', 'ENTITY_ID', 'ENTITY_TYPE_ID'],
				sprintf(
					'SELECT csd.ID, %d, %d FROM %s csd WHERE csd.DOC_TYPE = %s AND CONTRACTOR_ID = %d',
					$sqlHelper->convertToDbInteger($entityId),
					$sqlHelper->convertToDbInteger($entityTypeId),
					StoreDocumentTable::getTableName(),
					$sqlHelper->convertToDbString(StoreDocumentTable::TYPE_ARRIVAL),
					$sqlHelper->convertToDbInteger((int)$contractor['ID'])
				),
				[
					'ENTITY_ID' => $sqlHelper->convertToDbInteger($entityId),
					'ENTITY_TYPE_ID' => $sqlHelper->convertToDbInteger($entityTypeId),
				]
			)
		);

		Application::getConnection()->query(
			$sqlHelper->prepareMergeSelect(
				'b_crm_agent_contract_contractor',
				['CONTRACT_ID', 'ENTITY_ID', 'ENTITY_TYPE_ID'],
				['CONTRACT_ID', 'ENTITY_ID', 'ENTITY_TYPE_ID'],
				sprintf(
					'SELECT cac.ID, %d, %d FROM %s cac WHERE CONTRACTOR_ID = %d',
					$sqlHelper->convertToDbInteger($entityId),
					$sqlHelper->convertToDbInteger($entityTypeId),
					AgentContractTable::getTableName(),
					$sqlHelper->convertToDbInteger((int)$contractor['ID'])
				),
				[
					'ENTITY_ID' => $sqlHelper->convertToDbInteger($entityId),
					'ENTITY_TYPE_ID' => $sqlHelper->convertToDbInteger($entityTypeId),
				]
			)
		);

		return $result
			->setEntityTypeId($entityTypeId)
			->setEntityId($entityId)
		;
	}

	/**
	 * @param array $contractor
	 * @return Result
	 */
	private static function addContractorEntity(int $entityTypeId, array $contractor): Result
	{
		$result = new Result();

		/** @var Factory\Company $factory */
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			$result->addError(new Error('Entity factory not found'));
			return $result;
		}

		$categoryId = self::$categoryIds[$entityTypeId] ?? null;
		if (!$categoryId)
		{
			$result->addError(new Error('Category id not found'));
			return $result;
		}

		$multiFields = new Collection();
		if (!empty($contractor['PHONE']))
		{
			$multiFields->add(
				(new Multifield\Value())
					->setTypeId(Phone::ID)
					->setValueType(Phone::VALUE_TYPE_WORK)
					->setValue($contractor['PHONE'])
			);
		}

		if (check_email($contractor['EMAIL']))
		{
			$multiFields->add(
				(new Multifield\Value())
					->setTypeId(Email::ID)
					->setValueType(Email::VALUE_TYPE_WORK)
					->setValue($contractor['EMAIL'])
			);
		}

		$createdBy = self::getCreatedBy($contractor);
		$modifiedBy = self::getModifiedBy($contractor);

		$item = $factory->createItem()
			->setCategoryId($categoryId)
			->setAssignedById($createdBy)
			->set(Item::FIELD_NAME_FM, $multiFields)
			->set('CREATED_BY_ID', $createdBy)
			->set('MODIFY_BY_ID', $modifiedBy)
			->set('DATE_CREATE', self::getDateCreate($contractor))
			->set('DATE_MODIFY', self::getDateModify($contractor))
		;

		if ($item instanceof Item\Company)
		{
			$item->setTitle($contractor['COMPANY']);
		}
		elseif ($item instanceof Item\Contact)
		{
			$item->setName($contractor['PERSON_NAME']);
		}

		$addOperation = $factory->getAddOperation($item);

		self::configureAddOperation($addOperation);

		return $addOperation->launch();
	}

	/**
	 * @param int $companyId
	 * @param string $name
	 * @param array $contractor
	 * @return Result
	 */
	private static function addCompanyContact(int $companyId, string $name, array $contractor): Result
	{
		$result = new Result();

		/** @var Factory\Contact $factory */
		$factory = Container::getInstance()->getFactory(\CCrmOwnerType::Contact);
		if (!$factory)
		{
			$result->addError(new Error('Contact factory not found'));
			return $result;
		}

		$categoryId = self::$categoryIds[\CCrmOwnerType::Contact] ?? null;
		if (!$categoryId)
		{
			$result->addError(new Error('Contact category id not found'));
			return $result;
		}

		$createdBy = self::getCreatedBy($contractor);
		$modifiedBy = self::getModifiedBy($contractor);

		$addOperation = $factory->getAddOperation(
			$factory->createItem()
				->setName($name)
				->setCategoryId($categoryId)
				->setAssignedById($createdBy)
				->set('CREATED_BY_ID', $createdBy)
				->set('MODIFY_BY_ID', $modifiedBy)
				->set('DATE_CREATE', self::getDateCreate($contractor))
				->set('DATE_MODIFY', self::getDateModify($contractor))
		);

		self::configureAddOperation($addOperation);

		$result = $addOperation->launch();
		if (!$result->isSuccess())
		{
			return $result;
		}

		$bindContactToCompanyResult = Container::getInstance()->getRelationManager()->bindItems(
			new ItemIdentifier(
				\CCrmOwnerType::Company,
				$companyId
			),
			new ItemIdentifier(
				\CCrmOwnerType::Contact,
				(int)$result->getId()
			)
		);

		if (!$bindContactToCompanyResult->isSuccess())
		{
			$result->addErrors($bindContactToCompanyResult->getErrors());
			return $result;
		}

		return $result;
	}

	/**
	 * @param array $contractor
	 * @return int|null
	 */
	private static function getCreatedBy(array $contractor): ?int
	{
		if (isset($contractor['CREATED_BY']) && (int)$contractor['CREATED_BY'] > 0)
		{
			return (int)$contractor['CREATED_BY'];
		}

		return CurrentUser::get()->getId();
	}

	/**
	 * @param array $contractor
	 * @return DateTime|mixed
	 */
	private static function getDateCreate(array $contractor)
	{
		return $contractor['DATE_CREATE'] ?: new DateTime();
	}

	/**
	 * @param array $contractor
	 * @return int|null
	 */
	private static function getModifiedBy(array $contractor): ?int
	{
		if (isset($contractor['MODIFIED_BY']) && (int)$contractor['MODIFIED_BY'] > 0)
		{
			return (int)$contractor['MODIFIED_BY'];
		}

		return CurrentUser::get()->getId();
	}

	/**
	 * @param array $contractor
	 * @return DateTime|mixed
	 */
	private static function getDateModify(array $contractor)
	{
		return $contractor['DATE_MODIFY'] ?: new DateTime();
	}

	/**
	 * @param Add $addOperation
	 */
	private static function configureAddOperation(Add $addOperation): void
	{
		$addOperation
			->disableAllChecks()
			->disableBeforeSaveActions()
			->disableAfterSaveActions()
			->disableAutomation()
			->disableFieldProcession()
			->disableBizProc()
			->disableSaveToHistory()
			->disableSaveToTimeline()
			->disableActivitiesAutocompletion()
		;
	}

	private static function loadCategoryIds(): void
	{
		foreach ([\CCrmOwnerType::Contact, \CCrmOwnerType::Company] as $entityTypeId)
		{
			$category = CategoryRepository::getOrCreateByEntityTypeId($entityTypeId);
			if ($category)
			{
				self::$categoryIds[$entityTypeId] = $category->getId();
			}
		}
	}

	/**
	 * @param int $entityTypeId
	 * @param int $entityId
	 * @param array $contractor
	 * @param array $preset
	 * @return Result
	 */
	private static function addRequisite(
		int $entityTypeId,
		int $entityId,
		array $contractor,
		array $preset
	): Result
	{
		$fields = [];

		if ($contractor['PERSON_TYPE'] === ContractorTable::TYPE_COMPANY)
		{
			if (!empty($contractor['INN']))
			{
				$fields['RQ_INN'] = $contractor['INN'];
			}
			if (!empty($contractor['KPP']))
			{
				$fields['RQ_KPP'] = $contractor['KPP'];
			}
		}

		$addressFields = [];
		if (!empty($contractor['ADDRESS']))
		{
			$addressFields['ADDRESS_2'] = $contractor['ADDRESS'];
		}
		if (!empty($contractor['POST_INDEX']))
		{
			$addressFields['POSTAL_CODE'] = $contractor['POST_INDEX'];
		}
		if (!empty($addressFields))
		{
			$addressType = $contractor['PERSON_TYPE'] === ContractorTable::TYPE_COMPANY
				? EntityAddressType::Registered
				: EntityAddressType::Primary
			;

			$fields['RQ_ADDR'][$addressType] = $addressFields;
		}

		if (empty($fields))
		{
			return new Result();
		}

		return EntityRequisite::getSingleInstance()->add(
			array_merge(
				[
					'ENTITY_TYPE_ID' => $entityTypeId,
					'ENTITY_ID' => $entityId,
					'PRESET_ID' => $preset['ID'],
					'DATE_CREATE' => self::getDateCreate($contractor),
					'CREATED_BY_ID' => self::getCreatedBy($contractor),
					'NAME' => $preset['NAME'],
				],
				$fields
			),
			[
				'ALLOW_SET_SYSTEM_FIELDS' => true,
				'DISABLE_REQUIRED_USER_FIELD_CHECK' => true,
			]
		);
	}

	/**
	 * @param int $entityTypeId
	 * @return array|null
	 */
	private static function getRequisitePreset(int $entityTypeId): ?array
	{
		$presetId = EntityRequisite::getDefaultPresetId($entityTypeId);
		if (!$presetId)
		{
			return null;
		}

		$preset = EntityPreset::getSingleInstance()->getById($presetId);
		if (!is_array($preset))
		{
			return null;
		}

		return $preset;
	}

	/**
	 * @return bool
	 */
	public static function isCompleted(): bool
	{
		return Option::get('crm', self::COMPLETED_OPTION_NAME, 'N') === 'Y';
	}

	private static function setCompleted(): void
	{
		Option::set('crm', self::COMPLETED_OPTION_NAME, 'Y');
	}

	/**
	 * @return Query\Query
	 */
	private static function getUnprocessedContractorsQuery(): Query\Query
	{
		$query = ContractorTable::query();

		return $query
			->whereNotExists(
				new SqlExpression(
					'
						SELECT 1
						FROM b_crm_contractor_conversion cc_conv
						WHERE
							cc_conv.CONTRACTOR_ID = ?#.ID
					',
					$query->getInitAlias()
				)
			)
		;
	}

	/**
	 * @return Query\Result
	 */
	private static function getUnprocessedContractors(): Query\Result
	{
		return self::getUnprocessedContractorsQuery()
			->setSelect(['*'])
			->setLimit(self::STEP_LIMIT)
			->exec()
		;
	}

	/**
	 * @return int
	 */
	private static function getUnprocessedContractorsCnt(): int
	{
		$row = self::getUnprocessedContractorsQuery()
			->setSelect(['CNT'])
			->registerRuntimeField('CNT', new ExpressionField('CNT', 'COUNT(1)'))
			->exec()
			->fetch()
		;

		return isset($row['CNT']) ? (int)$row['CNT'] : 0;
	}

	/**
	 * @param array $errorMessages
	 * @return string
	 */
	private static function getMessage(array $errorMessages): string
	{
		return implode(',', $errorMessages);
	}

	public static function isMigrated(): bool
	{
		return self::isCompleted();
	}

	public static function runMigration(): void
	{
		self::bind(30);
	}

	public static function showMigrationProgress(): void
	{
		echo self::getHtml();
	}
}
