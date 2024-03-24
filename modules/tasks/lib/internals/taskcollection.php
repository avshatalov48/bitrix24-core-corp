<?php

namespace Bitrix\Tasks\Internals;


use Bitrix\Tasks\Internals\Existence\ExistenceTrait;

class TaskCollection extends EO_Task_Collection
{
	use ExistenceTrait;
}