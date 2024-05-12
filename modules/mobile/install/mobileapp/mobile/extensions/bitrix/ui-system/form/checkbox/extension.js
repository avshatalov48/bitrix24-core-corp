/**
 * @module ui-system/form/checkbox
 */
jn.define('ui-system/form/checkbox', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Corner, Color } = require('tokens');
	const { PropTypes } = require('utils/validation');
	const { IconView } = require('ui-system/blocks/icon');
	const { OutlineIconTypes } = require('assets/icons/types');

	const DEFAULT_COLOR = Color.accentMainPrimary;
	const DISABLED_COLOR = Color.base7;
	const DEFAULT_SIZE = 20;

	class Checkbox extends LayoutComponent
	{
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
			const { testId, disabled, useState = true } = this.props;

			return View(
				{
					testId,
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
				iconSize: size - 2,
				icon: OutlineIconTypes.check,
				iconColor: Color.baseWhiteFixed,
			};

			if (indeterminate)
			{
				iconProps.icon = OutlineIconTypes.minus;
				iconProps.iconColor = this.getBaseColor();
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

		getBaseColor()
		{
			const { background = null, disabled } = this.props;

			if (disabled)
			{
				return DISABLED_COLOR;
			}

			return background || DEFAULT_COLOR;
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
				borderRadius: Corner.XS,
			};

			if (indeterminate)
			{
				style.borderWidth = 1;
				style.borderColor = this.getBaseColor();

				return style;
			}

			if (checked)
			{
				style.backgroundColor = this.getBaseColor();
			}
			else
			{
				style.borderWidth = 1;
				style.borderColor = Color.base6;
			}

			return style;
		}
	}

	Checkbox.defaultProps = {
		size: DEFAULT_SIZE,
		useState: true,
	};

	Checkbox.propTypes = {
		size: PropTypes.number,
		checked: PropTypes.bool,
		disabled: PropTypes.bool,
		background: PropTypes.string,
		useState: PropTypes.bool,
		indeterminate: PropTypes.bool,
	};

	Checkbox.defaultProps = {
		size: 24,
		checked: true,
		disabled: false,
		background: AppTheme.colors.accentMainPrimary,
		useState: true,
		indeterminate: false,
	};

	module.exports = { Checkbox };
});
