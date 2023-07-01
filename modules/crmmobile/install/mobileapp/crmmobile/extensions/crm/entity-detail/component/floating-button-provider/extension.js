/**
 * @module crm/entity-detail/component/floating-button-provider
 */
jn.define('crm/entity-detail/component/floating-button-provider', (require, exports, module) => {
	const { Loc } = require('loc');
	const { PlanRestriction } = require('layout/ui/plan-restriction');
	const { FloatingMenuItem } = require('layout/ui/detail-card/floating-button/menu/item');
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
					result.forEach(({ value: menu }) => {
						if (!isAvailableConversion)
						{
							PlanRestriction.open({ title: conversionTitle });

							return null;
						}

						if (menu instanceof ContextMenu)
						{
							return menu.show();
						}

						throw new Error('Context menu not found', result);
					});
				}),
				icon: svgIcon,
				iconAfter: !isAvailableConversion && { type: ContextMenuItem.ImageAfterTypes.LOCK },
				showArrow: isAvailableConversion,
			}),
			new FloatingMenuItem({
				id: 'crm-document',
				title: Loc.getMessage('M_CRM_DETAIL_MENU_ITEM_DOCUMENT'),
				isSupported: true,
				isAvailable: (detailCard) => {
					const { isDocumentPreviewerAvailable } = detailCard.getComponentParams();
					if (!isDocumentPreviewerAvailable)
					{
						return false;
					}

					return !detailCard.isNewEntity() && !detailCard.isReadonly();
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
				icon: '<svg width="31" height="31" viewBox="0 0 31 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.84668 6.55583C7.84668 5.84974 8.41908 5.27734 9.12516 5.27734H17.1259C17.8403 5.27734 18.5221 5.57622 19.0062 6.10158L22.5409 9.93739C22.976 10.4095 23.2175 11.0281 23.2175 11.6701V17.2341C22.8605 17.1805 22.4951 17.1527 22.1231 17.1527C18.0903 17.1527 14.821 20.4218 14.8208 24.4546H9.12516C8.41908 24.4546 7.84668 23.8822 7.84668 23.1761V6.55583ZM11.4413 10.3691C10.8837 10.3691 10.4317 10.8212 10.4317 11.3788C10.4317 11.9365 10.8837 12.3885 11.4414 12.3885H18.8792C19.4369 12.3885 19.8889 11.9365 19.8889 11.3788C19.8889 10.8212 19.4369 10.3691 18.8792 10.3691H11.4413ZM11.4413 14.2046C10.8837 14.2046 10.4317 14.6567 10.4317 15.2143C10.4317 15.7719 10.8837 16.224 11.4414 16.224H18.8792C19.4369 16.224 19.8889 15.7719 19.8889 15.2143C19.8889 14.6567 19.4369 14.2046 18.8792 14.2046H11.4413Z" fill="#767C87"/><path fill-rule="evenodd" clip-rule="evenodd" d="M23.2084 20.0762H21.0379V23.3698L17.7444 23.3698L17.7444 25.5403H21.0379V28.8335H23.2084V25.5403H26.5017V23.3698L23.2084 23.3698V20.0762Z" fill="#767C87"/></svg>',
			}),
		];
	};

	module.exports = { floatingButtonProvider };
});
