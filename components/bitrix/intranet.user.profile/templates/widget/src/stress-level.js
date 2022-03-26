import {Type, Tag, Loc, ajax, Text} from 'main.core';
import {EventEmitter} from 'main.core.events';
import Options from "./options";

type StressLevelResponse = {
	id: number,
	date: string,
	value: number,
	type: string,
	typeDescription: string,
	comment: string,
	hash: string,
	url: {
		check: string,
		result: string
	}
};
export class StressLevel {
	constructor() {
	}

	static getOpenSliderFunction(url)
	{
		if (Type.isStringFilled(url))
		{
			return () => {
				EventEmitter.emit(EventEmitter.GLOBAL_TARGET, Options.eventNameSpace + 'onNeedToHide');
				BX.SidePanel.Instance.open(url, {
					cacheable: false,
					data: {},
					width: 500
				});
			}
		}
		return () => {};
	}

	static showData(data: StressLevelResponse): Element
	{
		data.value = parseInt(data.value || 0);

		const result = Tag.render`
			<div class="system-auth-form__item system-auth-form__scope --vertical" id="user-indicator-pulse">
				<div class="system-auth-form__item-block --margin-bottom">
					<div class="system-auth-form__stress-widget">
						<div data-role="value-degree" class="system-auth-form__stress-widget--arrow" style="transform: rotate(90deg);"></div>
						<div class="system-auth-form__stress-widget--content">
							<div class="system-auth-form__stress-widget--content-title">${Loc.getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_NORESULT_INDICATOR_TEXT')}</div>
							<div data-role="value" class="system-auth-form__stress-widget--content-progress ${data.value > 0 ? '' : '--empty'}">0</div>
						</div>
					</div>
					<div class="system-auth-form__item-container --stress-widget-sp">
						<div class="system-auth-form__item-block --center --width-100">
							<span class="system-auth-form__stress-widget--status  --flex --${Text.encode(data.type)}">${Text.encode(data.typeDescription)}</span>
							<span class="system-auth-form__icon-help" onclick="${this.getOpenSliderFunction(data.url.result)}"></span>
						</div>
						<div class="system-auth-form__item-title --link-dotted" onclick="${this.getOpenSliderFunction(data.url.check)}">${Loc.getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_NORESULT_BUTTON')}</div>
					</div>
				</div>
				<div class="system-auth-form__item-block --flex --center">
					<div class="system-auth-form__stress-widget--message">${Text.encode(data.comment)}</div>
				</div>
			</div>
		`;
		setTimeout(() => {
			const intervalId = setInterval((value) => {
				value.current++;
				value.node.innerHTML = value.current;
				if (value.current >= value.end)
				{
					clearInterval(intervalId);
				}
			}, 600 / data.value, {
				current: 0,
				end: data.value,
				node: result.querySelector('[data-role="value"]')
			});
			result.querySelector('[data-role="value-degree"]').style.transform = 'rotate(' + (1.8 * data.value) + 'deg)'
		}, 1000);
		return result;
	}

	static showEmpty({url: {check}}): Element
	{
		return Tag.render`
			<div class="system-auth-form__item system-auth-form__scope --vertical --empty-stress --clickable" onclick="${this.getOpenSliderFunction(check)}">
				<div class="system-auth-form__item-block --margin-bottom">
					<div class="system-auth-form__stress-widget">
						<div data-role="value-degree" class="system-auth-form__stress-widget--arrow" style="transform: rotate(90deg);"></div>
						<div class="system-auth-form__stress-widget--content">
							<div class="system-auth-form__stress-widget--content-title">${Loc.getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_NORESULT_INDICATOR_TEXT')}</div>
							<div data-role="value" class="system-auth-form__stress-widget--content-progress --empty">?</div>
						</div>
					</div>
					<div class="system-auth-form__item-container --stress-widget-sp">
						<div class="system-auth-form__stress-widget--message">${Loc.getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_NORESULT_TITLE')}</div>
					</div>
				</div>
				<div class="system-auth-form__item-block --flex --center">
					<div class="system-auth-form__item-title --link-dotted">${Loc.getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_NORESULT_BUTTON')}</div>
				</div>
				<div class="system-auth-form__item-new">
					<div class="system-auth-form__item-new--title">${Loc.getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_RESULT_COME_ON')}</div>
				</div>
			</div>`;
	}

	static getPromise({signedParameters, componentName, userId, data}): Promise
	{
		return new Promise((resolve, reject) => {
			const promise = data ? Promise.resolve({data}) :
				ajax.runAction('socialnetwork.api.user.stresslevel.get', {
					signedParameters: signedParameters,
					data: {
						c: componentName,
						fields: {
							userId: userId
						}
					}
				});
				promise.then(({data}) => {
					if (data && data.id !== undefined && data.value !== undefined)
					{
						return resolve(this.showData(data));
					}

					const node = Loc.getMessage('USER_ID') === userId ?
						this.showEmpty(data) : document.createElement('DIV');

					return resolve(node);
				})
				.catch((error: Error) => {
					resolve(this.showData({
						id: undefined,
						value: undefined,
						urls: {
							check: undefined
						}
					}));
				})
			})
		;
	}
}