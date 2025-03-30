<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Badge\Type\CopilotCallAssessmentStatus;
use Bitrix\Crm\Component\Base;
use Bitrix\Crm\Component\EntityList\BadgeBuilder;
use Bitrix\Crm\Copilot\AiQualityAssessment\RatingCalculator;
use Bitrix\Crm\Copilot\CallAssessment\Controller\CopilotCallAssessmentClientTypeController;
use Bitrix\Crm\Copilot\CallAssessment\Controller\CopilotCallAssessmentController;
use Bitrix\Crm\Copilot\CallAssessment\Entity\CopilotCallAssessment;
use Bitrix\Crm\Copilot\CallAssessment\Enum\CallType;
use Bitrix\Crm\Copilot\CallAssessment\Enum\ClientType;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Enum\GlobalSetting;
use Bitrix\Crm\Integration\AI\Model\QueueTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\LayoutSettings;
use Bitrix\Crm\WebForm\Internals\PageNavigation;
use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Grid\Options;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;
use Bitrix\UI;
use const Bitrix\Crm\Copilot\AiQualityAssessment\RatingCalculator;

class CrmCopilotCallAssessmentListComponent extends Base
{
	protected const DEFAULT_PAGE_SIZE = 20;

	private string $navParamName = 'page';
	private ?Options $gridOptions = null;
	private ?PageNavigation $pageNavigation = null;
	private array | string | null $defaultDateTimeFormat = null;

