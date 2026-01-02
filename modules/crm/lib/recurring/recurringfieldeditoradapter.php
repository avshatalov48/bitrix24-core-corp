<?php

namespace Bitrix\Crm\Recurring;

use Bitrix\Crm\Field\Collection;
use Bitrix\Crm\Item;
use Bitrix\Crm\Recurring\Entity\Base;
use Bitrix\Crm\Recurring\Entity\Dynamic;
use Bitrix\Crm\Recurring\Entity\ParameterMapper\EntityForm;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\EditorAdapter\Normalizer\FieldValueNormalizer;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;
use CCrmOwnerType;

Container::getInstance()->getLocalization()->loadMessages();

final class RecurringFieldEditorAdapter
{
	private ?array $recurringData = null;
	private ?array $createCategoriesList = null;

	public const FIELD_RECURRING = 'RECURRING';

	public function __construct(
		private readonly Collection $fieldsCollection,
	)
	{
	}

	public function isUsed(Item $item): bool
	{
		return $item->hasField(Item::FIELD_NAME_RECURRING);
	}

	public function getEntityData(Item $item): array
	{
		return [
			Item::FIELD_NAME_RECURRING => $this->getRecurringData($item),
			Item::FIELD_NAME_IS_RECURRING => $this->getPreparedIsRecurringField($item),
		];
	}

	private function getRecurringData(Item $item): array
	{
		if ($this->recurringData === null)
		{
			$this->recurringData = $this->prepareData($item);
		}

		return $this->recurringData;
	}

