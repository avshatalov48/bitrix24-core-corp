/**
 * @module layout/ui/reaction-list/tab-panel/src/tab-panel
 */
jn.define('layout/ui/reaction-list/tab-panel/src/tab-panel', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color, Indent } = require('tokens');
	const { ChipInnerTab } = require('ui-system/blocks/chips/chip-inner-tab');
	const { ScrollView } = require('layout/ui/scroll-view');
	const { ReactionIcon } = require('ui-system/blocks/reaction/icon');

	class ReactionTabPanel extends LayoutComponent
	{
		static constants = Object.freeze({
			ALL: 'all',
		});

		constructor(props)
		{
			super(props);

			this.state = {
				selectedReactionTab: ReactionTabPanel.constants.ALL,
			};

			this.parentWidget = props.parentWidget ?? layout;
		}

		get #reactions()
		{
			return this.props.reactions || [];
		}

		get selectedReactions()
		{
			return this.#reactions.filter((reaction) => reaction.userIds.length > 0);
		}

		#isSelected(activeTab, reactionId)
		{
			return activeTab === reactionId;
		}

		getTotalCounter()
		{
			return this.selectedReactions.reduce((total, reaction) => {
				return total + reaction.userIds.length;
			}, 0);
		}

		handleSelectedChipInnerTab(activeTab)
		{
			this.setState({ selectedReactionTab: activeTab }, () => {
				BX.postComponentEvent('ReactionTabsEvent', [
					activeTab,
				]);
			});
		}

		renderChipInnerTab(reactionId, activeTab, userCount = 0)
		{
			const icon = reactionId === ReactionTabPanel.constants.ALL
				? null
				: ReactionIcon.getIconByReactionId(reactionId);
			const totalCounter = this.getTotalCounter();
			const text = reactionId === ReactionTabPanel.constants.ALL
				? Loc.getMessage('REACTION_LIST_WIDGET_ALL_REACTIONS').replace('#QUANTITY#', totalCounter)
				: String(userCount);
			const testId = reactionId === ReactionTabPanel.constants.ALL
				? ReactionTabPanel.constants.ALL
				: reactionId;

			return ChipInnerTab({
				testId: this.#isSelected(activeTab, reactionId) ? `reaction-tab-selected-${testId}` : `reaction-tab-${testId}`,
				icon,
				text,
				selected: this.#isSelected(activeTab, reactionId),
				style: {
					marginRight: Indent.L.toNumber(),
				},
				textStyles: {
					fontSize: 15,
				},
				accentBorderColor: Color.accentSoftBorderBlue,
				accentTextColor: Color.accentMainPrimary,
				onClick: () => this.handleSelectedChipInnerTab(reactionId),
			});
		}

		render()
		{
			const { selectedReactionTab } = this.state;
			const activeTab = selectedReactionTab || ReactionTabPanel.constants.ALL;

			return ScrollView(
				{
					horizontal: true,
					showsHorizontalScrollIndicator: false,
					style: {
						height: 40,
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'flex-start',
						width: '100%',
						marginBottom: Indent.M.toNumber(),
						marginTop: Indent.XS.toNumber(),
					},
				},
				View(
					{
						style: {
							display: 'flex',
							flexDirection: 'row',
							marginLeft: Indent.XL3.toNumber(),
						},
					},
					this.renderChipInnerTab(ReactionTabPanel.constants.ALL, activeTab),
					...this.selectedReactions.map((element) => this.renderChipInnerTab(
						element.reactionId,
						selectedReactionTab,
						element.userIds.length,
					)),
				),
			);
		}
	}

	module.exports = { ReactionTabPanel };
});
