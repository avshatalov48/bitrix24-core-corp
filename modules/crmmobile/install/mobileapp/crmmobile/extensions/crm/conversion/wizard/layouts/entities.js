/**
 * @module crm/conversion/wizard/layouts/entities
 */
jn.define('crm/conversion/wizard/layouts/entities', (require, exports, module) => {
	const { Loc } = require('loc');
	const { unique } = require('utils/array');
	const { NotifyManager } = require('notify-manager');
	const { menuButton } = require('layout/ui/context-menu/button');
	const { getEntityMessage } = require('crm/loc');
	const { EntityBoolean } = require('crm/ui/entity-boolean');

	/**
	 * @class ConversionWizardEntitiesLayout
	 */
	class ConversionWizardEntitiesLayout extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const { recentActivityIds } = props;

			this.state = {
				entityIds: {},
				entityTypeIds: recentActivityIds,
			};
		}

		onChange({ entityIds = {}, entityTypeIds = [] })
		{
			const { onChange } = this.props;

			this.setState({
				entityIds,
				entityTypeIds,
			}, () => {
				onChange(this.state);
			});
		}

		onFinish(params)
		{
			const { moveToNextStep, onChange } = this.props;
			onChange(params);
			moveToNextStep();
		}

		renderBooleanEntities()
		{
			const { entityTypeIds: recentActivityIds } = this.state;
			const { entityTypeIds: initialEntityTypeIds, isSingleEntity } = this.props;
			const booleanEntities = initialEntityTypeIds.map((entityTypeId, i) => {
				const fieldText = getEntityMessage('MCRM_CONVERSION_WIZARD_LAYOUT_ENTITY', entityTypeId);

				return EntityBoolean({
					entityTypeId,
					enable: recentActivityIds.includes(entityTypeId),
					onChange: (selectedTypeId, enable) => {
						let entityTypeIds = [];
						if (isSingleEntity)
						{
							entityTypeIds = initialEntityTypeIds.filter((initialEntityTypeId) => (enable
								? initialEntityTypeId === selectedTypeId
								: initialEntityTypeId !== selectedTypeId));
						}
						else
						{
							const uniqEntityTypeIds = unique([...recentActivityIds, selectedTypeId]);
							entityTypeIds = enable
								? uniqEntityTypeIds
								: uniqEntityTypeIds.filter((id) => id !== selectedTypeId);
						}

						this.onChange({ entityTypeIds });
					},
					text: fieldText,
					disabledText: fieldText,
					styles: {
						block: {
							marginTop: Boolean(i) && 8,
						},
					},
				});
			});

			return View(
				{
					style: {
						borderRadius: 12,
						backgroundColor: '#ffffff',
						paddingVertical: 14,
						paddingHorizontal: 16,
						marginBottom: 10,
					},
				},
				...booleanEntities,
			);
		}

		renderSelectorButton()
		{
			const { entityTypeId, permissions, getLayoutWidget, ConversionSelector, isReturnCustomer } = this.props;

			if (!ConversionSelector || !ConversionSelector.hasConversionSelectorMenuButton({
				entityTypeId,
				permissions,
				isReturnCustomer,
			}))
			{
				return null;
			}

			return menuButton({
				title: Loc.getMessage('MCRM_CONVERSION_WIZARD_LAYOUT_SELECTOR_TITLE'),
				showArrow: true,
				style: {
					container: {
						marginBottom: 10,
					},
				},
				onClickCallback: async () => {
					const show = await NotifyManager.showLoadingIndicator();
					const layoutWidget = getLayoutWidget();
					if (!layoutWidget)
					{
						return;
					}

					if (show)
					{
						ConversionSelector.open({
							permissions,
							layoutWidget,
							onChange: this.onFinish.bind(this),
						});
					}
				},
			});
		}

		render()
		{
			return View(
				{
					style: {
						width: '100%',
						height: '100%',
						flexDirection: 'column',
						backgroundColor: '#eef2f4',
						borderRadius: 12,
					},
				},
				this.renderBooleanEntities(),
				this.renderSelectorButton(),
				menuButton({
					type: 'cancel',
					onClickCallback: () => {
						const { getLayoutWidget } = this.props;
						const layoutWidget = getLayoutWidget();

						if (layoutWidget)
						{
							layoutWidget.close();
						}
					},
				}),
			);
		}
	}

	module.exports = { ConversionWizardEntitiesLayout };
});
