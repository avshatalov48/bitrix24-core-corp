import { ExecuteAssigment } from './handlers/batch-manager/execute-assigment';
import { ExecuteConversion } from './handlers/batch-manager/execute-conversion';
import { ExecuteDeletion } from './handlers/batch-manager/execute-deletion';
import { ExecuteExclusion } from './handlers/batch-manager/execute-exclusion';
import { ExecuteMerge } from './handlers/batch-manager/execute-merge';
import { ExecuteObservers } from './handlers/batch-manager/execute-observers';
import { ExecuteRefreshAccountingData } from './handlers/batch-manager/execute-refresh-accounting-data';
import { ExecuteRestartAutomation } from './handlers/batch-manager/execute-restart-automation';
import { ExecuteSetCategory } from './handlers/batch-manager/execute-set-category';
import { ExecuteSetExport } from './handlers/batch-manager/execute-set-export';
import { ExecuteSetOpened } from './handlers/batch-manager/execute-set-opened';
import { ExecuteSetStage } from './handlers/batch-manager/execute-set-stage';
import { ExecuteWhatsappMessage } from './handlers/batch-manager/execute-whatsapp-message';
import { AddItemsToCallList } from './handlers/call-list/add-items-to-call-list';
import { CreateAndStartCallList } from './handlers/call-list/create-and-start-call-list';
import { CreateCallList } from './handlers/call-list/create-call-list';
import { LoadEnumsAndEditSelected } from './handlers/load-enums-and-edit-selected';
import { OpenTaskCreationForm } from './handlers/open-task-creation-form';
import { RenderUserTagSelector } from './handlers/render-user-tag-selector';
import { RenderUserTagMultipleSelector } from './handlers/render-user-tag-multiple-selector';
import { AddItemsToSegment } from './handlers/sender/add-items-to-segment';
import { AddLetter } from './handlers/sender/add-letter';
import { Router } from './router';

// region batch processing
Router.registerHandler(ExecuteDeletion);
Router.registerHandler(ExecuteSetStage);
Router.registerHandler(ExecuteSetCategory);
Router.registerHandler(ExecuteSetOpened);
Router.registerHandler(ExecuteSetExport);
Router.registerHandler(ExecuteMerge);
Router.registerHandler(ExecuteExclusion);
Router.registerHandler(ExecuteAssigment);
Router.registerHandler(ExecuteConversion);
Router.registerHandler(ExecuteWhatsappMessage);
Router.registerHandler(ExecuteRefreshAccountingData);
Router.registerHandler(ExecuteRestartAutomation);
Router.registerHandler(ExecuteObservers);
// endregion

// region call list
Router.registerHandler(CreateCallList);
Router.registerHandler(CreateAndStartCallList);
Router.registerHandler(AddItemsToCallList);
// endregion

// region sender
Router.registerHandler(AddLetter);
Router.registerHandler(AddItemsToSegment);
// endregion

Router.registerHandler(RenderUserTagSelector);
Router.registerHandler(RenderUserTagMultipleSelector);
Router.registerHandler(OpenTaskCreationForm);
Router.registerHandler(LoadEnumsAndEditSelected);
