<?php

use Bitrix\Disk\Driver;
use Bitrix\Disk\ExternalLink;
use Bitrix\Disk\ProxyType;
use Bitrix\Disk\Internals\DiskComponent;
use Bitrix\Disk\User;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Grid;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Collection;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Loc::loadMessages(__FILE__);

class CDiskExternalLinkListComponent extends DiskComponent
{
	protected $componentId = 'external_link_list';
	/** @var \Bitrix\Disk\Folder */
	protected $folder;
	/** @var  array */
	protected $breadcrumbs;

	private function processGridActions($gridId)
	{
		$buttonName = 'action_button_'.$gridId;
		if (!Bitrix\Main\Grid\Context::isInternalRequest() || !$this->existActionButton($buttonName) || !check_bitrix_sessid())
		{
			return;
		}

		$buttonValue = $this->getActionButtonValue($buttonName);
		foreach ($this->request->getPost('rows') as $rowId)
		{
			if ($buttonValue === 'delete')
			{
				$this->deleteExternalLink($rowId);
			}
		}
	}

	protected function deleteExternalLink($id)
	{
		/** @var ExternalLink $externalLink */
		$externalLink = ExternalLink::loadById($id, array('OBJECT.STORAGE'));
		if (!$externalLink)
		{
			return false;
		}

		//todo perf we can use getModelList and filter by SimpleRights with ID in (...). Also at once we make so quickly
		if (
			!$externalLink->getFile()->canRead(
				$externalLink->getFile()->getStorage()->getCurrentUserSecurityContext()
			)
		)
		{
			return false;
		}

		return $externalLink->delete();
	}

	protected function existActionButton($buttonName)
	{
		return $this->getActionButtonValue($buttonName) !== null;
	}

	protected function getActionButtonValue($buttonName)
	{
		if (!$this->request->isPost())
		{
			return null;
		}

		$controls = $this->request->getPost('controls');
		if (empty($controls[$buttonName]))
		{
			return null;
		}

		return $controls[$buttonName];
	}

	protected function processActionDefault()
	{
		$gridId = $this->componentId;

		$this->application->setTitle(htmlspecialcharsbx($this->storage->getProxyType()->getTitleForCurrentUser()));

		$this->processGridActions($gridId);

		$proxyType = $this->storage->getProxyType();
		$this->arResult = array(
			'GRID' => $this->getGridData($gridId),
			'ROOT_OBJECT' => array(
				'NAME' => $proxyType->getTitleForCurrentUser(),
				'LINK' => $proxyType->getBaseUrlFolderList(),
				'ID' => $this->storage->getRootObjectId(),
			),
			'STORAGE' => array(
				'ID' => $this->storage->getId(),
			),
		);

		$this->includeComponentTemplate();
	}

	private function getGridData($gridId)
	{
		$grid = array(
			'ID' => $gridId,
		);

		$securityContext = $this->storage->getCurrentUserSecurityContext();
		$parameters = array(
			'with' => array('FILE', 'CREATE_USER'),
			'filter' => array(
				'IS_EXPIRED' => false,
				'OBJECT.STORAGE_ID' => $this->storage->getId(),
				'CREATED_BY' => $this->getUser()->getId(),
			),
		);
		$parameters = Driver::getInstance()->getRightsManager()->addRightsCheck($securityContext, $parameters, array('OBJECT_ID', 'OBJECT.CREATED_BY'));
		$items = ExternalLink::getModelList($parameters);

		Collection::sortByColumn($items, array(
			'CREATE_TIME' => array(SORT_NUMERIC, SORT_ASC),
		));

		$urlManager = Driver::getInstance()->getUrlManager();
		$currentUrl = $this->request->getRequestUri();
		$rows = array();
		foreach ($items as $externalLink)
		{
			/** @var ExternalLink $externalLink */
			$exportData = $externalLink->toArray();

			$file = $externalLink->getFile();
			if (!$file)
			{
				continue;
			}

			$nameSpecialChars = htmlspecialcharsbx($file->getName());
			$createDateText = htmlspecialcharsbx((string)$externalLink->getCreateTime());
			$openUrl = $urlManager->getUrlFocusController('openFileDetail', array('fileId' => $file->getId(), 'back' => $currentUrl));
			$columnName = "
				<table class=\"bx-disk-object-name\"><tr>
						<td style=\"width: 45px;\"><div data-object-id=\"{$externalLink->getId()}\" class=\"draggable bx-file-icon-container-small bx-disk-file-icon\"></div></td>
						<td><a class=\"bx-disk-folder-title\" id=\"disk_obj_{$externalLink->getId()}\" href=\"{$openUrl}\" data-bx-dateModify=\"{$createDateText}\">{$nameSpecialChars}</a></td>
				</tr></table>
			";

			$createdByLink = \CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_USER'], array("user_id" => $externalLink->getCreatedBy()));
			$rows[] = array(
				'data' => $exportData,
				'columns' => array(
					'CREATE_TIME' => formatDate('x', $externalLink->getCreateTime()->getTimestamp(), (time() + CTimeZone::getOffset())),
					'UPDATE_TIME' => formatDate('x', $externalLink->getCreateTime()->getTimestamp(), (time() + CTimeZone::getOffset())),
					'NAME' => $columnName,
					'FORMATTED_SIZE' => CFile::formatSize($file->getSize()),
					'DOWNLOAD_COUNT' => $externalLink->getDownloadCount()?: 0,
					'CREATE_USER' => "
						<div class=\"bx-disk-user-link\"><a target='_blank' href=\"{$createdByLink}\" id=\"\">" . htmlspecialcharsbx($externalLink->getCreateUser()->getFormattedName()) . "</a></div>
					",
				),
				'actions' => array(
					 array(
						 "text" => Loc::getMessage('DISK_EXTERNAL_LINK_LIST_ACT_OPEN'),
						 "href" => $openUrl,
					),
					array(
						"text" => Loc::getMessage('DISK_EXTERNAL_LINK_LIST_ACT_DOWNLOAD'),
						"href" => $urlManager->getUrlForDownloadFile($file),
					),
					array(
						"text" => Loc::getMessage('DISK_EXTERNAL_LINK_LIST_ACT_GET_EXTERNAL_LINK'),
						"onclick" => "BX.Disk['ExternalLinkListClass_{$this->getComponentId()}'].showExternalLink({$externalLink->getId()}, {$externalLink->getObjectId()}, '{$this->getShortUrlExternalLink($externalLink)}');",
					),
					array(
						"text" => Loc::getMessage('DISK_EXTERNAL_LINK_LIST_ACT_DISABLE_EXTERNAL_LINK'),
						"onclick" => "BX.Disk['ExternalLinkListClass_{$this->getComponentId()}'].disableExternalLink({$externalLink->getId()}, {$externalLink->getObjectId()});",
					),
				),
			);
		}
		unset($externalLink);

		$grid['MODE'] = 'list';
		$grid['HEADERS'] = $this->getGridHeaders();
		$grid['ROWS'] = $rows;
		$grid['TOTAL_ROWS_COUNT'] = count($rows);
		$grid['FOOTER'] = array();
		$grid['ACTION_PANEL'] = $this->getGroupActions($gridId);

		return $grid;
	}

