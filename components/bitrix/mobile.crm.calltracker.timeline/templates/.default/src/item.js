import {Cache, Tag, Text, Type, Runtime, pos} from 'main.core';
import 'main.polyfill.intersectionobserver';
import Backend from "./backend";

let intersectionObserver;
function observeIntersection(entity, callback)
{
	if (!intersectionObserver)
	{
		intersectionObserver = new IntersectionObserver(function(entries) {
			entries.forEach((entry) => {
				if (entry.isIntersecting)
				{
					intersectionObserver.unobserve(entry.target);
					const observedCallback = entry.target.observedCallback;
					delete entry.target.observedCallback;
					setTimeout(observedCallback);
				}
			});
		}, {
			threshold: 0
		});
	}
	entity.observedCallback = callback;

	intersectionObserver.observe(entity);
}

export default class Item
{
	static checkForPaternity()
	{
		return true;
	}
	static renderWithDebounce = Runtime.debounce(function() {
		BitrixMobile.LazyLoad.showImages();
	}, 500);


	constructor(data)
	{
		this.id = data['ID'];
		this.data = data;

		this.cache = new Cache.MemoryCache();

		Item.renderWithDebounce();
	}

	getId()
	{
		return this.id;
	}
	getOwnerId()
	{
		return ['OWN', this.getId()].join('_');
	}
	getNode()
	{
		return this.cache.remember('mainNode', () => {
			const avatarUrl = Text.encode(
				this.data['AUTHOR'] && this.data['AUTHOR']['IMAGE_URL']
				? this.data['AUTHOR']['IMAGE_URL']
				: ''
			);
			const expand = () => {
				const node = this.getNode().querySelector('div[data-bx-role="more-button"]');
				node.parentNode.removeChild(node);

				const wrapper = this.getTextNode().parentNode;
				const startHeight = pos(wrapper).height;
				const endHeight = pos(this.getTextNode()).height;

				wrapper.style.maxHeight = startHeight + 'px';
				wrapper.style.overflow = 'hidden';

				let time = (endHeight - startHeight) / (2000 - startHeight);
				time = (time < 0.3 ? 0.3 : (time > 0.8 ? 0.8 : time));

				(new BX["easing"]({
					duration: time * 1000,
					start: { height: startHeight},
					finish: { height: endHeight},
					transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
					step: function(state){
						wrapper.style.maxHeight = state.height + "px";
					},
					complete : function(){
						wrapper.style.cssText = '';
						wrapper.style.maxHeight = 'none';
						BX.LazyLoad.showImages(true);
					}
				})).animate();
			};
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
								<div class="post-comment-more" data-bx-role="more-button" onclick="${expand}">
									<div class="post-comment-more-but"></div>
								</div>
							</div>
						</div>
						${this.getFilesNode()}
						${this.getActionNode()}
					</div>
				</div>
			</div>`;
			return render;
		});
	}

	getTextNode()
	{
		return this.cache.remember('textNode', () => {
			const renderTag = Tag.render`<div class="post-comment-text"></div>`
			Runtime.html(renderTag, this.data['COMMENT']);

			return renderTag;
		});
	}

	getDateNode()
	{
		return this.cache.remember('dateNode', () => {
			if (Type.isStringFilled(this.data['CREATED']))
			{
				return this.data['CREATED'];
			}
			return BX.formatDate();
		});
	}

	getFilesNode()
	{
		return this.cache.remember('filesBlock', () => {
			if (this.data['HAS_FILES'] !== 'Y')
			{
				return '';
			}

			const renderTag = Tag.render`
		<div class="post-item-attached-file-wrap">
			<div class="post-item-attached-file-list">
			</div>
		</div>`;

			if (Type.isStringFilled(this.data['PARSED_ATTACHMENT']))
			{
				Runtime.html(renderTag, this.data['PARSED_ATTACHMENT']);
			}
			else
			{
				setTimeout(() => {
					observeIntersection(renderTag, () => {
						const options = ['GET_FILE_BLOCK'];
						this.data['HAS_INLINE_ATTACHMENT'] === 'Y' ? options.push('GET_COMMENT') : null;
						Backend
							.getItem(this.id, options)
							.then(({data: {files, text}, errors}) => {
								if (Type.isStringFilled(files))
								{
									Runtime.html(renderTag, files);
								}
								if (Type.isStringFilled(text))
								{
									Runtime.html(this.getTextNode(), text);
								}
							}, ({errors}) => {
								const errorMessages = [];
								errors.forEach((error) => {
									errorMessages.push(error.message);
								});
							});
					});
				}, 100);
			}

			return Tag.render`<div class="post-item-attached-file-wrap">${renderTag}</div>`;
		});
	}

	getActionNode()
	{
		return this.cache.remember('actionNode', () => {
			return '';
			return Tag.render`
				<div class="post-comment-control-box">
					<div class="post-comment-control-item">Edit</div>
					<div class="post-comment-control-item">Delete</div>
				</div>`;
		});
	}
}