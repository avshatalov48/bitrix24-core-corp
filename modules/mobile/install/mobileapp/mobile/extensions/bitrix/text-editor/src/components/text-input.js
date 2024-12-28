/**
 * @module text-editor/components/text-input
 */
jn.define('text-editor/components/text-input', (require, exports, module) => {
	const { Type } = require('type');
	const { parser } = require('text-editor/internal/parser');
	const { Indent } = require('tokens');

	const textInputPromise = Symbol('@@textInputPromise');
	const textInputResolver = Symbol('@@textInputResolver');

	class TextInputComponent extends LayoutComponent
	{
		/**
		 * @param props {{
		 *     value?: string,
		 *     events?: {
		 *         [eventName: string]: (Array) => void,
		 *     },
		 *     style: {
		 *         [key: string]: any,
		 *     },
		 *     autoFocus?: boolean,
		 *     allowBBCode?: boolean,
		 *     placeholder?: string,
		 *     onLinkClick?: () => void,
		 * }}
		 */
		constructor(props = {})
		{
			super(props);

			this[textInputPromise] = new Promise((resolve) => {
				this[textInputResolver] = resolve;
			});

			this.state = {
				value: props.value ?? '',
				storedValue: '',
				selection: { start: 0, end: 0 },
				autoFocus: props.autoFocus ?? false,
				style: {
					...props.style,
				},
				forcedHeight: undefined,
				placeholder: props.placeholder ?? '',
				onLinkClick: props.onLinkClick,
			};

			if (Type.isPlainObject(props.events))
			{
				Object.entries(props.events).forEach(([eventName, handler]) => {
					this.on(eventName, handler);
				});
			}
		}

		componentWillReceiveProps(props)
		{
			this.setState({
				onLinkClick: props.onLinkClick,
			});

			super.componentWillReceiveProps(props);
		}

		/**
		 * Inserts text or BBCode
		 * @param text {string}
		 */
		async insert(text)
		{
			if (Type.isString(text))
			{
				const selection = this.getSelection();
				const textInput = await this.getTextInput();

				textInput.insert(text);

				const ast = parser.parse(text);
				const textLength = ast.getPlainTextLength();
				const targetSelection = selection.start + textLength;

				await this.setSelection({
					start: targetSelection,
					end: targetSelection,
				});
			}
		}

		/**
		 * Gets selection
		 * @returns {{start: number, end: number}}
		 */
		getSelection()
		{
			return this.state.selection;
		}

		forceStyle(style)
		{
			this.setState({
				style: {
					...this.state.style,
					...style,
				},
			});
		}

		/**
		 * Sets selection
		 * @param selection {{start: number, end: number}}
		 */
		async setSelection(selection)
		{
			if (Type.isPlainObject(selection))
			{
				const { start, end } = selection;
				if (Type.isNumber(start) && Type.isNumber(end))
				{
					const textInput = await this.getTextInput();
					await textInput.setSelection(start, end);

					this.setState({
						selection: {
							start,
							end,
						},
					});
				}
			}
		}

		/**
		 * Format current selection
		 * @param type {'bold'|'italic'|'underline'|'strikethrough'|'markedList'|'numericList'}
		 */
		async format(type)
		{
			const textInput = await this.getTextInput();

			if (type === 'bold')
			{
				textInput.applyBold();
			}

			if (type === 'italic')
			{
				textInput.applyItalic();
			}

			if (type === 'underline')
			{
				textInput.applyUnderline();
			}

			if (type === 'strikethrough')
			{
				textInput.applyStrikethrough();
			}

			if (type === 'markedList')
			{
				textInput.applyBulletList();
			}

			if (type === 'numericList')
			{
				textInput.applyNumberList();
			}
		}

		/**
		 * Forces focus on input
		 */
		async focus()
		{
			const textInput = await this.getTextInput();
			textInput.focus();
		}

		/**
		 * @private
		 * @returns {Promise<*>}
		 */
		getTextInput()
		{
			return this[textInputPromise];
		}

		getValue()
		{
			return this.state.storedValue;
		}

		async getPlainTextValue()
		{
			const textInput = await this.getTextInput();

			return textInput.getTextValue();
		}

		async blur()
		{
			const textInput = await this.getTextInput();
			textInput.blur({ hideKeyboard: true });
		}

		setValue(value)
		{
			this.setState({
				value,
				storedValue: value,
			});
		}

		render()
		{
			return TextInput({
				placeholder: this.state.placeholder,
				placeholderTextColor: this.state.placeholderTextColor,
				keyboardType: 'default',
				multiline: true,
				focus: this.state.autoFocus,
				showBBCode: !this.props.allowBBCode,
				style: {
					...this.state.style,
					flex: 1,
					height: this.state.forcedHeight,
					paddingHorizontal: Indent.XL3.toNumber(),
				},
				onLayout: (rect) => {
					this.setState({
						forcedHeight: rect.height,
					});
				},
				onFocus: () => {
					console.log('focus');
					this.emit('onFocus', []);
				},
				onBlur: () => {
					console.log('blur');
					this.emit('onBlur', []);
				},
				selectedStyles: (data) => {
					this.emit('onStyleChange', [data]);
				},
				ref: (textInputRef) => {
					this[textInputResolver](textInputRef);
				},
				value: this.state.value,
				onChangeText: (value) => {
					this.setState({
						storedValue: value,
					});
					this.emit('onChange', [value]);
				},
				onSelectionChange: ({ selection }) => {
					this.setState({
						selection: { ...selection },
					});
				},
				onLinkClick: this.state.onLinkClick,
			});
		}
	}

	module.exports = {
		TextInputComponent,
	};
});
