import {BalloonNotifier} from "./balloonnotifier";
import {NotificationBar} from "./notificationbar";
import {Database} from "./database";
import {PublicationQueue} from "./publicationqueue";
import {PostMenu} from "./menu/postmenu";
import {PostFormManager} from "./postform";
import {Post} from "./post";
import {PinnedPanel} from "./pinned";
import {Dom, Tag, Loc, Type, ajax, Runtime} from "main.core";
import {BaseEvent, EventEmitter} from "main.core.events";

import "mobile.imageviewer";
import {Utils} from "mobile.utils";
import {Ajax} from 'mobile.ajax';

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
			postItemPostContentView: 'post-item-contentview',
			postItemDescriptionBlock: 'post-item-description',
			postItemAttachedFileWrap: 'post-item-attached-disk-file-wrap',
			postItemInformWrap: 'post-item-inform-wrap',
			postItemInformWrapTree: 'post-item-inform-wrap-tree',
			postItemInformComments: 'post-item-inform-comments',
			postItemInformMore: 'post-item-more',
			postItemMore: 'post-more-block',
			postItemPinnedBlock: 'post-item-pinned-block',
			postItemPinActive: 'lenta-item-pin-active',
			postItemGratitudeUsersSmallContainer: 'lenta-block-grat-users-small-cont',
			postItemGratitudeUsersSmallHidden: 'lenta-block-grat-users-small-hidden',
			postItemImportantUserList: 'post-item-important-list',

			addPostButton: 'feed-add-post-button'
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

		EventEmitter.subscribe('BX.LazyLoad:ImageLoaded', this.onLazyLoadImageLoaded.bind(this));
		EventEmitter.subscribe('MobilePlayer:onError', this.onMobilePlayerError);

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
		const selectedDestinations = {
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
			if (BXMobileAppContext.getApiVersion() >= this.getApiVersion('layoutPostForm'))
			{
				PostFormManagerInstance.show({
					pageId: this.getPageId(),
					postId: 0
				});
			}
			else
			{
				app.exec('showPostForm', oMSL.showNewPostForm());
			}
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

		Ajax.runComponentAction('bitrix:mobile.socialnetwork.log.ex', 'getEntryContent', {
			mode: 'class',
			signedParameters: this.getOption('signedParameters', {}),
			data: {
				params: {
					logId: (parseInt(params.logId) > 0 ? parseInt(params.logId) : 0),
					pinned: (!!params.pinned ? 'Y' : 'N'),
					entityType: (Type.isStringFilled(params.entityType) ? params.entityType : ''),
					entityId: (parseInt(params.entityId) > 0 ? parseInt(params.entityId) : 0),
					siteTemplateId: Loc.getMessage('MOBILE_EXT_LIVEFEED_SITE_TEMPLATE_ID')
				}
			}
		}).then((response) =>
		{
			if (logId <= 0)
			{
				Ajax.runComponentAction('bitrix:mobile.socialnetwork.log.ex', 'getEntryLogId', {
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
							action: params.action,
							serverTimestamp: parseInt(response.data.componentResult.serverTimestamp)
						});
					}
				});
			}
			else
			{
				this.insertPost({
					logId: logId,
					content: response.data.html,
					postId: params.postId,
					queueKey: params.queueKey,
					action: params.action,
					serverTimestamp: parseInt(response.data.componentResult.serverTimestamp)
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
					const pageBlockNode = postContainer.querySelector(`.${this.class.postItemPostBlock}`);
					const resultBlockNode = contentWrapper.querySelector(`.${this.class.postItemPostBlock}`);

					if (pageBlockNode || resultBlockNode)
					{
						const pageClassList = this.filterPostBlockClassList(pageBlockNode.classList);
						const resultClassList = this.filterPostBlockClassList(resultBlockNode.classList);

						pageClassList.forEach((className) => {
							pageBlockNode.classList.remove(className);
						});
						resultClassList.forEach((className) => {
							pageBlockNode.classList.add(className);
						});
					}

					BitrixMobile.LazyLoad.showImages();
				});
				this.processDetailBlock(postContainer, contentWrapper, `.${this.class.postItemAttachedFileWrap}`).then(() =>
				{
					BitrixMobile.LazyLoad.showImages();
				});
				this.processDetailBlock(postContainer, contentWrapper, `.${this.class.postItemInformWrap}`);
				this.processDetailBlock(postContainer, contentWrapper, `.${this.class.postItemInformWrapTree}`);
			}
			else
			{
				postContainer = postContainer.querySelector(`div.${this.class.postItemTopWrap}`);

				const contentPostItemTopWrap = contentWrapper.querySelector(`div.${this.class.postItemTopWrap}`);

				Runtime.html(postContainer, contentPostItemTopWrap.innerHTML).then(() => {
					oMSL.checkNodesHeight();
					BitrixMobile.LazyLoad.showImages();
				});
			}

			contentWrapper.remove();
		}
		else if (action === 'add')
		{
			this.setNewPostContainer(Tag.render`<div class="${this.class.postNewContainerTransformNew} ${this.class.postLazyLoadCheck}" ontransitionend="${this.handleInsertPostTransitionEnd.bind(this)}"></div>`);
			Dom.prepend(this.getNewPostContainer(), containerNode);
			Utils.htmlWithInlineJS(this.getNewPostContainer(), content).then(() =>
			{
				const postNode = this.getNewPostContainer().querySelector(`div.${this.class.listPost}`);
				Dom.style(this.getNewPostContainer(), 'height', `${postNode.scrollHeight + 12/*margin-bottom*/}px`);

				const serverTimestamp = (
					typeof (params.serverTimestamp) != 'undefined'
					&& parseInt(params.serverTimestamp) > 0
						? parseInt(params.serverTimestamp)
						: 0
				);

				if (serverTimestamp > 0)
				{
					this.setOptions({
						frameCacheTs: serverTimestamp
					});
				}

				oMSL.registerBlocksToCheck();
				setTimeout(() => { oMSL.checkNodesHeight(); }, 100);

				setTimeout(() => {
					this.updateFrameCache({
						timestamp: serverTimestamp
					});
				}, 750);
			});
		}

		PublicationQueueInstance.emit('onPostInserted', new BaseEvent({
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

	filterPostBlockClassList(classList)
	{
		const result = [];

		Array.from(classList).forEach((className) => {
			if (
				className === 'info-block-background'
				|| className === 'info-block-background-with-title'
				|| className === 'info-block-gratitude'
				|| className === 'info-block-important'
				|| className === 'ui-livefeed-background'
				|| className.match(/info-block-gratitude-(.+)/i)
				|| className.match(/ui-livefeed-background-(.+)/i)
			)
			{
				result.push(className);
			}
		});

		return result;
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

	onPinnedPanelChange(params)
	{
		const logId = (params.logId ? parseInt(params.logId) : 0);
		const value = (['Y', 'N'].indexOf(params.value) !== -1 ? params.value : null);
		const pinActionContext = (Type.isStringFilled(params.pinActionContext) ? params.pinActionContext : 'list');

		if (
			!logId
			|| !value
			|| !PinnedPanelInstance.getPinnedPanelNode()
		)
		{
			return;
		}

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
			PinnedPanelInstance.extractEntry({
				logId: logId,
				postNode: postNode,
				containerNode: document.getElementById(this.nodeId.feedContainer)
			});
		}
		else if (value === 'Y')
		{
			if (Type.isDomNode(params.postNode))
			{
				app.showPopupLoader({text:""});

				PinnedPanelInstance.getPinnedData({
					logId: logId
				}).then((pinnedData) => {
					app.hidePopupLoader();

					PinnedPanelInstance.insertEntry({
						logId: logId,
						postNode: params.postNode,
						pinnedContent: pinnedData
					});

				}, (response) => {
					app.hidePopupLoader();
				})
			}
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
		else if (e.target.classList.contains(this.class.addPostButton))
		{
			if (BXMobileAppContext.getApiVersion() >= this.getApiVersion('layoutPostForm'))
			{
				const formManager = new PostFormManager();
				formManager.show({
					pageId: this.getPageId(),
					groupId: this.getOption('groupId', 0),
				});
			}
			else
			{
				app.exec('showPostForm', oMSL.showNewPostForm());
			}
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
				e.target.classList.contains(this.class.postItemPostContentView)
				|| e.target.closest(`.${this.class.postItemPostContentView}`)
				|| e.target.classList.contains(this.class.postItemDescriptionBlock) // tasks
				|| e.target.closest(`.${this.class.postItemDescriptionBlock}`)
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

			let postItemGratitudeUsersSmallContainer = null;
			if (e.target.classList.contains(this.class.postItemGratitudeUsersSmallContainer))
			{
				postItemGratitudeUsersSmallContainer = e.target;
			}
			else
			{
				postItemGratitudeUsersSmallContainer = e.target.closest(`.${this.class.postItemGratitudeUsersSmallContainer}`);
			}

			if (
				expand
				|| Type.isDomNode(postItemGratitudeUsersSmallContainer)
			)
			{
				if (Type.isDomNode(postItemGratitudeUsersSmallContainer))
				{
					postItemGratitudeUsersSmallContainer.style.display = 'none';
					const postItemGratitudeUsersSmallHidden = postItemGratitudeUsersSmallContainer.parentNode.querySelector(`.${this.class.postItemGratitudeUsersSmallHidden}`);
					if (postItemGratitudeUsersSmallHidden)
					{
						postItemGratitudeUsersSmallHidden.style.display = 'block';
					}
				}

				const logId = this.getOption('logId', 0);
				const post = new Post({
					logId: logId
				});

				post.expandText();

				e.stopPropagation();
				return e.preventDefault();
			}

			let importantUserListNode = null;
			if (e.target.classList.contains(this.class.postItemImportantUserList))
			{
				importantUserListNode = e.target;
			}
			else
			{
				importantUserListNode = e.target.closest(`.${this.class.postItemImportantUserList}`)
			}

			if (importantUserListNode)
			{
				const inputNode = importantUserListNode.parentNode.querySelector('input');
				let postId = 0;
				if (Type.isDomNode(inputNode))
				{
					postId = parseInt(inputNode.getAttribute('bx-data-post-id'));
				}

				if (postId > 0)
				{
					app.exec("openComponent", {
						name: "JSStackComponent",
						componentCode: "livefeed.important.list",
						scriptPath: "/mobileapp/jn/livefeed.important.list/?version=1.0.0",

						params: {
							POST_ID: postId,
							SETTINGS: this.getOption('importantData', {}),
						},
						rootWidget: {
							name: 'list',
							settings: {
								objectName: "livefeedImportantListWidget",
								title: BX.message('MOBILE_EXT_LIVEFEED_USERS_LIST_TITLE'),
								modal: false,
								backdrop: {
									mediumPositionPercent: 75
								}
							}
						}
					}, false);

				}
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

	updateFrameCache(params)
	{
		let contentNode = document.getElementById('framecache-block-feed');
		if (!Type.isDomNode(contentNode))
		{
			contentNode = document.getElementById('bxdynamic_feed_refresh');
		}
		if (!Type.isDomNode(contentNode))
		{
			return;
		}

		const props = {
			USE_BROWSER_STORAGE: true,
			AUTO_UPDATE: true,
			USE_ANIMATION: false
		};

		const timestamp = (typeof params.timestamp != 'undefined' ? parseInt(params.timestamp) : 0);
		if (timestamp > 0)
		{
			props.TS = timestamp;
		}

		BX.frameCache.writeCacheWithID(
			'framecache-block-feed',
			contentNode.innerHTML,
			parseInt(Math.random() * 100000),
			JSON.stringify(props)
		);
	}

	onMobilePlayerError(event)
	{
		const [player, src] = event.getData();

		if (!Type.isDomNode(player))
		{
			return;
		}

		if (!Type.isStringFilled(src))
		{
			return;
		}

		const container = player.parentNode;
		if (container)
		{
			if (container.querySelector('.disk-mobile-player-error-container'))
			{
				return;
			}
		}
		else
		{
			if (player.querySelector('.disk-mobile-player-error-container'))
			{
				return;
			}
		}

		const sources = player.getElementsByTagName('source');
		let sourcesLeft = sources.length;

		Array.from(sources).forEach((source) => {
			if (
				Type.isStringFilled(source.src)
				&& source.src === src
			)
			{
				Dom.remove(source);
				sourcesLeft--;
			}
		});

		if (sourcesLeft > 0)
		{
			return;
		}

		const errorContainer = Dom.create('div', {
			props: {
				className: 'disk-mobile-player-error-container'
			},
			children: [
				Dom.create('div', {
					props: {
						className: 'disk-mobile-player-error-icon'
					},
					html: ''
				}),
				Dom.create('div', {
					props: {
						className: 'disk-mobile-player-error-message'
					},
					html: Loc.getMessage('MOBILE_EXT_LIVEFEED_PLAYER_ERROR_MESSAGE')
				})
			]
		});

		const downloadLink = errorContainer.querySelector('.disk-mobile-player-download');
		if (downloadLink)
		{
			Dom.adjust(downloadLink, {
				events: {
					click: () => {
						app.openDocument({
							url: src
						});
					}
				}
			});
		}

		if (container)
		{
			player.style.display = 'none';
			container.appendChild(errorContainer);
		}
		else
		{
			Dom.adjust(player, {
				children: [ errorContainer ]
			});
		}
	}

	getApiVersion(feature)
	{
		let result = 0;
		switch (feature)
		{
			case 'layoutPostForm':
				result = 37;
				break;
			default:
		}

		return result;
	}
}

const Instance = new Feed();
const BalloonNotifierInstance = new BalloonNotifier();
const NotificationBarInstance = new NotificationBar();
const DatabaseUnsentPostInstance = new Database();
const PublicationQueueInstance = new PublicationQueue();
const PostMenuInstance = new PostMenu();
const PostFormManagerInstance = new PostFormManager();
const PinnedPanelInstance = new PinnedPanel();

export {
	Instance,
	BalloonNotifierInstance,
	NotificationBarInstance,
	DatabaseUnsentPostInstance,
	PublicationQueueInstance,
	PostMenuInstance,
	PostFormManagerInstance,
	PinnedPanelInstance
};