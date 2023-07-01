/**
 * @module crm/crm-mode/wizard/layouts/conversion/blocks/stage
 */
jn.define('crm/crm-mode/wizard/layouts/conversion/blocks/stage', (require, exports, module) => {
	const { TypeId } = require('crm/type');
	const { getEntityMessage } = require('crm/loc');
	const { EntitySvg } = require('crm/assets/entity');
	const { CategorySvg } = require('crm/assets/category');
	const { arrowRight, chevronDown } = require('assets/common');
	const { openCategoryListView } = require('crm/category-list-view/open');

	const ICON_SIZE = 30;
	const ARROW_SIZE = 24;

	/**
	 * @class StageBlock
	 */
	class StageBlock extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = { category: props.category };
		}

		changePipeline()
		{
			const { onChange, getLayoutWidget } = this.props;
			const { category } = this.state;

			openCategoryListView({
				readOnly: false,
				needConfirm: false,
				categoryId: category.id,
				entityTypeId: TypeId.Deal,
				parentWidget: getLayoutWidget(),
				onChangeCategory: ({ category: selectedCategory, categoryListLayout }) => {
					categoryListLayout.close();

					this.setState(
						{ category: selectedCategory },
						() => {
							onChange({ name: 'categoryId', value: Number(selectedCategory.id) });
						},
					);
				},
			});
		}

		render()
		{
			const { category } = this.state;

			return View(
				{
					style: {
						alignItems: 'center',
						flexDirection: 'row',
					},
				},
				View(
					{
						style: {
							justifyContent: 'center',
							alignItems: 'center',
							flexDirection: 'row',
							backgroundColor: '#f2e9fe',
							borderRadius: 8,
							paddingHorizontal: 25,
							paddingVertical: 12,
						},
					},
					Image({
						resizeMode: 'cover',
						style: {
							width: ICON_SIZE,
							height: ICON_SIZE,
							marginRight: 8,
						},
						svg: {
							content: EntitySvg.dealInverted('#a77bde'),
						},
					}),
					Text({
						style: {
							fontSize: 16,
						},
						text: getEntityMessage('MCRM_CRM_MODE_LAYOUTS_CONVERSION', TypeId.Deal),
					}),
				),
				Image({
					resizeMode: 'cover',
					style: {
						width: ARROW_SIZE,
						height: ARROW_SIZE,
						marginHorizontal: 1,
					},
					svg: {
						content: arrowRight('#a8adb4'),
					},
				}),
				View(
					{
						style: {
							flex: 1,
							alignItems: 'center',
							paddingVertical: 12,
							paddingLeft: 10,
							paddingRight: 8,
							borderWidth: 1,
							borderColor: '#2fc6f6',
							borderRadius: 8,
							flexDirection: 'row',
						},
						onClick: this.changePipeline.bind(this),
					},
					Image({
						resizeMode: 'cover',
						style: {
							width: ICON_SIZE,
							height: ICON_SIZE,
							marginRight: 8,
						},
						svg: {
							content: CategorySvg.crmFunnel(),
						},
					}),
					View(
						{
							style: {
								flexDirection: 'row',
								flexShrink: 2,
							},
						},
						Text({
							style: {
								fontSize: 16,
								flexShrink: 2,
								marginRight: 2,
							},
							numberOfLines: 1,
							ellipsize: 'end',
							text: category.name,
						}),
						Image({
							style: {
								width: 18,
								height: 18,
								flexShrink: 0,
							},
							svg: {
								content: chevronDown('#a8adb4', { box: true }),
							},
						}),
					),
				),
			);
		}
	}

	module.exports = {
		stageBlock: (props) => new StageBlock(props),
	};
});
