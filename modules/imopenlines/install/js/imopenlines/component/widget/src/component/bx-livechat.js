/**
 * Bitrix OpenLines widget
 * LiveChat base component (Vue component)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import {BitrixVue} from 'ui.vue';
import {Vuex} from 'ui.vue.vuex';
import {Utils} from 'im.lib.utils';
import {Logger} from 'im.lib.logger';
import {
	FormType,
	VoteType,
	LocationStyle,
	LocationType,
	LanguageType,
	WidgetEventType,
	WidgetBaseSize,
	WidgetMinimumSize
} from '../const';
import {DeviceType, DeviceOrientation, EventType} from 'im.const';
import {EventEmitter} from 'main.core.events';

import {WidgetSendMessageHandler} from '../event-handler/widget-send-message-handler';
import {WidgetTextareaHandler} from '../event-handler/widget-textarea-handler';
import {WidgetTextareaUploadHandler} from '../event-handler/widget-textarea-upload-handler';
import {WidgetReadingHandler} from '../event-handler/widget-reading-handler';
import {WidgetResizeHandler} from '../event-handler/widget-resize-handler';
import {WidgetConsentHandler} from '../event-handler/widget-consent-handler';
import {WidgetFormHandler} from '../event-handler/widget-form-handler';
import {TextareaDragHandler} from 'im.event-handler';
import {WidgetReactionHandler} from '../event-handler/widget-reaction-handler';
import {WidgetHistoryHandler} from '../event-handler/widget-history-handler';
import {WidgetDialogActionHandler} from '../event-handler/widget-dialog-action-handler';

BitrixVue.component('bx-livechat',
{
	data()
	{
		return {
			// sizes
			widgetAvailableHeight: 0,
			widgetAvailableWidth: 0,
			widgetCurrentHeight: 0,
			widgetCurrentWidth: 0,
			widgetIsResizing: false,
			textareaHeight: 100,
			// welcome form
			welcomeFormFilled: false,
			// multi dialog
			startNewChatMode: false,
		};
	},
	computed:
	{
		FormType: () => FormType,
		VoteType: () => VoteType,
		DeviceType: () => DeviceType,
		EventType: () => EventType,

		showTextarea()
		{
			if (this.widget.common.isCreateSessionMode)
			{
				return this.startNewChatMode;
			}

			const {crmFormsSettings} = this.widget.common;

			// show if we dont use welcome form
			if (!crmFormsSettings.useWelcomeForm || !crmFormsSettings.welcomeFormId)
			{
				return true;
			}
			else
			{
				// show if we use welcome form with delay, otherwise check if it was filled
				return crmFormsSettings.welcomeFormDelay ? true : this.welcomeFormFilled;
			}
		},
		// for welcome CRM-form before dialog start
		showWelcomeForm()
		{
			//we are using welcome form, it doesnt have delay and it was not already filled
			return this.widget.common.crmFormsSettings.useWelcomeForm
				&& !this.widget.common.crmFormsSettings.welcomeFormDelay
				&& this.widget.common.crmFormsSettings.welcomeFormId
				&& !this.welcomeFormFilled;
		},
		textareaHeightStyle()
		{
			return {flex: `0 0 ${this.textareaHeight}px`};
		},
		textareaBottomMargin()
		{
			if (!this.widget.common.copyright && !this.isBottomLocation())
			{
				return {marginBottom: '5px'};
			}
			return '';
		},
		widgetHeightStyle()
		{
			if (Utils.device.isMobile() || this.widget.common.pageMode)
			{
				return;
			}

			if (this.widgetAvailableHeight < WidgetBaseSize.height || this.widgetAvailableHeight < this.widgetCurrentHeight)
			{
				this.widgetCurrentHeight = Math.max(this.widgetAvailableHeight, WidgetMinimumSize.height);
			}

			return `${this.widgetCurrentHeight}px`;
		},
		widgetWidthStyle()
		{
			if (Utils.device.isMobile() || this.widget.common.pageMode)
			{
				return;
			}

			if (this.widgetAvailableWidth < WidgetBaseSize.width || this.widgetAvailableWidth < this.widgetCurrentWidth)
			{
				this.widgetCurrentWidth = Math.max(this.widgetAvailableWidth, WidgetMinimumSize.width);
			}

			return `${this.widgetCurrentWidth}px`;
		},
		userSelectStyle()
		{
			return this.widgetIsResizing ? 'none' : 'auto';
		},
		widgetMobileDisabled()
		{
			if (this.application.device.type !== DeviceType.mobile)
			{
				return false;
			}

			if (this.application.device.orientation !== DeviceOrientation.horizontal)
			{
				return false;
			}

			if (navigator.userAgent.toString().includes('iPhone'))
			{
				return true;
			}
			else
			{
				return (typeof window.screen !== 'object') || window.screen.availHeight < 800;
			}
		},
		widgetPositionClass()
		{
			const className = [];

			if (this.widget.common.pageMode)
			{
				className.push('bx-livechat-page-mode');
			}
			else
			{
				className.push(`bx-livechat-position-${LocationStyle[this.widget.common.location]}`);
			}

			return className;
		},
		widgetLanguageClass()
		{
			const className = [];

			if (this.application.common.languageId === LanguageType.russian)
			{
				className.push('bx-livechat-logo-ru');
			}
			else if (this.application.common.languageId === LanguageType.ukraine)
			{
				className.push('bx-livechat-logo-ua');
			}
			else
			{
				className.push('bx-livechat-logo-en');
			}

			return className;
		},
		widgetPlatformClass()
		{
			const className = [];

			if (Utils.device.isMobile())
			{
				className.push('bx-livechat-mobile');
			}
			else if (Utils.browser.isSafari())
			{
				className.push('bx-livechat-browser-safari');
			}
			else if (Utils.browser.isIe())
			{
				className.push('bx-livechat-browser-ie');
			}

			if (Utils.platform.isMac())
			{
				className.push('bx-livechat-mac');
			}
			else
			{
				className.push('bx-livechat-custom-scroll');
			}

			return className;
		},
		widgetClassName()
		{
			const className = [];

			className.push(...this.widgetPositionClass, ...this.widgetLanguageClass, ...this.widgetPlatformClass);

			if (!this.widget.common.online)
			{
				className.push('bx-livechat-offline-state');
			}

			if (this.widget.common.dragged)
			{
				className.push('bx-livechat-drag-n-drop');
			}

			if (this.widget.common.dialogStart)
			{
				className.push('bx-livechat-chat-start');
			}

			if (
				this.widget.dialog.operator.name
				&& !(this.application.device.type === DeviceType.mobile && this.application.device.orientation === DeviceOrientation.horizontal)
			)
			{
				className.push('bx-livechat-has-operator');
			}

			if (this.widget.common.styles.backgroundColor && Utils.isDarkColor(this.widget.common.styles.iconColor))
			{
				className.push('bx-livechat-bright-header');
			}

			return className;
		},
		showMessageDialog()
		{
			return this.messageCollection.length > 0;
		},
		localize()
		{
			return BitrixVue.getFilteredPhrases('BX_LIVECHAT_', this);
		},
		...Vuex.mapState({
			widget: state => state.widget,
			application: state => state.application,
			dialog: state => state.dialogues.collection[state.application.dialog.dialogId],
			messageCollection: state => state.messages.collection[state.application.dialog.chatId]
		})
	},
	created()
	{
		Logger.warn('Livechat component created');
		// we need to wait for initialization and widget opening to init logic handlers
		this.onCreated().then(() => {
			this.subscribeToEvents();
			this.initEventHandlers();
		});
	},
	mounted()
	{
		if (this.widget.user.id > 0)
		{
			this.welcomeFormFilled = true;
		}
		this.registerZIndex();
	},
	beforeDestroy()
	{
		this.unsubscribeEvents();
		this.destroyHandlers();
		this.unregisterZIndex();
	},
	methods:
	{
		// region initialization
		initEventHandlers()
		{
			this.sendMessageHandler = new WidgetSendMessageHandler(this.$Bitrix);
			this.textareaHandler = new WidgetTextareaHandler(this.$Bitrix);
			this.textareaUploadHandler = new WidgetTextareaUploadHandler(this.$Bitrix);
			this.readingHandler = new WidgetReadingHandler(this.$Bitrix);
			this.consentHandler = new WidgetConsentHandler(this.$Bitrix);
			this.formHandler = new WidgetFormHandler(this.$Bitrix);
			this.textareaDragHandler = this.getTextareaDragHandler();
			this.resizeHandler = this.getWidgetResizeHandler();
			this.reactionHandler = new WidgetReactionHandler(this.$Bitrix);
			this.historyHandler = new WidgetHistoryHandler(this.$Bitrix);
			this.dialogActionHandler = new WidgetDialogActionHandler(this.$Bitrix);
		},
		destroyHandlers()
		{
			this.sendMessageHandler.destroy();
			this.textareaHandler.destroy();
			this.textareaUploadHandler.destroy();
			this.readingHandler.destroy();
			this.consentHandler.destroy();
			this.formHandler.destroy();
			this.textareaDragHandler.destroy();
			this.resizeHandler.destroy();
			this.reactionHandler.destroy();
			this.historyHandler.destroy();
			this.dialogActionHandler.destroy();
		},
		subscribeToEvents()
		{
			document.addEventListener('keydown', this.onWindowKeyDown);

			if (!Utils.device.isMobile() && !this.widget.common.pageMode)
			{
				this.getAvailableSpaceFunc = Utils.throttle(this.getAvailableSpace, 50);
				window.addEventListener('resize', this.getAvailableSpaceFunc);
			}
		},
		unsubscribeEvents()
		{
			document.removeEventListener('keydown', this.onWindowKeyDown);

			if (!Utils.device.isMobile() && !this.widget.common.pageMode)
			{
				window.removeEventListener('resize', this.getAvailableSpaceFunc);
			}
		},
		initMobileEnv(): Promise
		{
			const metaTags = document.head.querySelectorAll('meta');
			const viewPortMetaSiteNode = [...metaTags].find(element => element.name === 'viewport');

			if (viewPortMetaSiteNode)
			{
				// save tag and remove it from DOM
				this.viewPortMetaSiteNode = viewPortMetaSiteNode;
				this.viewPortMetaSiteNode.remove();
			}
			else
			{
				this.createViewportMeta();
			}

			if (!this.viewPortMetaWidgetNode)
			{
				this.viewPortMetaWidgetNode = document.createElement('meta');
				this.viewPortMetaWidgetNode.setAttribute('name', 'viewport');
				this.viewPortMetaWidgetNode.setAttribute('content', 'width=device-width, initial-scale=1.0, user-scalable=0');
				document.head.append(this.viewPortMetaWidgetNode);
			}

			document.body.classList.add('bx-livechat-mobile-state');

			if (Utils.browser.isSafariBased())
			{
				document.body.classList.add('bx-livechat-mobile-safari-based');
			}

			return new Promise((resolve) => {
				setTimeout(() => {
					this.$store.dispatch('widget/show').then(resolve);
				}, 50);
			});
		},
		createViewportMeta()
		{
			let contentWidth = document.body.offsetWidth;
			if (contentWidth < window.innerWidth)
			{
				contentWidth = window.innerWidth;
			}
			if (contentWidth < 1024)
			{
				contentWidth = 1024;
			}

			this.viewPortMetaSiteNode = document.createElement('meta');
			this.viewPortMetaSiteNode.setAttribute('name', 'viewport');
			this.viewPortMetaSiteNode.setAttribute('content', `width=${contentWidth}, initial-scale=1.0, user-scalable=1`);
		},
		removeMobileEnv()
		{
			document.body.classList.remove('bx-livechat-mobile-state');

			if (Utils.browser.isSafariBased())
			{
				document.body.classList.remove('bx-livechat-mobile-safari-based');
			}

			if (this.viewPortMetaWidgetNode)
			{
				this.viewPortMetaWidgetNode.remove();
				this.viewPortMetaWidgetNode = null;
			}

			if (this.viewPortMetaSiteNode)
			{
				document.head.append(this.viewPortMetaSiteNode);
				this.viewPortMetaSiteNode = null;
			}
		},
		onCreated(): Promise
		{
			return new Promise((resolve) => {
				if (Utils.device.isMobile())
				{
					this.initMobileEnv().then(resolve);
				}
				else
				{
					this.$store.dispatch('widget/show').then(() => {
						this.widgetCurrentHeight = WidgetBaseSize.height;
						this.widgetCurrentWidth = WidgetBaseSize.width;
						this.getAvailableSpace();

						// restore widget size from cache
						this.widgetCurrentHeight = this.widget.common.widgetHeight || this.widgetCurrentHeight;
						this.widgetCurrentWidth = this.widget.common.widgetWidth || this.widgetCurrentWidth;

						resolve();
					});
				}

				// restore textarea size from cache
				this.textareaHeight = this.widget.common.textareaHeight || this.textareaHeight;
				this.initCollections();
			});
		},
		initCollections()
		{
			this.$store.commit('files/initCollection', { chatId: this.getApplication().getChatId() });
			this.$store.commit('messages/initCollection', { chatId: this.getApplication().getChatId() });
			this.$store.commit('dialogues/initCollection', {
				dialogId: this.getApplication().getDialogId(),
				fields: {
					entityType: 'LIVECHAT',
					type: 'livechat'
				}
			});
		},
		// endregion initialization
		// region events
		onBeforeClose()
		{
			if (Utils.device.isMobile())
			{
				this.removeMobileEnv();
			}
		},
		onAfterClose()
		{
			this.getApplication().close();
		},
		onOpenMenu()
		{
			this.historyHandler.getHtmlHistory();
		},
		onPullRequestConfig()
		{
			this.getApplication().recoverPullConnection();
		},
		onSmilesSelectSmile(event)
		{
			EventEmitter.emit(EventType.textarea.insertText, {text: event.text});
		},
		onSmilesSelectSet()
		{
			EventEmitter.emit(EventType.textarea.setFocus);
		},
		onWidgetStartDrag(event)
		{
			this.resizeHandler.startResize(event, this.widgetCurrentHeight, this.widgetCurrentWidth);
			this.widgetIsResizing = true;
			EventEmitter.emit(EventType.textarea.setBlur, true);
		},
		onWindowKeyDown(event)
		{
			// not escape
			if (event.keyCode !== 27)
			{
				return;
			}

			// hide form
			if (this.widget.common.showForm !== FormType.none)
			{
				this.$store.commit('widget/common', {showForm: FormType.none});
			}
			// decline consent
			else if (this.widget.common.showConsent)
			{
				EventEmitter.emit(WidgetEventType.declineConsent);
			}
			// close widget
			else
			{
				this.close();
			}

			event.preventDefault();
			event.stopPropagation();

			EventEmitter.emit(EventType.textarea.setFocus);
		},
		onWelcomeFormSendSuccess()
		{
			this.welcomeFormFilled = true;
		},
		onWelcomeFormSendError(error)
		{
			console.error('onWelcomeFormSendError', error);
			this.welcomeFormFilled = true;
		},
		onTextareaStartDrag(event)
		{
			this.textareaDragHandler.onStartDrag(event, this.textareaHeight);
			EventEmitter.emit(EventType.textarea.setBlur, true);
		},
		openDialogList()
		{
			this.$store.commit('widget/common', {isCreateSessionMode: !this.widget.common.isCreateSessionMode});
			this.startNewChatMode = false;
		},
		onStartNewChat()
		{
			this.startNewChatMode = true;
		},
		// endregion events
		// region helpers
		getApplication()
		{
			return this.$Bitrix.Application.get();
		},
		close()
		{
			if (this.widget.common.pageMode)
			{
				return false;
			}

			this.onBeforeClose();
			this.$store.commit('widget/common', {showed: false});
		},
		// how much width and height we have for resizing
		getAvailableSpace()
		{
			const widgetMargin = 50;
			if (this.isBottomLocation())
			{
				const bottomPosition = this.$refs.widgetWrapper.getBoundingClientRect().bottom;
				const widgetBottomMargin = window.innerHeight - bottomPosition;
				this.widgetAvailableHeight = window.innerHeight - widgetMargin - widgetBottomMargin;
			}
			else
			{
				const topPosition = this.$refs.widgetWrapper.getBoundingClientRect().top;
				this.widgetAvailableHeight = window.innerHeight - widgetMargin - topPosition;
			}

			this.widgetAvailableWidth = window.innerWidth - widgetMargin * 2;

			if (this.resizeHandler)
			{
				this.resizeHandler.setAvailableWidth(this.widgetAvailableWidth);
				this.resizeHandler.setAvailableHeight(this.widgetAvailableHeight);
			}
		},
		getTextareaDragHandler(): TextareaDragHandler
		{
			return new TextareaDragHandler({
				[TextareaDragHandler.events.onHeightChange]: ({data}) => {
					const {newHeight} = data;
					if (this.textareaHeight !== newHeight)
					{
						this.textareaHeight = newHeight;
					}
				},
				[TextareaDragHandler.events.onStopDrag]: () => {
					this.$store.commit('widget/common', {textareaHeight: this.textareaHeight});
					EventEmitter.emit(EventType.dialog.scrollToBottom, {chatId: this.chatId, force: true});
				}
			});
		},
		getWidgetResizeHandler(): WidgetResizeHandler
		{
			return new WidgetResizeHandler({
				widgetLocation: this.widget.common.location,
				availableWidth: this.widgetAvailableWidth,
				availableHeight: this.widgetAvailableHeight,
				events: {
					[WidgetResizeHandler.events.onSizeChange]: ({data}) => {
						const {newHeight, newWidth} = data;
						if (this.widgetCurrentHeight !== newHeight)
						{
							this.widgetCurrentHeight = newHeight;
						}
						if (this.widgetCurrentWidth !== newWidth)
						{
							this.widgetCurrentWidth = newWidth;
						}
					},
					[WidgetResizeHandler.events.onStopResize]: () => {
						this.widgetIsResizing = false;
						this.$store.commit('widget/common', {widgetHeight: this.widgetCurrentHeight, widgetWidth: this.widgetCurrentWidth});
					}
				}
			});
		},
		isBottomLocation()
		{
			return [LocationType.bottomLeft, LocationType.bottomMiddle, LocationType.bottomRight].includes(this.widget.common.location);
		},
		isPageMode()
		{
			return this.widget.common.pageMode;
		},
		registerZIndex()
		{
			this.zIndexStackInstance = this.$Bitrix.Data.get('zIndexStack');
			if (this.zIndexStackInstance && !!this.$refs.widgetWrapper)
			{
				this.zIndexStackInstance.register(this.$refs.widgetWrapper);
			}
		},
		unregisterZIndex()
		{
			if (this.zIndexStackInstance)
			{
				this.zIndexStackInstance.unregister(this.$refs.widgetWrapper);
			}
		}
		// endregion helpers
	},
	// language=Vue
	template: `
		<transition enter-active-class="bx-livechat-show" leave-active-class="bx-livechat-close" @after-leave="onAfterClose">
			<div
				:class="widgetClassName"
				v-if="widget.common.showed"
				:style="{height: widgetHeightStyle, width: widgetWidthStyle, userSelect: userSelectStyle}"
				class="bx-livechat-wrapper bx-livechat-show"
				ref="widgetWrapper"
			>
				<div class="bx-livechat-box">
					<div v-if="isBottomLocation() && !isPageMode()" class="bx-livechat-widget-resize-handle" @mousedown="onWidgetStartDrag"></div>
					<bx-livechat-head 
						:isWidgetDisabled="widgetMobileDisabled" 
						@openMenu="onOpenMenu" 
						@close="close"
						@openDialogList="openDialogList"
					/>
					<template v-if="widgetMobileDisabled">
						<bx-livechat-body-orientation-disabled/>
					</template>
					<template v-else-if="application.error.active">
						<bx-livechat-body-error/>
					</template>
					<template v-else-if="!widget.common.configId">
						<div class="bx-livechat-body" key="loading-body">
							<bx-livechat-body-loading/>
						</div>
					</template>
					<template v-else>
						<div v-show="!widget.common.dialogStart" class="bx-livechat-body" :class="{'bx-livechat-body-with-scroll': showWelcomeForm}" key="welcome-body">
							<bx-imopenlines-form
							  v-show="showWelcomeForm"
							  @formSendSuccess="onWelcomeFormSendSuccess"
							  @formSendError="onWelcomeFormSendError"
							/>
							<template v-if="!showWelcomeForm">
								<bx-livechat-body-operators/>
								<keep-alive include="bx-livechat-smiles">
									<template v-if="widget.common.showForm === FormType.smile">
										<bx-livechat-smiles @selectSmile="onSmilesSelectSmile" @selectSet="onSmilesSelectSet"/>
									</template>
								</keep-alive>
							</template>
						</div>
						<template v-if="widget.common.dialogStart">
							<bx-pull-component-status :canReconnect="true" @reconnect="onPullRequestConfig"/>
							<div :class="['bx-livechat-body', {'bx-livechat-body-with-message': showMessageDialog}]" key="with-message">
								<template v-if="widget.common.isCreateSessionMode">
									<bx-livechat-dialogues-list @startNewChat="onStartNewChat"/>
								</template>
								<template v-else-if="showMessageDialog">
									<div class="bx-livechat-dialog">
										<bx-im-component-dialog
											:userId="application.common.userId"
											:dialogId="application.dialog.dialogId"
											:messageLimit="application.dialog.messageLimit"
											:enableReactions="true"
											:enableDateActions="false"
											:enableCreateContent="false"
											:enableGestureQuote="true"
											:enableGestureMenu="true"
											:showMessageAvatar="false"
											:showMessageMenu="false"
											:skipDataRequest="true"
											:showLoadingState="false"
											:showEmptyState="false"
										 />
									</div>
								</template>
								<template v-else>
									<bx-livechat-body-loading/>
								</template>

								<keep-alive include="bx-livechat-smiles">
									<template v-if="widget.common.showForm === FormType.like && widget.common.vote.enable">
										<bx-livechat-form-vote/>
									</template>
									<template v-else-if="widget.common.showForm === FormType.welcome">
										<bx-livechat-form-welcome/>
									</template>
									<template v-else-if="widget.common.showForm === FormType.offline">
										<bx-livechat-form-offline/>
									</template>
									<template v-else-if="widget.common.showForm === FormType.history">
										<bx-livechat-form-history/>
									</template>
									<template v-else-if="widget.common.showForm === FormType.smile">
										<bx-livechat-smiles @selectSmile="onSmilesSelectSmile" @selectSet="onSmilesSelectSet"/>
									</template>
								</keep-alive>
							</div>
						</template>
						<div v-if="showTextarea || startNewChatMode" class="bx-livechat-textarea" :style="[textareaHeightStyle, textareaBottomMargin]" ref="textarea">
							<div class="bx-livechat-textarea-resize-handle" @mousedown="onTextareaStartDrag" @touchstart="onTextareaStartDrag"></div>
							<bx-im-component-textarea
								:siteId="application.common.siteId"
								:userId="application.common.userId"
								:dialogId="application.dialog.dialogId"
								:writesEventLetter="3"
								:enableEdit="true"
								:enableCommand="false"
								:enableMention="false"
								:enableFile="application.disk.enabled"
								:autoFocus="application.device.type !== DeviceType.mobile"
								:styles="{button: {backgroundColor: widget.common.styles.backgroundColor, iconColor: widget.common.styles.iconColor}}"
							/>
						</div>
						<div v-if="!widget.common.copyright && !isBottomLocation" class="bx-livechat-nocopyright-resize-wrap" style="position: relative;">
							<div class="bx-livechat-widget-resize-handle" @mousedown="onWidgetStartDrag"></div>
						</div>
						<bx-livechat-form-consent />
						<template v-if="widget.common.copyright">
							<div class="bx-livechat-copyright">
								<template v-if="widget.common.copyrightUrl">
									<a class="bx-livechat-copyright-link" :href="widget.common.copyrightUrl" target="_blank">
										<span class="bx-livechat-logo-name">{{localize.BX_LIVECHAT_COPYRIGHT_TEXT}}</span>
										<span class="bx-livechat-logo-icon"></span>
									</a>
								</template>
								<template v-else>
									<span class="bx-livechat-logo-name">{{localize.BX_LIVECHAT_COPYRIGHT_TEXT}}</span>
									<span class="bx-livechat-logo-icon"></span>
								</template>
								<div v-if="!isBottomLocation() && !isPageMode()" class="bx-livechat-widget-resize-handle" @mousedown="onWidgetStartDrag"></div>
							</div>
						</template>
					</template>
				</div>
			</div>
		</transition>
	`
});
