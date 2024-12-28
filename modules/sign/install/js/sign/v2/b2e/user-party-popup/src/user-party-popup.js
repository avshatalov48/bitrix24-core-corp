import { Tag, Text, Dom, Loc, Browser } from 'main.core';
import { Popup } from 'main.popup';
import { Api } from 'sign.v2.api';
import { Loader } from 'main.loader';
import 'main.polyfill.intersectionobserver';
import './user-party-popup.css';

const pageSize = 20;

type PopupOptions = {
	bindElement: HTMLElement,
};

type Department = {
	id: number,
	name: string,
};

type Member = {
	memberId: number,
	userId: number,
	name: string,
	avatar: string,
	profileUrl: string,
}

type ResponseDepartments = {
	departments: Array<Department>,
};

type ResponseMembers = {
	members: Array<Member>,
};

export class UserPartyPopup
{
	#popup: Popup = null;

	#api: Api;

	#options: PopupOptions = {};

	#ui = {
		membersContent: HTMLDivElement = null,
		departmentsContent: HTMLDivElement = null,
		tabs: HTMLElement = null,
	};

	#loader: Loader;

	#documentUid: string = null;

	#membersObserver: IntersectionObserver;
	#departmentsObserver: IntersectionObserver;

	#initialized: boolean = false;
	#membersLoaded: boolean = false;
	#departmentsLoaded: boolean = false;

	#membersLoadingLocked: boolean = false;
	#departmentsLoadingLocked: boolean = false;

	constructor(options: PopupOptions)
	{
		this.#options = options;
		this.#api = new Api();

		const observerOptions = {
			// rootMargin: (Browser.isMobile() ? '0%' : '-10% 0% -10% 0%'),
			threshold: 0.1,
		};

		this.#membersObserver = new IntersectionObserver((entries) => {
			entries.forEach((entry) => {
				if (!this.#membersLoaded && entry.isIntersecting)
				{
					this.#membersObserver.unobserve(entry.target);
					this.#loadNextMembersPage().then((members: Member[]) => {
						if (members.length > 0)
						{
							this.#appendMembersToPopup(members);
							if (members.length >= pageSize)
							{
								this.#membersObserver.observe(this.#ui.membersContent.lastChild);
							}
						}
					});
				}
			});
		}, observerOptions);

		this.#departmentsObserver = new IntersectionObserver(((entries) => {
			entries.forEach((entry) => {
				if (!this.#departmentsLoaded && entry.isIntersecting)
				{
					this.#departmentsObserver.unobserve(entry.target);
					this.#loadNextDepartmentsPage().then((departments: Department[]) => {
						if (departments.length > 0)
						{
							this.#appendDepartmentsToPopup(departments);
							if (departments.length >= pageSize)
							{
								this.#departmentsObserver.observe(this.#ui.departmentsContent.lastChild);
							}
						}
					});
				}
			});
		}), observerOptions);
	}

	show(): void
	{
		this.#getPopup().show();

		if (this.#initialized === false)
		{
			this.#loader.show();
			Dom.clean(this.#ui.membersContent);
			Dom.clean(this.#ui.departmentsContent);

			this.#init().then(async () => {
				this.#loader.hide();
				this.#initialized = true;
			});
		}
	}

	async #init(): Promise
	{
		const [members, departments] = await Promise.all([
			this.#loadMembersPage(1),
			this.#loadDepartmentsPage(1),
		]);

		if (members.length > 0)
		{
			this.#appendMembersToPopup(members);
			if (members.length >= pageSize)
			{
				this.#membersObserver.observe(this.#ui.membersContent.lastChild);
			}
		}

		if (departments.length > 0)
		{
			this.#appendDepartmentsToPopup(departments);
			if (departments.length >= pageSize)
			{
				this.#departmentsObserver.observe(this.#ui.departmentsContent.lastChild);
			}
		}
	}

	setDocumentUid(documentUid: string): UserPartyPopup
	{
		this.#documentUid = documentUid;

		return this;
	}

	resetData(): UserPartyPopup
	{
		Dom.clean(this.#ui.membersContent);
		Dom.clean(this.#ui.departmentsContent);

		this.#initialized = false;
		this.#membersLoaded = false;
		this.#departmentsLoaded = false;

		return this;
	}

	#getPopup(): Popup
	{
		if (!this.#popup)
		{
			return this.#createPopup();
		}

		return this.#popup;
	}

	#createPopup(): Popup
	{
		this.#popup = new Popup({
			content: this.#renderContent(),
			bindElement: this.#options.bindElement,
			// height: 250,
			width: 330,
			autoHide: true,
			closeByEsc: true,
		});

		return this.#popup;
	}

	#renderContent(): HTMLElement
	{
		const membersOnclick = (e) => {
			this.#switchTab('members');
			e.preventDefault();
		};

		const departmentsOnclick = (e) => {
			this.#switchTab('departments');
			e.preventDefault();
		};

		this.#ui.tabs = Tag.render`
			<span class="bx-user-party-popup-popup-head">
				<span onclick="${membersOnclick}" class="bx-user-party-popup-popup-head-item --member bx-user-party-popup-popup-head-item-current">
					<span class="bx-user-party-popup-popup-head-icon"></span>
					<span class="bx-user-party-popup-popup-head-text">${Loc.getMessage('SIGN_USER_PARTY_POPUP_TAB_MEMBERS')}</span>
				</span>
				<span onclick="${departmentsOnclick}" class="bx-user-party-popup-popup-head-item --department">
					<span class="bx-user-party-popup-popup-head-icon"></span>
					<span class="bx-user-party-popup-popup-head-text">${Loc.getMessage('SIGN_USER_PARTY_POPUP_TAB_DEPARTMENTS')}</span>
				</span>
			</span>
		`;

		this.#ui.membersContent = Tag.render`<div class="bx-user-party-popup-popup-content-container"></div>`;

		this.#ui.departmentsContent = Tag.render`
			<div class="bx-user-party-popup-popup-content-container bx-user-party-popup-popup-content-invisible"></div>
		`;

		const wrapper = Tag.render`
			<div class="bx-sign-user-party-popup">
				${this.#ui.tabs}
				${this.#ui.membersContent}
				${this.#ui.departmentsContent}
			</div>
		`;

		this.#loader = new Loader({ size: 80, target: wrapper, offset: { top: '10px' } });

		return wrapper;
	}

	#switchTab(tab: string): void
	{
		switch (tab)
		{
			case 'departments':
				Dom.removeClass(this.#ui.departmentsContent, 'bx-user-party-popup-popup-content-invisible');
				Dom.addClass(this.#ui.membersContent, 'bx-user-party-popup-popup-content-invisible');

				Dom.removeClass(
					this.#ui.tabs.querySelector('.bx-user-party-popup-popup-head-item.--member'),
					'bx-user-party-popup-popup-head-item-current',
				);
				Dom.addClass(
					this.#ui.tabs.querySelector('.bx-user-party-popup-popup-head-item.--department'),
					'bx-user-party-popup-popup-head-item-current',
				);

				break;

			case 'members':
			default:
				Dom.removeClass(this.#ui.membersContent, 'bx-user-party-popup-popup-content-invisible');
				Dom.addClass(this.#ui.departmentsContent, 'bx-user-party-popup-popup-content-invisible');

				Dom.removeClass(
					this.#ui.tabs.querySelector('.bx-user-party-popup-popup-head-item.--department'),
					'bx-user-party-popup-popup-head-item-current',
				);
				Dom.addClass(
					this.#ui.tabs.querySelector('.bx-user-party-popup-popup-head-item.--member'),
					'bx-user-party-popup-popup-head-item-current',
				);

				break;
		}
	}

	async #loadNextMembersPage(): Promise<Member[]>
	{
		if (this.#membersLoadingLocked === true)
		{
			return [];
		}

		this.#membersLoadingLocked = true;
		const page = Math.ceil(this.#ui.membersContent.children.length / pageSize) + 1;
		const newMembers = await this.#loadMembersPage(page);

		if (newMembers.length === 0)
		{
			this.#membersLoaded = true;
		}

		this.#membersLoadingLocked = false;

		return newMembers;
	}

	async #loadNextDepartmentsPage(): Promise<Department[]>
	{
		if (this.#departmentsLoadingLocked === true)
		{
			return [];
		}

		this.#departmentsLoadingLocked = true;

		const page = Math.ceil(this.#ui.departmentsContent.children.length / pageSize) + 1;
		const newDepartments = await this.#loadDepartmentsPage(page);

		if (newDepartments.length === 0)
		{
			this.#departmentsLoaded = true;
		}

		this.#departmentsLoadingLocked = false;

		return newDepartments;
	}

	async #loadMembersPage(page: number): Promise<Member[]>
	{
		const response: ResponseMembers = await this.#api.getMembersForDocument(this.#documentUid, page, pageSize);

		return response?.members || [];
	}

	async #loadDepartmentsPage(page: number): Promise<Department[]>
	{
		const response: ResponseDepartments = await this.#api.getDepartmentsForDocument(this.#documentUid, page, pageSize);

		return response?.departments || [];
	}

	#appendDepartmentsToPopup(departments: Array): void
	{
		departments.forEach((department: Department) => {
			const deptName = Text.encode(department.name);
			this.#ui.departmentsContent.append(Tag.render`
				<div data-department-id="${Text.encode(department.id)}" class="bx-user-party-popup-popup-user-item --department">
					<span class="bx-user-party-popup-popup-user-icon --default"></span>
					<span class="bx-user-party-popup-popup-user-name" title="${deptName}">${deptName}</span>
				</div>
			`);
		});
	}

	#appendMembersToPopup(members: Array): void
	{
		members.forEach((member: Member) => {
			const memberName = Text.encode(member.name);
			const avatar = Tag.render`<span class="bx-user-party-popup-popup-user-icon"></span>`;

			this.#ui.membersContent.append(Tag.render`
				<a href="${Text.encode(member.profileUrl)}" data-member-id="${Text.encode(member.memberId)}" class="bx-user-party-popup-popup-user-item --user">
					${avatar}
					<span class="bx-user-party-popup-popup-user-name" title="${memberName}">${memberName}</span>
				</a>
			`);

			if (member.avatar)
			{
				const avatarUrl = Text.encode(`data:image;base64,${member.avatar}`);
				Dom.style(avatar, 'backgroundImage', `url('${avatarUrl}')`);
			}
			else
			{
				Dom.addClass(avatar, '--default');
			}
		});
	}
}
