/**
 * @module layout/ui/editable-text-block
 */
jn.define('layout/ui/editable-text-block', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { TextEditor } = require('layout/ui/text-editor');
	const { TextEditor: BBCodeTextEditor } = require('text-editor');
	const { CollapsibleText } = require('layout/ui/collapsible-text');
	const { PlainTextFormatter } = require('bbcode/formatter/plain-text-formatter');
	const { pen } = require('assets/common');
	const { inAppUrl } = require('in-app-url');
	const { isOffline } = require('device/connection');
	const { Loc } = require('loc');

	/**
	 * @class EditableTextBlock
	 */
	class EditableTextBlock extends LayoutComponent
	{
		/**
		 * @param {Object} props
		 * @param {string} props.value
		 * @param {string} [props.placeholder=Loc.getMessage('MOBILE_EDITABLE_TEXT_BLOCK_PLACEHOLDER')]
		 * @param {Function} [props.onSave] - callback with newValue onSave(value, files=[])
		 * @param {Function} [props.onBeforeSave]
		 * @param {Object} [props.externalStyles]
		 * @param {string} [props.testId]
		 * @param {boolean} [props.showEditIcon=true]
		 * @param {string} [props.editIconTestId='TextEditorEditIcon']
		 * @param {Object} [props.textProps={}] - props for CollapsibleText
		 * @param {Object} [props.editorProps={}]
		 * @param {string} [props.editorProps.title]
		 * @param {string} [props.editorProps.placeholder]
		 * @param {string} [props.editorProps.required]
		 * @param {string} [props.editorProps.readOnly]
		 * @param {object} [props.editorProps.textAreaStyle]
		 * @param {boolean} [props.editorProps.useBBCodeEditor]
		 * @param {Object} [props.editorProps.bbCodeEditorParams]
		 * @param {Function} [props.editorProps.onLinkClick]
		 * @param {PageManager} [props.editorProps.parentWidget]
		 */
		constructor(props)
		{
			super(props);

			this.setTextEditorLayout(null);
		}

		render()
		{
			const { value, externalStyles = {}, testId } = this.props;

			return View(
				{
					testId,
					style: {
						paddingBottom: 15,
						paddingTop: value.length > 0 ? 14 : 30,
						paddingLeft: 16,
						paddingRight: 47,
						borderWidth: 1,
						borderColor: AppTheme.colors.bgSeparatorPrimary,
						borderRadius: 12,
						...externalStyles,
					},
				},
				this.renderEditIcon(),
				this.renderText(),
			);
		}

		renderText()
		{
			const { textProps, editorProps, value, placeholder } = this.props;
			const { bbCodeMode = false } = textProps;
			const { useBBCodeEditor = false, bbCodeEditorParams = {} } = editorProps;

			const params = {
				value: value || placeholder,
				...textProps,
				useBBCodeEditor,
				onClick: () => this.openEditor(),
				onLongClick: () => this.openEditor(),
			};

			if (useBBCodeEditor)
			{
				if (!bbCodeMode)
				{
					const plainTextFormatter = new PlainTextFormatter();
					const plainAst = plainTextFormatter.format({
						source: params.value,
						data: {
							files: bbCodeEditorParams.fileField?.value ?? [],
						},
					});

					params.value = plainAst.toString();
				}

				params.onLinkClick = () => this.openEditor();
			}

			return new CollapsibleText(params);
		}

		renderEditIcon()
		{
			const { showEditIcon, value, externalStyles = {}, editIconTestId } = this.props;

			if (!showEditIcon || value.trim() === '')
			{
				return null;
			}

			return View(
				{
					style: {
						position: 'absolute',
						right: 0,
						top: 0,
						paddingHorizontal: 11,
						paddingTop: externalStyles.paddingTop ?? 14,
					},
					testId: editIconTestId,
					onClick: () => this.openEditor(),
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
			const { value, editorProps } = this.props;
			const {
				title,
				placeholder,
				required,
				readOnly,
				textAreaStyle,
				useBBCodeEditor,
				bbCodeEditorParams = {},
				parentWidget,
			} = editorProps;

			if (useBBCodeEditor)
			{
				const bbCodeReadOnly = readOnly ?? bbCodeEditorParams.readOnly;
				const editorParams = {
					...bbCodeEditorParams,
					title,
					value,
					textInput: {
						...bbCodeEditorParams.textInput,
						placeholder,
					},
					readOnly: (!bbCodeReadOnly && isOffline() ? true : bbCodeReadOnly),
					onSave: ({ bbcode, files }) => this.onSave(bbcode, files),
				};

				BBCodeTextEditor.edit(editorParams)
					.then((layout) => this.setTextEditorLayout(layout))
					.catch(console.error)
				;

				return;
			}

			if (readOnly)
			{
				return;
			}

			TextEditor.open({
				title,
				placeholder,
				required,
				textAreaStyle,
				parentWidget,
				text: value,
				onSave: (text) => this.onSave(text),
				onBeforeSave: (editor) => this.onBeforeSave(editor),
				onLinkClick: ({ url }) => this.onEditorLinkClick(url),
			})
				.then(({ layout }) => this.setTextEditorLayout(layout))
				.catch((error) => console.error(error))
			;
		}

		onSave(value, files = [])
		{
			this.setTextEditorLayout(null);
			this.props.onSave?.(value.trim(), files);
		}

		async onBeforeSave(editor)
		{
			return this.props.onBeforeSave?.(editor) ?? null;
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

		setTextEditorLayout(layout)
		{
			this.textEditorLayout = layout;
		}
	}

	EditableTextBlock.defaultProps = {
		value: '',
		placeholder: Loc.getMessage('MOBILE_EDITABLE_TEXT_BLOCK_PLACEHOLDER'),
		showEditIcon: true,
		editIconTestId: 'TextEditorEditIcon',
		textProps: {},
		editorProps: {},
	};

	EditableTextBlock.propTypes = {
		value: PropTypes.string,
		placeholder: PropTypes.string,
		onSave: PropTypes.func,
		onBeforeSave: PropTypes.func,
		externalStyles: PropTypes.object,
		testId: PropTypes.string,
		showEditIcon: PropTypes.bool,
		editIconTestId: PropTypes.string,
		textProps: PropTypes.object,
		editorProps: PropTypes.shape({
			title: PropTypes.string,
			placeholder: PropTypes.string,
			required: PropTypes.bool,
			readOnly: PropTypes.bool,
			textAreaStyle: PropTypes.object,
			useBBCodeEditor: PropTypes.bool,
			bbCodeEditorParams: PropTypes.object,
			onLinkClick: PropTypes.func,
			parentWidget: PropTypes.object,
		}),
	};

	module.exports = {
		EditableTextBlock,
	};
});
