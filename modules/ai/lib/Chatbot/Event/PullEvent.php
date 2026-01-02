<?php declare (strict_types=1);

namespace Bitrix\AI\Chatbot\Event;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Pull\Event;

abstract class PullEvent
{
	protected array $users = [];
	protected string $command;
	protected array $params = [];

	abstract protected function prepareData(): array;

	/**
	 * @throws LoaderException
	 */
	public function send(bool $immediately = false): void
	{
		if (!Loader::includeModule('pull'))
		{
			return;
		}

		Event::add(
			$this->users,
			[
				'module_id' => 'ai',
				'expiry' => 5,
				'command' => $this->command,
				'params' => $this->prepareData(),
			]
		);

		if ($immediately)
		{
			Event::send();
		}
	}

	/**
	 * @throws LoaderException
	 */
	public function sendImmediately(): void
	{
		$this->send(true);
	}
}
