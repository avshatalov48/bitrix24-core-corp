<?php
namespace Bitrix\Crm\Integration\Channel;
use Bitrix\Main;

abstract class ChannelTracker implements IChannelTracker
{
	/** @var int */
	protected $typeID = 0;
	/** @var array */
	protected static $perm = array();

	public function __construct($typeID)
	{
		$this->typeID = $typeID;
	}
	/**
	 * Initialize tracker for using by user.
	 * @return void
	 */
	public function initializeUserContext()
	{
	}
	protected function prepareUserName($userID)
	{
		$names = $this->prepareUserNames(array($userID));
		return isset($names[$userID]) ? $names[$userID] : '';
	}
	protected function prepareUserNames(array $userIDs)
	{
		if(empty($userIDs))
		{
			return array();
		}

		$users = new \CUser();
		$by = 'ID';
		$order = 'ASC';
		$dbUsers = $users->GetList(
			$by,
			$order,
			array('ID' => implode('|', $userIDs)),
			array('FIELDS' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME'))
		);

		$results = array();
		if($dbUsers)
		{
			while($fields = $dbUsers->Fetch())
			{
				$results[$fields['ID']] = \CUser::FormatName(
					\CSite::GetNameFormat(),
					array(
						'LOGIN' => isset($fields['LOGIN']) ? $fields['LOGIN'] : '',
						'NAME' => isset($fields['NAME']) ? $fields['NAME'] : '',
						'LAST_NAME' => isset($fields['LAST_NAME']) ? $fields['LAST_NAME'] : '',
						'SECOND_NAME' => isset($fields['SECOND_NAME']) ? $fields['SECOND_NAME'] : ''
					),
					true,
					false
				);
			}
		}
		return $results;
	}

	//region IChannelTracker
	/**
	 * Get Channel Type ID
	 * @return int
	 */
	public function getTypeID()
	{
		return $this->typeID;
	}
	/**
	 * Create channel group info items.
	 * @return IChannelGroupInfo[]
	 */
	public function prepareChannelGroupInfos()
	{
		return array();
	}
	/**
	 * Check if channel is in use.
	 * @param array $params Array of channel parameters.
	 * @return bool
	 */
	public function isInUse(array $params = null)
	{
		$id = md5(is_array($params) ? serialize($params) : 'id');
		if (!isset(self::$perm[$id]))
		{
			self::$perm[$id] = $this->checkPossibilityOfUsing($params);
		}
		return self::$perm[$id];
	}
	/**
	 * Returns information about possibility of using. Should be redeclared in child class.
	 * @param array $params Array of channel parameters.
	 * @return bool
	 */
	protected function checkPossibilityOfUsing(array $params = null)
	{
		return false;
	}
	/**
	 * Register binding to the channel for specified Lead.
	 * @param int $ID Lead ID.
	 * @param array $params Array of binding parameters. For example ORIGIN_ID and COMPONENT_ID.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public function registerLead($ID, array $params = null)
	{
		LeadChannelBinding::register($ID, $this->typeID, $params);
	}
	/**
	 * Unregister binding to the channel for specified Lead.
	 * @param int $ID Lead ID.
	 * @return void
	 */
	public function unregisterLead($ID)
	{
		LeadChannelBinding::unregister($ID, $this->typeID);
	}
	/**
	 * Register binding to the channel for specified Deal.
	 * @param int $ID Deal ID.
	 * @param array $params Array of binding parameters. For example ORIGIN_ID and COMPONENT_ID.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public function registerDeal($ID, array $params = null)
	{
		DealChannelBinding::register($ID, $this->typeID, $params);
	}
	/**
	 * Unregister binding to the channel for specified Deal.
	 * @param int $ID Deal ID.
	 * @return void
	 */
	public function unregisterDeal($ID)
	{
		DealChannelBinding::unregister($ID, $this->typeID);
	}
	/**
	 * Register binding to the channel for specified Activity.
	 * @param int $ID Activity ID.
	 * @param array $params Array of binding parameters. For example ORIGIN_ID and COMPONENT_ID.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public function registerActivity($ID, array $params = null)
	{
		ActivityChannelBinding::register($ID, $this->typeID, $params);
	}
	/**
	 * Unregister binding to the channel for specified Activity.
	 * @param int $ID Activity ID.
	 * @return void
	 */
	public function unregisterActivity($ID)
	{
		ActivityChannelBinding::unregister($ID, $this->typeID);
	}
	//endregion
}