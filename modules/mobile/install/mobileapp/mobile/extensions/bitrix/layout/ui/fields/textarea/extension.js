/**
 * @module layout/ui/fields/textarea
 */
jn.define('layout/ui/fields/textarea', (require, exports, module) => {
	const { StringFieldClass } = require('layout/ui/fields/string');
	const { EditableTextBlock } = require('layout/ui/editable-text-block');
	const { Color } = require('tokens');

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
		}

		componentDidMount() {
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

		getEllipsizeParams()
		{
			return this.getConfig().ellipsize ? {
				numberOfLines: 4,
				ellipsize: 'end',
			} : null;
		}

		renderEditableContent()
		{
			if (this.props.useEditableTextBlock === true)
			{
				const styles = this.getDefaultStyles();

				return new EditableTextBlock({
					value: this.getValue(),
					placeholder: this.getTitleText(),
					onSave: (value) => this.props.onChange(value),
					textProps: {
						testId: this.isEmpty() ? `${this.testId}_NAME` : `${this.testId}_VALUE`,
						style: {
							color: this.isEmpty() ? this.styles.title.color : Color.base0,
							fontSize: this.isEmpty() ? styles.emptyValue.fontSize : styles.base.fontSize,
							fontWeight: '400',
						},
						bbCodeMode: true,
					},
					editorProps: {
						title: this.getTitleText(),
						placeholder: this.getPlaceholder(),
						parentWidget: this.getParentWidget() || layout,
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

		getFieldInputProps()
		{
			return {
				...super.getFieldInputProps(),
				enable: !(Application.getPlatform() === 'ios' && !this.state.focus),
				multiline: (this.props.multiline || true),
				onSubmitEditing: this.getConfig().onSubmitEditing,
			};
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

	module.exports = {
		TextAreaType: 'textarea',
		TextAreaField: (props) => new TextAreaField(props),
	};
});
