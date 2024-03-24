/**
 * @module layout/ui/checkbox
 */
jn.define('layout/ui/checkbox', (require, exports, module) => {
	const AppTheme = require('apptheme');

	class CheckBox extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				checked: BX.prop.getBoolean(props, 'checked', false),
				isDisabled: BX.prop.getBoolean(props, 'isDisabled', false),
			};
		}

		componentWillReceiveProps(props)
		{
			this.setState({
				checked: BX.prop.getBoolean(props, 'checked', false),
				isDisabled: BX.prop.getBoolean(props, 'isDisabled', false),
			});
		}

		render()
		{
			const { checked, isDisabled } = this.state;
			const { onClick } = this.props;

			return View(
				{
					clickable: !isDisabled,
					style: this.getStyle(),
					onClick: () => {
						if (isDisabled)
						{
							return;
						}

						this.setState(
							{ checked: !checked },
							() => {
								if (onClick)
								{
									onClick();
								}
							},
						);
					},
				},
				checked && Image({
					style: {
						width: 12,
						height: 9,
						opacity: 1,
					},
					tintColor: this.getMergeStyle().checkColor || AppTheme.colors.bgContentPrimary,
					svg: {
						content: '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="9" viewBox="0 0 12 9" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.95219 8.3198C4.56385 8.69826 3.94464 8.69826 3.5563 8.31979L0.763658 5.59814C0.345119 5.19024 0.345119 4.51755 0.763658 4.10965C1.16723 3.71633 1.81074 3.71633 2.21431 4.10965L4.25425 6.09772L9.78568 0.706892C10.1893 0.313577 10.8328 0.313576 11.2363 0.706891C11.6549 1.11479 11.6549 1.78748 11.2363 2.19538L4.95219 8.3198Z" fill="white"/></svg>',
					},
				}),
			);
		}

		getStyle()
		{
			const { isDisable, checked } = this.state;
			const style = {
				alignItems: 'center',
				justifyContent: 'center',
				width: 24,
				height: 24,
				borderRadius: 12,
			};
			const mergeStyle = this.getMergeStyle();

			if (checked)
			{
				style.backgroundColor = mergeStyle.backgroundColor || AppTheme.colors.accentExtraDarkblue;
			}
			else
			{
				style.borderWidth = 1.6;
				style.borderColor = mergeStyle.borderColor || AppTheme.colors.base5;
			}

			if (isDisable)
			{
				style.opacity = (mergeStyle.opacity || 0.5);
			}

			return style;
		}

		getMergeStyle()
		{
			return BX.prop.getObject(this.props, 'style', {});
		}
	}

	module.exports = { CheckBox };
});
