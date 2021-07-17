<?php

namespace Bitrix\ImConnector\Provider\Notifications;

use Bitrix\ImConnector\Provider\Base;

class Input extends Base\Input
{
	public function __construct(string $command, array $params)
	{
		parent::__construct($params);

		$this->params = $params;

		$this->command = $command;
		$this->connector = 'notifications';
		$this->line = $this->params['LINE_ID'];
		$this->data = [$this->params];
	}
}
