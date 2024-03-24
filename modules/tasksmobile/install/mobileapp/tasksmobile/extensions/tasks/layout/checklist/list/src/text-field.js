/**
 * @module tasks/layout/checklist/list/src/text-field
 */
jn.define('tasks/layout/checklist/list/src/text-field', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');
	const { Random } = require('utils/random');

	/**
	 * @class ItemTextField
	 */
	class ItemTextField extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.focused = false;
			this.textInputRef = null;
			this.cursorPosition = 0;
			this.handleOnSubmit = this.handleOnSubmit.bind(this);
			this.handleOnChange = this.handleOnChange.bind(this);
			this.handleOnLayout = this.handleOnLayout.bind(this);

			this.initialState(props);
		}

		componentWillReceiveProps(props)
		{
			this.initialState(props);
		}

		initialState(props)
		{
			const { completed } = props;

			this.state = {
				completed,
				reload: null,
			};
		}

		completeItem()
		{
			const { completed } = this.state;

			return new Promise((resolve) => {
				this.setState({ completed: !completed }, resolve);
			});
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
						marginTop: 4,
						justifyContent: 'center',
					},
					onLayout: this.handleOnLayout,
				},
				this.renderTextField(),
			);
		}

		handleOnChange(title)
		{
			const { onChangeText } = this.props;

			if (onChangeText)
			{
				onChangeText(title);
			}
		}

		handleOnSubmit()
		{
			const { onSubmit } = this.props;

			if (onSubmit)
			{
				onSubmit();
			}
		}

		handleOnLayout()
		{
			const { isFocused } = this.props;

			/**
			 * Set focus if the element is rendered in hidden space
			 */
			if (isFocused)
			{
				setTimeout(() => {
					this.textInputRef.focus();
				}, 200);
			}
		}

		focus()
		{
			if (this.textInputRef)
			{
				this.textInputRef.focus();
			}
		}

		blur()
		{
			if (this.textInputRef)
			{
				this.textInputRef.blur();
			}
		}

		/**
		 * @private
		 */
		isFocused()
		{
			if (this.textInputRef)
			{
				this.textInputRef.isFocused();
			}
		}

		/**
		 * @private
		 */
		clear()
		{
			if (this.textInputRef)
			{
				this.textInputRef.clear();
			}
		}

		reload()
		{
			this.setState({ reload: Random.getString() });
		}

		renderTextField()
		{
			const { styles = {}, onFocus, onBlur, isFocused, placeholder } = this.props;
			const value = this.getTitle();

			return TextInput({
				ref: (ref) => {
					this.textInputRef = ref;
				},
				focus: isFocused,
				placeholder,
				placeholderTextColor: AppTheme.colors.base4,
				style: {
					fontSize: 16,
					color: AppTheme.colors.base1,
					textAlignVertical: 'center',
					...styles,
				},
				onBlur: () => {
					this.focused = false;
					onBlur();
				},
				onFocus: () => {
					this.cursorPosition = value.length;
					this.focused = true;
					onFocus();
				},
				multiline: true,
				forcedValue: value,
				onSubmitEditing: this.handleOnSubmit,
				onChangeText: this.handleOnChange,
				onSelectionChange: ({ selection }) => {
					this.cursorPosition = selection.start;
				},
			});
		}

		renderBBCodeText()
		{
			const { styles = {} } = this.props;

			return View(
				{
					style: {
						alignItems: 'center',
						flexDirection: 'row',
					},
				},
				BBCodeText({
					style: {
						fontSize: 16,
						fontWeight: '400',
						color: AppTheme.colors.base3,
						textAlignVertical: 'center',
						...styles,
					},
					focus: false,
					linksUnderline: false,
					value: this.getTitleWithUrls(),
				}),
			);
		}

		getTitleWithUrls(title, members)
		{
			let newTitle = title;
			Object.keys(members).forEach((id) => {
				const { name } = members[id];
				newTitle = newTitle.replace(
					name,
					`[COLOR=${AppTheme.colors.accentMainLinks}][URL=${id}]${name}[/URL][/COLOR]`,
				);
			});
		}

		getTitle()
		{
			const { completed } = this.state;
			const { getTitle } = this.props;
			const title = getTitle();

			if (completed && title.length > 0)
			{
				return `[COLOR=${AppTheme.colors.base5}]${title}[/COLOR]`;
			}

			return title;
		}

		getCursorPosition()
		{
			return this.cursorPosition;
		}
	}

	module.exports = { ItemTextField };
});
