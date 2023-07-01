(() => {
	class Counter
	{
		static get types()
		{
			return {
				expired: {
					expired: 'expired',
					projectExpired: 'projectExpired',
				},
				newComments: {
					newComments: 'newComments',
					projectNewComments: 'projectNewComments',
				},
				my: {
					expired: 'expired',
					newComments: 'newComments',
				},
				project: {
					projectExpired: 'projectExpired',
					projectNewComments: 'projectNewComments',
				},
			};
		}

		static get colors()
		{
			return {
				danger: 'danger',
				success: 'success',
				gray: 'gray',
			};
		}

		constructor(project)
		{
			this.project = project;
			this.members = project._members;

			this.set(this.getDefault());
		}

		getDefault()
		{
			return {
				counters: {
					[Counter.types.my.expired]: 0,
					[Counter.types.my.newComments]: 0,
					[Counter.types.project.projectExpired]: 0,
					[Counter.types.project.projectNewComments]: 0,
				},
				color: Counter.colors.gray,
				value: 0,
				isHidden: false,
			};
		}

		get()
		{
			return {
				counters: this.counters,
				color: this.color,
				value: this.value,
				isHidden: this.isHidden,
			};
		}

		set(counter)
		{
			this.counters = (counter.counters || this.counters);
			this.color = (counter.color || this.color);
			this.value = (counter.value || 0);
			this.isHidden = (counter.isHidden || !this.members.isInProject());
		}

		read()
		{
			Object.keys(this.counters).forEach((type) => {
				if (Counter.types.newComments[type])
				{
					this.value -= this.counters[type];
					this.counters[type] = 0;
				}
			});
			this.value = (this.value < 0 ? 0 : this.value);
			this.color = (
				this.counters[Counter.types.expired.expired] > 0 ? Counter.colors.danger : Counter.colors.gray
			);
		}

		getNewCommentsCount()
		{
			let count = 0;

			Object.keys(this.counters).forEach((type) => {
				if (Counter.types.newComments[type])
				{
					count += this.counters[type];
				}
			});

			return count;
		}
	}

	class Member
	{
		constructor(project)
		{
			this.project = project;
			this.set(this.getDefault());
		}

		getDefault()
		{
			return {
				heads: {},
				members: {},
			};
		}

		get()
		{
			return {
				heads: this.heads,
				members: this.members,
			};
		}

		set(members)
		{
			this.heads = (members.heads || this.heads || {});
			this.members = (members.members || this.members || {});
		}

		getHeadIcons()
		{
			const icons = [];

			Object.values(this.heads)
				.filter(user => user.isOwner === 'N')
				.slice(0, 2)
				.forEach(user => icons.push(user.photo))
			;

			const owner = Object.values(this.heads).filter(user => user.isOwner === 'Y')[0];
			if (owner)
			{
				icons.splice(0, 0, owner.photo);
			}

			return icons;
		}

		getMemberIcons()
		{
			const icons = [];

			Object.values(this.members)
				.filter(user => user.isAccessRequesting === 'N')
				.slice(0, 5)
				.forEach(user => icons.push(user.photo))
			;

			return icons;
		}

		getHeadCount()
		{
			return Object.keys(this.heads).length;
		}

		getMemberCount()
		{
			return Object.values(this.members).filter(user => user.isAccessRequesting === 'N').length;
		}

		isOwner()
		{
			const owner = Object.values(this.heads).filter(user => user.isOwner === 'Y')[0];
			return (owner && Number(owner.id) === Number(this.project.userId));
		}

		isHead()
		{
			return Object.values(this.heads).find(user => Number(user.id) === Number(this.project.userId));
		}

		isMember()
		{
			return Object.values(this.members)
				.filter(user => user.isAccessRequesting === 'N')
				.find(user => user.id === this.project.userId)
			;
		}

		isInProject()
		{
			return this.isHead() || this.isMember();
		}
	}

	class Action
	{
		static get types()
		{
			return {
				about: 'about',
				edit: 'edit',
				delete: 'delete',
				members: 'members',
				invite: 'invite',
				join: 'join',
				leave: 'leave',
				pin: 'pin',
				unpin: 'unpin',
				read: 'read',
			};
		}

		/**
		 * @param {Project} project
		 */
		constructor(project)
		{
			this.project = project;
			this.counter = project._counter;

			this.set(this.getDefault());
		}

		getDefault()
		{
			return {
				[Action.types.edit]: false,
				[Action.types.delete]: false,
				[Action.types.invite]: false,
				[Action.types.join]: false,
				[Action.types.leave]: false,
			};
		}

		get()
		{
			return {
				[Action.types.about]: true,
				[Action.types.edit]: this.canEdit,
				[Action.types.delete]: this.canDelete,
				[Action.types.members]: true,
				[Action.types.invite]: this.canInvite,
				[Action.types.join]: this.canJoin,
				[Action.types.leave]: this.canLeave,
				[Action.types.pin]: !this.project.isPinned,
				[Action.types.unpin]: this.project.isPinned,
				[Action.types.read]: true,
			};
		}

		set(actions)
		{
			this.canEdit = (actions.edit || false);
			this.canDelete = (actions.delete || false);
			this.canInvite = (actions.invite || false);
			this.canJoin = (actions.join || false);
			this.canLeave = (actions.leave || false);
		}

		pin()
		{
			this.project.isPinned = true;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.project.pin', {projectId: this.project.id}))
					.call()
					.then(
						(response) => resolve(response),
						(response) => {
							console.error(response);
							reject(response);
						}
					)
				;
			});
		}

		unpin()
		{
			this.project.isPinned = false;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.project.unpin', {projectId: this.project.id}))
					.call()
					.then(
						(response) => resolve(response),
						(response) => {
							console.error(response);
							reject(response);
						}
					)
				;
			});
		}

		read()
		{
			this.counter.read();

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.viewedGroup.project.markAsRead', {
					fields: {
						groupId: this.project.id,
					}
				}))
					.call()
					.then(
						(response) => resolve(response),
						(response) => {
							console.error(response);
							reject(response);
						}
					)
				;
			});
		}

		join()
		{
			this.canJoin = false;
			this.canLeave = true;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('socialnetwork.api.usertogroup.join', {
					params: {
						groupId: this.project.id,
					},
				}))
					.call()
					.then(
						response => resolve(response),
						response => reject(response)
					)
				;
			});
		}

		leave()
		{
			this.canJoin = true;
			this.canLeave = false;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('socialnetwork.api.usertogroup.leave', {
					params: {
						groupId: this.project.id,
					},
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

	class Project
	{
		static get userOptions()
		{
			return {
				pinned: 2,
			};
		}

		static get types()
		{
			return {
				public: 'public',
				private: 'private',
				secret: 'secret',
				extranet: 'extranet',
			};
		}

		constructor(userId)
		{
			this.userId = userId;

			this._members = new Member(this);
			this._counter = new Counter(this);
			this._actions = new Action(this);

			this.setDefaultData();
		}

		setDefaultData()
		{
			this.id = `tmp-id-${(new Date()).getTime()}`;
			this.name = '';
			this.image = '';

			this.userGroupId = 0;
			this.numberOfMembers = 0;
			this.numberOfModerators = 0;

			this.isPinned = false;
			this.isClosed = false;
			this.isOpened = false;
			this.isVisible = false;
			this.isExtranet = false;

			this.members = {};
			this.counter = {};
			this.actions = {};

			this.activityDate = null;

			this.additionalData = {};
		}

		setData(row)
		{
			this.id = Number(row.id);
			this.name = row.name;
			this.image = row.image;

			this.numberOfMembers = Number(row.numberOfMembers);
			this.numberOfModerators = Number(row.numberOfModerators);

			this.isPinned = (row.isPinned === 'Y');
			this.isClosed = (row.closed === 'Y');
			this.isOpened = (row.opened === 'Y');
			this.isVisible = (row.visible === 'Y');
			this.isExtranet = (row.isExtranet === 'Y');

			this.members = row.members;
			this.counter = row.counter;
			this.actions = row.actions;

			const activityDate = Date.parse(row.activityDate);
			this.activityDate = (activityDate > 0 ? activityDate : null);

			this.additionalData = row.additionalData;
		}

		updateData(row)
		{
			const has = Object.prototype.hasOwnProperty;

			if (has.call(row, 'id'))
			{
				this.id = Number(row.id);
			}
			if (has.call(row, 'name'))
			{
				this.name = row.name;
			}
			if (has.call(row, 'image'))
			{
				this.image = row.image;
			}

			if (has.call(row, 'numberOfMembers'))
			{
				this.numberOfMembers = Number(row.numberOfMembers);
			}
			if (has.call(row, 'numberOfModerators'))
			{
				this.numberOfModerators = Number(row.numberOfModerators);
			}

			if (has.call(row, 'isPinned'))
			{
				this.isPinned = (row.isPinned === 'Y');
			}
			if (has.call(row, 'closed'))
			{
				this.isClosed = (row.closed === 'Y');
			}
			if (has.call(row, 'opened'))
			{
				this.isOpened = (row.opened === 'Y');
			}
			if (has.call(row, 'visible'))
			{
				this.isVisible = (row.visible === 'Y');
			}
			if (has.call(row, 'isExtranet'))
			{
				this.isExtranet = (row.isExtranet === 'Y');
			}

			if (has.call(row, 'members'))
			{
				this.members = row.members;
			}
			if (has.call(row, 'counter'))
			{
				this.counter = row.counter;
			}
			if (has.call(row, 'actions'))
			{
				this.actions = row.actions;
			}

			if (has.call(row, 'activityDate'))
			{
				const activityDate = Date.parse(row.activityDate);
				this.activityDate = (activityDate > 0 ? activityDate : null);
			}

			if (has.call(row, 'additionalData'))
			{
				this.additionalData = row.additionalData;
			}
		}

		open()
		{
			PageManager.openPage({
				cache: false,
				url: `/mobile/log/?group_id=${this.id}`,
			});
		}

		getType()
		{
			if (this.isExtranet)
			{
				return Project.types.extranet;
			}

			if (this.isVisible)
			{
				if (this.isOpened)
				{
					return Project.types.public;
				}

				return Project.types.private;
			}

			return Project.types.secret;
		}

		get counter()
		{
			return this._counter.get();
		}

		set counter(counter)
		{
			this._counter.set(counter);
		}

		getCounterValue()
		{
			return this.counter.value;
		}

		getCounterColor()
		{
			return this.counter.color;
		}

		getCounterIsHidden()
		{
			return this.counter.isHidden;
		}

		getCounterMyExpiredCount()
		{
			return this.counter.counters[Counter.types.my.expired];
		}

		getCounterMyNewCommentsCount()
		{
			return this.counter.counters[Counter.types.my.newComments];
		}

		getCounterProjectExpiredCount()
		{
			return this.counter.counters[Counter.types.project.projectExpired];
		}

		getCounterProjectNewCommentsCount()
		{
			return this.counter.counters[Counter.types.project.projectNewComments];
		}

		getNewCommentsCount()
		{
			return this._counter.getNewCommentsCount();
		}

		get actions()
		{
			return this._actions.get();
		}

		set actions(actions)
		{
			this._actions.set(actions);
		}

		pin()
		{
			return this._actions.pin();
		}

		unpin()
		{
			return this._actions.unpin();
		}

		read()
		{
			return this._actions.read();
		}

		join()
		{
			return this._actions.join();
		}

		leave()
		{
			return this._actions.leave();
		}

		pseudoRead()
		{
			return this._counter.read();
		}

		get members()
		{
			return this._members.get();
		}

		set members(members)
		{
			this._members.set(members);
		}

		getHeadIcons()
		{
			return this._members.getHeadIcons();
		}

		getMemberIcons()
		{
			return this._members.getMemberIcons();
		}

		getHeadCount()
		{
			return this._members.getHeadCount();
		}

		getMemberCount()
		{
			return this._members.getMemberCount();
		}

		isOwner()
		{
			return this._members.isOwner();
		}
	}

	jnexport([Project, 'Project']);
})();