	private function prepareData(Item $item): array
	{
		$entityTypeId = $item->getEntityTypeId();
		$factory = Container::getInstance()->getFactory($entityTypeId);

		if (!$item->isNew() && $item->getIsRecurring() && $factory?->isRecurringEnabled())
		{
			$recurringData = Manager::getList(
				['filter' => [
					'=ENTITY_TYPE_ID' => $entityTypeId,
					'=ITEM_ID' => $item->getId(),
				]],
				Manager::DYNAMIC,
			)->fetch();

			$recurringParams = [];
			if (is_array($recurringData))
			{
				$recurringParams = $recurringData['PARAMS'];
				if (isset($recurringParams['EXECUTION_TYPE']) && !isset($recurringParams['MODE']))
				{
					$recurringParams['MODE'] = $recurringParams['EXECUTION_TYPE'];
				}
				if ($recurringData['ACTIVE'] === 'N')
				{
					$recurringParams['MODE'] = Calculator::SALE_TYPE_NON_ACTIVE_DATE;
				}
				$recurringParams['SINGLE_INTERVAL_VALUE'] = (int)$recurringParams['SINGLE_INTERVAL_VALUE'];
				$singleDateBefore = null;
				if (CheckDateTime($recurringParams['SINGLE_DATE_BEFORE']))
				{
					$singleDateBefore = $recurringParams['SINGLE_DATE_BEFORE'];
				}
				$recurringParams['SINGLE_DATE_BEFORE']  = new Date($singleDateBefore);
				if (isset($recurringParams['REPEAT_TILL']) && !isset($recurringParams['MULTIPLE_TYPE_LIMIT']))
				{
					$recurringParams['MULTIPLE_TYPE_LIMIT'] = $recurringParams['REPEAT_TILL'];
				}
				$dateLimit = null;
				if (isset($recurringParams['END_DATE']) && !isset($recurringParams['MULTIPLE_DATE_LIMIT']))
				{
					$recurringParams['MULTIPLE_DATE_LIMIT'] = $recurringParams['END_DATE'];
				}
				if (CheckDateTime($recurringParams['MULTIPLE_DATE_LIMIT']))
				{
					$dateLimit = $recurringParams['MULTIPLE_DATE_LIMIT'];
				}
				$recurringParams['MULTIPLE_DATE_LIMIT']  = new Date($dateLimit);
				if (isset($recurringParams['LIMIT_REPEAT']) && !isset($recurringParams['MULTIPLE_TIMES_LIMIT']))
				{
					$recurringParams['MULTIPLE_TIMES_LIMIT'] = $recurringParams['LIMIT_REPEAT'];
				}
				$startDateValue = null;
				if (CheckDateTime($recurringParams['MULTIPLE_DATE_START']))
				{
					$startDateValue = $recurringParams['MULTIPLE_DATE_START'];
				}
				$recurringParams['MULTIPLE_DATE_START'] = new Date($startDateValue);
				$recurringParams['MULTIPLE_CUSTOM_INTERVAL_VALUE'] = (int)$recurringParams['MULTIPLE_CUSTOM_INTERVAL_VALUE'];
				$recurringParams['OFFSET_BEGINDATE_VALUE'] = (int)$recurringParams['OFFSET_BEGINDATE_VALUE'];
				$recurringParams['OFFSET_CLOSEDATE_VALUE'] = (int)$recurringParams['OFFSET_CLOSEDATE_VALUE'];

				$selectDateTypeFields = ['MULTIPLE_TYPE', 'MULTIPLE_CUSTOM_TYPE', 'SINGLE_TYPE', 'OFFSET_BEGINDATE_TYPE', 'OFFSET_CLOSEDATE_TYPE'];
				foreach ($selectDateTypeFields as $code)
				{
					if ((int)$recurringParams[$code] <= 0)
					{
						$recurringParams[$code] = Calculator::SALE_TYPE_DAY_OFFSET;
					}
				}

				if (isset($recurringData['CATEGORY_ID']) || (int)$recurringData['CATEGORY_ID'] > 0)
				{
					$recurringParams['CATEGORY_ID'] = $recurringData['CATEGORY_ID'];
				}
				else
				{
					$recurringParams['CATEGORY_ID'] = $item->getCategoryId();
				}

				$result = [];
				foreach ($recurringParams as $name => $value)
				{
					$changedName = "RECURRING[{$name}]";
					$result[$changedName] = $value;
				}

				$recurringParams = $result;
			}

			return $recurringParams;
		}

		$today = new Date();

		return [
			'RECURRING[MODE]' => Calculator::SALE_TYPE_NON_ACTIVE_DATE,
			'RECURRING[SINGLE_TYPE]' => Calculator::SALE_TYPE_DAY_OFFSET,
			'RECURRING[SINGLE_INTERVAL_VALUE]' => 0,
			'RECURRING[SINGLE_DATE_BEFORE]' => $today,
			'RECURRING[MULTIPLE_TYPE]' => Calculator::SALE_TYPE_DAY_OFFSET,
			'RECURRING[MULTIPLE_CUSTOM_TYPE]' => Calculator::SALE_TYPE_DAY_OFFSET,
			'RECURRING[MULTIPLE_CUSTOM_INTERVAL_VALUE]' => 1,
			'RECURRING[BEGINDATE_TYPE]' => Base::SETTED_FIELD_VALUE,
			'RECURRING[OFFSET_BEGINDATE_VALUE]' => 0,
			'RECURRING[OFFSET_BEGINDATE_TYPE]' => Calculator::SALE_TYPE_DAY_OFFSET,
			'RECURRING[CLOSEDATE_TYPE]' => Base::SETTED_FIELD_VALUE,
			'RECURRING[OFFSET_CLOSEDATE_VALUE]' => 0,
			'RECURRING[OFFSET_CLOSEDATE_TYPE]' => Calculator::SALE_TYPE_DAY_OFFSET,
			'RECURRING[MULTIPLE_DATE_START]' => $today,
			'RECURRING[MULTIPLE_DATE_LIMIT]' => $today,
			'RECURRING[MULTIPLE_TIMES_LIMIT]' => 1,
			'RECURRING[CATEGORY_ID]' => $item->getCategoryId(),
		];
	}

