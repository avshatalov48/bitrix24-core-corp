/**
 * Bitrix OpenLines widget
 * LiveChat base component (Vue component)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import {BitrixVue} from "ui.vue";
import {Vuex} from "ui.vue.vuex";
import {Utils} from "im.lib.utils";
import {Logger} from "im.lib.logger";
import {FormType, VoteType, LocationStyle, LocationType, LanguageType, EventType as WidgetEventType} from "../const";
import { DeviceType, DeviceOrientation, EventType, RestMethod as ImRestMethod } from "im.const";
import {
	DialogCore, TextareaCore, TextareaUploadFile, DialogReadMessages, DialogClickOnCommand, DialogClickOnUserName,
	DialogClickOnKeyboardButton, DialogClickOnMessageMenu, DialogClickOnMessageRetry, DialogSetMessageReaction,
	DialogClickOnUploadCancel
} from 'im.mixin';
import {md5} from "main.md5";
import {EventEmitter} from "main.core.events";

/**
 * @notice Do not mutate or clone this component! It is under development.
 */
BitrixVue.component('bx-livechat',
{
	mixins: [
		DialogCore, TextareaCore, TextareaUploadFile, DialogReadMessages, DialogClickOnCommand, DialogClickOnUserName,
		DialogClickOnKeyboardButton, DialogClickOnMessageMenu, DialogClickOnMessageRetry, DialogSetMessageReaction,
		DialogClickOnUploadCancel
	],
	data()
	{
		return {
			viewPortMetaSiteNode: null,
			viewPortMetaWidgetNode: null,
			storedMessage: '',
			storedFile: null,
			widgetMinimumHeight: 435,
			widgetMinimumWidth: 340,
			widgetBaseHeight: 557,
			widgetBaseWidth: 435,
			widgetMargin: 50,
			widgetAvailableHeight: 0,
			widgetAvailableWidth: 0,
			widgetCurrentHeight: 0,
			widgetCurrentWidth: 0,
			widgetDrag: false,
			textareaFocused: false,
			textareaDrag: false,
			textareaHeight: 100,
			textareaMinimumHeight: 100,
			textareaMaximumHeight: Utils.device.isMobile()? 200: 300,
			zIndexStackInstance: null,
			welcomeFormFilled: false
		}
	},
	created()
	{
		Logger.warn('bx-livechat created');
		this.onCreated();

		document.addEventListener('keydown', this.onWindowKeyDown);
		if(!Utils.device.isMobile() && !this.widget.common.pageMode)
		{
			window.addEventListener('resize', this.getAvailableSpaceFunc = Utils.throttle(this.getAvailableSpace, 50));
		}
		EventEmitter.subscribe(WidgetEventType.requestShowForm, this.onRequestShowForm);
	},
	mounted()
	{
		if (this.widget.user.id > 0)
		{
			this.welcomeFormFilled = true;
		}
		this.zIndexStackInstance = this.$Bitrix.Data.get('zIndexStack');
		if (this.zIndexStackInstance && !!this.$refs.widgetWrapper)
		{
			this.zIndexStackInstance.register(this.$refs.widgetWrapper);
		}
	},
	beforeDestroy()
	{
		if (this.zIndexStackInstance)
		{
			this.zIndexStackInstance.unregister(this.$refs.widgetWrapper);
		}
		document.removeEventListener('keydown', this.onWindowKeyDown);
		if(!Utils.device.isMobile() && !this.widget.common.pageMode)
		{
			window.removeEventListener('resize', this.getAvailableSpaceFunc);
		}
		EventEmitter.unsubscribe(WidgetEventType.requestShowForm, this.onRequestShowForm);

		this.onTextareaDragEventRemove();
	},
	computed:
	{
		FormType: () => FormType,
		VoteType: () => VoteType,
		DeviceType: () => DeviceType,
		EventType: () => EventType,

		showTextarea()
		{
			const crmFormsSettings = this.widget.common.crmFormsSettings;

			// show if we dont use welcome form
			if (!crmFormsSettings.useWelcomeForm || !crmFormsSettings.welcomeFormId)
			{
				return true;
			}
			else
			{
				// show if we use welcome form with delay
				if (crmFormsSettings.welcomeFormDelay)
				{
					return true;
				}
				else
				{
					return this.welcomeFormFilled;
				}
			}
		},
		showWelcomeForm()
		{
			//we are using welcome form, it has delay and it was not already filled
			return this.widget.common.crmFormsSettings.useWelcomeForm
				&& !this.widget.common.crmFormsSettings.welcomeFormDelay
				&& this.widget.common.crmFormsSettings.welcomeFormId
				&& !this.welcomeFormFilled
		},
		textareaHeightStyle()
		{
			return {flex: '0 0 '+this.textareaHeight+'px'};
		},
		textareaBottomMargin()
		{
			if (!this.widget.common.copyright && !this.isBottomLocation)
			{
				return {marginBottom: '5px'};
			}
			return '';
		},
		widgetBaseSizes()
		{
			return {
				width: this.widgetBaseWidth,
				height: this.widgetBaseHeight,
			}
		},
		widgetHeightStyle()
		{
			if(Utils.device.isMobile() || this.widget.common.pageMode)
			{
				return;
			}

			if (this.widgetAvailableHeight < this.widgetBaseSizes.height || this.widgetAvailableHeight < this.widgetCurrentHeight)
			{
				this.widgetCurrentHeight = Math.max(this.widgetAvailableHeight, this.widgetMinimumHeight);
			}

			return this.widgetCurrentHeight+'px';
		},
		widgetWidthStyle()
		{
			if(Utils.device.isMobile() || this.widget.common.pageMode)
			{
				return;
			}

			if (this.widgetAvailableWidth < this.widgetBaseSizes.width || this.widgetAvailableWidth < this.widgetCurrentWidth)
			{
				this.widgetCurrentWidth = Math.max(this.widgetAvailableWidth, this.widgetMinimumWidth);
			}

			return this.widgetCurrentWidth+'px';
		},
		userSelectStyle()
		{
			return this.widgetDrag ? 'none' : 'auto';
		},
		isBottomLocation()
		{
			return [LocationType.bottomLeft, LocationType.bottomMiddle, LocationType.bottomRight].includes(this.widget.common.location);
		},
		isLeftLocation()
		{
			return [LocationType.bottomLeft, LocationType.topLeft, LocationType.topMiddle].includes(this.widget.common.location);
		},
		localize()
		{
			return BitrixVue.getFilteredPhrases('BX_LIVECHAT_', this);
		},
		widgetMobileDisabled(state)
		{
			if (state.application.device.type === DeviceType.mobile)
			{
				if (navigator.userAgent.toString().includes('iPad'))
				{
				}
				else if (state.application.device.orientation === DeviceOrientation.horizontal)
				{
					if (navigator.userAgent.toString().includes('iPhone'))
					{
						return true;
					}
					else
					{
						return !(typeof window.screen === 'object' && window.screen.availHeight >= 800);
					}
				}
			}

			return false;
		},
		widgetClassName(state)
		{
			let className = ['bx-livechat-wrapper'];

			className.push('bx-livechat-show');

			if (state.widget.common.pageMode)
			{
				className.push('bx-livechat-page-mode');
			}
			else
			{
				className.push('bx-livechat-position-'+LocationStyle[state.widget.common.location]);
			}

			if (state.application.common.languageId === LanguageType.russian)
			{
				className.push('bx-livechat-logo-ru');
			}
			else if (state.application.common.languageId === LanguageType.ukraine)
			{
				className.push('bx-livechat-logo-ua');
			}
			else
			{
				className.push('bx-livechat-logo-en');
			}

			if (!state.widget.common.online)
			{
				className.push('bx-livechat-offline-state');
			}

			if (state.widget.common.dragged)
			{
				className.push('bx-livechat-drag-n-drop');
			}

			if (state.widget.common.dialogStart)
			{
				className.push('bx-livechat-chat-start');
			}

			if (
				state.widget.dialog.operator.name
				&& !(state.application.device.type === DeviceType.mobile && state.application.device.orientation === DeviceOrientation.horizontal)
			)
			{
				className.push('bx-livechat-has-operator');
			}

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

			if (state.widget.common.styles.backgroundColor && Utils.isDarkColor(state.widget.common.styles.iconColor))
			{
				className.push('bx-livechat-bright-header');
			}

			return className.join(' ');
		},
		showMessageDialog()
		{
			return this.messageCollection.length > 0;
		},
		...Vuex.mapState({
			widget: state => state.widget,
			application: state => state.application,
			dialog: state => state.dialogues.collection[state.application.dialog.dialogId],
			messageCollection: state => state.messages.collection[state.application.dialog.chatId]
		})
	},
	watch:
	{
		sessionClose(value)
		{
			Logger.log('sessionClose change', value);
		},
		//Redefined for uploadFile mixin
		dialogInited(newValue)
		{
			return false;
		}
	},
	methods:
	{
		getRestClient()
		{
			return this.$Bitrix.RestClient.get();
		},
		getApplication()
		{
			return this.$Bitrix.Application.get();
		},
		onSendMessage({data: event})
		{
			event.focus = event.focus !== false;s

			//hide smiles
			if (this.widget.common.showForm === FormType.smile)
			{
				this.$store.commit('widget/common', {showForm: FormType.none});
			}

			//show consent window if needed
			if (!this.widget.dialog.userConsent && this.widget.common.consentUrl)
			{
				if (event.text)
				{
					this.storedMessage = event.text;
				}
				this.showConsentWidow();

				return false;
			}

			event.text = event.text? event.text: this.storedMessage;
			if (!event.text)
			{
				return false;
			}

			this.hideForm();
			this.getApplication().addMessage(event.text);

			if (event.focus)
			{
				EventEmitter.emit(EventType.textarea.setFocus);
			}

			return true;
		},
		close(event)
		{
			if (this.widget.common.pageMode)
			{
				return false;
			}

			this.onBeforeClose();
			this.$store.commit('widget/common', {showed: false});
		},
		getAvailableSpace()
		{
			if (this.isBottomLocation)
			{
				let bottomPosition = this.$refs.widgetWrapper.getBoundingClientRect().bottom;
				let widgetBottomMargin = window.innerHeight - bottomPosition;
				this.widgetAvailableHeight = window.innerHeight - this.widgetMargin - widgetBottomMargin;
			}
			else
			{
				let topPosition = this.$refs.widgetWrapper.getBoundingClientRect().top;
				this.widgetAvailableHeight = window.innerHeight - this.widgetMargin - topPosition;
			}

			this.widgetAvailableWidth = window.innerWidth - this.widgetMargin * 2;
		},
		showLikeForm()
		{
			if (this.offline)
			{
				return false;
			}

			clearTimeout(this.showFormTimeout);
			if (!this.widget.common.vote.enable)
			{
				return false;
			}
			if (
				this.widget.dialog.sessionClose
				&& this.widget.dialog.userVote !== VoteType.none
			)
			{
				return false;
			}
			this.$store.commit('widget/common', {showForm: FormType.like});
		},
		onOpenMenu(event)
		{
			this.getApplication().getHtmlHistory();
		},
		hideForm()
		{
			clearTimeout(this.showFormTimeout);

			if (this.widget.common.showForm !== FormType.none)
			{
				this.$store.commit('widget/common', {showForm: FormType.none});
			}
		},
		showConsentWidow()
		{
			this.$store.commit('widget/common', {showConsent: true});
		},
		agreeConsentWidow()
		{
			this.$store.commit('widget/common', {showConsent: false});

			this.getApplication().sendConsentDecision(true);

			if (this.storedMessage || this.storedFile)
			{
				if (this.storedMessage)
				{
					this.onSendMessage({data: {focus: this.application.device.type !== DeviceType.mobile}});
					this.storedMessage = '';
				}
				if (this.storedFile)
				{
					this.onTextareaFileSelected();
					this.storedFile = '';
				}
			}
			else if (this.widget.common.showForm === FormType.none)
			{
				EventEmitter.emit(EventType.textarea.setFocus);
			}
		},
		disagreeConsentWidow()
		{
			this.$store.commit('widget/common', {showForm : FormType.none});
			this.$store.commit('widget/common', {showConsent : false});

			this.getApplication().sendConsentDecision(false);

			if (this.storedMessage)
			{
				EventEmitter.emit(EventType.textarea.insertText, {
					text: this.storedMessage,
					focus: this.application.device.type !== DeviceType.mobile
				});
				this.storedMessage = '';
			}
			if (this.storedFile)
			{
				this.storedFile = '';
			}

			if (this.application.device.type !== DeviceType.mobile)
			{
				EventEmitter.emit(EventType.textarea.setFocus);
			}
		},
		logEvent(name, ...params)
		{
			Logger.info(name, ...params);
		},
		onCreated()
		{
			if(Utils.device.isMobile())
			{
				let viewPortMetaSiteNode = Array.from(
					document.head.getElementsByTagName('meta')
				).filter(element => element.name === 'viewport')[0];

				if (viewPortMetaSiteNode)
				{
					this.viewPortMetaSiteNode = viewPortMetaSiteNode;
					document.head.removeChild(this.viewPortMetaSiteNode);
				}
				else
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
				}

				if (!this.viewPortMetaWidgetNode)
				{
					this.viewPortMetaWidgetNode = document.createElement('meta');
					this.viewPortMetaWidgetNode.setAttribute('name', 'viewport');
					this.viewPortMetaWidgetNode.setAttribute('content', 'width=device-width, initial-scale=1.0, user-scalable=0');
					document.head.appendChild(this.viewPortMetaWidgetNode);
				}

				document.body.classList.add('bx-livechat-mobile-state');

				if (Utils.browser.isSafariBased())
				{
					document.body.classList.add('bx-livechat-mobile-safari-based');
				}

				setTimeout(() => {
					this.$store.dispatch('widget/show');
				}, 50);
			}
			else
			{
				this.$store.dispatch('widget/show').then(() => {
					this.widgetCurrentHeight = this.widgetBaseSizes.height;
					this.widgetCurrentWidth = this.widgetBaseSizes.width;
					this.getAvailableSpace();

					this.widgetCurrentHeight = this.widget.common.widgetHeight || this.widgetCurrentHeight;
					this.widgetCurrentWidth = this.widget.common.widgetWidth || this.widgetCurrentWidth;
				});
			}

			this.textareaHeight = this.widget.common.textareaHeight || this.textareaHeight;

			this.$store.commit('files/initCollection', {chatId: this.getApplication().getChatId()});
			this.$store.commit('messages/initCollection', {chatId: this.getApplication().getChatId()});
			this.$store.commit('dialogues/initCollection', {dialogId: this.getApplication().getDialogId(), fields: {
				entityType: 'LIVECHAT',
				type: 'livechat'
			}});
		},
		onBeforeClose()
		{
			if(Utils.device.isMobile())
			{
				document.body.classList.remove('bx-livechat-mobile-state');

				if (Utils.browser.isSafariBased())
				{
					document.body.classList.remove('bx-livechat-mobile-safari-based');
				}

				if (this.viewPortMetaWidgetNode)
				{
					document.head.removeChild(this.viewPortMetaWidgetNode);
					this.viewPortMetaWidgetNode = null;
				}

				if (this.viewPortMetaSiteNode)
				{
					document.head.appendChild(this.viewPortMetaSiteNode);
					this.viewPortMetaSiteNode = null;
				}
			}
		},
		onAfterClose()
		{
			this.getApplication().close();
		},
		onRequestShowForm({data: event})
		{
			clearTimeout(this.showFormTimeout);
			if (event.type === FormType.like)
			{
				if (event.delayed)
				{
					this.showFormTimeout = setTimeout(() => {
						this.showLikeForm();
					}, 5000);
				}
				else
				{
					this.showLikeForm();
				}
			}
		},
		onDialogRequestHistory(event)
		{
			this.getApplication().getDialogHistory(event.lastId);
		},
		onDialogRequestUnread(event)
		{
			this.getApplication().getDialogUnread(event.lastId);
		},
		onClickOnUserName({data: event})
		{
			// TODO name push to auto-replace mention holder - User Name -> [USER=274]User Name[/USER]
			EventEmitter.emit(EventType.textarea.insertText, {text: event.user.name + ', '});
		},
		onClickOnUploadCancel({data: event})
		{
			this.getApplication().cancelUploadFile(event.file.id);
		},
		onClickOnKeyboardButton({data: event})
		{
			this.getApplication().execMessageKeyboardCommand(event);
		},
		onClickOnCommand({data: event})
		{
			if (event.type === 'put')
			{
				EventEmitter.emit(EventType.textarea.insertText, {text: event.value + ' '});
			}
			else if (event.type === 'send')
			{
				this.getApplication().addMessage(event.value);
			}
			else
			{
				Logger.warn('Unprocessed command', event);
			}
		},
		onClickOnMessageMenu({data: event})
		{
			Logger.warn('Message menu:', event);
		},
		onClickOnMessageRetry({data: event})
		{
			Logger.warn('Message retry:', event);
			this.getApplication().retrySendMessage(event.message);
		},
		onReadMessage({data: event})
		{
			this.getApplication().readMessage(event.id);
		},
		onSetMessageReaction({data: event})
		{
			this.getApplication().reactMessage(event.message.id, event.reaction);
		},
		onClickOnDialog({data: event})
		{
			if (this.widget.common.showForm !== FormType.none)
			{
				this.$store.commit('widget/common', {showForm: FormType.none});
			}
		},
		onTextareaKeyUp({data: event})
		{
			if (
				this.widget.common.watchTyping
				&& this.widget.dialog.sessionId
				&& !this.widget.dialog.sessionClose
				&& this.widget.dialog.operator.id
				&& this.widget.dialog.operatorChatId
				&& this.$Bitrix.PullClient.get().isPublishingEnabled()
			)
			{
				let infoString = md5(
					this.widget.dialog.sessionId
					+ '/' + this.application.dialog.chatId
					+ '/' + this.widget.user.id
				);
				this.$Bitrix.PullClient.get().sendMessage(
					[this.widget.dialog.operator.id],
					'imopenlines',
					'linesMessageWrite',
					{
						text: event.text,
						infoString,
						operatorChatId: this.widget.dialog.operatorChatId
					}
				);
			}
		},
		onTextareaFocus({data: event})
		{
			if (
				this.widget.common.copyright &&
				this.application.device.type === DeviceType.mobile
			)
			{
				this.widget.common.copyright = false;
			}
			if (Utils.device.isMobile())
			{
				clearTimeout(this.onTextareaFocusScrollTimeout);
				this.onTextareaFocusScrollTimeout = setTimeout(() => {
					document.addEventListener('scroll', this.onWindowScroll);
				}, 1000);
			}
			this.textareaFocused = true;
		},
		onTextareaBlur({data: event})
		{
			if (!this.widget.common.copyright && this.widget.common.copyright !== this.getApplication().copyright)
			{
				this.widget.common.copyright = this.getApplication().copyright;
				this.$nextTick(() => {
					EventEmitter.emit(EventType.dialog.scrollToBottom, {chatId: this.chatId, force: true});
				});
			}
			if (Utils.device.isMobile())
			{
				clearTimeout(this.onTextareaFocusScrollTimeout);
				document.removeEventListener('scroll', this.onWindowScroll);
			}

			this.textareaFocused = false;
		},
		onTextareaStartDrag(event)
		{
			if (this.textareaDrag)
			{
				return;
			}

			Logger.log('Livechat: textarea drag started');

			this.textareaDrag = true;

			event = event.changedTouches ? event.changedTouches[0] : event;

			this.textareaDragCursorStartPoint = event.clientY;
			this.textareaDragHeightStartPoint = this.textareaHeight;

			this.onTextareaDragEventAdd();

			EventEmitter.emit(EventType.textarea.setBlur, true);
		},
		onTextareaContinueDrag(event)
		{
			if (!this.textareaDrag)
			{
				return;
			}

			event = event.changedTouches ? event.changedTouches[0] : event;

			this.textareaDragCursorControlPoint = event.clientY;

			let textareaHeight = Math.max(
				Math.min(this.textareaDragHeightStartPoint + this.textareaDragCursorStartPoint - this.textareaDragCursorControlPoint, this.textareaMaximumHeight)
			, this.textareaMinimumHeight);

			Logger.log('Livechat: textarea drag', 'new: '+textareaHeight, 'curr: '+this.textareaHeight);

			if (this.textareaHeight !== textareaHeight)
			{
				this.textareaHeight = textareaHeight;
			}
		},
		onTextareaStopDrag()
		{
			if (!this.textareaDrag)
			{
				return;
			}

			Logger.log('Livechat: textarea drag ended');

			this.textareaDrag = false;

			this.onTextareaDragEventRemove();

			this.$store.commit('widget/common', {textareaHeight: this.textareaHeight});
			EventEmitter.emit(EventType.dialog.scrollToBottom, {chatId: this.chatId, force: true});
		},
		onTextareaDragEventAdd()
		{
			document.addEventListener('mousemove', this.onTextareaContinueDrag);
			document.addEventListener('touchmove', this.onTextareaContinueDrag);
			document.addEventListener('touchend', this.onTextareaStopDrag);
			document.addEventListener('mouseup', this.onTextareaStopDrag);
			document.addEventListener('mouseleave', this.onTextareaStopDrag);
		},
		onTextareaDragEventRemove()
		{
			document.removeEventListener('mousemove', this.onTextareaContinueDrag);
			document.removeEventListener('touchmove', this.onTextareaContinueDrag);
			document.removeEventListener('touchend', this.onTextareaStopDrag);
			document.removeEventListener('mouseup', this.onTextareaStopDrag);
			document.removeEventListener('mouseleave', this.onTextareaStopDrag);
		},
		onTextareaFileSelected({data: event} = {})
		{
			let fileInputEvent = null;
			if (event && event.fileChangeEvent && event.fileChangeEvent.target.files.length > 0)
			{
				fileInputEvent = event.fileChangeEvent;
			}
			else
			{
				fileInputEvent =  this.storedFile;
			}

			if (!fileInputEvent)
			{
				return false;
			}

			if (!this.widget.dialog.userConsent && this.widget.common.consentUrl)
			{
				this.storedFile = event.fileChangeEvent;
				this.showConsentWidow();

				return false;
			}

			this.getApplication().uploadFile(fileInputEvent);
		},
		onTextareaAppButtonClick({data: event})
		{
			if (event.appId === FormType.smile)
			{
				if (this.widget.common.showForm === FormType.smile)
				{
					this.$store.commit('widget/common', {showForm: FormType.none});
				}
				else
				{
					this.$store.commit('widget/common', {showForm: FormType.smile});
				}
			}
			else
			{
				EventEmitter.emit(EventType.textarea.setFocus);
			}
		},
		onTextareaEdit({data: event})
		{
			this.logEvent('edit message', event);
		},
		onPullRequestConfig(event)
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
			if (this.widgetDrag)
			{
				return;
			}

			this.widgetDrag = true;

			event = event.changedTouches ? event.changedTouches[0] : event;

			this.widgetDragCursorStartPointY = event.clientY;
			this.widgetDragCursorStartPointX = event.clientX;
			this.widgetDragHeightStartPoint = this.widgetCurrentHeight;
			this.widgetDragWidthStartPoint = this.widgetCurrentWidth;

			this.onWidgetDragEventAdd();
		},
		onWidgetContinueDrag(event)
		{
			if (!this.widgetDrag)
			{
				return;
			}

			event = event.changedTouches ? event.changedTouches[0] : event;

			this.widgetDragCursorControlPointY = event.clientY;
			this.widgetDragCursorControlPointX = event.clientX;

			let widgetHeight = 0;

			if (this.isBottomLocation)
			{
				widgetHeight = Math.max(
					Math.min(this.widgetDragHeightStartPoint + this.widgetDragCursorStartPointY - this.widgetDragCursorControlPointY, this.widgetAvailableHeight),
					this.widgetMinimumHeight
				);
			}
			else
			{
				widgetHeight = Math.max(
					Math.min(this.widgetDragHeightStartPoint - this.widgetDragCursorStartPointY + this.widgetDragCursorControlPointY, this.widgetAvailableHeight),
					this.widgetMinimumHeight
				);
			}

			let widgetWidth = 0;
			if (this.isLeftLocation)
			{
				widgetWidth = Math.max(
					Math.min(this.widgetDragWidthStartPoint - this.widgetDragCursorStartPointX + this.widgetDragCursorControlPointX, this.widgetAvailableWidth),
					this.widgetMinimumWidth
				);
			}
			else
			{
				widgetWidth = Math.max(
					Math.min(this.widgetDragWidthStartPoint + this.widgetDragCursorStartPointX - this.widgetDragCursorControlPointX, this.widgetAvailableWidth),
					this.widgetMinimumWidth
				);
			}

			if (this.widgetCurrentHeight !== widgetHeight)
			{
				this.widgetCurrentHeight = widgetHeight;
			}

			if (this.widgetCurrentWidth !== widgetWidth)
			{
				this.widgetCurrentWidth = widgetWidth;
			}
		},
		onWidgetStopDrag()
		{
			if (!this.widgetDrag)
			{
				return;
			}

			this.widgetDrag = false;

			this.onWidgetDragEventRemove();

			this.$store.commit('widget/common', {widgetHeight: this.widgetCurrentHeight, widgetWidth: this.widgetCurrentWidth});
		},
		onWidgetDragEventAdd()
		{
			document.addEventListener('mousemove', this.onWidgetContinueDrag);
			document.addEventListener('mouseup', this.onWidgetStopDrag);
			document.addEventListener('mouseleave', this.onWidgetStopDrag);
		},
		onWidgetDragEventRemove()
		{
			document.removeEventListener('mousemove', this.onWidgetContinueDrag);
			document.removeEventListener('mouseup', this.onWidgetStopDrag);
			document.removeEventListener('mouseleave', this.onWidgetStopDrag);
		},
		onWindowKeyDown(event)
		{
			if (event.keyCode === 27)
			{
				if (this.widget.common.showForm !== FormType.none)
				{
					this.$store.commit('widget/common', {showForm: FormType.none});
				}
				else if (this.widget.common.showConsent)
				{
					this.disagreeConsentWidow();
				}
				else
				{
					this.close();
				}

				event.preventDefault();
				event.stopPropagation();

				EventEmitter.emit(EventType.textarea.setFocus);
			}
		},
		onWindowScroll(event)
		{
			clearTimeout(this.onWindowScrollTimeout);
			this.onWindowScrollTimeout = setTimeout(() => {
				EventEmitter.emit(EventType.textarea.setBlur, true);
			}, 50);
		},
		onWelcomeFormSendSuccess()
		{
			this.welcomeFormFilled = true;
		},
		onWelcomeFormSendError(error)
		{
			console.error('onWelcomeFormSendError', error);
			this.welcomeFormFilled = true;
		}
	},
	// language=Vue
	template: `
		<transition enter-active-class="bx-livechat-show" leave-active-class="bx-livechat-close" @after-leave="onAfterClose">
			<div :class="widgetClassName" v-if="widget.common.showed" :style="{height: widgetHeightStyle, width: widgetWidthStyle, userSelect: userSelectStyle}" ref="widgetWrapper">
				<div class="bx-livechat-box">
					<div v-if="isBottomLocation" class="bx-livechat-widget-resize-handle" @mousedown="onWidgetStartDrag"></div>
					<bx-livechat-head :isWidgetDisabled="widgetMobileDisabled" @like="showLikeForm" @openMenu="onOpenMenu" @close="close"/>
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
								<template v-if="showMessageDialog">
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
						<div v-if="showTextarea" class="bx-livechat-textarea" :style="[textareaHeightStyle, textareaBottomMargin]" ref="textarea">
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
						<bx-livechat-form-consent @agree="agreeConsentWidow" @disagree="disagreeConsentWidow"/>
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
								<div v-if="!isBottomLocation" class="bx-livechat-widget-resize-handle" @mousedown="onWidgetStartDrag"></div>
							</div>
						</template>
					</template>
				</div>
			</div>
		</transition>
	`
});
