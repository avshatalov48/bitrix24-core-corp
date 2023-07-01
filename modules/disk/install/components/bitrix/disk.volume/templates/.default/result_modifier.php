<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var \Bitrix\Disk\Internals\BaseComponent $component */

use Bitrix\Disk\Ui\FileAttributes;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

Loc::loadMessages(__DIR__ . '/template.php');
Loc::loadMessages(__FILE__);

\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/main/utils.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/main/public_tools.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/main/popup_menu.js');

/** @var \CDiskVolumeComponent $component */
$component = $this->getComponent();


$APPLICATION->setTitle(Loc::getMessage('DISK_VOLUME_PAGE_TITLE'));

if($component->hasErrors())
{
	$error = array_shift($component->getErrors());
	$arResult['ERROR_MESSAGE'] = $error->getMessage();
}

$isBitrix24Template = SITE_TEMPLATE_ID === "bitrix24";

if ($isBitrix24Template)
{
	$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
	$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'pagetitle-toolbar-field-view tasks-pagetitle-view');
}



if (!isset($linkStorageReload) || !is_callable($linkStorageReload))
{
	$linkStorageReload = function (&$row)
	{
		return ' data-storageId="'.$row['STORAGE_ID'].'" data-filterId="'.$row['ID'].'" data-collected="'.(int)$row['COLLECTED'].'"';
	};
}

if (!isset($linkFolderReload) || !is_callable($linkFolderReload))
{
	$linkFolderReload = function (&$row)
	{
		return ' data-storageId="'.$row['STORAGE_ID'].'" data-folderId="'.$row['FOLDER_ID'].'" data-filterId="'.$row['ID'].'" data-collected="'.(int)$row['COLLECTED'].'"';
	};
}


