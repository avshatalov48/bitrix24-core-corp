/**
 * @module crm/crm-mode/wizard/layouts/mode/block
 */
jn.define('crm/crm-mode/wizard/layouts/mode/block', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { TypeId } = require('crm/type');
	const { getEntityMessage } = require('crm/loc');
	const { applyCheck } = require('crm/assets/common');
	const { plus, arrowRight } = require('assets/common');
	const { label } = require('crm/crm-mode/wizard/layouts/mode/label');
	const { MODES, ENTITY_COLORS } = require('crm/crm-mode/wizard/layouts/src/constants');
	const { makeImagesPath } = require('crm/crm-mode/wizard/layouts/src/images');

	/**
	 * @class ModeBlock
	 */
	class ModeBlock extends LayoutComponent
	{
		getLabelByEntityId(entityId)
		{
			return label(
				{
					text: getEntityMessage('MCRM_CRM_MODE_LAYOUTS_MODE_LABEL', entityId),
					...ENTITY_COLORS[entityId],
				},
			);
		}

		renderLabels(mode)
		{
			const isClassicMode = mode === MODES.classic;

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						marginLeft: -2,
					},
				},
				isClassicMode && View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
						},

					},
					this.getLabelByEntityId(TypeId.Lead),
					Image({
						style: {
							width: 12,
							height: 12,
						},
						svg: {
							content: arrowRight(AppTheme.colors.base4),
						},
					}),
				),
				this.getLabelByEntityId(TypeId.Deal),
				Image({
					style: {
						width: 12,
						height: 12,
					},
					svg: {
						content: plus(AppTheme.colors.base4),
					},
				}),
				this.renderLabelsOverlay([TypeId.Company, TypeId.Contact]),
			)
			;
		}

		renderLabelsOverlay(entityTypeIds)
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				...entityTypeIds.map((entityTypeId, i) => {
					const isFirst = i === 0;

					return View(
						{
							style: {
								marginLeft: isFirst ? 0 : -6,
								zIndex: -i,
							},
						},
						this.getLabelByEntityId(entityTypeId),
					);
				}),
			);
		}

		renderDelimiter()
		{
			return View({
				style: {
					flex: 1,
					borderTopWidth: 1,
					borderTopColor: AppTheme.colors.base7,
					marginBottom: 14,
					marginTop: 8,
				},
			});
		}

		render()
		{
			const { active, mode, onClick } = this.props;

			return View(
				{},
				active && Image({
					style: {
						width: 25,
						height: 25,
						position: 'absolute',
						top: 0,
						right: 0,
						backgroundColor: AppTheme.colors.bgContentPrimary,
						zIndex: 10,
					},
					svg: {
						content: applyCheck,
					},
				}),
				View(
					{
						style: {
							borderWidth: 1,
							borderColor: active ? AppTheme.colors.accentBrandBlue : AppTheme.colors.base6,
							borderRadius: 12,
							paddingTop: 14,
							paddingBottom: 16,
							paddingHorizontal: 18,
							marginBottom: mode === MODES.simple ? 12 : 0,
							zIndex: 1,
						},
						onClick: () => {
							onClick(mode);
						},
					},
					View(
						{
							style: {
								flexDirection: 'row',
								alignItems: 'center',
							},
						},
						Image({
							style: {
								width: 52,
								height: 52,
								alignSelf: 'center',
							},
							svg: {
								uri: makeImagesPath(mode.toLowerCase(), active),
							},
						}),
						Text(
							{
								text: Loc.getMessage(`MCRM_CRM_MODE_LAYOUTS_MODE_${mode}_TITLE`),
								style: {
									marginLeft: 14,
									fontSize: 18,
									color: active ? AppTheme.colors.base1 : AppTheme.colors.base3,
									alignSelf: 'center',
								},
							},
						),
					),
					this.renderDelimiter(),
					this.renderLabels(mode),
					Text(
						{
							text: Loc.getMessage(`MCRM_CRM_MODE_LAYOUTS_MODE_${mode}_DESCRIPTION`),
							style: {
								color: AppTheme.colors.base3,
								marginTop: 10,
							},
						},
					),
				),
			);
		}
	}

	module.exports = { ModeBlock };
});
