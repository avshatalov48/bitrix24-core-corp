/**
 * @module sign/master/steps/fields-input-step/fields-layout/select-input
 */
jn.define('sign/master/steps/fields-input-step/fields-layout/select-input', (require, exports, module) => {
	const { Input, InputDesign } = require('ui-system/form/inputs/input');
	const { SelectField } = require('layout/ui/fields/select');
	const { PureComponent } = require('layout/pure-component');

	/**
	 * @class SelectInput
	 */
	class SelectInput extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				focus: Boolean(this.props.focus || this.props.isFocused),
			};
		}

		render()
		{
			const {
				id,
				title,
				value,
				onChange,
				config,
				size,
				mode,
				style,
				erase,
			} = this.props;

			this.field = SelectField({
				id,
				title,
				value,
				onChange,
				config,
				onBlur: this.handleOnBlur,
			});

			return Input({
				readOnly: true,
				dropdown: !this.field.isReadOnly() && !this.field.isRestricted(),
				locked: this.field.isRestricted(),
				required: this.field.isRequired(),
				value: this.getValue(),
				label: (this.field.shouldShowTitle() ? this.field.getTitleText() : ''),
				design: this.getDesign(),
				onFocus: this.handleOnFocus,
				onClick: this.handleOnContentClick,
				onLongClick: this.handleOnContentLongClick,
				placeholder: this.getPlaceholder(),
				erase,
				size,
				mode,
				style,
			});
		}

		getPlaceholder()
		{
			return this.props.placeholder ?? this.field.getSelectedItemsText();
		}

		getValue()
		{
			if (this.field.isEmpty())
			{
				return '';
			}

			return this.field.getSelectedItemsText();
		}

		handleOnFocus = async () => {
			const { onFocus } = this.props;
			await this.handleOnContentClick();

			return onFocus();
		};

		handleOnContentClick = async () => {
			const contentClick = this.field.getContentClickHandler();
			await this.#setFocused(true);

			return contentClick?.();
		};

		handleOnContentLongClick = () => {
			const longClick = this.field.getContentLongClickHandler();

			longClick?.();
		};

		handleOnBlur = async () => {
			const { onBlur } = this.props;
			await this.#setFocused(false);

			return onBlur?.();
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

		withFocused()
		{
			const { withFocused } = this.props;

			return Boolean(withFocused);
		}

		isFocused()
		{
			const { focus } = this.state;

			return Boolean(focus);
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
	}

	module.exports = {
		SelectInput: (props) => new SelectInput(props),
	};
});
