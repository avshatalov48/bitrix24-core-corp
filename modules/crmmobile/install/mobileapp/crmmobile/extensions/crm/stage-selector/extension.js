/**
 * @module crm/stage-selector
 */
jn.define('crm/stage-selector', (require, exports, module) => {
	const { StageSelectorField } = require('layout/ui/fields/stage-selector');
	const { TypeId } = require('crm/type');
	const { actionCheckChangeStage } = require('crm/entity-actions/check-change-stage');
	const { getEntityMessage } = require('crm/loc');
	const { connect } = require('statemanager/redux/connect');
	const { CrmStageSelectorItem } = require('crm/stage-selector/item');
	const { isOnline } = require('device/connection');
	const { showOfflineToast } = require('toast');
	const { AnalyticsEvent } = require('analytics');

	const {
		selectStagesIdsBySemantics,
		getCrmKanbanUniqId,
		fetchCrmKanban,
	} = require('crm/statemanager/redux/slices/kanban-settings');
	const {
		selectStatusIdById,
		selectById: selectStageById,
	} = require('crm/statemanager/redux/slices/stage-settings');

	/**
	 * @class CrmStageSelector
	 * @typedef {LayoutComponent<CrmStageSelectorProps, CrmStageSelectorState>} CrmStageSelector
	 */
	class CrmStageSelector extends StageSelectorField
	{
		get entityTypeId()
		{
			return BX.prop.getNumber(this.props, 'entityTypeId', 0);
		}

		get isNewEntity()
		{
			return BX.prop.getBoolean(this.props, 'isNewEntity', false);
		}

		get entityId()
		{
			return BX.prop.getNumber(this.getConfig(), 'entityId', null);
		}

		get categoryId()
		{
			return BX.prop.getNumber(this.props, 'categoryId', null);
		}

		get activeStatusId()
		{
			return BX.prop.getString(this.props, 'activeStatusId', null);
		}

		fetchCrmKanban(props)
		{
			const {
				processStages,
			} = props.stageIdsBySemantics;

			if (processStages.length === 0)
			{
				this.props.fetchCrmKanban({
					entityTypeId: props.entityTypeId,
					categoryId: props.categoryId,
				});
			}
		}

		componentDidMount()
		{
			this.fetchCrmKanban(this.props);
		}

		componentWillReceiveProps(props)
		{
			this.fetchCrmKanban(props);

			super.componentWillReceiveProps(props);
		}

		static isNewLead(entityTypeId, isNewEntity)
		{
			return isNewEntity && TypeId.Lead === entityTypeId;
		}

		getAnalytics()
		{
			return new AnalyticsEvent(BX.componentParameters.get('analytics', {}))
				.setElement('stage_selector');
		}

		onBeforeHandleChange(params)
		{
			if (!isOnline())
			{
				showOfflineToast();

				return Promise.reject();
			}

			const { category } = this.state;
			const { activeStageId, selectedStageId, uid, selectedStatusId } = params;

			return actionCheckChangeStage({
				category,
				entityId: this.entityId,
				entityTypeId: this.entityTypeId,
				activeStageId,
				selectedStageId,
				uid,
				isSelectedStageFinalConverted: this.isFinalConvertedStage(selectedStageId, selectedStatusId),
				isActiveStageFinalConverted: this.isFinalConvertedStage(activeStageId, this.activeStatusId),
				analytics: this.getAnalytics(),
			});
		}

		isFinalConvertedStage(id, statusId)
		{
			return this.successStages.includes(id) && statusId === 'CONVERTED';
		}

		forceUpdate(params)
		{
			if (this.props.forceUpdate)
			{
				this.props.forceUpdate({
					...params,
					data: {
						itemId: this.entityId,
					},
				});
			}
		}

		notifyAboutReadOnlyStatus()
		{
			if (this.isReadonlyNotificationEnabled())
			{
				Notify.showUniqueMessage(
					getEntityMessage('CRM_STAGE_SELECTOR_NOTIFY_READONLY_TEXT2', this.props.entityTypeId),
					BX.message('CRM_STAGE_SELECTOR_NOTIFY_READONLY_TITLE'),
					{ time: 4 },
				);
			}
		}

		async openStageList(activeStageId)
		{
			const { CrmStageListView } = await requireLazy('crm:stage-list-view');

			const props = {
				entityTypeId: this.entityTypeId,
				categoryId: this.categoryId,
				kanbanSettingsId: getCrmKanbanUniqId(this.entityTypeId, this.categoryId),
				activeStageId,
				data: {
					itemId: this.entityId,
				},
				isNewEntity: this.getConfig().isNewEntity,
				readOnly: true,
				canMoveStages: false,
				enableStageSelect: true,
				onStageSelect: (id, statusId) => this.changeActiveStageId(id, statusId),
				uid: this.uid,
			};

			return CrmStageListView.open(props, this.getParentWidget());
		}

		renderStages(currentStages, activeIndex)
		{
			return this.currentStages.map((stageId, index) => {
				return CrmStageSelectorItem({
					stageId,
					index,
					activeIndex,
					showMenu: !this.isReadOnly() && activeIndex === index,
					onStageClick: this.onStageClickHandler,
					onStageLongClick: this.onStageLongClickHandler,
					onChange: this.onChangeStageHandler,
				});
			});
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const kanbanId = getCrmKanbanUniqId(ownProps.entityTypeId, ownProps.categoryId);
		const stageIdsBySemantics = selectStagesIdsBySemantics(state, kanbanId);
		let value = ownProps.value;
		if (!value && ownProps.columnCode)
		{
			const stageIds = [
				...stageIdsBySemantics.processStages,
				...stageIdsBySemantics.successStages,
				...stageIdsBySemantics.failedStages,
			];
			const activeStage = stageIds
				.map((id) => selectStageById(state, id))
				.find((stage) => stage.statusId === ownProps.columnCode);

			value = activeStage?.id;
		}

		return {
			value,
			stageIdsBySemantics,
			activeStatusId: selectStatusIdById(state, ownProps.value),
		};
	};

	const mapDispatchToProps = ({
		fetchCrmKanban,
	});

	module.exports = {
		CrmStageSelectorType: 'crm-stage',
		CrmStageSelector: connect(mapStateToProps, mapDispatchToProps)(CrmStageSelector),
	};
});
