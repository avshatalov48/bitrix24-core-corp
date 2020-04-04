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
import {Utils} from "im.utils";
import {Logger} from "im.tools.logger";
import {FormType, VoteType, LocationStyle, LanguageType} from "../const";
import {DeviceType, DeviceOrientation} from "im.const";

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
		this.$root.$on('requestShowForm', this.onRequestShowForm);
	},
	beforeDestroy()
	{
		document.removeEventListener('keydown', this.onWindowKeyDown);
		this.$root.$off('requestShowForm', this.onRequestShowForm);

		this.onTextareaDragEventRemove();
	},
	computed:
	{
		FormType: () => FormType,
		VoteType: () => VoteType,
		DeviceType: () => DeviceType,
		textareaHeightStyle(state)
		{
			return 'flex: 0 0 '+this.textareaHeight+'px;'
		},
		localize()
		{
			return Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
		},
		widgetMobileDisabled(state)
		{
			if (state.application.device.type == DeviceType.mobile)
			{
				if (navigator.userAgent.toString().includes('iPad'))
				{
				}
				else if (state.application.device.orientation == DeviceOrientation.horizontal)
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

			if (state.application.common.languageId == LanguageType.russian)
			{
				className.push('bx-livechat-logo-ru');
			}
			else if (state.application.common.languageId == LanguageType.ukraine)
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
				&& !(state.application.device.type == DeviceType.mobile && state.application.device.orientation == DeviceOrientation.horizontal)
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
				&& this.widget.dialog.userVote != VoteType.none
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
			this.$store.commit('widget/common', {showForm: FormType.none});
		},
		showConsentWidow()
		{
			this.$store.commit('widget/common', {showConsent: true});
		},
		agreeConsentWidow()
		{
			this.$store.commit('widget/common', {showConsent: false});

			this.$root.$bitrixWidget.sendConsentDecision(true);

			if (this.storedMessage || this.storedFile)
			{
				if (this.storedMessage)
				{
					this.onTextareaSend({focus: this.application.device.type != DeviceType.mobile});
					this.storedMessage = '';
				}
				if (this.storedFile)
				{
					this.onTextareaFileSelected();
					this.storedFile = '';
				}
			}
			else if (this.widget.common.showForm == FormType.none)
			{
				this.$root.$emit('onMessengerTextareaFocus');
			}
		},
		disagreeConsentWidow()
		{
			this.$store.commit('widget/common', {showForm : FormType.none});
			this.$store.commit('widget/common', {showConsent : false});

			this.$root.$bitrixWidget.sendConsentDecision(false);

			if (this.storedMessage)
			{
				this.$root.$emit('onMessengerTextareaInsertText', {
					text: this.storedMessage,
					focus: this.application.device.type != DeviceType.mobile
				});
				this.storedMessage = '';
			}
			if (this.storedFile)
			{
				this.storedFile = '';
			}

			if (this.application.device.type != DeviceType.mobile)
			{
				this.$root.$emit('onMessengerTextareaFocus');
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
				).filter(element => element.name == 'viewport')[0];

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
					this.$store.commit('widget/common', {showed: true});
				}, 50);
			}
			else
			{
				this.$store.commit('widget/common', {showed: true});
			}

			this.textareaHeight = this.widget.common.textareaHeight || this.textareaHeight;

			this.$store.commit('files/initCollection', {chatId: this.$root.$bitrixWidget.getChatId()});
			this.$store.commit('messages/initCollection', {chatId: this.$root.$bitrixWidget.getChatId()});
			this.$store.commit('dialogues/initCollection', {dialogId: this.$root.$bitrixWidget.getDialogId(), fields: {
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
			this.$root.$bitrixWidget.close();
		},
		onRequestShowForm(event)
		{
			clearTimeout(this.showFormTimeout);
			if (event.type == FormType.welcome)
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
			else if (event.type == FormType.offline)
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
			else if (event.type == FormType.like)
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
			this.$root.$bitrixWidget.getDialogHistory(event.lastId);
		},
		onDialogRequestUnread(event)
		{
			this.$root.$bitrixWidget.getDialogUnread(event.lastId);
		},
		onDialogMessageClickByUserName(event)
		{
			// TODO name push to auto-replace mention holder - User Name -> [USER=274]User Name[/USER]
			this.$root.$emit('onMessengerTextareaInsertText', {text: event.user.name+' '});
		},
		onDialogMessageClickByCommand(event)
		{
			if (event.type === 'put')
			{
				this.$root.$emit('onMessengerTextareaInsertText', {text: event.value+' '});
			}
			else if (event.type === 'send')
			{
				this.$root.$bitrixWidget.addMessage(event.value);
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
			this.$root.$bitrixWidget.retrySendMessage(event.message);
		},
		onDialogReadMessage(event)
		{
			this.$root.$bitrixWidget.readMessage(event.id);
		},
		onDialogClick(event)
		{
			this.$store.commit('widget/common', {showForm: FormType.none});
		},
		onTextareaSend(event)
		{
			event.focus = event.focus !== false;

			if (this.widget.common.showForm == FormType.smile)
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
			this.$root.$bitrixWidget.addMessage(event.text);

			if (event.focus)
			{
				this.$root.$emit('onMessengerTextareaFocus');
			}

			return true;
		},
		onTextareaWrites(event)
		{
			this.$root.$bitrixController.startWriting();
		},
		onTextareaFocus(event)
		{
			if (
				this.widget.common.copyright &&
				this.application.device.type == DeviceType.mobile
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
			if (!this.widget.common.copyright && this.widget.common.copyright !== this.$root.$bitrixWidget.copyright)
			{
				this.widget.common.copyright = this.$root.$bitrixWidget.copyright;
				this.$nextTick(() => {
					this.$root.$emit('onMessengerDialogScrollToBottom', {force: true});
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

			this.$root.$emit('onMessengerTextareaBlur', true);
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

			if (this.textareaHeight != textareaHeight)
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
			this.$root.$emit('onMessengerDialogScrollToBottom', {force: true});
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
			let fileInput = event && event.fileInput? event.fileInput: this.storedFile;
			if (!fileInput)
			{
				return false;
			}

			if (fileInput.files[0].size > this.application.disk.maxFileSize)
			{
				// TODO change alert to correct overlay window
				alert(this.localize.BX_LIVECHAT_FILE_SIZE_EXCEEDED.replace('#LIMIT#', Math.round(this.application.disk.maxFileSize/1024/1024)));
				return false;
			}

			if (!this.widget.dialog.userConsent && this.widget.common.consentUrl)
			{
				this.storedFile = event.fileInput;
				this.showConsentWidow();

				return false;
			}

			this.$root.$bitrixWidget.addFile(fileInput);
		},
		onTextareaAppButtonClick(event)
		{
			if (event.appId == FormType.smile)
			{
				if (this.widget.common.showForm == FormType.smile)
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
				this.$root.$emit('onMessengerTextareaFocus');
			}
		},
		onPullRequestConfig(event)
		{
			this.$root.$bitrixWidget.recoverPullConnection();
		},
		onSmilesSelectSmile(event)
		{
			this.$root.$emit('onMessengerTextareaInsertText', {text: event.text});
		},
		onSmilesSelectSet()
		{
			this.$root.$emit('onMessengerTextareaFocus');
		},
		onWindowKeyDown(event)
		{
			if (event.keyCode == 27)
			{
				if (this.widget.common.showForm != FormType.none)
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

				this.$root.$emit('onMessengerTextareaFocus');
			}
		},
		onWindowScroll(event)
		{
			clearTimeout(this.onWindowScrollTimeout);
			this.onWindowScrollTimeout = setTimeout(() => {
				this.$root.$emit('onMessengerTextareaBlur', true);
			}, 50);
		},
	},
	template: `
		<transition enter-active-class="bx-livechat-show" leave-active-class="bx-livechat-close" @after-leave="onAfterClose">
			<div :class="widgetClassName" v-if="widget.common.showed">
				<div class="bx-livechat-box">
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
							<bx-pull-status :canReconnect="true" @reconnect="onPullRequestConfig"/>
							<div :class="['bx-livechat-body', {'bx-livechat-body-with-message': showMessageDialog}]" key="with-message">
								<transition name="bx-livechat-animation-upload-file">
									<template v-if="widget.common.uploadFile">
										<div class="bx-livechat-file-upload">	
											<div class="bx-livechat-file-upload-sending"></div>
											<div class="bx-livechat-file-upload-text">{{localize.BX_LIVECHAT_FILE_UPLOAD}}</div>
										</div>	
									</template>
								</transition>	
								<template v-if="showMessageDialog">
									<div class="bx-livechat-dialog">
										<bx-messenger-dialog
											:userId="application.common.userId" 
											:dialogId="application.dialog.dialogId"
											:chatId="application.dialog.chatId"
											:messageLimit="application.dialog.messageLimit"
											:enableEmotions="false"
											:enableDateActions="false"
											:enableCreateContent="false"
											:showMessageAvatar="false"
											:showMessageMenu="false"
											listenEventScrollToBottom="onMessengerDialogScrollToBottom"
											listenEventRequestHistory="onDialogRequestHistoryResult"
											listenEventRequestUnread="onDialogRequestUnreadResult"
											@readMessage="onDialogReadMessage"
											@requestHistory="onDialogRequestHistory"
											@requestUnread="onDialogRequestUnread"
											@clickByCommand="onDialogMessageClickByCommand"
											@clickByUserName="onDialogMessageClickByUserName"
											@clickByMessageMenu="onDialogMessageMenuClick"
											@clickByMessageRetry="onDialogMessageRetryClick"
											@click="onDialogClick"
										 />
									</div>	 
								</template>
								<template v-else>
									<bx-livechat-body-loading/>
								</template>
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
						<div class="bx-livechat-textarea" :style="textareaHeightStyle" ref="textarea">
							<div class="bx-livechat-textarea-resize-handle" @mousedown="onTextareaStartDrag" @touchstart="onTextareaStartDrag"></div>
							<bx-messenger-textarea
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
								listenEventInsertText="onMessengerTextareaInsertText"
								listenEventFocus="onMessengerTextareaFocus"
								listenEventBlur="onMessengerTextareaBlur"
								@writes="onTextareaWrites" 
								@send="onTextareaSend" 
								@focus="onTextareaFocus" 
								@blur="onTextareaBlur" 
								@edit="logEvent('edit message', $event)"
								@fileSelected="onTextareaFileSelected"
								@appButtonClick="onTextareaAppButtonClick"
							/>
						</div>
						<bx-livechat-form-consent @agree="agreeConsentWidow" @disagree="disagreeConsentWidow"/>
						<template v-if="widget.common.copyright">
							<bx-livechat-footer/>
						</template>
					</template>
				</div>
			</div>
		</transition>
	`
});