<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator\Dto;

final class DashboardList
{
	/** @var int[]|null */
	private ?array $cachedIdCollection = null;

	public function __construct(
		/** @var Dashboard[] */
		public array	$dashboards,
		public int		$commonCount,
	){}

	/**
	 * @return int[]
	 */
	public function collectId(): array
	{
		if ($this->cachedIdCollection === null)
		{
			$this->cachedIdCollection = [];
			foreach ($this->dashboards as $dashboard)
			{
				$this->cachedIdCollection[] = $dashboard->id;
			}
		}

		return $this->cachedIdCollection;
	}
}