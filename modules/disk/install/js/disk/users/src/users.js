import {Cache, Loc, Type, Text, Tag} from "main.core";
import {Popup} from "main.popup";
import {observeIntersection} from './utils';
import Pagination from './pagination';
import {Loader} from 'main.loader';

type UserType = {
	id: Number,
	entityId: ?Number,
	url: ?String,
	avatar: ?String,
	name: String
};

const repo = [];

export default class Users {
	/*
	 * @test
	 * @return {*}
	 */
	static get(index)
	{
		return repo[index > 0 ? index : 0];
	}

	cache = new Cache.MemoryCache();
	maxCount = 3;
	popup: ?Popup;
	pagination: Pagination;
	loader: ?Loader;
	title: ?String = null;
	items: Map = new Map();
	options = {};

	constructor(data: UserType[], paginationCallback: ?Function, options: {})
	{
		this.options = options || {};

		data.forEach(this.addItem.bind(this));
		this.renderFirst();

		this.pagination = new Pagination(paginationCallback);
		this.pagination.subscribe('onGetPage', this.onGetPage.bind(this));
		this.pagination.subscribe('onEndPage', this.onEndPage.bind(this));
		repo.push(this);
	}

	/**
	 * @private
	 */
	addItem(user: UserType): UserType
	{
		user.id = user.id || user.entityId;
		this.items.set(user.id, user);

		return user;
	}
	/**
	 * @private
	 */
	renderFirst()
	{
		let visibleCount = 0;
		const keys = this.items.keys();

		let key;
		const usersContainer = this.getUserListContainer();
		while (
			(visibleCount < this.maxCount)
			&&
			(key = keys.next().value)
		)
		{
			const userNode = this.getUserContainer(this.items.get(key));
			if (!usersContainer.contains(userNode))
			{
				usersContainer.appendChild(
					userNode
				);
			}
			visibleCount++;
		}

		if (this.items.size > this.maxCount)
		{
			this.getMoreButton().innerHTML = this.items.size - this.maxCount;
			this.getMoreButton().style.display = 'flex';
			this.getContainer().style.cursor = 'pointer';
		}
		else
		{
			this.getMoreButton().style.display = 'none';
			this.getContainer().style.cursor = '';
		}

		if (this.items.size <= 0)
		{
			this.getContainer().style.display = 'none';
		}
		else if (this.getContainer().style.display === 'none')
		{
			this.getContainer().style.display = 'flex';
		}
	}
	/**
	 * @private
	 */
	getUserContainer(user: UserType)
	{
		return this.cache.remember('userContainer' + user['id'], () => {
			return Tag.render`
				<div class="ui-icon ui-icon-common-user disk-active-user-list-item" title="${Text.encode(user['name'])}">
					<i ${user['avatar'] ? `style="background: url('${encodeURI(Text.encode(user['avatar']))}') no-repeat center; background-size: cover;" ` : ''}>
					</i>
				</div>
			`;
		});
	}
	/**
	 * @private
	 */
	renderPopupUser(user: UserType)
	{
		let wrapper;
		if (user.url)
		{
			wrapper = Tag.render`
				<a href="${user['url']}" class="disk-active-user-popup-item">
				</a>>
			`;
		}
		else
		{
			wrapper = Tag.render`
				<div class="disk-active-user-popup-item">
				</div>>
			`;
		}

		const userRow = Tag.render`
			<div class="ui-icon ui-icon-common-user disk-active-user-popup-icon">
				<i ${user['avatar'] ? `style="background: url('${encodeURI(Text.encode(user['avatar']))}') no-repeat center; background-size: cover;" `: ''}>
				</i>
			</div>
			<div class="disk-active-user-popup-name">${Text.encode(user['name'])}</div>
		`;

		wrapper.append(...userRow);

		return wrapper;
	}
	/**
	 * @public
	 */
	getContainer()
	{
		const placeInGrid = this.options.placeInGrid || false;

		return this.cache.remember('mainContainer', () => {
			const style = this.items.size <= 0 ? ' style="display: none;" ' : '';
			const gridModifier = placeInGrid? 'disk-active-user--grid' : '';

			return Tag.render`
		<div class="disk-active-user-box ${gridModifier}" ${style} onclick="${this.showPopupUsers.bind(this)}">
			<div class="disk-active-user">
				<div class="disk-active-user-inner">
					${this.getUserListContainer()}
					${this.getMoreButton()}
				</div>
			</div>
		</div>`
		});
	}
	/**
	 * @private
	 */
	getUserListContainer()
	{
		return this.cache.remember('users', () => {
			return Tag.render`
			<div class="disk-active-user-list"></div>`;
		});
	}
	/**
	 * @private
	 */
	getMoreButton()
	{
		return this.cache.remember('more', () => {
			return Tag.render`
		<div class="disk-active-user-value" style="display: none;"></div>`;
		});
	}
	/**
	 * @private
	 */
	showPopupUsers()
	{
		this.getPopup().show();
		this.items.forEach(
			(item) => {
				this.getPopupUsersContainer().appendChild(this.renderPopupUser(item));
			}
		);
	}
	/**
	 * @private
	 */
	getPopup()
	{
		if (this.popup)
		{
			return this.popup;
		}

		this.popup = new Popup({
			className: 'disk-active-user-popup',
			content: Tag.render`<div class="disk-active-user-popup-content disk-active-user-popup--grid">
				${this.title ? `<div class="disk-active-user-popup-title">${this.title}</div>` : ''}
				<div class="disk-active-user-popup-box">
					<div class="disk-active-user-popup-inner">
						${this.getPopupUsersContainer()}
						${this.getPopupUsersEndBlock()}
					</div>
				</div>
			</div>`,
			bindElement: this.getContainer(),
			closeByEsc: true,
			autoHide: true,
		});
		this.popup.subscribeOnce('onAfterClose', function() {
			delete this.popup;
			this.cache.delete('popupUsers');
			this.cache.delete('popupUsersEndBlock');
		}.bind(this));
		return this.popup
	}
	/**
	 * @private
	 */
	getPopupUsersContainer()
	{
		return this.cache.remember('popupUsers', () => {
			return document.createElement('div');
		});
	}
	/**
	 * @private
	 */
	getPopupUsersEndBlock()
	{
		return this.cache.remember('popupUsersEndBlock', () => {
			const res = document.createElement('div');

			if (this.pagination.isFinished())
			{
				return res;
			}

			const onclick = this.getNextPage.bind(this);
			res.className = 'disk-active-user-popup-box-pagination-loader';
			res.innerHTML = Loc.getMessage('JS_DISK_USERS_PAGINATION');
			res.addEventListener('click', onclick);
			observeIntersection(res, onclick)
			return res;
		});
	}
	/**
	 * @private
	 */
	getNextPage()
	{
		this.loader = (this.loader || new Loader({target: this.getPopupUsersEndBlock(), size: 20}));
		this.loader.show();
		this.pagination.getNext();
	}
	/**
	 * @private
	 */
	onGetPage({data})
	{
		if (Type.isArray(data))
		{
			data.forEach((item: UserType) => {
				const user = this.addItem(item);
				this.getPopupUsersContainer()
					.appendChild(this.renderPopupUser(user))
			});
		}
	}
	/**
	 * @private
	 */
	onEndPage()
	{
		if (this.loader)
		{
			this.loader.hide();
		}
		this.getPopupUsersEndBlock().style.display = 'none';
	}
	/**
	 * @public
	 */
	addUser(userData: UserType)
	{
		this.addItem(userData);
		this.renderFirst();
	}

	hasUser(userId: number)
	{
		return this.items.has(userId);
	}

	getUser(userId: number)
	{
		return this.items.get(userId);
	}

	forEach(fn)
	{
		this.items.forEach(fn);
	}

	/**
	 * @public
	 */
	deleteUser(userId)
	{
		if (!this.hasUser(userId))
		{
			return;
		}

		const user = this.items.get(userId);
		this.items.delete(userId);
		if (this.cache.has('userContainer' + user['id']))
		{
			const usersContainer = this.getUserListContainer();
			const userNode = this.cache.get('userContainer' + userId);
			this.cache.delete('userContainer' + userId)

			if (usersContainer.contains(userNode))
			{
				usersContainer.removeChild(userNode);
			}
		}
		this.renderFirst();
	}
}
