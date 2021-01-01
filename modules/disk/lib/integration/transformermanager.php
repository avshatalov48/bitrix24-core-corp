<?php

namespace Bitrix\Disk\Integration;

use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Uf\BlogPostCommentConnector;
use Bitrix\Disk\Uf\BlogPostConnector;
use Bitrix\Main\Application;
use Bitrix\Main\UI\Viewer\Transformation\CallbackHandler;
use Bitrix\Transformer\DocumentTransformer;
use Bitrix\Transformer\FileTransformer;
use Bitrix\Transformer\InterfaceCallback;
use Bitrix\Transformer\Command;
use Bitrix\Main\Loader;
use Bitrix\Transformer\VideoTransformer;

/**
 * @deprecated
 */
class TransformerManager implements InterfaceCallback
{
	const MODULE_ID = 'disk';
	const PATH = 'disk_preview';

	const COMMAND_STATUS_ERROR = 1000;
	const QUEUE_NAME = 'disk_on_load';

	const PULL_TAG = 'DISKTRANSFORMATION';

	/**
	 * Returns name of this class.
	 * @return string
	 */
	public static function className()
	{
		return get_called_class();
	}

	/**
	 * Function to process results after transformation.
	 *
	 * @param int $status Status of the command.
	 * @param string $command Name of the command.
	 * @param array $params Input parameters of the command.
	 * @param array $result Result of the command from controller
	 *      Here keys are identifiers to result information. If result is file it will be in 'files' array.
	 *      'files' - array of the files, where key is extension, and value is absolute path to the result file.
	 *
	 * This method returns true on success or string on error.
	 *
	 * @return bool|string
	 */
	public static function call($status, $command, $params, $result = array())
	{
		if(isset($params['fileId']) && $params['fileId'] > 0)
		{
			FileTransformer::clearInfoCache($params['fileId']);
		}

		if(!isset($params['id']) || !isset($params['fileId']))
		{
			return 'wrong parameters';
		}

		$file = File::getById($params['id']);
		if(!$file)
		{
			return 'file '.$params['id'].' not found';
		}

		$view = $file->getView();
		if(
			$view->isNeededLimitRightsOnTransformTime(false)
			&& Loader::includeModule('socialnetwork')
		)
		{
			$blogPostIDs = self::getBlogPostIds($file);
			foreach($blogPostIDs as $id)
			{
				\Bitrix\Socialnetwork\ComponentHelper::setBlogPostLimitedViewStatus(array(
					'postId' => $id,
					'show' => true
				));
			}
		}

		static::clearCacheByFile($file);

		return true;
	}

	protected static function clearCacheByFile(File $file)
	{
		Application::getInstance()->getTaggedCache()->clearByTag("disk_file_{$file->getId()}");
		BlogPostConnector::clearCacheByObjectId($file->getId());
		BlogPostCommentConnector::clearCacheByObjectId($file->getId());
	}

	/**
	 * @param string $file Absolute path to the file.
	 * @param string $type Mime-type of the file.
	 * @return bool|int
	 */
	protected static function saveFile($file, $type)
	{
		$fileArray = \CFile::MakeFileArray($file, $type);
		$fileArray['MODULE_ID'] = self::MODULE_ID;
		$fileId = \CFile::SaveFile($fileArray, self::PATH);
		if($fileId)
		{
			return $fileId;
		}
		return false;
	}

	public static function resetCacheInUfAfterTransformation(\Bitrix\Main\Event $event)
	{
		$bfileId = $event->getParameter('fileId');
		if (!$bfileId)
		{
			return;
		}

		$file = File::load(['=FILE_ID' => $bfileId,]);
		if (!$file)
		{
			return;
		}

		static::clearCacheByFile($file);
	}

	/**
	 * Fill parameters to call FileTransformer::transform().
	 *
	 * @param File $file
	 * @return bool
	 */
	public static function transformToView(File $file)
	{
		$view = $file->getView();

		if(!Loader::includeModule('transformer'))
		{
			return false;
		}

		$transformFormats = array($view->getPreviewExtension());
		$transformParams = array('id' => $file->getId(), 'fileId' => $file->getFileId(), 'queue' => \Bitrix\Main\UI\Viewer\Transformation\TransformerManager::QUEUE_NAME);
		$viewExtension = $view->getViewExtension();
		$fileExtension = mb_strtolower($file->getExtension());
		if($view::isAlwaysTransformToViewFormat())
		{
			$transformFormats[] = $viewExtension;
		}
		elseif($fileExtension != $viewExtension && !in_array($fileExtension, $view::getAlternativeExtensions()))
		{
			$transformFormats[] = $viewExtension;
		}

		$transformer = self::getTransformerByFormat($viewExtension);
		if($transformer)
		{
			$result = $transformer->transform((int)$file->getFileId(), $transformFormats, self::MODULE_ID, [self::className(), CallbackHandler::class], $transformParams);
			return($result->isSuccess());
		}

		return false;
	}

