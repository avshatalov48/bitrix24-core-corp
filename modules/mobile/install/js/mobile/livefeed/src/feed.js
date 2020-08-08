import {BalloonNotifier} from "./balloonnotifier";
import {NotificationBar} from "./notificationbar";
import {Database} from "./database";
import {PublicationQueue} from "./publicationqueue";
import {Dom, Tag, Loc, Type, ajax, Runtime} from "main.core";
import {Event} from "main.core.events";
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
			postNewContainerTransformNew: 'lenta-item-new-cont',
			postNewContainerTransform: 'lenta-item-transform-cont',
			postLazyLoadCheck:  'lenta-item-lazyload-check',
			post: 'lenta-item',
			postItemTopWrap: 'post-item-top-wrap',
			postItemTop: 'post-item-top',
			postItemPostBlock: 'post-item-post-block',
			postItemAttachedFileWrap: 'post-item-attached-disk-file-wrap',
			postItemInformWrap: 'post-item-inform-wrap',
			postItemInformWrapTree: 'post-item-inform-wrap-tree'
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

		Event.EventEmitter.subscribe('BX.LazyLoad:ImageLoaded', this.onLazyLoadImageLoaded.bind(this));
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

		const
			postId = (typeof params.postId != 'undefined' ? parseInt(params.postId) : 0),
			context = (typeof params.context != 'undefined' ? params.context : ''),
			pageId = (typeof params.pageId != 'undefined' ? params.pageId : ''),
			groupId = (typeof params.groupId != 'undefined' ? params.groupId : null);

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

		const
			context = (typeof params.context != 'undefined' ? params.context : ''),
			pageId = (typeof params.pageId != 'undefined' ? params.pageId : ''),
			postId = (typeof params.postId != 'undefined' ? parseInt(params.postId) : 0);

		this.getEntryContent({
			entityType: 'BLOG_POST',
			entityId: postId,
			queueKey: params.key,
			action: 'update'
		});
	}

	afterPostAddError(params)
	{
		if (!Type.isPlainObject(params))
		{
			params = {};
		}

		const
			context = (Type.isStringFilled(params.context) ? params.context : ''),
			groupId = (params.groupId ? params.groupId : '');

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

		params.callback = () => {
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

		const
			context = (Type.isStringFilled(params.context) ? params.context : '');

		params.callback = () => {
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

		params.callback = (Type.isFunction(params.callback) ? params.callback : () => {});

		const
			errorText = (Type.isStringFilled(params.errorText) ? params.errorText : false);

		NotificationBarInstance.showError({
			text: (errorText ? errorText : Loc.getMessage('MOBILE_EXT_LIVEFEED_PUBLICATION_ERROR')),
			onTap: (notificationParams) => {
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

		app.showPopupLoader({text: (
				Type.isStringFilled(params.text)
					? params.text
					: ''
			)});
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

		const
			logId = (params.logId ? parseInt(params.logId) : 0),
			BMAjaxWrapper = new MobileAjaxWrapper;

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
					entityType: (Type.isStringFilled(params.entityType) ? params.entityType : ''),
					entityId: (parseInt(params.entityId) > 0 ? parseInt(params.entityId) : 0),
					siteTemplateId: BX.message('MOBILE_EXT_LIVEFEED_SITE_TEMPLATE_ID')
				}
			}
		}).then((response) => {
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
				}).then((responseLogId) => {
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
			}
			else
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

		const
			content = contentWrapper.querySelector(selector),
			container = postContainer.querySelector(selector);

		if (container && content)
		{
			return Runtime.html(container, content.innerHTML);
		}

		return Promise.reject();
	}

	insertPost(params)
	{
		const
			containerNode = document.getElementById(this.nodeId.feedContainer),
			content = params.content,
			logId = params.logId,
			queueKey = params.queueKey,
			action = params.action;

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
				this.processDetailBlock(postContainer, contentWrapper, `.${this.class.postItemPostBlock}`).then(() => {
					BitrixMobile.LazyLoad.showImages();
				});
				this.processDetailBlock(postContainer, contentWrapper, `.${this.class.postItemAttachedFileWrap}`).then(() => {
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
					BitrixMobile.LazyLoad.showImages();
				});
			}

			contentWrapper.remove();
		}
		else if (action === 'add')
		{
			this.setNewPostContainer(Tag.render`<div class="${this.class.postNewContainerTransformNew} ${this.class.postLazyLoadCheck}" ontransitionend="${this.handleInsertPostTransitionEnd.bind(this)}"></div>`);
			Dom.prepend(this.getNewPostContainer(), containerNode);
			Utils.htmlWithInlineJS(this.getNewPostContainer(), content).then(() => {
				const
					postNode = this.getNewPostContainer().querySelector(`div.${this.class.post}`);

				Dom.style(this.getNewPostContainer(), 'height', `${postNode.scrollHeight + 15/*margin-bottom*/}px`);
			});
		}

		PublicationQueueInstance.emit('onPostInserted', new Event.BaseEvent({
			data: {
				key: queueKey
			}
		}));
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

		const [ imageNode ] = event.getData();
		if (imageNode)
		{
			const postCheckNode = imageNode.closest('.' + this.class.postLazyLoadCheck);
			if (postCheckNode)
			{
				const
					postNode = postCheckNode.querySelector(`div.${this.class.post}`);

				if (postNode)
				{
					postCheckNode.classList.add(this.class.postNewContainerTransform);
					Dom.style(postCheckNode, 'height', `${postNode.scrollHeight}px`);
					setTimeout(() => {
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
}


const
	Instance = new Feed(),
	BalloonNotifierInstance = new BalloonNotifier(),
	NotificationBarInstance = new NotificationBar(),
	DatabaseUnsentPostInstance = new Database(),
	PublicationQueueInstance = new PublicationQueue();

export {
	Instance,
	BalloonNotifierInstance,
	NotificationBarInstance,
	DatabaseUnsentPostInstance,
	PublicationQueueInstance
};