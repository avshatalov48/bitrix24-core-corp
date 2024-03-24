<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Numerator\Numerator;
use Bitrix\Main\Loader;
use Bitrix\DocumentGenerator\DataProviderManager;

Loc::loadMessages(__FILE__);

/**
 */
class CrmDocumentNumeratorsListComponent extends CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{
	protected $gridId = 'CRM_NUMERATORS_GRID';
	protected $filterId = 'CRM_NUMERATORS_LIST';

	protected function getProviders()
	{
		static $providers = null;
		if ($providers === null)
		{
			$providers = DataProviderManager::getInstance()->getList(['filter' => ['MODULE' => 'crm']]);
		}
		return $providers;
	}

	/** @inheritdoc */
	public function executeComponent()
	{
		if (!Loader::includeModule('crm'))
		{
			ShowError(Loc::getMessage('CRM_MODULE_NOT_INSTALLED'));
			return;
		}
		if (!Loader::includeModule('documentgenerator'))
		{
			ShowError(Loc::getMessage('DOCUMENTGENERATOR_MODULE_NOT_INSTALLED'));
			return;
		}
		if (!CCrmPerms::IsAccessEnabled())
		{
			ShowError(Loc::getMessage('CRM_PERMISSION_DENIED'));
			return;
		}
		$this->arResult['CAN_EDIT'] = $this->arResult['CAN_DELETE'] = true;
		$userId = isset($this->arParams['USER_ID']) ? intval($this->arParams['USER_ID']) : 0;
		if ($userId <= 0)
		{
			$userId = CCrmPerms::GetCurrentUserID();
		}
		$this->arResult['USER_ID'] = $userId;

		$this->arResult["MESSAGES"] = [];
		$this->arResult['GRID_ID'] = $this->gridId;
		$this->arResult['FILTER_ID'] = $this->filterId;
		$this->processGridActions($this->arResult['GRID_ID'], $userId);

		$this->arResult['FORM_ID'] = isset($this->arParams['FORM_ID']) ? $this->arParams['FORM_ID'] : '';
		$this->arResult['TAB_ID'] = isset($this->arParams['TAB_ID']) ? $this->arParams['TAB_ID'] : '';

		$this->arResult['HEADERS'] = [
			['id' => 'ID', 'name' => Loc::getMessage('CRM_COLUMN_NUMERATOR_LIST_ID'), 'sort' => 'ID', 'default' => false, 'editable' => false],
			['id' => 'NAME', 'name' => Loc::getMessage('CRM_COLUMN_NUMERATOR_LIST_NAME'), 'sort' => 'NAME', 'default' => true, 'editable' => true, 'params' => ['size' => 200]],
			['id' => 'TEMPLATE_NAME', 'name' => Loc::getMessage('CRM_COLUMN_NUMERATOR_LIST_TEMPLATE_NAME'), 'default' => true, 'editable' => false,],
			['id' => 'TEMPLATE', 'name' => Loc::getMessage('CRM_COLUMN_NUMERATOR_LIST_TEMPLATE'), 'default' => true, 'editable' => false],
		];

		$gridOptions = new CCrmGridOptions($this->arResult['GRID_ID']);
		$gridSorting = $gridOptions->GetSorting(
			[
				'sort' => ['ID' => 'asc'],
				'vars' => ['by' => 'by', 'order' => 'order'],
			]
		);
		$this->arResult['SORT'] = $gridSorting['sort'];
		$this->arResult['SORT_VARS'] = $gridSorting['vars'];

		$items = [];
		$filterEntities = [];
		foreach ($this->getProviders() as $provider)
		{
			$filterEntities[$provider['CLASS']] = $provider['NAME'];
		}

		$this->arResult['FILTER'] =
			[
				['id' => "CRM_ENTITIES", 'name' => GetMessage('CRM_NUMERATOR_LIST_FILTER_ENTITIES'), 'type' => 'list', 'items' => $filterEntities, 'params' => ['multiple' => 'Y'], 'default' => true],
			];
		$numerators = $this->getFilteredNumerators();

		$count = 0;
		foreach ($numerators as $index => $numerator)
		{
			$fields['~ID'] = $numerator['ID'];
			$fields['ID'] = intval($numerator['ID']);

			$fields['~NAME'] = $numerator['NAME'];
			$fields['NAME'] = htmlspecialcharsbx($numerator['NAME']);

			$fields['~TYPE'] = $numerator['TYPE'];
			$fields['TYPE'] = htmlspecialcharsbx($numerator['TYPE']);

			$fields['~TEMPLATE_NAME'] = $numerator['TEMPLATE_NAME'];
			$fields['TEMPLATE_NAME'] = htmlspecialcharsbx($numerator['TEMPLATE_NAME']);

			$fields['~TEMPLATE'] = $numerator['TEMPLATE'];
			$fields['TEMPLATE'] = htmlspecialcharsbx($numerator['TEMPLATE']);

			$fields['CAN_EDIT'] = $fields['CAN_DELETE'] = true;
			$fields['PATH_TO_EDIT'] = $fields['PATH_TO_DELETE'] = '';

			$items[] = $fields;
			$count++;
		}
		$this->arResult['ROWS_COUNT'] = $count;

		$this->arResult['ITEMS'] = &$items;

		$this->includeComponentTemplate();
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getFilteredNumerators()
	{
		$numResults = [];
		$filterOptions = new \Bitrix\Main\UI\Filter\Options($this->arResult['FILTER_ID']);
		$gridFilter = $filterOptions->getFilter($this->arResult['FILTER']);

		$templateNameExpression = \Bitrix\Crm\DbHelper::getSqlByDbType(
			"GROUP_CONCAT(`template`.`NAME` SEPARATOR ', ')",
			"STRING_AGG(template.NAME, ', ')"
		);
		$numeratorsBaseQuery = \Bitrix\DocumentGenerator\Model\TemplateTable::query()
			->setCustomBaseTableAlias('template')
			->registerRuntimeField('NUMERATOR', new \Bitrix\Main\Entity\ReferenceField('NUMERATOR', '\Bitrix\Main\Numerator\Model\Numerator', ['=this.NUMERATOR_ID' => 'ref.ID']))
			->addSelect(new \Bitrix\Main\Entity\ExpressionField('N_NUMERATOR_NAME', 'template_numerator.NAME'))
			->addSelect(new \Bitrix\Main\Entity\ExpressionField('N_NUMERATOR_TEMPLATE', 'template_numerator.TEMPLATE'))
			->addSelect(new \Bitrix\Main\Entity\ExpressionField('N_NUMERATOR_ID', 'template_numerator.ID'))
			->addSelect(new \Bitrix\Main\Entity\ExpressionField('N_NUMERATOR_TYPE', 'template_numerator.TYPE'))
			->addSelect(new \Bitrix\Main\Entity\ExpressionField('TEMPLATE_NAME', $templateNameExpression), 'TEMPLATE_NAME')
			->whereNotNull('NUMERATOR.ID')
			->whereNotNull('NUMERATOR_ID')
			->where('NUMERATOR_ID', '!=', 0)
			->addGroup('NUMERATOR.ID');
		if (isset($gridFilter['FIND']) && $gridFilter['FIND'])
		{
			$numeratorsBaseQuery = $numeratorsBaseQuery
				->where(\Bitrix\Main\Entity\Query::filter()
					->logic('or')
					->where([
						['NAME', 'like', "%{$gridFilter['FIND']}%",],
					])
				);
		}
		$numeratorSubQuery = null;
		if (!isset($gridFilter['CRM_ENTITIES']))
		{
			$templateNameExpression = \Bitrix\Crm\DbHelper::getSqlByDbType(
				"GROUP_CONCAT(`numerator_template`.`NAME` SEPARATOR ', ')",
				"STRING_AGG(numerator_template.NAME, ', ')"
			);
			$numeratorSubQuery = \Bitrix\Main\Numerator\Model\NumeratorTable::query()
				->setCustomBaseTableAlias('numerator')
				->addSelect(new \Bitrix\Main\Entity\ExpressionField('N_NUMERATOR_NAME', 'numerator.NAME'))
				->addSelect(new \Bitrix\Main\Entity\ExpressionField('N_NUMERATOR_TEMPLATE', 'numerator.TEMPLATE'))
				->addSelect(new \Bitrix\Main\Entity\ExpressionField('N_NUMERATOR_ID', 'numerator.ID'))
				->addSelect(new \Bitrix\Main\Entity\ExpressionField('N_NUMERATOR_TYPE', 'numerator.TYPE'))
				->addSelect(new \Bitrix\Main\Entity\ExpressionField('TEMPLATE_NAME', $templateNameExpression), 'TEMPLATE_NAME')
				->where('N_NUMERATOR_TYPE', 'DOCUMENT')
				->registerRuntimeField('', new \Bitrix\Main\Entity\ReferenceField('TEMPLATE', '\Bitrix\DocumentGenerator\Model\TemplateTable',
					['=this.ID' => 'ref.NUMERATOR_ID'], ["join_type" => "LEFT"]))
				->addGroup('N_NUMERATOR_ID');
			if (isset($gridFilter['FIND']) && $gridFilter['FIND'])
			{
				$numeratorSubQuery = $numeratorSubQuery
					->where(\Bitrix\Main\Entity\Query::filter()
						->logic('or')
						->where([
							['NAME', 'like', "%{$gridFilter['FIND']}%",],
						])
					);
			}
		}
		elseif (!empty($gridFilter['CRM_ENTITIES']))
		{
			$numeratorsBaseQuery = $numeratorsBaseQuery
				->whereIn('PROVIDER.PROVIDER', $gridFilter['CRM_ENTITIES']);
		}
		if ($numeratorSubQuery)
		{
			$numeratorsBaseQuery = $numeratorsBaseQuery
				->unionAll($numeratorSubQuery);
		}
		if (!empty($this->arResult['SORT']))
		{
			if (isset($this->arResult['SORT']['NAME']))
			{
				$numeratorsBaseQuery = $numeratorsBaseQuery
					->addUnionOrder('N_NUMERATOR_NAME', $this->arResult['SORT']['NAME'] == 'desc' ? 'DESC' : 'ASC')
					->addUnionOrder('TEMPLATE_NAME');
			}
		}

		$numeratorsBaseQuery = $numeratorsBaseQuery
			->exec()
			->fetchAll();

		foreach ($numeratorsBaseQuery as $numeratorData)
		{
			foreach ($numeratorData as $key => $value)
			{
				$key = str_replace('N_NUMERATOR_', '', $key);
				$numResults[$numeratorData['N_NUMERATOR_ID']][$key] = $value;
			}
		}

		return $numResults;
	}

	private function processDelete()
	{
		if (empty($_POST['ID']))
		{
			return;
		}
		foreach ($_POST['ID'] as $numId)
		{
			$numerator = Numerator::load($numId);
			if (!$numerator)
			{
				continue;
			}
			$result = Numerator::delete($numId);
			if (!$result->isSuccess())
			{
				$name = $numerator->getConfig();
				$name = $name[Numerator::getType()]['name'];
				$this->addError(Loc::getMessage('CRM_NUMERATOR_LIST_DELETE_ERROR', ['#NUMERATOR_NAME#' => $name]));
			}
		}
	}

	/**
	 * @param $errorMessage
	 */
	private function addError($errorMessage)
	{
		$this->arResult["MESSAGES"][] = [
			"TYPE"  => \Bitrix\Main\Grid\MessageType::ERROR,
			"TITLE" => Loc::getMessage('CRM_NUMERATOR_LIST_INTERNAL_ERROR_TITLE'),
			"TEXT"  => $errorMessage,
		];
	}

	private function processEdit()
	{
		if (empty($_POST['FIELDS']))
		{
			return;
		}
		foreach ($_POST['FIELDS'] as $id => $sourceData)
		{
			if (empty($sourceData['NAME']))
			{
				continue;
			}
			$numerator = Numerator::load($id);
			if (!$numerator)
			{
				continue;
			}
			$config = $numerator->getConfig();
			$oldName = $config[Numerator::getType()]['name'];
			$config[Numerator::getType()]['name'] = \Bitrix\Main\Text\Encoding::convertEncodingToCurrent($sourceData['NAME']);
			$result = Numerator::update($id, $config);

			if (!$result->isSuccess())
			{
				$this->addError(Loc::getMessage('CRM_NUMERATOR_LIST_EDIT_ERROR', ['#NUMERATOR_NAME#' => $oldName]));
			}
		}
	}

	/**
	 * @param $gridId
	 * @param $userId
	 */
	private function processGridActions($gridId, $userId)
	{
		$postAction = 'action_button_' . $gridId;
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST[$postAction]) && check_bitrix_sessid())
		{
			if ($_POST[$postAction] == 'delete' || $_POST[$postAction] == 'destroy')
			{
				$this->processDelete();
			}
			elseif ($_POST[$postAction] == 'edit')
			{
				$this->processEdit();
			}
		}
	}

	/**
	 * @return array
	 */
	public function configureActions()
	{
		return [];
	}
}