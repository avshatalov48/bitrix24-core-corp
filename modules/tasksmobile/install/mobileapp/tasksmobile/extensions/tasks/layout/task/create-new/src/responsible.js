/**
 * @module tasks/layout/task/create-new/responsible
 */
jn.define('tasks/layout/task/create-new/responsible', (require, exports, module) => {
	const { Avatar } = require('layout/ui/user/avatar');
	const { EmptyAvatar } = require('layout/ui/user/empty-avatar');
	const { Text4 } = require('ui-system/typography/text');
	const { EntitySelectorFactory, EntitySelectorFactoryType } = require('selector/widget/factory');
	const { Color, Indent } = require('tokens');
	const { Loc } = require('tasks/loc');
	const { Haptics } = require('haptics');
	const { Icon } = require('assets/icons');
	const { showToast, Position } = require('toast');
	const { AnalyticsEvent } = require('analytics');

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
				this.isEditable() && Image({
					style: {
						width: 13,
						height: 13,
						marginLeft: 2,
					},
					tintColor: Color.base2.toHex(),
					named: 'chevron_down',
				}),
			);
		}

		renderAvatar()
		{
			const editable = this.isEditable();
			const disabled = !editable;

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
					Image({
						named: 'person',
						tintColor: Color.base4.toHex(),
						style: {
							width: 14,
							height: 14,
						},
					}),
				),
				editable && this.state.image && Avatar({
					id: this.state.id,
					name: this.state.name,
					image: this.state.image,
					size: 24,
					testId: `responsible_USER_${this.state.id}_ICON`,
				}),
				editable && !this.state.image && EmptyAvatar({
					id: this.state.id,
					name: this.state.name,
					size: 24,
					testId: `responsible_USER_${this.state.id}_LETTERS_ICON`,
				}),
			);
		}

		renderText()
		{
			if (!this.isEditable())
			{
				return Text4({
					testId: 'responsible_USER_DISABLED_VALUE',
					text: Loc.getMessage('M_TASKS_RESPONSIBLE_DISABLED_BY_FLOW'),
					color: Color.base4,
					style: {
						flexShrink: 1,
						marginLeft: Indent.M.toNumber(),
					},
				});
			}

			return Text4({
				testId: `responsible_USER_${this.state.id}_VALUE`,
				text: this.state.name,
				color: Color.base2,
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
								role: 'R',
								groupId: this.props.groupId,
							},
						},
					],
				},
				initSelectedIds: [this.state.id],
				allowMultipleSelection: false,
				selectOptions: {
					canUnselectLast: false,
					nonSelectableErrorText: Loc.getMessage(
						'M_TASKS_DENIED_SELECT_USER_AS_RESPONSIBLE',
					),
				},
				createOptions: {
					enableCreation: true,
					analytics: new AnalyticsEvent().setSection('task'),
					getParentLayout: () => selectorWidget,
				},
				events: {
					onClose: this.onChange,
					onViewHiddenStrict: this.props.onSelectorHidden,
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
			void selector.show({}, this.props.parentWidget).then((widget) => {
				selectorWidget = widget;
			});
		}
	}

	module.exports = { Responsible };
});
