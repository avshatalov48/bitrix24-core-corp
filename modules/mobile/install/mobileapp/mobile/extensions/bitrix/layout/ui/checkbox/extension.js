/**
 * @module layout/ui/checkbox
 */
jn.define('layout/ui/checkbox', (require, exports, module) => {
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
			return View(
				{
					style: this.getStyle(),
					onClick: () => {
						if (!this.state.isDisabled)
						{
							this.setState(
								{checked: !this.state.checked},
								() => (this.props.onClick && this.props.onClick())
							);
						}
					}
				},
				(this.state.checked && Image({
					style: {
						width: 12,
						height: 9,
						opacity: 1,
					},
					svg: {
						content: `<svg xmlns="http://www.w3.org/2000/svg" width="12" height="9" viewBox="0 0 12 9" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.95219 8.3198C4.56385 8.69826 3.94464 8.69826 3.5563 8.31979L0.763658 5.59814C0.345119 5.19024 0.345119 4.51755 0.763658 4.10965C1.16723 3.71633 1.81074 3.71633 2.21431 4.10965L4.25425 6.09772L9.78568 0.706892C10.1893 0.313577 10.8328 0.313576 11.2363 0.706891C11.6549 1.11479 11.6549 1.78748 11.2363 2.19538L4.95219 8.3198Z" fill="${(this.getMergeStyle().checkColor || '#ffffff')}"/></svg>`,
					},
				})),
			);
		}

		getStyle()
		{
			const style = {
				alignItems: 'center',
				justifyContent: 'center',
				width: 24,
				height: 24,
				borderRadius: 12,
			};
			const mergeStyle = this.getMergeStyle();

			if (this.state.checked)
			{
				style.backgroundColor = (mergeStyle.backgroundColor ? mergeStyle.backgroundColor : '#0091e3');
			}
			else
			{
				style.backgroundColor = '#ffffff';
				style.borderWidth = 1.6;
				style.borderColor = (mergeStyle.borderColor ? mergeStyle.borderColor : '#bdc1c6');
			}

			if (this.state.isDisabled)
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

	module.exports = {CheckBox};
});