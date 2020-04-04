/**
 * Bitrix im dialog mobile
 * Registry class
 *
 * @package bitrix
 * @subpackage mobile
 * @copyright 2001-2019 Bitrix
 */

// vue components
import 'pull.components.status';
import 'im.component.dialog';

// dialog files
import "./dialog.css";
import {Dialog} from "./dialog";

// widget components

import "./component/bx-messenger";
import "./component/bx-messenger-body-error";
import "./component/bx-messenger-body-loading";
import "./component/bx-messenger-body-empty";

export {Dialog}