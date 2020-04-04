<?php

namespace Bitrix\Disk\Ui;


use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Document\DocumentHandler;
use Bitrix\Disk\Driver;
use Bitrix\Disk\ExternalLink;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\BaseObject;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\Version;
use CFile;

/**
 * Class Viewer
 *
 * Helps working with core_viewer.js.
 * @package Bitrix\Disk\Ui
 */
final class Viewer
{
	/**
	 * @param $extension
	 * @return bool
	 */
	private static function isViewable($extension)
	{
		static $allowedFormat = array(
			'txt' => 'txt',
			'.txt' => 'txt',
			'pdf' => 'pdf',
			'.pdf' => 'pdf',
			'doc' => 'doc',
			'.doc' => '.doc',
			'docx' => 'docx',
			'.docx' => '.docx',
			'xls' => 'xls',
			'.xls' => '.xls',
			'xlsx' => 'xlsx',
			'.xlsx' => '.xlsx',
			'ppt' => 'ppt',
			'.ppt' => '.ppt',
			'pptx' => 'pptx',
			'.pptx' => '.pptx',
			'.xodt' => '.xodt',
			'xodt' => 'xodt',
		);

		return isset($allowedFormat[$extension]) || isset($allowedFormat[strtolower($extension)]);
	}

	/**
	 * @param $extension
	 * @return bool
	 */
	private static function isOnlyViewable($extension)
	{
		static $allowedFormat = array(
			'txt' => 'txt',
			'.txt' => 'txt',
			'pdf' => 'pdf',
			'.pdf' => 'pdf',
		);

		return isset($allowedFormat[$extension]) || isset($allowedFormat[strtolower($extension)]);
	}

	/**
	 * Gets data attributes by file array to viewer..
	 * @param array $file File array (specific).
	 * @return string
	 */
	public static function getAttributesByArray(array $file)
	{
		return ""
		. " data-bx-baseElementId=\"disk-attach-{$file['ID']}\""
		. " data-bx-isFromUserLib=\"" . (empty($file['IN_PERSONAL_LIB'])? '' : 1) ."\""
		;
	}

