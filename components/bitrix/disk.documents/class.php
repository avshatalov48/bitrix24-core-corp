<?php

use Bitrix\Disk;
use Bitrix\Disk\Controller\Integration\Flipchart;
use Bitrix\Disk\Internals\BaseComponent;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Type\DocumentGridVariant;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\User;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\UiTour;
use Bitrix\Main\Web\Uri;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

Main\Loader::requireModule('disk');

class CDiskDocumentsComponent extends BaseComponent implements Controllerable
{
	public const ERROR_COULD_NOT_VIEW_DOCUMENTS = 'DISK_DOCUMENTS_22000';

	protected $paginationName = 'nav';

	private $userId;
	private $storage;
	private $nowTime;
	private $fullFormatWithoutSec;

	private Disk\Type\DocumentGridVariant $variant = Disk\Type\DocumentGridVariant::All;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->userId = (int)$this->getUser()->getId();
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function prepareParams()
	{
		if (empty($this->arParams['USER_ID']))
		{
			$this->errorCollection[] = new Error('Empty USER_ID parameter.');
		}
		elseif ($this->arParams['USER_ID'] !== $this->getUserId())
		{
			$error = new Error('Documents owner is not a current user.', self::ERROR_COULD_NOT_VIEW_DOCUMENTS);
			$this->errorCollection->setError($error);
		}

		if ($this->getUserId() <= 0)
		{
			$this->errorCollection[] = new Error('Documents owner is not specified.');
		}
		else if (!($storage = Driver::getInstance()->getStorageByUserId($this->getUserId())))
		{
			$this->errorCollection[] = new Error('User storage is not found.');
		}
		else
		{
			$this->storage = $storage;
		}

		$this->arResult['VARIANT'] = Disk\Type\DocumentGridVariant::All;
		if (isset($this->arParams['VARIANT']) && ($this->arParams['VARIANT'] instanceof Disk\Type\DocumentGridVariant))
		{
			$this->variant = $this->arParams['VARIANT'];
			$this->arResult['VARIANT'] = $this->arParams['VARIANT'];
		}
		if ($this->variant == Disk\Type\DocumentGridVariant::FlipchartList)
		{
			$this->arResult['HIDE_BUTTONS'] = true;
		}

		return parent::prepareParams();
	}

	protected function shouldWorkWithOnlyOffice(): bool
	{
		if (!Disk\Document\OnlyOffice\OnlyOfficeHandler::isEnabled())
		{
			return false;
		}

		$bitrix24Scenario = new Disk\Document\OnlyOffice\Bitrix24Scenario();
		if (!$bitrix24Scenario->canUseEdit())
		{
			return false;
		}

		return true;
	}

