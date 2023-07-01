/**
 * @module crm/timeline/item/ui/body/blocks/editable-description
 */
jn.define('crm/timeline/item/ui/body/blocks/editable-description', (require, exports, module) => {
	const { Loc } = require('loc');
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { TimelineTextEditor } = require('crm/timeline/ui/text-editor');
	const { transparent } = require('utils/color');

	const MAX_NUMBER_OF_LINES = 10000;

	/**
	 * @class TimelineItemBodyEditableDescriptionBlock
	 */
	class TimelineItemBodyEditableDescriptionBlock extends TimelineItemBodyBlock
	{
		constructor(...props)
		{
			super(...props);

			this.state = {
				text: this.props.text,
				expanded: false,
				editable: this.props.hasOwnProperty('editable') && this.props.editable,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state.text = props.text;
		}

		render()
		{
			return View(
				{},
				View(
					{
						style: {
							paddingBottom: 15,
							paddingTop: this.state.text.length > 0 ? 14 : 30,
							paddingLeft: 16,
							paddingRight: 30,
							borderWidth: 1,
							borderColor: transparent('#000000', 0.1),
							borderRadius: 12,
							maxHeight: this.state.expanded ? null : 200,
						},
						onClick: () => this.toggleExpanded(),
						onLongClick: () => this.openEditor(),
					},
					this.renderEditIcon(),
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
			);
		}

		renderEditIcon()
		{
			if (this.isReadonly || !this.state.editable)
			{
				return null;
			}

			return View(
				{
					onClick: () => this.openEditor(),
					style: {
						position: 'absolute',
						right: 0,
						top: 3,
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
			);
		}

		openEditor()
		{
			if (this.isReadonly || !this.state.editable)
			{
				return;
			}

			TimelineTextEditor.open({
				title: Loc.getMessage('M_CRM_TIMELINE_BLOCK_EDITABLE_TEXT_TITLE2'),
				text: this.state.text,
				required: true,
				placeholder: Loc.getMessage('CRM_TIMELINE_SCHEDULED_TITLE2'),
				onSave: (text) => this.onSave(text),
			});
		}

		toggleExpanded()
		{
			this.setState({ expanded: !this.state.expanded });
		}

		onSave(text)
		{
			text = text.trim();

			this.setState({ text }, () => {
				if (this.props.saveAction)
				{
					const { actionParams } = this.props.saveAction;
					actionParams.value = this.state.text;
					this.emitAction({
						...this.props.saveAction,
						actionParams,
					});
				}
			});
		}
	}

	module.exports = { TimelineItemBodyEditableDescriptionBlock };
});
