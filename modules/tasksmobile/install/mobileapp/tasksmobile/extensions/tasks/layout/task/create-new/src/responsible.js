/**
 * @module tasks/layout/task/create-new/responsible
 */
jn.define('tasks/layout/task/create-new/responsible', (require, exports, module) => {
	const { Avatar } = require('ui-system/blocks/avatar');
	const { EntitySelectorFactory, EntitySelectorFactoryType } = require('selector/widget/factory');
	const { Color, Indent } = require('tokens');
	const { Loc } = require('tasks/loc');
	const { Haptics } = require('haptics');
	const { UserName } = require('layout/ui/user/user-name');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { showToast, Position } = require('toast');
	const { AnalyticsEvent } = require('analytics');

	const store = require('statemanager/redux/store');
	const { dispatch } = store;
	const { usersAddedFromEntitySelector } = require('statemanager/redux/slices/users');
	const { selectGroupById } = require('tasks/statemanager/redux/slices/groups');

	class Responsible extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.initialResponsibleId = Number(this.props.responsible.id);

			this.initState(props);

			this.onChange = this.onChange.bind(this);
			this.openSelector = this.openSelector.bind(this);
		}

		initState(props)
		{
			const { responsible } = props;

			this.state = {
				id: responsible.id,
				name: responsible.name,
				image: responsible.image,
			};
		}

		componentWillReceiveProps(props)
		{
			this.initState(props);
		}

		isEditable()
		{
			return !this.props.flowId;
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
					testId: 'responsible_CONTENT',
					onClick: this.openSelector,
				},
				this.renderAvatar(),
				this.renderText(),
				this.isEditable() && IconView({
					size: 16,
					style: {
						marginLeft: 2,
					},
					color: Color.base2,
					icon: Icon.CHEVRON_DOWN,
				}),
			);
		}

		renderAvatar()
		{
			const editable = this.isEditable();
			const disabled = !editable;
			const { id: userId } = this.state;

			return View(
				{},
				disabled && View(
					{
						testId: 'responsible_USER_DISABLED_ICON',
						style: {
							width: 24,
							height: 24,
							backgroundColor: Color.bgContentTertiary.toHex(),
							borderRadius: 12,
							justifyContent: 'center',
							alignItems: 'center',
						},
					},
					IconView({
						icon: Icon.PERSON,
						color: Color.base5,
						size: 20,
					}),
				),
				editable && Avatar({
					id: userId,
					size: 24,
					withRedux: true,
					testId: `responsible_USER_${userId}_ICON`,
				}),
			);
		}

		renderText()
		{
			const { id: userId } = this.state;

			let textParams = {
				id: userId,
				color: Color.base2,
				testId: `responsible_USER_${userId}_VALUE`,
			};

			if (!this.isEditable())
			{
				textParams = {
					testId: 'responsible_USER_DISABLED_VALUE',
					text: Loc.getMessage('M_TASKS_RESPONSIBLE_DISABLED_BY_FLOW'),
					color: Color.base4,
					withRedux: false,
				};
			}

			return UserName({
				...textParams,
				style: {
					flexShrink: 1,
					marginLeft: Indent.M.toNumber(),
				},
			});
		}

		onChange(users)
		{
			const user = users[0];
			const newResponsible = {
				id: Number(user.id),
				name: user.title,
				image: (user.imageUrl || ''),
			};

			dispatch(usersAddedFromEntitySelector([user]));

			if (newResponsible.id !== Number(this.state.id))
			{
				this.setState(newResponsible);
				if (this.props.onChange)
				{
					this.props.onChange(newResponsible);
				}
			}
		}

		openSelector()
		{
			if (!this.isEditable())
			{
				Haptics.notifyWarning();

				showToast({
					message: Loc.getMessage('M_TASKS_FIELD_DISABLED_BY_FLOW_EXPLANATION_RESPONSIBLE'),
					position: Position.TOP,
					iconName: Icon.LOCK.getIconName(),
					code: 'fieldDisabledByFlow',
				});

				return;
			}

			const { groupId, onSelectorHidden, parentWidget } = this.props;

			let selectorWidget = null;
			const selector = EntitySelectorFactory.createByType(EntitySelectorFactoryType.USER, {
				provider: {
					context: 'TASKS_MEMBER_SELECTOR_EDIT_responsible',
					options: {
						useLettersForEmptyAvatar: true,
						recentItemsLimit: 10,
						maxUsersInRecentTab: 10,
						searchLimit: 20,
					},
					filters: [
						{
							id: 'tasks.userDataFilter',
							options: {
								groupId,
								role: 'R',
							},
						},
					],
				},
				initSelectedIds: [this.state.id],
				allowMultipleSelection: false,
				selectOptions: {
					canUnselectLast: false,
					getNonSelectableErrorText: (item) => {
						const isCollaber = item.params.entityType === 'collaber';
						const isCollab = selectGroupById(store.getState(), groupId)?.isCollab;

						if (isCollaber && !isCollab)
						{
							return Loc.getMessage('M_TASKS_DENIED_SELECT_COLLABER_WITHOUT_COLLAB');
						}

						return Loc.getMessage('M_TASKS_DENIED_SELECT_USER_AS_RESPONSIBLE');
					},
				},
				createOptions: {
					enableCreation: !(env.isCollaber || env.extranet),
					analytics: new AnalyticsEvent().setSection('tasks'),
					getParentLayout: () => selectorWidget,
				},
				events: {
					onClose: this.onChange,
					onViewHiddenStrict: onSelectorHidden,
				},
				widgetParams: {
					title: Loc.getMessage('TASKSMOBILE_TASK_CREATE_FIELD_RESPONSIBLE_SELECTOR_TITLE'),
					backdrop: {
						onlyMediumPosition: true,
						mediumPositionPercent: 70,
						adoptHeightByKeyboard: false,
					},
				},
			});
			void selector.show({}, parentWidget).then((widget) => {
				selectorWidget = widget;
			});
		}
	}

	module.exports = { Responsible };
});
