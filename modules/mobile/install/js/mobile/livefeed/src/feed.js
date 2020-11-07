import {BalloonNotifier} from "./balloonnotifier";
import {NotificationBar} from "./notificationbar";
import {Database} from "./database";
import {PublicationQueue} from "./publicationqueue";
import {PostMenu} from "./menu/postmenu";
import {Post} from "./post";
import {PinnedPanel} from "./pinned";
import {Dom, Tag, Loc, Type, ajax, Runtime, Event} from "main.core";

import "mobile.imageviewer";
import {Utils} from "mobile.utils";

class Feed
{
	constructor()
	{
		this.pageId = null;
		this.refreshNeeded = false;
		this.refreshStarted = false;
		this.options = {};
		this.nodeId = {
			feedContainer: 'lenta_wrapper'
		};
		this.class = {
			listWrapper: 'lenta-list-wrap',
			postWrapper: 'post-wrap',
			pinnedPanel: 'lenta-pinned-panel',
			pin: 'lenta-item-pin',

			postNewContainerTransformNew: 'lenta-item-new-cont',
			postNewContainerTransform: 'lenta-item-transform-cont',
			postLazyLoadCheck: 'lenta-item-lazyload-check',
			listPost: 'lenta-item',
			detailPost: 'post-wrap',
			postItemTopWrap: 'post-item-top-wrap',
			postItemTop: 'post-item-top',
			postItemPostBlock: 'post-item-post-block',
			postItemAttachedFileWrap: 'post-item-attached-disk-file-wrap',
			postItemInformWrap: 'post-item-inform-wrap',
			postItemInformWrapTree: 'post-item-inform-wrap-tree',
			postItemInformComments: 'post-item-inform-comments',
			postItemInformMore: 'post-item-more',
			postItemMore: 'post-more-block',
			postItemPinnedBlock: 'post-item-pinned-block',
			postItemPinActive: 'lenta-item-pin-active'
		};

		this.newPostContainer = null;
		this.maxScroll = 0;

		this.init();
	}

	init()
	{
		BXMobileApp.addCustomEvent('Livefeed.PublicationQueue::afterPostAdd', this.afterPostAdd.bind(this));
		BXMobileApp.addCustomEvent('Livefeed.PublicationQueue::afterPostAddError', this.afterPostAddError.bind(this));
		BXMobileApp.addCustomEvent('Livefeed.PublicationQueue::afterPostUpdate', this.afterPostUpdate.bind(this));
		BXMobileApp.addCustomEvent('Livefeed.PublicationQueue::afterPostUpdateError', this.afterPostUpdateError.bind(this));
		BXMobileApp.addCustomEvent('Livefeed::showLoader', this.showLoader.bind(this));
		BXMobileApp.addCustomEvent('Livefeed::hideLoader', this.hideLoader.bind(this));
		BXMobileApp.addCustomEvent('Livefeed::scrollTop', this.scrollTop.bind(this));
		BXMobileApp.addCustomEvent("Livefeed::onLogEntryDetailNotFound", this.removePost.bind(this)); // from detail page
		BXMobileApp.addCustomEvent('Livefeed.PinnedPanel::change', this.onPinnedPanelChange.bind(this));
		BXMobileApp.addCustomEvent('Livefeed.PostDetail::pinChanged', this.onPostPinChanged.bind(this));

		Event.EventEmitter.subscribe('BX.LazyLoad:ImageLoaded', this.onLazyLoadImageLoaded.bind(this));

		document.addEventListener('DOMContentLoaded', () =>
		{
			document.addEventListener('click', this.handleClick.bind(this));
		});

	}

	setPageId(value)
	{
		this.pageId = value;
	}

	getPageId()
	{
		return this.pageId;
	};

	setOptions(optionsList)
	{
		for (let key in optionsList)
		{
			if (!optionsList.hasOwnProperty(key))
			{
				continue;
			}

			this.options[key] = optionsList[key];
		}
	}

