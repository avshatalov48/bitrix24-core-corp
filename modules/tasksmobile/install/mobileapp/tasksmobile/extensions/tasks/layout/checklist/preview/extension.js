/**
 * @module tasks/layout/checklist/preview
 */
jn.define('tasks/layout/checklist/preview', (require, exports, module) => {
	const { Icon } = require('assets/icons');
	const { Loc } = require('loc');
	const { Indent } = require('tokens');
	const { isOnline } = require('device/connection');
	const { PropTypes } = require('utils/validation');
	const { showOfflineToast } = require('toast');
	const { UIMenu } = require('layout/ui/menu');
	const { PureComponent } = require('layout/pure-component');
	const { AddButton } = require('layout/ui/fields/theme/air/elements/add-button');
	const { MoreButton } = require('layout/ui/fields/theme/air/elements/more-button');
	const { Title } = require('tasks/layout/checklist/preview/src/title');
	const { Item, ItemStub } = require('tasks/layout/checklist/preview/src/item');

	const MAX_ELEMENTS = 3;
	const ClickStrategy = {
		CREATE: 'CREATE',
		OPEN: 'OPEN',
	};

	/**
	 * @typedef {Object} ChecklistPreviewPropsInitialState
	 * @property {string} title
	 * @property {number} completed
	 * @property {number} uncompleted
	 */

	/**
	 * @typedef {Object} ChecklistPreviewPropsConfig
	 * @property {Object} parentWidget
	 * @property {Object} checklistController
	 * @property {ChecklistPreviewPropsInitialState[]} initialState
	 * @property {number|string} taskId
	 */

	/**
	 * @typedef {Object} ChecklistPreviewProps
	 * @property {string} id
	 * @property {string} testId
	 * @property {Object} value
	 * @property {boolean} readOnly
	 * @property {ChecklistPreviewPropsConfig} config
	 * @property {boolean} loading
	 * @property {boolean} [hideTitle=false]
	 * @property {number} maxElements
	 * @property {boolean} showAddButton
	 */

	/**
	 * @class ChecklistPreview
	 */
	class ChecklistPreview extends PureComponent
	{
		/**
		 * @param {ChecklistPreviewProps} props
		 */
		constructor(props)
		{
			super(props);

			this.bindContainerRef = this.bindContainerRef.bind(this);
			this.handleContentClick = this.handleContentClick.bind(this);
			this.createChecklist = this.createChecklist.bind(this);

			const { initialState = [] } = this.getConfig();

			this.state = {
				value: props.value,
				collapsed: initialState.length > MAX_ELEMENTS,
			};
		}

		getConfig()
		{
			return this.props.config || {};
		}

		get controller()
		{
			return this.getConfig().checklistController;
		}

		get testId()
		{
			return this.props.testId;
		}

		isEmpty()
		{
			const { completed = 0, uncompleted = 0 } = this.state.value || {};

			return (completed + uncompleted) === 0;
		}

		isLoading()
		{
			return this.props.loading;
		}

		validate()
		{
			return true;
		}

		isValid()
		{
			return true;
		}

		isRequired()
		{
			return false;
		}

		isReadOnly()
		{
			return this.props.readOnly;
		}

		isDisabled()
		{
			return Boolean(this.props.disabled);
		}

		isMultiple()
		{
			return Boolean(this.props.multiple);
		}

		getId()
		{
			return this.props.id;
		}

		#getMaxElements()
		{
			const { maxElements } = this.props;

			return maxElements > 0 ? maxElements : MAX_ELEMENTS;
		}

		hasUploadingFiles()
		{
			return false;
		}

		bindContainerRef(ref)
		{
			this.fieldContainerRef = ref;
		}

		setTaskId(taskId)
		{
			this.controller.setTaskId(taskId);
		}

		/**
		 * @public
		 * @param {number} completed
		 * @param {number} uncompleted
		 * @return {void}
		 */
		triggerChange({ completed = 0, uncompleted = 0 })
		{
			this.props.onChange?.({ completed, uncompleted });
		}

		/**
		 * @public
		 * @return {(function(): void)|null}
		 */
		getContentClickHandler()
		{
			if (this.isReadOnly() && !this.props.onContentClick)
			{
				return null;
			}

			return this.handleContentClick;
		}

		handleContentClick()
		{
			if (!this.isReadOnly() && !this.isDisabled() && !isOnline())
			{
				showOfflineToast({}, this.getParentWidget());

				return;
			}

			if (this.props.onContentClick)
			{
				this.props.onContentClick(this);
			}

			if (this.isReadOnly())
			{
				return;
			}

			const checklists = [...this.controller.getChecklists().values()];
			if (checklists.length > 0)
			{
				this.#openChecklistSelector(checklists);

				return;
			}

			this.createChecklist();
		}

		#openChecklistSelector(checklists)
		{
			this.menu = this.#createMenu(checklists);
			this.menu.show({ target: this.fieldContainerRef });
		}

		#createMenu(checklists)
		{
			return new UIMenu(this.#getActions(checklists));
		}

		#getActions(checklists)
		{
			const getId = (item) => item.getRootItem()?.getId?.();

			const getTitle = (item) => {
				return item.getRootItem()?.getTitle?.()
					|| Loc.getMessage('TASKS_FIELDS_CHECKLIST_AIR_COMPACT_TITLE');
			};

			const actions = checklists.map((item, index) => ({
				id: `checklist-${getId(item) ?? index}`,
				testId: `checklist-${getId(item) ?? index}`,
				title: getTitle(item),
				onItemSelected: () => this.openPageManager(checklists[index]),
				iconName: Icon.TASK_LIST,
			}));

			if (!this.isReadOnly())
			{
				actions.push({
					id: 'create-checklist',
					testId: 'create-checklist',
					title: Loc.getMessage('TASKS_FIELDS_CHECKLIST_AIR_ADD_CHECKLIST'),
					onItemSelected: this.createChecklist,
					iconName: Icon.PLUS,
				});
			}

			return actions;
		}

		componentWillReceiveProps(props)
		{
			this.state.value = props.value;

			const elementsLoaded = this.props.loading === true && props.loading === false;

			if (elementsLoaded && this.controller)
			{
				const checklistsCount = this.controller.getChecklists().size;
				this.state.collapsed = checklistsCount > this.#getMaxElements();
			}
		}

		#isCollapsed()
		{
			return this.state.collapsed;
		}

		#expand()
		{
			this.setState({ collapsed: false });
		}

		openPageManager(checklist)
		{
			this.controller.openChecklist({ checklist });
		}

		createChecklist()
		{
			if (this.isLoading())
			{
				return;
			}

			const { handleOnCreateChecklist } = this.controller;

			const factory = handleOnCreateChecklist();

			factory();
		}

		/**
		 * @public
		 * @return {*}
		 */
		getParentWidget()
		{
			return this.getConfig().parentWidget;
		}

		/**
		 * @return {*[]}
		 */
		getSortedChecklists()
		{
			const checklists = this.controller.getChecklists();

			return [...checklists.values()]
				.filter((checklist) => checklist?.getRootItem()?.hasDescendants())
				.sort((a, b) => {
					const itemA = a.getRootItem()?.getSortIndex();
					const itemB = b.getRootItem()?.getSortIndex();

					return itemA - itemB;
				});
		}

		/**
		 * @public
		 * @return {{title: string}[]}
		 */
		getChecklistStubs()
		{
			return (this.getConfig().initialState || [{ title: '' }]);
		}

		getChecklists()
		{
			return this.isLoading()
				? this.getChecklistStubs()
				: this.getSortedChecklists();
		}

		render()
		{
			const { ThemeComponent } = this.props;

			if (ThemeComponent)
			{
				return this.props.ThemeComponent({ field: this });
			}

			const checklists = this.getChecklists();
			const collapsed = this.#isCollapsed();
			const visibleChecklists = collapsed
				? checklists.slice(0, this.#getMaxElements())
				: checklists;
			const restCount = checklists.length - this.#getMaxElements();

			return View(
				{
					testId: `${this.testId}_FIELD`,
					ref: this.bindContainerRef,
					style: {
						paddingTop: Indent.XL.toNumber(),
						paddingBottom: collapsed ? 32 : Indent.XL.toNumber(),
					},
					onLayout: () => {
						const { onLayout } = this.props;

						if (onLayout)
						{
							onLayout(this);
						}
					},
				},
				this.renderTitle(visibleChecklists),
				this.renderStubItems(visibleChecklists),
				this.renderItems(visibleChecklists),
				this.renderAddButtons(),
				this.renderMoreButton(restCount),
			);
		}

		renderTitle(checklists)
		{
			const { hideTitle } = this.props;

			if (hideTitle)
			{
				return null;
			}

			return Title({
				loading: this.isLoading(),
				testId: this.testId,
				count: checklists.length,
			});
		}

		renderItems(checklists)
		{
			if (this.isEmpty() || this.isLoading())
			{
				return null;
			}

			return View(
				{
					testId: `${this.testId}_CONTENT`,
				},
				...checklists.map((checklist, index) => {
					const rootItem = checklist.getRootItem();

					return Item({
						testId: this.testId,
						totalCount: rootItem.getTotalCount(),
						completedCount: rootItem.getCompletedCount(),
						title: rootItem.getTitle(),
						isComplete: rootItem.getIsComplete(),
						showBorder: index < (checklists.length - 1),
						onClick: () => this.openPageManager(checklist),
					});
				}),
			);
		}

		renderStubItems(checklists)
		{
			if (!this.isLoading())
			{
				return null;
			}

			return View(
				{
					testId: `${this.testId}_CONTENT`,
				},
				...checklists.map((checklist, index) => ItemStub({
					testId: this.testId,
					title: checklist.title,
					showBorder: index < (checklists.length - 1),
				})),
			);
		}

		renderMoreButton(restCount)
		{
			if (!this.#isCollapsed())
			{
				return null;
			}

			return MoreButton({
				testId: `${this.testId}_SHOW_ALL`,
				text: Loc.getMessage(
					'TASKS_FIELDS_CHECKLIST_AIR_SHOW_MORE',
					{
						'#COUNT#': restCount,
					},
				),
				onClick: () => this.#expand(),
			});
		}

		renderAddButtons()
		{
			const { showAddButton } = this.props;

			if (this.#isCollapsed() || this.isReadOnly() || !showAddButton)
			{
				return null;
			}

			return AddButton({
				testId: this.testId,
				text: Loc.getMessage('TASKS_FIELDS_CHECKLIST_AIR_ADD_CHECKLIST'),
				onClick: this.createChecklist,
				style: {
					paddingHorizontal: Indent.XL2.toNumber(),
				},
			});
		}
	}

	ChecklistPreview.defaultProps = {
		showAddButton: true,
		hideTitle: false,
	};

	ChecklistPreview.propTypes = {
		id: PropTypes.string,
		testId: PropTypes.string,
		value: PropTypes.object,
		readOnly: PropTypes.bool,
		config: PropTypes.shape({
			parentWidget: PropTypes.object,
			checklistController: PropTypes.object,
			initialState: PropTypes.arrayOf(PropTypes.shape({
				title: PropTypes.string,
				completed: PropTypes.number,
				uncompleted: PropTypes.number,
			})),
			taskId: PropTypes.oneOfType([PropTypes.number, PropTypes.string]),
		}),
		showAddButton: PropTypes.bool,
		maxElements: PropTypes.number,
		loading: PropTypes.bool,
		onLayout: PropTypes.func,
	};

	module.exports = {
		ChecklistPreview,
		ClickStrategy,
		/**
		 * @param {ChecklistPreviewProps} props
		 */
		ChecklistField: (props) => new ChecklistPreview(props),
	};
});
