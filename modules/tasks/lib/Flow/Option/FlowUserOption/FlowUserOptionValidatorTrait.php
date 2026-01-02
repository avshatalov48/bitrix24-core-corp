<?php

namespace Bitrix\Tasks\Flow\Option\FlowUserOption;

trait FlowUserOptionValidatorTrait
{
	private function validateName(string $name): void
	{
		if (null === FlowUserOptionDictionary::tryFrom($name))
		{
			throw new \InvalidArgumentException("Invalid flow_user_option name: $name");
		}
	}
}