/**
 * @module ui-system/form/inputs/datetime
 */
jn.define('ui-system/form/inputs/datetime', (require, exports, module) => {
	const { isNil } = require('utils/type');
	const { refSubstitution } = require('utils/function');
	const { PureComponent } = require('layout/pure-component');
	const { PropTypes } = require('utils/validation');
	const { DateTimeFieldClass, DatePickerType } = require('layout/ui/fields/datetime');
	const { Input, InputClass, InputSize, InputMode, InputDesign, Icon } = require('ui-system/form/inputs/input');

	/**
	 * @typedef {InputProps} DateTimeInputProps
	 * @property {boolean} [enableTime]
	 * @property {boolean} [checkTimezoneOffset]
	 * @property {'date' | 'datetime'} [datePickerType]
	 * @property {string} [dateFormat]
	 * @property {string} [defaultListTitle]
	 * @property {Function} [dateFormatter]
	 * @property {Array} [items]
	 * @property {Function} [copyingOnLongClick]
	 *
	 * @class DateTimeInputTheme
	 */
	class DateTimeInput extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.#initState(props);
		}

		componentWillReceiveProps(nextProps)
		{
			this.#initState(nextProps);
		}

		#initState(initProps)
		{
			const { dataTimeConfig, props } = this.getProps(initProps);

			this.field = new DateTimeFieldClass({
				config: dataTimeConfig,
				onBlur: this.handleOnBlur,
				...props,
			});

			this.state = {
				focus: Boolean(props.focus || props.isFocused),
			};
		}

		getProps(initProps)
		{
			const {
				enableTime,
				parentWidget,
				datePickerType,
				dateFormat,
				dateFormatter,
				defaultListTitle,
				checkTimezoneOffset,
				copyingOnLongClick,
				...restProps
			} = initProps;

			const cleanNil = (values) => {
				return Object.fromEntries(
					Object.entries(values).filter(([_, value]) => !isNil(value)),
				);
			};

			const dataTimeConfig = cleanNil({
				dateFormat,
				enableTime,
				datePickerType,
				parentWidget,
				dateFormatter,
				defaultListTitle,
				checkTimezoneOffset,
				copyingOnLongClick,
			});

			return { props: restProps, dataTimeConfig };
		}

		handleOnFocus = async () => {
			const { onFocus } = this.props;
			await this.handleOnContentClick();

			return onFocus();
		};

		handleOnBlur = async () => {
			const { onBlur } = this.props;
			await this.#setFocused(false);

			return onBlur?.();
		};

		handleOnContentClick = async () => {
			const contentClick = this.field.getContentClickHandler();
			await this.#setFocused(true);

			return contentClick?.();
		};

		#setFocused(focus)
		{
			if (!this.withFocused())
			{
				return Promise.resolve();
			}

			return new Promise((resolve) => {
				this.setState(
					{ focus },
					resolve,
				);
			});
		}

		handleOnContentLongClick = () => {
			const longClick = this.field.getContentLongClickHandler();

			longClick?.();
		};

		getValue()
		{
			if (this.field.isEmpty())
			{
				return '';
			}

			return this.field.getDisplayedValue();
		}

		getPlaceholder()
		{
			return this.field.getEmptyText();
		}

		isFocused()
		{
			const { focus } = this.state;

			return Boolean(focus);
		}

		render()
		{
			const { props } = this.getProps(this.props);

			return Input({
				...props,
				readOnly: true,
				value: this.getValue(),
				design: this.getDesign(),
				placeholder: this.getPlaceholder(),
				onFocus: this.handleOnFocus,
				onClick: this.handleOnContentClick,
				onLongClick: this.handleOnContentLongClick,
			});
		}

		getDesign()
		{
			const { design } = this.props;

			if (!this.withFocused())
			{
				return design;
			}

			return this.isFocused() ? InputDesign.PRIMARY : design;
		}

		withFocused()
		{
			const { withFocused } = this.props;

			return Boolean(withFocused);
		}
	}

	DateTimeInput.defaultProps = {
		...InputClass.defaultProps,
		withFocused: true,
	};

	DateTimeInput.propTypes = {
		...InputClass.propTypes,
		value: PropTypes.number,
		copyingOnLongClick: PropTypes.bool,
		withFocused: PropTypes.bool,
		onChange: PropTypes.func,
		// datetime field props
		enableTime: PropTypes.bool,
		checkTimezoneOffset: PropTypes.bool,
		datePickerType: PropTypes.oneOf(Object.values(DatePickerType)),
		dateFormat: PropTypes.string,
		defaultListTitle: PropTypes.string,
		dateFormatter: PropTypes.func,
		items: PropTypes.array,
	};

	module.exports = {
		/**
		 * @param {DateTimeInputProps} props
		 */
		DateTimeInput: (props) => refSubstitution(DateTimeInput)(props),
		InputSize,
		InputMode,
		InputDesign,
		DatePickerType,
		Icon,
	};
});
