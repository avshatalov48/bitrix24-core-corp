<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\AiAssistant\Tools;

class StageCreateTool extends BaseCrmTool
{
	public function getName(): string
	{
		return 'create_new_funnel_stage';
	}

	public function getDescription(): string
	{
		return "Creates a new stage with the specified `fields` for the entity with entity type identifier `entityTypeId` in the funnel identified by `categoryId`. Use this function when explicitly instructed to add a new custom funnel stage";
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
				'fields' => [
					'description' => 'Fields for the new CRM funnel stage',
					'type' => 'object',
					'properties' => [
						'name' => [
							'description' => 'Name of the stage. Must not be an empty string',
							'type' => 'string',
						],
						'semantics' => [
							'description' => 'Stage semantics: \'P\' for stage in progress, \'F\' for failed stage',
							'type' => 'string',
							'enum' => ['P', 'F'],
						],
						'color' => [
							'description' => 'Stage color in hex format',
							'type' => 'string',
						],
						'sort' => [
							'description' => 'Sorting index of the stage. Stages must follow sorting order: P < S < F. A stage with semantics \'P\' cannot have a sorting index greater than stages with semantics \'S\' or \'F\'. Similarly, a stage with semantics \'S\' cannot have a sorting index greater than stages with semantics \'F\'',
							'type' => 'number',
						],
					],
				],
			],
			'additionalProperties' => false,
			'required' => ['entityTypeId', 'categoryId', 'fields'],
		];
	}

	public function executeTool(int $userId, ...$args): string
	{
		$operation = new \Bitrix\Crm\Integration\AI\Function\Category\Stage\Create(currentUserId: $userId);
		$result = $operation->invoke(
			entityTypeId: $args['entityTypeId'],
			categoryId: $args['categoryId'],
			fields: $args['fields'],
		);

		return $result->isSuccess()
			? "Stage '{$args['fields']['name']}' created successfully"
			: "Error creating stage: " . implode(", ", $result->getErrorMessages());
	}
}
