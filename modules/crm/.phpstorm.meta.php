<?php
namespace PHPSTORM_META
{
	registerArgumentsSet('bitrix_crm_serviceLocator_codes',
		'crm.kanban.entity.lead',
		'crm.kanban.entity.deal',
		'crm.kanban.entity.invoice',
		'crm.kanban.entity.quote',
		'crm.kanban.entity.order',
	);

	expectedArguments(\Bitrix\Main\DI\ServiceLocator::get(), 0, argumentsSet('bitrix_crm_serviceLocator_codes'));

	override(\Bitrix\Main\DI\ServiceLocator::get(0), map([
		'crm.kanban.entity.lead' => \Bitrix\Crm\Kanban\Entity\Lead::class,
		'crm.kanban.entity.deal' => \Bitrix\Crm\Kanban\Entity\Deal::class,
		'crm.kanban.entity.invoice' => \Bitrix\Crm\Kanban\Entity\Invoice::class,
		'crm.kanban.entity.quote' => \Bitrix\Crm\Kanban\Entity\Quote::class,
		'crm.kanban.entity.order' => \Bitrix\Crm\Kanban\Entity\Order::class,
	]));
}