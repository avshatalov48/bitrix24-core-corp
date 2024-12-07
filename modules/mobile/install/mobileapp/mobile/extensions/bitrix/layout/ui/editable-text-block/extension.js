/**
 * @module layout/ui/editable-text-block
 */
jn.define('layout/ui/editable-text-block', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { TextEditor } = require('layout/ui/text-editor');
	const { TextEditor: BBCodeTextEditor } = require('text-editor');
	const { CollapsibleText } = require('layout/ui/collapsible-text');
	const { Type } = require('type');
	const { pen } = require('assets/common');
	const { inAppUrl } = require('in-app-url');
	const { isOffline } = require('device/connection');

	/**
	 * @class EditableTextBlock
	 */
	class EditableTextBlock extends LayoutComponent
	{
		/**
		 * @param {Object} props
		 * @param {string} props.value
		 * @param {string} [props.placeholder]
		 * @param {Function} [props.onSave] - callback with newValue onSave(value)
		 * @param {Function} [props.onBeforeSave]
		 * @param {Object} [props.externalStyles]
		 * @param {Object} [props.textProps] - props for CollapsibleText
		 * @param {Object} [props.editorProps]
		 * @param {string} [props.editorProps.placeholder]
		 * @param {string} [props.editorProps.title]
		 * @param {string} [props.editorProps.required]
		 * @param {Function} [props.editorProps.onLinkClick]
		 * @param {object} [props.editorProps.textAreaStyle]
		 * @param {boolean} [props.editorProps.useBBCodeEditor]
		 * @param {string} [props.testId]
		 * @param {string} [props.editIconTestId]
		 * @param {boolean} [props.showEditIcon]
		 */
		constructor(props)
		{
			super(props);

			this.textEditorLayout = null;
		}

		shouldShowEditIcon()
		{
			return BX.prop.getBoolean(this.props, 'showEditIcon', true);
		}

		render()
		{
			return View(
				{
					style: {
						paddingBottom: 15,
						paddingTop: this.props.value.length > 0 ? 14 : 30,
						paddingLeft: 16,
						paddingRight: 47,
						borderWidth: 1,
						borderColor: AppTheme.colors.bgSeparatorPrimary,
						borderRadius: 12,
						...this.props.externalStyles,
					},
					testId: this.props.testId,
				},
				this.shouldShowEditIcon() && this.renderEditIcon(),
				this.renderText(),
			);
		}

		renderText()
		{
			const params = {
				value: this.props.value || this.props.placeholder,
				...this.props.textProps,
				useBBCodeEditor: this.props.editorProps.useBBCodeEditor,
				onClick: () => this.openEditor(),
				onLongClick: () => this.openEditor(),
			};

			if (this.props.editorProps.useBBCodeEditor)
			{
				params.onLinkClick = () => this.openEditor();
			}

			return new CollapsibleText(params);
		}

		renderEditIcon()
		{
			if (this.props?.value.trim() === '')
			{
				return null;
			}

			const paddingTop = Type.isNil(this.props.externalStyles?.paddingTop) ? 14 : this.props.externalStyles.paddingTop;

			return View(
				{
					onClick: () => this.openEditor(),
					style: {
						position: 'absolute',
						right: 0,
						top: 0,
						paddingHorizontal: 11,
						paddingTop,
					},
					testId: this.props.editIconTestId || 'TextEditorEditIcon',
				},
				Image({
					svg: {
						content: pen(),
					},
					style: {
						height: 15,
						width: 14,
					},
				}),
			);
		}

		openEditor()
		{
			const {
				title,
				placeholder,
				required,
				textAreaStyle,
				useBBCodeEditor,
				bbCodeEditorParams = {},
				parentWidget,
			} = this.props.editorProps;
			const { value } = this.props;

			if (useBBCodeEditor)
			{
				const editorParams = {
					...bbCodeEditorParams,
					title,
					textInput: {
						...bbCodeEditorParams.textInput,
						placeholder,
					},
					value,
					readOnly: (!bbCodeEditorParams.readOnly && isOffline() ? true : bbCodeEditorParams.readOnly),
					onSave: ({ bbcode, files }) => {
						if (this.props.onSave)
						{
							this.props.onSave(bbcode, files);
						}
						this.textEditorLayout = null;
					},
				};

				BBCodeTextEditor.edit(editorParams)
					.then((layout) => {
						this.textEditorLayout = layout;
					})
					.catch(console.error)
				;

				return;
			}

			TextEditor.open({
				title,
				parentWidget,
				text: value,
				required,
				placeholder,
				textAreaStyle,
				onSave: (text) => this.onSave(text),
				onBeforeSave: (editor) => this.onBeforeSave(editor),
				onLinkClick: ({ url }) => this.onEditorLinkClick(url),
			})
				.then(({ layout }) => {
					this.textEditorLayout = layout;
				})
				.catch((error) => console.error(error))
			;
		}

		onSave(value)
		{
			const trimmedValue = value.trim();
			this.textEditorLayout = null;
			if (this.props.onSave)
			{
				this.props.onSave(trimmedValue);
			}
		}

		async onBeforeSave(editor)
		{
			if (this.props.onBeforeSave)
			{
				return this.props.onBeforeSave(editor);
			}

			return null;
		}

		onEditorLinkClick(url)
		{
			if (this.props.editorProps.onLinkClick)
			{
				this.props.editorProps.onLinkClick(url);

				return;
			}

			inAppUrl.open(url, {
				backdrop: true,
				parentWidget: this.textEditorLayout,
			});
		}
	}

	EditableTextBlock.propTypes = {
		style: PropTypes.object,
		value: PropTypes.string,
		placeholder: PropTypes.string,
		onSave: PropTypes.func,
		onBeforeSave: PropTypes.func,
		testId: PropTypes.string,
		editIconTestId: PropTypes.string,
		textProps: PropTypes.object,
		editorProps: PropTypes.shape({
			title: PropTypes.string,
			placeholder: PropTypes.string,
			onLinkClick: PropTypes.func,
			required: PropTypes.bool,
		}),
	};

	module.exports = {
		EditableTextBlock,
	};
});
