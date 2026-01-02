<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\AiAssistant\Tools;

class StageRenameTool extends BaseCrmTool
{
	public function getName(): string
	{
		return 'rename_funnel_stages';
	}

	public function getDescription(): string
	{
		return "Renames the stage names for the entity with an entity type identifier `entityTypeId` in the funnel identified by `categoryId`. Use this function when explicitly instructed to rename one or more stages";
	}

	public function getInputSchema(): array
	{
		return [
			'type' => 'object',
			'properties' => [
				'entityTypeId' => [
					'description' => 'CRM entity type identifier. Possible values: 1 (Lead), 2 (Deal), 7 (Proposal), 31 (Invoice), or an identifier of a smart-process type',
					'type' => 'number',
				],
				'categoryId' => [
					'description' => 'Identifier of the CRM entity funnel. Must be null if entityTypeId is 1 or 7. In all other cases, categoryId must not be null',
					'type' => 'number',
				],
				'names' => [
					'description' => 'Object containing information about the funnel stage and its new name',
					'type' => 'array',
					'items' => [
						'type' => 'object',
						'properties' => [
							'stageId' => [
								'description' => 'Stage identifier',
								'type' => 'string',
							],
							'name' => [
								'description' => 'New stage name. Must not be an empty string',
								'type' => 'string',
							],
						],
						'required' => ['stageId', 'name'],
					],
				],
			],
			'additionalProperties' => false,
			'required' => ['entityTypeId', 'categoryId', 'names'],
		];
	}

	public function executeTool(int $userId, ...$args): string
	{
		$operation = new \Bitrix\Crm\Integration\AI\Function\Category\Stage\Rename(currentUserId: $userId);
		$result = $operation->invoke(
			entityTypeId: $args['entityTypeId'],
			categoryId: $args['categoryId'],
			names: $args['names'],
		);

		return $result->isSuccess()
			? "Stages renamed successfully"
			: "Error renaming stages: " . implode(", ", $result->getErrorMessages());
	}
}
