// @flow
'use strict';

import 'ui.tilegrid';
import 'ui.forms';
import {Loc, Reflection, Tag, Type} from "main.core";
import {Menu} from "main.popup";

export default class GridUnit extends BX.TileGrid.Item
{
	static MENU_WIDTH = 200;
	static MENU_PADDING = 7;
	static MENU_INDEX = 3020;

	constructor(item)
	{
		super({
			id: item.type,
		});
		this.item = item;
	}

	getContent()
	{
		this.gridUnit = Tag.render`<div class="calendar-sync-item ${this.getAdditionalContentClass()}" style="${this.getContentStyles()}">
			<div class="calendar-item-content">
				${this.getImage()}
				${this.getTitle()}
				${(this.isActive() ? this.getStatus() : '')}
			</div>
		</div>`;

		this.gridUnit.addEventListener('click', this.onClick.bind(this));

		return this.gridUnit;
	}

	getTitle()
	{
		if (!this.layout.title)
		{
			this.layout.title = Tag.render `
				<div class="calendar-sync-item-title">${BX.util.htmlspecialchars(this.item.getGridTitle())}</div>`;
		}

		return this.layout.title;
	}

	getImage()
	{
		return Tag.render `
			<div class="calendar-sync-item-image">
				<div class="calendar-sync-item-image-item" style="background-image: ${'url(' + this.item.getGridIcon() + ')'}"></div>
			</div>`;
	}

	getStatus()
	{
		if (this.isActive())
		{
			return Tag.render `
				<div class="calendar-sync-item-status"></div>
			`;
		}

		return '';
	}

	isActive()
	{
		return this.item.getConnectStatus();
	}

	getAdditionalContentClass()
	{
		if (this.isActive())
		{
			if (this.item.getSyncStatus())
			{
				return 'calendar-sync-item-selected';
			}
			else
			{
				return 'calendar-sync-item-failed';
			}
		}
		else
		{
			return '';
		}
	}

	getContentStyles()
	{
		if (this.isActive())
		{
			return 'background-color:' + this.item.getGridColor() + ';';
		}
		else
		{
			return '';
		}
	}

	onClick()
	{
		if (this.item.hasMenu())
		{
			this.item.showMenu(this.gridUnit);
		}
		else if (this.item.getConnectStatus())
		{
			this.item.openActiveConnectionSlider(this.item.getConnection());
		}
		else
		{
			this.item.openInfoConnectionSlider();
		}
	}
}