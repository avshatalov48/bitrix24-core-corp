<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Channel;

use Bitrix\Crm\Tracking;

/**
 * Class Site
 *
 * @package Bitrix\Crm\Tracking\Channel
 */
class Site extends Base implements Features\Site
{
	protected $code = self::Site;

	/**
	 * Site constructor.
	 *
	 * @param string $siteId Site ID.
	 */
	public function __construct($siteId)
	{
		$this->value = $siteId;
	}

	/**
	 * Get description.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		$value = $this->getValue();
		if (!$value)
		{
			return null;
		}

		return Tracking\Internals\SiteTable::getHostBySiteId($value);
	}

	/**
	 * Get items.
	 *
	 * @return array
	 */
	public function getItems()
	{
		$list = Tracking\Internals\SiteTable::getList([
			'select' => ['ID', 'NAME' => 'HOST', 'ACTIVE', 'IS_INSTALLED'],
			'order' => ['ID' => 'DESC'],
			'cache' => ['ttl' => 36000]
		])->fetchAll();

		foreach ($list as $index => $item)
		{
			$item['ACTIVE'] = ($item['ACTIVE'] === 'Y' && $item['IS_INSTALLED'] === 'Y') ? 'Y' : 'N';
			unset($item['IS_INSTALLED']);
			$list[$index] = $item;
		}

		return $list;
	}
}