	public function prepareItems(array $files, array $visibleColumns = []): array
	{
		$items = [];
		$driver = Driver::getInstance();
		$urlManager = $driver->getUrlManager();

		$this->nowTime = time() + CTimeZone::getOffset();
		$this->fullFormatWithoutSec = preg_replace('/:s$/', '', CDatabase::dateFormatToPHP(CSite::GetDateFormat("FULL")));

		/* @var Disk\File $file */
		foreach ($files as $file)
		{
			/** @var Main\Type\DateTime $activityTime */
			$activityTime = $file->getExtra()->get('ACTIVITY_TIME');
			$timestampActivity = $activityTime->toUserTime()->getTimestamp();
			$timestampCreate = $file->getCreateTime()->toUserTime()->getTimestamp();
			$timestampUpdate = $file->getUpdateTime()->toUserTime()->getTimestamp();

			$fileId = $file->getId();
			$item = [
				'ID' => $file->getExtra()->get('TRACKED_OBJECT_ID'),
				'NAME' => $file->getName(),
				'SIZE' => $file->getSize(),
				'FILE_SIZE' => $file->getSize(),
				'CREATED_BY' => (in_array('CREATED_BY', $visibleColumns) ? [
					'ID' => $file->getCreateUser()->getId(),
					'URL' => $file->getCreateUser()->getDetailUrl(),
					'AVATAR_HTML' => $file->getCreateUser()->renderAvatar(),
					'NAME' => htmlspecialcharsbx($file->getCreateUser()->getFormattedName())
				] : null),
				'UPDATED_BY' => (in_array('UPDATED_BY', $visibleColumns) ? [
					'ID' => $file->getUpdatedBy(),
					'URL' => $file->getUpdateUser()->getDetailUrl(),
					'AVATAR_HTML' => $file->getUpdateUser()->renderAvatar(),
					'NAME' => htmlspecialcharsbx($file->getUpdateUser()->getFormattedName())
				] : null),
				'CREATE_TIME' => $this->getRelativeTime($timestampCreate),
				'UPDATE_TIME' => $this->getRelativeTime($timestampUpdate),
				'ACTIVITY_TIME' => $this->getRelativeTime($timestampActivity),
				'CONTENT_TYPE' => $file->getExtra()->get('FILE_CONTENT_TYPE'),
				'WIDTH' => $file->getExtra()->get('FILE_WIDTH'),
				'HEIGHT' => $file->getExtra()->get('FILE_HEIGHT'),
				'EXT' => $file->getExtension(),
				'TYPE' => $file->getType(),
				'object' => $file,
				'OPEN_DOCUMENT_LINK' => $this->getUrlManager()->getUrlForViewBoard($file->getId()),
			];

			$sourceUri = new Main\Web\Uri($urlManager->getUrlForDownloadFile($file));
			if ($fileId && !empty($item['FILE_CONTENT_TYPE']))
			{
				$attr = Disk\Ui\FileAttributes::buildByFileData([
					'ID' => $file->getFileId(),
					'CONTENT_TYPE' => $item['CONTENT_TYPE'],
					'WIDTH' => $item['WIDTH'],
					'HEIGHT' => $item['HEIGHT'],
					'ORIGINAL_NAME' => $item['NAME'],
					'FILE_SIZE' => $item['FILE_SIZE'],
				], $sourceUri);
			}
			else
			{
				$attr = Disk\Ui\FileAttributes::tryBuildByFileId($file->getFileId(), $sourceUri);
			}
			$attr
				->setObjectId($fileId)
				->setAttachedObjectId($file->getExtra()->get('ATTACHED_OBJECT_ID'))
				->setTitle($item['NAME'])
				->addAction([
					'type' => 'download',
				])
			;

			$documentName = \CUtil::JSEscape($item['NAME']);

			if ($this->shouldWorkWithOnlyOffice())
			{
				$attr->setAttribute('data-open-edit-instead-preview', true);
			}

			if ($file->getTypeFile() == TypeFile::FLIPCHART)
			{
				$openUrl = $this->getUrlManager()->getUrlForViewBoard($fileId);
				$attr->addAction([
					'type' => 'open',
					'buttonIconClass' => ' ',
					'action' => 'BX.Disk.Viewer.Actions.openInNewTab',
					'params' => [
						'objectId' => $fileId,
						'url' => $openUrl,
					],
				]);
			}
			else
			{
				$attr->addAction([
					'type' => 'edit',
					'action' => 'BX.Disk.Viewer.Actions.runActionDefaultEdit',
					'buttonIconClass' => ' ',
					'params' => [
						'objectId' => $fileId,
						'name' => $documentName,
						'dependsOnService' => null,
					],
					'items' => array_map(static function($handler) use ($documentName, $fileId) {
						return [
							'text' => $handler['name'],
							'onclick' => "BX.Disk.Viewer.Actions.runActionEdit({name: '{$documentName}', objectId: {$fileId}, serviceCode: '{$handler['code']}'})",
						];
					},  $this->arResult['DOCUMENT_HANDLERS']),
				]);
			}

			$item['ATTRIBUTES'] = $attr;

			$items[] = $item;
		}

		return $items;
	}

