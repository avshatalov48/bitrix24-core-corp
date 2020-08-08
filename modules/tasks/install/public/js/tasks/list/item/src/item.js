class Item
{
	static get statusList()
	{
		return {
			pending: 2,
			inProgress: 3,
			waitCtrl: 4,
			completed: 5,
			deferred: 6,
		};
	}

	static get counterColors()
	{
		return {
			green: '#9DCF00',
			red: '#FF5752',
			gray: '#AFB3B8',
		};
	}

	constructor(userId)
	{
		this.id = `tmp-id-${(new Date()).getTime()}`;
		this.userId = userId;
		this.deadline = null;
		this.changedDate = null;
		this.status = Item.statusList.pending;
		this.subStatus = Item.statusList.pending;
		this.isMuted = false;
		this.isPinned = false;
		this.notViewed = false;
		this.messageCount = 0;
		this.commentsCount = 0;
		this.newCommentsCount = 0;
		this.accomplices = [];
		this.auditors = [];
		this.params = {};
		this.params.allowChangeDeadline = true;
		this.rawAccess = {};

		this.counter = null;
	}

	setData(row)
	{
		this.id = row.id;
		this.title = row.title;
		this.groupId = row.groupId;
		this.status = row.realStatus;
		this.subStatus = row.status || this.status;

		this.createdBy = row.createdBy;
		this.responsibleId = row.responsibleId;
		this.accomplices = row.accomplices || [];
		this.auditors = row.auditors || [];

		this.commentsCount = row.commentsCount;
		this.newCommentsCount = row.newCommentsCount;

		this.isMuted = row.isMuted === 'Y';
		this.isPinned = row.isPinned === 'Y';
		this.notViewed = row.notViewed === 'Y';

		this.rawAccess = row.action;

		const deadline = Date.parse(row.deadline);
		const changedDate = Date.parse(row.changedDate);

		this.deadline = (deadline > 0 ? deadline : null);
		this.changedDate = (changedDate > 0 ? changedDate : null);
	}

	isCreator(userId = null)
	{
		return Number(userId || this.userId) === Number(this.createdBy);
	}

	isResponsible(userId = null)
	{
		return Number(userId || this.userId) === Number(this.responsibleId);
	}

	isAccomplice(userId = null)
	{
		return this.accomplices.includes(Number(userId || this.userId));
	}

	isAuditor(userId = null)
	{
		return this.auditors.includes(Number(userId || this.userId));
	}

	isMember(userId = null)
	{
		return this.isCreator(userId)
			|| this.isResponsible(userId)
			|| this.isAccomplice(userId)
			|| this.isAuditor(userId);
	}

	isDoer(userId = null)
	{
		return this.isResponsible(userId) || this.isAccomplice(userId);
	}

	isPureDoer(userId = null)
	{
		return this.isDoer(userId) && !this.isCreator(userId);
	}

	get isWaitCtrl()
	{
		return this.status === Item.statusList.waitCtrl;
	}

	get isWaitCtrlCounts()
	{
		return this.isWaitCtrl && this.isCreator() && !this.isResponsible();
	}

	get isCompleted()
	{
		return this.status === Item.statusList.completed;
	}

	get isCompletedCounts()
	{
		return this.isCompleted || (this.isWaitCtrl && !this.isCreator());
	}

	get isDeferred()
	{
		return this.status === Item.statusList.deferred;
	}

	get isExpired()
	{
		const date = new Date();
		return this.deadline && this.deadline <= date.getTime();
	}

	get isExpiredCounts()
	{
		return this.isExpired && this.isPureDoer() && !this.isCompletedCounts;
	}

	// counter instance
	getCounterData()
	{
		const counterColor = BX.UI.Counter.Color;

		let value = this.newCommentsCount || 0;
		let color = counterColor.SUCCESS;

		if (this.isExpired && !this.isCompletedCounts && !this.isWaitCtrlCounts && !this.isDeferred)
		{
			value += 1;
			color = counterColor.DANGER;
		}

		if (this.isMuted)
		{
			color = counterColor.GRAY;
		}

		return {value, color};
	}

	checkCounterInstance()
	{
		return this.counter !== null;
	}

	getCounterInstance()
	{
		if (!this.checkCounterInstance())
		{
			this.counter = new BX.UI.Counter({animate: true});
			this.updateCounterInstance();
		}

		return this.counter;
	}

	updateCounterInstance()
	{
		const counterData = this.getCounterData();

		if (counterData.value !== this.counter.getValue())
		{
			this.counter.update(counterData.value);
		}
		this.counter.setColor(counterData.color);
	}

	removeCounterInstance()
	{
		this.counter = null;
	}
}

export {Item};