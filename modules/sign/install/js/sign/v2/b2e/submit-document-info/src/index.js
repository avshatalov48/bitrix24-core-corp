import { Dom, Loc, Tag, Type } from 'main.core';
import { MemoryCache } from 'main.core.cache';
import { EventEmitter } from 'main.core.events';
import { DateTimeFormat } from 'main.date';
import type { GeneralField, TemplateField } from 'sign.v2.api';
import { Api, MemberStatus } from 'sign.v2.api';
import './style.css';
import 'ui.forms';
import { SignLink } from 'sign.v2.b2e.sign-link';

function sleep(ms: number): Promise<void>
{
	return new Promise((resolve) => {
		setTimeout(resolve, ms);
	});
}

type Company = {
	name: string;
	taxId: string;
};

type Options = {
	template: {
		uid: string,
	},
	company: Company,
	fields: Array<TemplateField>,
};

type MemberInvitedToSignEventData = {
	documentUid: string,
	member: { id: number, uid: string },
	signingLink: string
};

export class SubmitDocumentInfo
{
	#layoutCache: MemoryCache<HTMLElement> = new MemoryCache();
	#options: Options;
	#api: Api = new Api();

	constructor(options: Options)
	{
		this.#options = options;
	}

	#getCompanyLayout(): HTMLElement
	{
		const { name, taxId } = this.#options.company;

		return Tag.render`
			<div class="sign-b2e-submit-document-info__company">
				<div class="sign-b2e-submit-document-info__company_summary">
					<p class="sign-b2e-submit-document-info__company_name">
						${name}
					</p>
					<p class="sign-b2e-submit-document-info__company_tax">
						${Loc.getMessage('SIGN_SUBMIT_DOCUMENT_COMPANY_TAX', {
			'#TAX_ID#': taxId,
		})}
					</p>
				</div>
			</div>
		`;
	}

