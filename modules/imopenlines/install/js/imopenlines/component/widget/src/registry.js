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
import 'pull.component.status';
import 'ui.vue.components.smiles';
import 'im.component.dialog';
import 'im.component.textarea';
import 'im.view.quotepanel';
import 'imopenlines.component.message';
import 'imopenlines.component.form';

// widget files
import "./widget.css";
import {VoteType, LocationStyle, SubscriptionType} from "./const";

// widget utils
import {Cookie} from "im.lib.cookie";
import {WidgetPublicManager} from "./public";

// widget components
import "./component/bx-livechat";
import "./component/bx-livechat-body-error";
import "./component/bx-livechat-body-head";
import "./component/bx-livechat-body-loading";
import "./component/bx-livechat-body-operators";
import "./component/bx-livechat-dialogues-list";
import "./component/bx-livechat-body-orientation-disabled";
import "./component/bx-livechat-form-consent";
import "./component/bx-livechat-form-vote";
import "./component/bx-livechat-smiles";


BX.LiveChatWidget = WidgetPublicManager;
BX.LiveChatWidget.VoteType = VoteType;
BX.LiveChatWidget.SubscriptionType = SubscriptionType;
BX.LiveChatWidget.LocationStyle = LocationStyle;
BX.LiveChatWidget.Cookie = Cookie;

window.dispatchEvent(new CustomEvent('onBitrixLiveChatSourceLoaded', {detail: {}}));
