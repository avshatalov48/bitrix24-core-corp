import { Cache, Type, Event, Tag, Loc, Text, Dom, Runtime } from 'main.core';
import { EventEmitter, BaseEvent } from 'main.core.events';
import { Popup } from 'main.popup';
import { Loader } from 'main.loader';
import { Button } from 'ui.buttons';
import { ListEditor } from 'crm.field.list-editor';

import './css/style.css';

type FieldsetViewerOptions = {
	entityTypeId: number,
	entityId: number,
	documentUid: string,
	bindElement: HTMLElement,
	popupOptions?: {[key: string]: any},
	fieldListEditorOptions?: {[key: string]: any},
	fieldsPanelOptions?: {[key: string]: any},
	events: {
		onClose?: (BaseEvent) => void,
	},
};

type RequestError = {
	message: string,
	code: string | null,
	customData: any,
};

/**
 * @namespace BX.Crm.Requisite
 */
export class FieldsetViewer extends EventEmitter
{
	cache = new Cache.MemoryCache();
	endpoint = 'crm.api.fieldset.load';

	constructor(options: FieldsetViewerOptions = {})
	{
		super();
		this.setEventNamespace('BX.Crm.Requisite.FieldsetViewer');
		this.subscribeFromOptions(options?.events || {});
		this.setOptions(options);
		Event.bind(options.bindElement, 'click', this.onBindElementClick.bind(this));
	}

	setData(data: {[key: string]: any})
	{
		this.cache.set('data', data);
	}

	setEndpoint(endpoint: string): FieldsetViewer
	{
		this.endpoint = endpoint;

		return this;
	}

	getData(): {[key: string]: any}
	{
		return this.cache.get('data', {});
	}

	load(): Promise<any>
	{
		return new Promise((resolve, reject) => {
			const { entityTypeId, entityId, fieldListEditorOptions, documentUid } = this.getOptions();

			const presetId = fieldListEditorOptions?.fieldsPanelOptions?.presetId ?? null;

			BX.ajax
				.runAction(this.endpoint, { json: { entityTypeId, entityId, presetId, documentUid } })
				.then((result) => {
					resolve(result.data);
				})
				.catch((result) => {
					reject(result.errors);
				});
		});
	}

	setOptions(options: FieldsetViewerOptions)
	{
		this.cache.set('options', { ...options });
	}

	getOptions(): FieldsetViewerOptions
	{
		return this.cache.get('options');
	}

	getPopup(): Popup
	{
		return this.cache.remember('popup', () => {
			const options = this.getOptions();

			return new Popup({
				bindElement: options.bindElement,
				autoHide: false,
				width: 570,
				height: 478,
				className: 'crm-requisite-fieldset-viewer',
				noAllPaddings: true,
				...(Type.isPlainObject(options?.popupOptions) ? options?.popupOptions : {}),
				events: {
					onClose: () => {
						this.emit('onClose', { changed: this.getIsChanged() });
						this.setIsChanged(false);
					},
				},
			});
		});
	}

	setIsChanged(value: boolean)
	{
		this.cache.set('isChanged', Text.toBoolean(value));
	}

	getIsChanged(): boolean
	{
		return this.cache.get('isChanged', false);
	}

	getLoader(): Loader
	{
		return this.cache.remember('loader', () => {
			return new Loader();
		});
	}

	show()
	{
		const popup: Popup = this.getPopup();
		Dom.clean(popup.getContentContainer());
		void this.getLoader().show(popup.getContentContainer());

		this.load()
			.then((result) => {
				this.setData({ ...result });
				popup.setContent(
					this.createPopupContent(result),
				);
			})
			.catch((errors: Array<RequestError>) => {
				this.emit('onFieldSetLoadError', { requestErrors: errors });
			});

		popup.show();
	}

	hide()
	{
		this.getPopup().close();
	}

	onBindElementClick(event: MouseEvent)
	{
		event.preventDefault();
		this.show();
	}

	createPopupContent(data): HTMLDivElement
	{
		return Tag.render`
			<div class="crm-requisite-fieldset-viewer-content">
				${this.createBannerLayout(data)}
				${this.createListLayout(data)}
				${this.getFooterLayout()}
				${this.createCloseButton()}
			</div>
		`;
	}

	createBannerLayout(data): HTMLDivElement
	{
		const title = (
			Loc
				.getMessage('CRM_REQUISITE_FIELDSET_VIEWER_BANNER_TITLE')
				.replace('{{requisite}}', ` <strong>${Text.encode(data?.title)}</strong>`)
		);

		const description = (() => {
			let text = Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER_BANNER_DESCRIPTION');
			if (Type.isStringFilled(data?.more))
			{
				text += `
					<a class="ui-link" href="${Text.encode(data?.more)}">
										${Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER_BANNER_MORE_LINK_LABEL')}
									</a>
				`;
			}

			return text;
		})();

		return Tag.render`
			<div class="crm-requisite-fieldset-viewer-banner">
				<div class="crm-requisite-fieldset-viewer-banner-text">
					<div class="crm-requisite-fieldset-viewer-banner-text-title">
						${title}
					</div>
					<div class="crm-requisite-fieldset-viewer-banner-text-description">
						${description}
					</div>
				</div>
			</div>
		`;
	}

	createListLayout(data): HTMLDivElement
	{
		return Tag.render`
			<div class="crm-requisite-fieldset-viewer-list">
				${this.createListContainer(data.fields)}
			</div>
		`;
	}

	createListContainer(fields): HTMLDivElement
	{
		return Tag.render`
			<div class="crm-requisite-fieldset-viewer-list-container">
				${fields.map((options) => {
					return this.createListItem(options);
				})}
			</div>
		`;
	}