	protected function getRelativeTime(int $time): string
	{
		if (($this->nowTime - $time > 158400))
		{
			return formatDate($this->fullFormatWithoutSec, $time, $this->nowTime);
		}

		return formatDate('x', $time, $this->nowTime);
	}

	private function getVariantFilter(): array
	{
		return match($this->variant) {
			Disk\Type\DocumentGridVariant::FlipchartList => [
				'TRACKED_OBJECT.TYPE_FILE' => TypeFile::FLIPCHART,
			],
			Disk\Type\DocumentGridVariant::DocumentsList => [
				'TRACKED_OBJECT.TYPE_FILE' => [
					TypeFile::DOCUMENT,
					TypeFile::FLIPCHART,
				],
			],
			Disk\Type\DocumentGridVariant::All => [],
			default => [],
		};
	}

	public function getItems(array $filter, ?Main\UI\PageNavigation $pageNavigation, array $sorting, array $visibleColumns = []): array
	{
		$aliases = array_flip(array_intersect(['UPDATE_USER' => 'UPDATED_BY', 'CREATE_USER' => 'CREATED_BY'], $visibleColumns));
		$variantFilter = $this->getVariantFilter();

		$args = ([
			'select' => [
				'*',
				'FILE_CONTENT_TYPE' => 'FILE_CONTENT.CONTENT_TYPE',
				'FILE_WIDTH' => 'FILE_CONTENT.WIDTH',
				'FILE_HEIGHT' => 'FILE_CONTENT.HEIGHT',
				'TRACKED_OBJECT_ID' => 'TRACKED_OBJECT.ID',
				'ATTACHED_OBJECT_ID' => 'TRACKED_OBJECT.ATTACHED_OBJECT_ID',
				'ACTIVITY_TIME' => 'TRACKED_OBJECT.UPDATE_TIME',
			],
			'filter' => [
				'TRACKED_OBJECT.USER_ID' => $this->getUserId(),
				'DELETED_TYPE' => Disk\Internals\ObjectTable::DELETED_TYPE_NONE,
				'TYPE' => Disk\Internals\ObjectTable::TYPE_FILE,
			] + $filter + $variantFilter,
			'with' => $aliases,
			'order' => $sorting
		]) + ($pageNavigation ? [
			'limit' => $pageNavigation->getLimit() + 1,
			'offset' => $pageNavigation->getOffset(),
		] : []);

		$items = [];
		$nextPageIsEnable = false;

		$dbRes = Disk\File::getList($args);
		foreach ($dbRes as $row)
		{
			if ($pageNavigation && (count($items) >= $pageNavigation->getLimit()))
			{
				$nextPageIsEnable = true;
				break;
			}
			$model = Disk\File::buildFromRow($row, $aliases);
			$model->getExtra()->set('FILE_CONTENT_TYPE', $row['FILE_CONTENT_TYPE']);
			$model->getExtra()->set('FILE_WIDTH', $row['FILE_WIDTH']);
			$model->getExtra()->set('FILE_HEIGHT', $row['FILE_HEIGHT']);
			$model->getExtra()->set('TRACKED_OBJECT_ID', $row['TRACKED_OBJECT_ID']);
			$model->getExtra()->set('ATTACHED_OBJECT_ID', $row['ATTACHED_OBJECT_ID']);
			$model->getExtra()->set('ACTIVITY_TIME', $row['ACTIVITY_TIME'] ?: new Main\Type\DateTime(0));
			$items[] = $model;
		}

		$items = $this->prepareItems($items, $visibleColumns);

		return [array_values($items), $nextPageIsEnable];
	}

	private function getGridHeadersByDefault()
	{
		$columns = array_map(function($item) {
			return $item['id'];
			}, array_filter($this->getGridHeaders(), function($item) {
				return $item['default'] === true;
		}));
		return $columns;
	}

