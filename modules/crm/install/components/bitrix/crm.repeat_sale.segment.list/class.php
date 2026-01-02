<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Component\Base;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\RepeatSale\Job\Controller\RepeatSaleJobController;
use Bitrix\Crm\RepeatSale\Log\LogReport;
use Bitrix\Crm\RepeatSale\Segment\Controller\RepeatSaleSegmentController;
use Bitrix\Crm\RepeatSale\Segment\DataFormatter;
use Bitrix\Crm\RepeatSale\Segment\Entity\RepeatSaleSegment;
use Bitrix\Crm\RepeatSale\Statistics\PeriodType;
use Bitrix\Crm\RepeatSale\Widget\WidgetManager;
use Bitrix\Crm\Router\ResponseHelper;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\LayoutSettings;
use Bitrix\Crm\WebForm\Internals\PageNavigation;
use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Grid\Options;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\Icon;
use Bitrix\UI\Buttons\JsCode;
use Bitrix\UI\Buttons\Size;
use Bitrix\UI\Toolbar\ButtonLocation;

class CrmRepeatSaleSegmentListComponent extends Base
{
	protected const DEFAULT_PAGE_SIZE = 20;

	private string $navParamName = 'page';
	private ?Options $gridOptions = null;
	private ?PageNavigation $pageNavigation = null;
	private array|string|null $defaultDateTimeFormat = null;

	public function executeComponent(): void
	{
		$intranetToolManager = Container::getInstance()->getIntranetToolsManager();
		if (!$intranetToolManager->checkRepeatSaleAvailability())
		{
			$componentPage = 'disabled';

			$this->includeComponentTemplate($componentPage);

			return;
		}

		if (!Container::getInstance()->getRepeatSaleAvailabilityChecker()->hasPermission())
		{
			$componentPage = 'restrictions';

			$this->includeComponentTemplate($componentPage);

			return;
		}

		if (!Container::getInstance()->getRepeatSaleAvailabilityChecker()->isItemsCountsLessThenLimit())
		{
			$componentPage = 'limit';

			$this->includeComponentTemplate($componentPage);

			return;
		}

		if (!Container::getInstance()->getRepeatSaleAvailabilityChecker()->isAvailable())
		{
			ResponseHelper::showPageNotFound();
		}

		if (!Container::getInstance()->getUserPermissions()->repeatSale()->canRead())
		{
			$this->showError();

			return;
		}

		Container::getInstance()->getLocalization()->loadMessages();

		$this->arResult['SORT'] = $this->getOrder();
		$this->arResult['GRID_ID'] = $this->getGridId();
		$this->arResult['PAGE_NAVIGATION'] = $this->getPageNavigation();
		$this->arResult['ROWS'] = $this->getRows();
		$this->arResult['COLUMNS'] = $this->getColumns();

		$this->includeComponentTemplate();
	}

	private function showError(): void
	{
		$this->getApplication()->IncludeComponent(
			'bitrix:ui.info.error',
			'',
			[
				'TITLE' => Loc::getMessage('CRM_COMMON_ERROR_ACCESS_DENIED'),
				'DESCRIPTION' => '',
			],
		);
	}

	private function getRows(): array
	{
		$repeatSaleSegmentController = RepeatSaleSegmentController::getInstance();
		$segmentCollection = $repeatSaleSegmentController->getList([
			'order' => $this->getOrder(),
			'offset' => $this->getPageNavigation()->getOffset(),
			'limit' => $this->getPageNavigation()->getLimit(),
		]);

		$fieldsData = $this->getPreparedFieldsData($segmentCollection);

		$rows = [];
		foreach ($segmentCollection as $segment)
		{
			$rule['ID'] = $this->getField('ID', $segment);
			$rule['TITLE'] = $this->getField('TITLE', $segment, $fieldsData);
			$rule['IS_ENABLED'] = $this->getField('IS_ENABLED', $segment, $fieldsData);
			$rule['MODIFIED'] = $this->getField('MODIFIED', $segment, $fieldsData);
			$rule['EDIT'] = $this->getField('EDIT', $segment, $fieldsData);
			$rule['SECTION'] = $this->getField('SECTION', $segment, $fieldsData);
			$rule['DEAL_COUNT'] = $this->getField('DEAL_COUNT', $segment, $fieldsData);
			$rule['WIN_DEAL_COUNT'] = $this->getField('WIN_DEAL_COUNT', $segment, $fieldsData);
			$rule['LOSE_DEAL_COUNT'] = $this->getField('LOSE_DEAL_COUNT', $segment, $fieldsData);
			$rule['CONVERSION'] = $this->getField('CONVERSION', $segment, $fieldsData);

			$rows[] = [
				'id' => $segment['ID'],
				'columns' => $rule,
				'actions' => $this->getRowActions($segment['ID']),
			];
		}
		unset($rule);

		return $rows;
	}

