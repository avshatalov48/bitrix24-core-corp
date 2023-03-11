import {FollowManagerInstance} from "./feed";
import {Loc, Type} from 'main.core';
import {Ajax} from 'mobile.ajax';

class Post
{
	static moveBottom()
	{
		window.scrollTo(0, document.body.scrollHeight);
	}

	static moveTop()
	{
		window.scrollTo(0, 0);
	}

	constructor(data)
	{
		this.logId = 0;
		this.entryType = '';
		this.useFollow = false;
		this.useTasks = false;
		this.perm = '';
		this.destinations = {};
		this.postId = 0;
		this.url = '';
		this.entityXmlId = '';
		this.readOnly = false;
		this.contentTypeId = '';
		this.contentId = 0;
		this.showFull = false;

		this.taskId = 0;
		this.taskData = null;

		this.calendarEventId = 0;

		this.init(data);
	}

	init(data)
	{
		let {
			logId,
			entryType,
			useFollow,
			useTasks,
			perm,
			destinations,
			postId,
			url,
			entityXmlId,
			readOnly,
			contentTypeId,
			contentId,
			showFull,

			taskId,
			taskData,

			calendarEventId
		} = data;

		logId = parseInt(logId);

		if (logId <= 0)
		{
			return;
		}

		this.logId = logId;

		this.postId = parseInt(postId);
		this.contentId = parseInt(contentId);
		this.taskId = parseInt(taskId);
		this.calendarEventId = parseInt(calendarEventId);

		this.useFollow = !!useFollow;
		this.useTasks = !!useTasks;
		this.readOnly = !!readOnly;
		this.showFull = !!showFull;

		if (Type.isStringFilled(entryType))
		{
			this.entryType = entryType;
		}
		if (Type.isStringFilled(perm))
		{
			this.perm = perm;
		}
		if (Type.isStringFilled(url))
		{
			this.url = url;
		}
		if (Type.isStringFilled(entityXmlId))
		{
			this.entityXmlId = entityXmlId;
		}
		if (Type.isStringFilled(contentTypeId))
		{
			this.contentTypeId = contentTypeId;
		}
		if (Type.isStringFilled(taskData))
		{
			try
			{
				this.taskData = JSON.parse(taskData);
			}
			catch(e)
			{
				this.taskData = null;
			}
		}

		if (Type.isPlainObject(destinations))
		{
			this.destinations = destinations;
		}
	}

	setFavorites(data):void
	{
		if (this.logId <= 0)
		{
			return;
		}

		let {node, event} = data;

		// for old versions without post menu in the feed
		if (!Type.isDomNode(node))
		{
			node = document.getElementById('log_entry_favorites_' + this.logId);
		}

		if (Type.isDomNode(node))
		{
			const oldValue = (node.getAttribute('data-favorites') === 'Y' ? 'Y' : 'N');
			const newValue = (oldValue === 'Y' ? 'N' : 'Y');

			// for old versions without post menu in the feed
			if (node.classList.contains('lenta-item-fav'))
			{
				if (oldValue === 'Y')
				{
					node.classList.remove('lenta-item-fav-active');
				}
				else
				{
					node.classList.add('lenta-item-fav-active');
				}
			}

			node.setAttribute('data-favorites', newValue);

			Ajax.runAction('socialnetwork.api.livefeed.changeFavorites', {
				data: {
					logId: this.logId,
					value: newValue
				},
				analyticsLabel: {
					b24statAction: (newValue === 'Y' ? 'addFavorites' : 'removeFavorites'),
					b24statContext: 'mobile'
				}
			}).then((response) => {
				if (response.data.success)
				{
					if (newValue === 'Y')
					{
						FollowManagerInstance.setFollow({
							logId: this.logId,
							bOnlyOn: true,
							bRunEvent: true,
							bAjax: false
						});
					}

					BXMobileApp.onCustomEvent('onLogEntryFavorites', {
						log_id: this.logId,
						page_id: Loc.getMessage('MSLPageId')
					}, true);
				}
				else
				{
					node.setAttribute('data-favorites', oldValue);
				}
			}, () => {
				node.setAttribute('data-favorites', oldValue);
			});
		}

		if (event instanceof Event)
		{
			event.preventDefault();
			event.stopPropagation();
		}
	}

