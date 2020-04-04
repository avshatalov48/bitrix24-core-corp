<?php
namespace Bitrix\Crm\Integration\Channel;
interface IChannelInfo
{
	/**
	 * Get Channel Type ID.
	 * @return ChannelType
	 */
	public function getChannelTypeID();
	/**
	 * Get Channel Origin (Identifier, GUID or other sign).
	 * @return string
	 */
	public function getChannelOrigin();
	/**
	 * Get Channel Component.
	 * @return string
	 */
	public function getChannelComponent();
	/**
	 * Check if channel enabled.
	 * @return bool
	 */
	public function isEnabled();
	/**
	 * Check if channel in use.
	 * @return bool
	 */
	public function isInUse();
	/**
	 * Get channel caption.
	 * @return string
	 */
	public function getCaption();
	/**
	 * Get sorting
	 * @return int
	 */
	public function getSort();
	/**
	 * Get group ID
	 * @return string
	 */
	public function getGroupID();
	/**
	 * Check if current user has permission to configure this channel.
	 * @return bool
	 */
	public function checkConfigurationPermission();
	/**
	 * Get channel configuration URL.
	 * @return string
	 */
	public function getConfigurationUrl();
	/**
	 * Get channel unique key.
	 * @return string
	 */
	public function getKey();
}