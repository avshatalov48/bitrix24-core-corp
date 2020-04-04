<?php

namespace Bitrix\Voximplant\Routing;

use Bitrix\Voximplant\Call;
use Bitrix\Voximplant\Model\IvrActionTable;

class Ivr extends Node
{
	protected $ivrId;

	public function __construct($ivrId)
	{
		parent::__construct();

		$this->ivrId = $ivrId;
	}

	public function getFirstAction(Call $call)
	{
		$ivr = new \Bitrix\Voximplant\Ivr\Ivr($this->ivrId);
		return new Action(Command::IVR, [
			'IVR' => $ivr->toArray(true)
		]);
	}

	public function getNextAction(Call $call, array $request = [])
	{
		$actionId = $request['IVR_ACTION_ID'];
		$actionResult = $request['ACTION_RESULT'];

		if(!$actionId)
		{
			return false;
		}
		
		$ivrAction = IvrActionTable::getRowById($actionId);
		if(!$ivrAction)
		{
			return false;
		}

		if($ivrAction['ACTION'] !== \Bitrix\Voximplant\Ivr\Action::ACTION_EXIT)
		{
			$call->updateIvrActionId($actionId);
			$this->insertAfter(new IvrAction($actionId));
		}

		if($ivrAction['ACTION'] === \Bitrix\Voximplant\Ivr\Action::ACTION_DIRECT_CODE)
		{
			$call->updateGatheredDigits($actionResult['gatheredDigits']);
		}

		return false;
	}
}