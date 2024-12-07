/**
 * @module crm/timeline/item/ui/body/blocks/note
 */
jn.define('crm/timeline/item/ui/body/blocks/note', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { TimelineItemUserAvatar } = require('crm/timeline/item/ui/user-avatar');
	const { TextEditor } = require('layout/ui/text-editor');
	const { EditableTextBlock } = require('layout/ui/editable-text-block');
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { confirmDestructiveAction } = require('alert');

	/**
	 * @class TimelineItemBodyNoteBlock
	 */
	class TimelineItemBodyNoteBlock extends TimelineItemBodyBlock
	{
		constructor(...props)
		{
			super(...props);

			this.text = typeof this.props.text === 'string' ? this.props.text.trim() : '';

			this.initiallyFilled = this.text.length > 0;

			this.state = {
				expanded: false,
			};

			this.onReceiveOpenEditorRequest = this.onReceiveOpenEditorRequest.bind(this);
		}

		componentWillReceiveProps(props)
		{
			const text = typeof props.text === 'string' ? props.text.trim() : '';
			this.initiallyFilled = text.length > 0;
			this.text = text;
		}

		componentDidMount()
		{
			this.itemScopeEventBus.on('Crm.Timeline.Item.OpenNoteEditorRequest', this.onReceiveOpenEditorRequest);
		}

		componentWillUnmount()
		{
			this.itemScopeEventBus.off('Crm.Timeline.Item.OpenNoteEditorRequest', this.onReceiveOpenEditorRequest);
		}

		onReceiveOpenEditorRequest()
		{
			this.openEditor();
		}

		render()
		{
			const avatar = this.props.updatedBy || {};

			return View(
				{
					style: {
						paddingHorizontal: 1,
						display: this.text.length > 0 ? 'flex' : 'none',
					},
				},
				Shadow(
					{
						color: AppTheme.colors.shadowPrimary,
						radius: 2,
						offset: {
							y: 2,
						},
						style: {
							borderRadius: 1,
						},
					},
					View(
						{
							style: {
								backgroundColor: AppTheme.colors.accentSoftOrange1,
								flexDirection: 'row',
							},
						},
						View(
							{},
							TimelineItemUserAvatar({
								...avatar,
								testId: 'TimelineItemBodyNoteUserAvatar',
							}),
						),
						new EditableTextBlock(
							{
								value: this.text,
								onSave: (text) => this.onSave(text),
								onBeforeSave: (editor) => this.onBeforeSave(editor),
								externalStyles: {
									paddingLeft: 0,
									borderWidth: 0,
									borderRadius: 0,
									paddingTop: 14,
									flex: 1,
								},
								textProps: this.getTextParams(),
								editorProps: this.getEditorParams(),
								editIconTestId: 'TimelineItemBodyNoteEditIcon',
							},
						),
					),
				),
			);
		}

		getTextParams()
		{
			return {
				testId: 'TimelineItemBodyNoteText',
				style: {
					fontSize: 14,
					fontWeight: '400',
					color: AppTheme.colors.base1,
				},
				containerStyle: {
					flexGrow: 1,
					paddingRight: 40,
				},
				maxLettersCount: this.getMaxLettersCount(),
				maxEntersCount: this.getMaxEntersCount(),
				bbCodeMode: true,
			};
		}

		getEditorParams()
		{
			return {
				title: Loc.getMessage('M_CRM_TIMELINE_BLOCK_EDITABLE_NOTE_TITLE_MSGVER_1'),
				placeholder: Loc.getMessage('M_CRM_TIMELINE_BLOCK_EDITABLE_NOTE_PLACEHOLDER_MSGVER_1'),
				required: !this.initiallyFilled,
			};
		}

		openEditor()
		{
			TextEditor.open({
				title: Loc.getMessage('M_CRM_TIMELINE_BLOCK_EDITABLE_NOTE_TITLE_MSGVER_1'),
				text: this.text,
				required: !this.initiallyFilled,
				placeholder: Loc.getMessage('M_CRM_TIMELINE_BLOCK_EDITABLE_NOTE_PLACEHOLDER_MSGVER_1'),
				onBeforeSave: (editor) => this.onBeforeSave(editor),
				onSave: (text) => this.onSave(text),
			});
		}

		onSave(text)
		{
			this.text = text.trim();

			if (this.text.length > 0)
			{
				this.emitSaveAction();
			}
			else if (this.initiallyFilled)
			{
				this.emitDeleteAction();
			}
		}

		onBeforeSave(editor)
		{
			return new Promise((resolve, reject) => {
				const text = editor.value.trim();
				if (text.length === 0 && this.initiallyFilled)
				{
					confirmDestructiveAction({
						title: '',
						description: this.props.deleteConfirmationText,
						onDestruct: () => resolve(),
						onCancel: () => reject(),
					});
				}
				else
				{
					resolve();
				}
			});
		}

		emitSaveAction()
		{
			if (this.props.saveNoteAction)
			{
				this.setState({}, () => {
					const { actionParams } = this.props.saveNoteAction;
					actionParams.text = this.text;
					this.emitAction({
						...this.props.saveNoteAction,
						actionParams,
					});
				});
			}
		}

		emitDeleteAction()
		{
			if (this.props.deleteNoteAction)
			{
				this.emitAction(this.props.deleteNoteAction);
			}
		}

		getMaxLettersCount()
		{
			if (this.model.hasLowPriority)
			{
				return 35;
			}

			return 330;
		}

		getMaxEntersCount()
		{
			if (this.model.hasLowPriority)
			{
				return 1;
			}

			return 4;
		}
	}

	module.exports = { TimelineItemBodyNoteBlock };
});
