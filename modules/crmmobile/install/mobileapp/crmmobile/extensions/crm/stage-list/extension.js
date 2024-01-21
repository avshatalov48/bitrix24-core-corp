/**
 * @module crm/stage-list
 */
jn.define('crm/stage-list', (require, exports, module) => {
	const { StageList } = require('layout/ui/stage-list');

	const {
		CrmStageListItem,
		MIN_STAGE_HEIGHT,
		FIRST_TUNNEL_ADDITIONAL_HEIGHT,
		TUNNEL_HEIGHT,
	} = require('crm/stage-list/item');

	/**
	 * @class CrmStageList
	 */
	class CrmStageList extends StageList
	{
		get isNewLead()
		{
			return BX.prop.getBoolean(this.props, 'isNewLead', false);
		}

		get entityTypeId()
		{
			return BX.prop.getNumber(this.props, 'entityTypeId', null);
		}

		get tunnels()
		{
			return BX.prop.getArray(this.props, 'tunnels', []);
		}

		initStageProps(props)
		{
			super.initStageProps(props);

			this.showTunnels = BX.prop.get(this.stageParams, 'showTunnels', false);
		}

		getStageListTitle()
		{
			if (this.title)
			{
				return this.title;
			}

			if (this.showTunnels)
			{
				return BX.message('CRM_STAGE_LIST_TITLE');
			}

			return BX.message('STAGE_LIST_DEFAULT_TITLE');
		}

		calculateHeight(stages)
		{
			const stagesHeight = stages.length * MIN_STAGE_HEIGHT + 5;
			if (!this.showTunnels)
			{
				return stagesHeight;
			}

			const tunnelsHeight = this.tunnels.length > 0
				? this.tunnels.length * TUNNEL_HEIGHT + FIRST_TUNNEL_ADDITIONAL_HEIGHT
				: 0;

			return stagesHeight + tunnelsHeight;
		}

		renderStageListItem(stage)
		{
			const {
				canMoveStages,
				enableStageSelect,
			} = this.props;

			const active = this.getActiveStageId() === stage.id;

			return View(
				{},
				CrmStageListItem({
					...stage,
					readOnly: this.readOnly,
					onSelectedStage: this.onSelectedStage,
					canMoveStages,
					showTunnels: this.showTunnels,
					showTotal: this.showTotal,
					showCount: this.showCount,
					showCounters: this.showCounters,
					showAllStagesItem: this.showAllStagesItem,
					onOpenStageDetail: this.onOpenStageDetailHandler,
					enableStageSelect,
					active,
					isNewLead: this.isNewLead,
					disabledStageIds: this.disabledStageIds,
					entityTypeId: this.entityTypeId,
				}),
			);
		}

		prepareStagesData(stages)
		{
			return stages
				.reduce((acc, stageId, index) => {
					const tunnelCount = this.tunnels.filter((tunnelId) => {
						const srcStage = parseInt(tunnelId.split('_')[1], 10);

						return srcStage === stageId;
					}).length;

					return [
						...acc,
						{
							id: stageId,
							type: `${tunnelCount} ${this.showContentBorder(index, stages.length - 1) ? 'with border' : 'without border'}`,
							key: String(stageId),
							index: index + 1,
							showContentBorder: this.showContentBorder(index, stages.length - 1),
						},
					];
				}, []);
		}
	}

	module.exports = {
		CrmStageList,
	};
});
