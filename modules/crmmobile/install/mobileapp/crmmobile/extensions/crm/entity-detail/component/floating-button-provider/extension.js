/**
 * @module crm/entity-detail/component/floating-button-provider
 */
jn.define('crm/entity-detail/component/floating-button-provider', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { PlanRestriction } = require('layout/ui/plan-restriction');
	const { ImageAfterTypes } = require('layout/ui/context-menu/item');
	const { FloatingMenuItem } = require('layout/ui/detail-card/floating-button/menu/item');
	const { get } = require('utils/object');
	const { getActionToConversion } = require('crm/entity-actions/conversion');
	const { TimelineSchedulerDocumentProvider } = require('crm/timeline/scheduler/providers/document');

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
				preActionHandler: (detailCard) => onAction({
					uid: detailCard.getUid(),
					entityId: detailCard.getEntityId(),
					entityTypeId: detailCard.getEntityTypeId(),
					onFinishConverted: () => {
						detailCard.emitEntityUpdate('update');

						return detailCard.reloadTabs();
					},
				}),
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
				icon: `<svg width="31" height="31" viewBox="0 0 31 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.84668 6.55583C7.84668 5.84974 8.41908 5.27734 9.12516 5.27734H17.1259C17.8403 5.27734 18.5221 5.57622 19.0062 6.10158L22.5409 9.93739C22.976 10.4095 23.2175 11.0281 23.2175 11.6701V17.2341C22.8605 17.1805 22.4951 17.1527 22.1231 17.1527C18.0903 17.1527 14.821 20.4218 14.8208 24.4546H9.12516C8.41908 24.4546 7.84668 23.8822 7.84668 23.1761V6.55583ZM11.4413 10.3691C10.8837 10.3691 10.4317 10.8212 10.4317 11.3788C10.4317 11.9365 10.8837 12.3885 11.4414 12.3885H18.8792C19.4369 12.3885 19.8889 11.9365 19.8889 11.3788C19.8889 10.8212 19.4369 10.3691 18.8792 10.3691H11.4413ZM11.4413 14.2046C10.8837 14.2046 10.4317 14.6567 10.4317 15.2143C10.4317 15.7719 10.8837 16.224 11.4414 16.224H18.8792C19.4369 16.224 19.8889 15.7719 19.8889 15.2143C19.8889 14.6567 19.4369 14.2046 18.8792 14.2046H11.4413Z" fill="#767C87"/><path fill-rule="evenodd" clip-rule="evenodd" d="M23.2084 20.0762H21.0379V23.3698L17.7444 23.3698L17.7444 25.5403H21.0379V28.8335H23.2084V25.5403H26.5017V23.3698L23.2084 23.3698V20.0762Z" fill="${AppTheme.colors.base3}"/></svg>`,
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
				icon: `
					<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path
							fill-rule="evenodd"
							clip-rule="evenodd"
							d="M13.5772 3.57644C13.5772 3.24235 13.9811 3.07503 14.2174 3.31127L17.4634 6.55733C17.6099 6.70377 17.6099 6.94121 17.4634 7.08766L15.3142 9.23692C15.2101 9.23134 15.1054 9.22852 15 9.22852C14.509 9.22852 14.0323 9.28983 13.5772 9.40522V8.29212L13.5624 8.29556C10.7211 8.90185 8.53175 11.2686 8.1908 14.1993C8.17436 14.3406 8.08435 14.4639 7.95226 14.5166L5.5016 15.4956C5.26529 15.59 5.00574 15.4265 5.00145 15.1721C5.00048 15.1148 5 15.0575 5 15.0001C5 10.0012 8.66794 5.85897 13.4593 5.11801C13.4998 5.11174 13.5395 5.11249 13.5772 5.11928V3.57644ZM20.5482 16.5951C20.4131 17.0662 20.2195 17.5125 19.9759 17.9259L20.9799 21.2313C21.0401 21.4295 21.2495 21.5413 21.4477 21.4811L25.8401 20.147C26.1598 20.0499 26.2025 19.6148 25.9078 19.4574L24.3273 18.6133C24.7617 17.4926 25 16.2742 25 15.0001C25 10.8574 22.481 7.30314 18.8912 5.78536C18.6518 5.68417 18.3939 5.86479 18.3939 6.12462V8.82453C18.3939 8.95916 18.4665 9.08277 18.5812 9.15319C20.5454 10.3588 21.8555 12.5264 21.8555 15.0001C21.8555 15.7383 21.7388 16.4493 21.5228 17.1156L20.5482 16.5951ZM10.9544 19.1162C10.5701 18.7384 10.2384 18.3073 9.97147 17.8347L6.83143 17.1111C6.62961 17.0646 6.4283 17.1905 6.3818 17.3924L5.35096 21.8657C5.27594 22.1913 5.63198 22.445 5.91523 22.2679L7.31143 21.3947C9.14576 23.5978 11.9091 25.0001 15 25.0001C16.7244 25.0001 18.3468 24.5636 19.7629 23.7951C19.9702 23.6826 20.0108 23.4065 19.8527 23.2315L18.1793 21.3795C18.0674 21.2558 17.8877 21.2217 17.7348 21.2883C16.897 21.6532 15.9721 21.8555 15 21.8555C13.0352 21.8555 11.2635 21.029 10.0136 19.7046L10.9544 19.1162ZM11.2244 12.1401C11.2244 11.933 11.389 11.7651 11.5921 11.7651H18.4086C18.6118 11.7651 18.7764 11.933 18.7764 12.1401V13.8901C18.7764 14.0972 18.6118 14.2651 18.4086 14.2651H11.5921C11.389 14.2651 11.2244 14.0972 11.2244 13.8901V12.1401ZM11.2244 16.1099C11.2244 15.9028 11.389 15.7349 11.5921 15.7349H18.4086C18.6118 15.7349 18.7764 15.9028 18.7764 16.1099V17.8599C18.7764 18.067 18.6118 18.2349 18.4086 18.2349H11.5921C11.389 18.2349 11.2244 18.067 11.2244 17.8599V16.1099Z"
							fill="${AppTheme.colors.base3}"
						/>
					</svg>
				`,
			}),
		];
	};

	module.exports = { floatingButtonProvider };
});
