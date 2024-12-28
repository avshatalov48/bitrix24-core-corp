/**
 * @module ui-system/form/inputs/input/src/visual-decorator
 */
jn.define('ui-system/form/inputs/input/src/visual-decorator', (require, exports, module) => {
	const { Type } = require('type');
	const { isNil } = require('utils/type');
	const { isEmpty, isEqual } = require('utils/object');
	const { refSubstitution, debounce } = require('utils/function');
	const { PureComponent } = require('layout/pure-component');

	/**
	 * @typedef {Object} InputVisualDecoratorProps
	 * @property {string} required

	 * @class InputVisualDecorator
	 * @param {InputProps} props
	 */
	class InputVisualDecorator extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.componentRef = null;
			this.currentValue = null;
			this.initState(props, true);
			this.handleOnChange = debounce(this.handleOnChange, 50, this);
		}

		shouldComponentUpdate(nextProps, nextState)
		{
			const extractWithoutValue = ({ value, text, ...rest }) => rest;
			const hasValueChanged = (current, next, log) => !isEqual(current, next, log);

			let prevPropsToCompare = this.props;
			let nextPropsToCompare = nextProps;
			let prevStateToCompare = this.state;
			let nextStateToCompare = Array.isArray(nextState) ? nextState[0] : nextState;
			const currentValue = this.currentValue;

			if (currentValue !== null)
			{
				const nextPropsValue = nextProps.value;
				const hasChangedCurrentValue = hasValueChanged(currentValue, nextPropsValue);

				if (hasChangedCurrentValue && !isNil(nextPropsValue))
				{
					this.logComponentDifference({ value: currentValue }, { value: nextPropsValue }, null, null);

					return true;
				}

				prevPropsToCompare = extractWithoutValue(this.props);
				nextPropsToCompare = extractWithoutValue(nextProps);
				prevStateToCompare = extractWithoutValue(this.state);
				nextStateToCompare = extractWithoutValue(nextStateToCompare);
			}

			const hasChangedProps = hasValueChanged(prevPropsToCompare, nextPropsToCompare);
			const hasChangedState = hasValueChanged(prevStateToCompare, nextStateToCompare);
			const hasChanged = hasChangedState || hasChangedProps;

			if (hasChanged)
			{
				this.logComponentDifference(prevPropsToCompare, nextPropsToCompare, this.state, nextStateToCompare);

				return true;
			}

			return false;
		}

		componentWillReceiveProps(nextProps)
		{
			this.initState(nextProps);
		}

		initState(props, initialState = false)
		{
			const isFocused = props.focus || props.isFocused;
			const value = props.value ?? props.text ?? (this.state.value || '');
			const error = Type.isBoolean(props.error) && Boolean(props.error);
			const focus = Type.isBoolean(isFocused) ? Boolean(isFocused) : this.#isFocused();
			const valid = initialState ? true : this.isValidValue({ value, focus });

			this.state = { value, error, valid, focus };
		}

		render()
		{
			const { component: InputComponent } = this.props;

			return new InputComponent(
				{
					...this.getInputPropsParams(),
					...this.getInputStateParams(),
					ref: this.handleOnRef,
					error: this.#isError(),
					onBlur: this.handleOnBlur,
					onFocus: this.handleOnFocus,
					onChange: this.handleOnChange,
				},
			);
		}

		getInputPropsParams()
		{
			const {
				ref,
				text,
				value,
				innerRef,
				required,
				component,
				...restProps
			} = this.props;

			return restProps;
		}

		getInputStateParams()
		{
			const { valid, error, ...restState } = this.state;

			return restState;
		}

		#isFocused()
		{
			const { focus } = this.state;

			return Boolean(focus);
		}

		#isError()
		{
			const { error, valid } = this.state;

			return Boolean(error) || !valid;
		}

		handleOnRef = (componentRef) => {
			this.componentRef = componentRef;
			const { innerRef } = this.props;

			innerRef?.(componentRef);
		};

		handleOnFocus = () => {
			const { onFocus } = this.props;

			if (this.#isFocused())
			{
				return Promise.resolve();
			}

			return this.setFocused(true, onFocus);
		};

		handleOnBlur = () => {
			const { onBlur } = this.props;

			if (!this.#isFocused())
			{
				return Promise.resolve();
			}

			return this.setFocused(false, onBlur);
		};

		handleOnChange = (value) => {
			const { onChange } = this.props;
			const valid = this.isValidValue({
				focus: this.#isFocused(),
				value: this.getValue(),
			});

			this.updateState(
				{ value, valid },
				() => {
					this.currentValue = value;
					onChange?.(value);
				},
			);
		};

		handleOnValid(value)
		{
			const { onValid } = this.props;

			if (!onValid)
			{
				return true;
			}

			return onValid?.(value);
		}

		handleOnError = (error) => {
			const { onError, testId } = this.props;

			onError?.(error, testId);
		};

		isValidValue = ({ focus, value }) => {
			return focus ? this.handleOnValid(value) : this.isValid(value);
		};

		setFocused(focus, callback)
		{
			return new Promise((resolve) => {
				const valid = this.isValidValue({ focus, value: this.getValue() });
				this.updateState({
					valid,
					focus,
				}, () => {
					callback?.();
					resolve();
				});
			});
		}

		updateState(state, callback)
		{
			this.setState(
				state,
				() => {
					const valid = state.valid;
					if (!valid)
					{
						this.handleOnError({ valid });
					}

					callback();
				},
			);
		}

		getValue()
		{
			if (this.currentValue !== null)
			{
				return this.currentValue;
			}

			const { value } = this.state;

			return value;
		}

		isValid(value)
		{
			if (this.isRequired())
			{
				return !isEmpty(value);
			}

			return true;
		}

		isRequired()
		{
			const { required } = this.props;

			return Boolean(required);
		}
	}

	InputVisualDecorator.propTypes = {
		onValid: PropTypes.func,
	};

	module.exports = {
		InputVisualDecorator: refSubstitution(InputVisualDecorator),
	};
});
