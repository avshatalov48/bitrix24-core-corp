import {BaseSettingsElement} from "ui.form-elements.field";
import {Checker} from "ui.form-elements.view";
import {Loc, Tag} from "main.core";

export class SiteTitle24Field extends BaseSettingsElement
{
	#checker: Checker;
	#content: HTMLElement;
	constructor(params)
	{
		super();
		this.setEventNamespace('BX.Intranet.Settings');

		this.#checker = new Checker({
			id: 'siteLogo24',
			inputName: 'logo24',
			title: params.title ?? Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_LOGO24'),
			size: 'extra-small',
			// hintOn: '',
			// hintOff: '',
			isEnable: params.isEnable,
			checked: params.checked !== '',
			value: 'Y',
			bannerCode: 'limit_admin_logo24',
			hideSeparator: true,
		});
	}

	getFieldView()
	{
		return this.#checker;
	}

	render(): HTMLElement
	{
		if (this.#content)
		{
			return this.#content;
		}

		this.#content = Tag.render`
			<div class="ui-section__field-selector --align-center">
				<div class="ui-section__hint">
					${this.#checker.render()}
				</div>
			</div>
		`;

		return this.#content;
	}
}