/**
 * @module tasks/flow-list/simple-list/items/flow-redux/src/flow-disabled-content
 */
jn.define('tasks/flow-list/simple-list/items/flow-redux/src/flow-disabled-content', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { showToast } = require('toast');
	const { outline } = require('assets/icons');
	const { Color } = require('tokens');
	const { ChipStatusDesign } = require('ui-system/blocks/chips/chip-status');
	const { FlowContent } = require('tasks/flow-list/simple-list/items/flow-redux/src/flow-content');

	class FlowDisabledContent extends FlowContent
	{
		get shouldShowAiAdviceFooter()
		{
			return false;
		}

		get testId()
		{
			return `flow-disabled-content-${this.props.id}`;
		}

		getUserAvatarOpacity()
		{
			return 0.2;
		}

		getCreateTaskButtonDisabledProperty()
		{
			return true;
		}

		getStageHeaderColor()
		{
			return Color.base5;
		}

		getAtWorkCountCircleBackgroundColor()
		{
			return Color.base7;
		}

		getAtWorkCountCircleTextColor()
		{
			return Color.base4;
		}

		getCompletedCountCircleBackgroundColor()
		{
			return Color.base7;
		}

		getCompletedCountCircleTextColor()
		{
			return Color.base4;
		}

		getEfficiencyChipStatusDesign()
		{
			return ChipStatusDesign.NEUTRAL;
		}

		getEfficiencySvgUri()
		{
			return `${currentDomain}/bitrix/mobileapp/tasksmobile/extensions/tasks/flow-list/simple-list/items/flow-redux/images/${AppTheme.id}/disabled.png`;
		}

		getPlannedCompletionTimeText()
		{
			return Loc.getMessage('TASKSMOBILE_FLOW_CONTENT_PLANNED_COMLETION_DISABLED');
		}

		createTaskDisabledButtonClickHandler = () => {
			showToast(
				{
					message: Loc.getMessage('TASKSMOBILE_FLOW_CONTENT_DISABLED_TOAST'),
					svg: {
						content: outline.lock({
							color: Color.baseWhiteFixed.toHex(),
						}),
					},
				},
				this.props.layout,
			);
		};
	}

	module.exports = {
		FlowDisabledContent,
	};
});
