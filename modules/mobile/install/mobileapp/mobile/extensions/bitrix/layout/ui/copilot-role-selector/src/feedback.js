/**
 * @module layout/ui/copilot-role-selector/src/feedback
 */
jn.define('layout/ui/copilot-role-selector/src/feedback', (require, exports, module) => {
	const { Loc } = require('loc');
	const { ListFeedbackItem } = require('layout/ui/copilot-role-selector/src/views');
	const { ListItemType } = require('layout/ui/copilot-role-selector/src/types');
	const { openFeedbackForm: coreOpenFeedbackForm } = require('layout/ui/feedback-form-opener');

	const renderListFeedbackItem = () => {
		return ListFeedbackItem({
			item: getFeedBackItemData(),
			isLastItem: true,
			clickHandler: () => {
				openFeedBackForm();
			},
		});
	};

	const getFeedBackItemData = () => {
		return {
			name: Loc.getMessage('COPILOT_CONTEXT_STEPPER_FEEDBACK_ITEM_TITLE'),
			description: Loc.getMessage('COPILOT_CONTEXT_STEPPER_FEEDBACK_ITEM_DESCRIPTION'),
			type: ListItemType.FEEDBACK,
		};
	};

	const openFeedBackForm = () => {
		coreOpenFeedbackForm('copilotRoles');
	};

	module.exports = { renderListFeedbackItem, getFeedBackItemData, openFeedBackForm };
});