	getOption(key, defaultValue)
	{
		if (Type.isUndefined(defaultValue))
		{
			defaultValue = null;
		}

		if (!Type.isStringFilled(key))
		{
			return null;
		}

		return (!Type.isUndefined(this.options[key]) ? this.options[key] : defaultValue);
	}

	setRefreshNeeded(value)
	{
		this.refreshNeeded = value;
	}

	getRefreshNeeded()
	{
		return this.refreshNeeded;
	};

	setRefreshStarted(value)
	{
		this.refreshStarted = value;
	}

	getRefreshStarted()
	{
		return this.refreshStarted;
	};

	setNewPostContainer(value)
	{
		this.newPostContainer = value;
	};

	getNewPostContainer()
	{
		return this.newPostContainer;
	};

	setMaxScroll(value)
	{
		this.maxScroll = value;
	};

	getMaxScroll()
	{
		return this.maxScroll;
	};

	afterPostAdd(params)
	{
		if (!Type.isPlainObject(params))
		{
			params = {};
		}

		const postId = (typeof params.postId != 'undefined' ? parseInt(params.postId) : 0);
		const context = (typeof params.context != 'undefined' ? params.context : '');
		const pageId = (typeof params.pageId != 'undefined' ? params.pageId : '');
		const groupId = (typeof params.groupId != 'undefined' ? params.groupId : null);

		if (pageId !== this.pageId)
		{
			return;
		}

		DatabaseUnsentPostInstance.delete(groupId);

		if (postId <= 0)
		{
			return;
		}

		this.getEntryContent({
			entityType: 'BLOG_POST',
			entityId: postId,
			queueKey: params.key,
			action: 'add'
		});
	}

	afterPostUpdate(params)
	{
		if (!Type.isPlainObject(params))
		{
			params = {};
		}

		const context = (typeof params.context != 'undefined' ? params.context : '');
		const pageId = (typeof params.pageId != 'undefined' ? params.pageId : '');
		const postId = (typeof params.postId != 'undefined' ? parseInt(params.postId) : 0);
		const pinned = (typeof params.pinned != 'undefined' && !!params.pinned);

		this.getEntryContent({
			entityType: 'BLOG_POST',
			entityId: postId,
			queueKey: params.key,
			action: 'update',
			pinned: pinned
		});
	}

	afterPostAddError(params)
	{
		if (!Type.isPlainObject(params))
		{
			params = {};
		}

		const context = (Type.isStringFilled(params.context) ? params.context : '');
		const groupId = (params.groupId ? params.groupId : '');

		var selectedDestinations = {
			a_users: [],
			b_groups: []
		};

		oMSL.buildSelectedDestinations(
			params.postData,
			selectedDestinations
		);

		oMSL.setPostFormParams({
			selectedRecipients: selectedDestinations
		});

		oMSL.setPostFormParams({
			messageText: params.postData.POST_MESSAGE
		});

		DatabaseUnsentPostInstance.save(params.postData, groupId);

		params.callback = () =>
		{
			app.exec('showPostForm', oMSL.showNewPostForm());
		};
		this.showPostError(params);
	}

	afterPostUpdateError(params)
	{
		if (!Type.isPlainObject(params))
		{
			params = {};
		}

		const context = (Type.isStringFilled(params.context) ? params.context : '');

		params.callback = () =>
		{
			oMSL.editBlogPost({
				post_id: parseInt(params.postId)
			});
		};
		this.showPostError(params);
	}

	showPostError(params)
	{
		if (!Type.isPlainObject(params))
		{
			params = {};
		}

		params.callback = (Type.isFunction(params.callback) ? params.callback : () =>
		{
		});

		const errorText = (Type.isStringFilled(params.errorText) ? params.errorText : false);

		NotificationBarInstance.showError({
			text: (errorText ? errorText : Loc.getMessage('MOBILE_EXT_LIVEFEED_PUBLICATION_ERROR')),
			onTap: (notificationParams) =>
			{
				params.callback(notificationParams);
			}
		});
	}

