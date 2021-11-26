<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Marketing;


interface EventInterface
{
	public function __construct(int $userId, $params = null);

	public function execute(): bool;
	public function getDateSheduled(): int;
	public function validate(): bool;
	public function getClass(): string;
	public function getParams();
}