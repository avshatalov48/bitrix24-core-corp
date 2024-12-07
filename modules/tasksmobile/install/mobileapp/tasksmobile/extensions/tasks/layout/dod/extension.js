/**
 * @module tasks/layout/dod
 */
jn.define('tasks/layout/dod', (require, exports, module) => {
	const { Loc } = require('loc');
	const { isEqual, isEmpty } = require('utils/object');
	const { Color, Indent } = require('tokens');
	const { RequestExecutor } = require('rest');
	const { Text4 } = require('ui-system/typography/text');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { Box } = require('ui-system/layout/box');
	const { Area } = require('ui-system/layout/area');
	const { BottomSheet } = require('bottom-sheet');
	const { ChecklistController } = require('tasks/checklist');
	const { Button, ButtonSize } = require('ui-system/form/buttons/button');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { makeLibraryImagePath } = require('asset-manager');
	const { DodChecklists } = require('tasks/layout/dod/src/list');
	const { DodTypeSelector } = require('tasks/layout/dod/src/selector');
	const { RunActionExecutor } = require('rest/run-action-executor');

	/**
	 * @typedef {Object} DodProps
	 * @property {Object} parentWidget
	 * @property {Function} completeAction
	 * @property {Array} [dodTypes]
	 * @property {number} groupId
	 * @property {number} taskId
	 * @property {number} userId
	 * @property {Function} onComplete
	 *
	 * @class Dod
	 */
	class Dod extends LayoutComponent
	{
		/**
		 * @param {DodProps} props
		 */
		constructor(props)
		{
			super(props);

			this.parentWidget = props.parentWidget;
			this.dodBottomSheet = null;
			this.checklistController = null;

			this.#initialState(props);
		}

		componentDidUpdate()
		{
			const { selectedTypeId } = this.state;

			if (this.prevSelectedTypeId !== selectedTypeId || !this.checklistController)
			{
				void this.#changeDodChecklist();
			}

			this.prevSelectedTypeId = selectedTypeId;
		}

		componentWillReceiveProps(nextProps)
		{
			this.#initialState(nextProps);
		}

		#initialState(props)
		{
			const { activeTypeId } = props;

			this.state = {
				loading: true,
				dodTreeIds: [],
				dodTrees: [],
				selectedTypeId: activeTypeId,
				checklistDetails: [],
			};
		}

		static async show(props)
		{
			const { completeAction, ...restProps } = props;
			const component = new Dod(restProps);

			const entityBottomSheet = new BottomSheet({ component });
			const dodBottomSheet = entityBottomSheet
				.setParentWidget(component.getParentWidget())
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgContentPrimary.toHex())
				.enableForceDismissOnSwipeDown()
				.disableHorizontalSwipe()
				.enableSwipe()
				.enableResizeContent()
				.disableOnlyMediumPosition()
				.setTitleParams({
					text: Loc.getMessage('M_TASK_DOD_BOTTOM_SHEET_TITLE'),
					type: 'dialog',
				});

			const layoutWidget = await dodBottomSheet.open();
			component.setParentWidget(layoutWidget);
			component.setBottomSheet(dodBottomSheet);
		}

		#getDodTypes()
		{
			const { dodTypes } = this.props;

			return dodTypes;
		}

		getParentWidget()
		{
			return this.parentWidget || PageManager;
		}

		setParentWidget(parentWidget)
		{
			this.parentWidget = parentWidget;
		}

		setBottomSheet(dodBottomSheet)
		{
			this.dodBottomSheet = dodBottomSheet;
		}

		#fetchDodChecklist(typeId)
		{
			return new Promise((resolve) => {
				(new RequestExecutor('tasksmobile.Dod.getDodTree', {
					typeId,
					taskId: this.#getTaskId(),
					groupId: this.#getGroupId(),
				}))
					.call()
					.then((response) => {
						if (response?.error)
						{
							console.error(response?.error);
						}

						resolve(response?.result);
					})
					.catch(console.error);
			});
		}

		#saveDod()
		{
			const data = {
				taskId: this.#getTaskId(),
				typeId: this.#getSelectedTypeId(),
				items: this.checklistController.getChecklistRequestData(),
			};

			return (new RunActionExecutor(
				'tasks.scrum.doD.saveList',
				data,
			).setHandler().call());
		}

		#getGroupId()
		{
			const { groupId } = this.props;

			return groupId;
		}

		#getTaskId()
		{
			const { taskId } = this.props;

			return taskId;
		}

		#initChecklistController(params)
		{
			const { userId } = this.props;

			const initParams = params.dodTrees
				? params
				: { ...this.state, ...params };

			const checklistTree = this.#getSelectedDodTree(initParams);

			if (!checklistTree)
			{
				return;
			}

			this.checklistController = new ChecklistController({
				userId,
				checklistTree,
				taskId: this.#getTaskId(),
				groupId: this.#getGroupId(),
				inLayout: false,
				hideCompleted: false,
				hideMoreMenu: true,
				autoCompleteItem: false,
				parentWidget: this.parentWidget,
				onChange: this.#handleOnChange,
			});
		}

		#handleOnChange = () => {
			const checklistDetails = this.#getChecklistControllerDetails();

			if (!isEqual(this.#getChecklistDetail(), checklistDetails))
			{
				this.setState({
					checklistDetails,
				});
			}
		};

		#getChecklistDetail()
		{
			const { checklistDetails } = this.state;

			return checklistDetails;
		}

		#getChecklistControllerDetails()
		{
			const { checklistDetails } = this.checklistController.getReduxData();

			return checklistDetails;
		}

		render()
		{
			return Box(
				{
					withScroll: true,
					backgroundColor: Color.bgSecondary,
					footer: this.renderBoxButtons(),
				},
				this.renderDodBanner(),
				this.renderDodTypeSelector(),
				this.renderChecklists(),
			);
		}

		renderBoxButtons()
		{
			return BoxFooter(
				{
					safeArea: true,
				},
				Button({
					testId: 'DOD_BUTTON_COMPLETE_TASK',
					stretched: true,
					size: ButtonSize.L,
					onClick: this.#handleOnCompleteDodChecklist,
					text: Loc.getMessage('M_TASK_DOD_COMPLETE_TASK'),
					disabled: this.#isDisabledCompleteButton(),
				}),
			);
		}

		renderDodTypeSelector()
		{
			return Area(
				{
					excludePaddingSide: {
						bottom: true,
					},
				},
				new DodTypeSelector({
					types: this.#getDodTypes(),
					selectedTypeId: this.#getSelectedTypeId(),
					onSelected: this.#handleOnSelectedDodType,
				}),
			);
		}

		renderChecklists()
		{
			return Area(
				{
					isFirst: true,
				},
				new DodChecklists({
					loading: this.#isLoading(),
					value: this.checklistController?.getReduxData() || {},
					checklistController: this.checklistController,
				}),
			);
		}

		renderDodBanner()
		{
			return Area(
				{
					excludePaddingSide: {
						bottom: true,
					},
				},
				Card(
					{
						testId: 'DOD_CARD_BANNER',
						design: CardDesign.ACCENT,
					},
					View(
						{
							style: {
								alignItems: 'center',
								flexDirection: 'row',
							},
						},
						Image({
							testId: 'DOD_CARD_BANNER_IMAGE',
							style: {
								width: 90,
								height: 90,
								marginRight: Indent.M.toNumber(),
							},
							svg: {
								uri: makeLibraryImagePath('dod-banner.svg', 'illustrations', 'tasks'),
							},
						}),
						Text4({
							style: {
								flex: 1,
							},
							testId: 'DOD_CARD_BANNER_TEXT',
							text: Loc.getMessage('M_TASK_DOD_BANNER_TEXT'),
							color: Color.base2,
						}),
					),
				),
			);
		}

		#handleOnCompleteDodChecklist = () => {
			const { onComplete } = this.props;

			const complete = () => {
				this.dodBottomSheet?.close();
				if (onComplete)
				{
					onComplete();
				}
			};

			complete();
			this.#saveDod();
		};

		async #changeDodChecklist()
		{
			const typeId = this.#getSelectedTypeId();
			let state = {
				selectedTypeId: typeId,
			};

			if (!this.#isLoadedDodTree(typeId))
			{
				const { dodTrees, dodTreeIds } = this.state;
				const dodChecklist = await this.#fetchDodChecklist(typeId);

				state = {
					...state,
					loading: false,
					dodTreeIds: [...dodTreeIds, Number(typeId)],
					dodTrees: [...dodTrees, { ...dodChecklist, typeId }],
				};
			}

			this.#initChecklistController(state);

			this.setState({ ...state, checklistDetails: this.#getChecklistControllerDetails() });
		}

		#handleOnSelectedDodType = (typeId) => {
			const { loading } = this.state;
			const state = {
				selectedTypeId: typeId,
			};

			if (!loading && !this.#isLoadedDodTree(typeId))
			{
				state.loading = true;
			}

			this.setState(state);
		};

		#getSelectedDodTree(params)
		{
			const { loading, dodTrees, selectedTypeId } = params;

			if (loading)
			{
				return null;
			}

			return dodTrees.find((dodTree) => dodTree.typeId === selectedTypeId);
		}

		#getDodTypeById(id)
		{
			const { dodTypes } = this.props;

			return dodTypes.find((dodTree) => dodTree.id === id);
		}

		#getSelectedTypeId()
		{
			const { selectedTypeId } = this.state;

			return selectedTypeId;
		}

		#isDisabledCompleteButton()
		{
			return this.#isRequiredSelectedType() && !this.#isCompleted();
		}

		/**
		 * @param {number} typeId
		 * @returns {boolean}
		 */
		#isLoadedDodTree(typeId)
		{
			const { dodTreeIds } = this.state;

			return dodTreeIds.includes(typeId);
		}

		#isLoading()
		{
			const { loading } = this.state;

			return Boolean(loading);
		}

		#isCompleted()
		{
			const { dodTrees } = this.state;

			if (isEmpty(dodTrees))
			{
				return false;
			}

			const checklistDetails = this.#getChecklistDetail();

			return isEmpty(checklistDetails)
				? true
				: checklistDetails.every(({ uncompleted }) => uncompleted === 0);
		}

		#isRequiredSelectedType()
		{
			const selectedType = this.#getDodTypeById(this.#getSelectedTypeId());

			return selectedType?.dodRequired === 'Y';
		}
	}

	Dod.propTypes = {
		parentWidget: PropTypes.object,
		onComplete: PropTypes.func.isRequired,
		dodTypes: PropTypes.arrayOf(PropTypes.object).isRequired,
		groupId: PropTypes.number.isRequired,
		taskId: PropTypes.number.isRequired,
		userId: PropTypes.number.isRequired,
	};

	module.exports = { Dod };
});
