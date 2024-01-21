import {Tag, Loc, Type, Dom, ajax} from 'main.core';
import {EventEmitter} from 'main.core.events';
import Options from './options';

type ThemePickerData = {
	id: string,
	lightning: ?string,
	title: string,
	previewImage: string,
	previewColor: string
};

export default class ThemePicker extends EventEmitter {
	#params: ThemePickerData;

	#container;

	constructor(data: ThemePickerData)
	{
		super();
		this.#params = Object.assign({}, data);
		this.#params.lightning = String(data.id).indexOf('light:') === 0 ? 'light' : (
			String(data.id).indexOf('dark:') === 0 ? 'dark' : null
		);
		this.applyTheme = this.applyTheme.bind(this);
		this.setEventNamespace(Options.eventNameSpace);
		EventEmitter.subscribe(
			'BX.Intranet.Bitrix24:ThemePicker:onThemeApply',
			({data: {id, theme}}) =>
			{
				this.#params.id =  id;
				this.#params.lightning = String(id).indexOf('light:') === 0 ? 'light' : (
					String(id).indexOf('dark:') === 0 ? 'dark' : null
				);
				this.#params.title = theme.title;
				this.#params.previewImage = theme.previewImage;
				this.#params.previewColor = theme.previewColor;
				this.applyTheme();
			}
		)
	}

	applyTheme()
	{
		const container = this.getContainer();

		if (Type.isStringFilled(this.#params.previewImage) && this.#params.lightning)
		{
			container.style.removeProperty('backgroundImage');
			container.style.removeProperty('backgroundSize');
			container.style.backgroundImage = 'url("' + this.#params.previewImage + '")';
			container.style.backgroundSize = 'cover';
		}
		else
		{
			container.style.background = 'none';
		}

		if (Type.isStringFilled(this.#params.previewColor))
		{
			this.getContainer().style.backgroundColor = this.#params.previewColor;
		}

		if (!this.#params.lightning)
		{
			container.style.backgroundColor = 'rgba(255,255,255,1)';
		}

		Dom.removeClass(container, '--light --dark');
		Dom.addClass(container, '--' + this.#params.lightning);
	}

	getContainer()
	{
		if (this.#container)
		{
			return this.#container;
		}

		const onclick = () => {
			BX.Intranet.Bitrix24.ThemePicker.Singleton.showDialog(false);
			EventEmitter.emit(EventEmitter.GLOBAL_TARGET, Options.eventNameSpace + ':onOpen');
		};
		this.#container = Tag.render`
			<div class="system-auth-form__item system-auth-form__scope --border ${this.#params.lightning ? '--' + this.#params.lightning : ''} --padding-sm">
				<div class="system-auth-form__item-logo">
					<div data-role="preview-color" class="system-auth-form__item-logo--image --theme"></div>
				</div>
				<div class="system-auth-form__item-container --flex --column">
					<div class="system-auth-form__item-title --white-space --block">
						<span data-role="title">${Loc.getMessage('AUTH_THEME_DIALOG')}</span>
					</div>
					<div class="system-auth-form__item-content --margin-top-auto --center --center-force">
						<div class="ui-qr-popupcomponentmaker__btn" onclick="${onclick}">${Loc.getMessage('INTRANET_USER_PROFILE_CHANGE')}</div>
					</div>
				</div>
			</div>`;
		setTimeout(this.applyTheme, 0);
		return this.#container;
	}

	static getPromise()
	{
		return new Promise((resolve, reject) => {
			ajax.runComponentAction('bitrix:intranet.user.profile.button', 'getThemePickerData', {
				mode: 'class'
			}).then((response) => {
				const themePicker = new this(response.data);
				resolve(themePicker.getContainer())
			}).catch((reject));
		});
	}
}