	setPinned(data):void
	{
		if (this.logId <= 0)
		{
			return;
		}

		const {
			menuNode,
			context
		} = data;

		if (Type.isDomNode(menuNode))
		{
			const oldValue = (menuNode.getAttribute('data-pinned') === 'Y' ? 'Y' : 'N');
			const newValue = (oldValue === 'Y' ? 'N' : 'Y');

			menuNode.setAttribute('data-pinned', newValue);

			BXMobileApp.onCustomEvent('Livefeed::showLoader', {}, true, true);

			const action = (
				newValue === 'Y'
					? 'socialnetwork.api.livefeed.logentry.pin'
					: 'socialnetwork.api.livefeed.logentry.unpin'
			);

			Ajax.runAction(action, {
				data: {
					params: {
						logId: this.logId
					}
				},
				analyticsLabel: {
					b24statAction: (newValue === 'Y' ? 'pinLivefeedEntry' : 'unpinLivefeedEntry'),
					b24statContext: 'mobile'
				}
			}).then((response) => {
				BXMobileApp.onCustomEvent('Livefeed::hideLoader', {}, true, true);
				if (response.data.success)
				{
					BXMobileApp.onCustomEvent('Livefeed.PinnedPanel::change', {
						logId: this.logId,
						value: newValue,
						postNode: menuNode.closest('.lenta-item'),
						pinActionContext: context
					}, true, true);
					BXMobileApp.onCustomEvent('Livefeed.PostDetail::pinChanged', {
						logId: this.logId,
						value: newValue
					}, true, true);
				}
				else
				{
					menuNode.setAttribute('data-pinned', oldValue);
				}
			}, () => {
				BXMobileApp.onCustomEvent('Livefeed::hideLoader', {}, true, true);
				menuNode.setAttribute('data-pinned', oldValue);
			});
		}
	}

	openDetail(params)
	{
		const {
			pathToEmptyPage,
			pathToCalendarEvent,
			pathToTasksRouter,
			event,
			focusComments,
			showFull
		} = params;

		if (!Type.isStringFilled(pathToEmptyPage))
		{
			return;
		}

		if (
			this.taskId > 0
			&& BXMobileAppContext.getApiVersion() >= 31
			&& this.taskData
		)
		{
			BXMobileApp.Events.postToComponent('taskbackground::task::open', [
				{
					id: this.taskId,
					taskId: this.taskId,
					title: 'TASK',
					taskInfo: {
						title: this.taskData.title,
						creatorIcon: this.taskData.creatorIcon,
						responsibleIcon: this.taskData.responsibleIcon
					}
				},
				{
					taskId: this.taskId,
					getTaskInfo: true
				}
			]);
		}
		else
		{
			let path = pathToEmptyPage;

			if (
				this.calendarEventId > 0
				&& !focusComments
				&& pathToCalendarEvent.length > 0
			)
			{
				path = pathToCalendarEvent.replace('#EVENT_ID#', this.calendarEventId);
			}
			else if (
				this.taskId > 0
				&& this.taskData
				&& pathToTasksRouter.length > 0
			) // API version <= 31
			{
				path = pathToTasksRouter
					.replace('__ROUTE_PAGE__', 'view')
					.replace('#USER_ID#', Loc.getMessage('MOBILE_EXT_LIVEFEED_CURRENT_USER_ID')) + '&TASK_ID=' + this.taskId;
			}

			__MSLOpenLogEntryNew({
				path: path,
				log_id: this.logId,
				entry_type: this.entryType,
				use_follow: (this.useFollow ? 'Y' : 'N'),
				use_tasks: (this.useTasks ? 'Y' : 'N'),
				post_perm: this.perm,
				destinations: this.destinations,
				post_id: this.postId,
				post_url: this.url,
				entity_xml_id: this.entityXmlId,
				focus_comments: focusComments,
				focus_form: false,
				show_full: (this.showFull || showFull),
				read_only: (this.readOnly ? 'Y' : 'N'),
				post_content_type_id: this.contentTypeId,
				post_content_id: this.contentId,
			}, event);
		}
	}

	initDetailPin()
	{
		const menuNode = document.getElementById('log-entry-menu-' + this.logId);
		if (!menuNode)
		{
			return;
		}

		const postNode = menuNode.closest('.post-wrap');
		if (!postNode)
		{
			return;
		}

		const pinnedValue = menuNode.getAttribute('data-pinned');
		if (pinnedValue === 'Y')
		{
			postNode.classList.add('lenta-item-pin-active');
		}
		else
		{
			postNode.classList.remove('lenta-item-pin-active');
		}
	}

	expandText()
	{
		oMSL.expandText(this.logId);
	}
}

export {
	Post
}