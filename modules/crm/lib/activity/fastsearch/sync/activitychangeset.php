<?php

namespace Bitrix\Crm\Activity\FastSearch\Sync;


final class ActivityChangeSet
{
	public function __construct(
		private ?ActivitySearchData $old,
		private ActivitySearchData $new,
	)
	{
	}

	public static function build(?array $oldActivityFields, array $newActivityFields): self
	{
		$builder = ActivitySearchDataBuilder::getInstance();

		return new self(
			old: $oldActivityFields ? $builder->build($oldActivityFields) : null,
			new: $builder->build($newActivityFields),
		);
	}

	public function newAct(): ActivitySearchData
	{
		return $this->new;
	}

	public function oldAct(): ActivitySearchData
	{
		return $this->old;
	}

	public function isDeadlineChanged(): bool
	{
		return $this->newAct()->deadline()->getTimestamp() !== $this->oldAct()->deadline()->getTimestamp();
	}

	public function isResponsibleChanged(): bool
	{
		return $this->newAct()?->responsibleId() !== $this->oldAct()?->responsibleId();
	}

	public function isCompletedChanged(): bool
	{
		return $this->newAct()?->isCompleted() !== $this->oldAct()?->isCompleted();
	}

	public function isKindChanged(): bool
	{
		return $this->newAct()?->kind() !== $this->oldAct()?->kind();
	}

	public function isTypeChanged(): bool
	{
		return $this->newAct()?->type() !== $this->oldAct()?->type();
	}

	public function hasAnyChange(): bool
	{
		return $this->isDeadlineChanged()
			|| $this->isResponsibleChanged()
			|| $this->isCompletedChanged()
			|| $this->isKindChanged()
			|| $this->isTypeChanged();
	}
}