	showLoader(params)
	{
		if (!Type.isPlainObject(params))
		{
			params = {};
		}

		if (
			params.pageId
			&& this.pageId !== null
			&& params.pageId != this.pageId
		)
		{
			return;
		}

		app.showPopupLoader({
			text: (
				Type.isStringFilled(params.text)
					? params.text
					: ''
			)
		});
	}

	hideLoader()
	{
		app.hidePopupLoader();
	}

	scrollTop(params)
	{
		if (!Type.isPlainObject(params))
		{
			params = {};
		}

		if (
			params.pageId
			&& this.pageId !== null
			&& params.pageId != this.pageId
		)
		{
			return;
		}

		window.scrollTo(0, 0);
	};

	getEntryContent(params)
	{
		if (!Type.isPlainObject(params))
		{
			params = {};
		}

		const logId = (params.logId ? parseInt(params.logId) : 0);
		const BMAjaxWrapper = new MobileAjaxWrapper;

		if (
			logId <= 0
			&& !(
				Type.isStringFilled(params.entityType)
				&& parseInt(params.entityId) > 0
			)
		)
		{
			return;
		}

		BMAjaxWrapper.runComponentAction('bitrix:mobile.socialnetwork.log.ex', 'getEntryContent', {
			mode: 'class',
			signedParameters: this.getOption('signedParameters', {}),
			data: {
				params: {
					logId: (parseInt(params.logId) > 0 ? parseInt(params.logId) : 0),
					pinned: (!!params.pinned ? 'Y' : 'N'),
					entityType: (Type.isStringFilled(params.entityType) ? params.entityType : ''),
					entityId: (parseInt(params.entityId) > 0 ? parseInt(params.entityId) : 0),
					siteTemplateId: BX.message('MOBILE_EXT_LIVEFEED_SITE_TEMPLATE_ID')
				}
			}
		}).then((response) =>
		{
			if (logId <= 0)
			{
				BMAjaxWrapper.runComponentAction('bitrix:mobile.socialnetwork.log.ex', 'getEntryLogId', {
					mode: 'class',
					data: {
						params: {
							entityType: (Type.isStringFilled(params.entityType) ? params.entityType : ''),
							entityId: (parseInt(params.entityId) > 0 ? parseInt(params.entityId) : 0)
						}
					}
				}).then((responseLogId) =>
				{
					if (responseLogId.data.logId)
					{
						this.insertPost({
							logId: responseLogId.data.logId,
							content: response.data.html,
							postId: params.postId,
							queueKey: params.queueKey,
							action: params.action
						});
					}
				});
			} else
			{
				this.insertPost({
					logId: logId,
					content: response.data.html,
					postId: params.postId,
					queueKey: params.queueKey,
					action: params.action
				});
			}
		});
	};

	processDetailBlock(postContainer, contentWrapper, selector)
	{
		if (
			!postContainer
			|| !contentWrapper
		)
		{
			return Promise.reject();
		}

		const content = contentWrapper.querySelector(selector);
		const container = postContainer.querySelector(selector);

		if (container && content)
		{
			return Runtime.html(container, content.innerHTML);
		}

		return Promise.reject();
	}

