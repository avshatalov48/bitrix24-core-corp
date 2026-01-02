<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\AiAssistant\Tools;

class CategoryDeleteTool extends BaseCrmTool
{
	protected function executeTool(int $userId, ...$args): string
	{
		$operation = new \Bitrix\Crm\Integration\AI\Function\Category\Delete(currentUserId: $userId);
		$result = $operation->invoke(entityTypeId: $args['entityTypeId'], categoryId: $args['categoryId']);

		return $result->isSuccess()
			? "Funnel with ID {$args['categoryId']} successfully deleted for entity type {$args['entityTypeId']}"
			: "Error deleting funnel: " . implode(", ", $result->getErrorMessages());
	}

	public function getName(): string
	{
		return 'delete_funnel';
	}

	public function getDescription(): string
	{
		return 'Deletes the funnel identified by `categoryId` for the entity with the entity type identifier `entityTypeId`';
	}

	public function getInputSchema(): array
	{
		return [
			'type' => 'object',
			'properties' => [
				'entityTypeId' => [
					'description' => 'CRM entity type identifier. Possible values: 2 (Deal) or the identifier of a smart-process type',
					'type' => 'number',
				],
				'categoryId' => [
					'description' => 'Identifier of the CRM funnel to be deleted',
					'type' => 'number',
				],
			],
			'additionalProperties' => false,
			'required' => ['entityTypeId', 'categoryId'],
		];
	}
}