	private function getRowActions(int $id): array
	{
		if (!Container::getInstance()->getUserPermissions()->repeatSale()->canEdit())
		{
			return [];
		}

		return [
			[
				'TEXT' => Loc::getMessage('CRM_COMMON_ACTION_EDIT'),
				'ONCLICK' => 'BX.Crm.Router.openSlider("' . $this->getDetailsUri($id) . '", {width: 922, cacheable: false });',
				'DEFAULT' => true,
			],
		];
	}

	private function getOrder(): array
	{
		return $this->getGridOptions()->GetSorting([
			'sort' => ['ID' => 'desc'],
		])['sort'];
	}

	private function getGridOptions(): Options
	{
		if ($this->gridOptions === null)
		{
			$this->gridOptions = new Options($this->getGridId());
		}

		return $this->gridOptions;
	}

	private function getGridId(): string
	{
		return 'crm_repeat_sale_segment_grid';
	}

	private function getPageNavigation(): PageNavigation
	{
		if ($this->pageNavigation === null)
		{
			$pageNavigation = new PageNavigation($this->getPageNavigationId());
			$pageNavigation
				->allowAllRecords(false)
				->setPageSize($this->getPageSize())
				->initFromUri()
			;

			$this->pageNavigation = $pageNavigation;
			$this->pageNavigation->setRecordCount(RepeatSaleSegmentController::getInstance()->getTotalCount());
		}

		return $this->pageNavigation;
	}

	private function getPageNavigationId(): string
	{
		return "{$this->getGridId()}_{$this->navParamName}";
	}

	private function getPageSize(): int
	{
		$navParams = $this->getGridOptions()->getNavParams([
			'nPageSize' => static::DEFAULT_PAGE_SIZE,
		]);

		return (int)$navParams['nPageSize'];
	}

	private function getPreparedFieldsData(Main\ORM\Objectify\Collection $repeatSaleSegments): array
	{
		$userIds = [];
		$categoryIdentifiers = [];
		$segmentIds = [];

		foreach ($repeatSaleSegments as $repeatSale)
		{
			$userIds[] = $repeatSale->getCreatedById();
			$userIds[] = $repeatSale->getUpdatedById();

			$entityTypeId = $repeatSale->getEntityTypeId();
			$entityCategoryId = $repeatSale->getEntityCategoryId();
			if (CCrmOwnerType::isCorrectEntityTypeId($entityTypeId))
			{
				$categoryIdentifiers[$entityTypeId][] = $entityCategoryId;
			}

			$segmentIds[] = $repeatSale->getId();
		}

		if (empty($userIds))
		{
			$users = [];
		}
		else
		{
			$userBroker = Container::getInstance()->getUserBroker();
			$users = $userBroker->getBunchByIds($userIds);
		}

		$categories = [];
		if (isset($categoryIdentifiers[CCrmOwnerType::Deal]))
		{
			$dealCategories = Container::getInstance()->getFactory(CCrmOwnerType::Deal)->getCategories();

			$categories[\CCrmOwnerType::Deal] = [];
			foreach ($dealCategories as $dealCategory)
			{
				$categories[\CCrmOwnerType::Deal][$dealCategory->getId()] = $dealCategory->getName();
			}

			unset($categoryIdentifiers[CCrmOwnerType::Deal]);
		}

		if (!empty($categoryIdentifiers))
		{
			foreach ($categoryIdentifiers as $entityTypeId => $categoryIdentifier)
			{
				$entityCategories = Container::getInstance()->getFactory($entityTypeId)->getCategories();
				$categories[$entityTypeId] = [];
				foreach ($entityCategories as $entityCategory)
				{
					$categories[$entityTypeId][$entityCategory->getId()] = $entityCategory->getName();
				}
			}
		}

		$totalEntities = [];
		$totalWinEntities = [];
		$totalLoseEntities = [];
		if (!empty($segmentIds))
		{
			$options = new Options($this->getGridId());
			$usedColumns = $options->getUsedColumns();

			if (empty($usedColumns))
			{
				$usedColumns = array_map(
					static fn ($column) => $column['id'],
					array_filter($this->getColumns(), static fn ($column) => $column['default'] ?? false)
				);
			}

			$logReport = LogReport::getInstance();
			$totalEntities = (
				in_array('DEAL_COUNT', $usedColumns, true)
					? $logReport->getEntitiesCount($segmentIds)
					: 0
			);
			$totalWinEntities = (
				in_array('WIN_DEAL_COUNT', $usedColumns, true)
					? $logReport->getEntitiesCount($segmentIds, PhaseSemantics::SUCCESS)
					: 0
			);
			$totalLoseEntities = (
				in_array('LOSE_DEAL_COUNT', $usedColumns, true)
					? $logReport->getEntitiesCount($segmentIds, PhaseSemantics::FAILURE)
					: 0
			);
		}

		$controller = RepeatSaleJobController::getInstance();
		$jobInProcess = $controller->getJobInProcess();

		return [
			'users' => $users,
			'categories' => $categories,
			'totalEntities' => $totalEntities,
			'totalWinEntities' => $totalWinEntities,
			'totalLoseEntities' => $totalLoseEntities,
			'segmentIdInProcess' => $jobInProcess?->getSegmentId(),
		];
	}

