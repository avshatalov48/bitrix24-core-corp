/**
 * @module ui-system/form/checkbox
 */
jn.define('ui-system/form/checkbox', (require, exports, module) => {
	const { Corner, Color } = require('tokens');
	const { PropTypes } = require('utils/validation');
	const { IconView, Icon } = require('ui-system/blocks/icon');

	const DEFAULT_COLOR = Color.accentMainPrimary;
	const DISABLED_COLOR = Color.base7;
	const DEFAULT_SIZE = 20;

	/**
	 * @typedef CheckboxProps
	 * @property {string} testId
	 * @property {number} size
	 * @property {boolean} [checked]
	 * @property {boolean} [disabled]
	 * @property {boolean} [useState]
	 * @property {boolean} [indeterminate]
	 * @property {Color} [background]
	 * @property {Function} [onClick]
	 *
	 * @class Checkbox
	 */
	class Checkbox extends LayoutComponent
	{
		/**
		 * @param {CheckboxProps} props
		 */
		constructor(props)
		{
			super(props);

			this.initializeState(props);
			this.handleOnClick = this.handleOnClick.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.initializeState(props);
		}

		initializeState(props = {})
		{
			const { checked } = props;

			this.state = { checked };
		}

		render()
		{
			const { checked } = this.state;
			const { disabled, useState = true } = this.props;

			return View(
				{
					testId: this.#getTestId(),
					clickable: !disabled && useState,
					style: this.getStyle(),
					onClick: this.handleOnClick,
				},
				checked && this.renderIcon(),
			);
		}

		renderIcon()
		{
			const { indeterminate, size = DEFAULT_SIZE } = this.props;

			const iconProps = {
				size: size - 2,
				icon: Icon.CHECK,
				color: Color.baseWhiteFixed,
			};

			if (indeterminate)
			{
				iconProps.icon = Icon.MINUS;
				iconProps.color = this.getBaseColor();
			}

			return IconView(iconProps);
		}

		async handleOnClick()
		{
			const { onClick, useState = true, disabled } = this.props;

			if (disabled)
			{
				return;
			}

			if (useState)
			{
				await this.checked();
			}

			if (onClick)
			{
				onClick();
			}
		}

		checked()
		{
			const { checked } = this.state;

			return new Promise((resolve) => {
				this.setState({ checked: !checked }, resolve);
			});
		}

		/**
		 * @private
		 * @param {boolean} [hex]
		 * @return {Color}
		 */
		getBaseColor(hex)
		{
			const { background = null, disabled } = this.props;
			let color = background || DEFAULT_COLOR;

			if (disabled)
			{
				color = DISABLED_COLOR;
			}

			if (hex)
			{
				return color.toHex();
			}

			return color;
		}

		getStyle()
		{
			const { indeterminate, size = DEFAULT_SIZE } = this.props;
			const { checked } = this.state;

			const style = {
				alignItems: 'center',
				justifyContent: 'center',
				width: size,
				height: size,
				borderRadius: Corner.XS.toNumber(),
			};

			if (indeterminate)
			{
				style.borderWidth = 1;
				style.borderColor = this.getBaseColor(true);

				return style;
			}

			if (checked)
			{
				style.backgroundColor = this.getBaseColor(true);
			}
			else
			{
				style.borderWidth = 1;
				style.borderColor = Color.base6.toHex();
			}

			return style;
		}

		#getTestId()
		{
			const { testId } = this.props;
			const { checked } = this.state;
			const prefix = checked ? '' : 'un';

			return `${testId}_${prefix}selected`;
		}
	}

	Checkbox.defaultProps = {
		size: DEFAULT_SIZE,
		checked: true,
		disabled: false,
		background: Color.accentMainPrimary,
		useState: true,
		indeterminate: false,
	};

	Checkbox.propTypes = {
		testId: PropTypes.string.isRequired,
		size: PropTypes.number,
		checked: PropTypes.bool,
		disabled: PropTypes.bool,
		background: PropTypes.object,
		useState: PropTypes.bool,
		indeterminate: PropTypes.bool,
		onClick: PropTypes.func,
	};

	module.exports = { Checkbox };
});