	protected function getGroupActions($gridId)
	{
		$prefix = $gridId;

		$chooseAction = array('NAME' => Loc::getMessage('DISK_EXTERNAL_LINK_LIST_DEFAULT_ACTION'), 'VALUE' => 'none');
		$disableLink = array(
			'NAME' => Loc::getMessage('DISK_EXTERNAL_LINK_LIST_ACT_DISABLE_EXTERNAL_LINK_SHORT'),
			'CLASS' => 'icon remove',
			'VALUE' => 'delete',
			'ONCHANGE' => array(
				array(
					'ACTION' => Grid\Panel\Actions::CALLBACK,
					'CONFIRM' => true,
					'CONFIRM_APPLY_BUTTON' => Loc::getMessage(
						'DISK_EXTERNAL_LINK_LIST_ACT_DISABLE_EXTERNAL_LINK_SHORT'
					),
					'DATA' => array(
						array(
							'JS' => 'Grid.sendSelected()',
						),
					),
				),
			),
		);

		$dropDownList = array(
			$chooseAction,
			$disableLink
		);

		return array(
			'GROUPS' => array(
				array(
					'ITEMS' => array(
						array(
							"TYPE" => Grid\Panel\Types::DROPDOWN,
							"ID" => "action_button_{$prefix}",
							"NAME" => "action_button_{$prefix}",
							"ITEMS" => $dropDownList
						),
					)
				)
			)
		);
	}

	/**
	 * @return array
	 */
	protected function getGridHeaders()
	{
		return array(
			array(
				'id' => 'ID',
				'name' => 'ID',
				'default' => false,
				'show_checkbox' => true,
			),
			array(
				'id' => 'NAME',
				'name' => Loc::getMessage('DISK_EXTERNAL_LINK_LIST_COLUMN_NAME'),
				'default' => true,
			),
			array(
				'id' => 'CREATE_TIME',
				'name' => Loc::getMessage('DISK_EXTERNAL_LINK_LIST_COLUMN_CREATE_TIME'),
				'default' => true,
			),
			array(
				'id' => 'CREATE_USER',
				'name' => Loc::getMessage('DISK_EXTERNAL_LINK_LIST_COLUMN_CREATE_USER'),
				'default' => false,
			),
			array(
				'id' => 'FORMATTED_SIZE',
				'name' => Loc::getMessage('DISK_EXTERNAL_LINK_LIST_COLUMN_FORMATTED_SIZE'),
				'default' => true,
			),
			array(
				'id' => 'DOWNLOAD_COUNT',
				'name' => Loc::getMessage('DISK_EXTERNAL_LINK_LIST_COLUMN_DOWNLOAD_COUNT'),
				'default' => true,
			),
		);
	}

	protected function getShortUrlExternalLink(ExternalLink $externalLink)
	{
		return $this->getUrlManager()->getShortUrlExternalLink(
			array(
				'hash' => $externalLink->getHash(),
				'action' => 'default',
			),
			true
		);
	}
}