	public function executeComponent(): void
	{
		if (
			!AIManager::isAiCallProcessingEnabled()
			|| !Container::getInstance()->getUserPermissions()->canReadCopilotCallAssessmentSettings()
		)
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
		$this->arResult['FILTER'] = $this->getFilter();

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
			]
		);
	}

	private function getRows(): array
	{
		$callAssessmentController = CopilotCallAssessmentController::getInstance();
		$callAssessmentsCollection = $callAssessmentController->getList([
			'filter' => $this->getFilterConditions(),
			'order' => $this->getOrder(),
			'offset' => $this->getPageNavigation()->getOffset(),
			'limit' => $this->getPageNavigation()->getLimit(),
		]);

		$fieldsData = $this->getPreparedFieldsData($callAssessmentsCollection);

		$rows = [];
		foreach ($callAssessmentsCollection as $callAssessment)
		{
			$rule['ID'] = $this->getField('ID', $callAssessment);
			$rule['TITLE'] = $this->getField('TITLE', $callAssessment, $fieldsData);
			$rule['CLIENT'] = $this->getField('CLIENT', $callAssessment, $fieldsData);
			$rule['CALL_TYPE'] = $this->getField('CALL_TYPE', $callAssessment, $fieldsData);
			$rule['IS_ENABLED'] = $this->getField('IS_ENABLED', $callAssessment);
			$rule['ASSESSMENT_AVG'] = $this->getField('ASSESSMENT_AVG', $callAssessment, $fieldsData);
			// $rule['INSPECTOR'] = $this->getField('INSPECTOR', $callAssessment, $fieldsData);
			$rule['PROMPT'] = $this->getField('PROMPT', $callAssessment);
			if ($this->needShowGistColumn())
			{
				$rule['GIST'] = $this->getField('GIST', $callAssessment);
			}
			$rule['MODIFIED'] = $this->getField('MODIFIED', $callAssessment, $fieldsData);

			$rows[] = [
				'id' => $callAssessment['ID'],
				'columns' => $rule,
				'actions' => $this->getRowActions($callAssessment['ID']),
			];
		}
		unset($rule);

		return $rows;
	}

	private function getRowActions(int $id): array
	{
		if (!Container::getInstance()->getUserPermissions()->canEditCopilotCallAssessmentSettings())
		{
			return [];
		}

		return [
			[
				'TEXT' => Loc::getMessage('CRM_COMMON_ACTION_EDIT'),
				'ONCLICK' => 'BX.Crm.Router.openSlider("' . $this->getDetailsUri($id) . '", {width: 700, cacheable: false });',
				'DEFAULT' => true,
			],
			[
				'TEXT' => Loc::getMessage('CRM_COMMON_ACTION_COPY'),
				'HREF' => $this->getDetailsUri($id)->addParams(['copy' => 'Y']),
			],
			[
				'TEXT' => Loc::getMessage('CRM_COMMON_ACTION_DELETE'),
				'ONCLICK' => "BX.Event.EventEmitter.emit('BX.Crm.Copilot.CallAssessment:onClickDelete', {'id':'$id'})"
			],
		];
	}

	private function getFilterConditions(): array
	{
		$filterOptions = new \Bitrix\Main\UI\Filter\Options($this->getGridId());
		$gridFilter = $filterOptions->getFilter();
		$listFilter = new \Bitrix\Crm\Filter\ListFilter(
			CCrmOwnerType::Undefined,
			$this->getFilterFields()
		);

		$conditions = [];

		$findText = null;
		if (!empty($gridFilter['FIND']))
		{
			$findText = $gridFilter['FIND'];
			unset($gridFilter['FIND']);
		}
		$listFilter->prepareListFilter($conditions, $gridFilter);

		if (!empty($gridFilter['CLIENT_TYPE_ID']))
		{
			array_walk($gridFilter['CLIENT_TYPE_ID'], static fn($clientTypeId) => (int)$clientTypeId);
			$conditions['@CLIENT_TYPES.CLIENT_TYPE_ID'] = $gridFilter['CLIENT_TYPE_ID'];
		}

		if ($findText)
		{
			$helper = Application::getConnection()->getSqlHelper();
			$findText = str_replace('%', '\%', $findText);
			$conditions['%=TITLE'] = '%' . $helper->forSql($findText) . '%';
		}

		return $conditions;
	}

	private function getFilter(): array
	{
		return $this->getFilterFields();
	}

	private function getFilterFields(): array
	{
		return [
			'ID' => [
				'id' => 'ID',
				'name' => 'ID',
				'type' => 'string',
			],
			'TITLE' => [
				'id' => 'TITLE',
				'name' => Loc::getMessage('CRM_COMMON_TITLE'),
				'type' => 'string',
				'default' => true,
			],
			'CLIENT_TYPE.CLIENT_TYPE_ID' => [
				'id' => 'CLIENT_TYPE_ID',
				'name' => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_COLUMN_CLIENT'),
				'type' => 'list',
				'items' => [
					ClientType::NEW->value => ClientType::getTitle(ClientType::NEW->value),
					ClientType::IN_WORK->value => ClientType::getTitle(ClientType::IN_WORK->value),
					ClientType::RETURN_CUSTOMER->value => ClientType::getTitle(ClientType::RETURN_CUSTOMER->value),
					ClientType::REPEATED_APPROACH->value => ClientType::getTitle(ClientType::REPEATED_APPROACH->value),
				],
				'params' => [
					'multiple' => 'Y',
				],
			],
			'CALL_TYPE' => [
				'id' => 'CALL_TYPE',
				'name' => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_COLUMN_CALL_TYPE'),
				'type' => 'list',
				'items' => [
					CallType::ALL->value => CallType::getTitle(CallType::ALL->value),
					CallType::INCOMING->value => CallType::getTitle(CallType::INCOMING->value),
					CallType::OUTGOING->value => CallType::getTitle(CallType::OUTGOING->value),
				],
			],
			'IS_ENABLED' => [
				'id' => 'IS_ENABLED',
				'name' => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_COLUMN_IS_ENABLED'),
				'type' => 'list',
				'items' => [
					'N' => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_COLUMN_IS_ENABLED_DISABLED'),
					'Y' => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_COLUMN_IS_ENABLED_ENABLED'),
				],
			],
			'UPDATED_AT' => [
				'id' => 'UPDATED_AT',
				'name' => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_COLUMN_MODIFIED_BY'),
				'type' => 'date',
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
		return 'crm_copilot_call_assessment_grid';
	}

	private function getPageNavigation(): PageNavigation
	{
		if ($this->pageNavigation === null)
		{
			$pageNavigation = new PageNavigation($this->getPageNavigationId());
			$pageNavigation
				->allowAllRecords(false)
				->setPageSize($this->getPageSize())
				->initFromUri();

			$this->pageNavigation = $pageNavigation;
			$this->pageNavigation->setRecordCount(
				CopilotCallAssessmentController::getInstance()->getTotalCount($this->getFilterConditions())
			);
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

	private function getPreparedFieldsData(Main\ORM\Objectify\Collection $callAssessments): array
	{
		$userIds = [];
		$callAssessmentIds = [];

		foreach ($callAssessments as $callAssessment)
		{
			$userIds[] = $callAssessment->getCreatedById();
			$userIds[] = $callAssessment->getUpdatedById();

			$callAssessmentIds[] = $callAssessment->getId();
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

		if (empty($callAssessmentIds))
		{
			$clientTypes = [];
			$assessments = [];
		}
		else
		{
			$clientTypeController = CopilotCallAssessmentClientTypeController::getInstance();
			$clientTypes = $clientTypeController->getByAssessmentIds($callAssessmentIds);

			// @todo calc assessments only if select assessment field
			$assessments = (new RatingCalculator())->calculateRatingByAssessmentIds($callAssessmentIds);
		}

		return [
			'users' => $users,
			'clientTypes' => $clientTypes,
			'assessments' => $assessments,
		];
	}

	private function getField(
		string $fieldName,
		CopilotCallAssessment $callAssessmentItem,
		array $fieldsData = []
	): string
	{
		$content = '';

		if ($fieldName === 'CLIENT')
		{
			$content = $this->getClientField($callAssessmentItem, $fieldsData);
		}
		else if ($fieldName === 'CALL_TYPE')
		{
			$content = $this->getCallTypeField($callAssessmentItem);
		}
		else if ($fieldName === 'PROMPT')
		{
			$content = $this->getPromptField($callAssessmentItem);
		}
		else if ($fieldName === 'IS_ENABLED')
		{
			$content = $this->getIsEnabledField($callAssessmentItem);
		}
		else if ($fieldName === 'ASSESSMENT_AVG')
		{
			$content = $this->getAssessmentAvgField($callAssessmentItem, $fieldsData);
		}
//		else if ($fieldName === 'INSPECTOR')
//		{
//			$content = $this->getInspectorField($callAssessmentItem, $fieldsData);
//		}
		else if ($fieldName === 'TITLE')
		{
			$content = $this->getTitleField($callAssessmentItem);
		}
		else if ($fieldName === 'ID')
		{
			$content = $this->getIdField($callAssessmentItem);
		}
		else if ($fieldName === 'MODIFIED')
		{
			$content = $this->getModifiedField($callAssessmentItem, $fieldsData);
		}
		else if ($fieldName === 'GIST')
		{
			$content = $this->getGistField($callAssessmentItem, $fieldsData);
		}

		return '<div class="crm-copilot-call-assessment-list--field-wrapper">' . $content . '</div>';
	}

	private function getClientField(CopilotCallAssessment $callAssessmentItem, array $fieldsData): string
	{
		if (empty($fieldsData['clientTypes']))
		{
			return '';
		}

		$results = [];

		foreach ($fieldsData['clientTypes'] as $clientTypeData)
		{
			if ((int)$clientTypeData['ASSESSMENT_ID'] === $callAssessmentItem->getId())
			{
				$results[] = ClientType::getTitle($clientTypeData['CLIENT_TYPE_ID']);
			}
		}

		return implode(', ', $results);
	}

	private function getCallTypeField(CopilotCallAssessment $callAssessmentItem): string
	{
		return CallType::getTitle($callAssessmentItem->getCallType());
	}

	private function getPromptField(CopilotCallAssessment $callAssessmentItem): string
	{
		$id = $callAssessmentItem->getId();
		$textCode = (
			$this->isReadOnly()
				? 'CRM_COMMON_ACTION_SHOW'
				: 'CRM_COMMON_ACTION_EDIT'
		);

		$buttonBuilder = new UI\Buttons\Button([
			'id' => 'crm-copilot-call-assessment-list-edit-' . $id,
			'dataset' => [
				'btn-uniqid' => 'crm-copilot-call-assessment-list-edit-' . $id,
			],
			'color' => UI\Buttons\Color::LIGHT_BORDER,
			'text' => Loc::getMessage($textCode),
			'size' => UI\Buttons\Size::EXTRA_SMALL,
			'link' => $this->getDetailsUri($id)->getUri(),
			'round' => true,
		]);

		return $buttonBuilder->render();
	}

	private function getIsEnabledField(CopilotCallAssessment $callAssessmentItem): string
	{
		$id = $callAssessmentItem->getId();
		$switcherId = 'crm-copilot-call-assessment-list-is-enabled-' . $id;
		$switcherIdEscaped = CUtil::JSEscape($switcherId);

		$params = Main\Web\Json::encode([
			'id' => $id,
			'targetNodeId' => $switcherIdEscaped,
			'checked' => $callAssessmentItem->getIsEnabled(),
			'readOnly' => $this->isReadOnly(),
		]);

		return <<<HTML
			<div id="{$switcherId}"></div>
			<script>
				BX.ready(() => {
					const isEnabledField = new BX.Crm.Copilot.CallAssessmentList.ActiveField({$params});

					isEnabledField.init();
				});
			</script>
HTML;
	}

	private function getAssessmentAvgField(CopilotCallAssessment $callAssessmentItem, array $fieldsData): string
	{
		$value = $fieldsData['assessments'][$callAssessmentItem->getId()] ?? 0;

		$id = $callAssessmentItem->getId();
		$fieldId = 'crm-copilot-call-assessment-list-assessment-avg-' . $id;
		$fieldIdEscaped = CUtil::JSEscape($fieldId);

		$borders = [
			[
				'value' => $callAssessmentItem->getLowBorder(),
				'color' => '#FF5752',
				'id' => 'lowBorder',
			],
			[
				'color' => '#2FC6F6',
				'id' => 'default',
			],
			[
				'value' => $callAssessmentItem->getHighBorder(),
				'color' => '#9DCF00',
				'id' => 'highBorder',
			],
		];

		$params = Main\Web\Json::encode([
			'id' => $id,
			'targetNodeId' => $fieldIdEscaped,
			'value' => $value > 0 ? $value : null,
			'borders' => $borders,
		]);

		return <<<HTML
			<div id="{$fieldId}"></div>
			<script>
				BX.ready(() => {
					const roundChartField = new BX.Crm.Copilot.CallAssessmentList.RoundChartField({$params});

					roundChartField.init();
				});
			</script>
HTML;
	}

	// temporary disabled
	/*private function getInspectorField(CopilotCallAssessment $callAssessmentItem, array $fieldsData): string
	{
		$headItems = $callAssessmentItem->getControlHeads() ?? [];

		$content = '';
		foreach ($headItems as $headItem)
		{
			[$userType, $userId] = $headItem;

			if ($userType === InspectorType::DIVISION_HEAD->value)
			{
				$item = Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_COLUMN_INSPECTOR_DIVISION_HEAD');
			}
			else
			{
				$photoUrl = $fieldsData['users'][$userId]['PHOTO_URL'] ?? null;
				$avatar = $photoUrl ? '<img src="' . $photoUrl . '">' : '';
				$item = $avatar . ' ' . htmlspecialcharsbx($fieldsData['users'][$userId]['FORMATTED_NAME'] ?? '');
			}

			$content .= '<span>' . $item . '</span>';
		}

		return '<div class="crm-copilot-call-assessment-list-inspector-field">' . $content . '</div>';
	}*/

	private function getTitleField(CopilotCallAssessment $callAssessmentItem): string
	{
		$id = $callAssessmentItem->getId();
		$uri = $this->getDetailsUri($id);
		$badgeHtml = null;

		if ($callAssessmentItem->getStatus() === QueueTable::EXECUTION_STATUS_ERROR)
		{
			$badge = Badge::createByType(
				Badge::COPILOT_CALL_ASSESSMENT_STATUS_TYPE,
				CopilotCallAssessmentStatus::ERROR_VALUE
			);
			$badgeHtml = BadgeBuilder::render([$badge->getConfigFromMap()]);
		}

		return '<div class="crm-copilot-call-assessment-list--field-column"><a href="' . $uri . '">'
			. htmlspecialcharsbx($callAssessmentItem->getTitle())
			. '</a>' . $badgeHtml . '</div>';
	}

	private function getIdField(CopilotCallAssessment $callAssessmentItem): string
	{
		$id = $callAssessmentItem->getId();
		$uri = $this->getDetailsUri($id);

		return '<a href="' . $uri . '">' . $callAssessmentItem->getId() . '</a>';
	}

	private function getDetailsUri(int $id): Uri
	{
		return new Main\Web\Uri('/crm/copilot-call-assessment/details/' . $id . '/');
	}

	private function getModifiedField(CopilotCallAssessment $callAssessmentItem, array $fieldsData): string
	{
		$userInfo = $fieldsData['users'][$callAssessmentItem->getUpdatedById()] ?? [];
		$name = htmlspecialcharsbx($userInfo['FORMATTED_NAME'] ?? '');
		$updatedAt = $this->formatDateTime($callAssessmentItem->getUpdatedAt());

		$classPrefix = 'crm-copilot-call-assessment-list-modified-field';
		$date = '<div class="' . $classPrefix . '-date">' . $updatedAt . '</div>';
		$user = '<div class="' . $classPrefix . '-user">' . $name . '</div>';

		return '<div class="' . $classPrefix . '">' . $date . $user . '</div>';
	}

	private function getGistField(CopilotCallAssessment $callAssessmentItem, array $fieldsData): string
	{
		return '<div>' . nl2br(htmlspecialcharsbx($callAssessmentItem->getGist())) . '</div>';
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
			return Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_FORMAT_DATE_NOW');
		}

		return FormatDate($format, $dateTime, $userNow);
	}

	private function getDefaultDateTimeFormat(): string | array | null
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
				$format
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
			'name' => Loc::getMessage('CRM_COMMON_TITLE'),
			'sort' => 'TITLE',
		];
		$columns[] = [
			'id' => 'CLIENT',
			'default' => true,
			'name' => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_COLUMN_CLIENT'),
		];
		$columns[] = [
			'id' => 'CALL_TYPE',
			'default' => true,
			'name' => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_COLUMN_CALL_TYPE'),
		];
		$columns[] = [
			'id' => 'IS_ENABLED',
			'default' => true,
			'name' => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_COLUMN_IS_ENABLED'),
			'sort' => 'IS_ENABLED',
		];
//		$columns[] = [
//			'id' => 'INSPECTOR',
//			'default' => true,
//			'name' => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_COLUMN_INSPECTOR'),
//		];
		$columns[] = [
			'id' => 'ASSESSMENT_AVG',
			'default' => false,
			'name' => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_COLUMN_ASSESSMENT_AVG'),
		];
		$columns[] = [
			'id' => 'PROMPT',
			'default' => true,
			'name' => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_COLUMN_PROMPT'),
		];
		if ($this->needShowGistColumn())
		{
			$columns[] = [
				'id' => 'GIST',
				'default' => true,
				'name' => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_COLUMN_GIST'),
			];
		}
		$columns[] = [
			'id' => 'MODIFIED',
			'default' => true,
			'name' => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_COLUMN_MODIFIED_BY'),
			'sort' => 'UPDATED_AT',
		];

		return $columns;
	}

	protected function getToolbarParameters(): array
	{
		$parameters = [
			'isWithFavoriteStar' => true,
			'hideBorder' => true,
		];

		if ($this->isReadOnly())
		{
			return array_merge(parent::getToolbarParameters(), $parameters);
		}

		$buttons = [];

		$isCopilotEnabled = AIManager::isEnabledInGlobalSettings(GlobalSetting::CallAssessment);

		$buttons[UI\Toolbar\ButtonLocation::AFTER_TITLE][] = new UI\Buttons\Button([
			'color' => UI\Buttons\Color::SUCCESS,
			'text' => Loc::getMessage('CRM_COMMON_ACTION_CREATE'),
			'onclick' => new UI\Buttons\JsCode(
				"(new BX.Crm.Copilot.CallAssessmentList.ActionButton(" . ($isCopilotEnabled ? 'true' : 'false') . ")).execute()"
			),
		]);

		$parameters['buttons'] = $buttons;

//		@todo prepare for narrow screens
//		$logoPath = '/bitrix/components/bitrix/crm.copilot.call.assessment.list/templates/.default/images/crm-copilot-call-assessment-list-toolbar-person.png';
//		$parameters['guide'] = new \Bitrix\Crm\UI\Toolbar\ToolbarGuide(
//				Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_TOOLBAR_GUIDE_TITLE'),
//				$logoPath,
//				'flows' // @todo temporary, replace to actual
//			);

		return array_merge(parent::getToolbarParameters(), $parameters);
	}

	private function isReadOnly(): bool
	{
		return !Container::getInstance()->getUserPermissions()->canEditCopilotCallAssessmentSettings();
	}

	private function needShowGistColumn(): bool
	{
		return !is_null($this->request->get('criteria'));
	}

	protected function getTopPanelParameters(): array
	{
		return array_merge(
			parent::getTopPanelParameters(),
			['ACTIVE_ITEM_ID' => 'CALL_ASSESSMENT'],
		);
	}
}
