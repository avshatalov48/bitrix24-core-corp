<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\Activity\Provider\Zoom;
use Bitrix\Main;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Crm;
use Bitrix\Crm\Timeline\TimelineType;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Timeline\Entity\TimelineBindingTable;
use Bitrix\Crm\Timeline\ActivityController;
use Bitrix\Crm\Timeline\TimelineEntry;

Loc::loadMessages(__FILE__);

class CMobileCrmCallTrackerTimelineComponent
	extends CBitrixComponent
	implements Main\Engine\Contract\Controllerable, Main\Errorable
{
	protected $entityId;
	protected $entityTypeId;
	protected $entityTypeName;
	protected $errors;
	protected $paginationName = 'nav';

	/** @var CTextParser|null  */
	protected $parser = null;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errors = new Main\ErrorCollection();
	}

	public function onPrepareComponentParams($arParams)
	{
		if (Main\Loader::includeModule('crm'))
		{
			$arParams['ENTITY_TYPE_ID'] = (int)$arParams['ENTITY_TYPE_ID'];
			$arParams['ENTITY_ID'] = (int)$arParams['ENTITY_ID'];

			$this->entityTypeId = $arParams['ENTITY_TYPE_ID'];
			$this->entityTypeName = CCrmOwnerType::ResolveName($this->entityTypeId);
			$this->entityId = $arParams['ENTITY_ID'];

			if ($this->entityTypeName !== CCrmOwnerType::DealName)
			{
				$this->errors->setError(new Main\Error('Wrong entity type.'));
			}
		}
		else
		{
			$this->errors->setError(new Main\Error('Module CRM is not installed.'));
		}
		return $arParams;
	}

	protected function getEntity(int $entityId)
	{
		if (!Crm\Authorization\Authorization::checkReadPermission($this->entityTypeId, $entityId))
		{
			$this->errors->setError(new Main\Error(Loc::getMessage('CRM_PERMISSION_DENIED')));
			return null;
		}

		if (!($entity = Crm\DealTable::query()
			->where('ID', $entityId)
			->setSelect(['ID', 'TITLE'])
			->fetch()))
		{
			$this->errors->setError(new Main\Error(Loc::getMessage('CRM_CALL_TRACKER_ENTITY_NOT_FOUND_' . $this->entityTypeName)));
			return null;
		}

		return $entity;
	}
	private function getTimelineQuery()
	{
		$bindingQuery = new Query(TimelineBindingTable::getEntity());
		$bindingQuery
			->setSelect(['OWNER_ID', 'IS_FIXED'])
			->addFilter('=ENTITY_TYPE_ID', $this->entityTypeId)
			->addFilter('=ENTITY_ID', $this->entityId);

		$query = new Query(TimelineTable::getEntity());
		$query->setSelect(['*', 'IS_FIXED' => 'bind.IS_FIXED'])
			->registerRuntimeField('',
				new ReferenceField('bind',
					Base::getInstanceByQuery($bindingQuery),
					array('=this.ID' => 'ref.OWNER_ID'),
					array('join_type' => 'INNER')
				)
			)
			->where(
				Main\ORM\Query\Query::filter()
					->logic('or')
					->where('TYPE_ID', Crm\Timeline\TimelineType::COMMENT)
					->where( Main\ORM\Query\Query::filter()
						->where('TYPE_ID', Crm\Timeline\TimelineType::ACTIVITY)
						->where('TYPE_CATEGORY_ID', \CCrmActivityType::Provider)
						->where('ASSOCIATED_ENTITY_CLASS_NAME', Crm\Activity\Provider\CallTracker::PROVIDER_ID)
					)
			)
			->whereNotIn(
				'ASSOCIATED_ENTITY_TYPE_ID',
				Crm\Timeline\TimelineManager::getIgnoredEntityTypeIDs()
			);
		return $query;
	}

	private function prepareItems(array $items)
	{
		Crm\Timeline\TimelineManager::prepareDisplayData($items);

		$items = array_map(function($item) {
			$result = array_intersect_key($item, [
				'ID' => null,
				'CREATED' => null,
				'AUTHOR_ID' => null,
				'AUTHOR' => null,
				'HAS_FILES' => null,
				'HAS_INLINE_ATTACHMENT' => null,
				'COMMENT' => null,
				'ASSOCIATED_ENTITY' => null
			]);
			$result['CREATED'] = self::formatDate($result['CREATED'] ? $result['CREATED']->toUserTime() : new DateTime());

			if ($item['TYPE_ID'] == Crm\Timeline\TimelineType::COMMENT)
			{
				$result['TYPE_CODE'] = 'COMMENT';
				unset($result['ASSOCIATED_ENTITY']);
			}
			else
			{
				$result['TYPE_CODE'] = \Bitrix\Crm\Activity\Provider\CallTracker::TYPE_ID;
				$result['ASSOCIATED_ENTITY'] = array_intersect_key($result['ASSOCIATED_ENTITY'], [
					'ID' => null,
					'CREATED' => null,
					'TYPE_ID' => null,
					'OWNER_ID' => null,
					'OWNER_TYPE_ID' => null,
					'DIRECTION' => null,
					'SETTINGS' => null,
					'CALL_INFO' => null,
					'MEDIA_FILE_INFO' => null,
				]);
				$result['ASSOCIATED_ENTITY']['CREATED'] = self::formatDate(new DateTime($result['ASSOCIATED_ENTITY']['CREATED']));
			}
			return $result;
		}, $items);
		return $items;
	}

	protected function getItems($restart, $activityListIsInited = false)
	{
		$pageNavigation = new Main\UI\PageNavigation($this->paginationName);
		$pageNavigation->allowAllRecords(false);
		$query = $this->getTimelineQuery()
			->setOrder(['CREATED' => 'DESC', 'ID' => 'DESC']);
		if ($restart === true)
		{
			$pageNavigation->setPageSize($activityListIsInited === true ? 1 : 3);
		}
		else
		{
			$pageNavigation
				->setPageSize(10)
				->initFromUri();
			$pageNavigation->setCurrentPage($pageNavigation->getCurrentPage() - 1);
			$lastItem = Crm\Timeline\Entity\TimelineTable::getById($this->request->get('itemId'))->fetch();
			$query
				->where(
					Main\ORM\Query\Query::filter()
						->logic('or')
						->where('CREATED', '<', $lastItem['CREATED'])
						->where(
							Main\ORM\Query\Query::filter()
								->where('CREATED', $lastItem['CREATED'])
								->where('ID', '<', $lastItem['ID'])
						)
				);
		}
		$query
			->setOffset($pageNavigation->getOffset())
			->setLimit($pageNavigation->getLimit() + 1);

		$dbResult = $query->exec();
		$items = [];
		while ($res = $dbResult->fetch())
		{
			$items[(int)$res['ID']] = $res;
		}

		$nextPageIsEnable = false;
		if (count($items) > $pageNavigation->getLimit())
		{
			$nextPageIsEnable = true;
			$items = array_slice($items, 0, $pageNavigation->getLimit(), true);
		}

		$items = $this->prepareItems($items);

		return [array_reverse(array_values($items)), $nextPageIsEnable];
	}

	private function initActivityList()
	{
		$dbResult = \CCrmActivity::GetList(
			['CREATED' => 'asc'],
			[
				'=COMPLETED' => 'N',
				'CHECK_PERMISSIONS' => 'Y',
				'TYPE_ID' => \CCrmActivityType::Provider,
				'PROVIDER_ID' => \Bitrix\Crm\Activity\Provider\CallTracker::PROVIDER_ID,
				'BINDINGS' => [
					[
						'OWNER_ID' => $this->entityId,
						'OWNER_TYPE_ID' => $this->entityTypeId
					]
				]
			],
			false,
			false,
			array(
				'ID', 'OWNER_ID', 'OWNER_TYPE_ID', 'CREATED',
				'TYPE_ID', 'PROVIDER_ID', 'PROVIDER_TYPE_ID', 'ASSOCIATED_ENTITY_ID', 'DIRECTION',
				'SUBJECT', 'STATUS', 'DESCRIPTION', 'DESCRIPTION_TYPE',
				'DEADLINE', 'RESPONSIBLE_ID', 'PROVIDER_PARAMS', 'SETTINGS'
			),
			array('QUERY_OPTIONS' => array('LIMIT' => 100, 'OFFSET' => 0))
		);
		$items = array();
		while ($fields = $dbResult->fetch())
		{
			$items[$fields['ID']] = ActivityController::prepareScheduleDataModel($fields);
		}

		Crm\Timeline\EntityController::prepareAuthorInfoBulk($items);
		Crm\Timeline\EntityController::prepareMultiFieldInfoBulk($items);


		$items = array_map(function($item) {
			$result = [
				'AUTHOR_ID' => $item['AUTHOR_ID'],
				'AUTHOR' => $item['AUTHOR'],
				'TYPE_CODE' => 'CALL_TRACKER_ACTIVITY',
				'ASSOCIATED_ENTITY' => array_intersect_key($item['ASSOCIATED_ENTITY'], [
					'ID' => null,
					'TYPE_ID' => null,
					'CREATED' => null,
					'DEADLINE' => null,
					'OWNER_ID' => null,
					'OWNER_TYPE_ID' => null,
					'DIRECTION' => null,
					'SETTINGS' => null,
					'CALL_INFO' => null,
				])
			];

			$result['ASSOCIATED_ENTITY']['CREATED'] = self::formatDate(new DateTime($result['ASSOCIATED_ENTITY']['CREATED']));
			$result['ASSOCIATED_ENTITY']['DEADLINE'] = self::formatDate(new DateTime($result['ASSOCIATED_ENTITY']['DEADLINE']));
			return $result;
		}, $items);

		$this->arResult['ACTIVITIES'] = array_values($items);
		return !empty($this->arResult['ACTIVITIES']);
	}

	private static function formatDate(Main\Type\DateTime $dateTime)
	{
		static $format;
		if (!is_array($format))
		{
			$timeFormat = Main\Context::getCurrent()->getCulture()->getShortTimeFormat();

			$format = [
				'time' => $timeFormat,
				'dateTime' => Main\Context::getCurrent()->getCulture()->getLongDateFormat().' '.$timeFormat,
				'dateTimeWOYear' => Main\Context::getCurrent()->getCulture()->getDayMonthFormat().' '.$timeFormat,
			];
		}
		return \FormatDate([
			'tomorrow' => 'tomorrow, '.$format['time'],
			'today' => $format['time'],
			'yesterday' => 'yesterday, '.$format['time'],
			'' => $format[date("Y", $dateTime->getTimestamp()) == date("Y") ? 'dateTimeWOYear' : 'dateTime']
		], $dateTime, (new DateTime())->toUserTime());
	}

	public function executeComponent()
	{
		$activityListIsInited = $this->initActivityList();

		$this->arResult['ITEMS'] = [];
		$this->arResult['PAGINATION_HAS_MORE'] = false;
		if (!$this->errors->count()
			&& ($this->arResult['ENTITY'] = $this->getEntity($this->entityId)))
		{
			[$this->arResult['ITEMS'], $this->arResult['PAGINATION_HAS_MORE']] = $this->getItems(true, $activityListIsInited);
		}

		if ($this->errors->count())
		{
			ShowError($this->errors->offsetGet(0)->getMessage());
			return;
		}
		$this->includeComponentTemplate();
	}

	public function getItemsAction()
	{
		[$this->arResult['ITEMS'], $this->arResult['PAGINATION_HAS_MORE']] = $this->getItems(false);
		return ['items' => $this->arResult['ITEMS'], 'paginationHasMore' => $this->arResult['PAGINATION_HAS_MORE']];
	}

	public function getItemAction($id, $options = [])
	{
		$result = ['files' => '', 'text' => ''];
		if (in_array('GET_FILE_BLOCK', $options))
		{
			$result['files'] = Crm\Timeline\CommentController::getFileBlock($id, ['MOBILE' => 'Y']);
		}
		if (in_array('GET_COMMENT', $options))
		{
			$commentData = Crm\Timeline\TimelineEntry::getByID($id);
			$data = Crm\Timeline\CommentController::convertToHtml($commentData, ['INCLUDE_FILES' => 'Y', 'MOBILE' => 'Y']);
			$result['text'] = $data['COMMENT'];
		}
		return $result;
	}

	public function createItemAction($text, $files = [])
	{
		$text = trim($text);
		if ($text === '')
		{
			$this->errors->setError(new Main\Error('Empty comment message.'));
			return false;
		}
		$lastId = $this->getTimelineQuery()
			->setOrder(['ID' => 'DESC'])
			->setLimit(1)
			->exec()
			->fetch();
		$files = is_array($files) ? $files : [];
		$params = [
			'TEXT' => $text,
			'FILES' => $files,
			'SETTINGS' => ['HAS_FILES' => empty($files) ? 'N' : 'Y'],
			'AUTHOR_ID' => CCrmSecurityHelper::GetCurrentUserID(),
			'BINDINGS' => [['ENTITY_TYPE_ID' => $this->entityTypeId, 'ENTITY_ID' => $this->entityId]]
		];
		$itemId = Crm\Timeline\CommentEntry::create($params);
		if ($itemId <= 0)
		{
			$this->errors->setError(new Main\Error('Could not create comment.'));
		}
		$saveData = array(
			'COMMENT' => $text,
			'ENTITY_TYPE_ID' => $this->entityTypeId,
			'ENTITY_ID' => $this->entityId,
		);
		$item = Crm\Timeline\CommentController::getInstance()->onCreate($itemId, $saveData);

		$dbResult = $this->getTimelineQuery()
			->setOrder(['ID' => 'ASC'])
			->where(
				Main\ORM\Query\Query::filter()
					->logic('or')
					->where('ID', '>', is_array($lastId) ? $lastId['ID'] : 0)
					->where('ID', $item['ID'])
			)
			->exec();
		$items = [];
		while ($res = $dbResult->fetch())
		{
			$items[(int)$res['ID']] = $res;
		}
		$items = $this->prepareItems($items);

		if (!empty($files))
		{
			$data = Crm\Timeline\CommentController::convertToHtml($item, ['INCLUDE_FILES' => 'Y', 'MOBILE' => 'Y']);
			$items[$item['ID']]['COMMENT'] = $data['COMMENT'];
			$items[$item['ID']]['PARSED_ATTACHMENT'] = Crm\Timeline\CommentController::getFileBlock($itemId, ['MOBILE' => 'Y']);
		}
		return ['item' => ['ID' => $item['ID']], 'items' => array_values($items)];
	}

	public function configureActions()
	{
		return [];
	}

	protected function listKeysSignedParameters()
	{
		return [
			"ENTITY_TYPE_ID",
			"ENTITY_ID",
		];
	}

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors(): array
	{
		return $this->errors->toArray();
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return ?Main\Error
	 */
	public function getErrorByCode($code): ?Main\Error
	{
		return $this->errors->getErrorByCode($code);
	}
}