/**
 * @module layout/ui/product-grid/components/string-field/string
 */
jn.define('layout/ui/product-grid/components/string-field/string', (require, exports, module) => {

	const { isEqual } = require('utils/object');
	const { stringify } = require('utils/string');
	const { FocusContext } = require('layout/ui/product-grid/services/focus-context');
	const { Haptics } = require('haptics');

	/**
	 * @class ProductGridStringField
	 */
	class ProductGridStringField extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = this.buildState(props);
			this.textFieldRef = null;
			this.testId = props.testId ? props.testId : '';
		}

		buildState(props)
		{
			return {
				rawValue: props.value,
			};
		}

		componentWillReceiveProps(newProps)
		{
			const nextState = this.buildState(newProps);

			if (!isEqual(this.state, nextState))
			{
				this.setState(nextState);
			}
		}

		componentDidMount()
		{
			if (this.props.autofocus && this.textFieldRef)
			{
				this.textFieldRef.focus();
			}
		}

		render()
		{
			return View(
				{
					style: {
						flexGrow: 1,
						flexDirection: 'row',
					}
				},
				this.renderDecrementButton(),
				this.renderField(),
				this.renderIncrementButton(),
			);
		}

		renderDecrementButton()
		{
			if (!this.props.useDecrement)
			{
				return null;
			}

			return View(
				{
					style: {
						paddingRight: 4,
						paddingTop: 12,
						flexDirection: 'column',
						justifyContent: 'center',
					},
					onClick: () => this.decrement(),
					testId: `${this.testId}DecrementButton`,
				},
				Image({
					style: {
						width: 26,
						height: 26,
						backgroundColor: '#eef2f4',
						borderRadius: 47,
						opacity: this.props.disabled ? 0.6 : 1,
					},
					svg: {
						content: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M13 11H14H17V13L14 13L13 13H11L10 13H7V11H10H11H13Z" fill="#828B95"/></svg>`
					}
				})
			);
		}

		renderIncrementButton()
		{
			if (!this.props.useIncrement)
			{
				return null;
			}

			return View(
				{
					style: {
						paddingLeft: 4,
						flexDirection: 'column',
						justifyContent: 'center',
						paddingTop: 12,
					},
					onClick: () => this.increment(),
					testId: `${this.testId}IncrementButton`,
				},
				Image({
					style: {
						width: 26,
						height: 26,
						backgroundColor: '#eef2f4',
						borderRadius: 47,
						opacity: this.props.disabled ? 0.6 : 1,
					},
					svg: {
						content: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11 7H13V17H11V7Z" fill="#828B95"/><path d="M17 11V13L7 13L7 11L17 11Z" fill="#828B95"/></svg>`
					}
				})
			);
		}

		renderField()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						flexGrow: 1,
						paddingTop: 11,
					},
					onClick: () => this.onClick(),
					testId: this.testId,
				},
				View(
					{
						style: {
							borderColor: this.props.disabled ? '#EEF2F4' : '#bdc1c6',
							borderWidth: 1,
							borderRadius: 3,
							paddingLeft: 10,
							paddingRight: 10,
							paddingTop: 8,
							paddingBottom: 6,
							flexGrow: 1,
							flexDirection: 'row',
							justifyContent: 'space-between',
							backgroundColor: this.props.disabled ? '#EEF2F4' : 'transparent',
						}
					},
					this.renderLeftBlock(),
					this.props.disabled ? this.renderDisabledField() : this.renderEnabledField(),
					this.renderRightBlock(),
				),
				this.renderLabel(),
			);
		}

		renderDisabledField()
		{
			const text = stringify(this.formattedValue || this.props.placeholder);

			return View(
				{
					style: {
						flexGrow: 1,
					}
				},
				Text({
					text,
					numberOfLines: 1,
					style: {
						fontSize: 17,
						textAlign: this.textPosition,
						color: '#828B95',
						fontWeight: this.style.fontWeight || 'normal',
					}
				})
			);
		}

		renderEnabledField()
		{
			return TextField({
				...this.getNativeFieldProps(),
				value: this.currentlyRenderingValue,
			});
		}

		getNativeFieldProps()
		{
			return {
				ref: ref => {
					FocusContext.registerField({ref});
					this.textFieldRef = ref;
				},
				placeholder: this.props.placeholder || '',
				keyboardType: this.props.keyboardType || 'default',
				style: {
					fontSize: 17,
					textAlign: this.textPosition,
					flexGrow: 1,
					fontWeight: this.style.fontWeight || 'normal',
				},
				onBlur: () => {
					this.setState({}, () => this.onBlur());
				},
				onChangeText: (newVal) => {
					this.state.rawValue = newVal;
					this.onChange();
				},
				onFocus: () => {
					this.setState({}, () => this.onFocus());
				},
			};
		}

		format(val)
		{
			const formatter = this.props.formatValue || this.defaultFormatter();

			const formattedValue = formatter(val);

			return stringify(formattedValue);
		}

		defaultFormatter()
		{
			return val => val;
		}

		renderLeftBlock()
		{
			return this.props.leftBlock ? this.props.leftBlock(this) : null;
		}

		renderRightBlock()
		{
			return this.props.rightBlock ? this.props.rightBlock(this) : null;
		}

		renderLabel()
		{
			if (!this.props.label)
			{
				return null;
			}

			const positions = {left: 'flex-start', right: 'flex-end', center: 'center'};
			const position = this.props.labelAlign && positions[this.props.labelAlign]
				? positions[this.props.labelAlign]
				: positions.left;

			const margin = this.props.disabled ? 0 : 8;
			let padding = this.props.disabled ? 10 : 4;


			return View(
				{
					style: {
						position: 'absolute',
						top: 1,
						left: 0,
						width: '100%',
						flexDirection: 'row',
						justifyContent: position,
					}
				},
				View(
					{
						style: {
							backgroundColor: this.props.disabled ? '#EEF2F4' : '#ffffff',
							borderRadius: 4,
							paddingLeft: padding,
							paddingRight: padding,
							paddingTop: 3,
							paddingBottom: 3,
							marginLeft: margin,
							marginRight: margin,
							flexShrink: 1,
						}
					},
					Text({
						text: this.props.label,
						style: {
							color: '#A8ADB4',
							fontSize: 12,
						}
					})
				)
			);
		}

		increment()
		{
			FocusContext.blur();

			if (this.props.disabled)
			{
				return false;
			}

			const current = Number(this.state.rawValue);
			const max = this.props.hasOwnProperty('max') ? Number(this.props.max) : null;
			const step = this.props.hasOwnProperty('step') ? Number(this.props.step) : 1;

			const next = current + step;
			if (max !== null && next > max)
			{
				return false;
			}

			Haptics.impactMedium();

			this.setState({rawValue: next}, () => {
				this.onIncrement();
				this.onChange();
			});
		}

		decrement()
		{
			FocusContext.blur();

			if (this.props.disabled)
			{
				return false;
			}

			const current = Number(this.state.rawValue);
			const min = this.props.hasOwnProperty('min') ? Number(this.props.min) : null;
			const step = this.props.hasOwnProperty('step') ? Number(this.props.step) : 1;

			const next = current - step;
			if (min !== null && next < min)
			{
				return false;
			}

			Haptics.impactMedium();

			this.setState({rawValue: next}, () => {
				this.onDecrement();
				this.onChange();
			});
		}

		onIncrement()
		{
			if (this.props.onIncrement)
			{
				this.props.onIncrement(this);
			}
		}

		onDecrement()
		{
			if (this.props.onDecrement)
			{
				this.props.onDecrement(this);
			}
		}

		onBlur()
		{
			if (this.props.onBlur)
			{
				this.props.onBlur(this);
			}
		}

		onChange()
		{
			if (this.props.onChange)
			{
				this.props.onChange(this);
			}
		}

		onFocus()
		{
			this.forceCursorToTheEnd();

			if (this.props.onFocus)
			{
				this.props.onFocus(this);
			}
		}

		onClick()
		{
			if (this.props.onClick)
			{
				this.props.onClick(this);
			}
		}

		forceCursorToTheEnd()
		{
			if (this.textFieldRef)
			{
				const value = this.currentlyRenderingValue;
				this.textFieldRef.setSelection(value.length, value.length);
			}
		}

		get value()
		{
			return this.state.rawValue;
		}

		get formattedValue()
		{
			return this.format(this.state.rawValue);
		}

		get currentlyRenderingValue()
		{
			return stringify(this.isFocused ? this.value : this.formattedValue);
		}

		/**
		 * @returns {boolean}
		 */
		get isFocused()
		{
			if (this.textFieldRef && this.textFieldRef.isFocused())
			{
				return true;
			}
			return false;
		}

		get textPosition()
		{
			const positions = ['left', 'right', 'center'];
			return this.props.textAlign && positions.includes(this.props.textAlign)
				? this.props.textAlign
				: 'left';
		}

		get style()
		{
			return this.props.style || {};
		}
	}

	module.exports = { ProductGridStringField };

});