	insertPost(params)
	{
		const containerNode = document.getElementById(this.nodeId.feedContainer);
		const content = params.content;
		const logId = params.logId;
		const queueKey = params.queueKey;
		const action = params.action;

		if (
			!Type.isDomNode(containerNode)
			|| !Type.isStringFilled(content)
		)
		{
			return;
		}

		if (action === 'update')
		{
			let postContainer = document.getElementById('lenta_item_' + logId);
			if (!postContainer)
			{
				postContainer = document.getElementById('lenta_item');
			}

			if (!postContainer)
			{
				return;
			}

			const matches = this.pageId.match(/^detail_(\d+)/i);
			if (
				matches
				&& logId != matches[1]
			)
			{
				return;
			}

			const contentWrapper = postContainer.appendChild(document.createElement('div'));
			contentWrapper.style.display = 'none';
			Runtime.html(contentWrapper, content);

			if (postContainer.id === 'lenta_item') // empty detail
			{
				this.processDetailBlock(postContainer, contentWrapper, `.${this.class.postItemTop}`);
				this.processDetailBlock(postContainer, contentWrapper, `.${this.class.postItemPostBlock}`).then(() =>
				{
					BitrixMobile.LazyLoad.showImages();
				});
				this.processDetailBlock(postContainer, contentWrapper, `.${this.class.postItemAttachedFileWrap}`).then(() =>
				{
					BitrixMobile.LazyLoad.showImages();
				});
				this.processDetailBlock(postContainer, contentWrapper, `.${this.class.postItemInformWrap}`);
				this.processDetailBlock(postContainer, contentWrapper, `.${this.class.postItemInformWrapTree}`);
			} else
			{
				postContainer = postContainer.querySelector(`div.${this.class.postItemTopWrap}`);

				const contentPostItemTopWrap = contentWrapper.querySelector(`div.${this.class.postItemTopWrap}`);

				Runtime.html(postContainer, contentPostItemTopWrap.innerHTML).then(() =>
				{
					BitrixMobile.LazyLoad.showImages();
				});
			}

			contentWrapper.remove();
		} else if (action === 'add')
		{
			this.setNewPostContainer(Tag.render`<div class="${this.class.postNewContainerTransformNew} ${this.class.postLazyLoadCheck}" ontransitionend="${this.handleInsertPostTransitionEnd.bind(this)}"></div>`);
			Dom.prepend(this.getNewPostContainer(), containerNode);
			Utils.htmlWithInlineJS(this.getNewPostContainer(), content).then(() =>
			{
				const postNode = this.getNewPostContainer().querySelector(`div.${this.class.listPost}`);
				Dom.style(this.getNewPostContainer(), 'height', `${postNode.scrollHeight + 15/*margin-bottom*/}px`);
			});
		}

		PublicationQueueInstance.emit('onPostInserted', new Event.BaseEvent({
			data: {
				key: queueKey
			}
		}));
	}

	removePost(params)
	{
		const logId = parseInt(params.logId);

		if (logId <= 0)
		{
			return;
		}

		const itemNode = document.getElementById('lenta_item_' + logId);

		if (!itemNode)
		{
			return;
		}

		itemNode.remove();
	}

	handleInsertPostTransitionEnd(event)
	{
		if (event.propertyName === 'height')
		{
			this.getNewPostContainer().classList.remove(this.class.postNewContainerTransformNew);
			this.getNewPostContainer().classList.remove(this.class.postNewContainerTransform);
			Dom.style(this.getNewPostContainer(), 'height', null);

			this.recalcMaxScroll();
			BitrixMobile.LazyLoad.showImages();
		}
	}

	onLazyLoadImageLoaded(event)
	{
		this.recalcMaxScroll();

		const [imageNode] = event.getData();
		if (imageNode)
		{
			const postCheckNode = imageNode.closest(`.${this.class.postLazyLoadCheck}`);
			if (postCheckNode)
			{
				const postNode = postCheckNode.querySelector(`div.${this.class.listPost}`);

				if (postNode)
				{
					postCheckNode.classList.add(this.class.postNewContainerTransform);
					Dom.style(postCheckNode, 'height', `${postNode.scrollHeight}px`);
					setTimeout(() =>
					{
						postCheckNode.classList.remove(this.class.postNewContainerTransform);
						Dom.style(postCheckNode, 'height', null);
					}, 500);
				}
			}
		}
	}

	recalcMaxScroll()
	{
		this.setMaxScroll(document.documentElement.scrollHeight - window.innerHeight - 190);
	}

	setPreventNextPage(status)
	{
		this.setOptions({
			preventNextPage: !!status
		});

		const refreshNeededNode = document.getElementById('next_page_refresh_needed');
		const nextPageCurtainNode = document.getElementById('next_post_more');

		if (
			refreshNeededNode
			&& nextPageCurtainNode
		)
		{
			refreshNeededNode.style.display = (!!status ? 'block' : 'none');
			nextPageCurtainNode.style.display = (!!status ? 'none' : 'block');
		}
	}

