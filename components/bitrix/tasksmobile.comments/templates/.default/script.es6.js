import { Dom, Event, GetWindowInnerSize, GetWindowScrollPos, Reflection } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { ResultManager } from 'tasks.result';

const namespace = Reflection.namespace('BX.TasksMobile');

export default class Comments
{
	static get block(): Object
	{
		return {
			comments: 'comments',
			stub: 'stub',
		};
	}

	constructor(options): void
	{
		this.userId = options.userId;
		this.taskId = options.taskId;
		this.guid = options.guid;
		this.isTabsMode = options.isTabsMode;

		this.canReadCommentsOnInit = true;

		this.timeout = 0;
		this.timeoutSec = 2000;
		this.commentsList = null;
		this.unreadComments = new Map();
		this.commentsToRead = new Map();

		this.initTextField();
		this.initCommentsBlock();
		this.initTaskResultManager(options);

		this.sendEvents(options);
		this.bindEvents();
	}

	initTextField(): void
	{
		if (BX.MobileUI.TextField.defaultParams)
		{
			window.BX.MobileUI.TextField.show();
		}
		else
		{
			EventEmitter.subscribe(
				BX.MobileUI.events.MOBILE_UI_TEXT_FIELD_SET_PARAMS,
				() => window.BX.MobileUI.TextField.show(),
			);
		}
	}

	initCommentsBlock(): void
	{
		this.stub = BX('commentsStub');
		this.commentsBlock = BX('task-comments-block');

		const firstComment = BX('post-comments-wrap').querySelector('.post-comment-block');
		this.resolveVisibility(firstComment ? Comments.block.comments : Comments.block.stub);
	}

	resolveVisibility(visibleBlock = Comments.block.stub): void
	{
		const hiddenClass = '--hidden';

		if (visibleBlock === Comments.block.stub)
		{
			if (Dom.hasClass(this.stub, hiddenClass))
			{
				Dom.removeClass(this.stub, hiddenClass);
			}

			if (!Dom.hasClass(this.commentsBlock, hiddenClass))
			{
				Dom.addClass(this.commentsBlock, hiddenClass);
			}
		}
		else if (visibleBlock === Comments.block.comments)
		{
			if (Dom.hasClass(this.commentsBlock, hiddenClass))
			{
				Dom.removeClass(this.commentsBlock, hiddenClass);
			}

			if (!Dom.hasClass(this.stub, hiddenClass))
			{
				Dom.addClass(this.stub, hiddenClass);
			}
		}
	}

	initTaskResultManager({ resultComments, isClosed })
	{
		ResultManager.getInstance().initResult({
			context: 'task',
			taskId: this.taskId,
			comments: resultComments,
			isClosed,
		});
	}

	sendEvents(options): void
	{
		if (options.logId)
		{
			const params = {
				log_id: options.logId,
				ts: options.currentTs,
				bPull: false,
			};
			BXMobileApp.onCustomEvent('onLogEntryRead', params, true);
		}
	}

	bindEvents(): void
	{
		EventEmitter.subscribe('OnUCHasBeenInitialized', (event) => {
			const [xmlId, list] = event.getData();
			if (xmlId === `TASK_${this.taskId}`)
			{
				this.commentsList = list;
				this.commentsList.canCheckVisibleComments = !this.isTabsMode;
				this.unreadComments = new Map(this.commentsList.unreadComments);
			}
		});

		EventEmitter.subscribe('onUCFormSubmit', (event) => {
			const [data] = event.getData();
			if (data === `TASK_${this.taskId}`)
			{
				this.sendOnCommentWrittenEvent();
			}
			this.resolveVisibility(Comments.block.comments);
		});

		EventEmitter.subscribe('OnUCCommentWasPulled', (event) => {
			const [id, data] = event.getData();
			if (id[0] === `TASK_${this.taskId}`)
			{
				const author = data.messageFields.AUTHOR;
				if (Number(author.ID) !== Number(this.userId))
				{
					this.unreadComments.set(id[1], new Date());
				}
				this.resolveVisibility(Comments.block.comments);
			}
		});

		EventEmitter.subscribe('OnUCommentWasDeleted', (event) => {
			const [xmlId, id] = event.getData();
			const commentId = id[1];
			if (xmlId === `TASK_${this.taskId}`)
			{
				if (this.commentsList.getCommentsCount() <= 0)
				{
					this.resolveVisibility(Comments.block.stub);
				}
				this.unreadComments.delete(commentId);
			}
		});

		EventEmitter.subscribe('OnUCCommentWasRead', (event) => {
			const [xmlId, id] = event.getData();
			const commentId = id[1];
			if (xmlId === `TASK_${this.taskId}` && this.unreadComments.has(commentId))
			{
				this.commentsToRead.set(commentId, this.unreadComments.get(commentId));
				this.unreadComments.delete(commentId);

				this.sendOnCommentsReadEvent(this.unreadComments.size);

				if (this.timeout <= 0)
				{
					this.timeout = setTimeout(() => this.readComments(), this.timeoutSec);
				}
			}
		});

		BXMobileApp.addCustomEvent('onPull-tasks', () => {});

		if (this.isTabsMode)
		{
			BXMobileApp.addCustomEvent('tasks.task.tabs:onTabSelected', (event) => {
				if (event.guid === this.guid && this.commentsList)
				{
					this.setCanCheckVisibleComments(event.tab === 'tasks.task.comments');
				}
			});
		}
		else
		{
			Event.bind(document, 'visibilitychange', () => this.setCanCheckVisibleComments(!document.hidden));
			Event.bind(window, 'pagehide', () => this.setCanCheckVisibleComments(false));
			Event.bind(window, 'pageshow', () => this.setCanCheckVisibleComments(true));
		}

		BX.MobileUI.addLivefeedLongTapHandler(this.commentsBlock, { likeNodeClass: 'post-comment-control-item-like' });
	}

	setCanCheckVisibleComments(canCheck: boolean): void
	{
		if (!this.isTabsMode && canCheck && this.canReadCommentsOnInit)
		{
			this.canReadCommentsOnInit = false;
			this.readComments();
		}

		if (!this.commentsList)
		{
			return;
		}

		this.commentsList.canCheckVisibleComments = canCheck;

		if (canCheck)
		{
			const scroll = GetWindowScrollPos();
			const position = {
				top: scroll.scrollTop,
				bottom: scroll.scrollTop + GetWindowInnerSize().innerHeight,
			};
			this.commentsList.checkVisibleComments(position);

			if (this.canReadCommentsOnInit)
			{
				this.canReadCommentsOnInit = false;
				this.readComments();
			}
		}
	}

	sendOnCommentWrittenEvent(): void
	{
		const params = {
			taskId: this.taskId,
		};
		BXMobileApp.Events.postToComponent('tasks.task.comments:onCommentWritten', params);
	}

	sendOnCommentsReadEvent(newCommentsCount: number = 0): void
	{
		const params = {
			newCommentsCount,
			taskId: this.taskId,
		};
		BXMobileApp.Events.postToComponent('tasks.task.comments:onCommentsRead', params);
		BXMobileApp.Events.postToComponent('task.view.onCommentsRead', params, 'tasks.list');
	}

	readComments(): void
	{
		this.timeout = 0;
		this.commentsToRead.clear();

		void BX.ajax.runAction('tasks.task.view.update', {
			data: {
				taskId: this.taskId,
				parameters: {
					IS_REAL_VIEW: 'Y',
				},
			},
		});
	}
}

namespace.Comments = Comments;
