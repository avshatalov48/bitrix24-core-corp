import {Text, Tag, Dom, Type, Event} from 'main.core';
import {ColorField} from "landing.ui.field.color";
import {getSvg} from "./svg";
import 'ui.switcher';
import {getHexFromOpacity, getOpacityFromHex, hexToRgba} from "../util";
import 'main.loader';

type ControlOptions = {
	callback: Function;
	node: Element;
	options: Object | DropdownOptions,
	key: String,
	keyName: String,
	value: String,
	formId: number,
};

type DropdownOptions = {
	[key: string]: string,
}

const loader = new BX.Loader({mode: 'inline'});

export class Controls
{
	static renderDropdown(options: ControlOptions)
	{
		const handler = (event) => {
			Dom.attr(event.currentTarget, 'disabled', true);
			loader.setOptions({size: 40});
			loader.show(options.node.querySelector('.ui-ctl-after.ui-ctl-icon-angle'));
			options.callback(event.currentTarget.value);
		}

		Dom.append(this.renderLabel(options.keyName), options.node);

		const select = Tag.render`<select class="ui-ctl-element embed-control-node" onchange="${Text.encode(handler)}"></select>`;

		Object.entries(options.options).forEach(([option, name]) => {
			Dom.append(
				Tag.render`
					<option
						value="${Text.encode(option)}"
						${(!Type.isUndefined(options.value) && (options.value.toString() === option.toString())) ? 'selected' : ''}
					>
						${Text.encode(name)}
					</option>
				`,
				select
			);
		});

		Dom.append(Tag.render`
			<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
				<div class="ui-ctl-after ui-ctl-icon-angle"></div>
				${select}
			</div>
		`, options.node);
	}

	static renderText(options: ControlOptions, advanced: boolean)
	{
		const handler = (event) => {
			Dom.attr(event.currentTarget, 'disabled', true);
			loader.setOptions({size: 40});
			loader.show(options.node.querySelector('.ui-ctl-after'));
			options.callback(event.currentTarget.value);
		}

		const handlerShowSaveBtn = (event) => {
			Dom.show(options.node.querySelector('.ui-ctl-after'));
		}

		const afterIcon = advanced ? '' : '<button class="ui-ctl-after" hidden><svg width="20" height="15" viewBox="0 0 20 15" xmlns="http://www.w3.org/2000/svg"><path fill="#535C69" d="M7.34223 14.351L0.865356 8.03879L3.13226 5.82951L7.34223 9.93246L16.8678 0.648987L19.1348 2.85827L7.34223 14.351Z"/></svg></button>';

		Dom.append(this.renderLabel(options.keyName), options.node);

		const input = Tag.render`
			<input
				type="text"
				class="ui-ctl-element"
				value="${!Type.isUndefined(options.value) ? Text.encode(options.value) : ''}"
				data-onfocus="${Text.encode(handlerShowSaveBtn)}"
				data-onblur="${Text.encode(handler)}"
			>
		`;

		Event.bindOnce(input, 'blur', handler);
		if (!advanced)
		{
			Event.bindOnce(input, 'focus', handlerShowSaveBtn);
		}

		Dom.append(Tag.render`
			<div class="ui-ctl ui-ctl-textbox ui-ctl-after-icon">
				${afterIcon}
				${input}
			</div>
		`, options.node);
	}

	static renderTypeface(forValue, extended: boolean, options: ControlOptions)
	{
		const handler = (event) => {
			if (event.currentTarget.getAttribute('disabled') === 'true') { return; }

			const nodeList = event.currentTarget.closest('.crm-form-embed__settings').querySelectorAll('.crm-form-embed__settings--hide-button');
			nodeList.forEach((node) => {
				// node.disabled = true;
				Dom.attr(node, 'disabled', true);
			});

			if (!extended)
			{
				animateSwitching(
					event.currentTarget.closest('.crm-form-embed__settings--option.--typeface'),
					0,
					0,
					true
				);
			}

			options.callback(forValue);
		}

		const className = extended ? 'crm-form-embed__settings-main--option --option-big' : 'crm-form-embed__settings--option --typeface';

		Dom.append(Tag.render`
			<div class="crm-form-embed__settings--hide-button ${className} ${forValue === options.value ? '--active' : ''}" onclick="${Text.encode(handler)}">
				<span class="crm-form-embed__settings--option-font --${Text.encode(forValue)}">${Text.encode(options.keyName)}</span>
			</div>
		`, options.node);
	}

	static renderSquareButton(forValue, options: ControlOptions)
	{
		const handler = (event) => {
			if (event.currentTarget.getAttribute('disabled') === 'true') { return; }

			const nodeList = event.currentTarget.closest('.crm-form-embed__settings').querySelectorAll('.crm-form-embed__settings--option-layout');
			nodeList.forEach((node) => {
				// node.disabled = true;
				Dom.attr(node, 'disabled', true);
			});

			animateSwitching(
				event.currentTarget.closest('.crm-form-embed__settings--option-layout').querySelector('.crm-form-embed__settings-main--option-svg'),
				100,
				66,
				false
			);

			options.callback(forValue);
		}

		Dom.append(Tag.render`
			<div class="crm-form-embed__settings--option-layout ${forValue === options.value ? '--active' : ''}" onclick="${Text.encode(handler)}">
				<div class="crm-form-embed__settings-main--option-svg">
					${getSvg(options.key, forValue)}
				</div>
				<span class="crm-form-embed__settings--option-layout-text">${Text.encode(options.keyName)}</span>
			</div>
		`, options.node);
	}

