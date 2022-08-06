import {Dom, Type, Tag, Loc} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Post} from "./post";
import {Ajax} from 'mobile.ajax';
import {PageInstance} from './feed';

class PinnedPanel
{
	constructor()
	{
		this.panelInitialized = false;

		this.class = {

			panel: 'lenta-pinned-panel',
			panelCollapsed: 'lenta-pinned-panel-collapsed',
			panelActive: 'lenta-pinned-panel-active',

			collapsedPanel: 'lenta-pinned-collapsed-posts',
			collapsedPanelPostsValue: 'lenta-pinned-collapsed-count-posts',
			collapsedPanelComments: 'lenta-pinned-collapsed-posts-comments',
			collapsedPanelCommentsShown: 'lenta-pinned-collapsed-posts-comments-active',
			collapsedPanelCommentsValue: 'lenta-pinned-collapsed-count-comments',
			collapsedPanelCommentsValueNew: 'post-item-inform-right-new-value',

			collapsedPanelExpandButton: 'lenta-pinned-collapsed-posts-btn',

			post: 'lenta-item',

			postItemPinned: 'lenta-item-pinned',
			postItemPinActive: 'lenta-item-pin-active',

			postItemPinnedBlock: 'post-item-pinned-block',
			postItemPinnedTitle: 'post-item-pinned-title',
			postItemPinnedTextBox: 'post-item-pinned-text-box',
			postItemPinnedDesc: 'post-item-pinned-desc',

			cancelPanel: 'post-pinned-cancel-panel',
			cancelPanelButton: 'post-pinned-cancel-panel-btn'
		};

		this.init();
		EventEmitter.subscribe('onFrameDataProcessed', () => {
			this.init();
		});
	}

	init()
	{
		const panel = document.querySelector(`.${this.class.panel}`);
		if (
			!panel
			|| this.panelInitialized
		)
		{
			return;
		}

		this.panelInitialized = true;

		this.adjustCollapsedPostsPanel();
		const collapsedPanel = document.querySelector(`.${this.class.collapsedPanel}`);
		if (collapsedPanel)
		{
			collapsedPanel.addEventListener('touchend', (e) => {
				this.expandCollapsedPostsPanel();

				e.stopPropagation();
				return e.preventDefault();
			});
		}
	}

	resetFlags()
	{
		this.panelInitialized = false;
	}

	getPinnedPanelNode()
	{
		return document.querySelector('[data-livefeed-pinned-panel]');
	}

	recalcPanel({type})
	{
		if (this.getPostsCount() > 0)
		{
			this.getPinnedPanelNode().classList.add(`${this.class.panelActive}`);
		}
		else
		{
			this.getPinnedPanelNode().classList.remove(`${this.class.panelActive}`);
		}

		this.recalcCollapsedPostsPanel({type})
	}

	recalcCollapsedPostsPanel({type})
	{
		if (this.getPostsCount() >= Loc.getMessage('MOBILE_EXT_LIVEFEED_COLLAPSED_PINNED_PANEL_ITEMS_LIMIT'))
		{
			if (
				type === 'insert'
				|| this.getPinnedPanelNode().classList.contains(`${this.class.panelCollapsed}`)
			)
			{
				this.getPinnedPanelNode().classList.add(`${this.class.panelCollapsed}`);
			}
		}
		else
		{
			this.getPinnedPanelNode().classList.remove(`${this.class.panelCollapsed}`);
		}

		this.adjustCollapsedPostsPanel();
	}

	expandCollapsedPostsPanel()
	{
		this.getPinnedPanelNode().classList.remove(`${this.class.panelCollapsed}`);
	}

	getPostsCount()
	{
		return Array.from(this.getPinnedPanelNode().getElementsByClassName(`${this.class.post}`)).length;
	}

