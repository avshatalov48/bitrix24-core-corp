import {Menu as MainMenu} from "main.popup";
import "./css/menu.css";

export default class Menu extends MainMenu
{
	choseItemIdx = -1;

	isMenuEmpty(): boolean
	{
		return this.menuItems.length <= 0;
	}

	isChoseLastItem(): boolean
	{
		return this.choseItemIdx >= this.menuItems.length - 1;
	}

	isChoseFirstItem(): boolean
	{
		return this.choseItemIdx === 0;
	}

	isItemChosen(): boolean
	{
		return this.choseItemIdx >= 0;
	}

	isDestroyed(): boolean
	{
		return this.getPopupWindow().isDestroyed();
	}

	isItemExist(index: number): boolean
	{
		return typeof this.menuItems[this.choseItemIdx] !== 'undefined';
	}

	getChosenItem()
	{
		let result = null;

		if(this.isItemChosen() && this.isItemExist(this.choseItemIdx))
		{
			result = this.menuItems[this.choseItemIdx];
		}

		return result;
	}
	chooseNextItem(): void
	{
		if(!this.isMenuEmpty() && !this.isChoseLastItem())
		{
			this.chooseItem(this.choseItemIdx + 1);
		}

		return this.getChosenItem();
	}

	choosePrevItem(): void
	{
		if(!this.isMenuEmpty() && !this.isChoseFirstItem())
		{
			this.chooseItem(this.choseItemIdx - 1);
		}

		return this.getChosenItem();
	}

	highlightItem(index: number): void
	{
		if(this.isItemExist(index))
		{
			let item = this.getChosenItem();

			if(item && item.layout.item)
			{
				item.layout.item.classList.add("highlighted");
			}
		}
	}

	unHighlightItem(index: number): void
	{
		if(this.isItemExist(index))
		{
			let item = this.getChosenItem();

			if(item && item.layout.item)
			{
				item.layout.item.classList.remove("highlighted");
			}
		}
	}

	chooseItem(index: number)
	{
		this.unHighlightItem(this.choseItemIdx);
		this.choseItemIdx = index;
		this.highlightItem(this.choseItemIdx);
	}

	clearItems()
	{
		while(this.menuItems.length > 0)
		{
			this.removeMenuItem(this.menuItems[0].id);
		}
	}

	isShown(): boolean
	{
		return this.getPopupWindow().isShown();
	}
}
