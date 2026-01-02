<?php

namespace Bitrix\Crm\Integration\UI\EntityEditor;

use Bitrix\Crm\Entity\EntityEditorConfig;
use Bitrix\Crm\Entity\EntityEditorConfigScope;
use Bitrix\Crm\Entity\EntityEditorOptionBuilder;
use Bitrix\Crm\Integration\UI\EntityEditor\Enum\MarkTarget;
use CUserOptions;

final class MartaAIMarksRepository
{
	private const CATEGORY = 'crm';

	public function __construct(
		private readonly int $userId,
		private readonly string $configId,
		private readonly string $scope = EntityEditorConfigScope::COMMON,
		private readonly ?int $userScopeId = null,
	)
	{
	}

	public static function fromEntityEditorConfig(EntityEditorConfig $config): self
	{
		return new self(
			$config->getUserID(),
			$config->getConfigId(),
			$config->getScope(),
			$config->getUserScopeId(),
		);
	}

	public static function fromEntity(
		int $userId,
		int $entityTypeId,
		?int $categoryId,
		string $scope = EntityEditorConfigScope::COMMON,
		?int $userScopeId = null,
	): self
	{
		$option = (new EntityEditorOptionBuilder($entityTypeId))
			->setCategoryId($categoryId)
			->build();

		return new self($userId, $option, $scope, $userScopeId);
	}

	public function mark(MarkTarget $target, array $items): self
	{
		$currentItems = $this->get($target);
		if (!empty($currentItems))
		{
			$items = array_unique(array_merge($items, $currentItems));
		}

		$this->set($target, $items);

		return $this;
	}

	private function set(MarkTarget $target, array $items): bool
	{
		return CUserOptions::SetOption(
			self::CATEGORY,
			$this->option($target),
			$items,
			false,
			$this->userId,
		);
	}

	public function get(MarkTarget $target): array
	{
		return CUserOptions::GetOption(
			self::CATEGORY,
			$this->option($target),
			[],
			$this->userId,
		);
	}

	public function delete(MarkTarget $target): void
	{
		CUserOptions::DeleteOption(
			self::CATEGORY,
			$this->option($target),
			false,
			$this->userId,
		);
	}

	private function option(MarkTarget $target): string
	{
		return "{$this->configId}_{$this->scope}_{$this->userScopeId}_marta_ai_highlighted_{$target->value}";
	}
}