	createListItem(options: {[key: string]: any}): HTMLDivElement
	{
		const editButton = (() => {
			if (Type.isStringFilled(options?.editing?.url))
			{
				// eslint-disable-next-line init-declarations
				let onEditButtonClick;
				if (options?.editing?.entityTypeId === 8)
				{
					const postData = {
						permissionToken: options?.editing?.permissionToken,
					};
					onEditButtonClick = (): void => {
						BX.SidePanel.Instance.open(
							options?.editing?.url,
							{
								cacheable: false,
								requestMethod: 'post',
								requestParams: postData,
								events: {
									onClose: () => {
										this.show();
									},
								},
							},
						);

						this.setIsChanged(true);
					};
				}
				else if (['COMPANY_PHONE', 'COMPANY_EMAIL'].includes(options?.name))
				{
					onEditButtonClick = () => {
						// eslint-disable-next-line promise/catch-or-return
						Runtime.loadExtension('sign.v2.company-editor').then((exports) => {
							// eslint-disable-next-line no-shadow
							const { CompanyEditor, CompanyEditorMode, DocumentEntityTypeId, EditorTypeGuid } = exports;
							CompanyEditor.openSlider(
								{
									mode: CompanyEditorMode.Edit,
									documentEntityId: options?.editing?.documentEntityId,
									companyId: options?.editing?.entityId,
									layoutTitle: Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER__SET_FORM_TITLE'),
									entityTypeId: options?.editing?.documentEntityTypeId,
									showOnlyCompany: true,
									guid: (
										options?.editing?.documentEntityTypeId === DocumentEntityTypeId.B2b
											? EditorTypeGuid.B2b
											: EditorTypeGuid.B2e
									),
									params: {
										enableSingleSectionCombining: 'Y',
									}
								},
								{
									onCloseHandler: () => this.show(),
								},
							);
						})
							.catch((error) => {
								console.error(error)
								console.log('you should update sign service');

								onEditButtonClick = (): void => {
									BX.SidePanel.Instance.open(
										options?.editing?.url,
										{
											cacheable: false,
											events: {
												onClose: () => {
													this.show();
												},
											},
										},
									);

									this.setIsChanged(true);
								};
							});
					};
				}
				else
				{
					onEditButtonClick = (): void => {
						BX.SidePanel.Instance.open(
							options?.editing?.url,
							{
								cacheable: false,
								events: {
									onClose: () => {
										this.show();
									},
								},
							},
						);

						this.setIsChanged(true);
					};
				}

				return Tag.render`
					<span 
						class="ui-btn ui-btn-link" 
						onclick="${onEditButtonClick}">
							${Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER_LIST_ITEM_VALUE_LINK_LABEL')}
					</span>
				`;
			}

			return '';
		})();

		const value = Type.isObject(options?.value) ? Object.values(options?.value)?.reduce((a, b) => {
			return `${a}, ${b}`;
		}) : options?.value;

		return Tag.render`
			<div class="crm-requisite-fieldset-viewer-list-item">
				<div class="crm-requisite-fieldset-viewer-list-item-left">
					<div class="crm-requisite-fieldset-viewer-list-item-label">${Text.encode(options?.label)}</div>
					<div class="crm-requisite-fieldset-viewer-list-item-value">${Text.encode(value)}</div>
				</div>
				<div class="crm-requisite-fieldset-viewer-list-item-right">
					${editButton}
				</div>
			</div>
		`;
	}

	createCloseButton(): HTMLDivElement
	{
		return this.cache.remember('closeButton', () => {
			const onCloseClick = () => {
				this.hide();
			};

			return Tag.render`
				<div 
					class="crm-requisite-fieldset-viewer-close-button"
					onclick="${onCloseClick}"
				></div>
			`;
		});
	}

	getFieldListEditor(): ListEditor
	{
		return this.cache.remember('fieldListEditor', () => {
			const options: FieldsetViewerOptions = this.getOptions();

			return new ListEditor({
				setId: this.getData().id,
				title: Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER__SET_EDITOR_TITLE_MSGVER_1'),
				editable: {
					label: {
						label: Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER__SET_EDITOR_NAME_LABEL'),
						type: 'string',
					},
					required: {
						label: Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER__SET_EDITOR_REQUIRED_LABEL'),
						type: 'checkbox',
					},
				},
				autoSave: false,
				cacheable: false,
				events: {
					onSave: () => this.show(),
				},
				fieldsPanelOptions: {
					hideVirtual: 1,
					...(Type.isPlainObject(options.fieldsPanelOptions) ? options.fieldsPanelOptions : {}),
				},
				...(Type.isPlainObject(options.fieldListEditorOptions) ? options.fieldListEditorOptions : {}),
			});
		});
	}

	getEditButton(): Button
	{
		return this.cache.remember('editButton', () => {
			return new Button({
				text: Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER_EDIT_BUTTON_LABEL_MSGVER_1'),
				color: Button.Color.LIGHT_BORDER,
				icon: Button.Icon.EDIT,
				size: Button.Size.SMALL,
				round: true,
				events: {
					click: this.onEditButtonClick.bind(this),
				},
			});
		});
	}

	onEditButtonClick()
	{
		this.getFieldListEditor().showSlider();
		this.setIsChanged(true);
	}

	getFooterLayout(): HTMLDivElement
	{
		return this.cache.remember('footerLayout', () => {
			return Tag.render`
				<div class="crm-requisite-fieldset-viewer-footer">
					${this.getEditButton().render()}
				</div>
			`;
		});
	}
}
