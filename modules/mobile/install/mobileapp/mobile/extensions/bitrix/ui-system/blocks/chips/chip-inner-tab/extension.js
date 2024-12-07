/**
 * @module ui-system/blocks/chips/chip-inner-tab
 */
jn.define('ui-system/blocks/chips/chip-inner-tab', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Color, Component, Indent } = require('tokens');
	const { mergeImmutable } = require('utils/object');
	const { Text4, Text6 } = require('ui-system/typography/text');
	const { BadgeCounter, BadgeCounterDesign } = require('ui-system/blocks/badges/counter');

	/**
	 * @function ChipInnerTab
	 * @params {object} props
	 * @params {string} [props.text]
	 * @params {number | string} [props.counterValue]
	 * @params {BadgeCounterDesign} [props.counterDesign]
	 * @params {boolean} [props.selected=false]
	 * @params {boolean} [props.badgeNew=false]
	 * @params {function} [props.forwardRef]
	 * @return ChipInnerTab
	 */
	class ChipInnerTab extends LayoutComponent
	{
		get selected()
		{
			const { selected } = this.props;

			return selected;
		}

		render()
		{
			const {
				forwardRef,
				testId,
				onClick,
				style = {},
			} = this.props;

			const viewProps = mergeImmutable({
				testId,
				onClick,
				ref: forwardRef,
				style: {
					flexShrink: 1,
					alignItems: 'flex-start',
				},
			}, { style });

			return View(
				viewProps,
				this.renderContent(),
			);
		}

		renderContent()
		{
			return this.renderContentWrapper(
				[
					this.renderText(),
					...this.renderBadge(),
					this.renderAdditionalContent(),
				],
			);
		}

		renderContentWrapper(children)
		{
			const childrenView = Array.isArray(children) ? children : [children];

			return View(
				{
					style: {
						...this.getContentStyle(),
						...this.getContentBaseStyle(),
					},
				},
				...childrenView,
			);
		}

		/**
		 * @return {View[]}
		 */
		renderBadge()
		{
			const badges = [];

			if (!this.shouldRenderBadge())
			{
				return badges;
			}

			const { testId, counterValue, badgeNew } = this.props;

			if (this.shouldRenderBadgeCounter())
			{
				badges.push(BadgeCounter({
					testId,
					value: counterValue,
					design: this.getBadgeCounterDesign(),
					style: {
						marginLeft: Indent.XS.toNumber(),
					},
				}));
			}

			if (badgeNew)
			{
				badges.push(this.renderBadgeNew());
			}

			return badges;
		}

		renderBadgeNew()
		{
			return Text6({
				color: Color.accentMainSuccess,
				text: Loc.getMessage('MOBILE_UI_SYSTEM_BLOCKS_CHIPS_CHIP_INNER_TAB_BADGE_NEW'),
				style: {
					marginLeft: Indent.XS.toNumber(),
				},
			});
		}

		renderText()
		{
			const { text, textStyles } = this.props;

			return Text4({
				text,
				color: this.getTextColor(),
				ellipsize: 'end',
				numberOfLines: 1,
				style: textStyles,
			});
		}

		renderAdditionalContent()
		{
			return null;
		}

		shouldRenderBadge()
		{
			const { badgeNew } = this.props;

			return this.shouldRenderBadgeCounter() || badgeNew;
		}

		shouldRenderBadgeCounter()
		{
			const { counterValue } = this.props;

			return Type.isNumber(counterValue);
		}

		getContentStyle()
		{
			return {
				borderColor: this.getBorderColor().toHex(),
			};
		}

		getContentBaseStyle()
		{
			return {
				flexDirection: 'row',
				alignItems: 'center',
				justifyContent: 'center',
				height: Component.itbChipHeight.toNumber(),
				borderRadius: Component.itbChipCorner.toNumber(),
				borderWidth: Component.itbChipStroke.toNumber(),
				paddingLeft: Component.itbChipPaddingLr.toNumber(),
				paddingRight: Component.itbChipPaddingLr.toNumber(),
			};
		}

		getBadgeCounterDesign()
		{
			const { counterDesign } = this.props;

			return this.selected ? counterDesign : BadgeCounterDesign.GREY;
		}

		getBorderColor()
		{
			return this.selected ? Color.base4 : Color.bgSeparatorPrimary;
		}

		getTextColor()
		{
			return this.selected ? Color.base1 : Color.base3;
		}
	}

	ChipInnerTab.defaultProps = {
		selected: false,
		badgeNew: false,
	};

	ChipInnerTab.propTypes = {
		testId: PropTypes.string.isRequired,
		text: PropTypes.string.isRequired,
		selected: PropTypes.bool,
		badgeNew: PropTypes.bool,
		counterValue: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
		counterDesign: PropTypes.object,
		forwardRef: PropTypes.func,
	};

	module.exports = {
		ChipInnerTab: (props) => new ChipInnerTab(props),
		ChipInnerTabClass: ChipInnerTab,
		BadgeCounterDesign,
	};
});
