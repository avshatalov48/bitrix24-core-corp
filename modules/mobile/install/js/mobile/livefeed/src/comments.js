import {Instance, PageMenuInstance, PageScrollInstance} from './feed';
import {Post} from './post';

import {Loc, Type, Dom, Tag, Runtime} from 'main.core';
import {Ajax} from 'mobile.ajax';
import {BaseEvent, EventEmitter} from 'main.core.events';

class Comments
{
	constructor()
	{
		this.emptyCommentsXhr = null;
		this.repoLog = {};
		this.mid = {};

		this.init();
	}

	init()
	{
		EventEmitter.subscribe('OnUCommentWasDeleted', this.deleteHandler.bind(this));
		EventEmitter.subscribe('OnUCommentWasHidden', this.deleteHandler.bind(this));
		EventEmitter.subscribe('OnUCRecordHasDrawn', this.drawHandler.bind(this));
		EventEmitter.subscribe('OnUCFormSubmit', this.submitHandler.bind(this));
	}

	deleteHandler(event)
	{
		const [ ENTITY_XML_ID, id ] = event.getData();
		const logId = Instance.getLogId();

		if (this.mid[id.join('-')] !== 'hidden')
		{
			this.mid[id.join('-')] = 'hidden';

			if (this.repoLog[logId])
			{
				this.repoLog[logId]['POST_NUM_COMMENTS']--;
				BXMobileApp.onCustomEvent('onLogEntryCommentsNumRefresh', {
					log_id: logId,
					num: this.repoLog[logId]['POST_NUM_COMMENTS']
				}, true);
			}
		}
	}

	drawHandler(event)
	{
		const [ ENTITY_XML_ID, id ] = event.getData();
		const logId = Instance.getLogId();

		this.mid[ENTITY_XML_ID] = (this.mid[ENTITY_XML_ID] || {});
		if (this.mid[id.join('-')] !== 'drawn')
		{
			this.mid[id.join('-')] = 'drawn';
			let node = false;

			if (
				this.repoLog[logId]
				&& (node = document.getElementById(`record-${id.join('-')}-cover`))
				&& node
				&& node.parentNode == document.getElementById(`record-${ENTITY_XML_ID}-new`)
			)
			{
				this.repoLog[logId]['POST_NUM_COMMENTS']++;
				BXMobileApp.onCustomEvent('onLogEntryCommentsNumRefresh', {
					log_id: logId,
					num: this.repoLog[logId]['POST_NUM_COMMENTS']
				}, true);
			}
		}
	}

	submitHandler(event)
	{
		const [ entity_xml_id, id, obj, post_data ] = event.getData();

		if (
			post_data
			&& post_data.mobile_action
			&& post_data.mobile_action === 'add_comment'
			&& id > 0
		)
		{
			post_data.mobile_action = post_data.action = 'edit_comment';
			post_data.edit_id = id;
		}
	}

	setRepoItem(id, data)
	{
		this.repoLog[id] = data;
	}

