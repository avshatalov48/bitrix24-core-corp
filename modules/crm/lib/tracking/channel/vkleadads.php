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
 * Class VkLeadAds
 *
 * @package Bitrix\Crm\Tracking\Channel
 */
class VkLeadAds extends Base implements Features\SingleChannelSourceDetectable
{
	protected $code = self::VkLeadAds;

	/**
	 * Get source ID.
	 *
	 * @return int|null
	 */
	public function getSourceId()
	{
		return Tracking\Internals\SourceTable::getSourceByCode(Tracking\Source\Base::Vk);
	}
}