/**
 * @module tasks/layout/kanban/settings
 */
jn.define('tasks/layout/kanban/settings', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { isEqual } = require('utils/object');
	const { NotifyManager } = require('notify-manager');
	const { Views } = require('tasks/statemanager/redux/types');

	const {
		KanbanSettings,
		Stage,
		SEMANTICS,
	} = require('layout/ui/kanban/settings');
	const { TasksStageList } = require('tasks/layout/stage-list');

	const { connect } = require('statemanager/redux/connect');
	const {
		selectStatus,
		selectStages,
		getUniqId,
		updateStagesOrder,
	} = require('tasks/statemanager/redux/slices/kanban-settings');
	const { addStage } = require('tasks/statemanager/redux/slices/stage-settings');

	const getTitleIcon = (view, color = AppTheme.colors.base3) => {
		switch (view)
		{
			case Views.LIST:
				return `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M3.72266 5.72168C3.72266 5.44554 3.94651 5.22168 4.22266 5.22168H17.2388C17.515 5.22168 17.7388 5.44554 17.7388 5.72168V6.99804C17.7388 7.19798 17.6215 7.3705 17.4519 7.4505C17.202 7.42459 16.9483 7.41131 16.6916 7.41131C16.308 7.41131 15.9314 7.44094 15.5639 7.49804H4.22266C3.94651 7.49804 3.72266 7.27418 3.72266 6.99804V5.72168ZM9.40526 14.6976C9.40526 14.64 9.40593 14.5826 9.40726 14.5254H4.22266C3.94651 14.5254 3.72266 14.7492 3.72266 15.0254V16.3018C3.72266 16.5779 3.94651 16.8018 4.22266 16.8018H9.71369C9.51308 16.1356 9.40526 15.4292 9.40526 14.6976ZM9.86327 12.1494C10.1784 11.3054 10.6455 10.5355 11.2313 9.87305H4.22266C3.94651 9.87305 3.72266 10.0969 3.72266 10.373V11.6494C3.72266 11.9256 3.94651 12.1494 4.22266 12.1494H9.86327ZM14.8856 14.1706L16.085 15.37L18.9605 12.4946L19.8084 13.3425L16.0859 17.0651L16.0397 17.0189L16.0389 17.0197L14.0377 15.0185L14.8856 14.1706ZM11.336 14.7137C11.336 17.6715 13.7338 20.0693 16.6916 20.0693C19.6493 20.0693 22.0471 17.6715 22.0471 14.7137C22.0471 11.756 19.6493 9.35822 16.6916 9.35822C13.7338 9.35822 11.336 11.756 11.336 14.7137Z" fill="${color}"/></svg>`;
			case Views.KANBAN:
				return `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M19 5.83755L19 18.1625C19 18.6232 18.6365 19 18.1919 19L5.80813 19C5.36355 19 5 18.6232 5 18.1625L5 5.83754C5 5.37678 5.36355 5 5.80813 5L18.1919 5C18.6365 5 19 5.37678 19 5.83755ZM15.5831 7.14001C15.086 7.14001 14.6831 7.54296 14.6831 8.04001L14.6831 15.0992C14.6831 15.5963 15.086 15.9992 15.5831 15.9992C16.0802 15.9992 16.4831 15.5963 16.4831 15.0992L16.4831 8.04001C16.4831 7.54296 16.0802 7.14001 15.5831 7.14001ZM11.1001 8.04001C11.1001 7.54296 11.503 7.14001 12.0001 7.14001C12.4972 7.14001 12.9001 7.54296 12.9001 8.04001L12.9001 11.1C12.9001 11.5971 12.4972 12 12.0001 12C11.503 12 11.1001 11.5971 11.1001 11.1L11.1001 8.04001ZM8.41758 7.14001C7.92052 7.14001 7.51758 7.54296 7.51758 8.04001L7.51758 12.744C7.51758 13.241 7.92052 13.644 8.41758 13.644C8.91463 13.644 9.31758 13.241 9.31758 12.744L9.31758 8.04001C9.31758 7.54296 8.91463 7.14001 8.41758 7.14001Z" fill="${color}"/></svg>`;
			case Views.PLANNER:
				return `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M3.72266 5.72168C3.72266 5.44554 3.94651 5.22168 4.22266 5.22168H17.2388C17.515 5.22168 17.7388 5.44554 17.7388 5.72168V6.99804C17.7388 7.23172 17.5785 7.42796 17.3619 7.48278V7.47959H12.7282L12.7256 7.49804H4.22266C3.94651 7.49804 3.72266 7.27418 3.72266 6.99804V5.72168ZM4.22266 9.87305H12.3862L12.0609 12.1494H4.22266C3.94651 12.1494 3.72266 11.9256 3.72266 11.6494V10.373C3.72266 10.0969 3.94651 9.87305 4.22266 9.87305ZM4.22266 14.5254H11.3189L9.471 16.8018H4.22266C3.94651 16.8018 3.72266 16.5779 3.72266 16.3018V15.0254C3.72266 14.7492 3.94651 14.5254 4.22266 14.5254ZM21.5301 18.6029C21.5906 18.9107 21.427 19.2206 21.1322 19.3277C19.9053 19.7736 18.522 20.0378 17.055 20.0693H16.4615C14.9874 20.0376 13.5978 19.771 12.3665 19.3212C12.0843 19.2181 11.9194 18.9278 11.9675 18.6313C12.0133 18.3483 12.064 18.0769 12.1158 17.875C12.2929 17.1845 13.2892 16.6717 14.2059 16.2773C14.446 16.174 14.5909 16.0916 14.7373 16.0084C14.8803 15.9271 15.0247 15.8449 15.2607 15.7417C15.2875 15.6146 15.2982 15.4847 15.2927 15.355L15.6988 15.3068C15.6988 15.3068 15.7522 15.4038 15.6665 14.8334C15.6665 14.8334 15.2102 14.7151 15.1891 13.8068C15.1891 13.8068 14.846 13.9209 14.8253 13.3705C14.821 13.2609 14.7927 13.1554 14.7655 13.0544C14.7004 12.8121 14.6421 12.5952 14.939 12.4062L14.7248 11.8351C14.7248 11.8351 14.4995 9.62973 15.4869 9.80827C15.0863 9.17357 18.4649 8.64599 18.6893 10.5894C18.7776 11.1752 18.7776 11.7706 18.6893 12.3564C18.6893 12.3564 19.1941 12.2984 18.8571 13.258C18.8571 13.258 18.6716 13.9488 18.3867 13.7937C18.3867 13.7937 18.4328 14.6665 17.9843 14.8144C17.9843 14.8144 18.0163 15.2793 18.0163 15.3108L18.3912 15.3668C18.3912 15.3668 18.3799 15.7544 18.4547 15.7964C18.7967 16.0173 19.1716 16.1848 19.566 16.2927C20.7301 16.5882 21.3213 17.0953 21.3213 17.5391L21.5301 18.6029Z" fill="${color}"/></svg>`;
			case Views.DEADLINE:
				return `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.65071 5.48045V4.31107C9.65275 3.87946 9.31821 3.52758 8.90157 3.52438C8.51469 3.52241 8.19329 3.82201 8.14771 4.20954L8.14218 4.31107V5.48045C8.14218 5.91206 8.4798 6.26182 8.89644 6.26182C9.31308 6.26182 9.65071 5.91206 9.65071 5.48045ZM16.6486 5.69096V5.15941H17.8308C18.9103 5.22745 19.7467 6.16403 19.7272 7.28558V10.4825C19.0872 10.1922 18.3977 9.99188 17.6748 9.8977V8.40894H6.33106V16.8533H9.47559C9.45982 16.9414 9.45156 17.0273 9.45156 17.1106C9.45156 17.7567 9.53579 18.3831 9.69387 18.9794H5.30482C4.73836 18.9794 4.27862 18.5032 4.27862 17.9164V7.28558C4.27451 7.2303 4.27246 7.17608 4.27246 7.12186C4.27451 6.03539 5.12627 5.15729 6.17505 5.15941H7.35724V5.69096C7.35724 6.57119 8.04582 7.28558 8.89654 7.28558C9.74727 7.28558 10.4359 6.57119 10.4359 5.69096V5.15941H13.57V5.69096C13.57 6.57119 14.2596 7.28558 15.1093 7.28558C15.959 7.28558 16.6486 6.57119 16.6486 5.69096ZM11.4907 10.3123H12.6133C12.8394 10.3123 13.0227 10.4944 13.0227 10.719V11.8342C13.0227 12.0589 12.8394 12.241 12.6133 12.241H11.4907C11.2646 12.241 11.0813 12.0589 11.0813 11.8342V10.719C11.0813 10.4944 11.2646 10.3123 11.4907 10.3123ZM9.70208 10.3125H8.57947C8.35336 10.3125 8.17007 10.4946 8.17007 10.7192V11.8345C8.17007 12.0591 8.35336 12.2412 8.57947 12.2412H9.70208C9.92818 12.2412 10.1115 12.0591 10.1115 11.8345V10.7192C10.1115 10.4946 9.92818 10.3125 9.70208 10.3125ZM8.57852 13.2053H9.70113C9.92724 13.2053 10.1105 13.3874 10.1105 13.612V14.7273C10.1105 14.9519 9.92724 15.134 9.70113 15.134H8.57852C8.35242 15.134 8.16912 14.9519 8.16912 14.7273V13.612C8.16912 13.3874 8.35242 13.2053 8.57852 13.2053ZM15.8215 4.33975V5.44854C15.8215 5.8557 15.5024 6.18631 15.1083 6.18631C14.7142 6.18525 14.3971 5.85463 14.3971 5.44747V4.33975C14.3971 3.93152 14.7163 3.60197 15.1093 3.60197C15.5024 3.60197 15.8215 3.93152 15.8215 4.33975Z" fill="${color}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M11.8394 19.3078C12.736 21.3016 14.7542 22.5506 16.9386 22.4635C19.8366 22.4042 22.1387 20.0084 22.0823 17.1104C22.0822 14.9242 20.7537 12.9575 18.7257 12.1411C16.6977 11.3247 14.377 11.8226 12.8623 13.399C11.3477 14.9754 10.9428 17.314 11.8394 19.3078ZM16.1974 14.4214C16.1974 14.0501 16.4984 13.7491 16.8697 13.7491C17.241 13.7491 17.542 14.0501 17.542 14.4214V16.4383H18.8865C19.2578 16.4383 19.5588 16.7393 19.5588 17.1106C19.5588 17.4819 19.2578 17.7829 18.8865 17.7829H16.8697C16.4984 17.7829 16.1974 17.4819 16.1974 17.1106L16.1974 17.1V14.4214Z" fill="${color}"/></svg>`;
			default:
				return `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.98364 7.25195H23.0168C23.4822 7.25195 23.8595 7.61231 23.8595 8.05684C23.8595 8.14694 23.8436 8.23641 23.8126 8.3215L23.2678 9.8163C23.1498 10.1398 22.8305 10.3565 22.472 10.3565H5.5241C5.16473 10.3565 4.84491 10.1388 4.7276 9.81439L4.18714 8.31959C4.03522 7.89941 4.26867 7.44115 4.70856 7.29604C4.79705 7.26685 4.89002 7.25195 4.98364 7.25195ZM7.9538 12.9908H20.0467C20.512 12.9908 20.8893 13.3511 20.8893 13.7957C20.8893 13.8922 20.8711 13.9879 20.8357 14.0782L20.2489 15.573C20.1256 15.8872 19.8111 16.0953 19.4599 16.0953H8.50938C8.15267 16.0953 7.83461 15.8808 7.71527 15.5597L7.1597 14.0649C7.004 13.646 7.23331 13.1859 7.67188 13.0371C7.7624 13.0064 7.85775 12.9908 7.9538 12.9908ZM11.7082 18.7476H16.2923C16.7577 18.7476 17.1349 19.1079 17.1349 19.5525C17.1349 19.6308 17.123 19.7087 17.0994 19.7837L16.6301 21.2785C16.5232 21.619 16.1951 21.8522 15.8229 21.8522H12.2284C11.8654 21.8522 11.5432 21.6302 11.4287 21.3012L10.9085 19.8064C10.7618 19.3845 11.0008 18.9289 11.4424 18.7887C11.5281 18.7615 11.6179 18.7476 11.7082 18.7476Z" fill="${color}"/></svg>`;
		}
	};

	/**
		* @class TasksKanbanSettingsEditor
		*/
	class TasksKanbanSettingsEditor extends KanbanSettings
	{
		constructor(props)
		{
			super(props);
			this.onStageMove = this.onStageMove.bind(this);
		}

		getTitleForNavigation(title)
		{
			return BX.message(`TASKS_TITLE_FOR_NAVIGATION_${this.props.filterParams.view}`);
		}

		getStageListTitle()
		{
			return BX.message('TASKS_STAGES_LIST_TITLE');
		}

		renderStageList()
		{
			return new TasksStageList({
				title: this.getStageListTitle(),
				readOnly: false,
				canMoveStages: true,
				onStageMove: this.onStageMove,
				stageIdsBySemantics: this.changedFields.stageIdsBySemantics || this.props.stageIdsBySemantics,
				onOpenStageDetail: this.onOpenStageDetail,
				kanbanSettingsId: this.kanbanSettingsId,
				filterParams: this.props.filterParams,
			});
		}

		onStageMove(processStages)
		{
			this.changedFields = {
				...this.changedFields,
				stageIdsBySemantics: {
					...this.stageIdsBySemantics,
					processStages,
				},
			};
			this.setState({});
		}

		renderContent()
		{
			return View(
				{
					onClick: () => Keyboard.dismiss(),
					onPan: () => Keyboard.dismiss(),
				},
				this.renderStageList(),
				this.renderStageButtons(),
			);
		}

		async openStageDetail({ id })
		{
			const { TasksKanbanStageSettings } = await requireLazy('tasks:layout/kanban/stage-settings');

			await TasksKanbanStageSettings.open(
				{
					view: this.props.filterParams.view,
					projectId: this.props.filterParams.projectId,
					ownerId: this.props.filterParams.searchParams.ownerId,
					stageId: id,
					kanbanSettingsId: this.kanbanSettingsId,
				},
				this.layout,
			);
		}

		save()
		{
			return new Promise((resolve, reject) => {
				NotifyManager.showLoadingIndicator();
				if (this.props.updateStagesOrder)
				{
					this.props.updateStagesOrder(
						{
							view: this.props.filterParams.view,
							projectId: this.props.filterParams.projectId,
							ownerId: this.props.filterParams.searchParams.ownerId,
							fields: {
								id: this.kanbanSettingsId,
								...this.changedFields,
							},
						},
					)
						.then(() => {
							NotifyManager.hideLoadingIndicator(true);
							resolve();
						})
						.catch((errors) => {
							NotifyManager.showErrors(errors);
							reject(errors);
						});
				}
			});
		}

		hasChangedFields()
		{
			return this.changedFields.stageIdsBySemantics
								&& !isEqual(
									this.changedFields.stageIdsBySemantics.processStages,
									this.stageIdsBySemantics.processStages,
								);
		}

		createStage(semantics, semanticsType)
		{
			if (this.props.addStage)
			{
				const stage = new Stage({ semantics });

				const {
					name,
					color,
				} = stage;
				const preparedColor = color.replace('#', '');
				const afterId = this.stageIdsBySemantics.processStages.length > 0
					? this.stageIdsBySemantics.processStages[this.stageIdsBySemantics.processStages.length - 1]
					: null;

				return new Promise((resolve, reject) => {
					this.props.addStage(
						{
							filterParams: this.props.filterParams,
							name,
							color: preparedColor,
							afterId,
						},
					)
						.then((createResult) => {
							NotifyManager.hideLoadingIndicator(
								true,
								BX.message('CATEGORY_DETAIL_SUCCESS_CREATION'),
								1000,
							);
							setTimeout(() => resolve(createResult.payload), 1300);
						})
						.catch((response) => {
							NotifyManager.showErrors(response.errors);
							reject();
						});
				});
			}

			return Promise.reject();
		}

		renderStageButtons()
		{
			return View(
				{
					style: {
						borderRadius: 12,
						marginBottom: 8,
					},
				},
				this.renderCreateStageButton({
					buttonText: BX.message('CATEGORY_DETAIL_CREATE_PROCESS_STAGE'),
					onClick: () => {
						this.createStageAndOpenStageDetail(SEMANTICS.PROCESS, 'processStages');
					},
				}),
			);
		}

		static getWidgetParams(params)
		{
			return {
				modal: true,
				backgroundColor: AppTheme.colors.bgSecondary,
				backdrop: {
					showOnTop: true,
					forceDismissOnSwipeDown: true,
					horizontalSwipeAllowed: false,
					swipeContentAllowed: false,
					navigationBarColor: AppTheme.colors.bgSecondary,
				},
				titleParams: {
					svg: {
						content: getTitleIcon(params.filterParams.view),
					},
				},
			};
		}

		updateLayoutTitle()
		{
			this.layout.setTitle({
				text: this.getTitleForNavigation(),
				svg: {
					content: getTitleIcon(this.props.filterParams.view),
				},
			});
		}

		static open(params, parentWidget = PageManager)
		{
			return new Promise((resolve, reject) => {
				parentWidget
					.openWidget('layout', this.getWidgetParams(params))
					.then((layout) => {
						layout.enableNavigationBarBorder(false);
						layout.showComponent(connect(mapStateToProps, mapDispatchToProps)(this)({ layout, ...params }));
						resolve(layout);
					})
					.catch(reject);
			});
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const stageIdsBySemantics = {
			processStages: selectStages(
				state,
				getUniqId(
					ownProps.filterParams.view,
					ownProps.filterParams.projectId,
					ownProps.filterParams.searchParams.ownerId,
				),
			),
		};

		return {
			stageIdsBySemantics,
			originalKanbanSettingsId: ownProps.kanbanSettingsId,
			status: selectStatus(state),
		};
	};

	const mapDispatchToProps = ({
		updateStagesOrder,
		addStage,
	});

	module.exports = {
		TasksKanbanSettingsEditor,
	};
});
