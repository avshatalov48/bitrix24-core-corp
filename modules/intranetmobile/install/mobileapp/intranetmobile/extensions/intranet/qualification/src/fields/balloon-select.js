/**
 * @module intranet/qualification/fields/balloon-select
 */
jn.define('intranet/qualification/fields/balloon-select', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Icon, IconView } = require('ui-system/blocks/icon');
	const { Text4 } = require('ui-system/typography');
	const { Color, Indent } = require('tokens');
	const { PropTypes } = require('utils/validation');
	const { createTestIdGenerator } = require('utils/test');

	class BalloonSelect extends PureComponent
	{
		/**
		 * @param {Object} props
		 * @param {string} props.testId
		 * @param {Array} [props.items=[]]
		 * @param {Array} [props.selectedItemIds=[]]
		 * @param {Function} [props.onChange=null]
		 */
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({ context: this });

			this.#updateState(this.props);
		}

		componentWillReceiveProps(nextProps)
		{
			this.#updateState(nextProps);
		}

		#updateState(newState)
		{
			this.state = {
				selectedItemIds: newState.selectedItemIds,
			};
		}

		render()
		{
			return View(
				{
					style: {
						padding: Indent.XS.toNumber(),
					},
					testId: this.getTestId('container'),
				},
				...this.props.items.map((item, index) => this.#renderItem(item, index === 0)),
			);
		}

		#renderItem(item, isFirst)
		{
			let borderWidth = 0;
			let backgroundColor = Color.accentSoftBlue2;
			let textColor = this.#isAnyItemSelected() ? Color.base4 : Color.base1;
			let iconColor = backgroundColor;

			if (this.#isItemSelected(item.id))
			{
				borderWidth = 2;
				backgroundColor = Color.bgContentPrimary;
				textColor = Color.accentMainPrimary;
				iconColor = Color.accentMainPrimaryalt;
			}

			return View(
				{
					style: {
						borderWidth,
						flexDirection: 'row',
						alignItems: 'center',
						paddingLeft: Indent.XL3.toNumber(),
						paddingRight: Indent.L.toNumber(),
						paddingVertical: Indent.XL.toNumber(),
						marginTop: isFirst ? 0 : Indent.XL2.toNumber(),
						borderRadius: 24,
						borderColor: Color.accentMainPrimaryalt.toHex(),
						backgroundColor: backgroundColor.toHex(),
					},
					testId: this.getTestId('item-container'),
					onClick: () => this.#onItemClick(item.id),
				},
				Text4({
					style: {
						flex: 1,
					},
					accent: true,
					color: textColor,
					text: item.title,
					testId: this.getTestId('item-field'),
				}),
				IconView({
					style: {
						marginLeft: Indent.XL2.toNumber(),
					},
					icon: Icon.CHECK,
					size: 24,
					color: iconColor,
				}),
			);
		}

		#onItemClick(itemId)
		{
			const { items, onChange } = this.props;

			const selectedItemIds = this.#isItemSelected(itemId) ? [] : [itemId];
			const selectedItems = Object.fromEntries(
				selectedItemIds.map((id) => [id, items.find((item) => item.id === id).value]),
			);

			this.setState({ selectedItemIds }, () => onChange?.(selectedItems));
		}

		#isItemSelected(itemId)
		{
			return this.state.selectedItemIds.includes(itemId);
		}

		#isAnyItemSelected()
		{
			return this.state.selectedItemIds.length > 0;
		}
	}

	BalloonSelect.defaultProps = {
		items: [],
		selectedItemIds: [],
		onChange: null,
	};

	BalloonSelect.propTypes = {
		testId: PropTypes.string.isRequired,
		items: PropTypes.arrayOf(
			PropTypes.shape({
				id: PropTypes.string,
				title: PropTypes.string,
				value: PropTypes.any,
			}),
		),
		selectedItemIds: PropTypes.arrayOf(PropTypes.string),
		onChange: PropTypes.func,
	};

	module.exports = { BalloonSelect };
});
