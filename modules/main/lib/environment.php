<?php
namespace Bitrix\Main;

use Bitrix\Main\Type\ParameterDictionary;

class Environment
	extends ParameterDictionary
{
	/**
	 * Creates env object.
	 *
	 * @param array $arEnv
	 */
	public function __construct(array $arEnv)
	{
		parent::__construct($arEnv);
	}
}