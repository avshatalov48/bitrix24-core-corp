<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\AiAssistant\Tools;

class CategoryMoveItems extends BaseCrmTool
{
	public function getName(): string
	{
		return 'move_deals_between_funnels';
	}

	public function getDescription(): string
	{
		return "Moves all deals (maximum 100) from one funnel identified by `from` to another funnel identified by `to`. Use this function when explicitly instructed to transfer deals between different funnels";
	}

	public function getInputSchema(): array
	{
		return [
			'type' => 'object',
			'properties' => [
				'from' => [
					'description' => 'Identifier of the funnel from which the deals will be transferred',
					'type' => 'number',
				],
				'to' => [
					'description' => 'Identifier of the funnel to which the deals will be transferred',
					'type' => 'number',
				],
			],
			'additionalProperties' => false,
			'required' => ['from', 'to'],
		];
	}

	public function executeTool(int $userId, ...$args): string
	{
		$operation = new \Bitrix\Crm\Integration\AI\Function\Deal\MoveBetweenCategory(currentUserId: $userId);
		$result = $operation->invoke(from: $args['from'], to: $args['to']);

		return $result->isSuccess()
			? "Items successfully moved from '{$args['from']}' to '{$args['to']}'"
			: "Error moving items between funnels: " . implode(", ", $result->getErrorMessages());
	}
}