	static renderButton(forValue, type: string, advanced: boolean, options: ControlOptions)
	{
		const handler = (event) => {
			if (event.currentTarget.getAttribute('disabled') === 'true') { return; }

			const nodeList = event.currentTarget.closest('.crm-form-embed__settings').querySelectorAll('.crm-form-embed__settings--hide-button');
			nodeList.forEach((node) => {
				// node.disabled = true;
				Dom.attr(node, 'disabled', true);
			});

			if (!advanced)
			{
				animateSwitching(
					event.currentTarget.closest('.crm-form-embed__settings--option'),
					0,
					0,
					true
				);
			}

			options.callback(forValue);
		}

		const className = advanced ? 'crm-form-embed__settings-main--option --option-big' : 'crm-form-embed__settings--option';

		Dom.append(Tag.render`
			<div
				class="crm-form-embed__settings--hide-button ${className} ${forValue === options.value ? '--active' : ''}"
				onclick="${Text.encode(handler)}"
				data-embed-key="${Text.encode(options.key)}"
				data-embed-value="${Text.encode(forValue)}"
				title="${Text.encode(BX.Loc.getMessage('EMBED_SLIDER_OPTION_BUTTONSTYLE_LABEL'))}"
			>
				<button class="ui-btn ui-btn-md ${Text.encode(type)}">${Text.encode(BX.Loc.getMessage('EMBED_SLIDER_OPTION_BUTTONSTYLE_LABEL'))}</button>
			</div>
		`, options.node);
	}

	static renderLink(forValue, type: string, advanced: boolean, options: ControlOptions)
	{
		const handler = (event) => {
			if (event.currentTarget.getAttribute('disabled') === 'true') { return; }

			const nodeList = event.currentTarget.closest('.crm-form-embed__settings').querySelectorAll('.crm-form-embed__settings--hide-link');
			nodeList.forEach((node) => {
				// node.disabled = true;
				Dom.attr(node, 'disabled', true);
			});

			if (!advanced)
			{
				animateSwitching(
					event.currentTarget.closest('.crm-form-embed__settings--option'),
					0,
					0,
					true
				);
			}

			options.callback(forValue);
		}

		const className = advanced ? 'crm-form-embed__settings-main--option --option-big' : 'crm-form-embed__settings--option';

		Dom.append(Tag.render`
			<div
				class="crm-form-embed__settings--hide-link ${className} ${forValue === options.value ? '--active' : ''}"
				onclick="${Text.encode(handler)}"
				data-embed-key="${Text.encode(options.key)}"
				data-embed-value="${Text.encode(forValue)}"
			>
				<a href ="#" class="crm-form-embed__option-link ${Text.encode(type)}">${Text.encode(BX.Loc.getMessage('EMBED_SLIDER_OPTION_LINKSTYLE_LABEL'))}</a>
			</div>
		`, options.node);
	}

	static renderSwitcher(options: ControlOptions)
	{
		/**
		 * @this Switcher
		 */
		const handler = function(event) {
			this.setLoading(true);
			options.callback(this.isChecked() ? '1' : '0').then(() => {
				this.setLoading(false);
			});
		}

		const switcherNode = document.createElement('span');
		const switcherId = options.formId + '-' + options.key;
		switcherNode.className = 'ui-switcher';
		switcherNode.dataset.switcherId = switcherId;
		const switcher = new top.BX.UI.Switcher({
			id: switcherId,
			node: switcherNode,
			checked: options.value === '1',
			handlers: {
				toggled: handler,
			}
		});

		Dom.append(switcher.getNode(), options.node);
	}

	static renderColorPicker(subtype: string, options: ControlOptions): ColorField
	{
		/**
		 * @this ColorField
		 */
		const handler = function(event) {
			if (this instanceof ColorField) // double events with different context
			{
				/** @var IColorValue|ColorValue value */
				const value = this.getValue();
				const hexA = value.getHex() + getHexFromOpacity(value.getOpacity());
				options.callback(hexA);
			}
		}

		const colorPicker = createColorPicker(subtype, handler, handler);
		colorPicker.setValue({'--color': hexToRgba(options.value)});
		// colorPicker.processor.setValue(options.value.substring(0,6));
		// colorPicker.processor.setOpacity(getOpacityFromHex(options.value.substring(6)));
		Dom.append(colorPicker.getLayout(), options.node);
	}

	static renderOptionTitle(text: string): HTMLElement
	{
		return Tag.render`
			<div class="crm-form-embed__settings--option-title">${text ? Text.encode(text) : ''}</div>
		`;
	}

	static renderLabel(text: string, buttonSetting: boolean = false): HTMLElement
	{
		return Tag.render`
			<div class="crm-form-embed__label-text ${buttonSetting ? '--button-setting' : ''}">${text ? Text.encode(text) : ''}</div>
		`;
	}
}

function createColorPicker(subtype: string, onChange: function, onReset: function): ColorField
{
	return new ColorField({
		subtype: subtype,
		onChange: onChange,
		onReset: onReset,
	});
}

function animateSwitching(loaderTarget: HTMLElement, targetWidth: number, targetHeight: number, resetPadding: false)
{
	loaderTarget.innerHTML = '';
	if (targetWidth)
	{
		Dom.style(loaderTarget, 'width', targetWidth+'px');
	}
	if (targetHeight)
	{
		Dom.style(loaderTarget, 'height', targetHeight+'px');
	}
	if (resetPadding)
	{
		Dom.style(loaderTarget, 'padding', '0');
	}
	loader.setOptions({size: 40});
	loader.show(loaderTarget);
}