<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\ImOpenLines\Config,
	Bitrix\ImOpenLines\QueueManager;
use Bitrix\UI\EntitySelector;

class ImOpenLinesLinesEditAjaxController extends \Bitrix\Main\Engine\Controller
{
	/**
	 * @param int $configId
	 * @param int $sessid
	 * @return array|int
	 */
	public function addAvatarFileAction($configId)
	{
		if(Loader::includeModule('imopenlines'))
		{
			if(Config::canEditLine($configId))
			{
				$avatarFile = $this->request->getFile('avatarFile');
				$fileId = $this->saveAvatarFile($avatarFile);

				if (is_array($fileId))
				{
					$result = $fileId;
				}
				else
				{
					$result = $this->getAvatarFilePath($fileId);
				}
			}
			else
			{
				$result = [
					'error' => 'Permission denied'
				];
			}
		}
		else
		{
			$result = [
				'error' => 'Failed to load the open lines module'
			];
		}

		return $result;
	}

	/**
	 * @param int $configId
	 * @param int $fileId
	 * @return array
	 */
	public function removeAvatarFileAction($configId, $fileId)
	{
		if(Loader::includeModule('imopenlines'))
		{
			if(Config::canEditLine($configId))
			{
				\CFile::delete($fileId);

				$result = [
					'fileId' => $fileId
				];
			}
			else
			{
				$result = [
					'error' => 'Permission denied'
				];
			}
		}
		else
		{
			$result = [
				'error' => 'Failed to load the open lines module'
			];
		}

		return $result;
	}

	/**
	 * @param int $configId
	 * @param int $queue
	 * @return array
	 */
	public function getUsersQueueAction($configId, $queue): array
	{
		$result = [];

		if(
			Loader::includeModule('imopenlines') &&
			Loader::includeModule('ui')
		)
		{
			if(Config::canEditLine($configId))
			{
				if(!empty($queue))
				{
					$users = QueueManager::getUsersFromQueue($queue);

					$preselectedUsers = [];
					foreach ($users as $user)
					{
						$preselectedUsers[] = [
							$user['type'],
							$user['id']
						];
					}

					//TODO: 279941 (426ad54dd7a8) socialnetwork
					$userCollections = EntitySelector\Dialog::getSelectedItems($preselectedUsers);
					$items = $userCollections->getAll();
					foreach ($items as $item)
					{
						$result[] = [
							'entityId' => $item->getId(),
							'entityType' => $item->getEntityId(),
							'name' => $item->getTitle(),
							'avatar' => $item->getAvatar(),
							'department' => $users[$item->getId()]['department']
						];
					}
				}

			}
			else
			{
				$result = [
					'error' => 'Permission denied'
				];
			}
		}
		else
		{
			$result = [
				'error' => 'Failed to load module'
			];
		}

		return $result;
	}

	/**
	 * @param array $avatarFile
	 *
	 * @return array|int
	 */
	private function saveAvatarFile($avatarFile)
	{
		if (empty($avatarFile) && !is_array($avatarFile))
		{
			return [
				'error' => 'Empty input error'//TODO
			];
		}

		if(!is_uploaded_file($avatarFile['tmp_name']))
		{
			return [
				'error' => 'Name error'//TODO
			];
		}

		if(
			(string)$avatarFile['name'] === '' ||
			((int)$avatarFile['size']) === 0
		)
		{
			return [
				'error' => 'Size error'//TODO
			];
		}

		$names = explode('/', $avatarFile['type']);
		if ($names[1])
		{
			$avatarFile['name'] .= '.' . $names[1];
		}


		$checkResponse = CFile::CheckImageFile($avatarFile);
		if ($checkResponse !== null)
		{
			return [
				'error' => $checkResponse//TODO
			];
		}

		$avatarFile['MODULE_ID'] = 'imopenlines';
		$fileId = (int)CFile::SaveFile($avatarFile, 'imopenlines/queueavatars', true, false, 'avatars');

		if ($fileId <= 0)
		{
			return [
				'error' => 'Save error'//TODO
			];
		}

		return $fileId;
	}

	/**
	 * @param $fileId
	 *
	 * @return array
	 */
	private function getAvatarFilePath($fileId)
	{
		Loader::includeModule('imopenlines');
		$file = \CFile::getFileArray($fileId);
		if (!$file)
		{
			return array (
				'error' => 'Not saved filed error' //TODO
			);
		}

		$image = \CFile::resizeImageGet(
			$file,
			array('width' => 100, 'height' => 100),
			BX_RESIZE_IMAGE_EXACT, false
		);
		if($image['src'])
		{
			$path = $image['src'];
		}
		else
		{
			$path = \CFile::getFileSRC($file);
		}

		if (mb_substr($path, 0, 1) == '/')
		{
			$path = \Bitrix\ImOpenLines\Common::getServerAddress() . $path;
		}

		return array(
			'path' => $path,
			'fileId' => $fileId
		);
	}

	/**
	 * @param $configId
	 * @return bool
	 */
	public function checkCanActiveLineAction($configId)
	{
		Loader::includeModule('imopenlines');

		$result = true;
		$configId = (int)$configId;
		$linesLimit = \Bitrix\Imopenlines\Limit::getLinesLimit();

		if ($linesLimit > 0)
		{
			$activeLinesCount = \Bitrix\ImOpenLines\Model\ConfigTable::getList(
				[
					'select' => ['ID'],
					'filter' => ['ACTIVE' => 'Y', '!=ID' => $configId, '=TEMPORARY' => 'N'],
					'count_total' => true
				]
			)->getCount();

			if ($activeLinesCount >= $linesLimit)
			{
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * @param $configId
	 * @return bool
	 */
	public function deleteOpenLineAction($configId)
	{
		Loader::includeModule('imopenlines');
		$configManager = new Config();
		if(!Config::canEditLine($configId))
		{
			return false;
		}
		return $configManager->delete($configId);
	}
}