<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\AiAssistant\Tools;

class CategoryUpdateStagesList extends BaseCrmTool
{
	public function getName(): string
	{
		return 'update_funnel_stages';
	}

	public function getDescription(): string
	{
		return "Updates the 'In Progress' stages for the funnel identified by `categoryId`. The default stages remain unchanged. Use this function to modify the custom stages of an existing CRM funnel.";
	}

	public function getInputSchema(): array
	{
		return [
			'type' => 'object',
			'properties' => [
				'categoryId' => [
					'description' => 'Identifier of the target funnel',
					'type' => 'number',
				],
				'stages' => [
					'description' => 'Array of stage definitions (cannot be empty)',
					'type' => 'array',
					'items' => [
						'type' => 'object',
						'properties' => [
							'name' => [
								'description' => 'Name of the stage',
								'type' => 'string',
							],
							'color' => [
								'description' => 'Color code in HEX format (e.g., "#FF0000")',
								'type' => 'string',
							],
						],
						'required' => ['name'],
					],
				],
			],
			'additionalProperties' => false,
			'required' => ['categoryId', 'stages'],
		];
	}

	public function executeTool(int $userId, ...$args): string
	{
		$operation = new \Bitrix\Crm\Integration\AI\Function\Deal\Category\UpdateStageList(currentUserId: $userId);
		$result = $operation->invoke(categoryId: $args['categoryId'], stages: $args['stages']);

		return $result->isSuccess()
			? "Stages list for category '{$args['categoryId']}' successfully updated"
			: "Error updating stage list for category: " . implode(", ", $result->getErrorMessages());
	}
}