	protected function getPreparedIsRecurringField(Item $item): string
	{
		$fieldName = Item::FIELD_NAME_IS_RECURRING;
		$field = $this->fieldsCollection->getField($fieldName);
		if ($field)
		{
			$fieldType = $field->getType();
			$fieldValue = $item->get($fieldName);

			return (new FieldValueNormalizer($fieldType))->normalize($fieldValue);
		}

		return 'N';
	}

	public function prepareFieldByInfo(Item $item, array $field): array
	{
		if ($item->getIsRecurring())
		{
			$params = ['filter' => [
				'=ENTITY_TYPE_ID' => $item->getEntityTypeId(),
				'=ITEM_ID' => $item->getId(),
			]];
			$recurringData = Manager::getList(
				$params,
				Manager::DYNAMIC,
			)->fetch();

			if (!empty($recurringData['NEXT_EXECUTION']) && $recurringData['ACTIVE'] === 'Y')
			{
				$recurringViewText =  Loc::getMessage(
					'CRM_TYPE_RECURRING_FIELD_DATE_NEXT_EXECUTION',
					['#NEXT_DATE#' => $recurringData['NEXT_EXECUTION']],
				);
			}
			else
			{
				$recurringViewText = Loc::getMessage('CRM_TYPE_RECURRING_FIELD_NOTHING_SELECTED');
			}
		}
		elseif (!$item->isNew())
		{
			$params = [
				'filter' => [
					'=ENTITY_TYPE_ID' => $item->getEntityTypeId(),
					'=BASED_ID' => $item->getId(),
				],
				'select' => ['ITEM_ID'],
			];
			$recurringList = Manager::getList(
				$params,
				Manager::DYNAMIC,
			)->fetchAll();

			$recurringCount = count($recurringList);
			if ($recurringCount === 1)
			{
				$recurringViewText =  Loc::getMessage(
					'CRM_TYPE_RECURRING_FIELD_CREATED_FROM_CURRENT',
					['#RECURRING_ID#' => $recurringList[0]['ITEM_ID']],
				);
			}
			elseif ($recurringCount > 1)
			{
				$recurringLines = [];
				foreach ($recurringList as $recurringListItem)
				{
					$recurringLines[] = Loc::getMessage(
						'CRM_TYPE_SMART_INVOICE_FIELD_NUM_SIGN',
						['#SMART_INVOICE_ID#' => $recurringListItem['ITEM_ID']],
					);
				}

				if (!empty($recurringLines))
				{
					$recurringViewText =  Loc::getMessage(
						'CRM_TYPE_RECURRING_FIELD_CREATED_MANY_FROM_CURRENT',
						['#RECURRING_LIST#' => implode(', ', $recurringLines)],
					);
				}
			}
		}

		if (empty($recurringViewText) && empty($this->getAvailableCategories($item)))
		{
			$recurringViewText = Loc::getMessage('CRM_TYPE_RECURRING_FIELD_RESTRICTED');
		}

		if (empty($recurringViewText))
		{
			$recurringViewText = Loc::getMessage('CRM_TYPE_RECURRING_FIELD_NOTHING_SELECTED');
		}

		// @todo ****recurring need restrictions for dynamic types?
		$invoiceRecurringRestriction = null;
		if ($item->getEntityTypeId() === CCrmOwnerType::SmartInvoice)
		{
			if (!$this->isRecurringEnabled($item))
			{
				$invoiceRecurringRestriction = RestrictionManager::getInvoiceRecurringRestriction();
			}
		}

		$field['data']['view'] = [
			'text' => $recurringViewText,
		];
		$field['data']['fieldData'] = [
			'MULTIPLE_EXECUTION' => Manager::MULTIPLY_EXECUTION,
			'SINGLE_EXECUTION' => Manager::SINGLE_EXECUTION,
			'NON_ACTIVE' => Calculator::SALE_TYPE_NON_ACTIVE_DATE,
		];

		$field['data']['loaders'] = [
			'url' => UrlManager::getInstance()->create('crm.recurring.hint.get')->getUri(),
		];
		$field['data']['restrictScript'] =
			(!$this->isRecurringEnabled($item) && $invoiceRecurringRestriction !== null)
				? $invoiceRecurringRestriction->prepareInfoHelperScript()
				: ''
		;

		$field['elements'] = $this->prepareRecurringElements($item);
		$field['enableRecurring'] = true;

		return $field;
	}

