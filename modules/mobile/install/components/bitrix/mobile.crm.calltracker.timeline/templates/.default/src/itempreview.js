import Item from './item';
import {Tag, Text, Loc} from "main.core";
import Configuration from './configuration';

export default class ItemPreview extends Item
{
	static checkForPaternity(data)
	{
		return data.constructor.name === 'Comment';
	}

	static count = 0;
	constructor(data)
	{
		const itemData = {
			'ID': 'preview_node_' + (ItemPreview.count++),
			'COMMENT': Text.encode(data.text),
			'AUTHOR_ID': Configuration.currentAuthor.AUTHOR_ID,
			'AUTHOR': Configuration.currentAuthor.AUTHOR,
			'CREATED': (new Date().getTime() / 1000)
		};
		super(itemData);
		data.previewObj = this;
	}

	getNode()
	{
		return this.cache.remember('mainNode', () => {
			const avatarUrl = Text.encode(
				this.data['AUTHOR'] && this.data['AUTHOR']['IMAGE_URL']
					? this.data['AUTHOR']['IMAGE_URL']
					: ''
			);
			let render = Tag.render`<div class="feed-com-block-cover">
				<div class="post-comment-block post-comment-block-old post-comment-block-approved mobile-longtap-menu">
					<div class="ui-icon ui-icon-common-user post-comment-block-avatar">
						${
							avatarUrl
								? Tag.render`<i style="background-image:url('${avatarUrl}')"></i>`
								: Tag.render`<i></i>`
						}
					</div>
					<div class="post-comment-detail">
						<div class="post-comment-balloon">
							<div class="post-comment-cont">
								<a href="" class="post-comment-author">${Text.encode(this.data['AUTHOR']['FORMATTED_NAME'])}</a>
								<div class="post-comment-time">${this.getDateNode()}</div>
							</div>
							<div class="post-comment-wrap-outer">
								<div class="post-comment-wrap">
									${this.getTextNode()}
								</div>
								<div class="post-comment-more" style="display: none;">
									<div class="post-comment-more-but"></div>
								</div>
							</div>
						</div>
						<div class="post-comment-control-box" data-bx-role="loader-block">
							<div class="post-comment-control-item">${Loc.getMessage('MPL_MOBILE_PUBLISHING')}</div>
						</div>
						<div class="post-comment-control-box" data-bx-role="error-block">
							<div class="post-comment-control-item" data-bx-role="error-text"></div>
						</div>
					</div>
				</div>
			</div>`;
			return render;
		});
	}

	getDateNode()
	{
		return '';
	}

	setError(error: Error) {
		this.getNode().setAttribute('data-bx-status', 'failed');
		const errorNode = this.getNode().querySelector('[data-bx-role="error-text"]');
		errorNode.innerHTML = error.message;
	}
}