if (!isset($diskTitleFormat) || !is_callable($diskTitleFormat))
{
	/**
	 * @param array $row Result row.
	 * @param \Bitrix\Disk\Storage $storage|null
	 * @return string
	 */
	$diskTitleFormat = function (&$row, &$storage) use ($arParams, $arResult, $component, $linkStorageReload)
	{
		$entityType = '';
		if ($storage instanceof \Bitrix\Disk\Storage)
		{
			$entityType = $storage->getEntityType();
		}

		if ($entityType == \Bitrix\Disk\ProxyType\User::className())
		{
			$extraClass = '';
			if ($row['IS_EMAIL_CRM'])
			{
				$extraClass = 'tasks-grid-username-emailcrm';
			}
			elseif ($row['IS_EMAIL_AUTH'])
			{
				$extraClass = 'tasks-grid-username-email';
			}
			elseif ($row['IS_EXTRANET'])
			{
				$extraClass = 'tasks-grid-username-extranet';
			}
			if ($row['PICTURE'] == \Bitrix\Disk\Ui\Avatar::getDefaultPerson())
			{
				unset($row['PICTURE']);
			}

			$titleFormat =
				'<div class="tasks-grid-username-wrapper '.$extraClass.'">'.
				'<a class="tasks-grid-username disk-volume-storage-link" target="_self" href="'.$row['ACTION_URL'].'" '.$linkStorageReload($row).'>'.
				'<span class="tasks-grid-avatar" '.
				($row['PICTURE'] ? 'style="background-image: url(\''. Uri::urnEncode($row['PICTURE']).'\')"' : '').
				'></span>'.
				'<span class="tasks-grid-username-inner">'.
				$row['TITLE'].
				'</span>'.
				'</a>'.
				'</div>';

		}
		elseif ($entityType == \Bitrix\Disk\ProxyType\Group::className())
		{
			$extraClass = '';
			if ($row['IS_EMAIL_CRM'])
			{
				$extraClass = 'tasks-grid-username-emailcrm';
			}
			elseif ($row['IS_EMAIL_AUTH'])
			{
				$extraClass = 'tasks-grid-username-email';
			}
			elseif ($row['IS_EXTRANET'])
			{
				$extraClass = 'tasks-grid-username-extranet';
			}
			if ($row['PICTURE'] == \Bitrix\Disk\Ui\Avatar::getDefaultGroup())
			{
				unset($row['PICTURE']);
			}

			$titleFormat =
				'<div class="tasks-grid-username-wrapper '.$extraClass.'">'.
				'<a class="tasks-grid-username tasks-grid-groupname disk-volume-storage-link" target="_self" href="'.$row['ACTION_URL'].'" '.$linkStorageReload($row).'>'.
				'<span class="tasks-grid-avatar tasks-grid-avatar-group" '.
				($row['PICTURE'] ? 'style="background-image: url(\''.Uri::urnEncode($row['PICTURE']).'\')"' : '').
				'></span>'.
				'<span class="tasks-grid-username-inner">'.
				$row['TITLE'].
				'</span>'.
				'</a>'.
				'</div>';
		}
		elseif ($entityType == \Bitrix\Disk\ProxyType\Common::className())
		{
			$titleFormat =
				'<div class="tasks-grid-username-wrapper">'.
				'<a class="tasks-grid-username tasks-grid-commonname disk-volume-storage-link" target="_self" href="'.$row['ACTION_URL'].'" '.$linkStorageReload($row).'>'.
				'<span class="tasks-grid-avatar tasks-grid-avatar-common"></span>'.
				'<span class="tasks-grid-username-inner">'.
				$row['TITLE'].
				'</span>'.
				'</a>'.
				'</div>';
		}
		elseif (in_array($entityType, \Bitrix\Disk\Volume\Module\Im::getEntityType()))
		{
			$titleFormat =
				'<div class="tasks-grid-username-wrapper">'.
				'<a class="tasks-grid-username tasks-grid-commonname disk-volume-storage-link" target="_self" href="'.$row['ACTION_URL'].'" '.$linkStorageReload($row).'>'.
				'<span class="tasks-grid-avatar bx-disk-volume-im-icon"></span>'.
				'<span class="tasks-grid-username-inner">'.
				$row['TITLE'].
				'</span>'.
				'</a>'.
				'</div>';
		}
		elseif (in_array($entityType, \Bitrix\Disk\Volume\Module\Mail::getEntityType()))
		{
			$titleFormat =
				'<div class="tasks-grid-username-wrapper">'.
				'<a class="tasks-grid-username tasks-grid-commonname disk-volume-storage-link" target="_self" href="'.$row['ACTION_URL'].'" '.$linkStorageReload($row).'>'.
				'<span class="tasks-grid-avatar bx-disk-volume-mail-icon"></span>'.
				'<span class="tasks-grid-username-inner">'.
				$row['TITLE'].
				'</span>'.
				'</a>'.
				'</div>';
		}
		elseif (in_array($entityType, \Bitrix\Disk\Volume\Module\Documentgenerator::getEntityType()))
		{
			$titleFormat =
				'<div class="tasks-grid-username-wrapper">'.
				'<a class="tasks-grid-username tasks-grid-commonname disk-volume-storage-link" target="_self" href="'.$row['ACTION_URL'].'" '.$linkStorageReload($row).'>'.
				'<span class="tasks-grid-avatar bx-disk-volume-documentgenerator-icon"></span>'.
				'<span class="tasks-grid-username-inner">'.
				$row['TITLE'].
				'</span>'.
				'</a>'.
				'</div>';
		}
		else
		{
			if (isset($row['ACTION_URL']))
			{
				$titleFormat = '<a target="_self" href="'.$row['ACTION_URL'].'" class="bx-disk-volume-storage-link" '.$linkStorageReload($row).'>'.$row['TITLE'].'</a>';
			}
			else
			{
				$titleFormat = $row['TITLE'];
			}
		}


		return $titleFormat;
	};
}

