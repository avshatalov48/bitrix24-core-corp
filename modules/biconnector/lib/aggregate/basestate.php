<?php

namespace Bitrix\BIConnector\Aggregate;

abstract class BaseState
{
	abstract public function updateState($id, $value);
	abstract public function output();
}
