/**
 * @module bizproc/workflow/timeline/components/step/user-list
 * */

jn.define('bizproc/workflow/timeline/components/step/user-list', (require, exports, module) => {
	const { animate, parallel } = require('animation');
	const AppTheme = require('apptheme');
	const { check, cross } = require('bizproc/workflow/timeline/icons');
	const { PureComponent } = require('layout/pure-component');
	const { SafeImage } = require('layout/ui/safe-image');
	const { ReduxAvatar } = require('layout/ui/user/avatar');
	const { Type } = require('type');
	const { ProfileView } = require('user/profile');

	const UsersState = {
		HIDDEN: 'hidden',
		SHOWN: 'shown',
	};

	class UserList extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.shouldShowUserStatus = this.getUsers().length > 1;
			this.usersContainerRef = null;
			this.usersRef = null;

			this.state = {
				usersState: this.props.shouldHideUsers === true ? UsersState.HIDDEN : UsersState.SHOWN,
			};
		}

		/**
		 * @return {Array<{
		 *     	id: number,
		 * 		testId: ?string,
		 * 		fullName: string,
		 * 		workPosition: ?string,
		 * 		status: number,
		 * }>}
		 */
		getUsers()
		{
			const users = Type.isArray(this.props.users) ? this.props.users : [];

			return users.filter((user) => Type.isObjectLike(user) && user.id);
		}

		/**
		 * @return {number}
		 */
		get usersHeight()
		{
			return this.getUsers().length * 46;
		}

		render()
		{
			return View(
				{
					testId: this.props.testId,
					ref: (ref) => {
						this.usersContainerRef = ref;
					},
					style: {
						position: 'relative',
						height: this.isUsersHidden() ? this.usersHeight : null,
						opacity: this.isUsersHidden() ? 0 : null,
						paddingBottom: 2,
					},
				},
				View(
					{
						ref: (ref) => {
							this.usersRef = ref;
						},
						style: {
							position: this.isUsersHidden() ? 'absolute' : 'relative',
							top: this.isUsersHidden() ? -this.usersHeight : null,
							left: 0,
							right: 0,
						},
					},
					...this.getUsers().map((user) => this.renderUser(user)),
				),
			);
		}

		/**
		 * @param {{
		 * 		id: number,
		 * 		testId: ?string,
		 * 		fullName: string,
		 * 		workPosition: ?string,
		 * 		status: number,
		 * 	}} user
		 * @return {View}
		 */
		renderUser(user)
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						paddingBottom: 6,
					},
					onClick: () => this.openUserProfile(user.id),
				},
				ReduxAvatar({
					id: user.id,
				}),
				View(
					{
						style: {
							flex: 1,
							flexDirection: 'column',
							marginHorizontal: 6,
						},
					},
					View(
						{
							style: {
								flexDirection: 'row',
								alignItems: 'center',
							},
						},
						Text({
							testId: user.testId,
							style: {
								textAlign: 'center',
								fontSize: 15,
								fontWeight: '400',
								color: AppTheme.colors.accentMainLinks,
							},
							numberOfLines: 1,
							text: user.fullName,
						}),
						this.shouldShowUserStatus && user.status && this.renderStatus(user),
					),
					user.workPosition && Text({
						style: {
							fontSize: 13,
							fontWeight: '400',
							color: AppTheme.colors.base4,
						},
						numberOfLines: 1,
						ellipsize: 'end',
						text: user.workPosition,
					}),
				),
			);
		}

		/**
		 * @param {{
		 *     	id: number,
		 * 		testId: ?string,
		 * 		fullName: string,
		 * 		workPosition: ?string,
		 * 		status: number,
		 * }} user
		 */
		renderStatus(user)
		{
			const isAccepted = user.status === 1 || user.status === 3;
			const isDeclined = user.status === 2 || user.status === 4;

			if (!isAccepted && !isDeclined)
			{
				return null;
			}

			const icon = (
				isAccepted
					? check({ color: AppTheme.colors.accentMainSuccess })
					: cross({ color: AppTheme.colors.base4 })
			);

			return View(
				{
					style: {
						marginLeft: 8,
						borderRadius: 4,
						borderStyle: 'solid',
						borderWidth: 1,
						borderColor: isAccepted ? AppTheme.colors.accentMainSuccess : AppTheme.colors.base4,
					},
				},
				SafeImage({
					style: {
						width: 16,
						height: 16,
					},
					resizeMode: 'contain',
					placeholder: {
						content: icon,
					},
				}),
			);
		}

		/**
		 * @param {number} userId
		 */
		openUserProfile(userId)
		{
			this
				.props
				.layout
				.openWidget('list', {
					groupStyle: true,
					backdrop: {
						bounceEnable: false,
						swipeAllowed: true,
						showOnTop: true,
						hideNavigationBar: false,
						horizontalSwipeAllowed: false,
					},
				})
				.then((list) => ProfileView.open({ userId, isBackdrop: true }, list))
				.catch((err) => console.error(err))
			;
		}

		showUsers()
		{
			if (this.isUsersShown())
			{
				return;
			}

			const usersCount = this.getUsers().length;

			const runAnimations = parallel(
				() => animate(this.usersContainerRef, {
					duration: usersCount * 250,
					height: usersCount * 46,
					opacity: 1,
				}),
				() => animate(this.usersRef, {
					duration: usersCount * 250,
					top: 0,
				}),
			);

			runAnimations()
				.then(() => this.setState({ usersState: UsersState.SHOWN }))
				.catch((err) => {
					console.error(err);
					this.setState({ usersState: UsersState.SHOWN });
				})
			;
		}

		isUsersHidden()
		{
			return this.state.usersState === UsersState.HIDDEN;
		}

		isUsersShown()
		{
			return this.state.usersState === UsersState.SHOWN;
		}
	}

	module.exports = {
		/**
		 * @param {{
		 * 		eventEmitterUid: ?string,
		 * 		layout: any,
		 * 		shouldHideUsers: boolean,
		 * 	 	users: Array<{
		 * 	 	  id: number,
		 * 	 	  testId: ?string,
		 * 	 	  fulleName: string,
		 * 	 	  workPosition: ?string,
		 * 	 	  status: number,
		 * 	 	}>,
		 * 	 	testId: ?string,
		 * 	}} props
		 * @return {UserList}
		 */
		UserList: (props) => new UserList(props),
	};
});
