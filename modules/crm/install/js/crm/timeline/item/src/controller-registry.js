import ControllerManager from "./controller-manager";

import {Activity} from './controllers/activity';
import {CommonContentBlocks} from './controllers/common-content-blocks';
import {Modification} from './controllers/modification';
import {OpenLines} from './controllers/openlines';
import {SignDocument} from './controllers/sign-document';
import {Document} from './controllers/document';
import {Call} from './controllers/call';
import {ToDo} from './controllers/todo';
import {Helpdesk} from './controllers/helpdesk';
import {DealProductList} from './controllers/deal-product-list';

ControllerManager.registerController(Activity);
ControllerManager.registerController(CommonContentBlocks);
ControllerManager.registerController(OpenLines);
ControllerManager.registerController(Modification);
ControllerManager.registerController(SignDocument);
ControllerManager.registerController(Document);
ControllerManager.registerController(Call);
ControllerManager.registerController(ToDo);
ControllerManager.registerController(Helpdesk);
ControllerManager.registerController(DealProductList);
