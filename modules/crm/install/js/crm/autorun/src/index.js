import { Reflection } from 'main.core';
import { BatchAssignmentManager } from './managers/batch-assignment-manager';
import { BatchConversionManager } from './managers/batch-conversion-manager';
import { BatchDeletionManager } from './managers/batch-deletion-manager';
import { BatchExclusionManager } from './managers/batch-exclusion-manager';
import { BatchObserversManager } from './managers/batch-observers-manager';
import { BatchRefreshAccountingDataManager } from './managers/batch-refresh-accounting-data-manager';
import { BatchRestartAutomationManager } from './managers/batch-restart-automation-manager';
import { BatchSetCategoryManager } from './managers/batch-set-category-manager';
import { BatchSetExportManager } from './managers/batch-set-export-manager';
import { BatchSetOpenedManager } from './managers/batch-set-opened-manager';
import { BatchSetStageManager } from './managers/batch-set-stage-manager';
import { BatchWhatsappMessageManager } from './managers/batch-whatsapp-message-manager';
import { ProcessPanel } from './process/process-panel';
import { ProcessRegistry } from './process/process-registry';
import { ProcessState } from './process/process-state';
import { Processor } from './process/processor';
import { SummaryPanel } from './process/summary-panel';
import { ProgressBarRepository } from './progress-bar-repository';

// region Compatibility
const bxNamespace = Reflection.namespace('BX');

bxNamespace.AutorunProcessManager = Processor;
bxNamespace.AutorunProcessPanel = ProcessPanel;
bxNamespace.AutoRunProcessState = ProcessState;

const bxCrmNamespace = Reflection.namespace('BX.Crm');

bxCrmNamespace.ProcessSummaryPanel = SummaryPanel;
bxCrmNamespace.BatchDeletionManager = BatchDeletionManager;
bxCrmNamespace.BatchConversionManager = BatchConversionManager;
// endregion

export {
	ProcessRegistry,
	Processor,
	ProcessPanel,
	ProcessState,
	SummaryPanel,
	ProgressBarRepository,
	BatchAssignmentManager,
	BatchDeletionManager,
	BatchConversionManager,
	BatchSetCategoryManager,
	BatchSetStageManager,
	BatchSetOpenedManager,
	BatchSetExportManager,
	BatchExclusionManager,
	BatchWhatsappMessageManager,
	BatchRefreshAccountingDataManager,
	BatchRestartAutomationManager,
	BatchObserversManager,
};
