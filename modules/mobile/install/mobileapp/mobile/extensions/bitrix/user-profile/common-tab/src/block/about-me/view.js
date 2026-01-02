/**
 * @module user-profile/common-tab/src/block/about-me/view
 */
jn.define('user-profile/common-tab/src/block/about-me/view', (require, exports, module) => {
	const { createTestIdGenerator } = require('utils/test');
	const { Loc } = require('loc');
	const { Color, Indent } = require('tokens');
	const { CollapsibleText } = require('layout/ui/collapsible-text');
	const { EditableTextBlock } = require('layout/ui/editable-text-block');
	const { TextEditor } = require('text-editor');
	const { PlainTextFormatter } = require('bbcode/formatter/plain-text-formatter');
	const { PropTypes } = require('utils/validation');

	class AboutMe extends LayoutComponent
	{
		/**
		 * @param {Object} props
		 * @param {string} props.testId
		 * @param {Object} [props.aboutMe={ text: '', files: [] }]
		 * @param {boolean} [props.isEditMode=false]
		 * @param {Function} [props.onChange=null]
		 * @param {PageManager} [props.parentWidget=PageManager]
		 */
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				prefix: 'about-me',
				context: this,
			});

			this.#updateState(props);
		}

		componentWillReceiveProps(nextProps)
		{
			this.#updateState(nextProps);
		}

		#updateState(newState)
		{
			const { aboutMe } = newState;

			this.state = {
				text: aboutMe.text,
				files: aboutMe.files,
			};
		}

		render()
		{
			if (this.props.isEditMode)
			{
				return this.#renderEditableContent();
			}

			return this.#renderReadOnlyContent();
		}

		#renderReadOnlyContent()
		{
			const { parentWidget } = this.props;
			const { text: rawValue, files = [] } = this.state;

			const plainTextFormatter = new PlainTextFormatter();
			const plainAst = plainTextFormatter.format({
				source: rawValue,
				data: {
					files,
				},
			});
			const formattedValue = plainAst.toString();

			return new CollapsibleText({
				value: formattedValue,
				bbCodeMode: false,
				useBBCodeEditor: true,
				testId: this.getTestId('content'),
				onClick: () => this.#openTextEditor(rawValue, files, parentWidget),
				onLongClick: () => this.#openTextEditor(rawValue, files, parentWidget),
			});
		}

		#openTextEditor(rawValue, files, parentWidget)
		{
			void TextEditor.edit({
				parentWidget,
				title: Loc.getMessage('M_PROFILE_ABOUT_ME_TITLE'),
				value: rawValue,
				readOnly: true,
				fileField: {
					value: files,
				},
			});
		}

		#renderEditableContent()
		{
			const { text: rawValue, files = [] } = this.state;
			const isEmpty = rawValue.trim() === '';

			return View(
				{
					style: {
						marginTop: Indent.M.toNumber(),
						marginBottom: Indent.XL2.toNumber(),
					},
				},
				new EditableTextBlock({
					value: rawValue,
					textProps: {
						style: {
							color: (isEmpty ? Color.base4 : Color.base1).toHex(),
						},
						bbCodeMode: false,
						testId: this.getTestId('content'),
					},
					editorProps: {
						title: Loc.getMessage('M_PROFILE_ABOUT_ME_TITLE'),
						placeholder: Loc.getMessage('M_PROFILE_ABOUT_ME_PLACEHOLDER'),
						readOnly: false,
						useBBCodeEditor: true,
						bbCodeEditorParams: {
							title: Loc.getMessage('M_PROFILE_ABOUT_ME_TITLE'),
							value: rawValue,
							readOnly: false,
							allowFiles: true,
							fileField: {
								value: files,
							},
							autoFocus: isEmpty,
							closeOnSave: true,
							parentWidget: this.props.parentWidget,
						},
					},
					externalStyles: {
						paddingTop: Indent.XL2.toNumber(),
						marginTop: Indent.M.toNumber(),
						marginBottom: Indent.S.toNumber(),
					},
					showEditIcon: false,
					onSave: (newValue, newFiles) => this.#onSave(newValue, newFiles),
				}),
			);
		}

		#onSave(text, files)
		{
			this.setState({ text, files });
			this.props.onChange?.('aboutMe', { text, files });
		}
	}

	AboutMe.defaultProps = {
		testId: '',
		aboutMe: {
			text: '',
			files: [],
		},
		isEditMode: false,
		onChange: null,
		parentWidget: PageManager,
	};

	AboutMe.propTypes = {
		testId: PropTypes.string.isRequired,
		aboutMe: PropTypes.shape({
			text: PropTypes.string,
			files: PropTypes.arrayOf(PropTypes.object),
		}),
		isEditMode: PropTypes.bool,
		onChange: PropTypes.func,
		parentWidget: PropTypes.object,
	};

	module.exports = {
		AboutMe: (props) => new AboutMe(props),
	};
});
