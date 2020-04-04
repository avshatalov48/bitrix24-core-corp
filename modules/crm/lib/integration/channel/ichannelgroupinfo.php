<?php
namespace Bitrix\Crm\Integration\Channel;
interface IChannelGroupInfo
{
	/**
	 * Get channel group ID.
	 * @return string
	 */
	public function getID();
	/**
	 * Gets parent group ID.
	 * @return null|string
	 */
	public function getParentID();
	/**
	 * Get channel group caption.
	 * @return string
	 */
	public function getCaption();
	/**
	 * Get channel group gort.
	 * @return int
	 */
	public function getSort();
	/**
	 * Check if channel group is displayable
	 * Items of not displayable group will be displayed at top-level as non-grouped.
	 * @return bool
	 */
	public function isDisplayable();
	/**
	 * Get channel group URL.
	 * @return string
	 */
	public function getUrl();
}