import { type CategoryModel } from 'crm.category-model';
import { Builder, Dictionary, type EntityConvertEvent, type EventStatus } from 'crm.integration.analytics';
import { ajax as Ajax, Dom, Loc, Tag, Text, Type, Uri } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Popup } from 'main.popup';
import { sendData as sendAnalyticsData } from 'ui.analytics';
import { Button, ButtonColor } from 'ui.buttons';
import { MessageBox } from 'ui.dialogs.messagebox';
import 'ui.forms';
import { CategoryRepository } from './category-repository';
import { Config } from './config';
import type { ConfigItemData } from './config-item';
import { ConfigItem } from './config-item';
import { SchemeItem } from './scheme-item';

type ResolveResult = {
	isCanceled: boolean, // default false
	isFinished: boolean, // default true
};

type CategorySelectResult = ResolveResult & {
	category?: CategoryModel | null,
};

export type ConverterParams = {
	id?: string,
	serviceUrl: string,
	originUrl: string,
	isRedirectToDetailPageEnabled?: boolean,
	messages: Object<string, string>,
	analytics?: {
		c_section?: EntityConvertEvent['c_section'],
		c_sub_section?: EntityConvertEvent['c_sub_section'],
		c_element?: EntityConvertEvent['c_element'],
	},
};

// eslint-disable-next-line unicorn/numeric-separators-style
const REQUIRED_FIELDS_INFOHELPER_CODE = 8233923;

/**
 * @memberOf BX.Crm.Conversion
 */
export class Converter
{
	#entityTypeId: number;
	#entityId: number;
	#config: Config;
	#params: ConverterParams;
	#isProgress: boolean;
	#isSynchronisationAllowed: boolean;
	#fieldsSynchronizer: BX.CrmEntityFieldSynchronizationEditor | null;
	#data: Object;

	constructor(
		entityTypeId: number,
		config: Config,
		params?: ConverterParams,
	)
	{
		this.#entityTypeId = Number(entityTypeId);
		if (config instanceof Config)
		{
			this.#config = config;
		}
		else
		{
			console.error('Config is invalid in Converter constructor. Expected instance of Config', config, this);
		}
		this.#params = params ?? {};

		this.#params.id = Type.isStringFilled(this.#params.id) ? this.#params.id : Text.getRandom();
		this.#params.analytics = this.#filterExternalAnalytics(this.#params.analytics);

		this.#isProgress = false;
		this.#isSynchronisationAllowed = false;
		this.#entityId = 0;
	}

	getEntityTypeId(): number
	{
		return this.#entityTypeId;
	}

	getConfig(): Config
	{
		return this.#config;
	}

	getId(): string
	{
		return this.#params.id;
	}

	getServiceUrl(): ?string
	{
		const serviceUrl = this.#params.serviceUrl;
		if (!serviceUrl)
		{
			return null;
		}

		const additionalParams = {
			action: 'convert',
		};

		this.getConfig().getItems().forEach((item: ConfigItem) => {
			additionalParams[BX.CrmEntityType.resolveName(item.getEntityTypeId()).toLowerCase()] = item.isActive() ? 'Y' : 'N';
		});

		return BX.util.add_url_param(serviceUrl, additionalParams);
	}

