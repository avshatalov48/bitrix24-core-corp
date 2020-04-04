<?php
namespace Bitrix\ImConnector\Input;

use \Bitrix\Main\Data\Cache,
	\Bitrix\Main\Localization\Loc;
use \Bitrix\ImConnector\Result,
	\Bitrix\ImConnector\Status,
	\Bitrix\ImConnector\Library,
	\Bitrix\ImConnector\Connector;

Loc::loadMessages(__FILE__);
/**
 * The class disconnection of the connector due to the connection with the specified data on a different portal or lines.
 *
 * Class DeactivateConnector
 * @package Bitrix\ImConnector\Input
 */
class DeactivateConnector
{
	const CACHE_DIR = "/imconnector/component/";

	private $connector;
	private $line;
	private $data;

	/**
	 * DeactivateConnector constructor.
	 * @param string $connector ID connector.
	 * @param string $line ID line.
	 * @param array $data Array of input data.
	 */
	function __construct($connector, $line = null, $data = array())
	{
		$this->connector = $connector;
		$this->line = $line;
		$this->data = $data;
	}

	/**
	 * Receive data.
	 *
	 * @return Result
	 */
	public function receiving()
	{
		$result = new Result();

		Status::getInstance($this->connector, $this->line)->setError(true);
		$cacheId = Connector::getCacheIdConnector($this->line, $this->connector);

		//Reset cache
		$cache = Cache::createInstance();
		$cache->clean($cacheId, Library::CACHE_DIR_COMPONENT);

		return $result;
	}
}