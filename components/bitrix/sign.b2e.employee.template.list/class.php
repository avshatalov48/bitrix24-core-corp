<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Sign\Access\AccessController;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Config\Feature;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\Document\TemplateService;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass('bitrix:sign.base');

final class SignB2eEmployeeTemplateListComponent extends SignBaseComponent
{
	private const DEFAULT_GRID_ID = 'SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_GRID';
	private const DEFAULT_FILTER_ID = 'SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_FILTER';
	private const DEFAULT_NAVIGATION_KEY = 'sign-b2e-employee-template-list';
	private const DEFAULT_PAGE_SIZE = 10;
	private const ADD_NEW_TEMPLATE_LINK = '/sign/b2e/doc/0/?mode=template';
	private readonly TemplateService $documentTemplateService;
	private readonly PageNavigation $pageNavigation;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->documentTemplateService = Container::instance()->getDocumentTemplateService();
		$this->pageNavigation = $this->getPageNavigation();
	}

	public function executeComponent(): void
	{
		if (!Storage::instance()->isB2eAvailable())
		{
			showError((string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_B2E_NOT_ACTIVATED'));

			return;
		}

		if (!Feature::instance()->isSendDocumentByEmployeeEnabled())
		{
			showError((string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_TO_EMPLOYEE_NOT_ACTIVATED'));

			return;
		}

		parent::executeComponent();
	}

	public function exec(): void
	{
		$this->setParam('ADD_NEW_TEMPLATE_LINK', self::ADD_NEW_TEMPLATE_LINK);
		$this->setParam('COLUMNS', $this->getGridColumnList());
		$this->setParam('FILTER_FIELDS', $this->getFilterFieldList());
        $this->setParam('DEFAULT_FILTER_FIELDS', $this->getFilterFieldList());
        $this->setParam('FILTER_PRESETS', $this->getFilterFieldList());
		$this->setParam('GRID_ID',self::DEFAULT_GRID_ID);
		$this->setParam('FILTER_ID',self::DEFAULT_FILTER_ID);
		$this->setResult('TOTAL_COUNT', $this->pageNavigation->getRecordCount());
		$this->setResult('TEMPLATES', $this->getGridData());
		$this->setResult('PAGE_SIZE', $this->pageNavigation->getPageSize());
		$this->setResult('PAGE_NAVIGATION', $this->pageNavigation);
		$this->setResult('NAVIGATION_KEY', $this->pageNavigation->getId());
	}

	private function getGridData(): array
	{
		return array_map(
			static fn (Document\Template $template): array => [
				'id' => $template->id,
				'columns' =>
				[
					'TITLE' => $template->title,
					'DATE_CREATE' => $template->dateCreate,
				]
			],
			$this->documentTemplateService->getB2eEmployeeTemplateList(
                $this->getFilterQuery(),
				$this->pageNavigation->getPageSize(),
				$this->pageNavigation->getOffset(),
			)->toArray()
		);
	}

	private function getGridColumnList(): array
	{
		return [
			[
				'id' => 'TITLE',
				'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_COLUMN_NAME'),
				'default' => true,
			],
			[
				'id' => 'DATE_CREATE',
				'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_COLUMN_DATE_CREATE'),
				'default' => true,
			],
		];
	}

	private function getFilterFieldList(): array
	{
		return [
			[
				'id' => 'TITLE',
				'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_COLUMN_NAME'),
                'default' => true,
			],
			[
				'id' => 'DATE_CREATE',
				'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_COLUMN_DATE_CREATE'),
				'type' => 'date',
                'default' => true,
			],
		];
	}

	private function getPageNavigation(): PageNavigation
	{
		$pageSize = (int)$this->getParam('PAGE_SIZE');
		$pageSize = $pageSize > 0 ? $pageSize : self::DEFAULT_PAGE_SIZE;
		$navigationKey = $this->getParam('NAVIGATION_KEY') ?? self::DEFAULT_NAVIGATION_KEY;

		$pageNavigation = new PageNavigation($navigationKey);
		$pageNavigation->setPageSize($pageSize)
			->setRecordCount($this->documentTemplateService->getB2eEmployeeTemplateListCount($this->getFilterQuery()))
			->allowAllRecords(false)
			->initFromUri()
		;

		return $pageNavigation;
	}

    private function getFilterQuery(): ConditionTree
    {
        $filterData = $this->getFilterValues();

        return $this->prepareQueryFilter($filterData);
    }

    private function getFilterValues(): array
    {
        $options = new Options(self::DEFAULT_FILTER_ID);

        return $options->getFilter($this->getFilterFieldList());
    }

    private function prepareQueryFilter(array $filterData): ConditionTree
    {
        $filter = Bitrix\Main\ORM\Query\Query::filter();

        $dateCreateFrom = $filterData['DATE_CREATE_from'] ?? null;
        $dateCreateTo = $filterData['DATE_CREATE_to'] ?? null;
        $find = $filterData['FIND'] ?? null;
		$title = $find ?: $filterData['TITLE'] ?? null;

        if ($dateCreateFrom && \Bitrix\Main\Type\DateTime::isCorrect($dateCreateFrom))
        {
            $filter->where('DATE_CREATE', '>=', new \Bitrix\Main\Type\DateTime($dateCreateFrom));
        }

        if ($dateCreateTo && \Bitrix\Main\Type\DateTime::isCorrect($dateCreateTo))
        {
            $filter->where('DATE_CREATE', '<=', new \Bitrix\Main\Type\DateTime($dateCreateTo));
        }

        if ($title)
        {
            $filter->whereLike('TITLE', '%' . $title . '%');
        }

        return $filter;
    }
}
