import { ajax as Ajax, Loc, Text, Type } from 'main.core';
import { UI } from 'ui.notification';
import { TextEditor } from 'ui.text-editor';
import { BreadcrumbsEvents } from './navigation/breadcrumbs';
import { ButtonEvents } from './navigation/button';
import { Navigation } from './navigation/navigation';
import type { BasePage } from './page/base-page';
import { AboutPage } from './page/about-page';
import { ClientPage } from './page/client-page';
// import { ControlPage } from './page/control-page';
// import { EncouragementPage } from './page/encouragement-page';
import { SettingsPage } from './page/settings-page';

/**
 * @see BasePage
 */
interface Page extends BasePage
{
	getId(): string,
	getData(): Object,
	validate(): boolean,
	onValidationFailed(): void,
	isReadyToMoveOn(): boolean,
}

export const CallAssessment = {
	components: {
		AboutPage,
		ClientPage,
		SettingsPage,
		// ControlPage,
		// EncouragementPage,
		Navigation,
	},

	props: {
		settings: {
			type: Object,
			default: {},
		},
		params: {
			type: Object,
			default: {},
		},
		textEditor: TextEditor,
	},

	data(): Object
	{
		const { data } = this.params;

		let id = null;
		if (this.settings.isCopy)
		{
			id = null;
		}
		else
		{
			id = data?.id ?? null;
		}

		return {
			id,
			pagesData: this.initPagesData(data),
			activePageId: 'about',
			title: data.title ?? null,
			canShowNextPage: false,
		};
	},

	watch: {
		activePageId(pageId: string): void
		{
			this.onPageDataChange({
				id: pageId,
				data: this.getPageData(pageId),
			});
		},
	},

	methods: {
		initPagesData(data: Object): Object
		{
			return {
				client: {
					data: {
						activeClientTypeIds: data?.clientTypeIds,
					},
				},
				settings: {
					data: {
						callTypeId: data?.callTypeId,
						autoCheckTypeId: data?.autoCheckTypeId,
					},
				},
				// temporary disabled
				/* control: {
					data: {
						isStrictControl: data?.controlData?.isStrictControl ?? false,
						fluidCallCount: data?.controlData?.fluidCallCount,
						items: [],
						headItems: data?.controlData?.headItems ?? null,
						useSummary: data?.useSummary ?? false,
						users: data?.users ?? {},
					},
				},
				encouragement: {
					data: {
						isAutoEncourage: data?.encouragementData?.isAutoEncourage ?? false,
						encourageCallCount: data?.encouragementData?.encourageCallCount,

						users: data?.users ?? {},
						headItems: data?.controlData?.headItems ?? null, // for readonly head selector in page
					},
				}, */
			};
		},
		onNavigationButtonClick({ data }): void
		{
			const { id } = data;
			if (id === 'cancel' || id === 'close')
			{
				this.closeSlider();

				return;
			}

			if (id === 'back')
			{
				const currentIndex = this.pagesList.indexOf(this.activePageId);

				if (currentIndex > 0)
				{
					this.activePageId = this.pagesList[currentIndex - 1];
				}

				return;
			}

			if (!this.isActivePageValid())
			{
				this.onActivePageValidationFailed();

				return;
			}

			if (id === 'continue')
			{
				const currentIndex = this.pagesList.indexOf(this.activePageId);

				if (currentIndex < this.pagesList.length - 1)
				{
					this.activePageId = this.pagesList[currentIndex + 1];
				}

				return;
			}

			if (id === 'submit' || id === 'update')
			{
				this.sendData();
			}
		},
		sendData(): void
		{
			let data = {
				title: this.title,
			};

			this.pagesList.forEach((pageId: string) => {
				data = {
					...data,
					...this.$refs[pageId]?.getData(),
				};
			});

			Ajax
				.runAction('crm.copilot.callassessment.save', {
					data: {
						id: this.id,
						data,
					},
				}).then(
					(response) => {
						if (response?.status !== 'success')
						{
							UI.Notification.Center.notify({
								content: Text.encode(response.errors[0].message),
								autoHideDelay: 6000,
							});

							return;
						}

						this.onSaveCallback();
						this.closeSlider();
					},
					(response) => {
						const messageCode = response.errors[0]?.code === 'AI_NOT_AVAILABLE'
							? 'CRM_COPILOT_CALL_ASSESSMENT_PAGE_COPILOT_ERROR'
							: 'CRM_COPILOT_CALL_ASSESSMENT_PAGE_SAVE_ERROR'
						;

						UI.Notification.Center.notify({
							content: Loc.getMessage(messageCode),
							autoHideDelay: 6000,
						});
					},
				)
				.catch((response) => {
					UI.Notification.Center.notify({
						content: Text.encode(response.errors[0].message),
						autoHideDelay: 6000,
					});

					throw response;
				});
		},
		closeSlider(): void
		{
			top.BX.SidePanel.Instance.getSliderByWindow(window).close();
		},
		onSaveCallback(): void
		{
			if (Type.isFunction(this.params?.events?.onSave))
			{
				this.params.events.onSave();
			}
		},
		setActivePageId({ data }): void
		{
			const newPageId = data.itemId;
			const currentPageIndex = this.pagesList.indexOf(this.activePageId);
			const newPageIndex = this.pagesList.indexOf(newPageId);

			if (currentPageIndex === newPageIndex)
			{
				return;
			}

			if (newPageIndex > currentPageIndex && !this.isActivePageValid())
			{
				this.onActivePageValidationFailed();

				return;
			}

			this.activePageId = newPageId;
		},
		isPageActive(id: string): boolean
		{
			return this.activePageId === id;
		},
		onPageDataChange({ id: pageId, data }): void
		{
			this.pagesData[pageId] = data;
			this.canShowNextPage = this.isActivePageReadyToMoveOn();
		},
		getPageData(pageId: string): Object
		{
			if (Type.isObjectLike(this.pagesData[pageId]))
			{
				return this.pagesData[pageId].data;
			}

			return {};
		},
		getPageSettings(pageId: string): Object
		{
			if (pageId === 'settings')
			{
				return {
					baas: this.settings.baas ?? {},
				};
			}

			return {};
		},
		setTitle(title: string): void
		{
			this.title = title;
		},
		isActivePageValid(): boolean
		{
			return this.getActivePage().validate();
		},
		onActivePageValidationFailed(): void
		{
			return this.getActivePage().onValidationFailed();
		},
		isActivePageReadyToMoveOn(): boolean
		{
			return this.getActivePage().isReadyToMoveOn();
		},
		getActivePage(): Page
		{
			return this.$refs[this.activePageId];
		},
	},

	computed: {
		pagesList(): []
		{
			return [
				'about',
				'client',
				'settings',
				// 'control',
				// 'encouragement',
			];
		},
		isNew(): boolean
		{
			return this.params.data?.id <= 0;
		},
		readOnly(): boolean
		{
			return this.settings.readOnly;
		},
	},

	mounted()
	{
		this.canShowNextPage = this.isActivePageReadyToMoveOn();

		this.$Bitrix.eventEmitter.subscribe(BreadcrumbsEvents.itemClick, this.setActivePageId);
		this.$Bitrix.eventEmitter.subscribe(ButtonEvents.click, this.onNavigationButtonClick);
	},

	beforeUnmount()
	{
		this.$Bitrix.eventEmitter.unsubscribe(BreadcrumbsEvents.itemClick, this.setActivePageId);
		this.$Bitrix.eventEmitter.unsubscribe(ButtonEvents.click, this.onNavigationButtonClick);
	},

	template: `
		<div class="crm-copilot__call-assessment_container">
			<div class="crm-copilot__call-assessment_page-wrapper">
				<AboutPage 
					:is-active="isPageActive('about')"
					:data="getPageData('about')"
					:text-editor="textEditor"
					@onChange="onPageDataChange"
					ref="about"
				/>
				<ClientPage 
					:is-active="isPageActive('client')"
					:data="getPageData('client')"
					:read-only="readOnly"
					@onChange="onPageDataChange"
					ref="client"
				/>
				<SettingsPage
					:is-active="isPageActive('settings')"
					:data="getPageData('settings')"
					:settings="getPageSettings('settings')"
					:read-only="readOnly"
					@onChange="onPageDataChange"
					ref="settings"
				/>
				<!-- temporary hidden -->
				<!--<ControlPage
					:is-active="isPageActive('control')"
					:data="getPageData('control')"
					:read-only="readOnly"
					@onChange="onPageDataChange"
					ref="control"
				/>
				<EncouragementPage
					:is-active="isPageActive('encouragement')"
					:data="getPageData('encouragement')"
					:read-only="readOnly"
					@onChange="onPageDataChange"
					ref="encouragement"
				/>-->
			</div>
			<Navigation 
				:active-tab-id="activePageId"
				:is-enabled="canShowNextPage"
				:read-only="readOnly"
				:show-save-button="!isNew && !readOnly"
			/>
		</div>
	`,
};
