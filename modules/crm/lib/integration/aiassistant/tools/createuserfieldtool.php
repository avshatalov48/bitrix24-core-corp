<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\AiAssistant\Tools;

class CreateUserFieldTool extends BaseCrmTool
{
	public function getName(): string
	{
		return 'create_crm_custom_field';
	}

	public function getDescription(): string
	{
		return "Creates a custom field for a CRM deal. Use this function when a new CRM field needs to be created";
	}

	public function getInputSchema(): array
	{
		return [
			'type' => 'object',
			'properties' => [
				'categoryId' => [
					'description' => 'Identifier of the CRM deal funnel, also referred to as \'category\'',
					'type' => 'number',
				],
				'label' => [
					'description' => 'Name of the custom field to be created',
					'type' => 'string',
				],
				'type' => [
					'description' => 'Type of the custom field being created',
					'type' => 'string',
					'enum' => ['double', 'integer', 'string', 'date', 'datetime', 'enumeration', 'file', 'boolean'],
				],
				'isMultiple' => [
					'description' => 'Indicates if the custom field accepts multiple values. Always false for boolean fields. For enumeration fields, set to true if multiple options can be chosen (similar to a multi-select list); otherwise, set to false (standard single-select enumeration)',
					'type' => 'boolean',
				],
				'enumerationList' => [
					'description' => 'List of selectable options for a custom enumeration-type field. Required if the field type is set to \'enumeration\'',
					'type' => 'array',
					'items' => [
						'type' => 'object',
						'properties' => [
							'value' => [
								'description' => 'The display name of a selectable option in the enumeration',
								'type' => 'string',
							],
						],
						'required' => ['value'],
					],
				],
			],
			'additionalProperties' => false,
			'required' => ['categoryId', 'label', 'type', 'isMultiple'],
		];
	}

	public function executeTool(int $userId, ...$args): string
	{
		$operation = new \Bitrix\Crm\Integration\AI\Function\UserField\UniversalCreateUserField(currentUserId: $userId);
		$result = $operation->invoke(
			entityTypeId: \CCrmOwnerType::Deal,
			categoryId: $args['categoryId'],
			label: $args['label'],
			type: $args['type'],
			isMultiple: $args['isMultiple'],
			enumerationList: $args['enumerationList'] ?? null,
		);

		return $result->isSuccess()
			? "User field '{$args['label']}' created successfully"
			: "Error creating user field: " . implode(", ", $result->getErrorMessages());
	}
}
