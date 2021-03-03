/**
 * Bitrix OpenLines widget
 * LiveChat base component (Vue component)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import {Vue} from "ui.vue";
import {Vuex} from "ui.vue.vuex";
import {Utils} from "im.lib.utils";
import {Logger} from "im.lib.logger";
import {FormType, VoteType, LocationStyle, LocationType, LanguageType} from "../const";
import {DeviceType, DeviceOrientation, EventType} from "im.const";
import {md5} from "main.md5";

/**
 * @notice Do not mutate or clone this component! It is under development.
 */

Vue.component('bx-livechat',
{
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
		}
	},
	created()
	{
		this.onCreated();

		document.addEventListener('keydown', this.onWindowKeyDown);
		if(!Utils.device.isMobile() && !this.widget.common.pageMode)
		{
			window.addEventListener('resize', this.getAvailableSpaceFunc = Utils.throttle(this.getAvailableSpace, 50));
		}
		this.$root.$on('requestShowForm', this.onRequestShowForm);
	},
	beforeDestroy()
	{
		document.removeEventListener('keydown', this.onWindowKeyDown);
		if(!Utils.device.isMobile() && !this.widget.common.pageMode)
		{
			window.removeEventListener('resize', this.getAvailableSpaceFunc);
		}
		this.$root.$off('requestShowForm', this.onRequestShowForm);

		this.onTextareaDragEventRemove();
	},
	computed:
	{
		FormType: () => FormType,
		VoteType: () => VoteType,
		DeviceType: () => DeviceType,
		EventType: () => EventType,

		textareaHeightStyle(state)
		{
			return {flex: '0 0 '+this.textareaHeight+'px'};
		},
		textAreaBottomMargin()
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
			return Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
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
		quotePanelData()
		{
			let result = {
				id: 0,
				title: '',
				description: '',
				color: ''
			};
			if (!this.dialog.quoteId)
			{
				return result;
			}

			let message = this.$store.getters['messages/getMessage'](this.dialog.chatId, this.dialog.quoteId);
			if (!message)
			{
				return result;
			}

			let user = this.$store.getters['users/get'](message.authorId);

			result = {
				id: this.dialog.quoteId,
				title: user? user.name: '',
				color: user? user.color: '',
				description: message.textConverted? message.textConverted: this.localize.BX_LIVECHAT_FILE_MESSAGE
			};

			return result;
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
		}
	},
	methods:
	{
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
		showWelcomeForm()
		{
			clearTimeout(this.showFormTimeout);
			this.$store.commit('widget/common', {showForm: FormType.welcome});
		},
		showOfflineForm()
		{
			clearTimeout(this.showFormTimeout);

			if (this.widget.dialog.showForm !== FormType.welcome)
			{
				this.$store.commit('widget/common', {showForm: FormType.offline});
			}
		},
		showHistoryForm()
		{
			clearTimeout(this.showFormTimeout);
			this.$store.commit('widget/common', {showForm: FormType.history});
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

			this.$root.$bitrixApplication.sendConsentDecision(true);

			if (this.storedMessage || this.storedFile)
			{
				if (this.storedMessage)
				{
					this.onTextareaSend({focus: this.application.device.type !== DeviceType.mobile});
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
				this.$root.$emit(EventType.textarea.focus);
			}
		},
		disagreeConsentWidow()
		{
			this.$store.commit('widget/common', {showForm : FormType.none});
			this.$store.commit('widget/common', {showConsent : false});

			this.$root.$bitrixApplication.sendConsentDecision(false);

			if (this.storedMessage)
			{
				this.$root.$emit(EventType.textarea.insertText, {
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
				this.$root.$emit(EventType.textarea.focus);
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

			this.$store.commit('files/initCollection', {chatId: this.$root.$bitrixApplication.getChatId()});
			this.$store.commit('messages/initCollection', {chatId: this.$root.$bitrixApplication.getChatId()});
			this.$store.commit('dialogues/initCollection', {dialogId: this.$root.$bitrixApplication.getDialogId(), fields: {
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
			this.$root.$bitrixApplication.close();
		},
		onRequestShowForm(event)
		{
			clearTimeout(this.showFormTimeout);
			if (event.type === FormType.welcome)
			{
				if (event.delayed)
				{
					this.showFormTimeout = setTimeout(() => {
						this.showWelcomeForm();
					}, 5000);
				}
				else
				{
					this.showWelcomeForm();
				}
			}
			else if (event.type === FormType.offline)
			{
				if (event.delayed)
				{
					this.showFormTimeout = setTimeout(() => {
						this.showOfflineForm();
					}, 3000);
				}
				else
				{
					this.showOfflineForm();
				}
			}
			else if (event.type === FormType.like)
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
			this.$root.$bitrixApplication.getDialogHistory(event.lastId);
		},
		onDialogRequestUnread(event)
		{
			this.$root.$bitrixApplication.getDialogUnread(event.lastId);
		},
		onDialogMessageClickByUserName(event)
		{
			// TODO name push to auto-replace mention holder - User Name -> [USER=274]User Name[/USER]
			this.$root.$emit(EventType.textarea.insertText, {text: event.user.name+' '});
		},
		onDialogMessageClickByUploadCancel(event)
		{
			this.$root.$bitrixApplication.cancelUploadFile(event.file.id);
		},
		onDialogMessageClickByKeyboardButton(event)
		{
			this.$root.$bitrixApplication.execMessageKeyboardCommand(event);
		},
		onDialogMessageClickByCommand(event)
		{
			if (event.type === 'put')
			{
				this.$root.$emit(EventType.textarea.insertText, {text: event.value+' '});
			}
			else if (event.type === 'send')
			{
				this.$root.$bitrixApplication.addMessage(event.value);
			}
			else
			{
				Logger.warn('Unprocessed command', event);
			}
		},
		onDialogMessageMenuClick(event)
		{
			Logger.warn('Message menu:', event);
		},
		onDialogMessageRetryClick(event)
		{
			Logger.warn('Message retry:', event);
			this.$root.$bitrixApplication.retrySendMessage(event.message);
		},
		onDialogReadMessage(event)
		{
			this.$root.$bitrixApplication.readMessage(event.id);
		},
		onDialogQuoteMessage(event)
		{
			this.$root.$bitrixApplication.quoteMessage(event.message.id);
		},
		onDialogMessageReactionSet(event)
		{
			this.$root.$bitrixApplication.reactMessage(event.message.id, event.reaction);
		},
		onDialogClick(event)
		{
			if (this.widget.common.showForm !== FormType.none)
			{
				this.$store.commit('widget/common', {showForm: FormType.none});
			}
		},
		onTextareaKeyUp(event)
		{
			if (
				this.widget.common.watchTyping
				&& this.widget.dialog.sessionId
				&& !this.widget.dialog.sessionClose
				&& this.widget.dialog.operator.id
				&& this.widget.dialog.operatorChatId
				&& this.$root.$bitrixPullClient.isPublishingEnabled()
			)
			{
				let infoString = md5(
					this.widget.dialog.sessionId
					+ '/' + this.application.dialog.chatId
					+ '/' + this.widget.user.id
				);
				this.$root.$bitrixPullClient.sendMessage(
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
		onTextareaSend(event)
		{
			event.focus = event.focus !== false;

			if (this.widget.common.showForm === FormType.smile)
			{
				this.$store.commit('widget/common', {showForm: FormType.none});
			}

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
			this.$root.$bitrixApplication.addMessage(event.text);

			if (event.focus)
			{
				this.$root.$emit(EventType.textarea.focus);
			}

			return true;
		},
		onTextareaWrites(event)
		{
			this.$root.$bitrixController.application.startWriting();
		},
		onTextareaFocus(event)
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
		onTextareaBlur(event)
		{
			if (!this.widget.common.copyright && this.widget.common.copyright !== this.$root.$bitrixApplication.copyright)
			{
				this.widget.common.copyright = this.$root.$bitrixApplication.copyright;
				this.$nextTick(() => {
					this.$root.$emit(EventType.dialog.scrollToBottom, {force: true});
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

			this.$root.$emit(EventType.textarea.blur, true);
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
			this.$root.$emit(EventType.dialog.scrollToBottom, {force: true});
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
		onTextareaFileSelected(event)
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

			this.$root.$bitrixApplication.uploadFile(fileInputEvent);
		},
		onTextareaAppButtonClick(event)
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
				this.$root.$emit(EventType.textarea.focus);
			}
		},
		onPullRequestConfig(event)
		{
			this.$root.$bitrixApplication.recoverPullConnection();
		},
		onSmilesSelectSmile(event)
		{
			this.$root.$emit(EventType.textarea.insertText, {text: event.text});
		},
		onSmilesSelectSet()
		{
			this.$root.$emit(EventType.textarea.focus);
		},
		onQuotePanelClose()
		{
			this.$root.$bitrixApplication.quoteMessageClear();
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

				this.$root.$emit(EventType.textarea.focus);
			}
		},
		onWindowScroll(event)
		{
			clearTimeout(this.onWindowScrollTimeout);
			this.onWindowScrollTimeout = setTimeout(() => {
				this.$root.$emit(EventType.textarea.blur, true);
			}, 50);
		},
	},
	template: `
		<transition enter-active-class="bx-livechat-show" leave-active-class="bx-livechat-close" @after-leave="onAfterClose">
			<div :class="widgetClassName" v-if="widget.common.showed" :style="{height: widgetHeightStyle, width: widgetWidthStyle, userSelect: userSelectStyle}" ref="widgetWrapper">
				<div class="bx-livechat-box">
					<div v-if="isBottomLocation" class="bx-livechat-widget-resize-handle" @mousedown="onWidgetStartDrag"></div>
					<bx-livechat-head :isWidgetDisabled="widgetMobileDisabled" @like="showLikeForm" @history="showHistoryForm" @close="close"/>
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
						<template v-if="!widget.common.dialogStart">
							<div class="bx-livechat-body" key="welcome-body">
								<bx-livechat-body-operators/>
								<keep-alive include="bx-livechat-smiles">
									<template v-if="widget.common.showForm == FormType.smile">
										<bx-livechat-smiles @selectSmile="onSmilesSelectSmile" @selectSet="onSmilesSelectSet"/>	
									</template>
								</keep-alive>
							</div>
						</template>
						<template v-else-if="widget.common.dialogStart">
							<bx-pull-component-status :canReconnect="true" @reconnect="onPullRequestConfig"/>
							<div :class="['bx-livechat-body', {'bx-livechat-body-with-message': showMessageDialog}]" key="with-message">
								<template v-if="showMessageDialog">
									<div class="bx-livechat-dialog">
										<bx-im-view-dialog
											:userId="application.common.userId" 
											:dialogId="application.dialog.dialogId"
											:chatId="application.dialog.chatId"
											:messageLimit="application.dialog.messageLimit"
											:messageExtraCount="application.dialog.messageExtraCount"
											:enableReactions="true"
											:enableDateActions="false"
											:enableCreateContent="false"
											:enableGestureQuote="true"
											:enableGestureMenu="true"
											:showMessageAvatar="false"
											:showMessageMenu="false"
											:listenEventScrollToBottom="EventType.dialog.scrollToBottom"
											:listenEventRequestHistory="EventType.dialog.requestHistoryResult"
											:listenEventRequestUnread="EventType.dialog.requestUnreadResult"
											@readMessage="onDialogReadMessage"
											@requestHistory="onDialogRequestHistory"
											@requestUnread="onDialogRequestUnread"
											@quoteMessage="onDialogQuoteMessage"
											@clickByCommand="onDialogMessageClickByCommand"
											@clickByUserName="onDialogMessageClickByUserName"
											@clickByUploadCancel="onDialogMessageClickByUploadCancel"
											@clickByKeyboardButton="onDialogMessageClickByKeyboardButton"
											@clickByMessageMenu="onDialogMessageMenuClick"
											@clickByMessageRetry="onDialogMessageRetryClick"
											@setMessageReaction="onDialogMessageReactionSet"
											@click="onDialogClick"
										 />
									</div>	 
								</template>
								<template v-else>
									<bx-livechat-body-loading/>
								</template>
								
								<bx-im-view-quote-panel :id="quotePanelData.id" :title="quotePanelData.title" :description="quotePanelData.description" :color="quotePanelData.color" @close="onQuotePanelClose"/>
								
								<keep-alive include="bx-livechat-smiles">
									<template v-if="widget.common.showForm == FormType.like && widget.common.vote.enable">
										<bx-livechat-form-vote/>
									</template>
									<template v-else-if="widget.common.showForm == FormType.welcome">
										<bx-livechat-form-welcome/>	
									</template>
									<template v-else-if="widget.common.showForm == FormType.offline">
										<bx-livechat-form-offline/>	
									</template>
									<template v-else-if="widget.common.showForm == FormType.history">
										<bx-livechat-form-history/>	
									</template>
									<template v-else-if="widget.common.showForm == FormType.smile">
										<bx-livechat-smiles @selectSmile="onSmilesSelectSmile" @selectSet="onSmilesSelectSet"/>	
									</template>
								</keep-alive>
							</div>
						</template>	
						<div class="bx-livechat-textarea" :style="[textareaHeightStyle, textAreaBottomMargin]" ref="textarea">
							<div class="bx-livechat-textarea-resize-handle" @mousedown="onTextareaStartDrag" @touchstart="onTextareaStartDrag"></div>
							<bx-im-view-textarea
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
								:listenEventInsertText="EventType.textarea.insertText"
								:listenEventFocus="EventType.textarea.focus"
								:listenEventBlur="EventType.textarea.blur"
								@writes="onTextareaWrites" 
								@send="onTextareaSend" 
								@focus="onTextareaFocus" 
								@blur="onTextareaBlur"
								@keyup="onTextareaKeyUp" 
								@edit="logEvent('edit message', $event)"
								@fileSelected="onTextareaFileSelected"
								@appButtonClick="onTextareaAppButtonClick"
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