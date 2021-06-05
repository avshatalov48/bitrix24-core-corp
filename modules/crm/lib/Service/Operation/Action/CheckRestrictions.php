<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Item;
use Bitrix\Crm\Restriction\AccessRestriction;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class CheckRestrictions extends Action
{
	public const ERROR_CODE_RESTRICTIONS_ACCESS_DENIED = 'RESTRICTIONS_ACCESS_DENIED';

	/** @var AccessRestriction */
	protected $restriction;

	public function __construct(AccessRestriction $restriction)
	{
		parent::__construct();
		$this->restriction = $restriction;
	}

	public function process(Item $item): Result
	{
		$result = new Result();

		if (!$this->restriction->hasPermission())
		{
			$result->addError(new Error(Loc::getMessage('CRM_COMMON_ERROR_ACCESS_DENIED'), static::ERROR_CODE_RESTRICTIONS_ACCESS_DENIED));
		}

		return $result;
	}
}