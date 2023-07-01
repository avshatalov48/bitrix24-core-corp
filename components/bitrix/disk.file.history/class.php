<?php

use Bitrix\Disk\Configuration;
use Bitrix\Disk\Integration\Bitrix24Manager;
use Bitrix\Disk\Internals\DiskComponent;
use Bitrix\Disk\Internals\Engine\Contract\SidePanelWrappable;
use Bitrix\Disk\Ui\FileAttributes;
use Bitrix\Disk\Version;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

class CDiskFileHistoryComponent extends DiskComponent implements SidePanelWrappable
{
	const ERROR_COULD_NOT_FIND_OBJECT  = 'DISK_FH_22001';
	const ERROR_COULD_NOT_FIND_VERSION = 'DISK_FH_22002';

	/** @var \Bitrix\Disk\File */
	protected $file;

	protected $componentId = 'file_history';

	protected function processBeforeAction($actionName)
	{
		parent::processBeforeAction($actionName);

		if (!Bitrix24Manager::isFeatureEnabled('disk_file_history'))
		{
			return false;
		}

		$securityContext = $this->storage->getCurrentUserSecurityContext();
		if (!$this->file->canRead($securityContext))
		{
			$this->showAccessDenied();

			return false;
		}

		return true;
	}

	protected function prepareParams()
	{
		parent::prepareParams();

		if (!($this->arParams['FILE'] instanceof \Bitrix\Disk\File))
		{
			throw new \Bitrix\Main\ArgumentException('FILE is required');
		}
		$this->file = $this->arParams['FILE'];

		return $this;
	}

	protected function processActionDefault()
	{
		$this->application->setTitle(Loc::getMessage('DISK_FILE_VIEW_VERSION_PAGE_TITLE'));

		$gridId = 'file_version_list';

		$this->arResult = [
			'VERSION_GRID' => $this->getVersionGridData($gridId),
			'FILE' => [
				'ID' => $this->file->getId(),
			]
		];

		$this->includeComponentTemplate();
	}

	private function buildVersionFilter(): array
	{
		$dayLimit = Configuration::getFileVersionTtl();
		if ($dayLimit === -1 || $this->file->hasAttachedObjects())
		{
			return [];
		}

		return [
			'>CREATE_TIME' => DateTime::createFromTimestamp(time() - $dayLimit * 86400),
		];
	}

	private function getVersionGridData($gridId)
	{
		$grid = [
			'ID' => $gridId,
		];

		$gridOptions = new \Bitrix\Main\Grid\Options($grid['ID']);
		$gridSort = $gridOptions->getSorting(
			[
				'sort' => ['ID' => 'desc'],
				'vars' => ['by' => 'by', 'order' => 'order']
			]
		);

		$grid['SORT'] = $gridSort['sort'];
		$grid['SORT_VARS'] = $gridSort['vars'];

		$this->arResult['ITEMS'] = $this->file->getVersions(
			[
				'filter' => $this->buildVersionFilter(),
				'with' => ['CREATE_USER'],
				'order' => $gridSort['sort'],
			]
		);

		$securityContext = $this->storage->getCurrentUserSecurityContext();
		$urlManager = \Bitrix\Disk\Driver::getInstance()->getUrlManager();
		$rows = [];
		foreach ($this->arResult['ITEMS'] as $version)
		{
			/** @var $version Version */
			$objectArray = $version->toArray();
			$actions = [
				[
					"text" => Loc::getMessage('DISK_FILE_VIEW_HISTORY_ACT_DOWNLOAD'),
					"href" => $urlManager->getUrlForDownloadVersion($version),
				],
			];

			if ($this->file->canRestore($securityContext))
			{
				$actions[] = [
					"text" => Loc::getMessage('DISK_FILE_VIEW_HISTORY_ACT_RESTORE'),
					"onclick" => "BX.Disk['FileHistoryComponent_{$this->getComponentId()}'].openRestoreConfirm({
							object: {
								id: {$this->file->getId()},
								name: '{$this->file->getName()}'
							},
							version: {
								id: {$version->getId()}
							}
						})",
				];
			}
			if ($this->file->canRestore($securityContext) && $this->file->canDelete($securityContext))
			{
				$actions[] = [
					"id" => "delete",
					"text" => Loc::getMessage('DISK_FILE_VIEW_HISTORY_ACT_DELETE'),
					"onclick" => "BX.Disk['FileHistoryComponent_{$this->getComponentId()}'].openDeleteConfirm({
							object: {
								id: {$this->file->getId()},
								name: '{$this->file->getName()}'
							},
							version: {
								id: {$version->getId()}
							}
						})",
				];
			}

			$attr = FileAttributes::tryBuildByFileId($version->getFileId(), new Uri($urlManager->getUrlForDownloadVersion($version)))
				->setTitle($version->getName())
				->setGroupBy($this->componentId)
				->setVersionId($version->getId())
			;

			$createUser = $version->getCreateUser();
			$createdByLink = \CComponentEngine::makePathFromTemplate(
				$this->arParams['PATH_TO_USER'],
				['user_id' => $version->getCreatedBy()]
			);

			$anchorCreateUser = "
				<div class=\"bx-disk-user-link\"><span class=\"bx-disk-fileinfo-owner-avatar\" style=\"background-image: url('" . Uri::urnEncode($createUser->getAvatarSrc()) . "');\"></span><a target='_blank' href=\"{$createdByLink}\" id=\"\">" . htmlspecialcharsbx(
					$createUser->getFormattedName()) . "</a></div>
			";

			$rows[] = [
				'data' => $objectArray,
				'columns' => [
					'FORMATTED_SIZE' => CFile::formatSize($version->getSize()),
					'NAME' => "<span style='cursor: pointer;' {$attr}>" . $version->getName() . "</span>",
					'CREATE_USER' => $anchorCreateUser,
					'CREATE_TIME_VERSION' => $version->getCreateTime(),
					'CREATE_TIME_FILE' => $version->getObjectCreateTime(),
					'UPDATE_TIME_FILE' => $version->getObjectUpdateTime(),
				],
				'actions' => $actions,
			];
		}

		$grid['ROWS'] = $this->hideDeleteIfThereOnlyOneVersion($rows);
		$grid['TOTAL_ROWS_COUNT'] = count($rows);
		$grid['HEADERS'] = [
			[
				'id' => 'CREATE_USER',
				'name' => Loc::getMessage('DISK_FILE_VIEW_VERSION_COLUMN_CREATE_USER_2'),
				'default' => true,
			],
			[
				'id' => 'NAME',
				'name' => Loc::getMessage('DISK_FILE_VIEW_VERSION_COLUMN_NAME'),
				'default' => true,
			],
			[
				'id' => 'CREATE_TIME_VERSION',
				'name' => Loc::getMessage('DISK_FILE_VIEW_VERSION_COLUMN_CREATE_TIME_2'),
				'default' => true,
			],
			[
				'id' => 'FORMATTED_SIZE',
				'name' => Loc::getMessage('DISK_FILE_VIEW_VERSION_COLUMN_FORMATTED_SIZE'),
				'default' => true,
			],
			[
				'id' => 'ID',
				'name' => 'ID',
				'default' => false,
			],
		];

		return $grid;
	}

	private function hideDeleteIfThereOnlyOneVersion($versionRows)
	{
		if (count($versionRows) === 1)
		{
			foreach ($versionRows[0]['actions'] as $i => $action)
			{
				if (isset($action['id']) && $action['id'] === 'delete')
				{
					unset($versionRows[0]['actions'][$i]);
				}
			}
		}

		return $versionRows;
	}
}