	private function getField(string $fieldName, RepeatSaleSegment $segment, array $fieldsData = []): string
	{
		$content = match ($fieldName)
		{
			'IS_ENABLED' => $this->getIsEnabledField($segment, $fieldsData),
			'TITLE' => $this->getTitleField($segment),
			'ID' => $this->getIdField($segment),
			'EDIT' => $this->getEditField($segment),
			'SECTION' => $this->getSectionField($segment, $fieldsData),
			'DEAL_COUNT' => $this->getDealCountField($segment, $fieldsData),
			'WIN_DEAL_COUNT' => $this->getWinDealCountField($segment, $fieldsData),
			'LOSE_DEAL_COUNT' => $this->getLoseDealCountField($segment, $fieldsData),
			'CONVERSION' => $this->getConversionField($segment, $fieldsData),
			'MODIFIED' => $this->getModifiedField($segment, $fieldsData),
			default => '',
		};

		return '<div class="crm-repeat-sale-segment-list--field-wrapper">' . $content . '</div>';
	}

	private function getIsEnabledField(RepeatSaleSegment $segment, array $fieldsData): string
	{
		$id = $segment->getId();
		$switcherId = 'crm-repeat-sale-segment-list-is-enabled-' . $id;
		$switcherIdEscaped = CUtil::JSEscape($switcherId);

		$params = Json::encode([
			'id' => $id,
			'targetNodeId' => $switcherIdEscaped,
			'checked' => $segment->getIsEnabled(),
			'readOnly' => $this->isReadOnly(),
			'isFlowDisabled' => Container::getInstance()->getRepeatSaleAvailabilityChecker()->isEnablePending(),
		]);

		$inProcessBlock = '';
		if ($fieldsData['segmentIdInProcess'] === $id)
		{
			$inProcess = Loc::getMessage('CRM_REPEAT_SALE_SEGMENT_LIST_IN_PROCESS');
			$inProcessBlock = "<div class=\"crm-repeat-sale-segment-list-info\">$inProcess</div>";
		}

		return <<<HTML
			<div id="{$switcherId}"></div>
			{$inProcessBlock}
			<script>
				BX.ready(() => {
					const isEnabledField = new BX.Crm.RepeatSale.SegmentList.ActiveField({$params});

					isEnabledField.init();
				});
			</script>
HTML;
	}

	private function getTitleField(RepeatSaleSegment $segment): string
	{
		$id = $segment->getId();
		$uri = $this->getDetailsUri($id);

		return '<div class="crm-repeat-sale-segment-list--field-column"><a href="' . $uri . '">'
			. htmlspecialcharsbx($segment->getTitle())
			. '</a></div>';
	}

	private function getIdField(RepeatSaleSegment $segment): string
	{
		$id = $segment->getId();
		$uri = $this->getDetailsUri($id);

		return '<a href="' . $uri . '">' . $id . '</a>';
	}

	private function getDetailsUri(int $id): Uri
	{
		return DataFormatter::getInstance()->getUri($id);
	}

	private function getEditField(RepeatSaleSegment $segment): string
	{
		$id = $segment->getId();
		$textCode = (
			$this->isReadOnly()
				? 'CRM_COMMON_ACTION_SHOW'
				: 'CRM_COMMON_ACTION_EDIT'
		);

		$buttonBuilder = new Button([
			'id' => 'crm-repeat-sale-segment-list-edit-' . $id,
			'dataset' => [
				'btn-uniqid' => 'crm-repeat-sale-segment-list-edit-' . $id,
			],
			'color' => Color::LIGHT_BORDER,
			'text' => Loc::getMessage($textCode),
			'size' => Size::EXTRA_SMALL,
			'link' => $this->getDetailsUri($id)->getUri(),
			'round' => true,
		]);

		return $buttonBuilder->render();
	}

