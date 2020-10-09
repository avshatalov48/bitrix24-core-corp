<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;

class ImOpenLinesLinesEditAjaxController extends \Bitrix\Main\Engine\Controller
{
	/**
	 * @param $configId
	 *
	 * @return array|int
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function addAvatarFileAction($configId)
	{
		Loader::includeModule('imopenlines');

		$configManager = new \Bitrix\ImOpenLines\Config();
		$canEditLine = $configManager->canEditLine($configId);

		if (!$canEditLine)
		{
			return array(
				'error' => 'Permission denied'
			);
		}

		$avatarFile = $this->request->getFile('avatarFile');
		$fileId = $this->saveAvatarFile($avatarFile);

		if (is_array($fileId))
		{
			return $fileId;
		}
		else
		{
			return $this->getAvatarFilePath($fileId);
		}
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
			return array (
				'error' => 'Empty input error'//TODO
			);
		}

		if(!is_uploaded_file($avatarFile["tmp_name"]))
		{
			return array (
				'error' => 'Name error'//TODO
			);
		}

		if($avatarFile["name"] == '' || intval($avatarFile["size"]) == 0)
		{
			return array (
				'error' => 'Size error'//TODO
			);
		}

		$names = explode('/', $avatarFile["type"]);
		if ($names[1])
		{
			$avatarFile["name"] .= '.' . $names[1];
		}


		$checkResponse = CFile::CheckImageFile($avatarFile);
		if ($checkResponse !== null)
		{
			return array (
				'error' => $checkResponse//TODO
			);
		}

		$avatarFile["MODULE_ID"] = "imopenlines";
		$fileId = intval(CFile::SaveFile($avatarFile, "imopenlines/queueavatars", true, false, "avatars"));

		if ($fileId <= 0)
		{
			return array (
				'error' => 'Save error'//TODO
			);
		}

		return $fileId;
	}

	/**
	 * @param $fileId
	 *
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
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
	 *
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function checkCanActiveLineAction($configId)
	{
		Loader::includeModule('imopenlines');

		$result = true;
		$configId = intval($configId);
		$linesLimit = \Bitrix\Imopenlines\Limit::getLinesLimit();

		if ($linesLimit > 0)
		{
			$activeLinesCount = \Bitrix\ImOpenLines\Model\ConfigTable::getList(
				array(
					'select' => array('ID'),
					'filter' => array('ACTIVE' => 'Y', '!=ID' => $configId, '=TEMPORARY' => 'N'),
					'count_total' => true
				)
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
	 *
	 * @return array|bool
	 * @throws \Bitrix\Main\LoaderException
	 */

	public function deleteOpenLineAction($configId)
	{
		Loader::includeModule('imopenlines');
		$configManager = new \Bitrix\ImOpenLines\Config();
		if(!$configManager->canEditLine($configId))
		{
			return false;
		}
		return $configManager->delete($configId);
	}
}