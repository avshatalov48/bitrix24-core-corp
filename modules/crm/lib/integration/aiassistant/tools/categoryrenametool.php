<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\AiAssistant\Tools;

class CategoryRenameTool extends BaseCrmTool
{
	public function getName(): string
	{
		return 'rename_funnel_title';
	}

	public function getDescription(): string
	{
		return "Renames the funnel identified by `categoryId` to `title` for an entity with the entity type identifier `entityTypeId`. Use this function to update the existing CRM funnel name";
	}

	public function getInputSchema(): array
	{
		return [
			'type' => 'object',
			'properties' => [
				'entityTypeId' => [
					'description' => 'CRM entity type identifier. Can be either 2 (Deal) or a smart-process type identifier',
					'type' => 'number',
				],
				'categoryId' => [
					'description' => 'Identifier of the CRM entity sales funnel, also referred to as \'category\'',
					'type' => 'number',
				],
				'title' => [
					'description' => 'New funnel title. Must not be an empty string',
					'type' => 'string',
				],
			],
			'additionalProperties' => false,
			'required' => ['entityTypeId', 'categoryId', 'title'],
		];
	}

	public function executeTool(int $userId, ...$args): string
	{
		$operation = new \Bitrix\Crm\Integration\AI\Function\Category\Rename(currentUserId: $userId);
		$result = $operation->invoke(entityTypeId: $args['entityTypeId'], categoryId: $args['categoryId'], title: $args['title']);

		return $result->isSuccess()
			? "Funnel successfully renamed to '{$args['title']}'"
			: "Error renaming funnel: " . implode(", ", $result->getErrorMessages());
	}
}
