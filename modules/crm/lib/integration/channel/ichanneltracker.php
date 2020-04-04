<?php
namespace Bitrix\Crm\Integration\Channel;
interface IChannelTracker
{
	/**
	 * Get Channel Type ID
	 * @return int
	 */
	public function getTypeID();
	/**
	 * Check if External Channel is enabled.
	 * @return bool
	 */
	public function isEnabled();
	/**
	 * Initialize tracker for using by user.
	 * @return void
	 */
	public function initializeUserContext();
	/**
	 * Check if External Channel is in use.
	 * @param array $params Array of channel parameters.
	 * @return bool
	 */
	public function isInUse(array $params = null);
	/**
	 * Check if current user has permission to configure External Channel.
	 * @param array $params Array of channel parameters.
	 * @return bool
	 */
	public function checkConfigurationPermission(array $params = null);
	/**
	 * Get External Channel page URL.
	 * @param array|null $params Array of channel parameters.
	 * @return string
	 */
	public function getUrl(array $params = null);
	/**
	 * Create channel group info items.
	 * @return IChannelGroupInfo[]
	 */
	public function prepareChannelGroupInfos();
	/**
	 * Create External Channel info items.
	 * @return IChannelInfo[]
	 */
	public function prepareChannelInfos();
	/**
	 * Prepare channel caption
	 * @param array|null $params Array of channel parameters.
	 * @return string
	 */
	public function prepareCaption(array $params = null);
	//region Register/Unregister
	/**
	 * Register binding to the channel for specified Lead.
	 * @param int $ID Lead ID.
	 * @param array $params Array of binding parameters. For example ORIGIN_ID and COMPONENT_ID.
	 * @return void
	 */
	public function registerLead($ID, array $params = null);
	/**
	 * Unregister binding to the channel for specified Lead.
	 * @param int $ID Lead ID.
	 * @return void
	 */
	public function unregisterLead($ID);

	/**
	 * Register binding to the channel for specified Deal.
	 * @param int $ID Deal ID.
	 * @param array $params Array of binding parameters. For example ORIGIN_ID and COMPONENT_ID.
	 * @return void
	 */
	public function registerDeal($ID, array $params = null);
	/**
	 * Unregister binding to the channel for specified Deal.
	 * @param int $ID Deal ID.
	 * @return void
	 */
	public function unregisterDeal($ID);

	/**
	 * Register binding to the channel for specified Activity.
	 * @param int $ID Activity ID.
	 * @param array $params Array of binding parameters. For example ORIGIN_ID and COMPONENT_ID.
	 * @return void
	 */
	public function registerActivity($ID, array $params = null);
	/**
	 * Unregister binding to the channel for specified Activity.
	 * @param int $ID Activity ID.
	 * @return void
	 */
	public function unregisterActivity($ID);
	//endregion
}