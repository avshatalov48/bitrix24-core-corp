import { Form } from './form';
import { Tag, Type, Loc } from 'main.core';

export class PhoneForm extends Form
{
	getTitleRender(): HTMLElement
	{
		return Tag.render`<div class="intranet-reinvite-popup-title">
			${Loc.getMessage('INTRANET_JS_PHONE_POPUP_TITLE', {'#CODE#': 'redirect=detail&code=17729332'})}
		</div>`;
	}

	getFieldRender(): HTMLElement
	{
		const form = Tag.render`
			<div class="ui-ctl ui-ctl-textbox ui-ctl-before-icon ui-ctl-after-icon intranet-reinvite-popup-field-row">
				<div class="intranet-reinvite-popup-field-label">
					<label>${Loc.getMessage('INTRANET_JS_PHONE_FIELD_LABEL')}</label>
				</div>
				<div class="ui-ctl ui-ctl-textbox">
					<div id="intranet_reinvite_phone_flag" class="ui-ctl-before --flag"></div>
					<input id="intranet_reinvite_phone_input" type="text" name="newPhone" value="${this.getValue()}" class="ui-ctl-element">
				</div>
			</div>`;

		new BX.PhoneNumber.Input({
			node: form.querySelector('#intranet_reinvite_phone_input'),
			defaultCountry: 'ru',
			flagNode: form.querySelector('#intranet_reinvite_phone_flag'),
			flagSize: 24,
			onChange: function(e) {},
		});

		return form;
	}
}
