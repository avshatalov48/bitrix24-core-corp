import {Text, Type, Tag, Loc, ajax} from 'main.core';
import {EventEmitter} from 'main.core.events';
import Options from "./options";
type userPulseUserData = {
	FULL_NAME: string,
	AVATAR_SRC: string,
	ID: string
};
type userPulseData = {
	USERS_RATING: {
		position: number,
		range: number,
		top: [
			{
				USER_ID: string,
				ACTIVITY: number
			}
		]
	},
	USERS_INFO: userPulseUserData[]
};

export default class Ustat {
	data: userPulseData;
	constructor(data) {
		this.data = data;
		this.onclickHandle = this.onclickHandle.bind(this);
	}

	#renderUsers()
	{
		const userList = document.createDocumentFragment();
		let myPosition = parseInt(this.data['USERS_RATING']['position']);

		let myActivity = 0;
		const usersData = Type.isPlainObject(this.data['USERS_RATING']['top'])
			? Object.values(this.data['USERS_RATING']['top']) : (
				Type.isArray(this.data['USERS_RATING']['top']) && this.data['USERS_RATING']['top'].length > 0
					? [...this.data['USERS_RATING']['top']] : [{'USER_ID': Loc.getMessage('USER_ID'), ACTIVITY: 0}]);
		const dataResult = myPosition > 5 ?
			[...usersData.slice(0, 3), ...usersData.slice(-1), null]
			: usersData
		;
		dataResult
			.forEach((userRating, index) => {
				if (userRating === null)
				{
					userList.appendChild(Tag.render`<div class="system-auth-form__userlist-item --list"></div>`);
					return;
				}
				let fullName = userRating['ACTIVITY'];
				let avatarSrc = '';
				if (this.data['USERS_INFO'][userRating['USER_ID']])
				{
					fullName = [this.data['USERS_INFO'][userRating['USER_ID']]['FULL_NAME'], ': ', userRating['ACTIVITY']].join('');
					avatarSrc = String(this.data['USERS_INFO'][userRating['USER_ID']]['AVATAR_SRC']).length > 0
						? this.data['USERS_INFO'][userRating['USER_ID']]['AVATAR_SRC'] : null;
				}
				const isCurrentUser = String(userRating['USER_ID']) === String(Loc.getMessage('USER_ID'));
				if (isCurrentUser)
				{
					myActivity = userRating['ACTIVITY'];
				}
				userList.appendChild(
					Tag.render`
						<div title="${Text.encode(fullName)}" class="system-auth-form__userlist-item ui-icon ui-icon ui-icon-common-user">
							<i ${avatarSrc ? `style="background-image: url('${encodeURI(avatarSrc)}');background-size: cover;"` : ''}></i>
						</div>
					`
				);
			})
		;
		return {
			userList,
			myPosition,
			range: parseInt(this.data['USERS_RATING']['range']),
			myActivity
		};
	}

	showData(): Element
	{
		const {myPosition, userList, range} = this.#renderUsers();
		let div;
		if (range > 0 && myPosition > 0)
		{
			div = Tag.render`
			<div class="system-auth-form__item system-auth-form__scope --clickable" onclick="${this.onclickHandle}">
				<div class="system-auth-form__item-container">
					<div class="system-auth-form__item-title --without-margin">${Loc.getMessage('INTRANET_USER_PROFILE_PULSE_TITLE')}</div>
					<div class="system-auth-form__item-title --link-light --margin-s">
						<span>${Loc.getMessage('INTRANET_USER_PROFILE_PULSE_MY_RATING')}</span>
						<span class="system-auth-form__icon-help" data-hint="${Loc.getMessage('INTRANET_USTAT_COMPANY_HELP_RATING')}" data-hint-no-icon></span>
					</div>
					<div class="system-auth-form__item-title --link-light" data-role="empty-info">${Loc.getMessage('INTRANET_USER_PROFILE_PULSE_MY_IS_EMPTY')}</div>

					<div class="system-auth-form__item-title --white-space --margin-xl">
						<span>${Loc.getMessage('INTRANET_USER_PROFILE_PULSE_MY_POSITION',
				{'#POSITION#': myPosition, '#AMONG#': range})}</span>
						<span class="system-auth-form__ustat-icon --up"></span>
					</div>
					<div class="system-auth-form__userlist">
						${userList}
					</div>
				</div>
			</div>
		`;
		}
		else
		{
			const onclick = range > 0 ? this.onclickHandle : () => {};
			div = Tag.render`
			<div class="system-auth-form__item system-auth-form__scope --without-stat ${range > 0 ? '--clickable' : ''}" onclick="${onclick}">
				<div class="system-auth-form__item-container --flex --column">
					<div class="system-auth-form__item-title">${Loc.getMessage('INTRANET_USER_PROFILE_PULSE_TITLE')}</div>
					<div class="system-auth-form__item-container --center">
						<div class="system-auth-form__item-title --lighter" data-role="empty-info">${Loc.getMessage('INTRANET_USER_PROFILE_PULSE_MY_IS_EMPTY_BRIEF')}</div>
					</div>
				</div>
			</div>
		`;
		}

		BX.UI.Hint.init(div);
		return div;
	}

