import {Event, Type} from 'main.core';
import {Ajax} from 'mobile.ajax';

class ImportantManager
{
	constructor()
	{

	}

	setPostRead(node)
	{
		if (
			!Type.isDomNode(node)
			|| node.hasAttribute('done')
		)
		{
			return false;
		}

		const postId = parseInt(node.getAttribute('bx-data-post-id'));
		if (postId <= 0)
		{
			return false;
		}

		this.renderRead({
			node: node,
			value: true,
		});

		Ajax.runAction('socialnetwork.api.livefeed.blogpost.important.vote', {
			data: {
				params: {
					POST_ID : postId,
				}
			},
		}).then((response) => {
			if (
				!Type.isStringFilled(response.data.success)
				|| response.data.success !== 'Y'
			)
			{
				this.renderRead({
					node: node,
					value: false,
				});
			}
			else
			{
				BXMobileApp.onCustomEvent('onLogEntryImpPostRead', {
					postId: postId,
				}, true);
			}
		}, (response) => {
			this.renderRead({
				node: node,
				value: false,
			});
		});

		return true;
	}

	renderRead(params)
	{
		if (
			!Type.isObject(params)
			|| !Type.isDomNode(params.node)
		)
		{
			return;
		}

		const node = params.node;
		const value = !!params.value;

		if (value)
		{
			node.checked = true;
			node.setAttribute('done', 'Y');
			Event.unbindAll(node);
		}
		else
		{
			node.checked = false;
			delete node.checked;
			node.removeAttribute('done');
		}

		const container = node.closest('.post-item-important');
		if (!container)
		{
			return;
		}

		const listNode = container.querySelector('.post-item-important-list');
		if (!listNode)
		{
			return;
		}

		listNode.classList.add('post-item-important-list-read');
	}
}

export {
	ImportantManager,
}