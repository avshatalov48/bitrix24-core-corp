/**
 * @module ui-system/blocks/entity-cell
 */
jn.define('ui-system/blocks/entity-cell', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Color, Corner } = require('tokens');
	const { Indent } = require('tokens');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Text2, Text3, Text5 } = require('ui-system/typography/text');
	const { PropTypes } = require('utils/validation');
	const { EntityCellMode } = require('ui/system/blocks/entity-cell/src/entity-enum');
	const { Checkbox } = require('ui-system/form/checkbox');
	const { Ellipsize } = require('utils/enums/style');
	const { BadgeCounter, BadgeCounterDesign, BadgeCounterSize } = require('ui-system/blocks/badges/counter');
	const { ChipStatus, ChipStatusDesign, ChipStatusMode } = require('ui-system/blocks/chips/chip-status');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { createTestIdGenerator } = require('utils/test');

	/**
	 * @class EntityCell
	 * @property {string} [testId]
	 * @property {EntityCellMode} [mode='single']
	 * @property {boolean} [divider=true]
	 *
	 * @property {object} [icon]
	 * @property {object} [avatar]
	 *
	 * @property {string} title
	 * @property {Ellipsize} [titleEllipsize=Ellipsize.MIDDLE]
	 *
	 * @property {string} [extraName]
	 * @property {Color} [extraNameColor]
	 *
	 * @property {string} [badgeHeader]
	 *
	 * @property {string} [subtitle]
	 * @property {Ellipsize} [subtitleEllipsize]
	 *
	 * @property {boolean} [checked=false]
	 * @property {boolean} [indeterminate]
	 * @property {boolean} [disabled]
	 *
	 * @property {number} [badge]
	 *
	 * @property {boolean} [nextLevel=false]
	 *
	 * @property {Function} [onClick]
	 * @property {number} [level=0]
	 * @property {object} [entityData]
	 *
	 */
	class EntityCell extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.testIdGenerator = createTestIdGenerator({
				testId: props.testId,
			});
		}

		/**
		 * @param {string} suffix
		 * @returns {string}
		 */
		getTestId(suffix = '')
		{
			return this.testIdGenerator(suffix);
		}

		render()
		{
			const { divider, style = {} } = this.props;

			return View(
				{
					testId: this.getTestId(),
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						paddingHorizontal: Indent.XL.toNumber(),
						borderRadius: Corner.M.toNumber(),
						...style,
					},
					onClick: this.#handleOnClick,
				},
				this.renderLevel(),
				this.renderLeftContent(),
				View(
					{
						style: {
							borderBottomWidth: 1,
							borderBottomColor: divider && Color.bgSeparatorSecondary.toHex(),
							flexDirection: 'row',
							paddingRight: Indent.XL3.toNumber(),
							paddingVertical: Indent.XL2.toNumber(),
							flex: 2,
						},
					},
					this.renderTextContent(),
					this.renderBadge(),
					this.renderRightContent(),
				),
			);
		}

		#handleOnClick = () => {
			const { onClick, entityData } = this.props;

			onClick(entityData);
		};

		renderLevel()
		{
			const { level } = this.props;

			if (!level || level <= 0)
			{
				return null;
			}

			const actualLevel = Math.min(level, 4);

			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				...Array.from({ length: actualLevel })
					.fill()
					.map(() => View(
						{
							style: {
								width: 2,
								height: 2,
								borderRadius: 1,
								backgroundColor: Color.base4.toHex(),
								marginRight: Indent.XL3.toNumber(),
							},
						},
					)),
			);
		}

		renderLeftContent()
		{
			const { avatar, icon } = this.props;

			if (avatar)
			{
				return Avatar({
					testId: this.getTestId('avatar'),
					size: 40,
					uri: avatar,
					onClick: this.#handleOnClick,
					style: {
						marginRight: Indent.XL.toNumber(),
					},
				});
			}

			const iconValue = typeof icon === 'string'
				? Icon[icon.toUpperCase()]
				: icon;

			if (iconValue)
			{
				return IconView({
					testId: this.getTestId('icon-left'),
					icon: iconValue,
					color: Color.accentMainPrimary,
					size: 28,
					style: {
						marginRight: Indent.XL.toNumber(),
					},
				});
			}

			return null;
		}

		renderIcon(icon, color, size)
		{
			return IconView({
				testId: this.getTestId('icon'),
				icon,
				color,
				size,
			});
		}

		renderTextContent()
		{
			const {
				title,
				titleEllipsize = Ellipsize.MIDDLE,
				subtitle,
				subtitleEllipsize = Ellipsize.MIDDLE,
				extraName,
				badgeHeader,
			} = this.props;

			return View(
				{
					style: {
						flexDirection: 'column',
						marginRight: Indent.XL.toNumber(),
						flex: 2,
					},
				},
				View(
					{
						style: {
							flexShrink: 2,
							flexDirection: 'row',
							marginBottom: subtitle ? Indent.XS2.toNumber() : 0,
						},
					},
					Text2({
						text: title,
						testId: this.getTestId('title'),
						color: Color.base1,
						style: {
							flexShrink: 2,
							marginRight: (extraName || badgeHeader) && Indent.XS.toNumber(),
						},
						numberOfLines: 1,
						ellipsize: titleEllipsize.toString(),
					}),
					this.renderExtraName(),
					this.renderBadgeHeader(),
				),
				subtitle && Text5({
					text: subtitle,
					testId: this.getTestId('subtitle'),
					color: Color.base3,
					numberOfLines: 1,
					style: {
						flexShrink: 2,
					},
					ellipsize: subtitleEllipsize.toString(),
				}),
			);
		}

		renderExtraName()
		{
			const { extraName, extraNameColor } = this.props;

			if (!extraName)
			{
				return null;
			}

			return Text3({
				text: extraName,
				testId: this.getTestId('extra-name'),
				color: extraNameColor || Color.base2,
				numberOfLines: 1,
				ellipsize: Ellipsize.MIDDLE.toString(),
				style: {
					flexShrink: 2,
					marginRight: Indent.XS.toNumber(),
				},
			});
		}

		renderBadgeHeader()
		{
			const { badgeHeader } = this.props;

			if (!badgeHeader)
			{
				return null;
			}

			return ChipStatus({
				testId: this.getTestId('badge-header'),
				text: badgeHeader,
				design: ChipStatusDesign.SUCCESS,
				mode: ChipStatusMode.SOLID,
			});
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
					marginRight: Indent.XL.toNumber(),
				},
			});
		}

		renderRightContent()
		{
			const { mode, checked } = this.props;
			switch (mode)
			{
				case EntityCellMode.SINGLE:
					return checked ? this.renderCheckBox() : null;
				case EntityCellMode.MULTIPLE:
					return this.renderCheckBox();
				case EntityCellMode.GROUP:
					return this.renderGroupIndicator();
				case EntityCellMode.ENTITY_MENU:
					return this.renderIcon(Icon.MORE, Color.base4, 28);
				default:
					return null;
			}
		}

		renderCheckBox()
		{
			const { indeterminate, checked, disabled } = this.props;

			return new Checkbox({
				testId: this.getTestId('checkbox'),
				checked,
				size: 24,
				indeterminate,
				disabled,
			});
		}

		renderGroupIndicator()
		{
			const { nextLevel } = this.props;

			return nextLevel
				? this.renderIcon(Icon.CHEVRON_TO_THE_RIGHT, Color.base4, 20)
				: View({ style: { width: 20, height: 20 } });
		}
	}

	EntityCell.defaultProps = {
		titleEllipsize: Ellipsize.MIDDLE,
		subtitleEllipsize: Ellipsize.MIDDLE,
	};

	EntityCell.propTypes = {
		testId: PropTypes.string.isRequired,
		mode: PropTypes.instanceOf(EntityCellMode),
		divider: PropTypes.bool,

		icon: PropTypes.oneOfType([
			PropTypes.instanceOf(Icon),
			PropTypes.string,
		]),
		avatar: PropTypes.string,

		// header properties
		title: PropTypes.string.isRequired,
		titleEllipsize: PropTypes.instanceOf(Ellipsize),

		extraName: PropTypes.string,
		extraNameColor: PropTypes.instanceOf(Color),

		badgeHeader: PropTypes.string,

		subtitle: PropTypes.string,
		subtitleEllipsize: PropTypes.instanceOf(Ellipsize),

		// checkbox properties
		checked: PropTypes.bool,
		indeterminate: PropTypes.bool,
		disabled: PropTypes.bool,

		badge: PropTypes.number,

		nextLevel: PropTypes.bool,

		onClick: PropTypes.func.isRequired,

		level: PropTypes.number,
		entityData: PropTypes.object,
		style: PropTypes.object,
	};

	module.exports = {
		EntityCell: (props) => new EntityCell(props),
		EntityCellMode,
	};
});
