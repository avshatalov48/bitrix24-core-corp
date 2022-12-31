<?php

namespace Bitrix\TasksMobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;

Loader::requireModule('tasks');

class FieldsPinner extends Controller
{
	public function configureActions(): array
	{
		return [
			'pinField' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function getPinnedFieldsAction(int $userId): array
	{
		return [];
	}

	public function pinFieldAction(string $field): void
	{

	}
}
