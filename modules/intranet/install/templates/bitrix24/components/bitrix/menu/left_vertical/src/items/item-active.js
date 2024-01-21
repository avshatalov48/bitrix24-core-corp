import {Runtime, Uri} from 'main.core';
import Item from './item'
import ItemSystem from './item-system'
type ActiveLink = {
	priority: number,
	url: string,
	uri: Uri
};
export default class ItemActive
{
	item: ?Item;
	#link: ?ActiveLink;
	#actualLink: Uri;

	constructor()
	{
		this.#actualLink = new Uri(window.location.href);
	}

	checkAndSet(item: Item, links: Array): boolean
	{
		/*
		Custom items have more priority than standard items.
		Example:
			Calendar (standard item)
				data-link="/company/personal/user/1/calendar/"
				data-all-links="/company/personal/user/1/calendar/,/calendar/

			Company Calendar (custom item)
				 data-link="/calendar/"

		We've got two items with the identical link /calendar/'.
		*/
		if (item === this.item)
		{
			return false;
		}

		let theMostOfTheLinks = this.#link;
		links.forEach((link) => {
			const linkUri = new Uri(link.url);
			let changeActiveItem = false;
			if (!theMostOfTheLinks
				||
				theMostOfTheLinks.uri.getPath().length < linkUri.getPath().length)
			{
				changeActiveItem = true;
			}
			else if (theMostOfTheLinks.uri.getPath().length === linkUri.getPath().length)
			{
				const actualParams = this.#actualLink.getQueryParams();
				const maxCount = Object.keys(actualParams).length;
				const theMostOfTheLinkServiceData = {
					params: theMostOfTheLinks.uri.getQueryParams(),
					mismatches: maxCount
				};
				const comparedLinkParams = {
					params: linkUri.getQueryParams(),
					mismatches: maxCount
				};
				Array.from(
					Object.keys(actualParams)
				).forEach((key) => {
					if (String(actualParams[key]) === String(theMostOfTheLinkServiceData.params[key]))
					{
						theMostOfTheLinkServiceData.mismatches--;
					}
					if (String(actualParams[key]) === String(comparedLinkParams.params[key]))
					{
						comparedLinkParams.mismatches--;
					}
				});

				if (link.priority > 0 && item instanceof ItemSystem)
				{
					link.priority += 1;
				}

				if (theMostOfTheLinkServiceData.mismatches > comparedLinkParams.mismatches
					|| theMostOfTheLinks.priority < link.priority)
				{
					changeActiveItem = true;
				}
			}
			if (changeActiveItem)
			{
				theMostOfTheLinks = {
					priority: link.priority,
					url: link.url,
					uri: linkUri,
				};
			}
		});

		if (theMostOfTheLinks !== this.#link)
		{
			if (this.item)
			{
				this.unhighlight(this.item);
			}
			this.#link = theMostOfTheLinks;
			this.item = item;

			this.highlight();
			return true;
		}
		return false;
	}

	checkAndUnset(item: Item)
	{
		if (item instanceof Item && item === this.item)
		{
			this.unhighlight(this.item);
			this.item = null;
			this.#link = null;
		}
	}

	highlight()
	{
		if (this.item)
		{
			this.item.container.classList.add('menu-item-active');

			let parent = this.item.container.closest('[data-role="group-content"]');
			let parentContainer;
			while (parent)
			{
				parentContainer = parent.parentNode.querySelector(`[data-id="${parent.getAttribute('data-group-id')}"]`);
				if (parentContainer)
				{
					parentContainer.setAttribute('data-contains-active-item', 'Y');
					if (parentContainer.getAttribute('data-collapse-mode') === 'collapsed')
					{
						parentContainer.classList.add('menu-item-active');
					}
				}
				parent = parent.closest('[data-relo="group-content"]');
			}
		}
	}

	unhighlight(item: ?Item): ?Item
	{
		if (!(item instanceof Item))
		{
			item = this.item;
		}
		if (item instanceof Item)
		{
			item.container.classList.remove('menu-item-active');
			let parent = item.container.closest('[data-role="group-content"]');
			let parentContainer;
			while (parent)
			{
				parentContainer = parent.parentNode.querySelector(`[data-id="${parent.getAttribute('data-group-id')}"]`);
				if (parentContainer)
				{
					parentContainer.removeAttribute('data-contains-active-item');
					parentContainer.classList.remove('menu-item-active');
				}
				parent = parent.closest('[data-relo="group-content"]');
			}
			return item;
		}
		return null;
	}
}
