<?php

namespace Bitrix\Tasks\Internals\Fields;

// internal only! use CTasks
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Status extends Common
{
	const NEW_TASK = \CTasks::STATE_NEW;
	const PENDING = \CTasks::STATE_PENDING;
	const IN_PROGRESS = \CTasks::STATE_IN_PROGRESS;
	const SUPPOSEDLY_COMPLETED = \CTasks::STATE_SUPPOSEDLY_COMPLETED;
	const COMPLETED = \CTasks::STATE_COMPLETED;
	const DEFERRED = \CTasks::STATE_DEFERRED;
	const DECLINED = \CTasks::STATE_DECLINED;

	public static function all()
	{
		$cl = new \ReflectionClass(static::getClass());

		$res = $cl->getConstants();
		$list = [];
		foreach ($res as $key => $value)
		{
			$list[$key] = $value;
		}

		return $list;
	}

	public static function getTranslate($statusId)
	{
		return Loc::getMessage('TASKS_STATUS_'.$statusId);
	}
}