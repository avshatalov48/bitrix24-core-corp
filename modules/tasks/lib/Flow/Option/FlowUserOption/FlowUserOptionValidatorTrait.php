<?php

namespace Bitrix\Tasks\Flow\Option\FlowUserOption;

trait FlowUserOptionValidatorTrait
{
	private function validateCode(string $code): void
	{
		if (null === FlowUserOptionDictionary::tryFrom($code))
		{
			throw new \InvalidArgumentException("Invalid flow_user_option code: $code");
		}
	}
}