<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\AiAssistant\Tools;

class CategoryCreate extends BaseCrmTool
{
	public function getName(): string
	{
		return 'create_default_funnel';
	}

	public function getDescription(): string
	{
		return "Creates a new funnel with the specified `name` and sets the default stages. Use this function to add a standard funnel to the CRM without customizing the stages.";
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
			],
			'additionalProperties' => false,
			'required' => ['name'],
		];
	}

	public function executeTool(int $userId, ...$args): string
	{
		$operation = new \Bitrix\Crm\Integration\AI\Function\Deal\Category\Create(currentUserId: $userId);
		$result = $operation->invoke(name: $args['name']);

		return $result->isSuccess()
			? "Category '{$args['name']}' successfully created"
			: "Error creating category: " . implode(", ", $result->getErrorMessages());
	}
}
