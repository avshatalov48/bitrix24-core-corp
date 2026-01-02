/**
 * @module calendar/event-edit-form/layout/event-name-input
 */
jn.define('calendar/event-edit-form/layout/event-name-input', (require, exports, module) => {
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { StringInput, InputDesign, InputMode, InputSize } = require('ui-system/form/inputs/string');

	const { State, observeState } = require('calendar/event-edit-form/state');

	/**
	 * @class EventNameInput
	 */
	class EventNameInput extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.refs = {
				input: null,
			};
		}

		get layout()
		{
			return this.props.layout;
		}

		componentDidMount()
		{
			if (!State.isEditForm())
			{
				this.refs.input?.focus();
			}
		}

		render()
		{
			return StringInput({
				testId: 'calendar-event-edit-form-event-name-input',
				readOnly: this.props.editAttendeesMode,
				placeholder: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_EVENT_NAME'),
				size: InputSize.L,
				design: InputDesign.GREY,
				mode: InputMode.STROKE,
				value: this.props.eventNameValue,
				onBlur: this.onBlurHandler,
				onChange: this.onChangeHandler,
				onSubmit: this.onSubmitHandler,
				onErase: this.onEraseHandler,
				erase: !this.props.editAttendeesMode,
				forwardRef: this.#bindInputRef,
			});
		}

		#bindInputRef = (ref) => {
			this.refs.input = ref;
		};

		onChangeHandler = (eventNameValue) => {
			State.setEventNameValue(eventNameValue);
		};

		onBlurHandler = () => {
			this.changeLayoutTitle(this.props.eventNameValue);
		};

		onSubmitHandler = () => {
			Keyboard.dismiss();
		};

		onEraseHandler = () => {
			this.refs.input?.clear();
			this.changeLayoutTitle(Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_DEFAULT_EVENT_NAME'));
		};

		changeLayoutTitle(eventNameValue)
		{
			const text = Type.isStringFilled(eventNameValue) && eventNameValue.trim()
				? eventNameValue
				: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_DEFAULT_EVENT_NAME')
			;

			this.layout.setTitle({ text, type: 'wizard' });
		}
	}

	const mapStateToProps = (state) => ({
		eventNameValue: state.eventNameValue,
		editAttendeesMode: state.editAttendeesMode,
	});

	module.exports = { EventNameInput: observeState(EventNameInput, mapStateToProps) };
});
