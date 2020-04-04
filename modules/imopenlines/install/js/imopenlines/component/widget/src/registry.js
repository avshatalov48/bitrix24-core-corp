/**
 * Bitrix OpenLines widget
 * Widget component & controller
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

// core

import 'main.polyfill.customevent';

// vue components
import 'pull.components.status';
import 'ui.vue.components.smiles';
import 'im.component.dialog';
import 'im.component.textarea';
import 'imopenlines.component.message';

// widget files
import "./widget.css";
import {VoteType, LocationStyle, SubscriptionType} from "./const";

// widget utils
import {Cookie} from "./utils/cookie";
import {WidgetPublicManager} from "./public";

// widget components

import "./component/bx-livechat";
import "./component/bx-livechat-body-error";
import "./component/bx-livechat-body-head";
import "./component/bx-livechat-body-loading";
import "./component/bx-livechat-body-operators";
import "./component/bx-livechat-body-orientation-disabled";
import "./component/bx-livechat-form-consent";
import "./component/bx-livechat-form-history";
import "./component/bx-livechat-form-offline";
import "./component/bx-livechat-form-vote";
import "./component/bx-livechat-form-welcome";
import "./component/bx-livechat-smiles";
import "./component/bx-livechat-footer";


BX.LiveChatWidget = WidgetPublicManager;
BX.LiveChatWidget.VoteType = VoteType;
BX.LiveChatWidget.SubscriptionType = SubscriptionType;
BX.LiveChatWidget.LocationStyle = LocationStyle;
BX.LiveChatWidget.Cookie = Cookie;

window.dispatchEvent(new CustomEvent('onBitrixLiveChatSourceLoaded', {detail: {}}));