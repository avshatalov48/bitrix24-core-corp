(() => {
	const ERROR_TEXT_COLOR = '#F0371B';

	/**
	 * @class Fields.BaseField
	 */
	class BaseField extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				focus: (props.focus || false),
				additionalStyles: {},
				errorMessage: null
			};
			if (this.state.focus)
			{
				Fields.FocusManager.setFocusedField(this);
				props.onFocusIn && props.onFocusIn();
			}

			this.currentTickValidation = false;
			this.debouncedValidation = Utils.debounce(this.validate, 500, this, true);
		}

		componentWillReceiveProps(newProps)
		{
			if (this.needToValidateCurrentTick(newProps))
			{
				this.currentTickValidation = true;
			}
		}

		hasKeyboard()
		{
			return false;
		}

		needToValidateCurrentTick(newProps)
		{
			if (this.currentTickValidation)
			{
				return false;
			}

			if (this.props.value === newProps.value)
			{
				return false;
			}

			if (
				BX.type.isPlainObject(this.props.value)
				&& BX.type.isPlainObject(newProps.value)
				&& JSON.stringify(this.props.value) === JSON.stringify(newProps.value)
			)
			{
				return false;
			}

			return true;
		}

		isMultiple()
		{
			return BX.prop.getBoolean(this.props, 'multiple', false);
		}

		isReadOnly()
		{
			return BX.prop.getBoolean(this.props, 'readOnly', false);
		}

		isHidden()
		{
			return BX.prop.getBoolean(this.props, 'hidden', false);
		}

		isRequired()
		{
			return BX.prop.getBoolean(this.props, 'required', false);
		}

		showRequired()
		{
			return BX.prop.getBoolean(this.props, 'showRequired', true);
		}

		showTitle()
		{
			return BX.prop.getBoolean(this.props, 'showTitle', true);
		}

		canFocusTitle()
		{
			return BX.prop.getBoolean(this.props, 'canFocusTitle', true);
		}

		getConfig()
		{
			return BX.prop.getObject(this.props, 'config', {});
		}

		showEditIcon()
		{
			return BX.prop.getBoolean(this.props, 'showEditIcon', false);
		}

		getId()
		{
			return this.props.id;
		}

		handleChange(...values)
		{
			this.props.onChange(...values);
		}

		setAdditionalStyles(additionalStyles)
		{
			this.setState({additionalStyles});
		}

		getStyles()
		{
			const styles = {
				...this.getDefaultStyles(),
				...this.getConfig().styles
			};

			for (const [key, value] of Object.entries(this.state.additionalStyles))
			{
				styles[key] = {...styles[key], ...value};
			}

			return styles;
		}

		getDefaultStyles()
		{
			const base = {
				flex: 1,
				fontSize: 16,
				fontWeight: '400'
			};
			const emptyValue = {
				...base,
				color: '#A8ADB4'
			};
			const value = {
				...base,
				color: '#333333',
			};

			return {
				wrapper: {
					paddingTop: 7,
					paddingBottom: this.hasErrorMessage() ? 5 : 13
				},
				readOnlyWrapper: {
					paddingTop: 7,
					paddingBottom: this.hasErrorMessage() ? 5 : 12
				},
				title: {
					marginBottom: this.isReadOnly() ? 1 : 4,
					color: this.getTitleColor(),
					fontSize: 10,
					fontWeight: '500'
				},
				container: {
					flexDirection: 'row',
					alignItems: 'center'
				},
				errorWrapper: {
					marginLeft: 1,
					marginTop: -2
				},
				errorIcon: {
					width: 5,
					height: 5
				},
				errorContainer: {
					marginTop: -1,
					paddingLeft: 6,
					paddingRight: 9,
					backgroundColor: '#FF5752',
					borderTopRightRadius: 8,
					borderBottomLeftRadius: 8,
					alignSelf: 'flex-start'
				},
				errorText: {
					color: '#ffffff',
					fontSize: 13,
				},
				base,
				emptyValue,
				value
			};
		}

		getTitleColor()
		{
			if (this.hasError())
			{
				return ERROR_TEXT_COLOR;
			}

			if (this.state.focus && this.canFocusTitle())
			{
				return '#0b66c3';
			}

			return '#a8adb4';
		}

		validateCurrentTick()
		{
			this.currentTickValidation = false;
			this.debouncedValidation();
		}

		render()
		{
			if (this.isHidden())
			{
				return null;
			}

			if (this.currentTickValidation)
			{
				this.validateCurrentTick();
			}

			this.styles = this.getStyles();

			return View(
				{
					style: this.isReadOnly() ? this.styles.readOnlyWrapper : this.styles.wrapper,
					onClick: this.getContentClickHandler()
				},
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center'
						}
					},
					View(
						{
							style: {
								flexDirection: 'column',
								flexGrow: 2
							}
						},
						View(
							{
								style: {
									flexDirection: 'row'
								}
							},
							this.showTitle() && this.renderTitle(),
							this.renderRequired()
						),
						View(
							{
								style: this.styles.container
							},
							this.renderContent()
						),
					),
					!this.isReadOnly() && this.showEditIcon() ? this.renderEditIcon() : null
				),
				this.hasErrorMessage() && this.renderError()
			)
		}

		getContentClickHandler()
		{
			if (this.isReadOnly() && !this.props.onContentClick)
			{
				return null;
			}

			return () => {
				if (this.props.onContentClick)
				{
					this.props.onContentClick();
				}

				if (!this.state.focus)
				{
					this.focus();
				}
			}
		}

		focus(callback = null)
		{
			if (!this.isReadOnly())
			{
				this.setFocus(callback);
			}
		}

		setFocus(callback = null)
		{
			if (this.state.focus === true)
			{
				return;
			}

			Fields.FocusManager.blurFocusedFieldIfHas(this, () => {
				this.setState({focus: true}, () => {
					Fields.FocusManager.setFocusedField(this);

					callback && callback();
					this.props.onFocusIn && this.props.onFocusIn();
				});
			});
		}

		removeFocus(callback = null)
		{
			if (this.state.focus === false)
			{
				return;
			}

			this.currentTickValidation = true;

			this.setState({focus: false}, () => {
				callback && callback();
				this.props.onFocusOut && this.props.onFocusOut();
			});
		}

		isEmpty()
		{
			if (this.isMultiple())
			{
				if (!Array.isArray(this.props.value) || this.props.value.length === 0)
				{
					return true;
				}

				return this.props.value.every((value) => this.isEmptyValue(value));
			}

			return this.isEmptyValue(this.props.value);
		}

		isEmptyValue(value)
		{
			return !value;
		}

		checkRequired()
		{
			if (!this.isRequired())
			{
				return true;
			}

			return !this.isEmpty();
		}

		validate()
		{
			if (this.isReadOnly())
			{
				return true;
			}

			if (!this.checkRequired())
			{
				this.showRequiredError();

				return false;
			}

			if (this.hasError())
			{
				this.clearError();
			}

			return true;
		}

		showRequiredError()
		{
			this.setError(BX.message('FIELDS_BASE_REQUIRED_ERROR'));
		}

		setError(errorMessage)
		{
			this.setState({
				errorMessage
			});
		}

		hasError()
		{
			return this.state.errorMessage !== null;
		}

		hasErrorMessage()
		{
			return this.hasError() && this.state.errorMessage.length;
		}

		clearError()
		{
			this.setState({
				errorMessage: null
			});
		}

		renderTitle()
		{
			return Text(
				{
					style: this.styles.title,
					numberOfLines: 1,
					ellipsize: 'end',
					text: (this.props.title || BX.message('FIELDS_BASE_EMPTY_TITLE')).toLocaleUpperCase(Application.getLang())
				}
			)
		}

		renderRequired()
		{
			return (
				this.isRequired() && this.showRequired() && !this.isReadOnly()
					? Text(
						{
							style: {
								...this.styles.title,
								color: ERROR_TEXT_COLOR,
							},
							text: '*'
						}
					)
					: null
			);
		}

		renderContent()
		{
			if (this.isReadOnly())
			{
				return this.renderReadOnlyContent();
			}

			return this.renderEditableContent();
		}

		renderReadOnlyContent()
		{
			throw new Error('Method "renderReadOnlyContent" must be implemented.');
		}

		renderEditableContent()
		{
			throw new Error('Method "renderEditableContent" must be implemented.');
		}

		renderEmptyContent()
		{
			return Text(
				{
					style: this.styles.emptyValue,
					text: BX.message('FIELDS_BASE_EMPTY_VALUE')
				}
			);
		}

		renderError()
		{
			return View(
				{
					style: this.styles.errorWrapper
				},
				Image(
					{
						style: this.styles.errorIcon,
						svg: {
							content: `<svg width="5" height="4" viewBox="0 0 5 4" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M2.60352 2.97461L0 0V4H4.86133C3.99634 4 3.17334 3.62695 2.60352 2.97461Z" fill="#FF5752"/></svg>`
						}
					}
				),
				View(
					{
						style: this.styles.errorContainer
					},
					Text(
						{
							style: this.styles.errorText,
							text: this.state.errorMessage,
							numberOfLines: 1,
							ellipsize: 'end'
						}
					)
				)
			)
		}

		renderEditIcon()
		{
			return View(
				{
					style: {
						paddingLeft: 5
					}
				},
				Image(
					{
						style: {
							height: 15,
							width: 9
						},
						svg: {
							content: `<svg width="10" height="17" viewBox="0 0 10 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0.367432 2.26728L5.48231 7.38216L6.56745 8.42731L5.48231 9.47308L0.367432 14.588L1.84577 16.0663L9.48439 8.42768L1.84577 0.789062L0.367432 2.26728Z" fill="#C2C5CA"/></svg>`
						}
					}
				)
			)
		}
	}

	this.Fields = this.Fields || {};
	this.Fields.BaseField = BaseField;
})();
