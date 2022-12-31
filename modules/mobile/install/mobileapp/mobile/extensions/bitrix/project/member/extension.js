(() => {
	const { ProfileView } = jn.require("user/profile");
	class Action
	{
		static get types()
		{
			return {
				setOwner: 'setOwner',
				setModerator: 'setModerator',
				removeModerator: 'removeModerator',
				appoint: 'appoint',
				exclude: 'exclude',
				repeatInvite: 'repeatInvite',
				cancelInvite: 'cancelInvite',
				acceptRequest: 'acceptRequest',
				denyRequest: 'denyRequest',
			};
		}

		/**
		 * @param {ProjectMember} member
		 */
		constructor(member)
		{
			this.member = member;

			this.set(this.getDefault());
		}

		getDefault()
		{
			return {
				[Action.types.setOwner]: false,
				[Action.types.setModerator]: false,
				[Action.types.removeModerator]: false,
				[Action.types.appoint]: false,
				[Action.types.exclude]: false,
				[Action.types.repeatInvite]: false,
				[Action.types.cancelInvite]: false,
				[Action.types.acceptRequest]: false,
				[Action.types.denyRequest]: false,
			};
		}

		get()
		{
			return {
				[Action.types.setOwner]: this.canSetOwner,
				[Action.types.setModerator]: this.canSetModerator,
				[Action.types.removeModerator]: this.canRemoveModerator,
				[Action.types.appoint]: (this.canSetOwner || this.canSetModerator || this.canRemoveModerator),
				[Action.types.exclude]: this.canExclude,
				[Action.types.repeatInvite]: this.canRepeatInvite,
				[Action.types.cancelInvite]: this.canCancelInvite,
				[Action.types.acceptRequest]: this.canAcceptRequest,
				[Action.types.denyRequest]: this.canDenyRequest,
			};
		}

		set(actions)
		{
			this.canSetOwner = (actions.setOwner || false);
			this.canSetModerator = (actions.setModerator || false);
			this.canRemoveModerator = (actions.removeModerator || false);
			this.canExclude = (actions.exclude || false);
			this.canRepeatInvite = (actions.repeatInvite || false);
			this.canCancelInvite = (actions.cancelInvite || false);
			this.canAcceptRequest = (actions.acceptRequest || false);
			this.canDenyRequest = (actions.denyRequest || false);
		}

		setOwner()
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('socialnetwork.api.usertogroup.setowner', {
					userId: this.member.id,
					groupId: this.member.groupId,
				}))
					.call()
					.then(
						response => resolve(response),
						response => reject(response)
					)
				;
			});
		}

		setModerator()
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('socialnetwork.api.usertogroup.setmoderator', {
					userId: this.member.id,
					groupId: this.member.groupId,
				}))
					.call()
					.then(
						response => resolve(response),
						response => reject(response)
					)
				;
			});
		}

		removeModerator()
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('socialnetwork.api.usertogroup.removemoderator', {
					userId: this.member.id,
					groupId: this.member.groupId,
				}))
					.call()
					.then(
						response => resolve(response),
						response => reject(response)
					)
				;
			});
		}

		exclude()
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('socialnetwork.api.usertogroup.exclude', {
					userId: this.member.id,
					groupId: this.member.groupId,
				}))
					.call()
					.then(
						response => resolve(response),
						response => reject(response)
					)
				;
			});
		}

		repeatInvite()
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('socialnetwork.api.usertogroup.repeatinvite', {
					userId: this.member.id,
					groupId: this.member.groupId,
				}))
					.call()
					.then(
						response => resolve(response),
						response => reject(response)
					)
				;
			});
		}

		cancelInvite()
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('socialnetwork.api.usertogroup.cancelinvite', {
					userId: this.member.id,
					groupId: this.member.groupId,
				}))
					.call()
					.then(
						response => resolve(response),
						response => reject(response)
					)
				;
			});
		}

		acceptRequest()
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('socialnetwork.api.usertogroup.acceptrequest', {
					relationId: this.member.userGroupId,
					groupId: this.member.groupId,
				}))
					.call()
					.then(
						response => resolve(response),
						response => reject(response)
					)
				;
			});
		}

		denyRequest()
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('socialnetwork.api.usertogroup.denyrequest', {
					relationId: this.member.userGroupId,
					groupId: this.member.groupId,
				}))
					.call()
					.then(
						response => resolve(response),
						response => reject(response)
					)
				;
			});
		}
	}

	class ProjectMember
	{
		static get roles()
		{
			return {
				owner: 'A',
				moderator: 'E',
				member: 'K',
				request: 'Z',
			};
		}

		static get requestInitiatingType()
		{
			return {
				user: 'U',
				group: 'G',
			};
		}

		constructor(currentUserId)
		{
			this.currentUserId = currentUserId;

			this._actions = new Action(this);

			this.setDefaultData();
		}

		setDefaultData()
		{
			this.id = `tmp-id-${(new Date()).getTime()}`;
			this.name = '';
			this.image = '';

			this.groupId = 0;
			this.userGroupId = 0;

			this.role = false;
			this.requestInitiatedType = false;

			this.isAutoMember = false;
			this.workPosition = '';

			this.actions = {};
		}

		setData(row)
		{
			this.id = Number(row.userId);
			this.name = row.formattedUserName;
			this.image = row.image;

			this.groupId = row.groupId;
			this.userGroupId = row.id;

			this.role = row.role;
			this.requestInitiatedType = row.initiatedByType;

			this.isAutoMember = (row.autoMember === 'Y');
			this.workPosition = row.userWorkPosition;

			this.actions = row.actions;
		}

		updateData(row)
		{
			const has = Object.prototype.hasOwnProperty;

			if (has.call(row, 'id'))
			{
				this.id = Number(row.id);
			}
			if (has.call(row, 'formattedUserName'))
			{
				this.name = row.formattedUserName;
			}
			if (has.call(row, 'image'))
			{
				this.image = row.image;
			}

			if (has.call(row, 'groupId'))
			{
				this.groupId = Number(row.groupId);
			}
			if (has.call(row, 'userGroupId'))
			{
				this.userGroupId = Number(row.userGroupId);
			}

			if (has.call(row, 'role'))
			{
				this.role = row.role;
			}
			if (has.call(row, 'initiatedByType'))
			{
				this.requestInitiatedType = row.initiatedByType;
			}

			if (has.call(row, 'autoMember'))
			{
				this.isAutoMember = (row.autoMember === 'Y');
			}

			if (has.call(row, 'userWorkPosition'))
			{
				this.workPosition = row.userWorkPosition;
			}

			if (has.call(row, 'actions'))
			{
				this.actions = row.actions;
			}
		}

		open(parentWidget = null)
		{
			const widget = (parentWidget || PageManager);

			if (Application.getApiVersion() >= 27)
			{
				widget.openWidget('list', {
					groupStyle: true,
					backdrop: {
						bounceEnable: false,
						swipeAllowed: true,
						showOnTop: true,
						hideNavigationBar: false,
						horizontalSwipeAllowed: false,
					},
					onReady: list => ProfileView.open({userId: this.id, isBackdrop: true}, list),
					onError: error => console.log(error),
				});
			}
			else
			{
				widget.openPage({url: `/mobile/users/?user_id=${this.id}`});
			}
		}

		isOwner()
		{
			return (this.role === ProjectMember.roles.owner);
		}

		isModerator()
		{
			return (this.role === ProjectMember.roles.moderator);
		}

		isMember()
		{
			return (this.role === ProjectMember.roles.member);
		}

		isAccessRequesting()
		{
			return (this.role === ProjectMember.roles.request);
		}

		isAccessRequestingByMe()
		{
			return (this.isAccessRequesting() && this.requestInitiatedType === ProjectMember.requestInitiatingType.user);
		}

		get actions()
		{
			return this._actions.get();
		}

		set actions(actions)
		{
			this._actions.set(actions);
		}

		setOwner()
		{
			return this._actions.setOwner();
		}

		setModerator()
		{
			return this._actions.setModerator();
		}

		removeModerator()
		{
			return this._actions.removeModerator();
		}

		exclude()
		{
			return this._actions.exclude();
		}

		repeatInvite()
		{
			return this._actions.repeatInvite();
		}

		cancelInvite()
		{
			return this._actions.cancelInvite();
		}

		acceptRequest()
		{
			return this._actions.acceptRequest();
		}

		denyRequest()
		{
			return this._actions.denyRequest();
		}
	}

	jnexport([ProjectMember, 'ProjectMember']);
})();