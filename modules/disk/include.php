<?php

use Bitrix\Disk\Document\OnlyOffice\Bitrix24Scenario;
use Bitrix\Disk\Document\OnlyOffice\ExporterBitrix24Scenario;
use Bitrix\Main\UI\Extension;


\Bitrix\Main\Loader::registerAutoLoadClasses(
	"disk",
	array(
		"disk" => "install/index.php",
		"bitrix\\disk\\document\\blankfiledata" => "lib/document/blankfiledata.php",
		"bitrix\\disk\\document\\documentcontroller" => "lib/document/documentcontroller.php",
		"bitrix\\disk\\document\\documenthandler" => "lib/document/documenthandler.php",
		"bitrix\\disk\\document\\filedata" => "lib/document/filedata.php",
		"bitrix\\disk\\document\\googlehandler" => "lib/document/googlehandler.php",
		"bitrix\\disk\\document\\googleviewerhandler" => "lib/document/googleviewerhandler.php",
		"bitrix\\disk\\document\\localdocumentcontroller" => "lib/document/localdocumentcontroller.php",
		"bitrix\\disk\\document\\onedrivehandler" => "lib/document/onedrivehandler.php",
		"bitrix\\disk\\internals\\error\\error" => "lib/internals/error/error.php",
		"bitrix\\disk\\internals\\error\\errorcollection" => "lib/internals/error/errorcollection.php",
		"bitrix\\disk\\internals\\error\\ierrorable" => "lib/internals/error/ierrorable.php",
		"bitrix\\disk\\internals\\attachedobject" => "lib/internals/attachedobject.php",
		"bitrix\\disk\\internals\\basecomponent" => "lib/internals/basecomponent.php",
		"bitrix\\disk\\internals\\controller" => "lib/internals/controller.php",
		"bitrix\\disk\\internals\\datamanager" => "lib/internals/datamanager.php",
		"bitrix\\disk\\internals\\deletedlog" => "lib/internals/deletedlog.php",
		"bitrix\\disk\\internals\\diag" => "lib/internals/diag.php",
		"bitrix\\disk\\internals\\diskcomponent" => "lib/internals/diskcomponent.php",
		"bitrix\\disk\\internals\\editsession" => "lib/internals/editsession.php",
		"bitrix\\disk\\internals\\externallink" => "lib/internals/externallink.php",
		"bitrix\\disk\\internals\\file" => "lib/internals/file.php",
		"bitrix\\disk\\internals\\folder" => "lib/internals/folder.php",
		"bitrix\\disk\\internals\\model" => "lib/internals/model.php",
		"bitrix\\disk\\internals\\object" => "lib/internals/object.php",
		"bitrix\\disk\\internals\\objectpath" => "lib/internals/objectpath.php",
		"bitrix\\disk\\internals\\right" => "lib/internals/right.php",
		"bitrix\\disk\\internals\\sharing" => "lib/internals/sharing.php",
		"bitrix\\disk\\internals\\simpleright" => "lib/internals/simpleright.php",
		"bitrix\\disk\\internals\\storage" => "lib/internals/storage.php",
		"bitrix\\disk\\internals\\tmpfile" => "lib/internals/tmpfile.php",
		"bitrix\\disk\\internals\\version" => "lib/internals/version.php",
		"bitrix\\disk\\internals\\volume" => "lib/internals/volume.php",
		"bitrix\\disk\\proxytype\\base" => "lib/proxytype/base.php",
		"bitrix\\disk\\proxytype\\common" => "lib/proxytype/common.php",
		"bitrix\\disk\\proxytype\\group" => "lib/proxytype/group.php",
		"bitrix\\disk\\proxytype\\user" => "lib/proxytype/user.php",
		"bitrix\\disk\\security\\disksecuritycontext" => "lib/security/disksecuritycontext.php",
		"bitrix\\disk\\security\\fakesecuritycontext" => "lib/security/fakesecuritycontext.php",
		"bitrix\\disk\\security\\securitycontext" => "lib/security/securitycontext.php",
		"bitrix\\disk\\uf\\blogpostcommentconnector" => "lib/uf/blogpostcommentconnector.php",
		"bitrix\\disk\\uf\\blogpostconnector" => "lib/uf/blogpostconnector.php",
		"bitrix\\disk\\uf\\calendareventconnector" => "lib/uf/calendareventconnector.php",
		"bitrix\\disk\\uf\\connector" => "lib/uf/connector.php",
		"bitrix\\disk\\uf\\controller" => "lib/uf/controller.php",
		"bitrix\\disk\\uf\\documentcontroller" => "lib/uf/documentcontroller.php",
		"bitrix\\disk\\uf\\fileusertype" => "lib/uf/fileusertype.php",
		"bitrix\\disk\\uf\\forummessageconnector" => "lib/uf/forummessageconnector.php",
		"bitrix\\disk\\uf\\isupportforeignconnector" => "lib/uf/isupportforeignconnector.php",
		"bitrix\\disk\\uf\\localdocumentcontroller" => "lib/uf/localdocumentcontroller.php",
		"bitrix\\disk\\uf\\sonetcommentconnector" => "lib/uf/sonetcommentconnector.php",
		"bitrix\\disk\\uf\\sonetlogconnector" => "lib/uf/sonetlogconnector.php",
		"bitrix\\disk\\uf\\stubconnector" => "lib/uf/stubconnector.php",
		"bitrix\\disk\\uf\\taskconnector" => "lib/uf/taskconnector.php",
		"bitrix\\disk\\uf\\crmconnector" => "lib/uf/crmconnector.php",
		"bitrix\\disk\\uf\\crmdealconnector" => "lib/uf/crmconnector.php",
		"bitrix\\disk\\uf\\crmleadconnector" => "lib/uf/crmconnector.php",
		"bitrix\\disk\\uf\\crmcompanyconnector" => "lib/uf/crmconnector.php",
		"bitrix\\disk\\uf\\crmcontactconnector" => "lib/uf/crmconnector.php",
		"bitrix\\disk\\uf\\crmmessageconnector" => "lib/uf/crmconnector.php",
		"bitrix\\disk\\uf\\crmmessagecommentconnector" => "lib/uf/crmconnector.php",
		"bitrix\\disk\\uf\\userfieldmanager" => "lib/uf/userfieldmanager.php",
		"bitrix\\disk\\uf\\versionusertype" => "lib/uf/versionusertype.php",
		"bitrix\\disk\\ui\\avatar" => "lib/ui/avatar.php",
		"bitrix\\disk\\ui\\destination" => "lib/ui/destination.php",
		"bitrix\\disk\\ui\\icon" => "lib/ui/icon.php",
		"bitrix\\disk\\ui\\lazyload" => "lib/ui/lazyload.php",
		"bitrix\\disk\\ui\\text" => "lib/ui/text.php",
		"bitrix\\disk\\ui\\viewer" => "lib/ui/viewer.php",
		"bitrix\\disk\\attachedobject" => "lib/attachedobject.php",
		"bitrix\\disk\\bizprocdocument" => "lib/bizprocdocument.php",
		"bitrix\\disk\\deletedlog" => "lib/deletedlog.php",
		"bitrix\\disk\\desktop" => "lib/desktop.php",
		"bitrix\\disk\\configuration" => "lib/configuration.php",
		"bitrix\\disk\\userconfiguration" => "lib/configuration.php",
		"bitrix\\disk\\downloadcontroller" => "lib/downloadcontroller.php",
		"bitrix\\disk\\driver" => "lib/driver.php",
		"bitrix\\disk\\editsession" => "lib/editsession.php",
		"bitrix\\disk\\externallink" => "lib/externallink.php",
		"bitrix\\disk\\file" => "lib/file.php",
		"bitrix\\disk\\filelink" => "lib/filelink.php",
		"bitrix\\disk\\folder" => "lib/folder.php",
		"bitrix\\disk\\specificfolder" => "lib/folder.php",
		"bitrix\\disk\\folderlink" => "lib/folderlink.php",
		"bitrix\\disk\\baseobject" => "lib/baseobject.php",
		"bitrix\\disk\\right" => "lib/right.php",
		"bitrix\\disk\\rightsmanager" => "lib/rightsmanager.php",
		"bitrix\\disk\\sharing" => "lib/sharing.php",
		"bitrix\\disk\\simpleright" => "lib/simpleright.php",
		"bitrix\\disk\\socialnetworkhandlers" => "lib/socialnetworkhandlers.php",
		"bitrix\\disk\\storage" => "lib/storage.php",
		"bitrix\\disk\\systemuser" => "lib/systemuser.php",
		"bitrix\\disk\\typefile" => "lib/typefile.php",
		"bitrix\\disk\\urlmanager" => "lib/urlmanager.php",
		"bitrix\\disk\\user" => "lib/user.php",
		"bitrix\\disk\\version" => "lib/version.php",
		"bitrix\\disk\\document\\contract\\filecreatable" => "lib/document/contract/filecreatable.php",
	)
);