	getList(params)
	{
		const timestampValue = params.ts;
		const pullDown = !!params.bPullDown;
		const pullDownTop = (Type.isUndefined(params.bPullDownTop) || params.bPullDownTop);

		const moveBottom = (
			Type.isUndefined(params.obFocus.form)
			|| params.obFocus.form === 'NO'
				? 'NO'
				: 'YES'
		);
		const moveTop = (
			Type.isUndefined(params.obFocus.comments)
			|| params.obFocus.comments === 'NO'
				? 'NO'
				: 'YES'
		);
		const logId = oMSL.logId;
		const container = document.getElementById('post-comments-wrap');

		if (!pullDown)
		{
			if (pullDownTop)
			{
				BXMobileApp.UI.Page.Refresh.start();
			}

			Dom.clean(container);
			container.appendChild(Tag.render`<span id="post-comment-last-after"></span>`);
		}

		this.showEmptyListWaiter({
			container: container,
			enable: true,
		});

		EventEmitter.emit('BX.MobileLF:onCommentsGet');
		BXMobileApp.UI.Page.TextPanel.hide();

		this.emptyCommentsXhr = Ajax.wrap({
			type: 'json',
			method: 'POST',
			url: `${Loc.getMessage('MSLPathToLogEntry').replace("#log_id#", logId)}&empty_get_comments=Y${(!Type.isNil(timestampValue) ? `&LAST_LOG_TS=${timestampValue}` : '')}`,
			data: {},
			processData: true,
			callback: (response) => {

				const formWrap = document.getElementById('post-comments-form-wrap');

				if (pullDown)
				{
					app.exec('pullDownLoadingStop');
				}
				else if (pullDownTop)
				{
					BXMobileApp.UI.Page.Refresh.stop();
				}

				this.showEmptyListWaiter({
					container: container,
					enable: false,
				});

				if (Type.isStringFilled(response.POST_PERM))
				{
					oMSL.menuData.post_perm = response.POST_PERM;
					PageMenuInstance.detailPageMenuItems = PageMenuInstance.buildDetailPageMenu(oMSL.menuData);
					PageMenuInstance.init({
						type: 'detail',
					});
				}

				if (Type.isStringFilled(response.TEXT))
				{
					if (pullDown)
					{
						Dom.clean(container);
						if (!Type.isUndefined(response.POST_NUM_COMMENTS))
						{
							BXMobileApp.onCustomEvent('onLogEntryCommentsNumRefresh', {
								log_id: logId,
								num: parseInt(response.POST_NUM_COMMENTS)
							}, true);
						}
					}

					this.setRepoItem(logId, {
						POST_NUM_COMMENTS: response.POST_NUM_COMMENTS
					});

					const contentData = BX.processHTML(response.TEXT, true);

					Runtime.html(container, contentData.HTML).then(() => {
						setTimeout(() => {
							BitrixMobile.LazyLoad.showImages();
						}, 1000);
					});
					container.appendChild(Tag.render`<span id="post-comment-last-after"></span>`);

					let cnt = 0;
					const func = () => {
						cnt++;
						if (cnt < 100)
						{
							if (container.childNodes.length > 0)
							{
								BX.ajax.processScripts(contentData.SCRIPT);
							}
							else
							{
								BX.defer(func, this)();
							}
						}
					};
					BX.defer(func, this)();

					const event = new BaseEvent({
						compatData: [{
							mobile: true,
							ajaxUrl: `${Loc.getMessage('MOBILE_EXT_LIVEFEED_SITE_DIR')}mobile/ajax.php`,
							commentsContainerId: 'post-comments-wrap',
							commentsClassName: 'post-comment-wrap',
						}],
					});
					EventEmitter.emit('BX.UserContentView.onInitCall', event);
					EventEmitter.emit('BX.UserContentView.onClearCall', event);

					if (!pullDown) // redraw form
					{
						if (formWrap)
						{
							formWrap.innerHTML = '';
						}

						__MSLDetailPullDownInit(true);

						if (moveBottom === 'YES')
						{
							this.setFocusOnComments('form');
						}
						else if (moveTop == 'YES')
						{
							this.setFocusOnComments('list');
						}
					}

					Instance.setLastActivityDate();
					PageScrollInstance.checkScrollButton();

					const logIdContainer = document.getElementById('post_log_id');
					if (
						!Type.isUndefined(response.TS)
						&& logIdContainer
					)
					{
						logIdContainer.setAttribute('data-ts', response.TS);
					}
				}
				else
				{
					if (!pullDown)
					{
						this.showEmptyListWaiter({
							container: container,
							enable: false,
						});
					}

					app.alert({
						title: Loc.getMessage('MOBILE_EXT_LIVEFEED_ALERT_ERROR_TITLE'),
						text: Loc.getMessage('MOBILE_EXT_LIVEFEED_ALERT_ERROR_POST_NOT_FOUND_TEXT'),
						button: Loc.getMessage('MOBILE_EXT_LIVEFEED_ALERT_ERROR_BUTTON'),
						callback: () => {
							BXMobileApp.onCustomEvent('Livefeed::onLogEntryDetailNotFound', {
								logId: logId,
							}, true);
							BXMPage.close();
						}
					});
				}
			},
			callback_failure: () => {
				if (pullDown)
				{
					app.exec('pullDownLoadingStop');
				}
				else
				{
					BXMobileApp.UI.Page.Refresh.stop();
				}
				this.showEmptyListWaiter({
					container: container,
					enable: false,
				});
				this.showEmptyListFailed({
					container,
					timestampValue,
					pullDown,
					moveBottom
				});
			}
		});
	}