	getPinnedPanelNode()
	{
		return document.querySelector('[data-livefeed-pinned-panel]');
	}

	onPinnedPanelChange(params)
	{
		const logId = (params.logId ? parseInt(params.logId) : 0);
		const value = (['Y', 'N'].indexOf(params.value) !== -1 ? params.value : null);
		const pinActionContext = (Type.isStringFilled(params.pinActionContext) ? params.pinActionContext : 'list');

		if (
			!logId
			|| !value
			|| !this.getPinnedPanelNode()
		)
		{
			return;
		}

		const pinnedPanel = new PinnedPanel({});

		let postNode = (Type.isDomNode(params.postNode) ? params.postNode: null);
		if (!Type.isDomNode(params.postNode)) // from detail in list
		{
			postNode = document.getElementById(`lenta_item_${logId}`);
		}

		if (!Type.isDomNode(postNode))
		{
			return;
		}

		if (value === 'N')
		{
			pinnedPanel.extractEntry({
				postNode: postNode,
				containerNode: document.getElementById(this.nodeId.feedContainer)
			});
		}
		else if (value === 'Y')
		{
			if (pinActionContext === 'list')
			{
				app.showPopupLoader({text: ""});
			}

			pinnedPanel.getPinnedData({
				logId: logId
			}).then((pinnedData) =>
			{
				app.hidePopupLoader();
				pinnedPanel.insertEntry({
					logId: logId,
					postNode: postNode,
					pinnedPanelNode: this.getPinnedPanelNode(),
					pinnedContent: pinnedData
				});
			}, (response) =>
			{
				app.hidePopupLoader();
			})
		}
	}

	onPostPinChanged(params)
	{
		const logId = (params.logId ? parseInt(params.logId) : 0);
		const value = (['Y', 'N'].indexOf(params.value) !== -1 ? params.value : null);

		if (
			!logId
			|| !value
		)
		{
			return;
		}

		const menuNode = document.getElementById('log-entry-menu-' + logId);
		if (!menuNode)
		{
			return;
		}

		let postNode = menuNode.closest(`.${this.class.detailPost}`);
		if (!postNode)
		{
			postNode = menuNode.closest(`.${this.class.listPost}`);
		}
		if (!postNode)
		{
			return;
		}

		if (value === 'Y')
		{
			postNode.classList.add(this.class.postItemPinActive);
		}
		else
		{
			postNode.classList.remove(this.class.postItemPinActive);
		}
	}

