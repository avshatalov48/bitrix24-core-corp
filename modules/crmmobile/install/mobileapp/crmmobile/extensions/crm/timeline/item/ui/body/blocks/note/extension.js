/**
 * @module crm/timeline/item/ui/body/blocks/note
 */
jn.define('crm/timeline/item/ui/body/blocks/note', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { TimelineItemUserAvatar } = require('crm/timeline/item/ui/user-avatar');
	const { TimelineTextEditor } = require('crm/timeline/ui/text-editor');
	const { AppTheme } = require('apptheme/extended');
	const { Loc } = require('loc');
	const { Alert, ButtonType } = require('alert');
	const { largePen } = require('assets/common');

	/**
	 * @class TimelineItemBodyNoteBlock
	 */
	class TimelineItemBodyNoteBlock extends TimelineItemBodyBlock
	{
		constructor(...props)
		{
			super(...props);

			const text = typeof this.props.text === 'string' ? this.props.text.trim() : '';

			this.initiallyFilled = text.length > 0;

			this.state = {
				text,
				expanded: false,
			};

			this.onReceiveOpenEditorRequest = this.onReceiveOpenEditorRequest.bind(this);
		}

		componentWillReceiveProps(props)
		{
			const text = typeof props.text === 'string' ? props.text.trim() : '';
			this.initiallyFilled = text.length > 0;
			this.state.text = text;
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
						display: this.state.text.length > 0 ? 'flex' : 'none',
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
						View(
							{
								style: {
									flexGrow: 1,
									paddingVertical: 12,
									flex: 1,
								},
								onClick: () => this.toggleExpanded(),
								onLongClick: () => this.openEditor(),
							},
							this.renderText(),
						),
						this.renderEditIcon(),
					),
				),
			);
		}

		renderEditIcon()
		{
			if (this.isReadonly)
			{
				return null;
			}

			return View(
				{},
				View(
					{
						testId: 'TimelineItemBodyNoteEditIcon',
						onClick: () => this.openEditor(),
						style: {
							paddingHorizontal: 16,
							paddingVertical: 14,
						},
					},
					Image({
						tintColor: AppTheme.colors.base3,
						svg: {
							content: largePen(),
						},
						style: {
							width: 18,
							height: 18,
						},
					}),
				),
			);
		}

		renderText()
		{
			return BBCodeText({
				testId: 'TimelineItemBodyNoteText',
				value: this.prepareTextToRender(this.state.text),
				style: {
					fontSize: 14,
					fontWeight: '400',
					color: AppTheme.colors.base1,
				},
			});
		}

		prepareTextToRender(text)
		{
			const maxLettersCount = this.getMaxLettersCount();
			if (this.state.expanded || text.length <= maxLettersCount)
			{
				return text;
			}

			return `${text.slice(0, maxLettersCount).trim()}... [color=${AppTheme.colors.base3}]${Loc.getMessage('M_CRM_TIMELINE_VIEW_MORE')}[/color]`;
		}

		openEditor()
		{
			TimelineTextEditor.open({
				title: Loc.getMessage('M_CRM_TIMELINE_BLOCK_EDITABLE_NOTE_TITLE_MSGVER_1'),
				text: this.state.text,
				required: !this.initiallyFilled,
				placeholder: Loc.getMessage('M_CRM_TIMELINE_BLOCK_EDITABLE_NOTE_PLACEHOLDER_MSGVER_1'),
				onBeforeSave: (editor) => new Promise((resolve, reject) => {
					const text = editor.value.trim();
					if (text.length === 0 && this.initiallyFilled)
					{
						return Alert.confirm(
							'',
							this.props.deleteConfirmationText,
							[
								{
									type: ButtonType.CANCEL,
									onPress: reject,
								},
								{
									text: Loc.getMessage('CRM_TIMELINE_CONFIRM_REMOVE'),
									type: ButtonType.DESTRUCTIVE,
									onPress: resolve,
								},
							],
						);
					}

					resolve();
				}),
				onSave: (text) => this.onSave(text),
			});
		}

		onSave(text)
		{
			text = text.trim();
			this.state.text = text;

			if (text.length > 0)
			{
				this.emitSaveAction();
			}
			else if (this.initiallyFilled)
			{
				this.emitDeleteAction();
			}
		}

		emitSaveAction()
		{
			if (this.props.saveNoteAction)
			{
				const { actionParams } = this.props.saveNoteAction;
				actionParams.text = this.state.text;
				this.emitAction({
					...this.props.saveNoteAction,
					actionParams,
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

		toggleExpanded()
		{
			if (this.state.text.length > this.getMaxLettersCount())
			{
				this.setState({ expanded: !this.state.expanded });
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
	}

	module.exports = { TimelineItemBodyNoteBlock };
});