if (!isset($folderTitleFormat) || !is_callable($folderTitleFormat))
{
	/**
	 * @param array $row Result row.
	 * @param \Bitrix\Disk\Storage $storage|null
	 * @return string
	 */
	$folderTitleFormat = function (&$row, &$storage) use ($arParams, $arResult, $component, $linkStorageReload, $linkFolderReload)
	{
		$entityType = '';
		if ($storage instanceof \Bitrix\Disk\Storage)
		{
			$entityType = $storage->getEntityType();
		}

		if (in_array($entityType, \Bitrix\Disk\Volume\Module\Im::getEntityType()))
		{
			$specific = $component->getFragmentResult($row)->getSpecific();
			if ($specific['chat'])
			{
				$specific['chat']['style'] = "bx-disk-volume-im-avatar-".$specific['chat']['type'];

				$titleFormat =
					'<div class="tasks-grid-username-wrapper">'.

					'<a class="tasks-grid-username disk-volume-folder-link" target="_self" href="'.$row['ACTION_URL'].'" '.$linkFolderReload($row).'>'.
					'<span class="bx-disk-volume-im-avatar" '.(!empty($specific['chat']['color']) ? 'style="background-color:'.$specific['chat']['color'].'"' : '').'>'.
						'<span class="bx-disk-volume-im-avatar-default '.$specific['chat']['style'].'" '.(!empty($specific['chat']['avatar']) ? 'style="background-image: url(\''. Uri::urnEncode($specific['chat']['avatar']).'\')"' : '').'></span>'.
					'</span>'.
					'<span class="tasks-grid-username-inner">'.
						Loc::getMessage('DISK_VOLUME_IM_'.mb_strtoupper($specific['chat']['type'])).': '.
						$row['TITLE'].
					'</span>'.
					'</a>'.

					'<br><span class="disk-volume-folder-parent">'.
						Loc::getMessage('DISK_VOLUME_IM_CHAT_OWNER').': '. $specific['chat']['owner_name'].
					'</span>'.
					'</div>';

			}
		}
		else
		{
			$parentsHint = '';
			if (count($row['PARENTS']) > 0)
			{
				$parentsHint = ' onmouseover="BX.hint(this, \''. Loc::getMessage('DISK_VOLUME_PARENT_FOLDER').'\', \'/&nbsp;'. addslashes(implode('&nbsp;/&nbsp; ', $row['PARENTS'])). '&nbsp;/\')"';
			}

			$titleFormat =
				'<div class="tasks-grid-username-wrapper">'.
				'<a class="tasks-grid-username disk-volume-folder-link" target="_self" href="'.$row['ACTION_URL'].'" '.$linkFolderReload($row).'>'.
				'<span class="disk-volume-folder-icon"></span>'.
				'<span class="disk-volume-folder-name" '. $parentsHint. '>'.$row['TITLE'].'</span>'.
				'</a>'.
				'</span>';
		}

		return $titleFormat;
	};
}

if (!isset($fileTitleFormat) || !is_callable($fileTitleFormat))
{
	/**
	 * @param array $row Result row.
	 * @param \Bitrix\Disk\Storage $storage|null
	 * @return string
	 */
	$fileTitleFormat = function (&$row, &$storage) use ($arParams, $arResult, $component)
	{
		/** @var \Bitrix\Disk\Volume\Fragment $fragment */
		$fragment = $component->getFragmentResult($row);

		$titleFormat = $row['TITLE'];

		if (
			$fragment->getIndicatorType() == \Bitrix\Disk\Volume\File::className() ||
			$fragment->getIndicatorType() == \Bitrix\Disk\Volume\FileDeleted::className()
		)
		{
			$iconClass = 'bx-disk-file-icon';
			$dataAttributesForViewer = '';

			/** @var \Bitrix\Disk\File $file */
			$file = $fragment->getFile();
			if ($file instanceof \Bitrix\Disk\File)
			{
				$iconClass = \Bitrix\Disk\Ui\Icon::getIconClassByObject($file);
				$urlManager = \Bitrix\Disk\Driver::getInstance()->getUrlManager();
				$sourceUri = new Uri($urlManager->getUrlForDownloadFile($file));
				$dataAttributesForViewer = FileAttributes::tryBuildByFileId($file->getFileId(), $sourceUri)
					->setObjectId($file->getId())
					->setTitle($file->getName())
					->setGroupBy('volume')
				;
			}

			$entityType = '';
			if ($storage instanceof \Bitrix\Disk\Storage)
			{
				$entityType = $storage->getEntityType();
			}

			if (in_array($entityType, \Bitrix\Disk\Volume\Module\Im::getEntityType()))
			{
				$titleFormat =
					'<span class="bx-file-icon-container-small bx-file-icon-tiny '.$iconClass.'"></span>'.
					'<span class="bx-disk-file-title" id="disk_obj_'.$row['ID'].'" '. $dataAttributesForViewer .'>'.$row['TITLE'].'</span>'.

					'<div class="disk-volume-folder-parent" title="'.Loc::getMessage('DISK_VOLUME_PARENT_FOLDER').'">'.
					Loc::getMessage('DISK_VOLUME_USING_CHAT').': '.
					$row['PARENTS'][0].
					'</div>';
			}
			else
			{

				$parentsHint = '';
				if (count($row['PARENTS']) > 0)
				{
					$parentsHint = ' onmouseover="BX.hint(this, \''. Loc::getMessage('DISK_VOLUME_PARENT_FOLDER').'\', \'/&nbsp;'. addslashes(implode('&nbsp;/&nbsp; ', $row['PARENTS'])). '&nbsp;/\')"';
				}

				$titleFormat =
					'<span class="bx-file-icon-container-small bx-file-icon-tiny '.$iconClass.'"></span>'.
					'<a class="bx-disk-file-title" id="disk_obj_'.$row['ID'].'" href="'.$row['URL'].'" '. $parentsHint. ' '. $dataAttributesForViewer .'>'.$row['TITLE'].'</a>';
			}

		}
		return $titleFormat;
	};
}

