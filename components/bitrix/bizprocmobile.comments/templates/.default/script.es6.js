import { Dom, Event, GetWindowInnerSize, GetWindowScrollPos, Reflection } from 'main.core';
import { EventEmitter } from 'main.core.events';

const namespace = Reflection.namespace('BX.BizprocMobile');
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
		this.workflowId = options.workflowId;
		this.workflowIdInt = options.workflowIdInt;
		this.guid = options.guid;

		this.canReadCommentsOnInit = true;

		this.timeout = 0;
		this.timeoutSec = 2000;
		this.commentsList = null;
		this.unreadComments = new Map();
		this.commentsToRead = new Map();

		this.initTextField();
		this.initCommentsBlock();

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
		this.commentsBlock = BX('workflow-comments-block');

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
			if (xmlId === `WF_${this.workflowId}`)
			{
				this.commentsList = list;
				this.commentsList.canCheckVisibleComments = true;
				this.unreadComments = new Map(this.commentsList.unreadComments);
			}
		});

		EventEmitter.subscribe('onUCFormSubmit', () => this.resolveVisibility(Comments.block.comments));

		EventEmitter.subscribe('OnUCCommentWasPulled', (event) => {
			const [id, data] = event.getData();
			if (id[0] === `WF_${this.workflowId}`)
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
			if (xmlId === `WF_${this.workflowId}`)
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
			if (xmlId === `WF_${this.workflowId}` && this.unreadComments.has(commentId))
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

		// BXMobileApp.addCustomEvent('onPull-tasks', () => {});

		Event.bind(document, 'visibilitychange', () => this.setCanCheckVisibleComments(!document.hidden));
		Event.bind(window, 'pagehide', () => this.setCanCheckVisibleComments(false));
		Event.bind(window, 'pageshow', () => this.setCanCheckVisibleComments(true));

		BX.MobileUI.addLivefeedLongTapHandler(this.commentsBlock, { likeNodeClass: 'post-comment-control-item-like' });
	}

	setCanCheckVisibleComments(canCheck: boolean): void
	{
		if (canCheck && this.canReadCommentsOnInit)
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

	sendOnCommentsReadEvent(newCommentsCount: number = 0): void
	{
		// const params = {
		// 	newCommentsCount,
		// 	workflowId: this.workflowId,
		// };
		// BXMobileApp.Events.postToComponent('tasks.task.comments:onCommentsRead', params);
		// BXMobileApp.Events.postToComponent('task.view.onCommentsRead', params, 'tasks.list');
	}

	readComments(): void
	{
		this.timeout = 0;
		this.commentsToRead.clear();

		void BX.ajax.runAction('bizproc.workflow.comment.markAsRead', {
			data: {
				workflowId: this.workflowId,
				userId: this.userId,
			},
		});
	}
}

namespace.Comments = Comments;
