/**
 * @module crm/crm-mode/wizard/layouts/conversion/layout
 */
jn.define('crm/crm-mode/wizard/layouts/conversion/layout', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { UIScrollView } = require('layout/ui/scroll-view');
	const { BackdropHeader } = require('layout/ui/banners');
	const { BannerImage } = require('crm/crm-mode/wizard/layouts/src/images');
	const { stageBlock } = require('crm/crm-mode/wizard/layouts/conversion/blocks/stage');
	const { entityBlock } = require('crm/crm-mode/wizard/layouts/conversion/blocks/entity');
	const { caseBlock } = require('crm/crm-mode/wizard/layouts/conversion/blocks/case');

	/**
	 * @class ConversionLayout
	 */
	class ConversionLayout extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const { getCategory, categoryId, moveCase, selectedEntities } = this.props;
			this.category = getCategory();

			this.result = {
				categoryId: this.category.categoryId || categoryId,
				moveCase,
				selectedEntities,
			};
		}

		handleOnChange({ name, value })
		{
			const { onChange } = this.props;
			this.result[name] = value;

			if (onChange)
			{
				onChange(this.result);
			}
		}

		renderBlock(params)
		{
			const { title, description, body } = params;
			const { getLayoutWidget, moveCase, selectedEntities } = this.props;

			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgContentPrimary,
						paddingHorizontal: 16,
						paddingVertical: 10,
						borderRadius: 12,
						marginBottom: 10,
					},
				},
				Text(
					{
						text: title,
						style: {
							color: AppTheme.colors.base1,
							fontSize: 16,
						},
					},
				),
				View(
					{
						style: {
							paddingVertical: 12,
						},
					},
					body({
						moveCase,
						category: this.category,
						getLayoutWidget,
						selectedEntities,
						onChange: this.handleOnChange.bind(this),
					}),
				),
				Text(
					{
						text: description,
						style: {
							fontSize: 14,
							color: AppTheme.colors.base4,
						},
					},
				),
			);
		}

		render()
		{
			return UIScrollView(
				{
					style: {
						height: '100%',
					},
					children: [
						View(
							{
								style: {
									marginBottom: 12,
									borderRadius: 12,
								},
							},
							BackdropHeader({
								title: Loc.getMessage('MCRM_CRM_MODE_LAYOUTS_CONVERSION_HEADER_TITLE'),
								description: Loc.getMessage('MCRM_CRM_MODE_LAYOUTS_CONVERSION_HEADER_DESCRIPTION'),
								image: BannerImage('conversion'),
								position: 'flex-start',
							}),
						),
						this.renderBlock({
							title: Loc.getMessage('MCRM_CRM_MODE_LAYOUTS_CONVERSION_STAGE_BLOCK_TITLE'),
							description: Loc.getMessage('MCRM_CRM_MODE_LAYOUTS_CONVERSION_STAGE_BLOCK_DESCRIPTION'),
							body: stageBlock,
						}),
						this.renderBlock({
							title: Loc.getMessage('MCRM_CRM_MODE_LAYOUTS_CONVERSION_ENTITIES_BLOCK_TITLE'),
							description: Loc.getMessage('MCRM_CRM_MODE_LAYOUTS_CONVERSION_ENTITIES_BLOCK_DESCRIPTION'),
							body: entityBlock,
						}),
						this.renderBlock({
							title: Loc.getMessage('MCRM_CRM_MODE_LAYOUTS_CONVERSION_CASE_BLOCK_TITLE'),
							description: Loc.getMessage('MCRM_CRM_MODE_LAYOUTS_CONVERSION_CASE_BLOCK_DESCRIPTION'),
							body: caseBlock,
						}),
					],
				},
			);
		}
	}

	module.exports = { ConversionLayout };
});