if (!isset($fileUsingCountTitle) || !is_callable($fileUsingCountTitle))
{
	/**
	 * @param array $row Result row.
	 * @return string
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\ObjectException
	 */
	$fileUsingCountTitle = function (&$row) use ($arParams, $arResult, $component, $USER)
	{
		$usingCountTitle = Loc::getMessage('DISK_VOLUME_USING_COUNT_NONE');

		/** @var \Bitrix\Disk\Volume\Fragment $fragment */
		$fragment = $component->getFragmentResult($row);

		if (
			$fragment->getIndicatorType() == \Bitrix\Disk\Volume\File::className() ||
			$fragment->getIndicatorType() == \Bitrix\Disk\Volume\FileDeleted::className()
		)
		{
			/** @var \Bitrix\Disk\Volume\IVolumeIndicator $indicator */
			$indicator = $component->getIndicatorResult($row);

			/** @var \Bitrix\Disk\Storage $storage */
			$storage = $fragment->getStorage();

			$entityType = '';
			if ($storage instanceof \Bitrix\Disk\Storage)
			{
				$entityType = $storage->getEntityType();
			}

			/** @var \Bitrix\Disk\File $file */
			$file = $fragment->getFile();

			// Im
			if (in_array($entityType, \Bitrix\Disk\Volume\Module\Im::getEntityType()))
			{
				$row['USING_COUNT'] ++;
			}

			if ($row['USING_COUNT'] > 0)
			{
				$rowId = $row['ID'];

				$usingCount = 0;
				$usingCountMenuLinks = '';

				// Im
				if (in_array($entityType, \Bitrix\Disk\Volume\Module\Im::getEntityType()))
				{
					$usingCount ++;
					$usingCountMenuLinks .=
						'{title: \''. \CUtil::JSEscape(Loc::getMessage("DISK_VOLUME_USING_CHAT")). '\'}';
				}

				if ($file instanceof \Bitrix\Disk\File)
				{
					if ($fragment->getAttachedCount() > 0)
					{
						/** @var \Bitrix\Disk\Volume\File $indicator */
						$attachedObjects = $indicator::getAttachedList($fragment, $USER->getId());
						$usingCount = $row['ATTACHED_COUNT'] = count($attachedObjects);

						foreach ($attachedObjects as $attached)
						{
							if ($usingCountMenuLinks != '')
							{
								$usingCountMenuLinks .= ', ';
							}
							$usingCountMenuLinks .= \CUtil::PhpToJSObject($attached);
						}
						unset($attachedObjects, $attached);
					}

					if ($fragment->getLinkCount() > 0)
					{
						//$fileExtLink = $file->getExternalLinks();
						$usingCount += $fragment->getLinkCount();
						if ($usingCountMenuLinks != '')
						{
							$usingCountMenuLinks .= ', ';
						}
						if ($row['LINK_COUNT'] > 0)
						{
							$usingCountMenuLinks .=
								'{title: \''.Loc::getMessage('DISK_VOLUME_EXTERNAL_LINK_COUNT').': '.$fragment->getLinkCount().'\'}';
						}
					}

					if (isset($row['ACT_COUNT']) && $row['ACT_COUNT'] > 0)
					{
						$crmIndicator = new \Bitrix\Disk\Volume\Module\Crm();
						if ($crmIndicator->isMeasureAvailable())
						{
							$usingCount += $row['ACT_COUNT'];
							if ($usingCountMenuLinks != '')
							{
								$usingCountMenuLinks .= ', ';
							}
							$usingCountMenuLinks .=
								'{title: \''.Loc::getMessage('DISK_VOLUME_CRM_COUNT').': '.$row['ACT_COUNT'].'\'}';
						}
					}
				}
				if ($usingCountMenuLinks != '')
				{
					$usingCountTitle =
						$usingCount."&nbsp;".
						$component->decorateNumber($usingCount, array(
							Loc::getMessage('DISK_VOLUME_USING_COUNT_END1'),
							Loc::getMessage('DISK_VOLUME_USING_COUNT_END2'),
							Loc::getMessage('DISK_VOLUME_USING_COUNT_END3'),
						));

					$usingCountTitle = <<< JSMENU
							<span id="bx-disk-volume-file-using-{$rowId}" class="disk-volume-file-using-count">{$usingCountTitle}</span>
							<script type="text/javascript">
							BX.ready(function(){
								var menuItemsOptions = [{$usingCountMenuLinks}];
								var item, domItems = [];
								for (var j = 0; j < menuItemsOptions.length; j++) 
								{
									item = BX.create(menuItemsOptions[j].url ? "a" : "span", {
										props : {
											className: [
												'menu-popup-item',
												'menu-popup-no-icon'
											].join(' ')
										},
										attrs : {
											// title : menuItemsOptions[j].title,
											href : menuItemsOptions[j].url
										},
										children : [
											BX.create("span", { props : { className: "menu-popup-item-icon"} }),
											BX.create("span", {
												props : {
													className: "menu-popup-item-text"
												},
												html : menuItemsOptions[j].title
											})
										]
									});
									domItems.push(item);
								}
								var popupId = "popupMenuFileUsing{$rowId}";
								var popup = BX.Main.PopupManager.getPopupById(popupId);
								if (popup !== null && popup instanceof BX.Main.Popup)
								{
									popup.destroy();
								}
								popup = new BX.Main.Popup(popupId, BX("bx-disk-volume-file-using-{$rowId}"),
									{
										lightShadow : true,
										offsetTop: 0,
										offsetLeft: 0,
										autoHide: true,
										closeByEsc: true,
										noAllPaddings: true,
										bindOptions: {position: "bottom"},

										content : BX.create("DIV", {
											props : {
												className : "menu-popup"
											},
											html: BX.create("DIV", {
													props : {
														className : "menu-popup-items"
													},
													children: domItems
												}).outerHTML
										}).outerHTML
									}
								);
								BX.bind(BX("bx-disk-volume-file-using-{$rowId}"), "click", function (e) { 
									BX.eventCancelBubble(e);
									BX.fireEvent(document, 'click');
									popup.show();
									return BX.PreventDefault(e);
								});
							});
							</script>
JSMENU;
				}
				else
				{
					$usingCountTitle = Loc::getMessage('DISK_VOLUME_USING_COUNT_NONE');
				}
			}
		}

		return $usingCountTitle;
	};
}




