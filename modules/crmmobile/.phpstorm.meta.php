<?php
namespace PHPSTORM_META
{
	registerArgumentsSet('bitrix_mobile_serviceLocator_codes',
		'crmmobile.kanban.entity.lead',
		'crmmobile.kanban.entity.deal',
		'crmmobile.kanban.entity.quote',
		'crmmobile.kanban.entity.smartInvoice',
		'crmmobile.kanban.entity.dynamicTypeBasedStatic',
		'crmmobile.kanban.entity.dynamic',
		'crmmobile.kanban.entity.contact',
		'crmmobile.kanban.entity.company',
	);

	expectedArguments(\Bitrix\Main\DI\ServiceLocator::get(), 0, argumentsSet('bitrix_mobile_serviceLocator_codes'));

	override(\Bitrix\Main\DI\ServiceLocator::get(0), map([
		'crmmobile.kanban.entity.lead' => \Bitrix\CrmMobile\Kanban\Entity\Lead::class,
		'crmmobile.kanban.entity.deal' => \Bitrix\CrmMobile\Kanban\Entity\Deal::class,
		'crmmobile.kanban.entity.quote' => \Bitrix\CrmMobile\Kanban\Entity\Quote::class,
		'crmmobile.kanban.entity.smartInvoice' => \Bitrix\CrmMobile\Kanban\Entity\SmartInvoice::class,
		'crmmobile.kanban.entity.dynamicTypeBasedStatic' => \Bitrix\CrmMobile\Kanban\Entity\DynamicTypeBasedStatic::class,
		'crmmobile.kanban.entity.dynamic' => \Bitrix\CrmMobile\Kanban\Entity\Dynamic::class,
		'crmmobile.kanban.entity.contact' => \Bitrix\CrmMobile\Kanban\Entity\Contact::class,
		'crmmobile.kanban.entity.company' => \Bitrix\CrmMobile\Kanban\Entity\Company::class,
	]));
}
