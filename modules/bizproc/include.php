<?

$arClasses = array(
	"CBPActivity" => "classes/general/activity.php",
	"CBPActivityCondition" => "classes/general/activitycondition.php",
	"CBPCompositeActivity" => "classes/general/compositeactivity.php",
	"CBPActivityExecutionStatus" => "classes/general/constants.php",
	"CBPActivityExecutionResult" => "classes/general/constants.php",
	"CBPWorkflowStatus" => "classes/general/constants.php",
	"CBPActivityExecutorOperationType" => "classes/general/constants.php",
	"CBPDocumentEventType" => "classes/general/constants.php",
	"CBPCanUserOperateOperation" => "classes/general/constants.php",
	"CBPSetPermissionsMode" => "classes/general/constants.php",
	"CBPTaskStatus" => "classes/general/constants.php",
	"CBPTaskUserStatus" => "classes/general/constants.php",
	"CBPTaskDelegationType" => "classes/general/constants.php",
	"CBPDocument" => "classes/general/document.php",
	"CBPDocumentService" => "classes/general/documentservice.php",
	"CBPArgumentException" => "classes/general/exception.php",
	"CBPArgumentNullException" => "classes/general/exception.php",
	"CBPArgumentOutOfRangeException" => "classes/general/exception.php",
	"CBPArgumentTypeException" => "classes/general/exception.php",
	"CBPInvalidOperationException" => "classes/general/exception.php",
	"CBPNotSupportedException" => "classes/general/exception.php",
	"CBPHelper" => "classes/general/helper.php",
	"CBPAllHistoryService" => "classes/general/historyservice.php",
	"CBPHistoryService" => "classes/general/historyservice.php",
	"CBPHistoryResult" => "classes/general/historyservice.php",
	"CBPRuntime" => "classes/general/runtime.php",
	"CBPRuntimeService" => "classes/general/runtimeservice.php",
	"CBPSchedulerService" => "classes/general/schedulerservice.php",
	"CBPAllStateService" => "classes/general/stateservice.php",
	"CBPStateService" => "classes/general/stateservice.php",
	"CBPAllTaskService" => "classes/general/taskservice.php",
	"CBPTaskService" => "classes/general/taskservice.php",
	"CBPTaskResult" => "classes/general/taskservice.php",
	"CBPAllTrackingService" => "classes/general/trackingservice.php",
	"CBPTrackingService" => "classes/general/trackingservice.php",
	"CBPTrackingType" => "classes/general/trackingservice.php",
	"CBPVirtualDocument" => "classes/general/virtualdocument.php",
	"CBPWorkflow" => "classes/general/workflow.php",
	"CBPAllWorkflowPersister" => "classes/general/workflowpersister.php",
	"CBPWorkflowPersister" => "classes/general/workflowpersister.php",
	"CAllBPWorkflowTemplateLoader" => "classes/general/workflowtemplateloader.php",
	"CBPWorkflowTemplateLoader" => "classes/general/workflowtemplateloader.php",
	"CBPWorkflowTemplateResult" => "classes/general/workflowtemplateloader.php",
	"CBPWorkflowTemplateUser" => "classes/general/workflowtemplateloader.php",
	"CBPWorkflowTemplateValidationException" => "classes/general/workflowtemplateloader.php",
	"CBPCalc" => "classes/general/calc.php",
	"CBPViewHelper" => "classes/general/viewhelper.php",
	'CBPTaskChangedStatus' => "classes/general/constants.php",
	'CBPRestActivity' => 'classes/general/restactivity.php',

	"bitrix\\bizproc\\fieldtype" => "lib/fieldtype.php",
	"bitrix\\bizproc\\restactivitytable" => "lib/restactivity.php",
	"bitrix\\bizproc\\restservice" => "lib/restservice.php",
	"bitrix\\bizproc\\workflowinstancetable" => "lib/workflowinstance.php",
	"bitrix\\bizproc\\workflowstatetable" => "lib/workflowstate.php",
	"bitrix\\bizproc\\workflowtemplatetable" => "lib/workflowtemplate.php",
	"bitrix\\bizproc\\basetype\\base" => "lib/basetype/base.php",
	"bitrix\\bizproc\\basetype\\booltype" => "lib/basetype/booltype.php",
	"bitrix\\bizproc\\basetype\\date" => "lib/basetype/date.php",
	"bitrix\\bizproc\\basetype\\datetime" => "lib/basetype/datetime.php",
	"bitrix\\bizproc\\basetype\\double" => "lib/basetype/double.php",
	"bitrix\\bizproc\\basetype\\file" => "lib/basetype/file.php",
	"bitrix\\bizproc\\basetype\\inttype" => "lib/basetype/inttype.php",
	"bitrix\\bizproc\\basetype\\select" => "lib/basetype/select.php",
	"bitrix\\bizproc\\basetype\\stringtype" => "lib/basetype/stringtype.php",
	"bitrix\\bizproc\\basetype\\text" => "lib/basetype/text.php",
	"bitrix\\bizproc\\basetype\\user" => "lib/basetype/user.php",
	"bitrix\\bizproc\\basetype\\internalselect" => "lib/basetype/internalselect.php",
);
CModule::AddAutoloadClasses("bizproc", $arClasses);

require_once __DIR__.DIRECTORY_SEPARATOR.'compatibility.php';

/*patchlimitationmutatormark1*/
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/classes/general/interface.php");

CJSCore::RegisterExt('bp_selector', array(
	'js' => '/bitrix/js/bizproc/bp_selector.js',
	'css' => '/bitrix/js/bizproc/css/bp_selector.css',
	'lang' => '/bitrix/modules/bizproc/lang/'.LANGUAGE_ID.'/install/js/bp_selector.php',
	'rel' => array('core', 'popup', 'translit'),
));

CJSCore::RegisterExt('bp_starter', array(
	'js' => '/bitrix/js/bizproc/starter.js',
	//'css' => '/bitrix/js/bizproc/css/starter.css',
	'lang' => '/bitrix/modules/bizproc/lang/'.LANGUAGE_ID.'/install/js/starter.php',
	'rel' => array('core', 'popup', 'socnetlogdest'),
));

CJSCore::RegisterExt('bp_user_selector', array(
	'js' => '/bitrix/js/bizproc/user_selector.js',
	//'css' => '/bitrix/js/bizproc/css/starter.css',
	'lang' => '/bitrix/modules/bizproc/lang/'.LANGUAGE_ID.'/install/js/user_selector.php',
	'rel' => ['core', 'popup', 'socnetlogdest', 'bp_field_type'],
));

CJSCore::RegisterExt('bp_field_type', array(
	'js' => '/bitrix/js/bizproc/fieldtype.js',
	'css' => '/bitrix/js/bizproc/css/fieldtype.css',
	'lang' => '/bitrix/modules/bizproc/lang/'.LANGUAGE_ID.'/install/js/fieldtype.php',
	'rel' => array('core', 'popup', 'socnetlogdest', 'bp_user_selector'),
	'oninit' => function()
	{
		\Bitrix\Main\Loader::includeModule('socialnetwork');
	},
));