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
 * Class FbLeadAds
 *
 * @package Bitrix\Crm\Tracking\Channel
 */
class FbLeadAds extends Base implements Features\SingleChannelSourceDetectable
{
	protected $code = self::FbLeadAds;

	/**
	 * Get source ID.
	 *
	 * @return int|null
	 */
	public function getSourceId()
	{
		return Tracking\Internals\SourceTable::getSourceByCode(Tracking\Source\Base::Fb);
	}
}