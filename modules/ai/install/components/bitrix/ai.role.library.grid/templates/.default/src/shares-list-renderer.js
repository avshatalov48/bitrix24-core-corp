import { Tag, Type, Text } from 'main.core';
import { ListRenderer } from './list-renderer';
import { highlightText } from './helpers';

type SharesListItem = {
	name: string;
	img: ?string;
}

export class SharesListRenderer extends ListRenderer
{
	render(sharesList: SharesListItem[], searchValue: string): HTMLElement
	{
		const search = searchValue || null;
		const itemsElements = sharesList.map((item: SharesListItem) => {
			const encodedName = Text.encode(item.name);

			const highlightedName = highlightText(encodedName, search);

			return Tag.render`
				<li class="ai__role-library-grid-shares-popup_shares-list-item">
					<div class="ai__role-library-grid-shares-popup_shares-list-item-avatar">
						${this.#renderShareItemImg(item)}
					</div>
					<div class="ai__role-library-grid-shares-popup_shares-list-item-title">
						${highlightedName}
					</div>
				</li>
			`;
		});

		return Tag.render`<ul class="ai__role-library-grid-shares-popup_shares-list">${itemsElements}</ul>`;
	}

	#renderShareItemImg(shareItem: SharesListItem): HTMLElement
	{
		if (Type.isStringFilled(shareItem.img))
		{
			return Tag.render`<img src="${shareItem.img}" alt="${shareItem.name}" />`;
		}

		return this.#renderShareItemInitials(shareItem.name);
	}

	#renderShareItemInitials(title: string): HTMLElement
	{
		if (!title)
		{
			return '';
		}

		const initials = title
			.split(' ')
			.slice(0, 2)
			.map((titleWord: string) => {
				return titleWord[0].toUpperCase();
			})
			.join('');

		return Tag.render`<span class="ai__role-library-grid-shares-popup_shares-list-item-initials">${initials}</span>`;
	}
}