	public function getGridHeaders(array $sorting = [])
	{
		$result = [
			[
				'id' => 'ID',
				'name' => 'ID',
				'sort' => 'TRACKED_OBJECT.ID',
				'first_order' => 'asc',
				'prevent_default' => false,
			],
			[
				'id' => 'NAME',
				'name' => Main\Localization\Loc::getMessage('DISK_DOCUMENTS_HEADER_FILE_NAME'),
				'default' => true,
				'sort' => 'NAME',
				'editable' => true,
				'first_order' => 'asc',
				'prevent_default' => true,
			],
			[
				'id' => 'ACTIVITY_TIME',
				'name' => Main\Localization\Loc::getMessage('DISK_DOCUMENTS_HEADER_ACTIVITY'),
				'default' => true,
				'sort' => 'ACTIVITY_TIME',
				'first_order' => 'desc',
			],
			[
				'id' => 'FILE_SIZE',
				'name' => Main\Localization\Loc::getMessage('DISK_DOCUMENTS_HEADER_FILE_SIZE'),
				'sort' => 'SIZE',
				'first_order' => 'desc',
			],
			[
				'id' => 'CREATE_TIME',
				'name' => Main\Localization\Loc::getMessage('DISK_DOCUMENTS_HEADER_CREATE_TIME'),
				'sort' => 'CREATE_TIME',
				'first_order' => 'desc',
			],
			[
				'id' => 'CREATED_BY',
				'name' => Main\Localization\Loc::getMessage('DISK_DOCUMENTS_HEADER_CREATED_BY'),
				'default' => true,
			],
			[
				'id' => 'UPDATE_TIME',
				'name' => Main\Localization\Loc::getMessage('DISK_DOCUMENTS_HEADER_UPDATE_TIME'),
				'sort' => 'UPDATE_TIME',
				'first_order' => 'desc',
			],
			[
				'id' => 'UPDATED_BY',
				'name' => Main\Localization\Loc::getMessage('DISK_DOCUMENTS_HEADER_UPDATED_BY'),
			],
			[
				'id' => 'SHARED',
				'name' => Main\Localization\Loc::getMessage('DISK_DOCUMENTS_HEADER_SHARED'),
				'default' => true,
			]
		];
		if (Disk\Configuration::isEnabledManualExternalLink() || Disk\Configuration::isEnabledBoardExternalLink())
		{
			$result[] = [
				'id' => 'EXTERNAL_LINK',
				'name' => Main\Localization\Loc::getMessage('DISK_DOCUMENTS_HEADER_EXTERNAL_LINK'),
				'default' => true,
				'class' => 'external-link-header',
			];
		}

		if (!empty($sorting))
		{
			$result = array_map(function($item) use ($sorting) {
				if (isset($item['sort']) && array_key_exists($item['sort'], $sorting))
				{
					$item['color'] = \Bitrix\Main\Grid\Column\Color::BLUE;
				}
				return $item;
			}, $result);
		}
		return $result;
	}

	private function getFilterDefinition()
	{
		$result = [];
		$result['NAME'] = [
			'id' => 'NAME',
			'name' => Main\Localization\Loc::getMessage('DISK_DOCUMENTS_FILTER_FILE_NAME'),
			'type' => 'string',
			'default' => true,
		];
		return $result;
	}