	showEmptyListWaiter(params)
	{
		const container = params.container;
		const enable = !!params.enable;

		if (!Type.isDomNode(container))
		{
			return;
		}

		const waiterNode = container.querySelector('.post-comments-load-btn-wrap');
		if (waiterNode)
		{
			Dom.clean(waiterNode);
			Dom.remove(waiterNode);
		}

		if (!enable)
		{
			return;
		}

		container.appendChild(Tag.render`<div class="post-comments-load-btn-wrap"><div class="post-comments-loader"></div><div class="post-comments-load-text">${Loc.getMessage('MSLDetailCommentsLoading')}</div></div>`);
	}

	showEmptyListFailed(params)
	{
		const {
			container,
			timestampValue,
			pullDown,
			moveBottom,
			data,
		} = params;


		if (!Type.isDomNode(container))
		{
			return;
		}

		const errorMessage = (
			Type.isObject(data)
			&& Type.isStringFilled(data.ERROR_MESSAGE)
				? data.ERROR_MESSAGE
				: Loc.getMessage('MSLDetailCommentsFailed')
		);

		container.appendChild(Tag.render`<div class="post-comments-load-btn-wrap"><div class="post-comments-load-text">${errorMessage}</div><a class="post-comments-load-btn">${Loc.getMessage('MSLDetailCommentsReload')}</a></div>`);
		const button = container.querySelector('.post-comments-load-btn');
		if (!button)
		{
			return;
		}
		button.addEventListener('click', (event) => {
			if (Type.isDomNode(event.target.parent))
			{
				Dom.clean(event.target.parent);
				Dom.remove(event.target.parent);
			}

			// repeat get comments request (after error shown)
			this.getList({
				ts: timestampValue,
				bPullDown: pullDown,
				obFocus: {
					form: false,
				}
			});
		});

		button.addEventListener('touchstart', (event) => { event.target.classList.add('post-comments-load-btn-active'); });
		button.addEventListener('touchend', (event) => { event.target.classList.remove('post-comments-load-btn-active'); });
	}

	abortXhr()
	{
		if (this.emptyCommentsXhr)
		{
			this.emptyCommentsXhr.abort();
		}
	}

	setFocusOnComments(type)
	{
		type = (type === 'list' ? 'list' : 'form');

		if (type === 'form')
		{
			this.setFocusOnCommentForm();
			Post.moveBottom();
		}
		else if (type === 'list')
		{
			const container = document.getElementById('post-comments-wrap');
			if (!container)
			{
				return false;
			}

			const firstNewComment = container.querySelector('.post-comment-block-new');
			if (firstNewComment)
			{
				window.scrollTo(0, firstNewComment.offsetTop);
			}
			else
			{
				var firstComment = BX.findChild(container, { className : 'post-comment-block' }, true);
				window.scrollTo(0, (firstComment ? firstComment.offsetTop : 0));
			}
		}

		return false;
	}

	setFocusOnCommentForm()
	{
		BXMobileApp.UI.Page.TextPanel.focus();
		return false;
	}

	onLogEntryCommentAdd(logId, value) // for the feed
	{
		let newValue;

		const valuePassed = (!Type.isUndefined(value));

		value = (!Type.isUndefined(value) ? parseInt(value) : 0);

		const container = document.getElementById(`informer_comments_${logId}`);
		const containerNew = document.getElementById(`informer_comments_new_${logId}`);

		if (
			container
			&& !containerNew
		) // detail page
		{
			if (value > 0)
			{
				newValue = value;
			}
			else if (!valuePassed)
			{
				newValue = (container.innerHTML.length > 0 ? parseInt(container.innerHTML) : 0) + 1;
			}

			if (parseInt(newValue) > 0)
			{
				container.innerHTML = newValue;
				container.style.display = 'inline-block';

				if (document.getElementById(`informer_comments_text2_${logId}`))
				{
					document.getElementById(`informer_comments_text2_${logId}`).style.display = 'inline-block';
				}
				if (document.getElementById(`informer_comments_text_${logId}`))
				{
					document.getElementById(`informer_comments_text_${logId}`).style.display = 'none';
				}
			}
		}

		const containerAll = document.getElementById('comcntleave-all');
		if (containerAll) // more comments
		{
			if (value > 0)
			{
				newValue = value;
			}
			else if (!valuePassed)
			{
				newValue = (containerAll.innerHTML.length > 0 ? parseInt(containerAll.innerHTML) : 0) + 1;
			}
			containerAll.innerHTML = newValue;
		}
	}
}

export {
	Comments
}