	private function getSectionField(RepeatSaleSegment $segment, array $fieldsData): string
	{
		$entityTypeId = $segment->getEntityTypeId();
		$categoryId = $segment->getEntityCategoryId();
		$name = $fieldsData['categories'][$entityTypeId][$categoryId] ?? null;

		return $name ? htmlspecialcharsbx($name) : '';
	}

	private function getDealCountField(RepeatSaleSegment $segment, array $fieldsData): string
	{
		$id = (int)$segment->getId();
		$uri = new Main\Web\Uri('/crm/repeat-sale-segment/result/' . $id . '/');

		$value = $fieldsData['totalEntities'][$id] ?? 0;
		if ($value <= 0)
		{
			return 0;
		}

		return '<a href="' . $uri . '"> ' . $value . ' </a>';
	}

	private function getWinDealCountField(RepeatSaleSegment $segment, array $fieldsData): string
	{
		$id = (int)$segment->getId();

		$uri = new Main\Web\Uri('/crm/repeat-sale-segment/result/' . $id . '/');
		$uri->addParams([
			'PHASE_SEMANTIC' => PhaseSemantics::SUCCESS,
		]);

		$value = $fieldsData['totalWinEntities'][$id] ?? 0;
		if ($value <= 0)
		{
			return 0;
		}

		return '<a href="' . $uri . '"> ' . $value . ' </a>';
	}

	private function getLoseDealCountField(RepeatSaleSegment $segment, array $fieldsData): string
	{
		$id = (int)$segment->getId();

		$uri = new Main\Web\Uri('/crm/repeat-sale-segment/result/' . $id . '/');
		$uri->addParams([
			'PHASE_SEMANTIC' => PhaseSemantics::FAILURE,
		]);

		$value = $fieldsData['totalLoseEntities'][$id] ?? 0;
		if ($value <= 0)
		{
			return 0;
		}

		return '<a href="' . $uri . '"> ' . $value . ' </a>';
	}

	private function getConversionField(RepeatSaleSegment $segment, array $fieldsData): string
	{
		$id = (int)$segment->getId();
		$fieldId = 'crm-repeat-sale-segment-list-conversion-' . $id;
		$value = ($fieldsData['totalWinEntities'][$id] ?? 0) / ($fieldsData['totalEntities'][$id] ?? 1);

		$params = Json::encode([
			'id' => $id,
			'targetNodeId' => $fieldId,
			'value' => round(max($value, 0) * 100),
			'borders' => [
				[
					'value' => 30,
					'color' => '#FF5752',
					'id' => 'lowBorder',
				],
				[
					'color' => '#2FC6F6',
					'id' => 'default',
				],
				[
					'value' => 80,
					'color' => '#9DCF00',
					'id' => 'highBorder',
				],
			],
		]);

		return <<<HTML
			<div id="{$fieldId}"></div>
			<script>
				BX.ready(() => {
					const roundChartField = new BX.Crm.RepeatSale.SegmentList.RoundChartField({$params});

					roundChartField.init();
				});
			</script>
HTML;
	}

	private function getModifiedField(RepeatSaleSegment $segment, array $fieldsData): string
	{
		$userInfo = $fieldsData['users'][$segment->getUpdatedById()] ?? [];
		$name = htmlspecialcharsbx($userInfo['FORMATTED_NAME'] ?? '');
		$updatedAt = $this->formatDateTime($segment->getUpdatedAt());

		$classPrefix = 'crm-repeat-sale-segment-list-modified-field';
		$date = '<div class="' . $classPrefix . '-date">' . $updatedAt . '</div>';
		$user = '<div class="' . $classPrefix . '-user">' . $name . '</div>';

		return '<div class="' . $classPrefix . '">' . $date . $user . '</div>';
	}

	private function formatDateTime(DateTime $dateTime): string
	{
		$dateTime = $dateTime->toUserTime();

		$format = $this->getDefaultDateTimeFormat();
		if ($format === null)
		{
			return $dateTime->toString();
		}

		$userNow = CCrmDateTimeHelper::getUserTime(new DateTime());

		$offset = ($userNow->getTimestamp() - $dateTime->getTimestamp());
		$isLessThanOneMinute = $offset / 60 < 1;
		if ($isLessThanOneMinute)
		{
			return Loc::getMessage('CRM_REPEAT_SALE_SEGMENT_LIST_FORMAT_DATE_NOW');
		}

		return FormatDate($format, $dateTime, $userNow);
	}