	private function getFilter()
	{
		$filterOptions = new Main\UI\Filter\Options($this->arResult['FILTER_ID']);
		$rawFilter = $filterOptions->getFilter(
			$this->getFilterDefinition()
		);

		$filter = [];

		if (!empty($rawFilter['FIND']) && trim($rawFilter['FIND']) <> '')
		{
			$fulltextContent = Disk\Search\FullTextBuilder::create()
				->addText(trim($rawFilter['FIND']))
				->getSearchValue()
			;

			if ($fulltextContent && Main\Search\Content::canUseFulltextSearch($fulltextContent))
			{
				if (Disk\Search\Reindex\HeadIndex::isReady() && empty($rawFilter['SEARCH_BY_CONTENT']))
				{
					$filter["*HEAD_INDEX.SEARCH_INDEX"] = $fulltextContent;
				}
				elseif
				(
					!empty($rawFilter['SEARCH_BY_CONTENT']) &&
					Disk\Configuration::allowUseExtendedFullText() &&
					Disk\Search\Reindex\ExtendedIndex::isReady()
				)
				{
					$filter["*EXTENDED_INDEX.SEARCH_INDEX"] = $fulltextContent;
				}
				elseif
				(
					(!empty($rawFilter['SEARCH_BY_CONTENT']) || !Disk\Search\Reindex\HeadIndex::isReady()) &&
					Disk\Search\Reindex\BaseObjectIndex::isReady()
				)
				{
					$filter["*SEARCH_INDEX"] = $fulltextContent;
				}
			}
			if (empty($filter))
			{
				$filter['%NAME'] = [trim($rawFilter['FIND'])];
			}
		}

		if (!empty($rawFilter['NAME']) && trim($rawFilter['NAME']) <> '')
		{
			$filter['%NAME'][] = trim($rawFilter['NAME']);
		}
		if (isset($rawFilter['TYPE_FILE']))
		{
			$filter['TYPE_FILE'] = $rawFilter['TYPE_FILE'];
			if (in_array(Disk\TypeFile::DOCUMENT, $filter['TYPE_FILE']))
			{
				$filter['TYPE_FILE'][] = Disk\TypeFile::PDF;
				$filter['TYPE_FILE'][] = Disk\TypeFile::KNOWN;
			}
		}
		return $filter;
	}


	/**
	 * @return DocumentHandler[]
	 */
	public function listCloudHandlersForCreatingFile()
	{
		$handlers = array();
		if (Disk\Configuration::canCreateFileByCloud())
		{
			$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
			foreach ($documentHandlersManager->getHandlers() as $handler)
			{
				if ($handler instanceof Disk\Document\Contract\FileCreatable)
				{

					$handlers[] = array(
						'code' => $handler::getCode(),
						'name' => $handler::getName(),
					);
				}
			}
		}
		$handlers[] = array(
			'code' => Disk\Document\LocalDocumentController::getCode(),
			'name' => Disk\Document\LocalDocumentController::getName(),
		);
		return $handlers;
	}