	/**
	 * Fabric method to get transformer class by format.
	 *
	 * @param string $viewFormat Extension of the view.
	 * @return \Bitrix\Transformer\FileTransformer|bool
	 */
	private static function getTransformerByFormat($viewFormat)
	{
		if($viewFormat == 'mp4')
		{
			return new VideoTransformer();
		}
		elseif($viewFormat == 'pdf')
		{
			return new DocumentTransformer();
		}

		return false;
	}

	/**
	 * Returns true if file had been sent to transform at least once.
	 *
	 * @param File $file
	 * @return bool
	 */
	public static function checkTransformationAttempts(File $file)
	{
		$info = FileTransformer::getTransformationInfoByFile((int)$file->getFileId());
		if($info)
		{
			return true;
		}

		return false;
	}

	/**
	 * Returns array of BlogPost IDs to set limited rights
	 *
	 * @param File $file
	 * @return array
	 */
	public static function getBlogPostIds(File $file)
	{
		$blogPostIDs = array();

		$objects = $file->getAttachedObjects(array('filter' => array('=ENTITY_TYPE' => BlogPostConnector::className())));

		if(!empty($objects))
		{
			foreach($objects as $object)
			{
				$blogPostIDs[] = $object->getEntityId();
			}
		}

		return $blogPostIDs;
	}

	/**
	 * Returns array of SocNetLog IDs to set limited rights
	 *
	 * @param File $file
	 * @return array
	 */
	public static function getSocNetLogIds(File $file)
	{
		$logIds = array();

		if (Loader::includeModule('socialnetwork'))
		{
			$blogPostIDs = self::getBlogPostIds($file);
			if(!empty($blogPostIDs))
			{
				$entryInstance = new \Bitrix\Socialnetwork\Livefeed\BlogPost;
				$socNetLogs = \Bitrix\Socialnetwork\LogTable::getList(array(
					'filter' => array(
						'EVENT_ID' => $entryInstance->getEventId(),
						'SOURCE_ID' => $blogPostIDs,
					)
				))->fetchAll();

				foreach($socNetLogs as $socNetLog)
				{
					$logIds[] = $socNetLog['ID'];
				}
			}
		}

		return $logIds;
	}

	/**
	 * @param int $fileId
	 * @return string
	 */
	protected static function getPullTag($fileId)
	{
		return static::PULL_TAG.$fileId;
	}

	/**
	 * @param File $file
	 * @param int $viewId
	 * @param int $previewId
	 */
	protected static function addToStack(File $file, $viewId = 0, $previewId = 0)
	{
		if($viewId == 0 && $previewId == 0)
		{
			return;
		}
		if(\Bitrix\Main\Loader::includeModule("pull"))
		{
			$params = [];
			if($previewId > 0)
			{
				$params['previewUrl'] = \Bitrix\Main\Engine\UrlManager::getInstance()->create('disk.api.file.showPreview', ['fileId' => $file->getId()]);
			}
			if($viewId > 0)
			{
				$params['viewUrl'] = \Bitrix\Main\Engine\UrlManager::getInstance()->create('disk.api.file.showView', ['fileId' => $file->getId()]);
			}
			\CPullWatch::AddToStack(static::getPullTag($file->getId()), [
				'module_id' => Driver::INTERNAL_MODULE_ID,
				'command' => 'showPreview',
				'params' => $params,
			]);
		}
	}

	/**
	 * @param $fileId
	 * @param $userId
	 * @return bool|string
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function subscribe($fileId, $userId)
	{
		if(\Bitrix\Main\Loader::includeModule("pull"))
		{
			$pullTag = static::getPullTag($fileId);
			\CPullWatch::Add($userId, $pullTag, true);
			return $pullTag;
		}

		return false;
	}
}