/**
 * @module crm/entity-detail/component/floating-button-provider
 */
jn.define('crm/entity-detail/component/floating-button-provider', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');
	const { PlanRestriction } = require('layout/ui/plan-restriction');
	const { ImageAfterTypes } = require('layout/ui/context-menu/item');
	const { FloatingMenuItem } = require('layout/ui/detail-card/floating-button/menu/item');
	const { get } = require('utils/object');
	const { getActionToConversion } = require('crm/entity-actions/conversion');
	const { TimelineSchedulerDocumentProvider } = require('crm/timeline/scheduler/providers/document');
	const { AnalyticsEvent } = require('analytics');

	/**
	 * @returns {*[]}
	 */
	const floatingButtonProvider = (entityDetailCard) => {
		const { restrictions = {} } = entityDetailCard.getComponentParams();
		const isAvailableConversion = restrictions.conversion;

		const {
			id: conversionId,
			title: conversionTitle,
			svgIcon,
			onAction,
			canUseConversion,
		} = getActionToConversion();

		/* const newElementMenuItem = new FloatingMenuItem({
			id: 'crm-new-element',
			title: Loc.getMessage('M_CRM_DETAIL_MENU_ITEM_NEW_ENTITY'),
			isSupported: false,
			isAvailable: (detailCard) => !detailCard.isNewEntity(),
			position: 500,
			// ToDo new entity type elements
			// nestedItems: EntityType.getFloatingMenuItems(),
			icon: '<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M25.0393 15.5C25.0393 21.0446 20.5446 25.5393 15 25.5393C9.45542 25.5393 4.96066 21.0446 4.96066 15.5C4.96066 9.95545 9.45542 5.46069 15 5.46069C20.5446 5.46069 25.0393 9.95545 25.0393 15.5ZM13.75 10.5H16.25V14.25H20V16.75H16.25V20.5H13.75V16.75H9.99998V14.25H13.75V10.5Z" fill="#767C87"/></svg>',
		}); */
		return [
			new FloatingMenuItem({
				id: conversionId,
				title: conversionTitle,
				isSupported: true,
				isAvailable: (detailCard) => {
					if (detailCard.isNewEntity() || detailCard.isReadonly())
					{
						return false;
					}

					const entityTypeId = detailCard.getEntityTypeId();

					return canUseConversion(entityTypeId);
				},
				position: 400,
				preActionHandler: (detailCard) => {
					const analytics = new AnalyticsEvent(detailCard.getAnalyticsParams())
						.setEvent('entity_convert')
						.setSubSection('element_card')
						.setElement('add_floating_button');

					return onAction({
						uid: detailCard.getUid(),
						entityId: detailCard.getEntityId(),
						entityTypeId: detailCard.getEntityTypeId(),
						onFinishConverted: () => {
							detailCard.emitEntityUpdate('update');

							return detailCard.reloadTabs();
						},
						analytics,
					});
				},
				actionHandler: (detailCard, result) => new Promise(() => {
					result.forEach(({ value: conversion }) => {
						if (!isAvailableConversion)
						{
							PlanRestriction.open({ title: conversionTitle });

							return null;
						}

						if (!conversion)
						{
							throw new Error('Conversion not found', result);
						}

						return conversion();
					});
				}),
				icon: svgIcon,
				iconAfter: !isAvailableConversion && { type: ImageAfterTypes.LOCK },
				showArrow: isAvailableConversion,
			}),
			new FloatingMenuItem({
				id: 'crm-document',
				title: Loc.getMessage('M_CRM_DETAIL_MENU_ITEM_DOCUMENT'),
				isSupported: true,
				isAvailable: (detailCard) => {
					return (
						!detailCard.isNewEntity()
						&& !detailCard.isReadonly()
						&& get(detailCard.getComponentParams(), 'isDocumentGenerationEnabled', false)
					);
				},
				position: 600,
				actionHandler: (detailCard) => {
					const { documentGeneratorProvider } = detailCard.getComponentParams();

					TimelineSchedulerDocumentProvider.open({
						scheduler: {
							entity: {
								documentGeneratorProvider,
								typeId: detailCard.getEntityTypeId(),
								id: detailCard.getEntityId(),
							},
							parentWidget: PageManager,
						},
					});
				},
				showArrow: true,
				icon: Icon.CREATE_FILE,
			}),
			new FloatingMenuItem({
				id: 'bizproc-starter',
				title: Loc.getMessage('M_CRM_DETAIL_MENU_ITEM_BP_STARTER'),
				isSupported: true,
				isAvailable: (detailCard) => {
					const isNewEntity = detailCard.isNewEntity();
					const isAvailable = get(detailCard.getComponentParams(), 'isBizProcAvailable', false);
					const config = get(detailCard.getComponentParams(), 'bizProcStarterConfig', {});

					return (!isNewEntity && isAvailable && config.signedDocument !== undefined);
				},
				position: 700,
				actionHandler: (detailCard) => {
					const config = get(detailCard.getComponentParams(), 'bizProcStarterConfig', {});

					void requireLazy('bizproc:workflow/starter')
						.then(({ WorkflowStarter }) => {
							if (WorkflowStarter)
							{
								WorkflowStarter.open(config);
							}
						})
					;
				},
				showArrow: true,
				icon: Icon.BUSINESS_PROCESS,
			}),
		];
	};

	module.exports = { floatingButtonProvider };
});
