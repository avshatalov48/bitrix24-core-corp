<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\AiAssistant\Tools;

class CategoryCreateWithStages extends BaseCrmTool
{
	public function getName(): string
	{
		return 'create_funnel_with_custom_stages';
	}

	public function getDescription(): string
	{
		return "Creates a new funnel with the specified name and custom 'In Progress' stages, while keeping the default stages unchanged. Use this to add a customized funnel to the CRM.";
	}

	public function getInputSchema(): array
	{
		return [
			'type' => 'object',
			'properties' => [
				'name' => [
					'description' => 'Name of the funnel to be created',
					'type' => 'string',
				],
				'stages' => [
					'description' => 'Array of custom stages (cannot be empty)',
					'type' => 'array',
					'items' => [
						'type' => 'object',
						'properties' => [
							'name' => [
								'description' => 'Stage name',
								'type' => 'string',
							],
							'color' => [
								'description' => 'Stage color in HEX format',
								'type' => 'string',
							],
						],
						'required' => ['name'],
					],
				],
			],
			'additionalProperties' => false,
			'required' => ['name', 'stages'],
		];
	}

	public function executeTool(int $userId, ...$args): string
	{
		$operation = new \Bitrix\Crm\Integration\AI\Function\Deal\Category\CreateWithStages(currentUserId: $userId);
		$result = $operation->invoke(name: $args['name'], stages: $args['stages']);

		return $result->isSuccess()
			? "Category '{$args['name']}' successfully created with stages"
			: "Error creating category: " . implode(", ", $result->getErrorMessages());
	}
}
