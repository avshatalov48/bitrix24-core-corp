<?php
namespace Bitrix\ImOpenLines\SalesCenter;

use \Bitrix\Main\Loader;
use \Bitrix\ImConnector\InteractiveMessage\Output;
use Bitrix\Main\Result;

/**
 * Class SalesCenter
 * @package Bitrix\ImOpenLines\SalesCenter
 */
class Form extends Base
{
	protected $formIds = [];

	/**
	 * @param array $ids
	 * @return Form
	 */
	public function setFormIds($ids = []): Form
	{
		$this->formIds = $ids;

		return $this;
	}

	/**
	 * @return bool
	 */
	protected function isValidForm(): bool
	{
		$result = false;

		if(!empty($this->formIds) && is_array($this->formIds))
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function send(): Result
	{
		if(
			Loader::includeModule('imconnector') &&
			$this->isValidForm())
		{
			Output::getInstance($this->chatId)->setFormIds($this->formIds);
		}

		return $this->sendMessage();
	}
}