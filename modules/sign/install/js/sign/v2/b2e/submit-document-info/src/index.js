import { Dom, Loc, Tag, Text, Type } from 'main.core';
import { MemoryCache } from 'main.core.cache';
import { type BaseEvent, EventEmitter } from 'main.core.events';
import { Api } from 'sign.v2.api';
import type { FieldValue, TemplateField } from 'sign.v2.api';
import type { ProviderCodeType, MemberStatusType } from 'sign.type';
import { MemberStatus } from 'sign.type';
import './style.css';
import 'ui.forms';
import { SignLink } from 'sign.v2.b2e.sign-link';
import { BaseField, Selector, TextInput } from 'ui.form-elements.view';
import { DatePickerField } from './date-picker-field';
import readyToSendImage from './images/ready-to-send-state-image.svg';

function sleep(ms: number): Promise<void>
{
	return new Promise((resolve) => {
		setTimeout(resolve, ms);
	});
}

type Options = {
	template: {
		uid: string,
		title: string,
	},
	fields: Array<TemplateField>,
};

type MemberInvitedToSignEventData = {
	documentUid: string,
	member: { id: number, uid: string },
	signingLink: string
};

export type DocumentSendedSuccessFullyEvent = BaseEvent<{ document: { id: number, providerCode: ProviderCodeType } }>;

export class SubmitDocumentInfo extends EventEmitter
{
	events = Object.freeze({
		onProgressClosePageBtnClick: 'onProgressClosePageBtnClick',
		documentSendedSuccessFully: 'documentSendedSuccessFully',
	});

	#cache: MemoryCache<any> = new MemoryCache();
	#layoutCache: MemoryCache<HTMLElement> = new MemoryCache();
	#options: Options;
	#api: Api = new Api();
	#fieldFormId: string = 'sign-b2e-employee-fields-form';
	#uiFields: BaseField[] = [];

