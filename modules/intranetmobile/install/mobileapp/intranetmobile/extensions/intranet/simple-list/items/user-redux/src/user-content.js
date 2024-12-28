/**
 * @module intranet/simple-list/items/user-redux/user-content
 */
jn.define('intranet/simple-list/items/user-redux/user-content', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { connect } = require('statemanager/redux/connect');
	const { UserView } = require('intranet/simple-list/items/user-redux/user-view');
	const { selectWholeUserById } = require('intranet/statemanager/redux/slices/employees/selector');
	const { selectCanUserBeReinvited } = require('intranet/statemanager/redux/slices/employees/selector');

	class UserContent extends PureComponent
	{
		shouldComponentUpdate(nextProps, nextState)
		{
			if (this.props.id !== nextProps.id)
			{
				return true;
			}

			return super.shouldComponentUpdate(nextProps, nextState);
		}

		get user()
		{
			return this.props.user;
		}

		render()
		{
			return new UserView({
				...this.user,
				customStyles: this.props.customStyles,
				showBorder: this.props.showBorder,
				canInvite: this.props.canInvite,
			});
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const userId = Number(ownProps.id);
		const user = selectWholeUserById(state, userId);

		if (!user)
		{
			return {};
		}

		const {
			id,
			login,
			isAdmin,
			name,
			lastName,
			secondName,
			fullName,
			link,
			avatarSizeOriginal,
			avatarSize100,
			workPosition,
			personalMobile,
			personalPhone,
			employeeStatus,
			isCollaber,
			isExtranet,
			isExtranetUser,
			isAndroidAppInstalled,
			isIosAppInstalled,
			isWindowsAppInstalled,
			isLinuxAppInstalled,
			isMacAppInstalled,
			department,
			actions,
			requestStatus,
		} = user;

		const isMobileInstalled = isAndroidAppInstalled || isIosAppInstalled;
		const isDesktopInstalled = isWindowsAppInstalled || isLinuxAppInstalled || isMacAppInstalled;
		const canUserBeReinvited = selectCanUserBeReinvited(state, id);

		return {
			user: {
				id,
				login,
				isAdmin,
				employeeStatus,
				isCollaber,
				isExtranet,
				isExtranetUser,
				isMobileInstalled,
				isDesktopInstalled,
				department,
				name,
				lastName,
				secondName,
				fullName,
				workPosition,
				personalMobile,
				personalPhone,
				link,
				avatarSizeOriginal,
				avatarSize100,
				actions,
				requestStatus,
				canUserBeReinvited,
			},
		};
	};

	module.exports = {
		UserContentView: connect(mapStateToProps)(UserContent),
	};
});
