/**
 * @module ui-system/blocks/chips/chip-filter
 */
jn.define('ui-system/blocks/chips/chip-filter', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { IconView, Icon, iconTypes } = require('ui-system/blocks/icon');
	const { Feature } = require('feature');
	const { ChipInnerTabClass, BadgeCounterDesign } = require('ui-system/blocks/chips/chip-inner-tab');

	/**
	 * @function ChipFilter
	 * @params {object} props
	 * @params {string} [props.text]
	 * @params {value} [props.counterValue]
	 * @params {BadgeCounterDesign} [props.counterDesign]
	 * @params {boolean} [props.selected=false]
	 * @params {boolean} [props.modeMore=false]
	 * @params {function} [props.forwardRef]
	 * @return ChipFilter
	 */
	class ChipFilter extends ChipInnerTabClass
	{
		renderContent()
		{
			const { modeMore } = this.props;

			if (!modeMore)
			{
				return super.renderContent();
			}

			return this.renderMoreButton();
		}

		renderMoreButton()
		{
			return this.renderContentWrapper(
				IconView({
					icon: Feature.isAirStyleSupported() ? Icon.MORE : iconTypes.outline.more,
					size: 22,
					color: this.getIconColor(),
				}),
			);
		}

		renderAdditionalContent()
		{
			return this.renderIconCross();
		}

		renderIconCross()
		{
			if (!this.shouldRenderCross())
			{
				return null;
			}

			return IconView({
				icon: Feature.isAirStyleSupported() ? Icon.CROSS : iconTypes.outline.cross,
				size: 22,
				color: this.getIconColor(),
			});
		}

		shouldRenderCross()
		{
			const { selected, modeMore } = this.props;

			return selected && !modeMore;
		}

		getBadgeCounterDesign()
		{
			const { counterDesign } = this.props;

			return counterDesign;
		}

		getContentStyle()
		{
			const backgroundColor = this.selected ? Color.accentSoftBlue2 : null;

			return {
				...super.getContentStyle(),
				backgroundColor: backgroundColor?.withPressed(),
			};
		}

		getBorderColor()
		{
			return this.selected ? Color.accentSoftBlue1 : Color.bgSeparatorPrimary;
		}

		getContentBaseStyle()
		{
			if (!this.shouldRenderCross())
			{
				return super.getContentBaseStyle();
			}

			return {
				...super.getContentBaseStyle(),
				paddingRight: Indent.XS.toNumber(),
			};
		}

		getIconColor()
		{
			return Color.base4;
		}
	}

	ChipFilter.defaultProps = {
		selected: false,
		modeMore: false,
	};

	ChipFilter.propTypes = {
		testId: PropTypes.string.isRequired,
		text: PropTypes.string,
		modeMore: PropTypes.bool,
		selected: PropTypes.bool,
		counterValue: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
		counterDesign: PropTypes.object,
		forwardRef: PropTypes.func,
	};

	module.exports = {
		ChipFilter: (props) => new ChipFilter(props),
		BadgeCounterDesign,
	};
});
