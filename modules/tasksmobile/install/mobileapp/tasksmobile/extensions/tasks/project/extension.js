/**
 * @module tasks/project
 */
jn.define('tasks/project', (require, exports, module) => {
	const { ErrorLogger } = require('utils/logger/error-logger');

	const logger = new ErrorLogger();

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

		static getDefaultData()
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

		/**
		 * @param {Project} project
		 */
		constructor(project)
		{
			this.project = project;
			this.members = project.members;

			this.set(Counter.getDefaultData());
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
		static getDefaultData()
		{
			return {
				heads: {},
				members: {},
			};
		}

		/**
		 * @param {Project} project
		 */
		constructor(project)
		{
			this.project = project;

			this.set(Member.getDefaultData());
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
				.filter((user) => user.isOwner === 'N')
				.slice(0, 2)
				.forEach((user) => icons.push(user.photo))
			;

			const owner = Object.values(this.heads).find((user) => user.isOwner === 'Y');
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
				.filter((user) => user.isAccessRequesting === 'N')
				.slice(0, 5)
				.forEach((user) => icons.push(user.photo))
			;

			return icons;
		}

		getHeadCount()
		{
			return Object.keys(this.heads).length;
		}

		getMemberCount()
		{
			return Object.values(this.members).filter((user) => user.isAccessRequesting === 'N').length;
		}

		isOwner()
		{
			const owner = Object.values(this.heads).find((user) => user.isOwner === 'Y');

			return (owner && Number(owner.id) === Number(this.project.userId));
		}

		isHead()
		{
			return Object.values(this.heads).find((user) => Number(user.id) === Number(this.project.userId));
		}

		isMember()
		{
			return Object.values(this.members)
				.filter((user) => user.isAccessRequesting === 'N')
				.find((user) => user.id === this.project.userId)
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

		static getDefaultData()
		{
			return {
				[Action.types.edit]: false,
				[Action.types.delete]: false,
				[Action.types.invite]: false,
				[Action.types.join]: false,
				[Action.types.leave]: false,
			};
		}

		/**
		 * @param {Project} project
		 */
		constructor(project)
		{
			this.project = project;
			this.counter = project.counter;

			this.set(Action.getDefaultData());
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

		pin(mode)
		{
			this.project.isPinned = true;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasksmobile.Project.pin', {
					projectId: this.project.id,
					mode,
				}))
					.call()
					.then(
						(response) => resolve(response),
						(response) => {
							logger.error(response);
							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		unpin(mode)
		{
			this.project.isPinned = false;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasksmobile.Project.unpin', {
					projectId: this.project.id,
					mode,
				}))
					.call()
					.then(
						(response) => resolve(response),
						(response) => {
							logger.error(response);
							reject(response);
						},
					)
					.catch((response) => reject(response))
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
					},
				}))
					.call()
					.then(
						(response) => resolve(response),
						(response) => {
							logger.error(response);
							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		joinProject()
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
						(response) => resolve(response),
						(response) => reject(response),
					)
					.catch((response) => reject(response))
				;
			});
		}

		leaveProject()
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
						(response) => resolve(response),
						(response) => reject(response),
					)
					.catch((response) => reject(response))
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

			this.members = new Member(this);
			this.counter = new Counter(this);
			this.actions = new Action(this);

			this.setDefaultData();
		}

		setDefaultData()
		{
			this.id = `tmp-id-${Date.now()}`;
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

			this.setMembers({});
			this.setCounter({});
			this.setActions({});

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

			this.setMembers(row.members);
			this.setCounter(row.counter);
			this.setActions(row.actions);

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
				this.setMembers(row.members);
			}

			if (has.call(row, 'counter'))
			{
				this.setCounter(row.counter);
			}

			if (has.call(row, 'actions'))
			{
				this.setActions(row.actions);
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

		getCounter()
		{
			return this.counter.get();
		}

		setCounter(counter)
		{
			this.counter.set(counter);
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
			return this.counter.getNewCommentsCount();
		}

		getActions()
		{
			return this.actions.get();
		}

		setActions(actions)
		{
			this.actions.set(actions);
		}

		pin(mode)
		{
			return this.actions.pin(mode);
		}

		unpin(mode)
		{
			return this.actions.unpin(mode);
		}

		read()
		{
			return this.actions.read();
		}

		joinProject()
		{
			return this.actions.joinProject();
		}

		leaveProject()
		{
			return this.actions.leaveProject();
		}

		pseudoRead()
		{
			return this.counter.read();
		}

		getMembers()
		{
			return this.members.get();
		}

		setMembers(members)
		{
			this.members.set(members);
		}

		getHeadIcons()
		{
			return this.members.getHeadIcons();
		}

		getMemberIcons()
		{
			return this.members.getMemberIcons();
		}

		getHeadCount()
		{
			return this.members.getHeadCount();
		}

		getMemberCount()
		{
			return this.members.getMemberCount();
		}

		isOwner()
		{
			return this.members.isOwner();
		}
	}

	module.exports = { Project };
});