if (!empty($arResult['FileType']['LIST']))
{
	foreach ($arResult['FileType']['LIST'] as &$row)
	{
		try
		{
			$component->decorateResult($row);
		}
		catch (\Bitrix\Main\SystemException $exception)
		{
			continue;
		}
	}
	unset($row);
}

/** @var \Bitrix\Disk\Storage $currentStorage */
$currentStorage = null;

if (!empty($arResult['Storage']['LIST'][0]))
{
	do
	{
		try
		{
			$component->decorateResult($arResult['Storage']['LIST'][0]);
			/** @var \Bitrix\Disk\Volume\IVolumeIndicator $indicator */
			$indicator = $component->getIndicatorResult($arResult['Storage']['LIST'][0]);
			/** @var \Bitrix\Disk\Volume\Fragment $fragment */
			$fragment = $component->getFragmentResult($arResult['Storage']['LIST'][0]);
		}
		catch (\Bitrix\Main\SystemException $exception)
		{
			break;
		}

		if (!$fragment instanceof \Bitrix\Disk\Volume\Fragment)
		{
			break;
		}

		$currentStorage = $fragment->getStorage();

		if (!$arResult['ADMIN_MODE'] && ($currentStorage instanceof \Bitrix\Disk\Storage))
		{
			$arResult['Storage']['TITLE'] = htmlspecialcharsbx($currentStorage->getProxyType()->getTitleForCurrentUser());
		}
		else
		{
			$arResult['Storage']['TITLE'] = htmlspecialcharsbx($indicator::getTitle($fragment));
		}

		$component->decorateStorageIcon($arResult['Storage'], $currentStorage, 70, 70);

		if (
			!empty($arResult['Storage']['STYLE']) &&
			$arResult['Storage']['STYLE'] === 'User' &&
			isset($arResult['Storage']['PICTURE']) &&
			$arResult['Storage']['PICTURE'] == \Bitrix\Disk\Ui\Avatar::getDefaultPerson()
		)
		{
			unset($arResult['Storage']['PICTURE']);
		}

		if (
			!empty($arResult['Storage']['STYLE']) &&
			$arResult['Storage']['STYLE'] === 'Group' &&
			isset($arResult['Storage']['PICTURE']) &&
			$arResult['Storage']['PICTURE'] == \Bitrix\Disk\Ui\Avatar::getDefaultGroup()
		)
		{
			unset($arResult['Storage']['PICTURE']);
		}
	}
	while(false);
}