	protected function processActionDefault()
	{
		if ($this->errorCollection->getErrorByCode(self::ERROR_COULD_NOT_VIEW_DOCUMENTS))
		{
			$this->includeComponentTemplate('error_ownership');

			return;
		}
		if (!$this->errorCollection->isEmpty())
		{
			ShowError(implode('<br>', $this->errorCollection->getValues()));

			return;
		}

		$this->arResult['GRID_ID'] = 'diskDocumentsGrid';
		$this->arResult['FILTER_ID'] = 'diskDocumentsFilter';
		$this->arResult['PATH_TO_DISK'] = $this->storage->getProxyType()->getBaseUrlFolderList();
		$this->arResult['PATH_TO_TRASHCAN_LIST'] = $this->storage->getProxyType()->getBaseUrlTashcanList();

		$gridOptions = new Main\Grid\Options($this->arResult['GRID_ID']);
		$sorting = $gridOptions->GetSorting(['sort' => ['ACTIVITY_TIME' => 'desc']]);
		$navParams = $gridOptions->GetNavParams();

		$pageNavigation = new Main\UI\PageNavigation($this->paginationName);
		$pageNavigation
			->setPageSize($navParams['nPageSize'])
			->allowAllRecords(false);
		if ($this->request->get('grid_action') === 'more' && $this->request->get('grid_id') === $gridOptions->getId())
		{
			$pageNavigation->setCurrentPage($this->request->get($this->paginationName));
		}
		else
		{
			$pageNavigation->initFromUri();
		}

		$this->arResult['DOCUMENT_HANDLERS'] = $this->listCloudHandlersForCreatingFile();
		$filterData = $this->getFilter();
		[$this->arResult['ITEMS'], $nextPage] = $this->getItems(
			$filterData,
			$pageNavigation,
			$sorting['sort'],
			$gridOptions->GetVisibleColumns() ?: $this->getGridHeadersByDefault()
		);

		$pageNavigation
			->setRecordCount(
				$pageNavigation->getOffset()
				+ count($this->arResult['ITEMS'])
				+ ($nextPage ? 1 : 0)
			);
		$this->arResult['SORT'] = $sorting['sort'];
		$this->arResult['SORT_VARS'] = $sorting['vars'];
		$this->arResult['HEADERS'] = $this->getGridHeaders($this->arResult['SORT']);
		$this->arResult['FILTER'] = $this->getFilterDefinition();
		$this->arResult['NAV_OBJECT'] = $pageNavigation;
		$this->arResult['ENABLE_NEXT_PAGE'] = $nextPage;
		$this->arResult['CURRENT_PAGE'] = $pageNavigation->getCurrentPage();

		$this->arResult['GRID_VIEW'] = array(
			'MODE' => null,
			'VIEW_SIZE' => null,
		);
		$this->arResult['IS_FILTER_SET'] = !empty($filterData);
		$options = CUserOptions::GetOption('disk', 'documents');
		if (isset($options['viewMode']) && $options['viewMode'] === 'tile')
		{
			$this->arResult['GRID_VIEW']['MODE'] = 'tile';
			$this->arResult['GRID_VIEW']['VIEW_SIZE'] = $options['viewSize'];
		}

		$this->arResult['STORAGE'] = $this->storage;

		$this->arResult['CLASS_COMPONENT'] = $this;

		$this->arResult['BOARDS_GUIDE'] = [
			'ID' => $this->getBoardsGuideId(),
			'IS_VIEWED' => $this->isBoardsGuideViewed(),
			'IS_BOARDS_PAGE' => $this->variant === DocumentGridVariant::FlipchartList,
		];

		$this->includeComponentTemplate();
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	public function configureActions()
	{
		return [];
	}

	public function formatRows($rows)
	{
		$arResult = $this->arResult;
		return array_map(
			function($row) use ($arResult)
			{
				//region Data for name field
				$urlManager = Driver::getInstance()->getUrlManager();
				/* @var Disk\File $file */
				$file = $row['object'];

				$nameSpecialChars = htmlspecialcharsbx($row['NAME']);

				$lockedBy = null;
				$inlineStyleLockIcon = 'style="display:none;"';

				if (Disk\Configuration::isEnabledObjectLock() && $file->getLock())
				{
					$lockedBy = $file->getLock()->getCreatedBy();
					$inlineStyleLockIcon = '';
				}
				$iconClass = Disk\Ui\Icon::getIconClassByObject($file, $row['IS_SHARED']);
				//endregion
				$arResult['justCounter'] = $arResult['justCounter'] + 1;

				$fileNameFormatted = <<<HTML
					<span class="bx-disk-folder-title" style='cursor: pointer;' id="disk_obj_{$row['ID']}" {$row['ATTRIBUTES']}>
						{$nameSpecialChars}
					</span>
HTML;

				return [
					'id' => $row['ID'],
					'data' => [
						'ID' => $row['ID'],
						'NAME' => $row['NAME'],
						'FILE_SIZE' => $row['FILE_SIZE'],
						'CREATED_BY' => $row['CREATED_BY']['ID'],
						'UPDATED_BY' => $row['UPDATED_BY']['ID'],
						'FILE_CONTENT_TYPE' => $file->getExtra()->get('FILE_CONTENT_TYPE'),
					],
					'columnClasses' =>[
						'EXTERNAL_LINK' => 'main-grid-cell-external-link',
					],
					'columns' => array(
						'ID' => $row['ID'],
						'NAME' => <<<HTML
	<table class="bx-disk-object-name"><tr>
		<td style="width: 45px;">
			<div data-object-id="{$row['ID']}" class="bx-file-icon-container-small {$iconClass}">
				<div id="lock-anchor-created-{$row['ID']}" {$inlineStyleLockIcon} class="js-lock-icon js-disk-locked-document-tooltip disk-locked-document-block-icon-small-list disk-locked-document-block-icon-small-folder" data-lock-created-by="{$lockedBy}"></div>
			</div>
		</td>
		<td>
			{$fileNameFormatted}
		</td>
	</tr></table>
HTML
					,
						'FILE_SIZE' => \CFile::formatSize($row['FILE_SIZE']),
						'CREATED_BY' => <<<HTML
<div class="bx-disk-user-link disk-documents-grid-user">
	{$row['CREATED_BY']['AVATAR_HTML']}
	<a class="disk-documents-grid-user-link" target='_blank' href="{$row['CREATED_BY']['URL']}">{$row['CREATED_BY']['NAME']}</a>
</div>
HTML
					,
						'UPDATED_BY' => <<<HTML
<div class="bx-disk-user-link disk-documents-grid-user">
	{$row['UPDATED_BY']['AVATAR_HTML']}
	<a class="disk-documents-grid-user-link" target='_blank' href="{$row['UPDATED_BY']['URL']}">{$row['UPDATED_BY']['NAME']}</a>
</div>
HTML
					,
						'ACTIVITY_TIME' => $row['ACTIVITY_TIME'],
						'CREATE_TIME' => $row['CREATE_TIME'],
						'UPDATE_TIME' => $row['UPDATE_TIME'],
						'SHARED' => <<<HTML
<div class="bx-disk-sharing" id="bx-disk-user-shared-{$row['ID']}">
	<script>BX.ready(function(){
		BX.Disk.Documents.showShared({$row['ID']}, BX('bx-disk-user-shared-{$row['ID']}'));
	});</script>
</div>
HTML
					,
						'EXTERNAL_LINK' => $this->getHtmlForExternalLink($row, $file)
					,
					),
					'actions' => [[
						'id' => 'loader',
						'html' => '<svg class="disk-documents-circular" viewBox="25 25 50 50">
									   <circle class="disk-documents-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
									   <circle class="disk-documents-inner-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
									</svg>',
					]],
					'attrs' => [],
				];
			}, $arResult['ITEMS']);
	}

	private function getHtmlForExternalLink(array $row, Disk\File $file): string
	{
		if ($this->shouldShowCellForExternalLink($file))
		{
			return <<<HTML
<div class="bx-disk-external-link" id="bx-disk-external-link-{$row['ID']}">
	<div class=" disk-control-external-link disk-control-external-link-skeleton--active">
		<div class="disk-control-external-link-btn">
			<span class="ui-switcher ui-switcher-off">
				<span class="ui-switcher-cursor"></span>
				<span class="ui-switcher-disabled"></span>
			</span>
		</div>
		<div class="disk-control-external-link-main">
			<div class="disk-control-external-link-skeleton"></div>
		</div>
	</div>
	<script>BX.ready(function(){
		BX.Disk.Documents.showExternalLink({$row['ID']}, BX('bx-disk-external-link-{$row['ID']}'));
	});</script>
</div>
HTML;
		}

		return '';
	}

	private function shouldShowCellForExternalLink(Disk\File $file): bool
	{
		$fileType = (int)$file->getTypeFile();

		return match ($fileType) {
			TypeFile::FLIPCHART => Disk\Configuration::isEnabledBoardExternalLink(),
			default => Disk\Configuration::isEnabledManualExternalLink(),
		};
	}

	private function getBoardsGuideId(): string
	{
		if ($this->variant === DocumentGridVariant::FlipchartList)
		{
			return 'boards-guide-on-boards-page';
		}

		return 'boards-guide-on-documents-page';
	}

	private function isBoardsGuideViewed(): bool
	{
		$user = $this->getUser();
		if ($user instanceof CUser)
		{
			return (new UiTour($this->getBoardsGuideId()))->isViewed($this->getUser()->getId());
		}

		return false;
	}

}
