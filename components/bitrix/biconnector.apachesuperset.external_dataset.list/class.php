<?php

use Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetTable;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceCollection;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelationTable;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceTable;
use Bitrix\BIConnector\ExternalSource\SourceManager;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\BIConnector\Superset\Grid\ExternalDatasetGrid;
use Bitrix\BIConnector\Superset\Grid\Settings\ExternalDatasetSettings;
use Bitrix\BIConnector\ExternalSource\SupersetIntegration;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\Relations;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\UI\Buttons;
use Bitrix\UI\Toolbar\ButtonLocation;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class ApacheSupersetExternalDatasetListComponent extends CBitrixComponent
{
	private const GRID_ID = 'biconnector_superset_external_dataset_grid';

	private ExternalDatasetGrid $grid;

	public function onPrepareComponentParams($arParams)
	{
		$arParams['ID'] = (int)($arParams['ID'] ?? 0);
		$arParams['CODE'] = $arParams['CODE'] ?? '';

		return parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		if (!\Bitrix\Main\Loader::includeModule('biconnector'))
		{
			$this->arResult['ERROR_MESSAGE'] = \Bitrix\Main\Localization\Loc::getMessage('BICONNECTOR_APACHE_SUPERSET_DATASET_GRID_ERROR_LOAD_MODULES') ?? '';
			$this->includeComponentTemplate();

			return;
		}

		$this->init();

		$this->grid->processRequest();
		$this->loadRows();
		$this->arResult['GRID'] = $this->grid;

		$this->includeComponentTemplate();
	}

	private function init(): void
	{
		$this->initGrid();
		$this->initGridFilter();
		$this->initCreateButton();
	}

	private function initGrid(): void
	{
		$settings = new ExternalDatasetSettings([
			'ID' => self::GRID_ID,
			'SHOW_ROW_CHECKBOXES' => false,
			'SHOW_SELECTED_COUNTER' => false,
			'SHOW_TOTAL_COUNTER' => true,
			'EDITABLE' => false,
		]);

		$grid = new ExternalDatasetGrid($settings);
		$this->grid = $grid;

		if (empty($this->grid->getOptions()->getSorting()['sort']))
		{
			$this->grid->getOptions()->setSorting('ID', 'desc');
		}

		$ormParams = $this->grid->getOrmParams();

		$query = ExternalDatasetTable::query()
			->setSelect(['ID'])
			->setCacheTtl(3600)
			->setFilter($ormParams['filter'])
			->registerRuntimeField(
				(new Relations\Reference(
					'SOURCE_RELATION',
					ExternalSourceDatasetRelationTable::class,
					Join::on('this.ID', 'ref.DATASET_ID')
				))
			)
			->registerRuntimeField(
				(new Relations\Reference(
					'SOURCE',
					ExternalSourceTable::class,
					Join::on('this.SOURCE_RELATION.SOURCE_ID', 'ref.ID')
				))
			)
		;


		$totalCount = $query->queryCountTotal();
		$this->grid->initPagination($totalCount);

		if (!$totalCount)
		{
			$this->arResult['GRID_STUB'] = $this->getStub();
		}
	}

	private function loadRows(): void
	{
		$rowsData = [];

		$ormParams = $this->grid->getOrmParams();
		if (!in_array('EXTERNAL_ID', $ormParams['select'], true))
		{
			$ormParams['select'][] = 'EXTERNAL_ID';
		}
		$ormParams['select'][] = 'SOURCE';

		$datasetsQuery = ExternalDatasetTable::query()
			->setSelect($ormParams['select'])
			->setFilter($ormParams['filter'])
			->registerRuntimeField(
				(new Relations\ManyToMany(
					'SOURCE', ExternalSourceTable::class
				))
					->configureMediatorTableName(ExternalSourceDatasetRelationTable::getTableName())
					->configureLocalPrimary('ID', 'DATASET_ID')
					->configureRemotePrimary('ID', 'SOURCE_ID')
			)
			->setLimit($ormParams['limit'])
			->setOffset($ormParams['offset'])
			->setOrder($ormParams['order'])
		;

		$datasetCollection = $datasetsQuery->exec()->fetchCollection();

		foreach ($datasetCollection as $dataset)
		{
			$row = $dataset->toArray();
			$row['IS_DELETED'] = false;

			/** @var ExternalSourceCollection $sourceCollection */
			$sourceCollection = $dataset->get('SOURCE');
			$source = current($sourceCollection->getAll());
			if ($source)
			{
				$row['SOURCE'] = [
					'ID' => $source->getId(),
					'TYPE' => $source->getType(),
					'TITLE' => $source->getTitle(),
				];
			}

			$rowsData[] = $row;
		}

		if ($rowsData)
		{
			$additionalDataResult = (new SupersetIntegration())->loadDatasetList($datasetCollection);
			if ($additionalDataResult->isSuccess())
			{
				$additionalData = $additionalDataResult->getData();
				$externalIds = array_column($additionalData, 'id');

				foreach ($rowsData as $index => $dataset)
				{
					if (!in_array((int)$dataset['EXTERNAL_ID'], $externalIds, true))
					{
						$dataset['IS_DELETED'] = true;
						$rowsData[$index] = $dataset;
					}
				}
			}
		}

		$this->grid->setRawRows($rowsData);
	}

	private function initGridFilter(): void
	{
		$filter = $this->grid->getFilter();
		if ($filter)
		{
			$options = \Bitrix\Main\Filter\Component\ComponentParams::get(
				$this->grid->getFilter(),
				[
					'GRID_ID' => $this->grid->getId(),
				]
			);
		}
		else
		{
			$options = [
				'FILTER_ID' => $this->grid->getId(),
			];
		}

		Toolbar::addFilter($options);
	}

	private function initCreateButton(): void
	{
		if (SourceManager::isExternalConnectionsAvailable())
		{
			$button = new Buttons\Split\CreateButton([
				'dataset' => [
					'toolbar-collapsed-icon' => Buttons\Icon::ADD,
				],
			]);

			if (SupersetInitializer::isSupersetLoading() || SupersetInitializer::isSupersetUnavailable())
			{
				$button->getMainButton()->getAttributeCollection()['onclick'] = 'BX.BIConnector.ExternalDatasetManager.Instance.showSupersetError()';
			}
			else
			{
				$button->getMainButton()->getAttributeCollection()['onclick'] = 'BX.BIConnector.DatasetImport.Slider.open("csv")';
			}

			$menuItems = [
				[
					'text' => Loc::getMessage('BICONNECTOR_APACHE_SUPERSET_DATASET_GRID_MENU_ITEM_IMPORT_CSV_MSGVER_1'),
					'onclick' => new \Bitrix\UI\Buttons\JsCode('this.close(); BX.BIConnector.DatasetImport.Slider.open("csv")'),
				],
				[
					'text' => Loc::getMessage('BICONNECTOR_APACHE_SUPERSET_DATASET_GRID_MENU_ITEM_EXTERNAL_CONNECTION'),
					'onclick' => new \Bitrix\UI\Buttons\JsCode('this.close(); BX.BIConnector.DatasetImport.Slider.open("1c")'),
				],
			];

			$button->setMenu([
				'items' => $menuItems,
				'closeByEsc' => true,
				'angle' => true,
				'offsetLeft' => 115,
				'autoHide' => true,
			]);
		}
		else
		{
			$button = new Buttons\CreateButton([
				'dataset' => [
					'toolbar-collapsed-icon' => Buttons\Icon::ADD,
				],
			]);

			if (SupersetInitializer::isSupersetLoading() || SupersetInitializer::isSupersetUnavailable())
			{
				$button->getAttributeCollection()['onclick'] = 'BX.BIConnector.ExternalDatasetManager.Instance.showSupersetError()';
			}
			else
			{
				$button->getAttributeCollection()['onclick'] = 'BX.BIConnector.DatasetImport.Slider.open("csv")';
			}
		}

		Toolbar::addButton($button, ButtonLocation::AFTER_TITLE);
	}

	private function getStub(): ?string
	{
		if ($this->grid->getOrmFilter() !== [])
		{
			return null;
		}

		$iconPath = $this->getPath() . '/images/not-found.svg';
		$title = Loc::getMessage('BICONNECTOR_APACHE_SUPERSET_DATASET_GRID_STUB_TITLE') ?? '';
		$description = Loc::getMessage('BICONNECTOR_APACHE_SUPERSET_DATASET_GRID_STUB_DESCRIPTION_MSGVER_1') ?? '';

		return <<<HTML
			<div class="biconnector-dataset-grid-stub-container">
				<div class="biconnector-dataset-grid-stub-logo">
					<img src="{$iconPath}" alt="Not Found">
				</div>
				<div class="main-grid-empty-block-title">
					{$title}
				</div>
				<div class="main-grid-empty-block-description document-list-stub-description">
					{$description}
				</div>
			</div>
		HTML;
	}
}
