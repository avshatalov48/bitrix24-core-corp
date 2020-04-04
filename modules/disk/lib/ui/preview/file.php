<?

namespace Bitrix\Disk\Ui\Preview;

class File
{
	/**
	 * Returns HTML code for file preview.
	 * @param array $params Expected keys: action, fileId.
	 * @return string
	 */
	public static function buildPreview(array $params)
	{
		global $APPLICATION;
		if($params['action'] == 'showFile')
		{
			ob_start();
			$APPLICATION->includeComponent(
					'bitrix:disk.file.preview',
					'',
					$params
			);
			return ob_get_clean();
		}
		return null;
	}

	/**
	 * Returns true if current user has read access to the file.
	 * @param array $params Allowed keys: fileId.
	 * @param int $userId Current user's id.
	 * @return bool
	 */
	public static function checkUserReadAccess(array $params, $userId)
	{
		$result = false;

		$fileId = $params['fileId'];
		if($file = \Bitrix\Disk\File::loadById($fileId))
		{
			$securityContext = $file->getStorage()->getSecurityContext($userId);
			if($file->canRead($securityContext))
			{
				$result = true;
			}
		}
		return $result;
	}
}