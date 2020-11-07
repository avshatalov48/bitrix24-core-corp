import {Dom, Type} from "main.core";

class PinnedPanel
{
	constructor(data)
	{
		this.class = {
			postItemPinned: 'lenta-item-pinned',
			postItemPinActive: 'lenta-item-pin-active',

			postItemPinnedBlock: 'post-item-pinned-block',
			postItemPinnedTitle: 'post-item-pinned-title',
			postItemPinnedTextBox: 'post-item-pinned-text-box',
			postItemPinnedDesc: 'post-item-pinned-desc'
		};
	}

	getPinnedData(params)
	{
		const logId = (params.logId ? parseInt(params.logId) : 0);
		const BMAjaxWrapper = new MobileAjaxWrapper;

		if (logId <= 0)
		{
			return Promise.reject();
		}

		return new Promise((resolve, reject) => {
			BMAjaxWrapper.runAction('socialnetwork.api.livefeed.logentry.getPinData', {
				data: {
					params: {
						logId: logId
					}
				}
			}).then((response) => {
				if (response.status === 'success')
				{
					resolve(response.data);
				}
				else
				{
					reject(response.errors);
				}
			}, (response) => {
				reject();
			});
		})
	}

	insertEntry({
		logId,
		postNode,
		pinnedPanelNode,
		pinnedContent
	})
	{
		if (
			!Type.isDomNode(postNode)
			|| !Type.isDomNode(pinnedPanelNode)
		)
		{
			return;
		}

		const postItemPinnedBlock = postNode.querySelector(`.${this.class.postItemPinnedBlock}`);

		if (!Type.isDomNode(postItemPinnedBlock))
		{
			return;
		}

		postNode.classList.add(this.class.postItemPinned);
		postNode.classList.add(this.class.postItemPinActive);

		postItemPinnedBlock.innerHTML = `${(Type.isStringFilled(pinnedContent.TITLE) ? `<div class="${this.class.postItemPinnedTitle}">${pinnedContent.TITLE}</div>` : '')}<div class="${this.class.postItemPinnedTextBox}"><div class="${this.class.postItemPinnedDesc}">${pinnedContent.DESCRIPTION}</div></div>`;

		Dom.prepend(postNode, pinnedPanelNode);
	}

	extractEntry({
		postNode,
		containerNode
	})
	{
		if (
			!Type.isDomNode(postNode)
			|| !Type.isDomNode(containerNode)
		)
		{
			return;
		}

		postNode.classList.remove(this.class.postItemPinned);
		postNode.classList.remove(this.class.postItemPinActive);

		Dom.prepend(postNode, containerNode);
	}
}

export {
	PinnedPanel
}