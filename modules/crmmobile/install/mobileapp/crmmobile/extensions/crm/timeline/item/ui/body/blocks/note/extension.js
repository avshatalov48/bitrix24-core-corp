/**
 * @module crm/timeline/item/ui/body/blocks/note
 */
jn.define('crm/timeline/item/ui/body/blocks/note', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { TimelineItemUserAvatar } = require('crm/timeline/item/ui/user-avatar');
	const { TimelineTextEditor } = require('crm/timeline/ui/text-editor');
	const { transparent } = require('utils/color');
	const { Loc } = require('loc');
	const { Alert, ButtonType } = require('alert');

	const MAX_NUMBER_OF_LINES = 10000;

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
						color: transparent('#000000', 0.1),
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
								backgroundColor: '#fef3b8',
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
									maxHeight: this.state.expanded ? null : 200,
								},
								onClick: () => this.toggleExpanded(),
								onLongClick: () => this.openEditor(),
							},
							Text({
								text: this.state.text,
								ellipsize: 'end',
								numberOfLines: this.state.expanded ? MAX_NUMBER_OF_LINES : 10,
								style: {
									fontSize: 14,
									fontWeight: '400',
									color: '#333333',
								},
							}),
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
						svg: {
							content: '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.32707 0.776367L11.24 2.70943L3.75675 10.1725L1.84382 8.23948L9.32707 0.776367ZM0.769358 11.0047C0.751269 11.0732 0.77065 11.1455 0.819749 11.1959C0.870141 11.2463 0.942497 11.2657 1.01098 11.2463L3.14937 10.6702L1.34563 8.86699L0.769358 11.0047Z" fill="black" fill-opacity="0.2"/></svg>',
						},
						style: {
							width: 12,
							height: 12,
						},
					}),
				),
			);
		}

		openEditor()
		{
			TimelineTextEditor.open({
				title: Loc.getMessage('M_CRM_TIMELINE_BLOCK_EDITABLE_NOTE_TITLE'),
				text: this.state.text,
				required: !this.initiallyFilled,
				placeholder: Loc.getMessage('M_CRM_TIMELINE_BLOCK_EDITABLE_NOTE_PLACEHOLDER'),
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
			this.setState({ expanded: !this.state.expanded });
		}
	}

	module.exports = { TimelineItemBodyNoteBlock };
});
