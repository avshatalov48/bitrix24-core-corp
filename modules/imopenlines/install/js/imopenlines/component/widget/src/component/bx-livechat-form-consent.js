/**
 * Bitrix OpenLines widget
 * Form consent component (Vue component)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import {BitrixVue} from "ui.vue";
import {Vuex} from "ui.vue.vuex";
import { EventEmitter } from "main.core.events";
import { WidgetEventType } from "../const";

BitrixVue.component('bx-livechat-form-consent',
{
	computed:
	{
		...Vuex.mapState({
			widget: state => state.widget,
		})
	},
	methods:
	{
		agree()
		{
			EventEmitter.emit(WidgetEventType.acceptConsent);
		},
		disagree()
		{
			EventEmitter.emit(WidgetEventType.declineConsent);
		},
		onShow(element, done)
		{
			element.classList.add('bx-livechat-consent-window-show');
			done();
		},
		onHide(element, done)
		{
			element.classList.remove('bx-livechat-consent-window-show');
			element.classList.add('bx-livechat-consent-window-close');
			setTimeout(function() {
				done();
			}, 400);
		},
		onKeyDown(event)
		{
			if (event.keyCode == 9)
			{
				if (event.target === this.$refs.iframe)
				{
					if (event.shiftKey)
					{
						this.$refs.cancel.focus();
					}
					else
					{
						this.$refs.success.focus();
					}
				}
				else if (event.target === this.$refs.success)
				{
					if (event.shiftKey)
					{
						this.$refs.iframe.focus();
					}
					else
					{
						this.$refs.cancel.focus();
					}
				}
				else if (event.target === this.$refs.cancel)
				{
					if (event.shiftKey)
					{
						this.$refs.success.focus();
					}
					else
					{
						this.$refs.iframe.focus();
					}
				}
				event.preventDefault();
			}
			else if (event.keyCode == 39 || event.keyCode == 37)
			{
				if (event.target.nextElementSibling)
				{
					event.target.nextElementSibling.focus();
				}
				else if (event.target.previousElementSibling)
				{
					event.target.previousElementSibling.focus();
				}
				event.preventDefault();
			}
		},
	},
	directives:
	{
		focus:
		{
			inserted(element, params)
			{
				element.focus();
			}
		}
	},
	template: `
		<transition @enter="onShow" @leave="onHide">
			<template v-if="widget.common.showConsent && widget.common.consentUrl">
				<div class="bx-livechat-consent-window">
					<div class="bx-livechat-consent-window-title">{{$Bitrix.Loc.getMessage('BX_LIVECHAT_CONSENT_TITLE')}}</div>
					<div class="bx-livechat-consent-window-content">
						<iframe class="bx-livechat-consent-window-content-iframe" ref="iframe" frameborder="0" marginheight="0"  marginwidth="0" allowtransparency="allow-same-origin" seamless="true" :src="widget.common.consentUrl" @keydown="onKeyDown"></iframe>
					</div>								
					<div class="bx-livechat-consent-window-btn-box">
						<button class="bx-livechat-btn bx-livechat-btn-success" ref="success" @click="agree" @keydown="onKeyDown" v-focus>{{$Bitrix.Loc.getMessage('BX_LIVECHAT_CONSENT_AGREE')}}</button>
						<button class="bx-livechat-btn bx-livechat-btn-cancel" ref="cancel" @click="disagree" @keydown="onKeyDown">{{$Bitrix.Loc.getMessage('BX_LIVECHAT_CONSENT_DISAGREE')}}</button>
					</div>
				</div>
			</template>
		</transition>
	`
});