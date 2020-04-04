<?php
return [
	'P' => [
		[
			'CODE' => \Bitrix\Crm\Automation\Trigger\PaymentTrigger::getCode(),
			'APPLY_RULES' =>
				[
					'Condition' =>
						[
							'type' => 'field',
							'items' =>
								[
									[['field' => 'PAYED', 'operator' => '=', 'value' => 'Y'], 'AND']
								],
						],
				],
			'NAME' => \Bitrix\Crm\Automation\Trigger\PaymentTrigger::getName(),
		]
	],
	'D' => [
		[
			'CODE' => \Bitrix\Crm\Automation\Trigger\OrderCanceledTrigger::getCode(),
			'NAME' => \Bitrix\Crm\Automation\Trigger\OrderCanceledTrigger::getName(),
		]
	],
];