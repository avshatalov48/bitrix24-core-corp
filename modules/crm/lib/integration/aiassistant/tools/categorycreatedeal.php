<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\AiAssistant\Tools;

class CategoryCreateDeal extends BaseCrmTool
{
	public function getName(): string
	{
		return 'create_deal';
	}

	public function getDescription(): string
	{
		return "Creates a deal with the title specified by `title` in the funnel identified by `categoryId`. Use this function when explicitly instructed to create a new deal in a particular CRM funnel";
	}

	public function getInputSchema(): array
	{
		return [
			'type' => 'object',
			'properties' => [
				'categoryId' => [
					'description' => 'Identifier of the funnel in which the deal will be created',
					'type' => 'number',
				],
				'title' => [
					'description' => 'Title of the deal. Must not be an empty string',
					'type' => 'string',
				],
			],
			'additionalProperties' => false,
			'required' => ['categoryId', 'title'],
		];
	}

	public function executeTool(int $userId, ...$args): string
	{
		$operation = new \Bitrix\Crm\Integration\AI\Function\Deal\Create(currentUserId: $userId);
		$result = $operation->invoke(categoryId: $args['categoryId'], title: $args['title']);

		return $result->isSuccess()
			? "Deal '{$args['title']}' successfully created in category '{$args['categoryId']}'"
			: "Error creating deal: " . implode(", ", $result->getErrorMessages());
	}
}
