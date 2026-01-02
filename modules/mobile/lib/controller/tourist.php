<?php

namespace Bitrix\Mobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\JsonController;

final class Tourist extends JsonController
{
	public function configureActions(): array
	{
		return [
			'getEvents' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function getEventsAction(): array
	{
		return \Bitrix\Mobile\Tourist::getEvents();
	}

	public function rememberAction(string $event, ?string $context = null, ?int $count = null, ?int $timestamp = null): array
	{
		return \Bitrix\Mobile\Tourist::remember($event, $context, $count, $timestamp);
	}

	public function forgetAction(string $event): void
	{
		\Bitrix\Mobile\Tourist::forget($event);
	}
}
