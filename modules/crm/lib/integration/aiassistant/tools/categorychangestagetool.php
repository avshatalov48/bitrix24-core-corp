<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\AiAssistant\Tools;

class CategoryChangeStageTool extends BaseCrmTool
{
	public function getName(): string
	{
		return 'move_deals_between_stages';
	}

	public function getDescription(): string
	{
		return "Moves all deals (maximum 100) from the stage identified by `from` to the stage identified by `to` within the funnel specified by `categoryId`. Use this function when explicitly instructed to transfer deals between different stages of the same funnel";
	}

	public function getInputSchema(): array
	{
		return [
			'type' => 'object',
			'properties' => [
				'categoryId' => [
					'description' => 'Identifier of the funnel within which deals are moved between stages',
					'type' => 'number',
				],
				'from' => [
					'description' => 'Identifier of the stage from which the deals should be transferred',
					'type' => 'string',
				],
				'to' => [
					'description' => 'Identifier of the stage to which the deals should be transferred',
					'type' => 'string',
				],
			],
			'additionalProperties' => false,
			'required' => ['categoryId', 'from', 'to'],
		];
	}

	public function executeTool(int $userId, ...$args): string
	{
		$operation = new \Bitrix\Crm\Integration\AI\Function\Deal\MoveBetweenStage(currentUserId: $userId);
		$result = $operation->invoke(categoryId: $args['categoryId'], from: $args['from'], to: $args['to']);

		return $result->isSuccess()
			? "Items in funnel {$args['categoryId']} successfully moved from '{$args['from']}' to '{$args['to']}'"
			: "Error changing items stage in funnel: " . implode(", ", $result->getErrorMessages());
	}
}
