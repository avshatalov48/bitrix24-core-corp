/**
 * @module layout/ui/item-selector
 */
jn.define('layout/ui/item-selector', (require, exports, module) => {
	const { SelectField } = require('layout/ui/fields/select');
	const { Loc } = require('loc');
	const { chevronDown } = require('assets/common');
	const AppTheme = require('apptheme');

	/**
	 * @class ItemSelector
	 */
	class ItemSelector extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				selected: props.value ? props.value.map((item) => String(item)) : [],
			};

			this.maxElements = this.props.maxElements && this.props.maxElements >= 0 ? this.props.maxElements : 2;
		}

		componentWillReceiveProps(props)
		{
			this.setState({ selected: props.value ? props.value.map((item) => String(item)) : [] });
		}

		get fontSize()
		{
			return this.props.fontSize ?? 13;
		}

		get imageSize()
		{
			return this.props.imageSize ?? 13;
		}

		render()
		{
			let { valuesList: preparedValues } = this.props;
			const { inline, emptyState } = this.props;
			const { selected } = this.state;

			if (selected.length === 0)
			{
				preparedValues = [];
			}
			else
			{
				preparedValues = preparedValues
					.filter((item) => selected.includes(String(item.id)))
					.slice(0, selected.length > this.maxElements ? this.maxElements - 1 : selected.length)
					.map((item) => ({
						...item,
						value: item.title.toLowerCase(),
						isDashed: true,
					}));
			}

			return View(
				{
					style: {
						flexDirection: inline ? 'row' : 'column',
						alignItems: this.props.style?.alignItems ?? 'center',
						marginLeft: 2,
					},
					onClick: () => this.openSelector(),
				},
				View(
					{
						style: {
							flexDirection: inline ? 'row' : 'column',
							marginLeft: 2,
						},
					},
					selected.length === 0 && this.renderTextView({
						value: emptyState,
						isDashed: false,
					}),
					...preparedValues.map((item, index) => View(
						{
							style: {
								color: AppTheme.colors.base4,
								fontSize: this.fontSize,
								flexDirection: 'row',
								marginLeft: index === 0 ? 0 : 4,
							},
						},
						this.renderTextView(item),
						selected.length > index + 1 && Text(
							{
								text: ',',
								style: {
									color: AppTheme.colors.base4,
									fontSize: 13,
								},
							},
						),
					)),
					selected.length > this.maxElements && this.renderTextView({
						value: ` ${Loc.getMessage('MOBILE_UI_ITEM_SELECTOR_VIEW_MORE', {
							'#COUNT#': selected.length - this.maxElements + 1,
						})}`,
						isDashed: false,
					}),
				),
				Image(
					{
						tintColor: AppTheme.colors.base4,
						style: {
							height: this.imageSize,
							width: this.imageSize,
						},
						svg: {
							content: chevronDown(AppTheme.colors.base4, { box: true }),
						},
					},
				),
			);
		}

		getSelectedValues()
		{
			return this.state.selected;
		}

		renderTextView({ value, isDashed })
		{
			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				Text(
					{
						text: value,
						style: {
							color: AppTheme.colors.base4,
							fontSize: this.fontSize ?? 13,
						},
					},
				),
				isDashed && View(
					{
						style: {
							height: 2,
							borderBottomWidth: 1,
							borderBottomColor: AppTheme.colors.base5,
							borderStyle: 'dash',
							borderDashSegmentLength: 3,
							borderDashGapLength: 3,
						},
					},
				),
			);
		}

		openSelector()
		{
			const selected = this.state.selected;

			const items = this.props.valuesList.map((item) => {
				return {
					name: item.title,
					value: item.id,
				};
			});

			const select = SelectField({
				config: {
					type: 'selector',
					items,
					isSearchEnabled: false,
					parentWidget: this.props.layout || PageManager,
				},
				title: this.props.selectorTitle,
				multiple: true,
				value: selected,
				onChange: (newSelect) => {
					this.setState(
						{ selected: newSelect },
						() => this.props.onChange && this.props.onChange(this.state.selected),
					);
				},
			});

			select
				.openSelector()
				.catch((reject) => console.error(reject));
		}
	}

	module.exports = { ItemSelector };
});