	/**
	 * Gets data attributes by object (folder or file) to viewer.
	 * @param File|Folder|BaseObject $object Target object.
	 * @param array                  $additionalParams Additional parameters 'relativePath', 'externalId', 'canUpdate', 'showStorage'.
	 * @return string
	 */
	public static function getAttributesByObject(BaseObject $object, array $additionalParams = array())
	{
		$urlManager = Driver::getInstance()->getUrlManager();

		$name = $object->getName();
		$dateTime = $object->getUpdateTime();
		if($object instanceof Folder)
		{
			$user = $object->getCreateUser();
			$dataAttributesForViewer =
				'data-bx-viewer="folder" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-src="" ' .
				'data-bx-owner="' . htmlspecialcharsbx($user? $user->getFormattedName() : '') . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($dateTime) . '" '
			;
			return $dataAttributesForViewer;
		}
		if(!$object instanceof File)
		{
			return '';
		}

		if($object->getView()->getId() && $object->getView()->getJsViewerType())
		{
			$dataAttributesForViewer =
				'data-bx-viewer="'.$object->getView()->getJsViewerType().'" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-src="' . $urlManager->getUrlForShowViewHtml($object, array('sizeType' => 'absolute')) . '" ' .
				'data-bx-iframeSrc="' . $urlManager->getUrlToShowFileByService($object->getId(), 'gvdrive') . '" ' .
				'data-bx-transformTimeout="' . $object->getView()->getTransformTime() . '" ' .
				'data-bx-isFromUserLib="" ' .
				'data-bx-askConvert="' . (DocumentHandler::isNeedConvertExtension($object->getExtension())? '1':'') . '" ' .
				'data-bx-download="' . $urlManager->getUrlForDownloadFile($object) . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($object->getSize())) . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($dateTime) . '" ' .
				'data-bx-width="' . htmlspecialcharsbx($object->getView()->getJsViewerWidth()) . '" ' .
				'data-bx-height="' . htmlspecialcharsbx($object->getView()->getJsViewerHeight()) . '" ';
			;
			if($object->getView()->getJsViewerFallbackHtmlAttributeName())
			{
				$dataAttributesForViewer .= $object->getView()->getJsViewerFallbackHtmlAttributeName().'="'.$urlManager->getUrlForShowViewHtml($object, array('mode' => 'iframe')).'" ';
			}
			if($object->getView()->isJsViewerHideEditButton())
			{
				$dataAttributesForViewer .= 'data-bx-hideEdit="1" ';
			}
		}
		elseif(DocumentHandler::isEditable($object->getExtension()))
		{
			$dataAttributesForViewer =
				'data-bx-viewer="iframe" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-src="' . $urlManager->getUrlToShowFileByService($object->getId(), 'gvdrive') . '" ' .
				'data-bx-isFromUserLib="" ' .
				'data-bx-askConvert="' . (DocumentHandler::isNeedConvertExtension($object->getExtension())? '1':'') . '" ' .
				'data-bx-download="' . $urlManager->getUrlForDownloadFile($object) . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($object->getSize())) . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($dateTime) . '" '
			;
			if($object->getView()->getId() && $object->getView()->getJsViewerFallbackHtmlAttributeName())
			{
				$dataAttributesForViewer .= $object->getView()->getJsViewerFallbackHtmlAttributeName().'="'.$urlManager->getUrlForShowViewHtml($object, array('mode' => 'iframe')).'" ';
			}
		}
		elseif(self::isViewable($object->getExtension()))
		{
			$dataAttributesForViewer =
				'data-bx-viewer="iframe" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-src="' . $urlManager->getUrlToShowFileByService($object->getId(), 'gvdrive') . '" ' .
				'data-bx-isFromUserLib="" ' .
				'data-bx-download="' . $urlManager->getUrlForDownloadFile($object) . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($object->getSize())) . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($dateTime) . '" '
			;
			if($object->getView()->getId() && $object->getView()->getJsViewerFallbackHtmlAttributeName())
			{
				$dataAttributesForViewer .= $object->getView()->getJsViewerFallbackHtmlAttributeName().'="'.$urlManager->getUrlForShowViewHtml($object, array('mode' => 'iframe')).'" ';
			}
		}
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		elseif(TypeFile::isImage($object))
		{
			$dataAttributesForViewer =
				'data-bx-viewer="image" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-src="' . $urlManager->getUrlForDownloadFile($object) . '" ' .

				'data-bx-isFromUserLib="" ' .

				'data-bx-download="' . $urlManager->getUrlForDownloadFile($object) . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($dateTime) . '" '
			;
		}
		else
		{
			$user = $object->getCreateUser();
			$dataAttributesForViewer =
				'data-bx-viewer="unknown" ' .
				'data-bx-src="' . $urlManager->getUrlForDownloadFile($object) . '" ' .

				'data-bx-isFromUserLib="" ' .

				'data-bx-download="' . $urlManager->getUrlForDownloadFile($object) . '" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-owner="' . htmlspecialcharsbx($user? $user->getFormattedName() : '') . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($object->getSize())) . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($dateTime) . '" '
			;
		}
		$dataAttributesForViewer .=
			" bx-attach-file-id=\"{$object->getId()}\"" .
			" data-bx-version=\"\"" .
			" data-bx-history=\"\"" .
			" data-bx-historyPage=\"\""
		;

		if(!empty($additionalParams['relativePath']))
		{
			$dataAttributesForViewer .= ' data-bx-relativePath="' . htmlspecialcharsbx($additionalParams['relativePath'] . '/' . $name) . '" ';
		}
		if(!empty($additionalParams['externalId']))
		{
			$dataAttributesForViewer .= ' data-bx-externalId="' . htmlspecialcharsbx($additionalParams['externalId']) . '" ';
		}
		if(!empty($additionalParams['canUpdate']))
		{
			$dataAttributesForViewer .= ' data-bx-edit="' . $urlManager->getUrlForStartEditFile($object->getId(), 'gdrive') . '" ';
		}
		if(!empty($additionalParams['showStorage']))
		{
			$dataAttributesForViewer .= ' data-bx-storage="' . htmlspecialcharsbx($object->getParent()->getName()) . '" ';
		}
		if(!empty($additionalParams['lockedBy']) && $object->getLock())
		{
			$dataAttributesForViewer .= ' data-bx-lockedBy="' . $object->getLock()->getCreatedBy() . '" ';
		}

		return $dataAttributesForViewer;
	}

	public static function getAttributesByExternalLink(ExternalLink $externalLink, BaseObject $dependedObject = null, array $additionalParams = array())
	{
		$object = $dependedObject?: $externalLink->getObject();

		$urlManager = Driver::getInstance()->getUrlManager();
		$path = $additionalParams['path'];
		$token = $additionalParams['token'];
		$hash = $externalLink->getHash();
		$urlForDownloadFile = $additionalParams['urlForDownload'];
		$disableDocumentViewer = !empty($additionalParams['disableDocumentViewer']);

		$name = $object->getName();
		$dateTime = $object->getUpdateTime();
		if($object instanceof Folder)
		{
			$dataAttributesForViewer =
				'data-bx-viewer="folder" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-src="" ' .
				'data-bx-owner="-" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($dateTime) . '" '
			;
			return $dataAttributesForViewer;
		}
		if(!$object instanceof File)
		{
			return '';
		}

		if(!$disableDocumentViewer && $object->getView()->getId() && $object->getView()->getJsViewerType())
		{
			$pathToView = Driver::getInstance()->getUrlManager()->getUrlExternalLink(array(
				'hash' => $externalLink->getHash(),
				'action' => 'showView',
				'token' => $token,
				'path' => $path,
				'fileId' => $object->getId(),
				'ts' => $object->getUpdateTime()->getTimestamp(),
				'ncc' => 1,
			));

			$viewUrl = Driver::getInstance()->getUrlManager()->getUrlExternalLink(array(
				'hash' => $externalLink->getHash(),
				'action' => 'showViewHtml',
				'pathToView' => $pathToView,
				'token' => $token,
				'path' => $path,
				'fileId' => $object->getId(),
				'ts' => $object->getUpdateTime()->getTimestamp(),
				'ncc' => 1,
			));

			$dataAttributesForViewer =
				'data-bx-viewer="'.$object->getView()->getJsViewerType().'" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-src="' . $viewUrl . '" ' .
				'data-bx-iframeSrc="" ' .
				'data-bx-transformTimeout="' . $object->getView()->getTransformTime() . '" ' .
				'data-bx-isFromUserLib="" ' .
				'data-bx-askConvert="" ' .
				'data-bx-download="' . $urlForDownloadFile . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($object->getSize())) . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($dateTime) . '" ' .
				'data-bx-width="' . htmlspecialcharsbx($object->getView()->getJsViewerWidth()) . '" ' .
				'data-bx-height="' . htmlspecialcharsbx($object->getView()->getJsViewerHeight()) . '" ';
			;
		}
		elseif(!$disableDocumentViewer && self::isViewable($object->getExtension()))
		{
			$viewUrl = Driver::getInstance()->getUrlManager()->getUrlExternalLink(array(
				'hash' => $externalLink->getHash(),
				'action' => 'showByGoogleViewer',
				'token' => $token,
				'path' => $path,
				'fileId' => $object->getId(),
				'ncc' => 1,
			));

			$dataAttributesForViewer =
				'data-bx-viewer="iframe" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-src="' . $viewUrl . '" ' .
				'data-bx-isFromUserLib="" ' .
				'data-bx-download="' . $urlForDownloadFile . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($object->getSize())) . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($dateTime) . '" '
			;
		}
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		elseif(TypeFile::isImage($object))
		{
			$dataAttributesForViewer =
				'data-bx-viewer="image" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-src="' . $urlForDownloadFile . '" ' .

				'data-bx-isFromUserLib="" ' .

				'data-bx-download="' . $urlForDownloadFile . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($dateTime) . '" '
			;
		}
		else
		{
			$dataAttributesForViewer =
				'data-bx-viewer="unknown" ' .
				'data-bx-src="' . $urlForDownloadFile . '" ' .

				'data-bx-isFromUserLib="" ' .

				'data-bx-download="' . $urlForDownloadFile . '" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-owner="" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($object->getSize())) . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($dateTime) . '" '
			;
		}
		$dataAttributesForViewer .=
			" bx-attach-file-id=\"{$object->getId()}\"" .
			" data-bx-version=\"\"" .
			" data-bx-hideEdit=\"1\"" .
			" data-bx-history=\"\"" .
			" data-bx-historyPage=\"\""
		;

		return $dataAttributesForViewer;
	}

	/**
	 * Gets data attributes by version to viewer.
	 * @param Version $version Target version.
	 * @param array   $additionalParams Additional parameters 'canUpdate', 'showStorage'.
	 * @return string
	 */
	public static function getAttributesByVersion(Version $version, array $additionalParams = array())
	{
		$object = $version->getObject();
		$objectId = $object->getId();
		$urlManager = Driver::getInstance()->getUrlManager();

		if($version->getView()->getId() && $version->getView()->getJsViewerType())
		{
			$dataAttributesForViewer =
				'data-bx-viewer="'.$version->getView()->getJsViewerType().'" ' .
				'data-bx-title="' . htmlspecialcharsbx($version->getName()) . '" ' .
				'data-bx-src="' . $urlManager->getUrlForShowVersionViewHtml($version, array('sizeType' => 'absolute')) . '" ' .
				'data-bx-iframeSrc="' . $urlManager->getUrlToShowVersionByService($objectId, $version->getId(), 'gvdrive') . '" ' .
				'data-bx-transformTimeout="' . $version->getView()->getTransformTime() . '" ' .
				'data-bx-isFromUserLib="" ' .
				'data-bx-askConvert="' . (DocumentHandler::isNeedConvertExtension($version->getExtension())? '1':'') . '" ' .
				'data-bx-download="' . $urlManager->getUrlForDownloadVersion($version) . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($version->getSize())) . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($version->getCreateTime()) . '" ' .
				'data-bx-width="' . htmlspecialcharsbx($version->getView()->getJsViewerWidth()) . '" ' .
				'data-bx-height="' . htmlspecialcharsbx($version->getView()->getJsViewerHeight()) . '" ' .
				'data-bx-getLastVersionUri="' . $urlManager->getUrlToGetLastVersionUriByFile($objectId) . '" '
			;
			if($object->getView()->getJsViewerFallbackHtmlAttributeName())
			{
				$dataAttributesForViewer .= $object->getView()->getJsViewerFallbackHtmlAttributeName().'="'.$urlManager->getUrlForShowViewHtml($object, array('mode' => 'iframe')).'" ';
			}
			if($object->getView()->isJsViewerHideEditButton())
			{
				$dataAttributesForViewer .= 'data-bx-hideEdit="1" ';
			}
		}
		elseif(DocumentHandler::isEditable($version->getExtension()))
		{
			$dataAttributesForViewer =
				'data-bx-viewer="iframe" ' .
				'data-bx-title="' . htmlspecialcharsbx($version->getName()) . '" ' .
				'data-bx-src="' . $urlManager->getUrlToShowVersionByService($objectId, $version->getId(), 'gvdrive') . '" ' .
				'data-bx-isFromUserLib="" ' .
				'data-bx-askConvert="' . (DocumentHandler::isNeedConvertExtension($version->getExtension())? '1':'') . '" ' .
				'data-bx-download="' . $urlManager->getUrlForDownloadVersion($version) . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($version->getSize())) . '" ' .
				'data-bx-getLastVersionUri="' . $urlManager->getUrlToGetLastVersionUriByFile($objectId) . '" '
			;
			if($version->getView()->getId() && $version->getView()->getJsViewerFallbackHtmlAttributeName())
			{
				$dataAttributesForViewer .= $version->getView()->getJsViewerFallbackHtmlAttributeName().'="'.$urlManager->getUrlForShowVersionViewHtml($version, array('mode' => 'iframe')).'" ';
			}
			if($object->getView()->isTransformationAllowedOnOpen($object->getSize()))
			{
				$dataAttributesForViewer .= 'data-bx-transform="'.$urlManager->getUrlForTransformOnOpen($object).'" ';
			}
		}
		elseif(self::isViewable($object->getExtension()))
		{
			$dataAttributesForViewer =
				'data-bx-viewer="iframe" ' .
				'data-bx-title="' . htmlspecialcharsbx($version->getName()) . '" ' .
				'data-bx-src="' . $urlManager->getUrlToShowVersionByService($objectId, $version->getId(), 'gvdrive') . '" ' .
				'data-bx-isFromUserLib="" ' .
				'data-bx-download="' . $urlManager->getUrlForDownloadVersion($version) . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($version->getSize())) . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($version->getCreateTime()) . '" '
			;
			if($version->getView()->getId() && $version->getView()->getJsViewerFallbackHtmlAttributeName())
			{
				$dataAttributesForViewer .= $version->getView()->getJsViewerFallbackHtmlAttributeName().'="'.$urlManager->getUrlForShowVersionViewHtml($version, array('mode' => 'iframe')).'" ';
			}
		}
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		elseif(TypeFile::isImage($object))
		{
			$dataAttributesForViewer =
				'data-bx-viewer="image" ' .
				'data-bx-title="' . htmlspecialcharsbx($object->getName()) . '" ' .
				'data-bx-src="' . $urlManager->getUrlForDownloadVersion($version) . '" ' .

				'data-bx-isFromUserLib="" ' .

				'data-bx-download="' . $urlManager->getUrlForDownloadVersion($version) . '" '
			;
		}
		else
		{
			$dataAttributesForViewer =
				'data-bx-viewer="unknown" ' .
				'data-bx-src="' . $urlManager->getUrlForDownloadVersion($version) . '" ' .

				'data-bx-isFromUserLib="" ' .

				'data-bx-download="' . $urlManager->getUrlForDownloadVersion($version) . '" ' .
				'data-bx-title="' . htmlspecialcharsbx($object->getName()) . '" ' .
				'data-bx-owner="' . htmlspecialcharsbx($version->getCreateUser()? $version->getCreateUser()->getFormattedName() : '') . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($version->getCreateTime()) . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($version->getSize())) . '" '
			;
		}
		$dataAttributesForViewer .=
			" data-bx-version=\"" . $version->getGlobalContentVersion() . "\"" .
			" data-bx-history=\"\"" .
			" data-bx-historyPage=\"\""
		;

		if(!empty($additionalParams['canUpdate']))
		{
			$dataAttributesForViewer .= ' data-bx-edit="' . $urlManager->getUrlForStartEditVersion($objectId, $version->getId(), 'gdrive') . '" ';
		}
		if(!empty($additionalParams['showStorage']))
		{
			$dataAttributesForViewer .= ' data-bx-storage="' . htmlspecialcharsbx($object->getParent()->getName()) . '" ';
		}

		return $dataAttributesForViewer;
	}

	/**
	 * Gets data attributes by attached object to viewer.
	 * @param AttachedObject $attachedObject Target attached object.
	 * @param array          $additionalParams Additional parameters 'relativePath', 'externalId', 'canUpdate', 'canFakeUpdate', 'showStorage', 'version'.
	 * @return string
	 */
	public static function getAttributesByAttachedObject(AttachedObject $attachedObject, array $additionalParams = array())
	{
		$urlManager = Driver::getInstance()->getUrlManager();

		$version = $object = null;
		if($attachedObject->isSpecificVersion())
		{
			$version = $attachedObject->getVersion();
			if(!$version)
			{
				return '';
			}
			$name = $version->getName();
			$extension = $version->getExtension();
			$size = $version->getSize();
			$updateTime  = $version->getCreateTime();
		}
		else
		{
			$object = $attachedObject->getObject();
			if(!$object)
			{
				return '';
			}

			$name = $object->getName();
			$extension = $object->getExtension();
			$size = $object->getSize();
			$updateTime  = $object->getUpdateTime();
		}

		if($object instanceof File && $object->getView()->getId() && $object->getView()->getJsViewerType())
		{
			$dataAttributesForViewer =
				'data-bx-viewer="'.$object->getView()->getJsViewerType().'" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-src="' . $urlManager->getUrlForShowAttachedFileViewHtml($attachedObject->getId(), array('sizeType' => 'absolute'), $object->getUpdateTime()->getTimestamp()) . '" ' .
				'data-bx-iframeSrc="' . $urlManager->getUrlToShowAttachedFileByService($attachedObject->getId(), 'gvdrive') . '" ' .
				'data-bx-transformTimeout="' . $object->getView()->getTransformTime() . '" ' .
				'data-bx-isFromUserLib="" ' .
				'data-bx-askConvert="' . (DocumentHandler::isNeedConvertExtension($extension)? '1':'') . '" ' .
				'data-bx-download="' . $urlManager->getUrlUfController('download', array('attachedId' => $attachedObject->getId())) . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($size)) . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($updateTime) . '" ' .
				'data-bx-width="' . htmlspecialcharsbx($object->getView()->getJsViewerWidth()) . '" ' .
				'data-bx-height="' . htmlspecialcharsbx($object->getView()->getJsViewerHeight()) . '" '
			;
			if($object->getView()->getJsViewerFallbackHtmlAttributeName())
			{
				$dataAttributesForViewer .= $object->getView()->getJsViewerFallbackHtmlAttributeName().'="'.$urlManager->getUrlForShowAttachedFileViewHtml($attachedObject->getId(), array('mode' => 'iframe'), $object->getUpdateTime()->getTimestamp()).'" ';
			}
			if($object->getView()->isJsViewerHideEditButton())
			{
				$dataAttributesForViewer .= 'data-bx-hideEdit="1" ';
			}
		}
		elseif($version instanceof Version && $version->getView()->getId() && $version->getView()->getJsViewerType())
		{
			$dataAttributesForViewer =
				'data-bx-viewer="'.$version->getView()->getJsViewerType().'" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-src="' . $urlManager->getUrlForShowAttachedVersionViewHtml($attachedObject->getId()) . '" ' .
				'data-bx-iframeSrc="' . $urlManager->getUrlToShowAttachedFileByService($attachedObject->getId(), 'gvdrive') . '" ' .
				'data-bx-transformTimeout="' . $version->getView()->getTransformTime() . '" ' .
				'data-bx-isFromUserLib="" ' .
				'data-bx-askConvert="' . (DocumentHandler::isNeedConvertExtension($extension)? '1':'') . '" ' .
				'data-bx-download="' . $urlManager->getUrlUfController('download', array('attachedId' => $attachedObject->getId())) . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($size)) . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($updateTime) . '" ' .
				'data-bx-width="' . htmlspecialcharsbx($version->getView()->getJsViewerWidth()) . '" ' .
				'data-bx-height="' . htmlspecialcharsbx($version->getView()->getJsViewerHeight()) . '" ' .
				'data-bx-getLastVersionUri="' . $urlManager->getUrlToGetLastVersionUriByAttachedFile($attachedObject->getId()) . '" '
			;
			if($version->getView()->getJsViewerFallbackHtmlAttributeName())
			{
				$dataAttributesForViewer .= $version->getView()->getJsViewerFallbackHtmlAttributeName().'="'.$urlManager->getUrlForShowAttachedVersionViewHtml($attachedObject->getId(), array('mode' => 'iframe')).'" ';
			}
		}
		elseif(DocumentHandler::isEditable($extension))
		{
			$dataAttributesForViewer =
				'data-bx-viewer="iframe" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-src="' . $urlManager->getUrlToShowAttachedFileByService($attachedObject->getId(), 'gvdrive') . '" ' .
				'data-bx-isFromUserLib="" ' .
				'data-bx-askConvert="' . (DocumentHandler::isNeedConvertExtension($extension)? '1':'') . '" ' .
				'data-bx-download="' . $urlManager->getUrlUfController('download', array('attachedId' => $attachedObject->getId())) . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($size)) . '" ' .
				'data-bx-getLastVersionUri="' . $urlManager->getUrlToGetLastVersionUriByAttachedFile($attachedObject->getId()) . '" '
			;
			if($object instanceof File)
			{
				if($object->getView()->getId() && $object->getView()->getJsViewerFallbackHtmlAttributeName())
				{
					$dataAttributesForViewer .= $object->getView()->getJsViewerFallbackHtmlAttributeName().'="'.$urlManager->getUrlForShowAttachedFileViewHtml($attachedObject->getId(), array('mode' => 'iframe'), $object->getUpdateTime()->getTimestamp()).'" ';
				}
				if($object->getView()->isTransformationAllowedOnOpen($object->getSize()))
				{
					$dataAttributesForViewer .= 'data-bx-transform="'.$urlManager->getUrlUfController('transformOnOpen', array('attachedId' => $attachedObject->getId())).'" ';
				}
			}
		}
		elseif(self::isViewable($extension))
		{
			$dataAttributesForViewer =
				'data-bx-viewer="iframe" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-src="' . $urlManager->getUrlToShowAttachedFileByService($attachedObject->getId(), 'gvdrive') . '" ' .
				'data-bx-isFromUserLib="" ' .
				'data-bx-download="' . $urlManager->getUrlUfController('download', array('attachedId' => $attachedObject->getId())) . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($size)) . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($updateTime) . '" '
			;
			if($object instanceof File)
			{
				if($object->getView()->getId() && $object->getView()->getJsViewerFallbackHtmlAttributeName())
				{
					$dataAttributesForViewer .= $object->getView()->getJsViewerFallbackHtmlAttributeName().'="'.$urlManager->getUrlForShowAttachedFileViewHtml($attachedObject->getId(), array('mode' => 'iframe'), $object->getUpdateTime()->getTimestamp()).'" ';
				}
			}
		}
		elseif(TypeFile::isImage(($name)))
		{
			$dataAttributesForViewer =
				'data-bx-viewer="image" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-src="' . $urlManager->getUrlUfController('download', array('attachedId' => $attachedObject->getId())) . '" ' .

				'data-bx-isFromUserLib="" ' .

				'data-bx-download="' . $urlManager->getUrlUfController('download', array('attachedId' => $attachedObject->getId())) . '" '
			;
		}
		else
		{
			$user = $version? $version->getCreateUser() : $object->getCreateUser();
			$formattedName = $user? $user->getFormattedName() : '';

			$dataAttributesForViewer =
				'data-bx-viewer="unknown" ' .
				'data-bx-src="' . $urlManager->getUrlUfController('download', array('attachedId' => $attachedObject->getId())) . '" ' .

				'data-bx-isFromUserLib="" ' .

				'data-bx-download="' . $urlManager->getUrlUfController('download', array('attachedId' => $attachedObject->getId())) . '" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-owner="' . htmlspecialcharsbx($formattedName) . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($updateTime) . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($size)) . '" '
			;
		}
		$dataAttributesForViewer .=
			'data-bx-sizeBytes="' . htmlspecialcharsbx($size) . '" ' .
			" data-bx-history=\"\"" .
			" data-bx-historyPage=\"\""
		;
		if($object)
		{
			$dataAttributesForViewer .= " bx-attach-file-id=\"{$object->getId()}\"";
		}

		if(!empty($additionalParams['relativePath']))
		{
			$dataAttributesForViewer .= ' data-bx-relativePath="' . htmlspecialcharsbx($additionalParams['relativePath'] . '/' . $name) . '" ';
		}
		if(!empty($additionalParams['externalId']))
		{
			$dataAttributesForViewer .= ' data-bx-externalId="' . htmlspecialcharsbx($additionalParams['externalId']) . '" ';
		}
		if(!empty($additionalParams['canUpdate']))
		{
			$dataAttributesForViewer .= ' data-bx-edit="' . $urlManager->getUrlToStartEditUfFileByService($attachedObject->getId(), 'gdrive') . '" ';
		}
		if(!empty($additionalParams['canFakeUpdate']))
		{
			$dataAttributesForViewer .= ' data-bx-fakeEdit="' . $urlManager->getUrlToStartEditUfFileByService($attachedObject->getId(), 'gdrive') . '" ';
		}

		if(!empty($additionalParams['showStorage']) && $object)
		{
			$dataAttributesForViewer .= ' data-bx-storage="' . htmlspecialcharsbx($object->getParent()->getName()) . '" ';
		}
		if(!empty($additionalParams['version']))
		{
			$dataAttributesForViewer .= ' data-bx-version="' . htmlspecialcharsbx($additionalParams['version']) . '" ';
		}
		if(!empty($additionalParams['lockedBy']) && $attachedObject->getObject()->getLock())
		{
			$dataAttributesForViewer .= ' data-bx-lockedBy="' . $attachedObject->getObject()->getLock()->getCreatedBy() . '" ';
		}

		return $dataAttributesForViewer;
	}

	public static function getAttributesForDocumentPreviewImageByObject(BaseObject $object)
	{
		if(!$object instanceof File)
		{
			return '';
		}

		$urlManager = Driver::getInstance()->getUrlManager();
		$name = $object->getName();
		$dateTime = $object->getUpdateTime();

		$dataAttributesForViewer = '';

		if($object->getPreviewId())
		{
			$dataAttributesForViewer =
				'data-bx-viewer="image" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-src="' . $urlManager->getUrlForShowPreview($object) . '" ' .

				'data-bx-isFromUserLib="" ' .

				'data-bx-download="' . $urlManager->getUrlForDownloadFile($object) . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($dateTime) . '" '
			;
		}

		return $dataAttributesForViewer;
	}
}