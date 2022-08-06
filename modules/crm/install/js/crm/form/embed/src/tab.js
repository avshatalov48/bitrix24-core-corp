import {Tag, Dom, Text, Type} from 'main.core';
import "main.qrcode";
import {StepByStep} from 'ui.stepbystep';
import 'ui.switcher';
import 'main.loader';

// fallback values
export const HELP_CENTER_ID = 13003062;
export const HELP_CENTER_URL = 'https://helpdesk.bitrix24.ru/open/' + HELP_CENTER_ID;

export class Tab {

	#loader;

	constructor()
	{
		this.#loader = new BX.Loader();
	}

	get loader(): Loader
	{
		return this.#loader;
	}

	renderBubble(text: string, withoutFrame: boolean = false): HTMLElement
	{
		return Tag.render`
			<div class="crm-form-embed__customization-info">
				<div class="crm-form-embed__customization-info--avatar">
					<img src="/bitrix/js/crm/images/crm-form-info-avatar.svg" alt="avatar">
				</div>
				<div class="crm-form-embed__customization-info--text ${withoutFrame ? '--without-frame' : ''}">${text}</div>
			</div>
		`;
	}

	renderCopySection(
		title: string = null,
		btnText: string = null,
		copyText: string = null,
		innerContent: HTMLElement = null,
		iconClass: string = null
	): HTMLElement
	{
		const section = this.renderSection();

		if (!Type.isNull(title))
		{
			Dom.append(Tag.render`
					<div class="crm-form-embed__title-block">
						<div class="ui-slider-heading-4 --no-border-bottom">${Text.encode(title)}</div>
					</div>
			`, section);
		}

		const inner = Tag.render`<div class="crm-form-embed__copy-block"></div>`;

		if (Type.isDomNode(innerContent))
		{
			Dom.append(innerContent, inner);
		}

		if (!Type.isNull(btnText) && !Type.isNull(copyText))
		{
			Dom.append(Tag.render`
				<div class="crm-form-embed__btn-box --center">
					<button
						class="crm-form-embed-btn-copy-publink ui-btn ui-btn-lg ui-btn-primary ui-btn-round ${iconClass ? iconClass : ''}">
							${Text.encode(btnText)}
					</button>
				</div>
			`, inner);

			top.BX.clipboard.bindCopyClick(
				inner.querySelector('.crm-form-embed-btn-copy-publink'),
				{
					text: copyText,
					popup: {
						offsetLeft: 60,
					},
				}
			);
		}

		Dom.append(inner, section);

		return section;
	}

	renderPreviewSection(title: string, desc: string, btn: string, url: string): HTMLElement
	{
		const qr = Tag.render`<div class="crm-form-embed__qr-block--qr"></div>`;
		new QRCode(qr, {
			text: url,
			width: 147,
			height: 147,
		});

		const section = this.renderSection();
		Dom.addClass(section, '--crm-qr-bg');
		Dom.append(
			Tag.render`
				<div class="crm-form-embed__qr-block">
					<div class="crm-form-embed__qr-block--info">
						<div class="crm-form-embed__qr-block--info-name">${Text.encode(title)}</div>
						<div class="crm-form-embed__qr-block--info-text">
							<div class="ui-icon ui-icon-service-light-messenger ui-icon-md" style="float: left; margin-right: 10px;"><i></i></div>
							${Text.encode(desc)}
						</div>
						<button
							class="ui-btn ui-btn-light-border ui-btn-round crm-form-embed__qr-block--btn"
							onclick="window.open('${Text.encode(url)}');">
								${Text.encode(btn)}
						</button>
					</div>
					<div class="crm-form-embed__qr-block">
						${qr}
					</div>
				</div>
			`,
			section
		);
		return section;
	}

	/**
	 * @protected
	 */
	renderHeaderSection(icon: string, title: string, text: string, helpCenterId: number, helpCenterUrl: string): HTMLElement
	{
		const section = this.renderSection(null, true, true);
		Dom.append(Tag.render`
			<span class="ui-icon ui-slider-icon ui-icon-service-${Text.encode(icon)}">
				<i></i>
			</span>
		`, section);
		Dom.append(Tag.render`
			<div class="ui-slider-content-box">
				<div class="ui-slider-heading-2">${Text.encode(title)}</div>
				<div class="ui-slider-inner-box">
					<p class="ui-slider-paragraph-2 crm-form-embed-header-section">${Text.encode(text)}</p>
					${this.renderHelp(helpCenterId, helpCenterUrl)}
				</div>
			</div>
		`, section);
		return section;
	}

	renderStepperSection(content: Array): HTMLElement
	{
		const section = this.renderSection();

		const step = new StepByStep({
			target: section,
			content: content,
		});
		step.init();

		return section;
	}

	/**
	 * @protected
	 * @param elem HTMLElement
	 * @param rounding boolean
	 * @param withIcon boolean
	 */
	renderSection(elem, rounding: boolean = true, withIcon: boolean = false): HTMLElement
	{
		const section = Tag.render`
			<div 
				class="
					ui-slider-section
					${rounding ? '--rounding' : ''}
					${withIcon ? 'ui-slider-section-icon --icon-sm' : ''}
				"
			></div>
		`;

		if (Type.isDomNode(elem))
		{
			Dom.append(elem, section);
		}

		return section;
	}

	/**
	 * @protected
	 * @param elem HTMLElement
	 */
	renderContainer(elem): HTMLElement
	{
		const container = Tag.render`<div class="crm-form-embed__wrapper crm-form-embed__scope"></div>`;
		if (Type.isDomNode(elem))
		{
			Dom.append(elem, container);
		}
		return container;
	}

	renderHelp(
		helpCenterId: number,
		helpCenterUrl: string,
		caption: string = BX.Loc.getMessage('EMBED_SLIDER_MORE_INFO')
	): HTMLElement
	{
		const showHelp = function (event): false
		{
			if(top.BX.Helper)
			{
				top.BX.Helper.show("redirect=detail&code=" + this.dataset.helpId);
				event.preventDefault();
			}
			return false;
		}

		return Tag.render`
			<a
				data-help-id="${Text.encode(helpCenterId)}"
				onclick="${showHelp}"
				href="${Text.encode(helpCenterUrl)}"
				class="ui-slider-link" target="_blank"
			>
				${Text.encode(caption)}
			</a>
		`;
	}
}