	onclickHandle(event)
	{
		EventEmitter.emit(Options.eventNameSpace + 'onNeedToHide');
		if (window['openIntranetUStat'])
		{
			openIntranetUStat(event);
		}
	}

	showWideData()
	{
		const {myPosition, userList, range} = this.#renderUsers();
		const div =  Tag.render`
			<div class="system-auth-form__item system-auth-form__scope --center --padding-ustat ${range > 0 ? '--clickable' : '--without-stat'}">
				<div class="system-auth-form__item-image">
					<div class="system-auth-form__item-image--src --ustat"></div>
				</div>
				<div class="system-auth-form__item-container --overflow">
					<div class="system-auth-form__item-title --xl --without-margin">${Loc.getMessage('INTRANET_USER_PROFILE_PULSE_TITLE')}</div>
				</div>
				<div class="system-auth-form__item-container --block">
					<div class="system-auth-form__item-title --link-light" data-role="empty-info">${Loc.getMessage('INTRANET_USER_PROFILE_PULSE_MY_IS_EMPTY')}</div>
					<div class="system-auth-form__item-container--inline" data-role="my-position">
						<div class="system-auth-form__item-title --link-light --without-margin --margin-right">
							${Loc.getMessage('INTRANET_USER_PROFILE_PULSE_MY_RATING')}
						</div>
						<div class="system-auth-form__item-title --white-space --margin-xl">
							<span>${Loc.getMessage('INTRANET_USER_PROFILE_PULSE_MY_POSITION',
			{'#POSITION#': myPosition, '#AMONG#': range})}</span>
							<span class="system-auth-form__ustat-icon --up"></span>
						</div>
					</div>
					<div class="system-auth-form__userlist" data-role="user-list">
						${userList}
					</div>
				</div>
				<div class="system-auth-form__icon-help --absolute-right-bottom" data-hint="${Loc.getMessage('INTRANET_USTAT_COMPANY_HELP_RATING')}" data-hint-no-icon></div>
			</div>
		`;
		if (range > 0)
		{
			div.addEventListener('click', this.onclickHandle);
		}
		return div;
	}

	static getPromise({userId, isNarrow, data}): Promise
	{
		return new Promise((resolve, reject) => {
			(data ?
				Promise.resolve({data}) :
				ajax.runComponentAction(
					'bitrix:intranet.ustat.department',
					'getJson',
					{mode: 'class', data: {}}
				))
				.then(({data}) => {
					const ustat = new this(data);
					resolve(isNarrow ? ustat.showData() : ustat.showWideData());
				})
				.catch((errors) => {
					errors = Type.isArray(errors) ? errors : [errors];
					const node = document.createElement('ul');
					errors.forEach(({message}) => {
						const errorNode = document.createElement('li');
						errorNode.innerHTML = message;
						errorNode.className = 'ui-alert-message';
						node.appendChild(errorNode);
					});
					resolve(Tag.render`
						<div class="ui-alert ui-alert-danger">
							${node}
						</div>`)
				});

		});
	}
}