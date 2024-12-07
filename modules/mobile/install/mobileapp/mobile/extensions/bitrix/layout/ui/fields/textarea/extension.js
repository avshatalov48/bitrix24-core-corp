/**
 * @module layout/ui/fields/textarea
 */
jn.define('layout/ui/fields/textarea', (require, exports, module) => {
	const { StringFieldClass } = require('layout/ui/fields/string');
	const { EditableTextBlock } = require('layout/ui/editable-text-block');
	const { CollapsibleText } = require('layout/ui/collapsible-text');
	const { TextEditor } = require('text-editor');
	const { debounce } = require('utils/function');
	const AppTheme = require('apptheme');

	/**
	 * @class TextAreaField
	 */
	class TextAreaField extends StringFieldClass
	{
		constructor(props)
		{
			super(props);

			this.state.showAll = this.getValue().length <= 180;
			this.state.height = this.state.focus ? 20 : 1;

			if (this.props.useEditableTextBlock === true)
			{
				this.isPossibleToFocus = () => false;
				this.isEmptyEditable = () => false;
				this.showTitle = () => !this.isEmpty();
			}

			/** @public */
			this.debouncedChangeValues = debounce((text, files) => this.changeValues(text, files), 50, this);
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isMultiline()
		{
			return BX.prop.getBoolean(this.props, 'multiline', true);
		}

		componentDidMount()
		{
			super.componentDidMount();
			this.initialValue = this.getValue();
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			if (this.showAllFromProps)
			{
				return {
					...styles,
					editableValue: {
						...styles.editableValue,
						flex: 1,
					},
				};
			}

			return {
				...styles,
				editableValue: {
					...styles.editableValue,
					flex: 1,
					height: 'auto',
					minHeight: this.state.height ? 20 : 1,
					maxHeight: this.state.showAll ? null : 88,
				},
			};
		}

		renderReadOnlyContent()
		{
			if (!this.props.useBBCodeEditor)
			{
				return super.renderReadOnlyContent();
			}

			if (this.isEmpty())
			{
				return this.renderEmptyContent();
			}

			return View(
				{
					style: {
						flexDirection: 'column',
						flex: 1,
					},
				},
				new CollapsibleText({
					value: this.getValue(),
					style: this.getStyles().value,
					bbCodeMode: true,
					useBBCodeEditor: true,
					onClick: this.openBBCodeTextEditor.bind(this),
					onLongClick: this.openBBCodeTextEditor.bind(this),
				}),
			);
		}

		renderEditableContent()
		{
			if (this.props.useEditableTextBlock || this.props.useBBCodeEditor)
			{
				const styles = this.getDefaultStyles();

				return new EditableTextBlock({
					value: this.getValue(),
					placeholder: this.getTitleText(),
					onSave: (value, files) => this.props.onChange(value, files),
					textProps: {
						testId: this.isEmpty() ? `${this.testId}_NAME` : `${this.testId}_VALUE`,
						style: {
							color: this.isEmpty() ? this.styles.title.color : AppTheme.colors.base0,
							fontSize: this.isEmpty() ? styles.emptyValue.fontSize : styles.base.fontSize,
							fontWeight: '400',
							height: '100%',
						},
						bbCodeMode: true,
					},
					editorProps: {
						title: this.getTitleText(),
						placeholder: this.getPlaceholder(),
						useBBCodeEditor: this.props.useBBCodeEditor,
						bbCodeEditorParams: this.getBBCodeTextEditorParams(),
						parentWidget: this.getParentWidget() || PageManager,
					},
					externalStyles: {
						borderWidth: 0,
						paddingLeft: 0,
						paddingTop: this.isEmpty() ? 8 : 0,
						paddingBottom: this.isEmpty() ? 8 : 0,
						borderRadius: 0,
						width: '100%',
					},
					testId: this.isEmpty() ? `${this.testId}_TITLE` : `${this.testId}_INNER_CONTENT`,
				});
			}

			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'column',
						minHeight: this.state.height ? 20 : 1,
						height: 'auto',
					},
				},
				new TextInputHeightFixer({
					interactable: Application.getPlatform() !== 'ios' && this.state.focus && !this.state.showAll,
					textInput: this.getFieldInputProps(),
				}),
				this.renderShowAllButton(1),
				this.renderHideButton(),
			);
		}

		openBBCodeTextEditor(value = null)
		{
			void TextEditor.edit(this.getBBCodeTextEditorParams(value));
		}

		getBBCodeTextEditorParams(value = null)
		{
			const config = this.getConfig();

			return {
				title: this.getTitleText(),
				value: value || this.getValue(),
				readOnly: this.isReadOnly(),
				parentWidget: this.getParentWidget(),
				allowFiles: config.allowFiles,
				fileField: config.fileField,
				autoFocus: config.autoFocus,
				closeOnSave: true,
				textInput: {
					placeholder: this.getPlaceholder(),
				},
			};
		}

		getFieldInputProps()
		{
			return {
				...super.getFieldInputProps(),
				enable: !(Application.getPlatform() === 'ios' && !this.state.focus),
				multiline: this.isMultiline(),
				onSubmitEditing: this.getConfig().onSubmitEditing,
			};
		}

		changeValues(text, files)
		{
			this.fieldValue = text;
			this.handleChange(text, files);
		}
	}

	// todo We use this hack because of bug with native TextInput height calculation
	class TextInputHeightFixer extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				height: undefined,
			};

			this.initialRender = true;

			this.resizeContent = this.resizeContent.bind(this);
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						flexGrow: 2,
						height: Application.getPlatform() === 'ios' ? this.state.height : 'auto',
					},
					interactable: this.props.interactable,
				},
				TextInput({
					...this.props.textInput,
					onLayout: this.resizeContent,
					onContentSizeChange: this.resizeContent,
				}),
			);
		}

		resizeContent({ height })
		{
			if (this.initialRender)
			{
				this.initialRender = false;
				this.setState({ height });

				return;
			}

			if (this.state.height !== 'auto')
			{
				this.setState({ height: 'auto' });
			}
		}
	}

	TextAreaField.propTypes = {
		...StringFieldClass.propTypes,
		useBBCodeEditor: PropTypes.bool,
		useEditableTextBlock: PropTypes.bool,
		multiline: PropTypes.bool,
		interactable: PropTypes.bool,
		textInput: PropTypes.object,
	};

	TextAreaField.defaultProps = {
		...StringFieldClass.defaultProps,
		useBBCodeEditor: false,
		useEditableTextBlock: false,
		multiline: true,
		interactable: true,
	};

	module.exports = {
		TextAreaType: 'textarea',
		TextAreaFieldClass: TextAreaField,
		TextAreaField: (props) => new TextAreaField(props),
	};
});
