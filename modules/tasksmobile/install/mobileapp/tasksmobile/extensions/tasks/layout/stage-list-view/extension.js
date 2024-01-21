/**
 * @module tasks/layout/stage-list-view
 */
jn.define('tasks/layout/stage-list-view', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { StageListView, StageSelectActions } = require('layout/ui/stage-list-view');
	const { TasksStageList } = require('tasks/layout/stage-list');
	const { Views } = require('tasks/statemanager/redux/types');
	const { largePen } = require('assets/common');

	const { connect } = require('statemanager/redux/connect');
	const {
		selectStages,
		selectCanEdit,
	} = require('tasks/statemanager/redux/slices/kanban-settings');
	const {
		allStagesId,
	} = require('tasks/statemanager/redux/slices/stage-counters');

	const { mergeImmutable } = require('utils/object');

	const getTitleIcon = (view, color = AppTheme.colors.base3) => {
		switch (view)
		{
			case Views.LIST:
				return `
					<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd"
							d="M4.65332 7.0271C4.65332 6.75096 4.87718 6.5271 5.15332 6.5271H21.6735C21.9497 6.5271 22.1735 6.75096 22.1735 7.0271V8.87255C22.1735 9.06972 22.0594 9.24023 21.8936 9.32164C21.5558 9.28365 21.2124 9.26413 20.8645 9.26413C20.385 9.26413 19.9143 9.30117 19.4549 9.37255H5.15332C4.87718 9.37255 4.65332 9.14869 4.65332 8.87255V7.0271ZM11.7566 18.372C11.7566 18.3001 11.7574 18.2283 11.7591 18.1567H5.15332C4.87718 18.1567 4.65332 18.3806 4.65332 18.6567V20.5022C4.65332 20.7783 4.87718 21.0022 5.15332 21.0022H12.1421C11.8913 20.1695 11.7566 19.2865 11.7566 18.372ZM12.3291 15.1868C12.723 14.1318 13.3068 13.1694 14.0391 12.3413H5.15332C4.87718 12.3413 4.65332 12.5652 4.65332 12.8413V14.6868C4.65332 14.9629 4.87718 15.1868 5.15332 15.1868H12.3291ZM18.607 17.7132L20.1063 19.2125L23.7006 15.6182L24.7605 16.6781L20.1073 21.3313L20.0497 21.2736L20.0486 21.2747L17.5471 18.7731L18.607 17.7132ZM14.1701 18.3922C14.1701 22.0894 17.1672 25.0866 20.8645 25.0866C24.5617 25.0866 27.5589 22.0894 27.5589 18.3922C27.5589 14.695 24.5617 11.6978 20.8645 11.6978C17.1672 11.6978 14.1701 14.695 14.1701 18.3922Z"
							fill="${color}"/>
					</svg>
				`;
			case Views.KANBAN:
				return `
					<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd"
							d="M23.75 7.29693L23.75 22.7031C23.75 23.279 23.2956 23.75 22.7398 23.75L7.26016 23.75C6.70443 23.75 6.25 23.279 6.25 22.7031L6.25 7.29693C6.25 6.72098 6.70444 6.25 7.26016 6.25L22.7398 6.25C23.2956 6.25 23.75 6.72098 23.75 7.29693ZM19.4789 8.92502C18.8576 8.92502 18.3539 9.4287 18.3539 10.05L18.3539 18.874C18.3539 19.4953 18.8576 19.999 19.4789 19.999C20.1002 19.999 20.6039 19.4953 20.6039 18.874L20.6039 10.05C20.6039 9.4287 20.1002 8.92502 19.4789 8.92502ZM13.8751 10.05C13.8751 9.4287 14.3788 8.92502 15.0001 8.92502C15.6214 8.92502 16.1251 9.4287 16.1251 10.05L16.1251 13.875C16.1251 14.4963 15.6214 15 15.0001 15C14.3788 15 13.8751 14.4963 13.8751 13.875L13.8751 10.05ZM10.522 8.92502C9.90065 8.92502 9.39697 9.4287 9.39697 10.05L9.39697 15.93C9.39697 16.5513 9.90065 17.055 10.522 17.055C11.1433 17.055 11.647 16.5513 11.647 15.93L11.647 10.05C11.647 9.4287 11.1433 8.92502 10.522 8.92502Z"
							fill="${color}"/>
					</svg>
				`;
			case Views.PLANNER:
				return `
					<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd"
							d="M4.65332 7.0271C4.65332 6.75096 4.87718 6.5271 5.15332 6.5271H21.6735C21.9497 6.5271 22.1735 6.75096 22.1735 7.0271V8.87255C22.1735 9.13902 21.9651 9.3568 21.7024 9.37173V9.34949H15.9103L15.907 9.37255H5.15332C4.87718 9.37255 4.65332 9.14869 4.65332 8.87255V7.0271ZM5.15332 12.3413H15.4827L15.0761 15.1868H5.15332C4.87718 15.1868 4.65332 14.9629 4.65332 14.6868V12.8413C4.65332 12.5652 4.87718 12.3413 5.15332 12.3413ZM5.15332 18.1567H14.1487L11.8388 21.0022H5.15332C4.87718 21.0022 4.65332 20.7783 4.65332 20.5022V18.6567C4.65332 18.3806 4.87718 18.1567 5.15332 18.1567ZM26.9127 23.2536C26.9882 23.6384 26.7838 24.0257 26.4152 24.1596C24.8816 24.717 23.1525 25.0472 21.3187 25.0866H20.5768C18.7342 25.047 16.9972 24.7138 15.4581 24.1515C15.1054 24.0227 14.8993 23.6598 14.9593 23.2891C15.0166 22.9353 15.08 22.5961 15.1447 22.3438C15.3661 21.4807 16.6115 20.8396 17.7574 20.3467C18.0575 20.2175 18.2386 20.1146 18.4216 20.0105C18.6004 19.9088 18.7809 19.8062 19.0758 19.6772C19.1093 19.5182 19.1228 19.3559 19.1159 19.1937L19.6235 19.1335C19.6235 19.1335 19.6903 19.2548 19.5831 18.5418C19.5831 18.5418 19.0128 18.3939 18.9863 17.2585C18.9863 17.2585 18.5575 17.4011 18.5316 16.7132C18.5262 16.5761 18.4908 16.4443 18.4569 16.318C18.3755 16.0152 18.3026 15.744 18.6737 15.5078L18.406 14.7939C18.406 14.7939 18.1243 12.0372 19.3586 12.2603C18.8579 11.467 23.0811 10.8075 23.3616 13.2367C23.472 13.969 23.472 14.7133 23.3616 15.4455C23.3616 15.4455 23.9926 15.373 23.5714 16.5726C23.5714 16.5726 23.3395 17.436 22.9834 17.2421C22.9834 17.2421 23.0411 18.3331 22.4803 18.5181C22.4803 18.5181 22.5204 19.0991 22.5204 19.1385L22.989 19.2085C22.989 19.2085 22.9748 19.6931 23.0683 19.7455C23.4959 20.0216 23.9645 20.2309 24.4575 20.3659C25.9127 20.7353 26.6516 21.3691 26.6516 21.9239L26.9127 23.2536Z"
							fill="${color}"/>
					</svg>
				`;
			case Views.DEADLINE:
				return `
					<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd"
							d="M12.0634 6.85056V5.38883C12.0659 4.84932 11.6478 4.40947 11.127 4.40547C10.6434 4.40301 10.2416 4.77751 10.1846 5.26192L10.1777 5.38883V6.85056C10.1777 7.39008 10.5998 7.82727 11.1206 7.82727C11.6414 7.82727 12.0634 7.39008 12.0634 6.85056ZM20.8107 7.11369V6.44926H22.2885C23.6379 6.53431 24.6834 7.70503 24.659 9.10697V13.1031C23.859 12.7402 22.9971 12.4898 22.0935 12.3721V10.5112H7.91383V21.0666H11.8445C11.8248 21.1768 11.8145 21.2842 11.8145 21.3883C11.8145 22.1959 11.9197 22.9789 12.1173 23.7243H6.63103C5.92295 23.7243 5.34827 23.129 5.34827 22.3955V9.10697C5.34314 9.03787 5.34058 8.97009 5.34058 8.90232C5.34314 7.54424 6.40783 6.44661 7.71881 6.44926H9.19655V7.11369C9.19655 8.21398 10.0573 9.10697 11.1207 9.10697C12.1841 9.10697 13.0448 8.21398 13.0448 7.11369V6.44926H16.9625V7.11369C16.9625 8.21398 17.8245 9.10697 18.8866 9.10697C19.9487 9.10697 20.8107 8.21398 20.8107 7.11369ZM14.3634 12.8904H15.7666C16.0493 12.8904 16.2784 13.118 16.2784 13.3988V14.7928C16.2784 15.0736 16.0493 15.3012 15.7666 15.3012H14.3634C14.0808 15.3012 13.8516 15.0736 13.8516 14.7928V13.3988C13.8516 13.118 14.0808 12.8904 14.3634 12.8904ZM12.1276 12.8906H10.7243C10.4417 12.8906 10.2126 13.1183 10.2126 13.399V14.7931C10.2126 15.0739 10.4417 15.3015 10.7243 15.3015H12.1276C12.4102 15.3015 12.6393 15.0739 12.6393 14.7931V13.399C12.6393 13.1183 12.4102 12.8906 12.1276 12.8906ZM10.7232 16.5066H12.1264C12.409 16.5066 12.6382 16.7342 12.6382 17.015V18.4091C12.6382 18.6898 12.409 18.9175 12.1264 18.9175H10.7232C10.4405 18.9175 10.2114 18.6898 10.2114 18.4091V17.015C10.2114 16.7342 10.4405 16.5066 10.7232 16.5066ZM19.7769 5.42468V6.81067C19.7769 7.31962 19.378 7.73289 18.8854 7.73289C18.3928 7.73156 17.9964 7.31829 17.9964 6.80934V5.42468C17.9964 4.9144 18.3954 4.50246 18.8867 4.50246C19.378 4.50246 19.7769 4.9144 19.7769 5.42468Z"
							fill="${color}"/>
						<path fill-rule="evenodd" clip-rule="evenodd"
							d="M14.7992 24.1348C15.92 26.6271 18.4427 28.1883 21.1733 28.0795C24.7958 28.0053 27.6734 25.0106 27.6029 21.388C27.6027 18.6553 25.9421 16.1968 23.4071 15.1764C20.8721 14.156 17.9713 14.7783 16.0779 16.7487C14.1845 18.7192 13.6785 21.6425 14.7992 24.1348ZM20.2468 18.0268C20.2468 17.5627 20.623 17.1864 21.0872 17.1864C21.5513 17.1864 21.9275 17.5627 21.9275 18.0268V20.5479H23.6082C24.0723 20.5479 24.4485 20.9242 24.4485 21.3883C24.4485 21.8524 24.0723 22.2287 23.6082 22.2287H21.0871C20.6229 22.2287 20.2467 21.8524 20.2467 21.3883L20.2468 21.3751V18.0268Z"
						fill="${color}"/>
					</svg>
				`;
			default:
				return '';
		}
	};

	/**
	 * @class TasksStageListView
	 */
	class TasksStageListView extends StageListView
	{
		static open(params, parentWidget)
		{
			return new Promise((resolve, reject) => {
				parentWidget
					.openWidget('layout', this.getWidgetParams(params))
					.then((layout) => {
						layout.enableNavigationBarBorder(false);
						layout.showComponent(connect(mapStateToProps)(this)({ layout, ...params }));
						resolve(layout);
					})
					.catch((error) => {
						console.error(error);
					});
			});
		}

		static getWidgetParams(params)
		{
			return mergeImmutable(super.getWidgetParams(), {
				titleParams: {
					isRounded: false,
					svg: {
						content: getTitleIcon(params.filterParams.view),
					},
				},
			});
		}

		componentWillReceiveProps(props)
		{
			if (this.props.editable !== props.editable)
			{
				this.setEditable(props.editable);
			}
		}

		setEditable(enable)
		{
			if (enable)
			{
				this.layout.setRightButtons([
					{
						type: 'edit',
						svg: {
							content: largePen(),
						},
						callback: () => this.handlerCategoryEditOpen(),
					},
				]);
			}
			else
			{
				this.layout.setRightButtons([]);
			}
		}

		setTitle(title)
		{
			const titleText = this.getTitleForNavigation(title);
			this.layout.setTitle({
				text: titleText,
				isRounded: false,
				svg: {
					content: getTitleIcon(this.props.filterParams.view),
				},
			}, true);
		}

		getTitleForNavigation(title)
		{
			if (this.props.entityType === 'A')
			{
				return BX.message('TASKS_STAGE_LIST_VIEW_TITLE_FOR_NAVIGATION_SPRINT');
			}

			return BX.message(`TASKS_STAGE_LIST_VIEW_TITLE_FOR_NAVIGATION_${this.props.filterParams.view}`);
		}

		renderStageList()
		{
			const {
				stageParams,
				canMoveStages,
				filterParams,
				stageIdsBySemantics,
			} = this.props;

			let activeStageId = this.props.activeStageId;
			const activeStageExists = stageIdsBySemantics.processStages.includes(activeStageId)
				|| activeStageId === allStagesId;
			if (!activeStageExists)
			{
				activeStageId = allStagesId;
			}

			return new TasksStageList({
				title: this.getStageListTitle(),
				readOnly: this.getStageReadOnly(),
				stageIdsBySemantics: this.stageIdsBySemantics,
				canMoveStages,
				stageParams,
				activeStageId,
				onSelectedStage: this.onSelectedStageHandler,
				onOpenStageDetail: this.onOpenStageDetailHandler,
				enableStageSelect: this.enableStageSelect,
				kanbanSettingsId: this.kanbanSettingsId,
				filterParams,
				isReversed: this.isReversed,
				shouldShowStageListTitle: false,
			});
		}

		getStageReadOnly()
		{
			return this.props.readOnly;
		}

		async handlerCategoryEditOpen()
		{
			const { TasksKanbanSettingsEditor } = await requireLazy('tasks:layout/kanban/settings');

			TasksKanbanSettingsEditor.open(
				{
					filterParams: this.props.filterParams,
					kanbanSettingsId: this.kanbanSettingsId,
				},
				this.layout,
			);
		}

		onSelectedStage(stage)
		{
			if (this.selectAction === StageSelectActions.ChangeEntityStage)
			{
				this.onChangeEntityStage(stage.id, stage.statusId);
			}
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const canEdit = selectCanEdit(state, ownProps.kanbanSettingsId) || false;

		return {
			editable: canEdit,
			stageIdsBySemantics: {
				processStages: selectStages(state, ownProps.kanbanSettingsId),
			},
		};
	};

	module.exports = {
		TasksStageListView,
	};
});
