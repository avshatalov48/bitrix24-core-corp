/**
 * @module intranet/layout/user-selector-card
 */

jn.define('intranet/layout/user-selector-card', (require, exports, module) => {
	const { Loc } = require('loc');
	const { InputDesign, InputSize, InputMode, Input } = require('ui-system/form/inputs/input');
	const { SocialNetworkUserSelector } = require('selector/widget/entity/socialnetwork/user');
	const { usersSelector, usersUpserted } = require('statemanager/redux/slices/users');
	const { usersUpserted: intranetUsersUpserted } = require('intranet/statemanager/redux/slices/employees');
	const { batchActions } = require('statemanager/redux/batched-actions');
	const store = require('statemanager/redux/store');
	const { dispatch } = store;

	class UserSelectorCard extends LayoutComponent
	{
		/**
		 * @param {Object} props
		 * @param {Object} [props.user]
		 * @param {Number} [props.user.id]
		 * @param {String} [props.user.title]
		 * @param {String} [props.title]
		 * @param {Object} [props.parentWidget]
		 * @param {Object} [props.userProviderOptions]
		 * @param {Function} [props.onViewHidden]
		 * @param {Function} [props.onChange]
		 */
		constructor(props) {
			super(props);

			this.parentWidget = props.parentWidget ?? PageManager;
			this.defaultUser = null;

			this.state = {
				selectedUser: this.getDefaultUser(),
			};
		}

		componentDidMount()
		{
			super.componentDidMount();
			this.fetchDefaultUser(env.userId);
		}

		getDefaultUser()
		{
			const userId = env.userId;

			const defaultUser = usersSelector.selectById(store.getState(), userId);

			if (defaultUser)
			{
				this.defaultUser = {
					id: defaultUser.id,
					title: defaultUser.fullName,
				};

				return this.defaultUser;
			}

			return null;
		}

		fetchDefaultUser(userId)
		{
			BX.ajax.runAction('intranetmobile.employees.getUsersByIds', {
				data: {
					ids: [userId],
				},
			}).then((result) => {
				if (result.status !== 'success')
				{
					throw new Error(result.errors[0]);
				}

				const { items: mainInfo, users: intranetInfo } = result.data;
				this.saveUserToRedux(mainInfo, intranetInfo);

				this.defaultUser = {
					id: mainInfo[0].id,
					title: mainInfo[0].fullName,
				};

				if (!this.state.selectedUser)
				{
					this.setState({ selectedUser: this.defaultUser });
				}
			}).catch((result) => {
				console.error(result);
			});
		}

		saveUserToRedux(mainInfo, intranetInfo)
		{
			const actions = [usersUpserted(mainInfo), intranetUsersUpserted(intranetInfo)];
			dispatch(batchActions(actions));
		}

		render()
		{
			return Input({
				value: this.state.selectedUser?.title ?? '',
				label: this.props.title ?? Loc.getMessage('M_INTRANET_DEPARTMENT_SELECTOR_CARD_DEFAULT_TITLE'),
				size: InputSize.L,
				design: InputDesign.PRIMARY,
				mode: InputMode.NAKED,
				align: 'left',
				dropdown: true,
				onDropdown: this.openUserSelector,
				onFocus: this.openUserSelector,
			});
		}

		openUserSelector = () => {
			SocialNetworkUserSelector.make({
				provider: {
					options: this.props.userProviderOptions ?? {},
				},
				initSelectedIds: this.state.selectedUser?.id ? [this.state.selectedUser.id] : null,
				widgetParams: {
					backdrop: {
						mediumPositionPercent: 70,
						horizontalSwipeAllowed: false,
					},
				},
				allowMultipleSelection: false,
				closeOnSelect: true,
				events: {
					onClose: this.onChange,
					onViewHidden: this.onViewHidden,
				},
			}).show({}, this.parentWidget);
		};

		onViewHidden = () => {
			this.props.onViewHidden();
		};

		onChange = (users) => {
			this.setState({
				selectedUser: users.pop() ?? this.getDefaultUser(),
			}, () => {
				this.props.onChange?.(this.state.selectedUser.id);
			});
		};
	}

	UserSelectorCard.propTypes = {
		title: PropTypes.string,
		parentWidget: PropTypes.object,
		userProviderOptions: PropTypes.object,
		onViewHidden: PropTypes.func,
		onChange: PropTypes.func,
	};

	module.exports = { UserSelectorCard };
});