	getOriginUrl(): ?string
	{
		if (this.#params && 'originUrl' in this.#params)
		{
			return String(this.#params.originUrl);
		}

		return null;
	}

	isRedirectToDetailPageEnabled(): boolean
	{
		if (this.#params && 'isRedirectToDetailPageEnabled' in this.#params)
		{
			return this.#params.isRedirectToDetailPageEnabled;
		}

		return true;
	}

	/**
	 * Overwrite current analytics[c_element] param.
	 * Note that you are not allowed to change analytics[c_sub_section] - its by design.
	 *
	 * @param c_element
	 * @returns {BX.Crm.Conversion.Converter}
	 */
	// eslint-disable-next-line camelcase
	setAnalyticsElement(c_element: EntityConvertEvent['c_element']): Converter
	{
		// eslint-disable-next-line camelcase
		const filtered = this.#filterExternalAnalytics({ c_element });
		if ('c_element' in filtered)
		{
			this.#params.analytics.c_element = filtered.c_element;
		}

		return this;
	}

	convertBySchemeItemId(schemeItemId: string, entityId: number, data?: Object): void
	{
		const targetSchemeItem = this.#config.getScheme().getItemById(schemeItemId);
		if (!targetSchemeItem)
		{
			console.error('Scheme is not found', schemeItemId, this);

			return;
		}

		this.#config.updateFromSchemeItem(targetSchemeItem);

		this.convert(entityId, data);
	}

	convert(entityId: number, data?: Object): void
	{
		this.#entityId = entityId;
		this.#data = data;
		const schemeItem = this.#config.getScheme().getCurrentItem();
		if (!schemeItem)
		{
			console.error('Scheme is not found', this);

			return;
		}

		if (Type.isStringFilled(schemeItem.getAvailabilityLock()))
		{
			// eslint-disable-next-line no-eval
			eval(schemeItem.getAvailabilityLock());

			return;
		}

		this.#config.getActiveItems().forEach((item) => {
			this.#sendAnalyticsData(item.getEntityTypeId(), Dictionary.STATUS_ATTEMPT);
		});

		this.#collectAdditionalData(schemeItem).then((result: ResolveResult) => {
			if (result.isCanceled)
			{
				// pass it to next 'then' handler
				return result;
			}

			return this.#request();
		}).then((result: ResolveResult) => {
			if (!result.isFinished)
			{
				// dont need to register anything in statistics

				return;
			}

			const status = result.isCanceled ? Dictionary.STATUS_CANCEL : Dictionary.STATUS_SUCCESS;

			this.#config.getActiveItems().forEach((item) => {
				this.#sendAnalyticsData(item.getEntityTypeId(), status);
			});
		}).catch((error) => {
			if (error)
			{
				// eslint-disable-next-line no-console
				console.log('Convert error', error, this);
			}

			this.#config.getActiveItems().forEach((item) => {
				this.#sendAnalyticsData(item.getEntityTypeId(), Dictionary.STATUS_ERROR);
			});
		});
	}

	#request(): Promise<ResolveResult>
	{
		const promise = new Promise((resolve, reject) => {
			const serviceUrl = this.getServiceUrl();
			if (!serviceUrl)
			{
				console.error('Convert endpoint is not specifier');

				reject();

				return;
			}

			if (this.#isProgress)
			{
				console.error('Another request is in progress');

				reject();

				return;
			}

			this.#isProgress = true;
			Ajax({
				url: serviceUrl,
				method: 'POST',
				dataType: 'json',
				data:
					{
						MODE: 'CONVERT',
						ENTITY_ID: this.#entityId,
						ENABLE_SYNCHRONIZATION: this.#isSynchronisationAllowed ? 'Y' : 'N',
						ENABLE_REDIRECT_TO_SHOW: this.isRedirectToDetailPageEnabled() ? 'Y' : 'N',
						CONFIG: this.getConfig().externalize(),
						CONTEXT: this.#data,
						ORIGIN_URL: this.getOriginUrl(),
					},
				onsuccess: resolve,
				onfailure: reject,
			});
		});

		return promise
			.then((response) => {
				this.#isProgress = false;

				return this.#onRequestSuccess(response);
			})
			.catch((error) => {
				this.#isProgress = false;

				if (Type.isStringFilled(error))
				{
					// response may contain info about action required from user
					MessageBox.alert(Text.encode(error));
				}

				// pass error to next 'catch'
				throw error;
			})
		;
	}

	#sendAnalyticsData(dstEntityTypeId: number, status: EventStatus): void
	{
		const builder = Builder.Entity.ConvertEvent.createDefault(this.#entityTypeId, dstEntityTypeId)
			.setSection(this.#params.analytics.c_section)
			.setSubSection(this.#params.analytics.c_sub_section)
			.setElement(this.#params.analytics.c_element)
			.setStatus(status)
		;

		sendAnalyticsData(builder.buildData());
	}

	#filterExternalAnalytics(analytics: any): ConverterParams['analytics']
	{
		if (!Type.isPlainObject(analytics))
		{
			return {};
		}

		const allowedKeys = new Set([
			'c_section',
			'c_sub_section',
			'c_element',
		]);

		const result = {};
		for (const [key, value] of Object.entries(analytics))
		{
			if (allowedKeys.has(key) && Type.isStringFilled(value))
			{
				result[key] = value;
			}
		}

		return result;
	}

	#onRequestSuccess(response): Promise<ResolveResult, string>
	{
		return new Promise((resolve, reject) => {
			if (response.ERROR)
			{
				reject(response.ERROR?.MESSAGE || response.ERROR || 'Error during conversion');

				return;
			}

			if (Type.isPlainObject(response.REQUIRED_ACTION))
			{
				resolve(this.#processRequiredAction(response.REQUIRED_ACTION));

				return;
			}

			const data = Type.isPlainObject(response.DATA) ? response.DATA : {};
			if (!data)
			{
				reject();

				return;
			}

			const resolveResult: ResolveResult = { isCanceled: false, isFinished: true };

			const redirectUrl = Type.isString(data.URL) ? data.URL : '';
			if (data.IS_FINISHED === 'Y')
			{
				// result entity was created on backend, conversion is finished
				this.#data = {};

				const wasRedirectedInExternalEventHandler = this.#emitConvertedEvent(redirectUrl);
				if (wasRedirectedInExternalEventHandler)
				{
					resolve(resolveResult);

					return;
				}
			}
			else
			{
				// backend could not create result entity automatically, user interaction is required
				resolveResult.isFinished = false;
			}

			if (redirectUrl)
			{
				const redirectUrlObject = new Uri(redirectUrl);

				const currentRedirectUrlAnalytics = redirectUrlObject.getQueryParam('st') || {};

				redirectUrlObject.setQueryParam(
					'st',
					{
						...this.#params.analytics,
						...currentRedirectUrlAnalytics,
					},
				);

				BX.Crm.Page.open(redirectUrlObject.toString());
			}
			else if (window.top !== window)
			{
				// window.location.reload();
			}

			resolve(resolveResult);
		});
	}

	#collectAdditionalData(schemeItem: SchemeItem): Promise<ResolveResult, string>
	{
		const config = this.getConfig();

		const promises = [];

		schemeItem.getEntityTypeIds().forEach((entityTypeId) => {
			promises.push(() => {
				return this.#getCategoryForEntityTypeId(entityTypeId);
			});
		});

		const result: ResolveResult = {
			isCanceled: false,
			isFinished: true,
		};
		const promiseIterator = ((receivedPromises: Array, index: number = 0) => {
			return new Promise((resolve, reject) => {
				if (result.isCanceled || !receivedPromises[index])
				{
					resolve(result);

					return;
				}
				receivedPromises[index]().then((categoryResult: CategorySelectResult) => {
					if (categoryResult.isCanceled)
					{
						result.isCanceled = true;
					}
					else if (categoryResult.category)
					{
						const entityTypeId = categoryResult.category.getEntityTypeId();
						const configItem = config.getItemByEntityTypeId(entityTypeId);
						if (!configItem)
						{
							console.error(`Scheme is not correct: configItem is not found for ${entityTypeId}`, this);
							reject();

							return;
						}
						const initData = configItem.getInitData();
						initData.categoryId = categoryResult.category.getId();
						configItem.setInitData(initData);
					}

					resolve(promiseIterator(receivedPromises, index + 1));
				}).catch(reject);
			});
		});

		return promiseIterator(promises);
	}

	#getCategoryForEntityTypeId(entityTypeId): Promise<CategorySelectResult>
	{
		return new Promise((resolve, reject) => {
			const configItem = this.getConfig().getItemByEntityTypeId(entityTypeId);
			if (!configItem)
			{
				console.error(`Scheme is not correct: configItem is not found for ${entityTypeId}`, this);
				reject();

				return;
			}

			if (this.#isNeedToLoadCategories(entityTypeId))
			{
				CategoryRepository.Instance.getCategories(entityTypeId).then((categories: CategoryModel[]) => {
					if (categories.length > 1)
					{
						resolve(this.#showCategorySelector(categories, configItem.getTitle()));
					}
					else
					{
						resolve({
							isCanceled: false,
							category: categories[0],
						});
					}
				}).catch(reject);
			}
			else
			{
				resolve({ isCanceled: false, category: null });
			}
		});
	}

	#isNeedToLoadCategories(entityTypeId: number): boolean
	{
		return CategoryRepository.Instance.isCategoriesEnabled(entityTypeId);
	}

	#showCategorySelector(categories: CategoryModel[], title: string): Promise<CategorySelectResult>
	{
		return new Promise((resolve) => {
			const categorySelectorContent: HTMLElement = Tag.render`
				<div class="crm-converter-category-selector ui-form ui-form-line">
					<div class="ui-form-row">
						<div class="crm-converter-category-selector-label ui-form-label">
							<div class="ui-ctl-label-text">${Loc.getMessage('CRM_COMMON_CATEGORY')}</div>
						</div>
						<div class="ui-form-content">
							<div class="crm-converter-category-selector-select ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
								<div class="ui-ctl-after ui-ctl-icon-angle"></div>
								<select class="ui-ctl-element"></select>
							</div>
						</div>
					</div>
				</div>
			`;

			const select = categorySelectorContent.querySelector('select');
			categories.forEach((category) => {
				Dom.append(
					Tag.render`<option value="${category.getId()}">${Text.encode(category.getName())}</option>`,
					select,
				);
			});

			const popup = new Popup({
				titleBar: Loc.getMessage('CRM_CONVERSION_CATEGORY_SELECTOR_TITLE', {
					'#ENTITY#': Text.encode(title),
				}),
				content: categorySelectorContent,
				closeByEsc: true,
				closeIcon: true,
				buttons: [
					new Button({
						text: Loc.getMessage('CRM_COMMON_ACTION_SAVE'),
						color: ButtonColor.SUCCESS,
						onclick: () => {
							const value = [...select.selectedOptions][0].value;

							popup.destroy();

							for (const category of categories)
							{
								if (category.getId() === Number(value))
								{
									resolve({ category });

									return true;
								}
							}
							console.error('Selected category not found', value, categories);
							resolve({ isCanceled: true });

							return true;
						},
					}),
					new Button({
						text: Loc.getMessage('CRM_COMMON_ACTION_CANCEL'),
						color: ButtonColor.LIGHT,
						onclick: () => {
							popup.destroy();

							resolve({ isCanceled: true });

							return true;
						},
					}),
				],
				events: {
					onClose: () => {
						resolve({ isCanceled: true });
					},
				},
			});

			popup.show();
		});
	}

	#processRequiredAction(action: Object): Promise<ResolveResult>
	{
		const name = String(action.NAME);
		const data = Type.isPlainObject(action.DATA) ? action.DATA : {};

		if (name === 'SYNCHRONIZE')
		{
			let newConfig: ConfigItemData[] | null = null;
			if (Type.isArray(data.CONFIG))
			{
				newConfig = data.CONFIG;
			}
			else if (Type.isPlainObject(data.CONFIG))
			{
				newConfig = Object.values(data.CONFIG);
			}

			if (newConfig)
			{
				this.#config.updateItems(newConfig);
			}

			return this.#synchronizeFields(Type.isArray(data.FIELD_NAMES) ? data.FIELD_NAMES : []);
		}

		if (name === 'CORRECT' && Type.isPlainObject(data.CHECK_ERRORS))
		{
			return this.#askToFillRequiredFields(data);
		}

		return Promise.resolve({ isCanceled: false, isFinished: true });
	}

	#synchronizeFields(fieldNames: string[]): Promise<ResolveResult>
	{
		const synchronizer = this.#getFieldsSynchronizer(fieldNames);

		return new Promise((resolve) => {
			const listener = (sender, args) => {
				const isConversionCancelled = Type.isBoolean(args.isCanceled) && args.isCanceled === true;
				if (isConversionCancelled)
				{
					synchronizer.removeClosingListener(listener);

					resolve({ isCanceled: true, isFinished: true });

					return;
				}

				this.#isSynchronisationAllowed = true;
				this.#config.updateItems(Object.values(this.#fieldsSynchronizer.getConfig()));

				synchronizer.removeClosingListener(listener);
				resolve(this.#request());
			};

			synchronizer.addClosingListener(listener);
			synchronizer.show();
		});
	}

	#getFieldsSynchronizer(fieldNames: string[]): BX.CrmEntityFieldSynchronizationEditor
	{
		if (!this.#fieldsSynchronizer)
		{
			this.#fieldsSynchronizer = BX.CrmEntityFieldSynchronizationEditor.create(
				`crm_converter_fields_synchronizer_${this.getEntityTypeId()}`,
				{
					config: this.#config.externalize(),
					title: this.#getMessage('dialogTitle'),
					fieldNames,
					legend: this.#getMessage('syncEditorLegend'),
					fieldListTitle: this.#getMessage('syncEditorFieldListTitle'),
					entityListTitle: this.#getMessage('syncEditorEntityListTitle'),
					continueButton: this.#getMessage('continueButton'),
					cancelButton: this.#getMessage('cancelButton'),
				},
			);
		}

		this.#fieldsSynchronizer.setConfig(this.#config.externalize());
		this.#fieldsSynchronizer.setFieldNames(fieldNames);

		return this.#fieldsSynchronizer;
	}

	#askToFillRequiredFields(data: Object): Promise<ResolveResult>
	{
		// just in case that there is previous not yet closed editor
		BX.Crm.PartialEditorDialog.close('entity-converter-editor');

		const entityEditor = BX.Crm.PartialEditorDialog.create(
			'entity-converter-editor',
			{
				title: Loc.getMessage('CRM_CONVERSION_REQUIRED_FIELDS_POPUP_TITLE'),
				entityTypeId: this.#entityTypeId,
				entityId: this.#entityId,
				fieldNames: Object.keys(data.CHECK_ERRORS),
				helpData: {
					text: Loc.getMessage('CRM_CONVERSION_MORE_ABOUT_REQUIRED_FIELDS'),
					code: REQUIRED_FIELDS_INFOHELPER_CODE,
				},
				context: data.CONTEXT,
			},
		);

		return new Promise((resolve) => {
			const handler = (sender: BX.Crm.PartialEditorDialog, eventParams: Object) => {
				if (this.#entityTypeId !== eventParams?.entityTypeId || this.#entityId !== eventParams?.entityId)
				{
					return;
				}

				// eslint-disable-next-line @bitrix24/bitrix24-rules/no-bx
				BX.removeCustomEvent(window, 'Crm.PartialEditorDialog.Close', handler);

				// yes, 'canceled' with double 'l' in this case
				const isCanceled = Type.isBoolean(eventParams.isCancelled) ? eventParams.isCancelled : true;
				if (isCanceled)
				{
					resolve({ isCanceled: true, isFinished: true });
				}
				else
				{
					resolve(this.#request());
				}
			};

			// eslint-disable-next-line @bitrix24/bitrix24-rules/no-bx
			BX.addCustomEvent(window, 'Crm.PartialEditorDialog.Close', handler);

			entityEditor.open();
		});
	}

	#getMessage(phraseId): string
	{
		if (!this.#params.messages)
		{
			this.#params.messages = {};
		}

		return this.#params.messages[phraseId] || phraseId;
	}

	/**
	 * @deprecated Will be removed soon
	 * @todo delete, replace with messages from config.php
	 */
	getMessagePublic(phraseId): string
	{
		return this.#getMessage(phraseId);
	}

	#emitConvertedEvent(redirectUrl): boolean
	{
		const entityTypeId = this.getEntityTypeId();

		const eventArgs = {
			entityTypeId,
			entityTypeName: BX.CrmEntityType.resolveName(entityTypeId),
			entityId: this.#entityId,
			redirectUrl,
			isRedirected: false,
		};

		const current = BX.Crm.Page.getTopSlider();
		if (current)
		{
			eventArgs.sliderUrl = current.getUrl();
		}

		// eslint-disable-next-line @bitrix24/bitrix24-rules/no-bx
		BX.onCustomEvent(window, 'Crm.EntityConverter.Converted', [this, eventArgs]);
		BX.localStorage.set('onCrmEntityConvert', eventArgs, 10);

		this.getConfig().getActiveItems().forEach((item) => {
			EventEmitter.emit('Crm.EntityConverter.SingleConverted', {
				entityTypeName: BX.CrmEntityType.resolveName(item.getEntityTypeId()),
			});
		});

		return eventArgs.isRedirected;
	}
}
