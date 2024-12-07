/**
 * @module ui-system/blocks/setting-selector
 */
jn.define('ui-system/blocks/setting-selector', (require, exports, module) => {
	const { Indent, Color } = require('tokens');
	const { Ellipsize } = require('utils/enums/style');
	const { PureComponent } = require('layout/pure-component');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Text3, Text5 } = require('ui-system/typography/text');
	const { Switcher, SwitcherSize } = require('ui-system/blocks/switcher');

	/**
	 * @typedef {Object} SettingSelectorProps
	 * @property {string} testId
	 * @property {boolean} [checked]
	 * @property {boolean} [locked]
	 * @property {Icon} [icon]
	 * @property {Color} [iconColor]
	 * @property {string} [title]
	 * @property {Ellipsize} [titleEllipsize]
	 * @property {number} [numberOfLinesTitle]
	 * @property {string} [subtitle]
	 * @property {Ellipsize} [subtitleEllipsize]
	 * @property {number} [numberOfLinesSubtitle]
	 * @property {Function} [onClick]
	 *
	 * @function SettingSelector
	 * @param {SettingSelectorProps} props
	 */

	class SettingSelector extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.#initializeState(props);
		}

		componentWillReceiveProps(props)
		{
			this.#initializeState(props);
		}

		#initializeState(props = {})
		{
			const { checked } = props;

			this.state = {
				checked: Boolean(checked),
			};
		}

		render()
		{
			const { testId, style = {} } = this.props;

			return View(
				{
					testId,
					style,
					onClick: this.#handleOnClick,
				},
				View(
					{
						style: {
							flexDirection: 'column',
						},
					},
					View(
						{
							style: {
								alignItems: 'center',
								flexDirection: 'row',
							},
						},
						View(
							{
								style: {
									flex: 1,
									flexDirection: 'row',
									paddingVertical: Indent.XS2.toNumber(),
								},
							},
							this.renderIcon(),
							this.renderTitle(),
						),
						this.renderSwitch(),
					),
					this.renderSubtitle(),
				),
			);
		}

		renderTitle()
		{
			const { title, titleEllipsize, numberOfLinesTitle = 2 } = this.props;

			if (!title)
			{
				return null;
			}

			return Text3({
				text: title,
				color: Color.base1,
				numberOfLines: numberOfLinesTitle,
				ellipsize: this.#getEllipsize(titleEllipsize),
				style: {
					flex: 1,
					flexShrink: 1,
				},
			});
		}

		renderSubtitle()
		{
			const { subtitle, numberOfLinesSubtitle = 2, subtitleEllipsize } = this.props;

			if (!subtitle)
			{
				return null;
			}

			return Text5({
				text: subtitle,
				color: Color.base3,
				numberOfLines: numberOfLinesSubtitle,
				ellipsize: this.#getEllipsize(subtitleEllipsize),
				style: {
					paddingVertical: Indent.XS2.toNumber(),
					marginRight: this.#getSwitchSize().getWidth(),
				},
			});
		}

		renderIcon()
		{
			const { icon, iconColor, locked } = this.props;
			const iconProps = {
				icon,
				size: 24,
				color: Color.resolve(iconColor, Color.base1),
				style: {
					marginRight: Indent.XS.toNumber(),
				},
			};

			if (locked)
			{
				iconProps.icon = Icon.LOCK;
				iconProps.color = Color.base1;
			}

			if (!iconProps.icon)
			{
				return null;
			}

			return IconView(iconProps);
		}

		renderSwitch()
		{
			const { locked, testId } = this.props;
			const { checked } = this.state;

			return Switcher({
				testId: `${testId}_toggle`,
				checked,
				useState: false,
				disabled: locked,
				size: this.#getSwitchSize(),
				style: {
					paddingVertical: Indent.XS2.toNumber(),
					marginLeft: Indent.XL4.toNumber(),
				},
			});
		}

		#handleOnClick = () => {
			const { locked, onClick } = this.props;
			const { checked: stateChecked } = this.state;
			let checked = !stateChecked;

			if (locked)
			{
				checked = stateChecked;
			}
			else
			{
				this.setState({ checked });
			}

			if (onClick)
			{
				onClick(checked);
			}
		};

		#getSwitchSize()
		{
			return SwitcherSize.XL;
		}

		#getEllipsize(value)
		{
			return Ellipsize.resolve(value, Ellipsize.END).toString();
		}
	}

	SettingSelector.defaultProps = {
		testId: null,
		checked: false,
		locked: false,
	};

	SettingSelector.propTypes = {
		testId: PropTypes.string.isRequired,
		checked: PropTypes.bool,
		locked: PropTypes.bool,
		icon: PropTypes.instanceOf(Icon),
		iconColor: PropTypes.instanceOf(Color),
		title: PropTypes.string,
		titleEllipsize: PropTypes.instanceOf(Ellipsize),
		numberOfLinesTitle: PropTypes.number,
		subtitle: PropTypes.string,
		subtitleEllipsize: PropTypes.instanceOf(Ellipsize),
		numberOfLinesSubtitle: PropTypes.number,
		onClick: PropTypes.func,
	};

	module.exports = {
		/** @param {SettingSelectorProps} props */
		SettingSelector: (props) => new SettingSelector(props),
		Icon,
		Ellipsize,
	};
});
