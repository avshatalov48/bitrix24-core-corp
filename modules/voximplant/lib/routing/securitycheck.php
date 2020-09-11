<?php

namespace Bitrix\Voximplant\Routing;

use Bitrix\Main\PhoneNumber\Parser;
use Bitrix\Voximplant\Call;
use Bitrix\Voximplant\Security;


class SecurityCheck extends Node
{
	public function getFirstAction(Call $call)
	{
		if($call->getIncoming() != \CVoxImplantMain::CALL_OUTGOING)
		{
			return false;
		}

		$country = '';
		$config = $call->getConfig();

		if($config['PORTAL_MODE'] == 'LINK' || $config['PORTAL_MODE'] == 'RENT')
		{
			$portalNumber = $call->getPortalNumber();
			if(mb_substr($portalNumber, 0, 1) != '+')
			{
				$portalNumber = '+' . $portalNumber;
			}
			$parsedNumber = Parser::getInstance()->parse($portalNumber);
			if($parsedNumber->isValid())
			{
				$country = Parser::getInstance()->parse($portalNumber)->getCountry();
			}
		}

		$isCallAllowed = Security\Helper::canUserCallNumber(
			$call->getUserId(),
			$call->getCallerId(),
			$country
		);

		if($isCallAllowed)
		{
			return false;
		}
		else
		{
			return new Action(Command::HANGUP, [
				'CODE' => 403,
				'REASON' => 'User ' . $call->getUserId() . ' is not allowed to call number ' . $call->getCallerId()
			]);
		}
	}

	public function getNextAction(Call $call, array $request = [])
	{
		return new Action(Command::HANGUP, [
			'REASON' => 'Security check failed'
		]);
	}
}