	constructor(options: Options)
	{
		super();
		this.setEventNamespace('BX.Sign.V2.B2e.SubmitDocumentInfo');
		this.#options = options;
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
		Dom.append(this.#getProgressLayout(), this.getLayout());
	}

	#hideProgress(): void
	{
		Dom.remove(this.#getProgressLayout());
	}

	#onProgressClosePageBtnClick(): void
	{
		this.emit(this.event.onProgressClosePageBtnClick);
		BX.SidePanel.Instance.close();
	}

	getLayout(): HTMLElement
	{
		return this.#layoutCache.remember(
			'layout',
			() => {
				if (this.#options.fields.length === 0)
				{
					return Tag.render`
						<div class="sign-submit-document-info-center-container">
							<div class="sign-submit-document-info-center-icon">
								<img src="${readyToSendImage}" alt="">
							</div>
							<p class="sign-submit-document-info-center-title">
								${Loc.getMessage('SIGN_SUBMIT_DOCUMENT_INFO_READY_TO_SEND_TITLE')}
							</p>
							<p class="sign-submit-document-info-center-description">
								${Loc.getMessage('SIGN_SUBMIT_DOCUMENT_INFO_READY_TO_SEND_DESCRIPTION', {
									'#TITLE#': Text.encode(this.#options.template.title),
								})}
							</p>
							<form id="${this.#fieldFormId}"></form>
						</div>
					`;
				}

				return Tag.render`
					<div class="sign-b2e-submit-document-info">
						<h1 class="sign-b2e-settings__header">${Loc.getMessage('SIGN_SUBMIT_DOCUMENT_INFO_HEAD')}</h1>
						<div class="sign-b2e-settings__item">
							<p class="sign-b2e-settings__item_title">
								${Loc.getMessage('SIGN_SUBMIT_DOCUMENT_INFO_DESCRIPTION')}
							</p>
							<form id="${this.#fieldFormId}">
								${this.#getFieldsLayout()}
							</form>
						</div>
					</div>
				`;
			},
		);
	}

	async sendForSign(): Promise<boolean>
	{
		const currentSidePanel = BX.SidePanel.Instance.getTopSlider();
		if (!this.#isFieldsValid())
		{
			return false;
		}

		let employeeMember = null;
		let document: null | { id: number, providerCode: ProviderCodeType } = null;
		EventEmitter.emit('BX.Sign.SignSettingsEmployee:onBeforeTemplateSend');
		try
		{
			const sendResult = await this.#api.template.send(
				this.#options.template.uid,
				this.#getFieldValues(),
			);
			employeeMember = sendResult.employeeMember;
			document = sendResult.document;
		}
		catch (e)
		{
			console.error(e);

			return false;
		}
		finally
		{
			EventEmitter.emit('BX.Sign.SignSettingsEmployee:onAfterTemplateSend');
		}
		const { uid: memberUid, id: memberId } = employeeMember;
		this.emit(this.events.documentSendedSuccessFully, { document });

		this.#showProgress();
		let pending = true;
		let openSigningSliderAfterPending = true;
		const signLink = new SignLink({ memberId });

		EventEmitter.subscribeOnce(currentSidePanel, 'SidePanel.Slider:onCloseStart', () => {
			pending = false;
			openSigningSliderAfterPending = false;
		});

		BX.PULL?.subscribe({
			moduleId: 'sign',
			command: 'memberInvitedToSign',
			callback: async (params: MemberInvitedToSignEventData): Promise<void> => {
				if (params.member.id !== memberId || !pending || !openSigningSliderAfterPending)
				{
					return;
				}

				pending = false;
				await this.#openSigningSliderAndCloseCurrent(signLink);
			},
		});

		do
		{
			await sleep(5000);
			if (!openSigningSliderAfterPending)
			{
				return true;
			}

			if (!pending)
			{
				break;
			}
			let status: MemberStatusType | null = null;
			try
			{
				status = (await this.#api.getMember(memberUid)).status;
			}
			catch (e)
			{
				console.error(e);
				this.#hideProgress();

				return false;
			}

			if (status === MemberStatus.ready || status === MemberStatus.stoppableReady)
			{
				pending = false;
			}
		}
		while (pending);

		if (openSigningSliderAfterPending)
		{
			await this.#openSigningSliderAndCloseCurrent(signLink);
		}

		return true;
	}

	async #openSigningSliderAndCloseCurrent(signLink: SignLink): Promise<void>
	{
		return this.#cache.remember('openSigningSliderAndCloseCurrent', async () => {
			const currentSidePanel = BX.SidePanel.Instance.getTopSlider();
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
		});
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
		const label = `
			<span>
				${Text.encode(field.name)} 
				${field.required ? '<span class="sign-b2e-submit-document-info__field_required">*</span>' : ''}
			</span>
		`;

		const fieldsLayoutCallbackByType = {
			date: () => {
				const datePickerField = new DatePickerField({
					label,
					inputName: field.uid,
					value: field.value,
				});
				this.#uiFields.push(datePickerField);

				return Tag.render`
					<div class="sign-b2e-submit-document-info__field">
						${datePickerField.render()}
					</div>
				`;
			},
			string: () => {
				const fieldInput = new TextInput({
					label,
					inputName: field.uid,
					value: field.value,
				});
				this.#uiFields.push(fieldInput);

				return Tag.render`
					<div class="sign-b2e-submit-document-info__field">
						${fieldInput.render()}
					</div>
				`;
			},
			list: () => {
				const selector = new Selector({
					label,
					name: field.uid,
					inputName: field.uid,
					items: this.#getSelectorItemsWithEmpty(field),
				});

				this.#uiFields.push(selector);

				return Tag.render`
					<div class="sign-b2e-submit-document-info__field">
						${selector.render()}
					</div>
				`;
			},
			// @TODO address picker
			address: () => Tag.render`
				<div class="sign-b2e-submit-document-info__field">
					<span class="sign-b2e-submit-document-info__label">
						${Text.encode(field.name)}
					</span>
					<div class="sign-b2e-submit-document-info__subfields">
						${field.subfields.map((subfield) => Tag.render`
							<div>${this.#getOrCreateFieldLayout(subfield)}</div>
						`)}
					</div>
				</div>
			`,
		};

		const defaultLayout = () => Tag.render`<div></div>`;

		return fieldsLayoutCallbackByType[field.type] ?? defaultLayout;
	}

	#getFieldValues(): FieldValue[]
	{
		const form = document.getElementById(this.#fieldFormId);
		const formData = new FormData(form);

		const fieldValues = [];

		formData.forEach((value, name) => {
			fieldValues.push({ name, value });
		});

		return fieldValues;
	}

	#isFieldsValid(): boolean
	{
		let errorCount = 0;
		this.#uiFields.forEach((domField: BaseField) => {
			domField.cleanError();
			const templateField = this.#getFieldByUid(domField.getName());
			if (!templateField)
			{
				return;
			}

			if (templateField.required && domField.getValue()?.trim() === '')
			{
				domField.setErrors([]);
				errorCount += 1;
			}
		});

		return errorCount === 0;
	}

	#getFieldByUid(uid: string): ?TemplateField
	{
		return this.#findFieldByUidRecursive(uid, this.#options.fields);
	}

	#findFieldByUidRecursive(uid: string, fields: TemplateField[]): ?TemplateField
	{
		for (const field of fields)
		{
			if (field.uid === uid)
			{
				return field;
			}

			if (field.subfields)
			{
				const subfield = this.#findFieldByUidRecursive(uid, field.subfields);
				if (subfield)
				{
					return subfield;
				}
			}
		}

		return null;
	}

	#getSelectorItemsWithEmpty(field: TemplateField): Array<{ value: string, name: string, selected: boolean }>
	{
		const items = [];

		if (!field.items.some((item) => item.code === field.value))
		{
			items.push({
				value: '',
				name: '',
				selected: true,
				hidden: true,
				disabled: true,
			});
		}

		field.items.forEach((item) => {
			items.push({
				value: Text.encode(item.code),
				name: Text.encode(item.label),
				selected: item.code === field.value,
			});
		});

		return items;
	}
}
