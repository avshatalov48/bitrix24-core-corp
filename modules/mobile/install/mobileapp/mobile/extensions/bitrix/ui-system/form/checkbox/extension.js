/**
 * @module ui-system/form/checkbox
 */
jn.define('ui-system/form/checkbox', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Corner } = require('tokens');
	const { PropTypes } = require('utils/validation');
	const { IconView } = require('ui-system/blocks/icon');
	const { OutlineIconTypes } = require('assets/icons/types');

	const DEFAULT_COLOR = AppTheme.colors.accentMainPrimary;

	class Checkbox extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.initialState(props);
			this.handleOnClick = this.handleOnClick.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.initialState(props);
		}

		initialState(props)
		{
			const { checked } = props;

			this.state = { checked };
		}

		render()
		{
			const { disabled, useState } = this.props;

			return View(
				{
					clickable: !disabled && useState,
					style: this.getStyle(),
					onClick: this.handleOnClick,
				},
				this.renderIcon(),
			);
		}

		renderIcon()
		{
			const { checked } = this.state;
			const { indeterminate, size } = this.props;

			const iconProps = {
				iconSize: size - 2,
				icon: indeterminate ? OutlineIconTypes.minus : OutlineIconTypes.check,
			};

			if (indeterminate)
			{
				iconProps.iconColor = indeterminate ? this.getBaseColor() : AppTheme.colors.baseWhiteFixed;
			}

			return checked && IconView(iconProps);
		}

		async handleOnClick()
		{
			const { onClick, useState } = this.props;

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
				return AppTheme.colors.base5;
			}

			return background || DEFAULT_COLOR;
		}

		getStyle()
		{
			const { indeterminate, size } = this.props;
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
				style.borderColor = AppTheme.colors.base6;
			}

			return style;
		}
	}

	Checkbox.propTypes = {
		size: PropTypes.number,
		checked: PropTypes.bool,
		disabled: PropTypes.bool,
		background: PropTypes.string,
		useState: PropTypes.bool,
		indeterminate: PropTypes.bool,
	};

	module.exports = { Checkbox };
});