switch ($arResult['ACTION'])
{
	case $component::ACTION_DISKS:
	{
		$resId = $arResult['INDICATOR'];

		$arResult['GRID_DATA'] = array();
		foreach ($arResult[$resId]['LIST'] as &$row)
		{
			try
			{
				$component->decorateResult($row);
				$component->decorateResultActionUrl($row);
				/** @var \Bitrix\Disk\Volume\IVolumeIndicator $indicator */
				$indicator = $component->getIndicatorResult($row);
				/** @var \Bitrix\Disk\Volume\Fragment $fragment */
				$fragment = $component->getFragmentResult($row);
			}
			catch (\Bitrix\Main\SystemException $exception)
			{
				continue;
			}

			if (!$fragment instanceof \Bitrix\Disk\Volume\Fragment)
			{
				continue;
			}

			$storage = $fragment->getStorage();
			if (!$storage instanceof \Bitrix\Disk\Storage)
			{
				continue;
			}


			// trashcan filter id
			$trashcanFilterId = -1;
			if ($indicator::getIndicatorId() == \Bitrix\Disk\Volume\Storage\TrashCan::getIndicatorId())
			{
				$trashcanFilterId = $row['ID'];
				$row['TRASHCAN_SIZE'] = $row['FILE_SIZE'];
				$row['TRASHCAN_SIZE_FORMAT'] = $row['FILE_SIZE_FORMAT'];
				// preview
				if ($row['PREVIEW_SIZE'] > 0)
				{
					$row['FILE_SIZE'] += $row['PREVIEW_SIZE'];
					$row['TRASHCAN_SIZE'] += $row['PREVIEW_SIZE'];
					$row['TRASHCAN_SIZE_FORMAT'] = \CFile::formatSize($row['TRASHCAN_SIZE']);
				}
			}
			else
			{
				foreach ($arResult['TrashCan']['LIST'] as $trashCan)
				{
					if ($trashCan['STORAGE_ID'] == $row['STORAGE_ID'])
					{
						$trashcanFilterId = $trashCan['ID'];
						$row['TRASHCAN_SIZE'] = $trashCan['FILE_SIZE'];

						$row['FILE_SIZE'] += $trashCan['FILE_SIZE'];
						// preview
						if ($trashCan['PREVIEW_SIZE'] > 0)
						{
							$row['FILE_SIZE'] += $trashCan['PREVIEW_SIZE'];
							$trashCan['FILE_SIZE'] += $trashCan['PREVIEW_SIZE'];
						}

						$row['TRASHCAN_SIZE_FORMAT'] = \CFile::formatSize($trashCan['FILE_SIZE']);

						break;
					}
				}
			}
			// preview
			if ($row['PREVIEW_SIZE'] > 0)
			{
				$row['FILE_SIZE'] += $row['PREVIEW_SIZE'];
			}
			$row['FILE_SIZE_FORMAT'] = \CFile::formatSize($row['FILE_SIZE']);

			$actions = array();

			$actions[] = array(
				'text' => Loc::getMessage('DISK_VOLUME_MORE'),
				'onclick' => "BX.Disk.measureManager.showStorageMeasure(". $row['ID']. ", '". \CUtil::JSEscape($row['ACTION_URL']). "');",
				'default' => true,
			);

			if ($arResult["ADMIN_MODE"])
			{
				$actions[] = $component->getMenuItemDiskSendNotification($row, $storage);
				$actions[] = $component->getMenuItemDiskClearance($row, $storage);
			}

			if (isset($row['URL']))
			{
				$actions[] = array(
					"text" => Loc::getMessage('DISK_VOLUME_OPEN'),
					'href' => $row['URL'],
				);
			}

			$row['ACTIONS'] = $actions;

			$component->decorateStorageIcon($row, $storage);

			$row['TITLE_FORMAT'] = $diskTitleFormat($row, $storage);

			$arResult['GRID_DATA'][] = array(
				'id' => $row['ID'],
				'data' => $row,
				'columns' => array(
					'TITLE' => $row['TITLE_FORMAT'],
					'PERCENT' => $row['PERCENT'].'&nbsp;%',
					'FILE_COUNT' => $row['FILE_COUNT'],
					'VERSION_COUNT' => $row['VERSION_COUNT'],
					'UNNECESSARY_VERSION_SIZE' => ($row['UNNECESSARY_VERSION_SIZE'] > 0) ? $row['UNNECESSARY_VERSION_SIZE_FORMAT'] : '&ndash;',
					'UNNECESSARY_VERSION_COUNT' => ($row['UNNECESSARY_VERSION_COUNT'] > 0 ? $row['UNNECESSARY_VERSION_COUNT'] : '&ndash;'),
					'FILE_SIZE' => ($row['FILE_SIZE'] > 0) ? $row['FILE_SIZE_FORMAT'] : '&ndash;',
					'TRASHCAN_SIZE' => ($row['TRASHCAN_SIZE'] > 0) ? $row['TRASHCAN_SIZE_FORMAT'] : '&ndash;',
				),
				'actions' => $row['ACTIONS'],
				'attrs' => array(
					"data-indicatorId" => $indicator::getIndicatorId(),
					"data-storageId" => $storage->getId(),
					"data-filteridTrashcan" => $trashcanFilterId,
					"data-filterId" => $row['ID'],
					"data-collected" => (int)$row['COLLECTED'],
				),
			);
		}
		unset($row);

		$arResult['GROUP_ACTIONS'] = $component->getGridMenuGroupActions($component::ACTION_DISKS);
		break;
	}


	case $component::ACTION_STORAGE:
	{
		$resId = $arResult['INDICATOR'];

		$arResult['GRID_DATA'] = array();

		foreach ($arResult[$resId]['LIST'] as &$row)
		{
			try
			{
				$component->decorateResult($row);
				$component->decorateResultActionUrl($row);
				/** @var \Bitrix\Disk\Volume\IVolumeIndicator $indicator */
				$indicator = $component->getIndicatorResult($row);
			}
			catch (\Bitrix\Main\SystemException $exception)
			{
				continue;
			}

			// preview
			if ($row['PREVIEW_SIZE'] > 0)
			{
				$row['FILE_SIZE'] += $row['PREVIEW_SIZE'];
				$row['FILE_SIZE_FORMAT'] = \CFile::formatSize($row['FILE_SIZE']);
			}

			if ($arResult['STORAGE_ROOT_ID'] == $row['FOLDER_ID'])
			{
				$row['TITLE'] = '<i>'.htmlspecialcharsbx('<'.Loc::getMessage('DISK_VOLUME_ROOT_FILES').'>').'</i>';
				$row['TITLE_FORMAT'] = $folderTitleFormat($row, $currentStorage);
			}
			else
			{
				$row['TITLE_FORMAT'] = $folderTitleFormat($row, $currentStorage);
			}

			$row['ACTIONS'] = $component->getFolderActionMenu($row, $currentStorage);

			$arResult['GRID_DATA'][] = array(
				'id' => $row['ID'],
				'data' => $row,
				'columns' => array(
					'TITLE' => $row['TITLE_FORMAT'],
					'UPDATE_TIME' => $row['UPDATE_TIME'],
					'VERSION_COUNT' => $row['VERSION_COUNT'],
					'UNNECESSARY_VERSION_SIZE' => ($row['UNNECESSARY_VERSION_SIZE'] > 0) ? $row['UNNECESSARY_VERSION_SIZE_FORMAT'] : '&ndash;',
					'UNNECESSARY_VERSION_COUNT' => ($row['UNNECESSARY_VERSION_COUNT'] > 0 ? $row['UNNECESSARY_VERSION_COUNT'] : '&ndash;'),
					'FILE_SIZE' => ($row['FILE_SIZE'] > 0) ? $row['FILE_SIZE_FORMAT'] : '&ndash;',
					'FILE_COUNT' => $row['FILE_COUNT'],
					'PERCENT' => $row['PERCENT'].'&nbsp;%',
				),
				'actions' => $row['ACTIONS'],
				'attrs' => array(
					"data-indicatorId" => $indicator::getIndicatorId(),
					"data-storageId" => $row['STORAGE_ID'],
				),
				"has_child" => true,
			);
		}
		unset($row);

		$arResult['GROUP_ACTIONS'] = $component->getGridMenuGroupActionsStorage($component::ACTION_STORAGE, $currentStorage);

		break;
	}


	case $component::ACTION_FILES:
	{
		$resId = $arResult['INDICATOR'];
		$isTrashcan = ($arResult['INDICATOR'] === \Bitrix\Disk\Volume\FileDeleted::getIndicatorId());

		$arResult['GRID_DATA'] = array();
		foreach ($arResult[$resId]['LIST'] as &$row)
		{
			try
			{
				$component->decorateResult($row);
				$component->decorateResultActionUrl($row);
				/** @var \Bitrix\Disk\Volume\File $indicator */
				$indicator = $component->getIndicatorResult($row);
				/** @var \Bitrix\Disk\Volume\Fragment $fragment */
				$fragment = $component->getFragmentResult($row);
			}
			catch (\Bitrix\Main\SystemException $exception)
			{
				continue;
			}

			// preview
			if ($row['PREVIEW_SIZE'] > 0)
			{
				$row['FILE_SIZE'] += $row['PREVIEW_SIZE'];
				$row['FILE_SIZE_FORMAT'] = \CFile::formatSize($row['FILE_SIZE']);
			}

			$row['TITLE_FORMAT'] = $fileTitleFormat($row, $currentStorage);
			$row['USING_COUNT'] = $fileUsingCountTitle($row);

			if ($isTrashcan)
			{
				$row['ACTIONS'] = $component->getDeletedFileActionMenu($row);
			}
			else
			{
				$row['ACTIONS'] = $component->getFileActionMenu($row, $currentStorage);
			}

			$arResult['GRID_DATA'][] = array(
				'id' => $row['ID'],
				'data' => $row,
				'columns' => array(
					'TITLE' => $row['TITLE_FORMAT'],
					'SIZE_FILE' => ($row['SIZE_FILE'] > 0 ? $row['SIZE_FILE_FORMAT'] : '&ndash;'),
					'UPDATE_TIME' => $row['UPDATE_TIME'],
					'USING_COUNT' => $row['USING_COUNT'],
					'VERSION_COUNT' => $row['VERSION_COUNT'],
					'VERSION_SIZE' => ($row['VERSION_SIZE'] > 0 ? $row['VERSION_SIZE_FORMAT'] : '&ndash;'),
					'UNNECESSARY_VERSION_SIZE' => ($row['UNNECESSARY_VERSION_SIZE'] > 0 ? $row['UNNECESSARY_VERSION_SIZE_FORMAT'] : '&ndash;'),
					'UNNECESSARY_VERSION_COUNT' => ($row['UNNECESSARY_VERSION_COUNT'] > 0 ? $row['UNNECESSARY_VERSION_COUNT'] : '&ndash;'),
				),
				'actions' => $row['ACTIONS'],
				'attrs' => array(
					"data-indicatorId" => $indicator::getIndicatorId(),
					"data-storageId" => $row['STORAGE_ID'],
				),

			);
		}
		unset($row);

		$arResult['GROUP_ACTIONS'] = $component->getGridMenuGroupActions($isTrashcan ? $component::ACTION_TRASH_FILES : $component::ACTION_FILES);

		break;
	}
}

