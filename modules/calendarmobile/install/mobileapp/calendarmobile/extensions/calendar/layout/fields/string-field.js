/**
 * @module calendar/layout/fields/string-field
 */
jn.define('calendar/layout/fields/string-field', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const isAndroid = Application.getPlatform() === 'android';

	/**
	 * @class StringField
	 */
	class StringField extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				errorMessage: props.errorMessage,
				validationError: false,
				rawValue: null,
			};

			this.textFieldRef = null;
			this.errorRef = null;

			this.onBlur = this.onBlur.bind(this);
			this.onChange = this.onChange.bind(this);
			this.onFocus = this.onFocus.bind(this);
		}

		get value()
		{
			return this.state.rawValue;
		}

		render()
		{
			return View(
				{
					style: {
						flexGrow: 1,
					},
					testId: this.props.testId,
				},
				this.renderField(),
			);
		}

		renderField()
		{
			return View(
				{
					style: {
						marginTop: 11,
						paddingTop: 11,
					},
				},
				View(
					{
						style: {
							borderColor: this.state.validationError
								? AppTheme.colors.accentMainAlert
								: AppTheme.colors.accentBrandBlue,
							borderWidth: 2,
							borderRadius: 3,
							paddingHorizontal: 10,
							paddingBottom: 6,
							paddingTop: isAndroid ? 13 : 6,
							height: 52,
							flexGrow: 1,
							justifyContent: 'flex-start',
							backgroundColor: 'transparent',
						},
						testId: 'string_field_container',
					},
					this.renderTextField(),
				),
				this.renderLabel(),
				this.renderError(),
			);
		}

		renderTextField()
		{
			return TextField(
				{
					value: '',
					placeholder: this.props.placeholder || '',
					keyboardType: this.props.keyboardType || 'default',
					style: {
						fontSize: 16,
						flexGrow: 1,
						fontWeight: '400',
						textAlign: 'left',
						color: AppTheme.colors.base1,
					},
					maxLength: this.props.maxLength || null,
					isPassword: this.props.isPassword || false,
					onChangeText: this.onChange,
					onBlur: this.onBlur,
					onFocus: this.onFocus,
					onSubmitEditing: () => this.textFieldRef.blur(),
					ref: (ref) => {
						this.textFieldRef = ref;
					},
					testId: 'string_field_text_field',
				},
			);
		}

		renderLabel()
		{
			return View(
				{
					style: {
						position: 'absolute',
						top: 1,
						left: 0,
						justifyContent: 'flex-start',
					},
					testId: 'string_field_label',
				},
				View(
					{
						style: {
							backgroundColor: AppTheme.colors.bgContentPrimary,
							borderRadius: 4,
							paddingHorizontal: 4,
							marginHorizontal: 8,
							paddingVertical: 3,
							flexShrink: 1,
						},
					},
					Text(
						{
							style: {
								fontSize: 14,
								fontWeight: '400',
								color: AppTheme.colors.base4,
							},
							text: this.props.label,
						},
					),
				),
			);
		}

		renderError()
		{
			const { errorMessage } = this.state;

			return View(
				{
					style: {
						marginLeft: 1,
						marginTop: -2,
						marginBottom: 2,
						opacity: 0,
					},
					ref: (ref) => {
						this.errorRef = ref;
					},
					testId: 'string_field_error',
				},
				Image(
					{
						svg: {
							content: tooltipTriangle(AppTheme.colors.accentMainAlert),
						},
						style: {
							width: 5,
							height: 5,
						},
					},
				),
				View(
					{
						style: {
							marginTop: -1,
							paddingLeft: 6,
							paddingRight: 9,
							backgroundColor: AppTheme.colors.accentMainAlert,
							borderTopRightRadius: 8,
							borderBottomLeftRadius: 8,
							alignSelf: 'flex-start',
						},
					},
					Text(
						{
							style: {
								color: AppTheme.colors.baseWhiteFixed,
								fontSize: 13,
							},
							text: errorMessage,
							numberOfLines: 1,
							ellipsize: 'end',
						},
					),
				),
			);
		}

		onBlur()
		{
			if (this.props.onBlur)
			{
				this.props.onBlur();
			}
		}

		onChange(value)
		{
			this.state.rawValue = value;

			if (this.props.onChange)
			{
				this.props.onChange();
			}
		}

		onFocus()
		{
			if (this.props.onFocus)
			{
				this.props.onFocus();
			}
		}

		isFocused()
		{
			return this.textFieldRef.isFocused();
		}

		blur()
		{
			return this.textFieldRef.blur();
		}

		setErrorState()
		{
			if (this.state.validationError)
			{
				return;
			}

			this.setState({ validationError: true }, () => {
				if (this.errorRef)
				{
					this.errorRef.animate({
						duration: 200,
						opacity: 1,
					});
				}
			});
		}

		clearErrorState()
		{
			if (!this.state.validationError)
			{
				return;
			}

			this.setState({ validationError: false }, () => {
				if (this.errorRef)
				{
					this.errorRef.animate({
						duration: 200,
						opacity: 0,
					});
				}
			});
		}
	}

	const tooltipTriangle = (color) => `
		<svg width="5" height="4" viewBox="0 0 5 4" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M2.60352 2.97461L0 0V4H4.86133C3.99634 4 3.17334 3.62695 2.60352 2.97461Z" fill="${color}"/>
			</svg>
	`;

	module.exports = { StringField };
});