	private function isRecurringEnabled(Item $item): bool
	{
		$entityTypeId = $item->getEntityTypeId();

		if  (!Manager::isAvailableEntityTypeId($entityTypeId))
		{
			return false;
		}

		return Container::getInstance()->getFactory($entityTypeId)?->isRecurringEnabled() ?? false;
	}

	private function getAvailableCategories(Item $item): array
	{
		if ($this->createCategoriesList === null)
		{
			$result = [];
			$categories = Container::getInstance()->getFactory($item->getEntityTypeId())?->getCategories();

			foreach ($categories as $category)
			{
				$result[] = [
					'NAME' => $category->getName(),
					'VALUE' => $category->getId(),
				];
			}

			$this->createCategoriesList = $result;
		}

		return $this->createCategoriesList;
	}

	private function prepareRecurringElements(Item $item): array
	{
		$isRecurringEnabled = $this->isRecurringEnabled($item);
		if (!$isRecurringEnabled)
			//|| (($this->arResult['READ_ONLY'] ?? null) === true)) // @todo
		{
			return [];
		}

		$categories = $this->getAvailableCategories($item);
		$editable = !empty($categories);

		$data = [
			[
				'name' => 'RECURRING[MODE]',
				'title' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_RECURRING'),
				'type' => 'list',
				'editable' => true,
				'enableAttributes' => false,
				'enabledMenu' => false,
				'data' => [
					'items' => [
						[
							'VALUE' => Calculator::SALE_TYPE_NON_ACTIVE_DATE,
							'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_NOT_REPEAT'),
						],
						[
							'VALUE' => Manager::MULTIPLY_EXECUTION,
							'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_MANY_TIMES'),
						],
						[
							'VALUE' => Manager::SINGLE_EXECUTION,
							'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_ONCE_TIME'),
						],
					],
				],
			],
			[
				'name' => 'SINGLE_PARAMS',
				'title' => Loc::getMessage(
					$item->getEntityTypeId() === CCrmOwnerType::SmartInvoice
						? 'CRM_TYPE_RECURRING_FIELD_SINGLE_TITLE_INVOICE'
						: 'CRM_TYPE_RECURRING_FIELD_SINGLE_TITLE'
				),
				'type' => 'recurring_single_row',
				'editable' => true,
				'enableAttributes' => false,
				'enabledMenu' => false,
				'data' => [
					'select' => [
						'name' => 'RECURRING[SINGLE_TYPE]',
						'items' => [
							[
								'VALUE' => Calculator::SALE_TYPE_DAY_OFFSET,
								'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_SINGLE_TYPE_DAY'),
							],
							[
								'VALUE' => Calculator::SALE_TYPE_WEEK_OFFSET,
								'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_SINGLE_TYPE_WEEK'),
							],
							[
								'VALUE' => Calculator::SALE_TYPE_MONTH_OFFSET,
								'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_SINGLE_TYPE_MONTH'),
							],
						],
					],
					'amount' => 'RECURRING[SINGLE_INTERVAL_VALUE]',
					'date' => 'RECURRING[SINGLE_DATE_BEFORE]',
				],
			],
			[
				'name' => 'MULTIPLE_PARAMS',
				'type' => 'recurring',
				'editable' => $editable,
				'transferable' => false,
				'enableAttributes' => false,
				'enableRecurring' => $isRecurringEnabled,
				'elements' => [
					[
						'name' => 'RECURRING[MULTIPLE_TYPE]',
						'title' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_MULTIPLE_PERIOD_TITLE'),
						'type' => 'list',
						'editable' => true,
						'enableAttributes' => false,
						'enabledMenu' => false,
						'data' => [
							'items' => [
								[
									'VALUE' => Calculator::SALE_TYPE_DAY_OFFSET,
									'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_EVERYDAY'),
								],
								[
									'VALUE' => Calculator::SALE_TYPE_WEEK_OFFSET,
									'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_EVERY_WEEK'),
								],
								[
									'VALUE' => Calculator::SALE_TYPE_MONTH_OFFSET,
									'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_EVERY_MONTH'),
								],
								[
									'VALUE' => Calculator::SALE_TYPE_YEAR_OFFSET,
									'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_EVERY_YEAR'),
								],
								[
									'VALUE' => Calculator::SALE_TYPE_CUSTOM_OFFSET,
									'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_CUSTOM_INTERVAL'),
								],
							],
						],
					],
					[
						'name' => 'MULTIPLE_CUSTOM',
						'title' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_CUSTOM_INTERVAL_TITLE'),
						'type' => 'recurring_custom_row',
						'editable' => true,
						'enableAttributes' => false,
						'enabledMenu' => false,
						'data' => [
							'select' => [
								'name' => 'RECURRING[MULTIPLE_CUSTOM_TYPE]',
								'items' => [
									[
										'VALUE' => Calculator::SALE_TYPE_DAY_OFFSET,
										'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_CUSTOM_DAY'),
									],
									[
										'VALUE' => Calculator::SALE_TYPE_WEEK_OFFSET,
										'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_CUSTOM_WEEK'),
									],
									[
										'VALUE' => Calculator::SALE_TYPE_MONTH_OFFSET,
										'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_CUSTOM_MONTH'),
									],
									[
										'VALUE' => Calculator::SALE_TYPE_YEAR_OFFSET,
										'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_CUSTOM_YEAR'),
									],
								],
							],
							'amount' => 'RECURRING[MULTIPLE_CUSTOM_INTERVAL_VALUE]',
						],
					],
				],
				'data' => [
					'view' => [],
					'fieldData' => [
						'MULTIPLE_EXECUTION' => Manager::MULTIPLY_EXECUTION,
						'SINGLE_EXECUTION' => Manager::SINGLE_EXECUTION,
						'MULTIPLE_CUSTOM' => Calculator::SALE_TYPE_CUSTOM_OFFSET,
					],
				],
			],
			[
				'name' => 'RECURRING[MULTIPLE_DATE_START]',
				'title' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_MULTIPLE_START_DATE_TITLE'),
				'type' => 'datetime',
				'editable' => true,
				'enableAttributes' => false,
				'enabledMenu' => false,
				'data' => ['enableTime' => false],
			],
			[
				'name' => 'MULTIPLE_LIMIT',
				'type' => 'recurring',
				'editable' => $editable,
				'transferable' => false,
				'enableAttributes' => false,
				'enableRecurring' => $isRecurringEnabled,
				'elements' => [
					[
						'name' => 'RECURRING[MULTIPLE_TYPE_LIMIT]',
						'title' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_MULTIPLE_FINAL_LIMIT_TITLE'),
						'type' => 'list',
						'editable' => true,
						'enableAttributes' => false,
						'enabledMenu' => false,
						'data' => [
							'items' => [
								[
									'VALUE' => Base::NO_LIMITED,
									'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_MULTIPLE_FINAL_NO_LIMIT'),
								],
								[
									'VALUE' => Base::LIMITED_BY_DATE,
									'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_MULTIPLE_FINAL_LIMIT_DATE'),
								],
								[
									'VALUE' => Base::LIMITED_BY_TIMES,
									'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_MULTIPLE_FINAL_LIMIT_TIMES'),
								],
							],
						],
					],
					[
						'name' => 'RECURRING[MULTIPLE_DATE_LIMIT]',
						'title' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_MULTIPLE_LIMIT_DATE_TITLE'),
						'type' => 'datetime',
						'editable' => true,
						'enabledMenu' => false,
						'enableAttributes' => false,
						'data' => ['enableTime' => false],
					],
					[
						'name' => 'RECURRING[MULTIPLE_TIMES_LIMIT]',
						'title' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_MULTIPLE_LIMIT_TIMES_TITLE'),
						'type' => 'number',
						'editable' => true,
						'enabledMenu' => false,
						'enableAttributes' => false,
					],
				],
				'data' => [
					'view' => [],
					'fieldData' => [
						'MULTIPLE_EXECUTION' => Manager::MULTIPLY_EXECUTION,
						'SINGLE_EXECUTION' => Manager::SINGLE_EXECUTION,
						'NO_LIMIT' => Base::NO_LIMITED,
						'LIMITED_BY_DATE' => Base::LIMITED_BY_DATE,
						'LIMITED_BY_TIMES' => Base::LIMITED_BY_TIMES,
					],
				],
			],
			[
				'name' => 'NEW_BEGINDATE',
				'type' => 'recurring',
				'editable' => $editable,
				'transferable' => false,
				'enableAttributes' => false,
				'enableRecurring' => $isRecurringEnabled,
				'elements' => [
					[
						'name' => 'RECURRING[BEGINDATE_TYPE]',
						'title' => Loc::getMessage(
							$item->getEntityTypeId() === CCrmOwnerType::SmartInvoice
								? 'CRM_TYPE_RECURRING_FIELD_NEW_BEGINDATE_VALUE_TITLE_INVOICE'
								: 'CRM_TYPE_RECURRING_FIELD_NEW_BEGINDATE_VALUE_TITLE'
						),
						'type' => 'list',
						'editable' => true,
						'enableAttributes' => false,
						'enabledMenu' => false,
						'data' => [
							'items' => [
								[
									'VALUE' => Base::SETTED_FIELD_VALUE,
									'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_NEW_VALUE_CURRENT_FIELD'),
								],
								[
									'VALUE' => Base::CALCULATED_FIELD_VALUE,
									'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_NEW_VALUE_DATE_CREATION_OFFSET'),
								],
							],
						],
					],
					[
						'name' => 'OFFSET_BEGINDATE',
						'title' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_DATE_CREATION_BEGINDATE_OFFSET_TITLE'),
						'type' => 'recurring_custom_row',
						'editable' => true,
						'enableAttributes' => false,
						'enabledMenu' => false,
						'data' => [
							'select' => [
								'name' => 'RECURRING[OFFSET_BEGINDATE_TYPE]',
								'items' => [
									[
										'VALUE' => Calculator::SALE_TYPE_DAY_OFFSET,
										'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_CUSTOM_DAY'),
									],
									[
										'VALUE' => Calculator::SALE_TYPE_WEEK_OFFSET,
										'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_CUSTOM_WEEK'),
									],
									[
										'VALUE' => Calculator::SALE_TYPE_MONTH_OFFSET,
										'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_CUSTOM_MONTH'),
									],
								],
							],
							'amount' => 'RECURRING[OFFSET_BEGINDATE_VALUE]',
						],
					],
				],
				'data' => [
					'view' => [],
					'fieldData' => [
						'SETTED_FIELD_VALUE' => Base::SETTED_FIELD_VALUE,
						'CALCULATED_FIELD_VALUE' => Base::CALCULATED_FIELD_VALUE,
						'MULTIPLE_EXECUTION' => Manager::MULTIPLY_EXECUTION,
						'SINGLE_EXECUTION' => Manager::SINGLE_EXECUTION,
					],
				],
			],
			[
				'name' => 'NEW_CLOSEDATE',
				'type' => 'recurring',
				'editable' => $editable,
				'transferable' => false,
				'enableAttributes' => false,
				'enableRecurring' => $isRecurringEnabled,
				'elements' => [
					[
						'name' => 'RECURRING[CLOSEDATE_TYPE]',
						'title' => Loc::getMessage(
							$item->getEntityTypeId() === CCrmOwnerType::SmartInvoice
								? 'CRM_TYPE_RECURRING_FIELD_NEW_CLOSEDATE_VALUE_TITLE_INVOICE'
								: 'CRM_TYPE_RECURRING_FIELD_NEW_CLOSEDATE_VALUE_TITLE'
						),
						'type' => 'list',
						'editable' => true,
						'enableAttributes' => false,
						'enabledMenu' => false,
						'data' => [
							'items' => [
								[
									'VALUE' => Base::SETTED_FIELD_VALUE,
									'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_NEW_VALUE_CURRENT_FIELD'),
								],
								[
									'VALUE' => Base::CALCULATED_FIELD_VALUE,
									'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_NEW_VALUE_DATE_CREATION_OFFSET'),
								],
							],
						],
					],
					[
						'name' => 'OFFSET_CLOSEDATE',
						'title' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_DATE_CREATION_CLOSEDATE_OFFSET_TITLE'),
						'type' => 'recurring_custom_row',
						'editable' => true,
						'enableAttributes' => false,
						'enabledMenu' => false,
						'data' => [
							'select' => [
								'name' => 'RECURRING[OFFSET_CLOSEDATE_TYPE]',
								'items' => [
									[
										'VALUE' => Calculator::SALE_TYPE_DAY_OFFSET,
										'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_CUSTOM_DAY'),
									],
									[
										'VALUE' => Calculator::SALE_TYPE_WEEK_OFFSET,
										'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_CUSTOM_WEEK'),
									],
									[
										'VALUE' => Calculator::SALE_TYPE_MONTH_OFFSET,
										'NAME' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_CUSTOM_MONTH'),
									],
								],
							],
							'amount' => 'RECURRING[OFFSET_CLOSEDATE_VALUE]',
						],
					],
				],
				'data' => [
					'view' => [],
					'fieldData' => [
						'SETTED_FIELD_VALUE' => Base::SETTED_FIELD_VALUE,
						'CALCULATED_FIELD_VALUE' => Base::CALCULATED_FIELD_VALUE,
						'MULTIPLE_EXECUTION' => Manager::MULTIPLY_EXECUTION,
						'SINGLE_EXECUTION' => Manager::SINGLE_EXECUTION,
					],
				],
			],
		];

		$factory = Container::getInstance()->getFactory($item->getEntityTypeId());
		if (!empty($categories) && $factory?->isCategoriesEnabled())
		{
			$data[] = [
				'name' => 'RECURRING[CATEGORY_ID]',
				'title' => Loc::getMessage('CRM_TYPE_RECURRING_FIELD_CATEGORY_RECURRING'),
				'type' => 'list',
				'editable' => true,
				'enabledMenu' => false,
				'enableAttributes' => false,
				'data' => [
					'items' => $categories,
				],
			];
		}

		return $data;
	}

	public function saveRecurringData(Item $item, array $recurringData): Result
	{
		$result = $this->getRecurringDataFromEmbeddedEditor($item, $recurringData);

		if (!$result->isSuccess())
		{
			return $result;
		}

		$entityTypeId = $item->getEntityTypeId();
		$recurring = Manager::getList(
			[
				'filter' => [
					'=ENTITY_TYPE_ID' => $entityTypeId,
					'=ITEM_ID' => $item->getId(),
				],
				'select' => ['ID'],
			],
			Manager::DYNAMIC,
		)->fetch();

		$recurringFields = $result->getData()['recurringFields'] ?? [];
		$recurringFields['ENTITY_TYPE_ID'] = $entityTypeId;

		if (is_array($recurring) && !$item->isNew())
		{
			return Manager::update($recurring['ID'], $recurringFields,Manager::DYNAMIC);
		}

		$fields = $item->getData();

		return Manager::createEntity($fields, $recurringFields, Manager::DYNAMIC);
	}

	private function getRecurringDataFromEmbeddedEditor(Item $item, array $recurringData): Result
	{
		$result = new Result();

		if (
			$recurringData['MODE'] === Calculator::SALE_TYPE_NON_ACTIVE_DATE
			|| (
				(int)$recurringData['MODE'] === Manager::MULTIPLY_EXECUTION
				&& (int)$recurringData['MULTIPLE_TYPE'] === Calculator::SALE_TYPE_CUSTOM_OFFSET
				&& (int)$recurringData['MULTIPLE_CUSTOM_INTERVAL_VALUE'] <= 0
			)
		)
		{
			$recurringFields = [
				'ACTIVE' => 'N',
				'NEXT_EXECUTION' => null,
				'PARAMS' => $recurringData,
			];
		}
		else
		{
			$startDate = null;
			$recurringFields = [
				'CATEGORY_ID' => $item->getCategoryId(),
				'PARAMS' => $recurringData,
			];

			if ((int)$recurringData['MODE'] === Manager::SINGLE_EXECUTION)
			{
				$singleCalculationFields = [
					EntityForm::FIELD_MODE_NAME,
					EntityForm::FIELD_SINGLE_TYPE_NAME,
					EntityForm::FIELD_SINGLE_INTERVAL_NAME,
				];

				$limitParams = array_intersect_key($recurringData, array_flip($singleCalculationFields));
				$limitMapper = EntityForm::getInstance();
				$limitMapper->fillMap($limitParams);

				$startDateValue = null;
				if (CheckDateTime($recurringData['SINGLE_DATE_BEFORE']))
				{
					$startDateValue = $recurringData['SINGLE_DATE_BEFORE'];
				}
				$startDate = new Date($startDateValue);
				$recurringFields['START_DATE'] = $startDate;
				$recurringFields['IS_LIMIT'] = Base::LIMITED_BY_DATE;

				$instance = Calculator::getInstance();
				$instance->setStartDate($startDate);
				$instance->setParams($limitMapper->getPreparedMap());

				$recurringFields['LIMIT_DATE'] = $instance->calculateDate();
			}
			elseif ((int)$recurringData['MODE'] === Manager::MULTIPLY_EXECUTION)
			{
				$startDateValue = null;
				if (CheckDateTime($recurringData['MULTIPLE_DATE_START']))
				{
					$startDateValue = $recurringData['MULTIPLE_DATE_START'];
				}
				$startDate = new Date($startDateValue);
				$recurringFields['START_DATE'] = $startDate;
				$recurringFields['IS_LIMIT'] = Base::NO_LIMITED;

				if ($recurringData['MULTIPLE_TYPE_LIMIT'] === Base::LIMITED_BY_TIMES)
				{
					$recurringFields['IS_LIMIT'] = Base::LIMITED_BY_TIMES;
					$recurringFields['LIMIT_REPEAT'] = (int)$recurringData['MULTIPLE_TIMES_LIMIT'];
				}
				elseif ($recurringData['MULTIPLE_TYPE_LIMIT'] === Base::LIMITED_BY_DATE)
				{
					$recurringFields['IS_LIMIT'] = Base::LIMITED_BY_DATE;
					$recurringFields['LIMIT_DATE'] = new Date($recurringData['MULTIPLE_DATE_LIMIT']);
				}
			}

			$today = new Date();

			$nextDate = Dynamic::getNextDate($recurringData, $startDate);
			if ($nextDate->getTimestamp() < $today->getTimestamp())
			{
				return $result->addError(new Error(Loc::getMessage('CRM_RECURRING_EDITOR_EDAPTER_RECURRING_DATE_START_ERROR')));
			}
		}

		return $result->setData(['recurringFields' => $recurringFields]);
	}

	// @todo for other dynamic types recurring
	/*	private function isCategoriesEnabled(): bool
	{
		$factory = Container::getInstance()->getFactory($this->item->getEntityTypeId());

		return $factory && $factory->isCategoriesEnabled();
	}

	private function canAddItems(): bool
	{
		return Container::getInstance()->getUserPermissions()->entityType()->canAddItems($this->item->getEntityTypeId());
	}*/
}
