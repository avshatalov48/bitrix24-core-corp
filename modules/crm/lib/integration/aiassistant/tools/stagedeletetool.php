<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\AiAssistant\Tools;

class StageDeleteTool extends BaseCrmTool
{
	protected function executeTool(int $userId, ...$args): string
	{
		$operation = new \Bitrix\Crm\Integration\AI\Function\Category\Stage\Delete(currentUserId: $userId);
		$result = $operation->invoke(
			entityTypeId: $args['entityTypeId'],
			categoryId: $args['categoryId'],
			stageId: $args['stageId'],
		);

		return $result->isSuccess()
			? "Stage with ID '{$args['stageId']}' successfully deleted from category '{$args['categoryId']}' for entity type '{$args['entityTypeId']}'."
			: "Error deleting stage: " . implode(", ", $result->getErrorMessages());
	}

	public function getName(): string
	{
		return 'delete_funnel_stage';
	}

	public function getDescription(): string
	{
		return 'Deletes the stage identified by stageId for the entity with the entity type identifier entityTypeId in the funnel identified by categoryId.';
	}

	public function getInputSchema(): array
	{
		return [
			"type" => "object",
			"properties" => [
				"entityTypeId" => [
					"type" => "number",
					"description" => "CRM entity type identifier. Possible values: 1 (Lead), 2 (Deal), 7 (Proposal), 31 (Invoice), or an identifier of a smart-process type",
				],
				"categoryId" => [
					"type" => [
						"number",
						"null",
					],
					"description" => "Identifier of the CRM entity funnel. Must be null if entityTypeId is 1 or 7. In all other cases, categoryId must not be null",
				],
				"stageId" => [
					"type" => "string",
					"description" => "Identifier of the funnel stage to be deleted. Must not be an empty string.",
				],
			],
			"required" => [
				"entityTypeId",
				"categoryId",
				"stageId",
			],
		];
	}
}
