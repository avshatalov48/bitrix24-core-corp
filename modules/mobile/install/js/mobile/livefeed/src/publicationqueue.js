import {Tag, Dom, Loc, Type, Event} from "main.core";
import {Instance} from "./feed";
import {EventEmitter} from 'main.core.events';

class PublicationQueue extends EventEmitter
{
	constructor()
	{
		super();

		this.repo = {};

		this.nodeId = {
			container: 'post-balloon-container'
		};

		this.class = {
			balloonHidden: 'post-balloon-hidden',
			balloonFixed: 'post-balloon-box-fixed',
			balloonPublished: 'post-balloon-done',
			balloonShow: 'post-balloon-show',
			balloonHide: 'post-balloon-hide'
		};

		this.timeout = {
			show: 750
		};

		this.init();
	}

	init()
	{
		BXMobileApp.addCustomEvent('Livefeed.PublicationQueue::afterSetItem', this.afterSetItem.bind(this));
		BXMobileApp.addCustomEvent('Livefeed.PublicationQueue::afterPostAdd', this.afterPostAdd.bind(this));
		BXMobileApp.addCustomEvent('Livefeed.PublicationQueue::afterPostAddError', this.afterPostAddError.bind(this));
		BXMobileApp.addCustomEvent('Livefeed.PublicationQueue::afterPostUpdate', this.afterPostUpdate.bind(this));
		BXMobileApp.addCustomEvent('Livefeed.PublicationQueue::afterPostUpdateError', this.afterPostUpdateError.bind(this));

		this.setEventNamespace('BX.Mobile.Livefeed');
		this.subscribe('onFeedReady', this.onFeedLoaded.bind(this));
		this.subscribe('onPostInserted', this.onPostInserted.bind(this));

		Event.bind(document, 'scroll', this.onScroll.bind(this));
	}

	onScroll()
	{
		const
			containerNode = document.getElementById(this.nodeId.container);

		if (!Type.isDomNode(containerNode))
		{
			return;
		}

		if (window.pageYOffset > 0)
		{
			containerNode.classList.add(this.class.balloonFixed);
		}
		else
		{
			containerNode.classList.remove(this.class.balloonFixed);
		}
	}

	onFeedLoaded()
	{
		app.exec('getStorageValue', {
			storageId: 'livefeed',
			key: 'publicationQueue',
			callback: (queue) =>
			{
				queue = (Type.isPlainObject(queue) ? queue : (Type.isStringFilled(queue) ? JSON.parse(queue) : {}));

				if (!Type.isPlainObject(queue))
				{
					return;
				}

				for (let key in queue)
				{
					if (!queue.hasOwnProperty(key))
					{
						continue;
					}

					this.addToTray(key, {});
				}

				this.drawList();
			}
		});
	}

	onPostInserted(event)
	{
		this.removeFromTray(event.data.key);
	}

	addToTray(key, params)
	{
		this.repo[key] = params;
		this.repo[key].node = this.drawItem();
		this.repo[key].node.classList.add(this.class.balloonShow);
	}
	removeFromTray(key, params)
	{
		this.hideItem(key);

		setTimeout(() => {
			if (this.repo[key])
			{
				delete this.repo[key];
			}
		}, 3000);
	}

	addSuccess(key, warningText)
	{
		if (
			this.repo[key]
			&& this.repo[key].node
		)
		{
			this.repo[key].node.classList.remove(this.class.balloonHidden);
			this.repo[key].node.classList.remove(this.class.balloonShow);
			this.repo[key].node.classList.add(this.class.balloonPublished);
			this.repo[key].node.lastElementChild.innerHTML = (Type.isStringFilled(warningText) ? warningText : Loc.getMessage('MOBILE_EXT_LIVEFEED_PUBLICATION_QUEUE_SUCCESS_TITLE'));
		}

		setTimeout(() => {
			this.removeFromTray(key);
		}, 5000);
	}

	drawItem()
	{
		const
			title = Loc.getMessage('MOBILE_EXT_LIVEFEED_PUBLICATION_QUEUE_ITEM_TITLE');

		return Tag.render`
				<div class="post-balloon-hidden post-balloon post-balloon-active"><span class="post-balloon-icon"></span><span class="post-balloon-text">${title}</span></div>
			`;
	}

	hideItem(key, params)
	{
		if (this.repo[key])
		{
			this.repo[key].node.classList.add(this.class.balloonHide);
		}
	}

	drawList()
	{
		const
			containerNode = document.getElementById(this.nodeId.container);

		if (!Type.isDomNode(containerNode))
		{
			return;
		}

		Dom.clean(containerNode);

		for (let key in this.repo)
		{
			if (
				!this.repo.hasOwnProperty(key)
				|| !Type.isDomNode(this.repo[key].node)
			)
			{
				continue;
			}

			Dom.append(this.repo[key].node, containerNode);
		}
	}

	afterSetItem(params)
	{
		const
			key = params.key ? params.key : '',
			pageId = params.pageId ? params.pageId : '',
			contentType = params.contentType ? params.contentType : '';

		if (
			pageId != Instance.getPageId()
			|| !key
			|| !Type.isStringFilled(contentType)
		)
		{
			return;
		}

		if (contentType == 'post')
		{
			this.addToTray(key, {
				key: key
			});

			setTimeout(() => {
				this.drawList();
			}, this.timeout.show);
		}
	}

	afterPostAdd(params)
	{
		if (!Type.isPlainObject(params))
		{
			params = {};
		}

		this.addSuccess((params.key ? params.key : ''), params.warningText);
		this.drawList();
	}

	afterPostUpdate(params)
	{
		if (!Type.isPlainObject(params))
		{
			params = {};
		}

		this.addSuccess(params.key ? params.key : '');
		this.drawList();
	}

	afterPostAddError(params)
	{
		if (!Type.isPlainObject(params))
		{
			params = {};
		}

		const
			key = params.key ? params.key : '';

		this.removeFromTray(key, {
			key: key
		});
		this.drawList();
	}

	afterPostUpdateError(params)
	{
		if (!Type.isPlainObject(params))
		{
			params = {};
		}

		const
			key = params.key ? params.key : '';

		this.removeFromTray(key, {
			key: key
		});
		this.drawList();
	}
}

export {
	PublicationQueue
}