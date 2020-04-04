<?php
namespace Bitrix\Landing\Controller;

use \Bitrix\Landing\Zip;
use \Bitrix\Main\Engine\Controller;
use \Bitrix\Main\Engine\Response\Zip\Archive;

class Site extends Controller
{
	public function getDefaultPreFilters()
	{
		return [];
	}

	/**
	 * Zip export site.
	 * @param int $id Site id.
	 * @return Archive
	 */
	public function downloadAction($id)
	{
		if (Zip\Config::serviceEnabled())
		{
			return Zip\Site::export($id);
		}
	}
}