/**
 * @module crm/stage-list-view
 */
jn.define('crm/stage-list-view', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Alert } = require('alert');
	const { Loc } = require('loc');
	const { StageListView, StageSelectActions } = require('layout/ui/stage-list-view');
	const { stringify } = require('utils/string');
	const { TypeId } = require('crm/type');
	const { getEntityMessage } = require('crm/loc');
	const { CrmStageList } = require('crm/stage-list');
	const { NotifyManager } = require('notify-manager');

	const { connect } = require('statemanager/redux/connect');
	const {
		selectById,
		selectStagesIdsBySemantics,
		fetchCrmKanban,
	} = require('crm/statemanager/redux/slices/kanban-settings');

	/**
	 * @class CrmStageSelector
	 */
	class CrmStageListView extends StageListView
	{
		static open(params, parentWidget)
		{
			return new Promise((resolve, reject) => {
				parentWidget
					.openWidget('layout', this.getWidgetParams())
					.then((layout) => {
						layout.enableNavigationBarBorder(false);
						layout.showComponent(connect(mapStateToProps, mapDispatchToProps)(this)({ layout, ...params }));
						resolve(layout);
					})
					.catch((error) => {
						console.error(error);
					});
			});
		}

		get entityTypeId()
		{
			return BX.prop.getNumber(this.props, 'entityTypeId', null);
		}

		get categoriesEnabled()
		{
			return BX.prop.getBoolean(this.props, 'categoriesEnabled', false);
		}

		get isNewEntity()
		{
			return BX.prop.getBoolean(this.props, 'isNewEntity', false);
		}

		get tunnels()
		{
			return BX.prop.getArray(this.props, 'tunnels', []);
		}

		get categoryId()
		{
			return BX.prop.getInteger(this.props, 'categoryId', null);
		}

		get activeStageId()
		{
			return BX.prop.getInteger(this.props, 'activeStageId', null);
		}

		getDisabledStageIdsByCategory()
		{
			return BX.prop.getArray(this.props, 'disabledStageIds', []);
		}

		componentDidMount()
		{
			if (this.stageIdsBySemantics.processStages.length === 0 && this.categoryId)
			{
				this.navigationLoader.setLoading(true);
				this.props.fetchCrmKanban({
					entityTypeId: this.entityTypeId,
					categoryId: this.categoryId,
				}).then(() => {
					this.navigationLoader.setLoading(false);
				}).catch(() => {
					this.navigationLoader.setLoading(false);
					NotifyManager.showDefaultError();
				});
			}

			super.componentDidMount();
		}

		bindEvents()
		{
			BX.addCustomEvent('Crm.CategoryDetail::onDeleteCategory', (kanbanSettingsId) => {
				if (this.kanbanSettingsId === kanbanSettingsId)
				{
					this.closeLayout();
				}
			});
		}

		getTitleForNavigation(title)
		{
			if (!this.categoriesEnabled)
			{
				return this.kanbanSettingsName;
			}

			const name = stringify(title).trim();

			return (
				name === ''
					? BX.message('CRM_STAGE_LIST_VIEW_FUNNEL_EMPTY_TITLE2')
					: BX.message('CRM_STAGE_LIST_VIEW_FUNNEL_TITLE2').replace('#CATEGORY_NAME#', name)
			);
		}

		onChangeEntityStage(stage, statusId)
		{
			if (this.isNewLead() && this.getSuccessStagesIds().includes(stage))
			{
				Alert.confirm(
					Loc.getMessage('CRM_STAGE_LIST_VIEW_CHANGE_NEW_LEAD_SUCCESS_STAGE_NOTIFY_TITLE'),
					Loc.getMessage('CRM_STAGE_LIST_VIEW_CHANGE_NEW_LEAD_SUCCESS_STAGE_NOTIFY'),
				);

				return;
			}

			super.onChangeEntityStage(stage, statusId);
		}

		isNewLead()
		{
			return this.isNewEntity && this.entityTypeId === TypeId.Lead;
		}

		getSuccessStagesIds()
		{
			const { successStages } = this.stageIdsBySemantics;

			if (!Array.isArray(successStages))
			{
				return [];
			}

			return successStages;
		}

		onCreateTunnel(stage)
		{
			this.selectedStage = stage;
			this.layout.close();
		}

		onSelectTunnelDestination(stage)
		{
			this.selectedStage = stage;
			this.layout.close();
		}

		getStageListTitle()
		{
			if (
				this.selectAction === StageSelectActions.SelectTunnelDestination
				|| this.selectAction === StageSelectActions.CreateTunnel
			)
			{
				return BX.message('STAGE_LIST_VIEW_BACKDROP_TUNNEL_TITLE');
			}

			return getEntityMessage('CRM_STAGE_LIST_VIEW_TITLE', this.entityTypeId);
		}

		renderStageList()
		{
			const {
				stageParams,
				canMoveStages,
			} = this.props;

			return new CrmStageList({
				tunnels: this.tunnels,
				title: this.getStageListTitle(),
				readOnly: this.getStageReadOnly(),
				stageIdsBySemantics: this.stageIdsBySemantics,
				canMoveStages,
				stageParams,
				activeStageId: this.getActiveStageId(),
				onSelectedStage: this.onSelectedStageHandler,
				onOpenStageDetail: this.onOpenStageDetailHandler,
				enableStageSelect: this.enableStageSelect,
				disabledStageIds: this.getDisabledStageIdsByCategory(),
				isNewLead: this.isNewLead(),
				kanbanSettingsId: this.kanbanSettingsId,
			});
		}

		getActiveStageId()
		{
			const stageIdExist = this.getStages().includes(this.activeStageId);

			return stageIdExist ? this.activeStageId : null;
		}

		getStages()
		{
			const processStages = BX.prop.getArray(this.stageIdsBySemantics, 'processStages', []);
			const successStages = BX.prop.getArray(this.stageIdsBySemantics, 'successStages', []);
			const failedStages = BX.prop.getArray(this.stageIdsBySemantics, 'failedStages', []);

			return [...processStages, ...successStages, ...failedStages];
		}

		getStageReadOnly()
		{
			if (
				this.selectAction === StageSelectActions.SelectTunnelDestination
				|| this.selectAction === StageSelectActions.CreateTunnel
			)
			{
				return true;
			}

			return this.props.readOnly;
		}

		onSelectedStage(stage)
		{
			switch (this.selectAction)
			{
				case StageSelectActions.ChangeEntityStage:
					this.onChangeEntityStage(stage.id, stage.statusId);

					break;
				case StageSelectActions.CreateTunnel:
					this.onCreateTunnel(stage);

					break;
				case StageSelectActions.SelectTunnelDestination:
					this.onSelectTunnelDestination(stage);

					break;
				default:
					break;
			}
		}

		async handlerOnOpenStageDetail(stage)
		{
			const { CrmKanbanStageSettings } = await requireLazy('crm:kanban/stage-settings') || {};

			if (CrmKanbanStageSettings)
			{
				await CrmKanbanStageSettings.open(
					{
						entityTypeId: this.entityTypeId,
						stage,
					},
					this.layout,
				);
			}
		}

		async handlerCategoryEditOpen()
		{
			const { CrmKanbanSettings } = await requireLazy('crm:kanban/settings') || {};

			if (CrmKanbanSettings)
			{
				CrmKanbanSettings.open(
					{
						entityTypeId: this.entityTypeId,
						kanbanSettingsId: this.kanbanSettingsId,
					},
					this.layout,
				);
			}
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const {
			name = '',
			categoriesEnabled = false,
			editable = false,
			tunnels,
			categoryId,
		} = selectById(state, ownProps.kanbanSettingsId) || {};

		return {
			stageIdsBySemantics: selectStagesIdsBySemantics(state, ownProps.kanbanSettingsId),
			kanbanSettingsName: name,
			categoriesEnabled,
			editable,
			tunnels,
			categoryId,
		};
	};

	const mapDispatchToProps = ({
		fetchCrmKanban,
	});

	module.exports = {
		CrmStageListView,
	};
});