	#getProgressLayout(): HTMLElement
	{
		return this.#layoutCache.remember(
			'progressLayout',
			() => Tag.render`
				<div class="sign-b2e-submit-document-info__progress">
					<div class="sign-b2e-submit-document-info__progress_icon"></div>
					<h2 class="sign-b2e-submit-document-info__progress_head">
						${Loc.getMessage('SIGN_SUBMIT_DOCUMENT_INFO_PROGRESS_HEAD')}
					</h2>
					<p class="sign-b2e-submit-document-info__progress_description">
						${Loc.getMessage('SIGN_SUBMIT_DOCUMENT_INFO_PROGRESS_DESCRIPTION')}
					</p>
					<button
						class="ui-btn ui-btn-round ui-btn-light-border"
						onclick="${() => this.#onProgressClosePageBtnClick()}"
					>
						${Loc.getMessage('SIGN_SUBMIT_DOCUMENT_INFO_PROGRESS_CLOSE')}
					</button>
				</div>
			`,
		);
	}

	#showProgress(): void
	{
		Dom.removeClass(this.#getProgressLayout(), '--hidden');
	}

	#hideProgress(): void
	{
		Dom.addClass(this.#getProgressLayout(), '--hidden');
	}

	#onProgressClosePageBtnClick(): void
	{
		BX.SidePanel.Instance.close();
	}

	getLayout(): HTMLElement
	{
		return this.#layoutCache.remember(
			'layout',
			() => {
				this.#hideProgress();

				return Tag.render`
					<div class="sign-b2e-submit-document-info">
						<h1 class="sign-b2e-settings__header">${Loc.getMessage('SIGN_START_PROCESS_HEAD')}</h1>
						<div class="sign-b2e-settings__item">
							<p class="sign-b2e-settings__item_title">
								${Loc.getMessage('SIGN_SUBMIT_DOCUMENT_COMPANY')}
							</p>
							${this.#getCompanyLayout()}
						</div>
						<div class="sign-b2e-settings__item">
							<p class="sign-b2e-settings__item_title">
								${Loc.getMessage('SIGN_SUBMIT_DOCUMENT_INFO_DESCRIPTION')}
							</p>
							${this.#getFieldsLayout()}
						</div>
						${this.#getProgressLayout()}
					</div>
				`;
			},
		);
	}

	async sendForSign(): Promise<boolean>
	{
		const { employeeMember } = await this.#api.template.send(this.#options.template.uid);
		const { uid: memberUid, id: memberId } = employeeMember;
		const currentSidePanel: BX.SidePanel.Slider | null = BX.SidePanel.Instance.getTopSlider();

		this.#showProgress();
		let pending = true;
		let openSigningSliderAfterPending = true;
		const signLink = new SignLink({ memberId });

		EventEmitter.subscribe('SidePanel.Slider:onCloseStart', () => {
			pending = false;
			openSigningSliderAfterPending = false;
		});

		BX.PULL?.subscribe({
			moduleId: 'sign',
			command: 'memberInvitedToSign',
			callback: async (params: MemberInvitedToSignEventData): Promise<void> => {
				if (params.member.id !== memberId && pending && openSigningSliderAfterPending)
				{
					return;
				}

				pending = false;
				openSigningSliderAfterPending = false;
				await this.#openSigningSliderAndCloseCurrent(signLink, currentSidePanel);
			},
		});

		do
		{
			await sleep(5000);
			if (!openSigningSliderAfterPending)
			{
				return true;
			}
			const { status } = await this.#api.getMember(memberUid);

			if (status === MemberStatus.ready || status === MemberStatus.stoppableReady)
			{
				pending = false;
			}
		}
		while (pending);

		if (openSigningSliderAfterPending)
		{
			await this.#openSigningSliderAndCloseCurrent(signLink, currentSidePanel);
		}

		return true;
	}

	async #openSigningSliderAndCloseCurrent(
		signLink: SignLink,
		currentSidePanel: BX.SidePanel.Slider | null,
	): Promise<void>
	{
		// load signing data before close current slider
		await signLink.preloadData();
		if (Type.isNull(currentSidePanel))
		{
			signLink.openSlider({ events: {} });
		}
		else
		{
			currentSidePanel.close(false, () => signLink.openSlider({ events: {} }));
		}
	}

	#getFieldsLayout(): HTMLElement[]
	{
		return this.#options.fields
			.map((field) => this.#getOrCreateFieldLayout(field))
		;
	}

	#getOrCreateFieldLayout(field: TemplateField): HTMLElement
	{
		return this.#layoutCache.remember(`fieldLayout.${field.uid}`, () => {
			const fieldsLayoutCallbackByType = this.#getFieldLayoutCallback(field);

			if (Type.isNull(fieldsLayoutCallbackByType))
			{
				throw new TypeError(`Unknown field type: ${field.type}`);
			}

			return fieldsLayoutCallbackByType(field);
		});
	}

	#getFieldLayoutCallback(field: TemplateField): () => HTMLElement
	{
		const now = new Date();
		const fieldName = Tag.render`
			<span class="sign-b2e-submit-document-info__label">
				${field.name}
			</span>
		`;

		const fieldsLayoutCallbackByType = {
			date: () => Tag.render`
				<div class="sign-b2e-submit-document-info__field">
					${fieldName}
					<div class="ui-ctl ui-ctl-after-icon ui-ctl-date" onclick="${() => this.#onDateFieldClick(field)}">
						<div class="ui-ctl-after ui-ctl-icon-calendar"></div>
						<div class="ui-ctl-element sign-b2e-submit-document-info__field-date__value">${this.#formatDateToUserFormat(now)}</div>
					</div>
				</div>
			`,
			string: () => Tag.render`
				<div class="sign-b2e-submit-document-info__field">
					${fieldName}
					<div class="ui-ctl ui-ctl-textbox">
						<input type="text" class="ui-ctl-element">
					</div>
				</div>
			`,
			list: () => Tag.render`
				<div class="sign-b2e-submit-document-info__field">
					${fieldName}
					<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						<select class="ui-ctl-element">
							${field.items.map((item) => Tag.render`
								<option value="${item.code}">${item.label}</option>
							`)}
						</select>
					</div>
				</div>
			`,
			number: () => Tag.render`
				<div class="sign-b2e-submit-document-info__field">
					${fieldName}
					<div class="ui-ctl ui-ctl-textbox">
						<input type="number" class="ui-ctl-element">
					</div>
				</div>
			`,
		};

		const defaultLayout = () => Tag.render`<div></div>`;

		return fieldsLayoutCallbackByType[field.type] ?? defaultLayout;
	}

	#onDateFieldClick(field: GeneralField): void
	{
		BX.calendar({
			node: this.#getOrCreateFieldLayout(field),
			field: this.#getOrCreateFieldLayout(field),
			bTime: false,
			callback_after: (date: Date) => {
				const dateFieldValue = this.#getOrCreateFieldLayout(field)
					.querySelector('.sign-b2e-submit-document-info__field-date__value')
				;
				if (dateFieldValue)
				{
					dateFieldValue.textContent = this.#formatDateToUserFormat(date);
				}
			},
		});
	}

	#formatDateToUserFormat(date: Date): string
	{
		return DateTimeFormat.format(DateTimeFormat.getFormat('FORMAT_DATE'), date);
	}
}