	private function getDefaultDateTimeFormat(): string|array|null
	{
		if ($this->defaultDateTimeFormat !== null)
		{
			return $this->defaultDateTimeFormat;
		}

		$culture = Application::getInstance()->getContext()->getCulture();
		if ($culture === null)
		{
			return null;
		}

		$shortTimeFormat = $culture->getShortTimeFormat();
		$format = $culture->getLongDateFormat() . ', ' . $shortTimeFormat;

		$layoutSettings = LayoutSettings::getCurrent();
		if ($layoutSettings && $layoutSettings->isSimpleTimeFormatEnabled())
		{
			$timeFormat = $shortTimeFormat;

			$this->defaultDateTimeFormat = [
				'tomorrow' => 'tomorrow, ' . $timeFormat,
				'i' => 'iago',
				'today' => 'today, ' . $timeFormat,
				'yesterday' => 'yesterday, ' . $timeFormat,
				'-' => $format,
			];
		}
		else
		{
			$this->defaultDateTimeFormat = preg_replace(
				'/:s$/',
				'',
				$format,
			);
		}

		return $this->defaultDateTimeFormat;
	}

	private function getColumns(): array
	{
		$columns = [];

		$columns[] = [
			'id' => 'ID',
			'default' => false,
			'name' => 'ID',
			'sort' => 'ID',
		];
		$columns[] = [
			'id' => 'TITLE',
			'default' => true,
			'name' => Loc::getMessage('CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_EDIT'),
			'sort' => 'TITLE',
		];
		$columns[] = [
			'id' => 'IS_ENABLED',
			'default' => true,
			'name' => Loc::getMessage('CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_IS_ENABLED'),
			'sort' => 'IS_ENABLED',
		];
		$columns[] = [
			'id' => 'EDIT',
			'default' => true,
			'name' => Loc::getMessage('CRM_COMMON_SETTINGS'),
		];
		$columns[] = [
			'id' => 'SECTION',
			'default' => true,
			'name' => Loc::getMessage('CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_SECTION'),
		];
		$columns[] = [
			'id' => 'DEAL_COUNT',
			'default' => true,
			'name' => Loc::getMessage('CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_DEAL_COUNT'),
		];
		$columns[] = [
			'id' => 'WIN_DEAL_COUNT',
			'default' => true,
			'name' => Loc::getMessage('CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_WIN_DEAL_COUNT'),
		];
		$columns[] = [
			'id' => 'LOSE_DEAL_COUNT',
			'name' => Loc::getMessage('CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_LOSE_DEAL_COUNT'),
		];
		$columns[] = [
			'id' => 'CONVERSION',
			'default' => true,
			'name' => Loc::getMessage('CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_CONVERSION'),
		];
		$columns[] = [
			'id' => 'MODIFIED',
			'default' => true,
			'name' => Loc::getMessage('CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_MODIFIED_BY'),
			'sort' => 'UPDATED_AT',
		];

		return $columns;
	}

	protected function getToolbarParameters(): array
	{
		$config = WidgetManager::getInstance()->getWidgetConfig();
		$type = $config['type'] ?? null;
		if ($type === null)
		{
			return parent::getToolbarParameters();
		}

		$showConfetti = $config['showConfetti'] ?? false;
		$showConfettiValue = $showConfetti ? 'true' : 'false';
		$periodTypeId = $config['periodTypeId'] ?? PeriodType::Day30->value;

		$parameters = [
			'isWithFavoriteStar' => true,
			'hideBorder' => true,
			'buttons' => [
				ButtonLocation::RIGHT => [
					new Button([
						'text' => Loc::getMessage('CRM_REPEAT_SALE_SEGMENT_LIST_WIDGET_BUTTON'),
						'color' => Color::LIGHT_BORDER,
						'icon' => Icon::COPILOT,
						'onclick' => new JsCode(
							"BX.Crm.RepeatSale.Widget.execute(
								'{$type}',
								event.target,
								{
									showSettingsButton: false,
									showConfetti: {$showConfettiValue},
									periodTypeId: {$periodTypeId},
									isRepeatSaleGrid: true,
								},
								event
							)",
						),
						'dataset' => [
							'id' => 'crm-repeat-sale-widget-button',
							'subSection' => Dictionary::SUB_SECTION_DETAILS,
						],
					]),
				],
			],
		];

		return array_merge(parent::getToolbarParameters(), $parameters);
	}

	private function isReadOnly(): bool
	{
		return !Container::getInstance()->getUserPermissions()->repeatSale()->canEdit();
	}

	protected function getTopPanelParameters(): array
	{
		return array_merge(
			parent::getTopPanelParameters(),
			['ACTIVE_ITEM_ID' => 'REPEAT_SALE_SEGMENT'],
		);
	}
}
