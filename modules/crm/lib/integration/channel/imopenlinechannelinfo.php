<?php
namespace Bitrix\Crm\Integration\Channel;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class ImOpenLineChannelInfo extends ChannelInfo
{
	/** @var int */
	private $lineID = 0;
	/** @var string */
	private $connectorID = '';

	/**
	 * Check if channel enabled.
	 * @return bool
	 */
	public function isEnabled()
	{
		/** @var IMOpenLineTracker $mgr */
		$mgr = $this->tracker;
		return $mgr->isEnabled() && $mgr->isConnectorEnabled($this->connectorID);
	}
	public function __construct($caption, $lineID, $connectorID, $sort = 1000, $groupID = '')
	{
		if(!is_numeric($lineID) && $lineID <= 0)
		{
			$lineID = 0;
		}
		$this->lineID = (int)$lineID;
		$this->connectorID = (string)$connectorID;

		parent::__construct(
			IMOpenLineTracker::getInstance(),
			ChannelType::IMOPENLINE,
			$caption,
			(string)$this->lineID,
			$this->connectorID,
			$sort,
			$groupID
		);
	}
	/**
	 * Check if channel in use.
	 * @return bool
	 */
	public function isInUse()
	{
		/** @var IMOpenLineTracker $mgr */
		$mgr = $this->tracker;
		return $mgr->isConnectorInUse($this->connectorID, $this->lineID);
	}
	/**
	 * Get channel caption.
	 * @return string
	 */
	public function getCaption()
	{
		if($this->caption !== '')
		{
			return $this->caption;
		}

		/** @var IMOpenLineTracker $mgr */
		$mgr = $this->tracker;
		return ($this->caption = $mgr->getConnectorCaption($this->connectorID));
	}
}