CJSCore::RegisterExt('disk', array(
	'js' => '/bitrix/js/disk/c_disk.js',
	'css' => '/bitrix/js/disk/css/disk.css',
	'lang' => BX_ROOT.'/modules/disk/lang/'.LANGUAGE_ID.'/js_disk.php',
	'rel' => array('core', 'popup', 'ajax', 'fx', 'dd', 'ui.notification', 'ui.design-tokens', 'ui.fonts.opensans'),
	'oninit' => function() {

		$bitrix24Scenario = new Bitrix24Scenario();
		$exporterBitrix24Scenario = new ExporterBitrix24Scenario($bitrix24Scenario);
		$onlyOfficeEnabled = \Bitrix\Disk\Document\OnlyOffice\OnlyOfficeHandler::isEnabled();

		if ($onlyOfficeEnabled)
		{
			Extension::load('disk.onlyoffice-promo-popup');
		}

		$isCompositeMode = defined("USE_HTML_STATIC_CACHE") && USE_HTML_STATIC_CACHE === true;

		if($isCompositeMode)
		{
			// It's a hack. The package "disk" can be included in static area and pasted in <head>.
			// It means that every page has this BX.messages in composite cache. But we have user's depended options in BX.messages.
			// And in this case we'll rewrite composite cache and have invalid data in composite cache.
			// So in this way we have to insert BX.messages in dynamic area by viewContent placeholders.
			global $APPLICATION;
			$APPLICATION->AddViewContent("inline-scripts", '
				<script>
					BX.message["disk_restriction"] = false;
					BX.message["disk_onlyoffice_available"] = ' . (int)$onlyOfficeEnabled . ';
					BX.message["disk_revision_api"] = ' . (int)\Bitrix\Disk\Configuration::getRevisionApi() . ';
					BX.message["disk_document_service"] = "' . (string)\Bitrix\Disk\UserConfiguration::getDocumentServiceCode() . '"
					' . ($onlyOfficeEnabled ? $exporterBitrix24Scenario->exportToBxMessages() : '') . '
				</script>
			');
		}
		else
		{
			$messages = [
				'disk_restriction' => false,
				'disk_onlyoffice_available' => $onlyOfficeEnabled,
				'disk_revision_api' => (int)\Bitrix\Disk\Configuration::getRevisionApi(),
				'disk_document_service' => (string)\Bitrix\Disk\UserConfiguration::getDocumentServiceCode(),
			];

			$scenarioMessages = $onlyOfficeEnabled ? $exporterBitrix24Scenario->exportToArray() : [];

			return [
				'lang_additional' => array_merge($messages, $scenarioMessages),
			];
		}
	},
));

CJSCore::RegisterExt('file_dialog', array(
	'js' => '/bitrix/js/disk/file_dialog.js',
	'css' => '/bitrix/js/disk/css/file_dialog.css',
	'lang' => '/bitrix/modules/disk/lang/'.LANGUAGE_ID.'/install/js/file_dialog.php',
	'rel' => array('core', 'popup', 'json', 'ajax', 'disk', 'ui.design-tokens'),
));

CJSCore::RegisterExt('disk_desktop', array(
	'js' => '/bitrix/js/disk/disk_desktop.js',
	'lang' => '/bitrix/modules/disk/lang/'.LANGUAGE_ID.'/install/js/disk_desktop.php',
	'rel' => array('core',),
));

CJSCore::RegisterExt('disk_tabs', array(
	'js' => '/bitrix/js/disk/tabs.js',
	'css' => '/bitrix/js/disk/css/tabs.css',
	'rel' => array('core', 'disk',),
));

CJSCore::RegisterExt('disk_queue', array(
	'js' => '/bitrix/js/disk/queue.js',
	'rel' => array('core', 'disk',),
));

CJSCore::RegisterExt('disk_page', array(
	'js' => '/bitrix/js/disk/page.js',
	'rel' => array('disk',),
));

CJSCore::RegisterExt('disk_folder_tree', array(
	'js' => '/bitrix/js/disk/tree.js',
	'rel' => array('disk',),
));

CJSCore::RegisterExt('disk_external_loader', array(
	'js' => '/bitrix/js/disk/external_loader.js',
	'rel' => array('core', 'disk', 'disk_queue'),
));

CJSCore::RegisterExt('disk_information_popups', array(
	'js' => '/bitrix/js/disk/information_popups.js',
	'lang' => '/bitrix/modules/disk/lang/'.LANGUAGE_ID.'/install/js/information_popups.php',
	'rel' => array('core', 'disk', 'helper'),
));

\Bitrix\Disk\Internals\Engine\Binder::registerDefaultAutoWirings();