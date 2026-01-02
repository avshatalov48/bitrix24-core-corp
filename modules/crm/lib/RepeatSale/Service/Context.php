<?php

namespace Bitrix\Crm\RepeatSale\Service;

final class Context
{
	private int $jobId;
	private int $segmentId;

	public function getJobId(): int
	{
		return $this->jobId;
	}

	public function setJobId(int $jobId): self
	{
		$this->jobId = $jobId;

		return $this;
	}

	public function getSegmentId(): int
	{
		return $this->segmentId;
	}

	public function setSegmentId(int $segmentId): self
	{
		$this->segmentId = $segmentId;

		return $this;
	}
}
