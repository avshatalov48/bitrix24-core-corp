/**
 * @bxjs_lang_path extension.php
 * @module crm/kanban/toolbar/entity-toolbar
 */
jn.define('crm/kanban/toolbar/entity-toolbar', (require, exports, module) => {
	const { KanbanToolbar } = require('layout/ui/kanban/toolbar');
	const { Loc } = require('loc');

	const { connect } = require('statemanager/redux/connect');
	const {
		getCrmKanbanUniqId,
		selectById,
	} = require('crm/statemanager/redux/slices/kanban-settings');

	const { StageDropdown } = require('crm/kanban/toolbar/stage-dropdown');
	const { StageSummary } = require('crm/kanban/toolbar/stage-summary');

	class EntityToolbar extends KanbanToolbar
	{
		constructor(props)
		{
			super(props);
			this.setDefaultStageHandler = this.setDefaultStage.bind(this);
		}

		get entityTypeId()
		{
			return BX.prop.getInteger(this.props, 'entityTypeId', null);
		}

		get filterParams()
		{
			return BX.prop.getObject(this.props, 'filterParams', {});
		}

		get categoryId()
		{
			return BX.prop.getInteger(this.filterParams, 'CATEGORY_ID', null);
		}

		get kanbanSettingsName()
		{
			return BX.prop.getString(this.props, 'kanbanSettingsName', '');
		}

		get categoriesEnabled()
		{
			return BX.prop.getBoolean(this.props, 'categoriesEnabled', false);
		}

		onToolbarClick()
		{
			void requireLazy('crm:stage-list-view').then(({ CrmStageListView }) => {
				const props = {
					entityTypeId: this.entityTypeId,
					kanbanSettingsId: getCrmKanbanUniqId(this.entityTypeId, this.categoryId),
					categoryId: this.categoryId,
					activeStageId: this.getActiveStageId(),
					readOnly: true,
					canMoveStages: false,
					enableStageSelect: true,
					clickable: false,
					onStageSelect: this.setActiveStage.bind(this),
					stageParams: {
						showTotal: true,
						showCount: true,
						showCounters: true,
						showTunnels: false,
						showAllStagesItem: true,
					},
				};

				void CrmStageListView.open(props, this.layout);
			});
		}

		/**
		 * @returns {String}
		 */
		getStageSelectorTitle()
		{
			if (this.categoriesEnabled && this.kanbanSettingsName !== '')
			{
				return Loc.getMessage('MCRM_STAGE_TOOLBAR_CATEGORY_NAME2', {
					'#CATEGORY_NAME#': this.kanbanSettingsName,
				});
			}

			return Loc.getMessage('MCRM_STAGE_TOOLBAR_CURRENT_STAGE');
		}

		renderStageSelector()
		{
			const styles = this.getStyles();

			return View(
				{
					style: styles.stageSelectorWrapper,
				},
				StageDropdown({
					onClick: this.onToolbarClick,
					entityTypeId: this.entityTypeId,
					categoryId: this.categoryId,
					activeStageId: this.state.activeStageId,
					title: this.getStageSelectorTitle(),
					loading: this.isLoading(),
					setDefaultStage: this.setDefaultStageHandler,
				}),
			);
		}

		renderCurrentStageSummary()
		{
			if (!this.getProps().showSum)
			{
				return null;
			}

			return StageSummary({
				entityTypeId: this.entityTypeId,
				categoryId: this.categoryId,
				activeStageId: this.state.activeStageId,
			});
		}

		getTestId()
		{
			return 'stageToolbar';
		}

		setDefaultStage()
		{
			this.onChangeStage(getCrmKanbanUniqId(this.entityTypeId, this.categoryId));
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const {
			entityTypeId,
			filterParams: {
				CATEGORY_ID: categoryId,
			},
		} = ownProps;

		const id = getCrmKanbanUniqId(entityTypeId, categoryId);

		const {
			categoriesEnabled = false,
			name = '',
		} = selectById(state, id) || {};

		return {
			categoriesEnabled,
			kanbanSettingsName: name,
		};
	};

	module.exports = {
		EntityToolbar: connect(mapStateToProps)(EntityToolbar),
	};
});
