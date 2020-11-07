<?php
namespace Bitrix\Mobile\Component\LogList;

use Bitrix\Main\ModuleManager;
use Bitrix\Socialnetwork\Component\LogList\Util;

class ParamPhotogallery
{
	protected $component;

	public function __construct($params)
	{
		if (!empty($params['component']))
		{
			$this->component = $params['component'];
		}
	}

	public function getComponent()
	{
		return $this->component;
	}

	public function preparePhotogalleryParams(&$componentParams)
	{
		if (!ModuleManager::isModuleInstalled('photogallery'))
		{
			return;
		}

		Util::checkEmptyParamInteger($componentParams, 'PHOTO_COUNT', 5);
		Util::checkEmptyParamInteger($componentParams, 'PHOTO_THUMBNAIL_SIZE', 76);
	}
}
?>