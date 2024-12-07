import { Tag, Dom } from 'main.core';
import { Loader } from 'main.loader';
import { ListItem } from './list-item';
import 'ui.icons';

export class Blank<T> extends ListItem<T>
{
	#placeholder: HTMLElement;
	#preview: HTMLElement;
	#loader: Loader;

	constructor(props: T)
	{
		super({ ...props, modifier: 'blank' });
		this.#placeholder = Tag.render`<div class="sign-blank-selector__list_item-status"></div>`;
		this.#preview = Tag.render`
			<div class="sign-blank-selector__list_item-preview" hidden>
				<img
					onload="${() => {
						this.#preview.hidden = false;
						this.#placeholder.hidden = true;
					}}"
				/>
			</div>
		`;
		this.#loader = new Loader({ size: 30, target: this.#placeholder });
		const layout = this.getLayout();
		Dom.prepend(this.#placeholder, layout);
		Dom.prepend(this.#preview, layout);
	}

	setAvatarWithDescription(description: string, userAvatarUrl: string | null): void
	{
		this.setDescription(description);
		this.setProps({
			...this.getProps(),
			userAvatarUrl,
		});
		const avatarIcon = userAvatarUrl
			? Tag.render`
				<img class="sign-blank-selector__list_item-info-avatar" src="${userAvatarUrl}" />
			`
			: Tag.render`
				<span class="sign-blank-selector__list_item-info-avatar ui-icon ui-icon-common-user">
					<i></i>
				</span>
			`;
		const { lastElementChild: descriptionNode } = this.getLayout();
		Dom.prepend(avatarIcon, descriptionNode);
	}

	select(): void
	{
		Dom.addClass(this.getLayout(), '--active');
	}

	deselect(): void
	{
		Dom.removeClass(this.getLayout(), '--active');
		this.getLayout().blur();
	}

	remove(): void
	{
		Dom.remove(this.getLayout());
	}

	setId(id: number): void
	{
		this.getLayout().dataset.id = id;
	}

	setReady(isReady: boolean): void
	{
		if (!isReady)
		{
			this.#loader.show();

			return;
		}

		const layout = this.getLayout();
		layout.tabIndex = '0';
		this.#loader.hide();
		Dom.addClass(layout, '--loaded');
	}

	setPreview(previewUrl: string): void
	{
		if (previewUrl)
		{
			this.#preview.firstElementChild.src = previewUrl;
		}
	}
}
