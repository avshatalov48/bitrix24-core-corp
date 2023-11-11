/**
 * @module crm/timeline/item/ui/body/blocks/editable-description
 */
jn.define('crm/timeline/item/ui/body/blocks/editable-description', (require, exports, module) => {
	const { Loc } = require('loc');
	const { TimelineItemBodyBaseEditableBlock } = require('crm/timeline/item/ui/body/blocks/base-editable-block');
	const { clone } = require('utils/object');

	/**
	 * @class TimelineItemBodyEditableDescriptionBlock
	 */
	class TimelineItemBodyEditableDescriptionBlock extends TimelineItemBodyBaseEditableBlock
	{
		getPreparedActionParams()
		{
			const actionParams = clone(this.props.saveAction.actionParams);
			actionParams.value = this.state.text;

			return actionParams;
		}

		getEditorTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_BLOCK_EDITABLE_TEXT_TITLE2');
		}

		getEditorPlaceholder()
		{
			return Loc.getMessage('M_CRM_TIMELINE_BLOCK_EDITABLE_TEXT_PLACEHOLDER');
		}
	}

	module.exports = { TimelineItemBodyEditableDescriptionBlock };
});
