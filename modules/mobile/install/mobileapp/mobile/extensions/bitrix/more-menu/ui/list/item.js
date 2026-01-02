/**
 * @module more-menu/ui/list/item
 */
jn.define('more-menu/ui/list/item', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Color, Corner, Indent } = require('tokens');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Text2 } = require('ui-system/typography/text');
	const { Ellipsize } = require('utils/enums/style');
	const { BadgeCounter, BadgeCounterDesign, BadgeCounterSize } = require('ui-system/blocks/badges/counter');
	const { createTestIdGenerator } = require('utils/test');
	const { PropTypes } = require('utils/validation');

	/**
	 * @class ListItem
	 */
	class ListItem extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.getTestId = createTestIdGenerator({
				prefix: props.testId,
			});
		}

		render()
		{
			const { style = {}, title } = this.props;

			return View(
				{
					testId: this.getTestId('wrapper'),
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						paddingHorizontal: Indent.XL.toNumber(),
						borderRadius: Corner.M.toNumber(),
						height: 49,
						...style,
					},
					onClick: this.#handleOnClick,
				},
				this.renderLeftContent(),
				Text2({
					text: title,
					testId: this.getTestId('title'),
					color: Color.base1,
					style: {
						flex: 2,
					},
					numberOfLines: 1,
					ellipsize: Ellipsize.MIDDLE.toString(),
				}),
				this.renderBadge(),
				IconView({
					testId: this.getTestId('right-icon'),
					icon: Icon.CHEVRON_TO_THE_RIGHT,
					color: Color.base4,
					size: 22,
					style: {
						marginLeft: Indent.XL.toNumber(),
					},
				}),
			);
		}

		#handleOnClick = () => {
			const { onClick, itemData } = this.props;

			onClick(itemData);
		};

		renderLeftContent()
		{
			const { icon } = this.props;

			const iconValue = typeof icon === 'string'
				? Icon[icon.toUpperCase()]
				: icon;

			if (iconValue)
			{
				return IconView({
					testId: this.getTestId('icon-left'),
					icon: iconValue,
					color: Color.accentMainPrimary,
					size: 26,
					style: {
						marginRight: Indent.XL.toNumber(),
					},
				});
			}

			return null;
		}

		renderBadge()
		{
			const { badge } = this.props;

			if (!badge)
			{
				return null;
			}

			return BadgeCounter({
				testId: this.getTestId('badge'),
				value: badge,
				size: BadgeCounterSize.M,
				showRawValue: true,
				design: BadgeCounterDesign.ALERT,
				style: {
					marginLeft: Indent.XL.toNumber(),
				},
			});
		}
	}

	ListItem.propTypes = {
		testId: PropTypes.string,
		itemData: PropTypes.object.isRequired,
		icon: PropTypes.oneOfType([PropTypes.string, PropTypes.object]),
		title: PropTypes.string.isRequired,
		badge: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
		onClick: PropTypes.func.isRequired,
		divider: PropTypes.bool,
		style: PropTypes.object,
	};

	module.exports = {
		ListItem,
	};
});
