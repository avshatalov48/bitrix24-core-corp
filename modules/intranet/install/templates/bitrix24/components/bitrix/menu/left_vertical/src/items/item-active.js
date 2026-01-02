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
			if (
				!theMostOfTheLinks
				|| theMostOfTheLinks.uri.getPath().length < linkUri.getPath().length
			)
			{
				changeActiveItem = true;
			}
			else if (theMostOfTheLinks.uri.getPath().length === linkUri.getPath().length)
			{
				const actualParams = this.#actualLink.getQueryParams();
				const theMostOfTheLinkServiceData = {
					params: theMostOfTheLinks.uri.getQueryParams(),
					matches: 0,
				};
				const comparedLinkParams = {
					params: linkUri.getQueryParams(),
					matches: 0,
				};

				for (const key of Object.keys(actualParams))
				{
					if (
						key in actualParams
						&& key in theMostOfTheLinkServiceData.params
						&& String(actualParams[key]) === String(theMostOfTheLinkServiceData.params[key])
					)
					{
						theMostOfTheLinkServiceData.matches++;
					}

					if (
						key in actualParams
						&& key in comparedLinkParams.params
						&& String(actualParams[key]) === String(comparedLinkParams.params[key])
					)
					{
						comparedLinkParams.matches++;
					}
				}

				if (
					comparedLinkParams.matches > theMostOfTheLinkServiceData.matches
					|| (
						comparedLinkParams.matches === theMostOfTheLinkServiceData.matches
						&& Object.keys(comparedLinkParams.params).length < Object.keys(theMostOfTheLinkServiceData.params).length
					)
					|| theMostOfTheLinks.priority < link.priority
				)
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
