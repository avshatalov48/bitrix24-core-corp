<?php

namespace Bitrix\ImConnector\Provider\Notifications;

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

		$this->command = $params['BX_COMMAND'];
		unset($params['BX_COMMAND']);
		$this->params = $params;
		$this->connector = 'notifications';
		$this->line = $this->params['LINE_ID'];
		$this->data = [$this->params];
	}
}
