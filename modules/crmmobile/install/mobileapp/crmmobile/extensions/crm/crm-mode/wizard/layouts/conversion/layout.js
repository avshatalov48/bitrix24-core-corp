/**
 * @module crm/crm-mode/wizard/layouts/conversion/layout
 */
jn.define('crm/crm-mode/wizard/layouts/conversion/layout', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BackdropHeader } = require('layout/ui/banners');
	const { EXTENSION_PATH } = require('crm/crm-mode/wizard/layouts/constants');
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
				categoryId: this.category.id || categoryId,
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
						backgroundColor: '#ffffff',
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
							color: '#828b95',
						},
					},
				),
			);
		}

		render()
		{
			return ScrollView(
				{
					style: {
						height: '100%',
						backgroundColor: '#eef2f4',
					},
				},
				View(
					{},
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
							image: `${EXTENSION_PATH}/conversion.png`,
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
				),
			);
		}
	}

	module.exports = { ConversionLayout };
});