	adjustCollapsedPostsPanel()
	{
		const postsCounter = this.getPostsCount();
		const postsCounterNode = this.getPinnedPanelNode().querySelector(`.${this.class.collapsedPanelPostsValue}`);

		if (postsCounterNode)
		{
			postsCounterNode.innerHTML = parseInt(postsCounter);
		}

		const commentsCounterNode = this.getPinnedPanelNode().querySelector(`.${this.class.collapsedPanelComments}`);
		const commentsCounterValueNode = this.getPinnedPanelNode().querySelector(`.${this.class.collapsedPanelCommentsValue}`);

		if (
			commentsCounterNode
			&& commentsCounterValueNode
		)
		{
			const newCommentCounter = Array.from(this.getPinnedPanelNode().querySelectorAll(`.${this.class.collapsedPanelCommentsValueNew}`)).reduce((acc, node) => {
				return acc + parseInt(node.innerHTML);
			}, 0);

			commentsCounterValueNode.innerHTML = '+' + newCommentCounter;
			if (newCommentCounter > 0)
			{
				commentsCounterNode.classList.add(`${this.class.collapsedPanelCommentsShown}`);
			}
			else
			{
				commentsCounterNode.classList.remove(`${this.class.collapsedPanelCommentsShown}`);
			}
		}
	}

	getPinnedData(params)
	{
		const logId = (params.logId ? parseInt(params.logId) : 0);

		if (logId <= 0)
		{
			return Promise.reject();
		}

		return new Promise((resolve, reject) => {
			Ajax.runAction('socialnetwork.api.livefeed.logentry.getPinData', {
				data: {
					params: {
						logId: logId
					}
				},
				headers: [
					{
						name: Loc.getMessage('MOBILE_EXT_LIVEFEED_AJAX_ENTITY_HEADER_NAME'),
						value: params.entityValue || '',
					},
					{
						name: Loc.getMessage('MOBILE_EXT_LIVEFEED_AJAX_TOKEN_HEADER_NAME'),
						value: params.tokenValue || '',
					}
				],
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
		pinnedContent
	})
	{
		const pinnedPanelNode = this.getPinnedPanelNode();

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

		const cancelPinnedPanel = Tag.render`<div class="${this.class.cancelPanel}" data-livefeed-id="${logId}">
			<div class="post-pinned-cancel-panel-content">
				<div class="post-pinned-cancel-panel-label">${Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_PINNED_CANCEL_TITLE')}</div>
					<div class="post-pinned-cancel-panel-text">${Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_PINNED_CANCEL_DESCRIPTION')}</div>
				</div>
			<div class="ui-btn ui-btn-light-border ui-btn-round ui-btn-sm ${this.class.cancelPanelButton}">${Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_PINNED_CANCEL_BUTTON')}</div>
		</div>`;

		const cancelButton = cancelPinnedPanel.querySelector(`.${this.class.cancelPanelButton}`);
		cancelButton.addEventListener('touchend', (event) => {

			const cancelPanel = event.currentTarget.closest(`.${this.class.cancelPanel}`);
			if (!cancelPanel)
			{
				return;
			}

			const logId = parseInt(cancelPanel.getAttribute('data-livefeed-id'));
			if (logId <= 0)
			{
				return;
			}

			const postNode = document.querySelector(`.${this.class.post}[data-livefeed-id="${logId}"]`);
			if (!postNode)
			{
				return;
			}

			const menuNode = postNode.querySelector('[data-menu-type="post"]');
			if (!menuNode)
			{
				return;
			}

			const postInstance = new Post({
				logId
			});

			return postInstance.setPinned({
				menuNode: menuNode,
				context: 'list'
			});
		});

		postNode.parentNode.insertBefore(cancelPinnedPanel, postNode);
		Dom.prepend(postNode, pinnedPanelNode);

		this.recalcPanel({
			type: 'insert'
		});
		PageInstance.onScroll();
	}

	extractEntry({
		logId,
		postNode,
		containerNode
	})
	{
		const pinnedPanelNode = this.getPinnedPanelNode();

		if (
			!Type.isDomNode(postNode)
			|| !Type.isDomNode(containerNode)
			|| !Type.isDomNode(pinnedPanelNode)
			|| postNode.parentNode !== pinnedPanelNode
			|| parseInt(logId) <= 0
		)
		{
			return;
		}

		postNode.classList.remove(this.class.postItemPinned);
		postNode.classList.remove(this.class.postItemPinActive);

		const cancelPanel = document.querySelector(`.${this.class.cancelPanel}[data-livefeed-id="${parseInt(logId)}"]`);
		if (cancelPanel)
		{
			cancelPanel.parentNode.insertBefore(postNode, cancelPanel);
			Dom.remove(cancelPanel);
		}
		else
		{
			Dom.prepend(postNode, containerNode);
		}

		this.recalcPanel({
			type: 'extract'
		});
	}
}

export {
	PinnedPanel
}