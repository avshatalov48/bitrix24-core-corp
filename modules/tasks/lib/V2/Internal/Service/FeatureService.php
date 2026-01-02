<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service;

use Bitrix\Main\Validation\Validator\JsonValidator;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\V2\Internal\Repository\OptionRepositoryInterface;

class FeatureService
{
	public function __construct(
		private readonly OptionRepositoryInterface $optionRepository
	)
	{
	}

	public function isHostAllowed(string $host): bool
	{
		$allowedHosts = $this->getAllowedHosts();

		return in_array($host, $allowedHosts, true);
	}

	public function allowHost(string $host): void
	{
		$allowedHosts = $this->getAllowedHosts();

		if (!in_array($host, $allowedHosts, true))
		{
			$allowedHosts[] = $host;
			$this->optionRepository->set('tasks', 'tasks_v2_allowed_portal_hosts', Json::encode($allowedHosts));
		}
	}

	private function getAllowedHosts(): array
	{
		$allowedHosts = $this->optionRepository->get('tasks', 'tasks_v2_allowed_portal_hosts', '');

		$validator = new JsonValidator();
		if ($validator->validate($allowedHosts)->isSuccess())
		{
			$allowedHosts = Json::decode($allowedHosts);
		}

		if (!is_array($allowedHosts))
		{
			return [];
		}

		return $allowedHosts;
	}
}
