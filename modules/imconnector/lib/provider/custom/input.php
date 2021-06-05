<?php
namespace Bitrix\ImConnector\Provider\Custom;

use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\Provider\Base;

class Input extends Base\Input
{
	/**
	 * Input constructor.
	 * @param array $params
	 */
	public function __construct(array $params)
	{
		parent::__construct($params);

		$this->params = $params;

		$this->command = $this->params['BX_COMMAND'];
		$this->connector = $this->params['CONNECTOR'];
		$this->line = $this->params['LINE'];
		$this->data = $this->params['DATA'];
	}

	/**
	 * @return Result
	 */
	protected function receivingError(): Result
	{
		return $this->receivingBase();
	}

	/**
	 * @return Result
	 */
	protected function receivingStatusBlock(): Result
	{
		return $this->receivingBase();
	}
}