	handleClick(e)
	{
		if (e.target.classList.contains(this.class.pin))
		{
			let post = null;
			let menuNode = null;
			let context = 'list';

			let postNode = e.target.closest(`.${this.class.listPost}`);
			if (postNode) // lest
			{
				menuNode = postNode.querySelector('[data-menu-type="post"]');
				post = this.getPostFromNode(postNode);
			}
			else // detail
			{
				context = 'detail';
				postNode = e.target.closest(`.${this.class.detailPost}`);
				if (postNode)
				{
					menuNode = postNode.querySelector('[data-menu-type="post"]');
					if (menuNode)
					{
						post = this.getPostFromLogId(menuNode.getAttribute('data-log-id'));
					}
				}
			}

			if (
				post
				&& menuNode
			)
			{
				return post.setPinned({
					menuNode,
					context
				});
			}

			e.stopPropagation();
			return e.preventDefault();
		}
		else if (
			(
				e.target.closest(`.${this.class.listWrapper}`)
				|| e.target.closest(`.${this.class.pinnedPanel}`)
			)
			&& !(
				e.target.tagName.toLowerCase() === 'a'
				&& Type.isStringFilled(e.target.getAttribute('target'))
				&& e.target.getAttribute('target').toLowerCase() === '_blank'
			)
		)
		{
			const detailFromPinned = !!(
				e.target.classList.contains(this.class.postItemPinnedBlock)
				|| e.target.closest(`.${this.class.postItemPinnedBlock}`)
			);
			const detailFromNormal = !!(!detailFromPinned && (
				e.target.classList.contains(this.class.postItemPostBlock)
				|| e.target.closest(`.${this.class.postItemPostBlock}`)
			));
			const detailToComments = !!(!detailFromPinned && !detailFromNormal && (
				e.target.classList.contains(this.class.postItemInformComments)
				|| e.target.closest(`.${this.class.postItemInformComments}`)
			));
			const detailToExpanded = !!(!detailFromPinned && !detailFromNormal && !detailToComments && (
				e.target.classList.contains(this.class.postItemInformMore)
				|| e.target.closest(`.${this.class.postItemInformMore}`)
			));

			if (
				detailFromPinned
				|| detailFromNormal
				|| detailToComments
				|| detailToExpanded
			)
			{
				const postNode = e.target.closest(`.${this.class.listPost}`);
				if (postNode)
				{
					const post = this.getPostFromNode(postNode);
					if (post)
					{
						post.openDetail({
							pathToEmptyPage: this.getOption('pathToEmptyPage', ''),
							pathToCalendarEvent: this.getOption('pathToCalendarEvent', ''),
							pathToTasksRouter: this.getOption('pathToTasksRouter', ''),
							event: e,
							focusComments: detailToComments,
							showFull: detailToExpanded,
						});
					}
				}

				e.stopPropagation();
				return e.preventDefault();
			}
		}
		else if (e.target.closest(`.${this.class.postWrapper}`))
		{
			const expand = !!(
				e.target.classList.contains(this.class.postItemInformMore)
				|| e.target.closest(`.${this.class.postItemInformMore}`)
				|| e.target.classList.contains(this.class.postItemMore)
				|| e.target.closest(`.${this.class.postItemMore}`)
			);

			if (expand)
			{
				const logId = this.getOption('logId', 0);
				const post = new Post({
					logId: logId
				});

				post.expandText();

				e.stopPropagation();
				return e.preventDefault();
			}
		}
	}

	getPostFromNode(node)
	{
		if (!Type.isDomNode(node))
		{
			return;
		}

		const logId = parseInt(node.getAttribute('data-livefeed-id'));

		if (logId <= 0)
		{
			return;
		}

		return new Post({
			logId: logId,
			entryType: node.getAttribute('data-livefeed-post-entry-type'),
			useFollow: (node.getAttribute('data-livefeed-post-use-follow') === 'Y'),
			useTasks: (node.getAttribute('data-livefeed-post-use-tasks') === 'Y'),
			perm: node.getAttribute('data-livefeed-post-perm'),
			destinations: node.getAttribute('data-livefeed-post-destinations'),
			postId: parseInt(node.getAttribute('data-livefeed-post-id')),
			url: node.getAttribute('data-livefeed-post-url'),
			entityXmlId: node.getAttribute('data-livefeed-post-entity-xml-id'),
			readOnly: (node.getAttribute('data-livefeed-post-read-only') === 'Y'),
			contentTypeId: node.getAttribute('data-livefeed-post-content-type-id'),
			contentId: node.getAttribute('data-livefeed-post-content-id'),
			showFull: (node.getAttribute('data-livefeed-post-show-full') === 'Y'),

			taskId: parseInt(node.getAttribute('data-livefeed-task-id')),
			taskData: node.getAttribute('data-livefeed-task-data'),

			calendarEventId: parseInt(node.getAttribute('data-livefeed-calendar-event-id'))
		});
	}

	getPostFromLogId(logId)
	{
		let result = null;

		logId = parseInt(logId);
		if (logId <= 0)
		{
			return result;
		}

		result = new Post({
			logId
		});

		return result;
	}
}

const Instance = new Feed();
const BalloonNotifierInstance = new BalloonNotifier();
const NotificationBarInstance = new NotificationBar();
const DatabaseUnsentPostInstance = new Database();
const PublicationQueueInstance = new PublicationQueue();
const PostMenuInstance = new PostMenu();

export {
	Instance,
	BalloonNotifierInstance,
	NotificationBarInstance,
	DatabaseUnsentPostInstance,
	PublicationQueueInstance,
	PostMenuInstance
};