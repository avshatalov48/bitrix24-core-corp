<?php

namespace Bitrix\SalesCenter\Delivery\Wizard;

use Bitrix\Main\Result;
use Bitrix\SalesCenter\Delivery\Handlers\HandlerContract;

/**
 * Interface WizardContract
 * @package Bitrix\SalesCenter\Delivery\Wizard
 */
interface WizardContract
{
	/**
	 * @param array $settings
	 * @return Result
	 */
	public function install(array $settings): Result;

	/**
	 * @param int $id
	 * @param array $settings
	 * @return Result
	 */
	public function update(int $id, array $settings): Result;

	/**
	 * @param int $id
	 * @return Result
	 */
	public function delete(int $id): Result;

	/**
	 * @param HandlerContract $handler
	 * @return WizardContract
	 */
	public function setHandler(HandlerContract $handler): WizardContract;

	/**
	 * @return HandlerContract
	 */
	public function getHandler(): HandlerContract;
}
