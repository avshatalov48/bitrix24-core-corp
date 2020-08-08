<?php
namespace Bitrix\ImConnector;

use \Bitrix\Main\Context,
	\Bitrix\Main\IO\Path,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Text\UtfSafeString;

/**
 * Class to store common information, methods, and constants language constants used in different parts of the module.
 *
 * Class Library
 * @package Bitrix\ImConnector
 */
class Library
{
	const MODULE_ID = "imconnector";
	const MODULE_ID_OPEN_LINES = "imopenlines";
	const NAME_EXTERNAL_USER = "imconnector";
	const SERVER_URI = "im.bitrix.info";

	const ID_LIVE_CHAT_CONNECTOR = 'livechat';
	const ID_REAL_BOT_FRAMEWORK_CONNECTOR = 'botframework';
	const ID_REAL_BOT_FRAMEWORK_KIK_CONNECTOR = 'botframework.kik';
	const ID_FBINSTAGRAM_CONNECTOR = 'fbinstagram';
	const ID_VIBER_CONNECTOR = 'viber';
	const ID_YANDEX_CONNECTOR = 'yandex';
	const ID_WECHAT_CONNECTOR = 'wechat';
	const ID_NETWORK_CONNECTOR = 'network';
	const ID_FB_COMMENTS_CONNECTOR = 'facebookcomments';
	const ID_IMESSAGE_CONNECTOR = 'imessage';
	const ID_OLX_CONNECTOR = 'olx';

	const COMPONENT_NAME_REST = 'bitrix:imconnector.rest';

	const SCOPE_REST_IMCONNECTOR = 'imopenlines';

	//Components
	const CACHE_DIR_COMPONENT = "/imconnector/component/";
	const CACHE_TIME_COMPONENT = "86400"; //One day
	//Information about the connectors
	const CACHE_DIR_INFO_CONNECTORS_LINE = "/imconnector/infoconnectorsline/";
	const CACHE_TIME_INFO_CONNECTORS_LINE = "86400"; //One day
	//The status of connectors
	const CACHE_DIR_STATUS = "/imconnector/status/";
	const CACHE_TIME_STATUS = "2678400"; //One month

	//Agent time constants
	const GLOBAL_AGENT_EXEC_INTERVAL = 21600; //6 hours
	const LOCAL_AGENT_EXEC_INTERVAL  = 30;
	const INSTANT_AGENT_EXEC_INTERVAL  = 10;

	/** const error */
	const ERROR_CONNECTOR_PROXY_NO_USER_IM = "CONNECTOR_PROXY_NO_USER_IM";//Not the received user id of messenger
	const ERROR_CONNECTOR_PROXY_NO_ADD_USER = "CONNECTOR_PROXY_NO_ADD_USER";//Failed to create or retrieve a user system associated with a user of the remote messenger
	const ERROR_IMCONNECTOR_NOT_SPECIFIED_CORRECT_CONNECTOR = "IMCONNECTOR_NOT_SPECIFIED_CORRECT_CONNECTOR";//Not specified connector
	const ERROR_IMCONNECTOR_NOT_SPECIFIED_CORRECT_COMMAND = "IMCONNECTOR_NOT_SPECIFIED_CORRECT_COMMAND";//Not a valid command
	const ERROR_IMCONNECTOR_NOT_ACTIVE_LINE = 'NOT_ACTIVE_LINE';//Not active or not exists line
	const ERROR_IMCONNECTOR_NOT_ALL_THE_REQUIRED_DATA = "IMCONNECTOR_NOT_ALL_THE_REQUIRED_DATA";//Passed not all the required data
	const ERROR_IMCONNECTOR_EMPTY_PARAMETRS = 'IMCONNECTOR_EMPTY_PARAMETRS';//Passed empty parameters
	const ERROR_NOT_AVAILABLE_CONNECTOR = 'NOT_AVAILABLE_CONNECTOR';//Try to access non-existent or non-active connector
	const ERROR_FEATURE_IS_NOT_SUPPORTED = 'FEATURE_IS_NOT_SUPPORTED';//This feature is not supported
	const ERROR_ADD_EXISTING_CONNECTOR = 'ADD_EXISTING_CONNECTOR';//Attempt to add an existing connector
	const ERROR_UPDATE_NOT_EXISTING_CONNECTOR = 'UPDATE_NOT_EXISTING_CONNECTOR';//Trying to update, not activated connector
	const ERROR_DELETE_NOT_EXISTING_CONNECTOR = 'DELETE_NOT_EXISTING_CONNECTOR';//Attempt deletion of activated connector
	const ERROR_FAILED_TO_ADD_CONNECTOR = 'FAILED_TO_ADD_CONNECTOR';//Failed to add the connector, open line
	const ERROR_FAILED_TO_UPDATE_CONNECTOR = 'FAILED_TO_UPDATE_CONNECTOR';//Failed to update connector, open line
	const ERROR_FAILED_TO_DELETE_CONNECTOR = 'FAILED_TO_DELETE_CONNECTOR';//Failed to remove the connector, open line
	const ERROR_FAILED_TO_LOAD_MODULE_OPEN_LINES = 'FAILED_TO_LOAD_MODULE_OPEN_LINES';//Failed to load module open lines
	const ERROR_FAILED_TO_SAVE_SETTINGS_CONNECTOR = 'FAILED_TO_SAVE_SETTINGS_CONNECTOR';//Failed to save settings connector
	const ERROR_FAILED_TO_TEST_CONNECTOR = 'FAILED_TO_TEST_CONNECTOR';//Failed to test the connection of the connector
	const ERROR_FAILED_REGISTER_CONNECTOR = 'FAILED_REGISTER_CONNECTOR';//Failed to register connector

	const ERROR_IMCONNECTOR_REST_APPLICATION_REGISTRATION_ERROR = 'APPLICATION_REGISTRATION_ERROR';//Application registration error
	const ERROR_IMCONNECTOR_REST_APPLICATION_REGISTRATION_ERROR_POINT = 'APPLICATION_REGISTRATION_ERROR_POINT';
	const ERROR_IMCONNECTOR_REST_APPLICATION_UNREGISTRATION_ERROR = 'APPLICATION_UNREGISTRATION_ERROR';//
	const ERROR_IMCONNECTOR_REST_CONNECTOR_ID_REQUIRED = 'CONNECTOR_ID_REQUIRED';
	const ERROR_IMCONNECTOR_REST_NAME_REQUIRED = 'NAME_REQUIRED';
	const ERROR_IMCONNECTOR_REST_ICON_REQUIRED = 'ICON_REQUIRED';
	const ERROR_IMCONNECTOR_REST_NO_APPLICATION_ID = 'NO_APPLICATION_ID';
	const ERROR_IMCONNECTOR_REST_NO_PLACEMENT_HANDLER = 'NO_PLACEMENT_HANDLER';
	const ERROR_IMCONNECTOR_REST_GENERAL_CONNECTOR_REGISTRATION_ERROR = 'GENERAL_CONNECTOR_REGISTRATION_ERROR';

	/** const error connector server*/
	const ERROR_CONNECTOR_MESSENGER_INVALID_OAUTH_ACCESS_TOKEN = "CONNECTOR_MESSENGER_INVALID_OAUTH_ACCESS_TOKEN";

	/** const event */
	const EVENT_RECEIVED_MESSAGE = "OnReceivedMessage";
	const EVENT_RECEIVED_POST = "OnReceivedPost";

	const EVENT_RECEIVED_POST_UPDATE = "OnReceivedPostUpdate";
	const EVENT_RECEIVED_MESSAGE_UPDATE = "OnReceivedMessageUpdate";

	const EVENT_RECEIVED_MESSAGE_DEL = "OnReceivedMessageDel";

	const EVENT_RECEIVED_STATUS_DELIVERY = "OnReceivedStatusDelivery";
	const EVENT_RECEIVED_STATUS_READING = "OnReceivedStatusReading";

	public const EVENT_RECEIVED_STATUS_BLOCK = 'OnReceivedStatusBlock';
	public const EVENT_RECEIVED_TYPING_STATUS = 'OnReceivedStatusWrites';

	const EVENT_STATUS_ADD = "OnAddStatusConnector";
	const EVENT_STATUS_UPDATE = "OnUpdateStatusConnector";
	const EVENT_STATUS_DELETE = "OnDeleteStatusConnector";

	const EVENT_REGISTRATION_CUSTOM_CONNECTOR = 'OnImConnectorBuildList';
	const EVENT_INFO_LINE_CUSTOM_CONNECTOR = 'OnInfoLine';
	const EVENT_DELETE_MESSAGE_CUSTOM_CONNECTOR = 'OnDeleteMessageCustom';
	const EVENT_UPDATE_MESSAGE_CUSTOM_CONNECTOR = 'OnUpdateMessageCustom';
	const EVENT_SEND_MESSAGE_CUSTOM_CONNECTOR = 'OnSendMessageCustom';

	const EVENT_DELETE_LINE = 'OnDeleteLine';

	public const TIME_LIMIT_RESTRICTIONS = [
		'facebook' => [
			'LIMIT_START_DATE' => 1583280001, //04 Mar 2020 00:00:01
			'BLOCK_DATE' => 86400,
			'BLOCK_REASON' => 'DEFAULT'
		],
		'whatsappbytwilio' => [
			'LIMIT_START_DATE' => 1575158401,
			'BLOCK_DATE' => 86400,
			'BLOCK_REASON' => 'DEFAULT'
		],
	];

	public const AUTO_DELETE_BLOCK = [
		'imessage', 'vkgroup'
	];

	/** @var array A list of connectors, which works without servers */
	public static $noServerConnectors = array(
		'livechat',
		'network'
	);

	/** @var array A list of connectors, where it is not necessary to send system messages.*/
	public static $listNotNeedSystemMessages = array(
		'facebookcomments',
		'botframework.twilio',
		'botframework.emailoffice365',
		self::ID_FBINSTAGRAM_CONNECTOR,
	);

	/** @var array A list of connectors that support group chat.*/
	public static $listGroupChats = array(
		'botframework.slack',
		'botframework.skype',
		'botframework.groupme',
	);

	/** @var array A list of connectors, where it is impossible to send automatic newsletter.*/
	public static $listNotNewsletterChats = array(
		'livechat',
		'network',
		'facebookcomments',
		'botframework.slack',
		'botframework.groupme',
		self::ID_FBINSTAGRAM_CONNECTOR,
	);

	/** @var array */
	public static $listConnectorEditInternalMessages = array(
		'facebookcomments',
		'telegrambot',

		'botframework.skype',
		'botframework.slack',
		'botframework.telegram',
	);

	/** @var array */
	public static $listConnectorDelInternalMessages = array(
		'facebookcomments',
		self::ID_FBINSTAGRAM_CONNECTOR,

		'botframework.slack',
	);

	/** @var array */
	public static $listConnectorDelExternalMessages = array(
		'facebookcomments',
		self::ID_FBINSTAGRAM_CONNECTOR,
	);

	/** @var array A list of connectors, where it is not necessary to send the signature.*/
	public static $listNotNeedSignature = array(
		'facebookcomments',
		'botframework.twilio',
		self::ID_FBINSTAGRAM_CONNECTOR,
		'viber',
		'yandex'
	);

	/** @var array A list of connectors, where we use rich links on operator side.*/
	public static $listConnectorWithRichLinks = array(
		'imessage'
	);

	/** @var array A list of connectors, where we send writing status.*/
	public static $listConnectorWritingStatus = [
		'imessage'
	];

	public static $listSingleThreadGroupChats = array(
		'facebookcomments',
		self::ID_FBINSTAGRAM_CONNECTOR,
	);

	/** @var array Association mime type of the file and its corresponding expansion */
	public static $mimeTypeAssociationExtension = array(
		'application/vnd.hzn-3d-crossword' => '.x3d',
		'video/3gpp' => '.3gp',
		'video/3gpp2' => '.3g2',
		'application/vnd.mseq' => '.mseq',
		'application/vnd.3m.post-it-notes' => '.pwn',
		'application/vnd.3gpp.pic-bw-large' => '.plb',
		'application/vnd.3gpp.pic-bw-small' => '.psb',
		'application/vnd.3gpp.pic-bw-var' => '.pvb',
		'application/vnd.3gpp2.tcap' => '.tcap',
		'application/x-7z-compressed' => '.7z',
		'application/x-abiword' => '.abw',
		'application/x-ace-compressed' => '.ace',
		'application/vnd.americandynamics.acc' => '.acc',
		'application/vnd.acucobol' => '.acu',
		'application/vnd.acucorp' => '.atc',
		'audio/adpcm' => '.adp',
		'application/x-authorware-bin' => '.aab',
		'application/x-authorware-map' => '.aam',
		'application/x-authorware-seg' => '.aas',
		'application/vnd.adobe.air-application-installer-package+zip' => '.air',
		'application/x-shockwave-flash' => '.swf',
		'application/vnd.adobe.fxp' => '.fxp',
		'application/pdf' => '.pdf',
		'application/vnd.cups-ppd' => '.ppd',
		'application/x-director' => '.dir',
		'application/vnd.adobe.xdp+xml' => '.xdp',
		'application/vnd.adobe.xfdf' => '.xfdf',
		'audio/x-aac' => '.aac',
		'application/vnd.ahead.space' => '.ahead',
		'application/vnd.airzip.filesecure.azf' => '.azf',
		'application/vnd.airzip.filesecure.azs' => '.azs',
		'application/vnd.amazon.ebook' => '.azw',
		'application/vnd.amiga.ami' => '.ami',
		'application/vnd.android.package-archive' => '.apk',
		'application/vnd.anser-web-certificate-issue-initiation' => '.cii',
		'application/vnd.anser-web-funds-transfer-initiation' => '.fti',
		'application/vnd.antix.game-component' => '.atx',
		'application/vnd.apple.installer+xml' => '.mpkg',
		'application/applixware' => '.aw',
		'application/vnd.hhe.lesson-player' => '.les',
		'application/vnd.aristanetworks.swi' => '.swi',
		'text/x-asm' => '.s',
		'application/atomcat+xml' => '.atomcat',
		'application/atomsvc+xml' => '.atomsvc',
		'application/atom+xml' => '.xml',
		'application/pkix-attr-cert' => '.ac',
		'audio/x-aiff' => '.aif',
		'video/x-msvideo' => '.avi',
		'application/vnd.audiograph' => '.aep',
		'image' => '.jpg',
		'image/vnd.dxf' => '.dxf',
		'model/vnd.dwf' => '.dwf',
		'text/plain-bas' => '.par',
		'application/x-bcpio' => '.bcpio',
		'application/x-ms-dos-executable' => '.exe',
		'application/octet-stream' => '.exe',
		'image/bmp' => '.bmp',
		'application/x-bittorrent' => '.torrent',
		'application/vnd.rim.cod' => '.cod',
		'application/vnd.blueice.multipass' => '.mpm',
		'application/vnd.bmi' => '.bmi',
		'application/x-sh' => '.sh',
		'image/prs.btif' => '.btif',
		'application/vnd.businessobjects' => '.rep',
		'application/x-bzip' => '.bz',
		'application/x-bzip2' => '.bz2',
		'application/x-csh' => '.csh',
		'text/x-c' => '.c',
		'application/vnd.chemdraw+xml' => '.cdxml',
		'text/css' => '.css',
		'chemical/x-cdx' => '.cdx',
		'chemical/x-cml' => '.cml',
		'chemical/x-csml' => '.csml',
		'application/vnd.contact.cmsg' => '.cdbcmsg',
		'application/vnd.claymore' => '.cla',
		'application/vnd.clonk.c4group' => '.c4g',
		'image/vnd.dvb.subtitle' => '.sub',
		'application/cdmi-capability' => '.cdmia',
		'application/cdmi-container' => '.cdmic',
		'application/cdmi-domain' => '.cdmid',
		'application/cdmi-object' => '.cdmio',
		'application/cdmi-queue' => '.cdmiq',
		'application/vnd.cluetrust.cartomobile-config' => '.c11amc',
		'application/vnd.cluetrust.cartomobile-config-pkg' => '.c11amz',
		'image/x-cmu-raster' => '.ras',
		'model/vnd.collada+xml' => '.dae',
		'text/csv' => '.csv',
		'application/mac-compactpro' => '.cpt',
		'application/vnd.wap.wmlc' => '.wmlc',
		'image/cgm' => '.cgm',
		'x-conference/x-cooltalk' => '.ice',
		'image/x-cmx' => '.cmx',
		'application/vnd.xara' => '.xar',
		'application/vnd.cosmocaller' => '.cmc',
		'application/x-cpio' => '.cpio',
		'application/vnd.crick.clicker' => '.clkx',
		'application/vnd.crick.clicker.keyboard' => '.clkk',
		'application/vnd.crick.clicker.palette' => '.clkp',
		'application/vnd.crick.clicker.template' => '.clkt',
		'application/vnd.crick.clicker.wordbank' => '.clkw',
		'application/vnd.criticaltools.wbs+xml' => '.wbs',
		'application/vnd.rig.cryptonote' => '.cryptonote',
		'chemical/x-cif' => '.cif',
		'chemical/x-cmdf' => '.cmdf',
		'application/cu-seeme' => '.cu',
		'application/prs.cww' => '.cww',
		'text/vnd.curl' => '.curl',
		'text/vnd.curl.dcurl' => '.dcurl',
		'text/vnd.curl.mcurl' => '.mcurl',
		'text/vnd.curl.scurl' => '.scurl',
		'application/vnd.curl.car' => '.car',
		'application/vnd.curl.pcurl' => '.pcurl',
		'application/vnd.yellowriver-custom-menu' => '.cmp',
		'application/dssc+der' => '.dssc',
		'application/dssc+xml' => '.xdssc',
		'application/x-debian-package' => '.deb',
		'audio/vnd.dece.audio' => '.uva',
		'image/vnd.dece.graphic' => '.uvi',
		'video/vnd.dece.hd' => '.uvh',
		'video/vnd.dece.mobile' => '.uvm',
		'video/vnd.uvvu.mp4' => '.uvu',
		'video/vnd.dece.pd' => '.uvp',
		'video/vnd.dece.sd' => '.uvs',
		'video/vnd.dece.video' => '.uvv',
		'application/x-dvi' => '.dvi',
		'application/vnd.fdsn.seed' => '.seed',
		'application/x-dtbook+xml' => '.dtb',
		'application/x-dtbresource+xml' => '.res',
		'application/vnd.dvb.ait' => '.ait',
		'application/vnd.dvb.service' => '.svc',
		'audio/vnd.digital-winds' => '.eol',
		'image/vnd.djvu' => '.djvu',
		'application/xml-dtd' => '.dtd',
		'application/vnd.dolby.mlp' => '.mlp',
		'application/x-doom' => '.wad',
		'application/vnd.dpgraph' => '.dpg',
		'audio/vnd.dra' => '.dra',
		'application/vnd.dreamfactory' => '.dfac',
		'audio/vnd.dts' => '.dts',
		'audio/vnd.dts.hd' => '.dtshd',
		'image/vnd.dwg' => '.dwg',
		'application/vnd.dynageo' => '.geo',
		'application/ecmascript' => '.es',
		'application/vnd.ecowin.chart' => '.mag',
		'image/vnd.fujixerox.edmics-mmr' => '.mmr',
		'image/vnd.fujixerox.edmics-rlc' => '.rlc',
		'application/exi' => '.exi',
		'application/vnd.proteus.magazine' => '.mgz',
		'application/epub+zip' => '.epub',
		'message/rfc822' => '.eml',
		'application/vnd.enliven' => '.nml',
		'application/vnd.is-xpr' => '.xpr',
		'image/vnd.xiff' => '.xif',
		'application/vnd.xfdl' => '.xfdl',
		'application/emma+xml' => '.emma',
		'application/vnd.ezpix-album' => '.ez2',
		'application/vnd.ezpix-package' => '.ez3',
		'image/vnd.fst' => '.fst',
		'video/vnd.fvt' => '.fvt',
		'image/vnd.fastbidsheet' => '.fbs',
		'application/vnd.denovo.fcselayout-link' => '.fe_launch',
		'video/x-f4v' => '.f4v',
		'video/x-flv' => '.flv',
		'image/vnd.fpx' => '.fpx',
		'image/vnd.net-fpx' => '.npx',
		'text/vnd.fmi.flexstor' => '.flx',
		'video/x-fli' => '.fli',
		'application/vnd.fluxtime.clip' => '.ftc',
		'application/vnd.fdf' => '.fdf',
		'text/x-fortran' => '.f',
		'application/vnd.mif' => '.mif',
		'application/vnd.framemaker' => '.fm',
		'image/x-freehand' => '.fh',
		'application/vnd.fsc.weblaunch' => '.fsc',
		'application/vnd.frogans.fnc' => '.fnc',
		'application/vnd.frogans.ltf' => '.ltf',
		'application/vnd.fujixerox.ddd' => '.ddd',
		'application/vnd.fujixerox.docuworks' => '.xdw',
		'application/vnd.fujixerox.docuworks.binder' => '.xbd',
		'application/vnd.fujitsu.oasys' => '.oas',
		'application/vnd.fujitsu.oasys2' => '.oa2',
		'application/vnd.fujitsu.oasys3' => '.oa3',
		'application/vnd.fujitsu.oasysgp' => '.fg5',
		'application/vnd.fujitsu.oasysprs' => '.bh2',
		'application/x-futuresplash' => '.spl',
		'application/vnd.fuzzysheet' => '.fzs',
		'image/g3fax' => '.g3',
		'application/vnd.gmx' => '.gmx',
		'model/vnd.gtw' => '.gtw',
		'application/vnd.genomatix.tuxedo' => '.txd',
		'application/vnd.geogebra.file' => '.ggb',
		'application/vnd.geogebra.tool' => '.ggt',
		'model/vnd.gdl' => '.gdl',
		'application/vnd.geometry-explorer' => '.gex',
		'application/vnd.geonext' => '.gxt',
		'application/vnd.geoplan' => '.g2w',
		'application/vnd.geospace' => '.g3w',
		'application/x-font-ghostscript' => '.gsf',
		'application/x-font-bdf' => '.bdf',
		'application/x-gtar' => '.gtar',
		'application/x-texinfo' => '.texinfo',
		'application/x-gnumeric' => '.gnumeric',
		'application/vnd.google-earth.kml+xml' => '.kml',
		'application/vnd.google-earth.kmz' => '.kmz',
		'application/vnd.grafeq' => '.gqf',
		'image/gif' => '.gif',
		'text/vnd.graphviz' => '.gv',
		'application/vnd.groove-account' => '.gac',
		'application/vnd.groove-help' => '.ghf',
		'application/vnd.groove-identity-message' => '.gim',
		'application/vnd.groove-injector' => '.grv',
		'application/vnd.groove-tool-message' => '.gtm',
		'application/vnd.groove-tool-template' => '.tpl',
		'application/vnd.groove-vcard' => '.vcg',
		'video/h261' => '.h261',
		'video/h263' => '.h263',
		'video/h264' => '.h264',
		'application/vnd.hp-hpid' => '.hpid',
		'application/vnd.hp-hps' => '.hps',
		'application/x-hdf' => '.hdf',
		'audio/vnd.rip' => '.rip',
		'application/vnd.hbci' => '.hbci',
		'application/vnd.hp-jlyt' => '.jlt',
		'application/vnd.hp-pcl' => '.pcl',
		'application/vnd.hp-hpgl' => '.hpgl',
		'application/vnd.yamaha.hv-script' => '.hvs',
		'application/vnd.yamaha.hv-dic' => '.hvd',
		'application/vnd.yamaha.hv-voice' => '.hvp',
		'application/vnd.hydrostatix.sof-data' => '.sfd-hdstx',
		'application/hyperstudio' => '.stk',
		'application/vnd.hal+xml' => '.hal',
		'text/html' => '.html',
		'application/vnd.ibm.rights-management' => '.irm',
		'application/vnd.ibm.secure-container' => '.sc',
		'text/calendar' => '.ics',
		'application/vnd.iccprofile' => '.icc',
		'image/x-icon' => '.ico',
		'application/vnd.igloader' => '.igl',
		'image/ief' => '.ief',
		'application/vnd.immervision-ivp' => '.ivp',
		'application/vnd.immervision-ivu' => '.ivu',
		'application/reginfo+xml' => '.rif',
		'text/vnd.in3d.3dml' => '.3dml',
		'text/vnd.in3d.spot' => '.spot',
		'model/iges' => '.igs',
		'application/vnd.intergeo' => '.i2g',
		'application/vnd.cinderella' => '.cdy',
		'application/vnd.intercon.formnet' => '.xpw',
		'application/vnd.isac.fcs' => '.fcs',
		'application/ipfix' => '.ipfix',
		'application/pkix-cert' => '.cer',
		'application/pkixcmp' => '.pki',
		'application/pkix-crl' => '.crl',
		'application/pkix-pkipath' => '.pkipath',
		'application/vnd.insors.igm' => '.igm',
		'application/vnd.ipunplugged.rcprofile' => '.rcprofile',
		'application/vnd.irepository.package+xml' => '.irp',
		'text/vnd.sun.j2me.app-descriptor' => '.jad',
		'application/java-archive' => '.jar',
		'application/java-vm' => '.class',
		'application/x-java-jnlp-file' => '.jnlp',
		'application/java-serialized-object' => '.ser',
		'text/x-java-source,java' => '.java',
		'application/javascript' => '.js',
		'application/json' => '.json',
		'application/vnd.joost.joda-archive' => '.joda',
		'video/jpm' => '.jpm',
		'image/jpeg' => '.jpg',
		'video/jpeg' => '.jpgv',
		'application/vnd.kahootz' => '.ktz',
		'application/vnd.chipnuts.karaoke-mmd' => '.mmd',
		'application/vnd.kde.karbon' => '.karbon',
		'application/vnd.kde.kchart' => '.chrt',
		'application/vnd.kde.kformula' => '.kfo',
		'application/vnd.kde.kivio' => '.flw',
		'application/vnd.kde.kontour' => '.kon',
		'application/vnd.kde.kpresenter' => '.kpr',
		'application/vnd.kde.kspread' => '.ksp',
		'application/vnd.kde.kword' => '.kwd',
		'application/vnd.kenameaapp' => '.htke',
		'application/vnd.kidspiration' => '.kia',
		'application/vnd.kinar' => '.kne',
		'application/vnd.kodak-descriptor' => '.sse',
		'application/vnd.las.las+xml' => '.lasxml',
		'application/x-latex' => '.latex',
		'application/vnd.llamagraphics.life-balance.desktop' => '.lbd',
		'application/vnd.llamagraphics.life-balance.exchange+xml' => '.lbe',
		'application/vnd.jam' => '.jam',
		'application/vnd.lotus-1-2-3' => '.123',
		'application/vnd.lotus-approach' => '.apr',
		'application/vnd.lotus-freelance' => '.pre',
		'application/vnd.lotus-notes' => '.nsf',
		'application/vnd.lotus-organizer' => '.org',
		'application/vnd.lotus-screencam' => '.scm',
		'application/vnd.lotus-wordpro' => '.lwp',
		'audio/vnd.lucent.voice' => '.lvp',
		'audio/x-mpegurl' => '.m3u',
		'video/x-m4v' => '.m4v',
		'application/mac-binhex40' => '.hqx',
		'application/vnd.macports.portpkg' => '.portpkg',
		'application/vnd.osgeo.mapguide.package' => '.mgp',
		'application/marc' => '.mrc',
		'application/marcxml+xml' => '.mrcx',
		'application/mxf' => '.mxf',
		'application/vnd.wolfram.player' => '.nbp',
		'application/mathematica' => '.ma',
		'application/mathml+xml' => '.mathml',
		'application/mbox' => '.mbox',
		'application/vnd.medcalcdata' => '.mc1',
		'application/mediaservercontrol+xml' => '.mscml',
		'application/vnd.mediastation.cdkey' => '.cdkey',
		'application/vnd.mfer' => '.mwf',
		'application/vnd.mfmp' => '.mfm',
		'model/mesh' => '.msh',
		'application/mads+xml' => '.mads',
		'application/mets+xml' => '.mets',
		'application/mods+xml' => '.mods',
		'application/metalink4+xml' => '.meta4',
		'application/vnd.ms-powerpoint.template.macroenabled.12' => '.potm',
		'application/vnd.ms-word.document.macroenabled.12' => '.docm',
		'application/vnd.ms-word.template.macroenabled.12' => '.dotm',
		'application/vnd.mcd' => '.mcd',
		'application/vnd.micrografx.flo' => '.flo',
		'application/vnd.micrografx.igx' => '.igx',
		'application/vnd.eszigno3+xml' => '.es3',
		'application/x-msaccess' => '.mdb',
		'video/x-ms-asf' => '.asf',
		'application/x-msdownload' => '.exe',
		'application/vnd.ms-artgalry' => '.cil',
		'application/vnd.ms-cab-compressed' => '.cab',
		'application/vnd.ms-ims' => '.ims',
		'application/x-ms-application' => '.application',
		'application/x-msclip' => '.clp',
		'image/vnd.ms-modi' => '.mdi',
		'application/vnd.ms-fontobject' => '.eot',
		'application/vnd.ms-excel' => '.xls',
		'application/vnd.ms-excel.addin.macroenabled.12' => '.xlam',
		'application/vnd.ms-excel.sheet.binary.macroenabled.12' => '.xlsb',
		'application/vnd.ms-excel.template.macroenabled.12' => '.xltm',
		'application/vnd.ms-excel.sheet.macroenabled.12' => '.xlsm',
		'application/vnd.ms-htmlhelp' => '.chm',
		'application/x-mscardfile' => '.crd',
		'application/vnd.ms-lrm' => '.lrm',
		'application/x-msmediaview' => '.mvb',
		'application/x-msmoney' => '.mny',
		'application/vnd.openxmlformats-officedocument.presentationml.presentation' => '.pptx',
		'application/vnd.openxmlformats-officedocument.presentationml.slide' => '.sldx',
		'application/vnd.openxmlformats-officedocument.presentationml.slideshow' => '.ppsx',
		'application/vnd.openxmlformats-officedocument.presentationml.template' => '.potx',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '.xlsx',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.template' => '.xltx',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '.docx',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.template' => '.dotx',
		'application/x-msbinder' => '.obd',
		'application/vnd.ms-officetheme' => '.thmx',
		'application/onenote' => '.onetoc',
		'audio/vnd.ms-playready.media.pya' => '.pya',
		'video/vnd.ms-playready.media.pyv' => '.pyv',
		'application/vnd.ms-powerpoint' => '.ppt',
		'application/vnd.ms-powerpoint.addin.macroenabled.12' => '.ppam',
		'application/vnd.ms-powerpoint.slide.macroenabled.12' => '.sldm',
		'application/vnd.ms-powerpoint.presentation.macroenabled.12' => '.pptm',
		'application/vnd.ms-powerpoint.slideshow.macroenabled.12' => '.ppsm',
		'application/vnd.ms-project' => '.mpp',
		'application/x-mspublisher' => '.pub',
		'application/x-msschedule' => '.scd',
		'application/x-silverlight-app' => '.xap',
		'application/vnd.ms-pki.stl' => '.stl',
		'application/vnd.ms-pki.seccat' => '.cat',
		'application/vnd.visio' => '.vsd',
		'video/x-ms-wm' => '.wm',
		'audio/x-ms-wma' => '.wma',
		'audio/x-ms-wax' => '.wax',
		'video/x-ms-wmx' => '.wmx',
		'application/x-ms-wmd' => '.wmd',
		'application/vnd.ms-wpl' => '.wpl',
		'application/x-ms-wmz' => '.wmz',
		'video/x-ms-wmv' => '.wmv',
		'video/x-ms-wvx' => '.wvx',
		'application/x-msmetafile' => '.wmf',
		'application/x-msterminal' => '.trm',
		'application/msword' => '.doc',
		'application/x-mswrite' => '.wri',
		'application/vnd.ms-works' => '.wps',
		'application/x-ms-xbap' => '.xbap',
		'application/vnd.ms-xpsdocument' => '.xps',
		'audio/midi' => '.mid',
		'application/vnd.ibm.minipay' => '.mpy',
		'application/vnd.ibm.modcap' => '.afp',
		'application/vnd.jcp.javame.midlet-rms' => '.rms',
		'application/vnd.tmobile-livetv' => '.tmo',
		'application/x-mobipocket-ebook' => '.prc',
		'application/vnd.mobius.mbk' => '.mbk',
		'application/vnd.mobius.dis' => '.dis',
		'application/vnd.mobius.plc' => '.plc',
		'application/vnd.mobius.mqy' => '.mqy',
		'application/vnd.mobius.msl' => '.msl',
		'application/vnd.mobius.txf' => '.txf',
		'application/vnd.mobius.daf' => '.daf',
		'text/vnd.fly' => '.fly',
		'application/vnd.mophun.certificate' => '.mpc',
		'application/vnd.mophun.application' => '.mpn',
		'video/mj2' => '.mj2',
		'audio/mpeg' => '.mpga',
		'video/vnd.mpegurl' => '.mxu',
		'video/mpeg' => '.mpeg',
		'application/mp21' => '.m21',
		'audio/mp4' => '.mp4a',
		'video/mp4' => '.mp4',
		'application/mp4' => '.mp4',
		'application/vnd.apple.mpegurl' => '.m3u8',
		'application/vnd.musician' => '.mus',
		'application/vnd.muvee.style' => '.msty',
		'application/xv+xml' => '.mxml',
		'application/vnd.nokia.n-gage.data' => '.ngdat',
		'application/vnd.nokia.n-gage.symbian.install' => '.n-gage',
		'application/x-dtbncx+xml' => '.ncx',
		'application/x-netcdf' => '.nc',
		'application/vnd.neurolanguage.nlu' => '.nlu',
		'application/vnd.dna' => '.dna',
		'application/vnd.noblenet-directory' => '.nnd',
		'application/vnd.noblenet-sealer' => '.nns',
		'application/vnd.noblenet-web' => '.nnw',
		'application/vnd.nokia.radio-preset' => '.rpst',
		'application/vnd.nokia.radio-presets' => '.rpss',
		'text/n3' => '.n3',
		'application/vnd.novadigm.edm' => '.edm',
		'application/vnd.novadigm.edx' => '.edx',
		'application/vnd.novadigm.ext' => '.ext',
		'application/vnd.flographit' => '.gph',
		'audio/vnd.nuera.ecelp4800' => '.ecelp4800',
		'audio/vnd.nuera.ecelp7470' => '.ecelp7470',
		'audio/vnd.nuera.ecelp9600' => '.ecelp9600',
		'application/oda' => '.oda',
		'application/ogg' => '.ogx',
		'audio/ogg' => '.oga',
		'video/ogg' => '.ogv',
		'application/vnd.oma.dd2+xml' => '.dd2',
		'application/vnd.oasis.opendocument.text-web' => '.oth',
		'application/oebps-package+xml' => '.opf',
		'application/vnd.intu.qbo' => '.qbo',
		'application/vnd.openofficeorg.extension' => '.oxt',
		'application/vnd.yamaha.openscoreformat' => '.osf',
		'audio/webm' => '.weba',
		'video/webm' => '.webm',
		'application/vnd.oasis.opendocument.chart' => '.odc',
		'application/vnd.oasis.opendocument.chart-template' => '.otc',
		'application/vnd.oasis.opendocument.database' => '.odb',
		'application/vnd.oasis.opendocument.formula' => '.odf',
		'application/vnd.oasis.opendocument.formula-template' => '.odft',
		'application/vnd.oasis.opendocument.graphics' => '.odg',
		'application/vnd.oasis.opendocument.graphics-template' => '.otg',
		'application/vnd.oasis.opendocument.image' => '.odi',
		'application/vnd.oasis.opendocument.image-template' => '.oti',
		'application/vnd.oasis.opendocument.presentation' => '.odp',
		'application/vnd.oasis.opendocument.presentation-template' => '.otp',
		'application/vnd.oasis.opendocument.spreadsheet' => '.ods',
		'application/vnd.oasis.opendocument.spreadsheet-template' => '.ots',
		'application/vnd.oasis.opendocument.text' => '.odt',
		'application/vnd.oasis.opendocument.text-master' => '.odm',
		'application/vnd.oasis.opendocument.text-template' => '.ott',
		'image/ktx' => '.ktx',
		'application/vnd.sun.xml.calc' => '.sxc',
		'application/vnd.sun.xml.calc.template' => '.stc',
		'application/vnd.sun.xml.draw' => '.sxd',
		'application/vnd.sun.xml.draw.template' => '.std',
		'application/vnd.sun.xml.impress' => '.sxi',
		'application/vnd.sun.xml.impress.template' => '.sti',
		'application/vnd.sun.xml.math' => '.sxm',
		'application/vnd.sun.xml.writer' => '.sxw',
		'application/vnd.sun.xml.writer.global' => '.sxg',
		'application/vnd.sun.xml.writer.template' => '.stw',
		'application/x-font-otf' => '.otf',
		'application/vnd.yamaha.openscoreformat.osfpvg+xml' => '.osfpvg',
		'application/vnd.osgi.dp' => '.dp',
		'application/vnd.palm' => '.pdb',
		'text/x-pascal' => '.p',
		'application/vnd.pawaafile' => '.paw',
		'application/vnd.hp-pclxl' => '.pclxl',
		'application/vnd.picsel' => '.efif',
		'image/x-pcx' => '.pcx',
		'image/vnd.adobe.photoshop' => '.psd',
		'application/pics-rules' => '.prf',
		'image/x-pict' => '.pic',
		'application/x-chat' => '.chat',
		'application/pkcs10' => '.p10',
		'application/x-pkcs12' => '.p12',
		'application/pkcs7-mime' => '.p7m',
		'application/pkcs7-signature' => '.p7s',
		'application/x-pkcs7-certreqresp' => '.p7r',
		'application/x-pkcs7-certificates' => '.p7b',
		'application/pkcs8' => '.p8',
		'application/vnd.pocketlearn' => '.plf',
		'image/x-portable-anymap' => '.pnm',
		'image/x-portable-bitmap' => '.pbm',
		'application/x-font-pcf' => '.pcf',
		'application/font-tdpfr' => '.pfr',
		'application/x-chess-pgn' => '.pgn',
		'image/x-portable-graymap' => '.pgm',
		'image/png' => '.png',
		'image/x-portable-pixmap' => '.ppm',
		'application/pskc+xml' => '.pskcxml',
		'application/vnd.ctc-posml' => '.pml',
		'application/postscript' => '.ai',
		'application/x-font-type1' => '.pfa',
		'application/vnd.powerbuilder6' => '.pbd',
		'application/pgp-signature' => '.pgp',
		'application/vnd.previewsystems.box' => '.box',
		'application/vnd.pvi.ptid1' => '.ptid',
		'application/pls+xml' => '.pls',
		'application/vnd.pg.format' => '.str',
		'application/vnd.pg.osasli' => '.ei6',
		'text/prs.lines.tag' => '.dsc',
		'application/x-font-linux-psf' => '.psf',
		'application/vnd.publishare-delta-tree' => '.qps',
		'application/vnd.pmi.widget' => '.wg',
		'application/vnd.quark.quarkxpress' => '.qxd',
		'application/vnd.epson.esf' => '.esf',
		'application/vnd.epson.msf' => '.msf',
		'application/vnd.epson.ssf' => '.ssf',
		'application/vnd.epson.quickanime' => '.qam',
		'application/vnd.intu.qfx' => '.qfx',
		'video/quicktime' => '.qt',
		'application/x-rar-compressed' => '.rar',
		'audio/x-pn-realaudio' => '.ram',
		'audio/x-pn-realaudio-plugin' => '.rmp',
		'application/rsd+xml' => '.rsd',
		'application/vnd.rn-realmedia' => '.rm',
		'application/vnd.realvnc.bed' => '.bed',
		'application/vnd.recordare.musicxml' => '.mxl',
		'application/vnd.recordare.musicxml+xml' => '.musicxml',
		'application/relax-ng-compact-syntax' => '.rnc',
		'application/vnd.data-vision.rdz' => '.rdz',
		'application/rdf+xml' => '.rdf',
		'application/vnd.cloanto.rp9' => '.rp9',
		'application/vnd.jisp' => '.jisp',
		'application/rtf' => '.rtf',
		'text/richtext' => '.rtx',
		'application/vnd.route66.link66+xml' => '.link66',
		'application/rss+xml' => '.rss, .xml',
		'application/shf+xml' => '.shf',
		'application/vnd.sailingtracker.track' => '.st',
		'image/svg+xml' => '.svg',
		'application/vnd.sus-calendar' => '.sus',
		'application/sru+xml' => '.sru',
		'application/set-payment-initiation' => '.setpay',
		'application/set-registration-initiation' => '.setreg',
		'application/vnd.sema' => '.sema',
		'application/vnd.semd' => '.semd',
		'application/vnd.semf' => '.semf',
		'application/vnd.seemail' => '.see',
		'application/x-font-snf' => '.snf',
		'application/scvp-vp-request' => '.spq',
		'application/scvp-vp-response' => '.spp',
		'application/scvp-cv-request' => '.scq',
		'application/scvp-cv-response' => '.scs',
		'application/sdp' => '.sdp',
		'text/x-setext' => '.etx',
		'video/x-sgi-movie' => '.movie',
		'application/vnd.shana.informed.formdata' => '.ifm',
		'application/vnd.shana.informed.formtemplate' => '.itp',
		'application/vnd.shana.informed.interchange' => '.iif',
		'application/vnd.shana.informed.package' => '.ipk',
		'application/thraud+xml' => '.tfi',
		'application/x-shar' => '.shar',
		'image/x-rgb' => '.rgb',
		'application/vnd.epson.salt' => '.slt',
		'application/vnd.accpac.simply.aso' => '.aso',
		'application/vnd.accpac.simply.imp' => '.imp',
		'application/vnd.simtech-mindmapper' => '.twd',
		'application/vnd.commonspace' => '.csp',
		'application/vnd.yamaha.smaf-audio' => '.saf',
		'application/vnd.smaf' => '.mmf',
		'application/vnd.yamaha.smaf-phrase' => '.spf',
		'application/vnd.smart.teacher' => '.teacher',
		'application/vnd.svd' => '.svd',
		'application/sparql-query' => '.rq',
		'application/sparql-results+xml' => '.srx',
		'application/srgs' => '.gram',
		'application/srgs+xml' => '.grxml',
		'application/ssml+xml' => '.ssml',
		'application/vnd.koan' => '.skp',
		'text/sgml' => '.sgml',
		'application/vnd.stardivision.calc' => '.sdc',
		'application/vnd.stardivision.draw' => '.sda',
		'application/vnd.stardivision.impress' => '.sdd',
		'application/vnd.stardivision.math' => '.smf',
		'application/vnd.stardivision.writer' => '.sdw',
		'application/vnd.stardivision.writer-global' => '.sgl',
		'application/vnd.stepmania.stepchart' => '.sm',
		'application/x-stuffit' => '.sit',
		'application/x-stuffitx' => '.sitx',
		'application/vnd.solent.sdkm+xml' => '.sdkm',
		'application/vnd.olpc-sugar' => '.xo',
		'audio/basic' => '.au',
		'application/vnd.wqd' => '.wqd',
		'application/vnd.symbian.install' => '.sis',
		'application/smil+xml' => '.smi',
		'application/vnd.syncml+xml' => '.xsm',
		'application/vnd.syncml.dm+wbxml' => '.bdm',
		'application/vnd.syncml.dm+xml' => '.xdm',
		'application/x-sv4cpio' => '.sv4cpio',
		'application/x-sv4crc' => '.sv4crc',
		'application/sbml+xml' => '.sbml',
		'text/tab-separated-values' => '.tsv',
		'image/tiff' => '.tiff',
		'application/vnd.tao.intent-module-archive' => '.tao',
		'application/x-tar' => '.tar',
		'application/x-tcl' => '.tcl',
		'application/x-tex' => '.tex',
		'application/x-tex-tfm' => '.tfm',
		'application/tei+xml' => '.tei',
		'text/plain' => '.txt',
		'application/vnd.spotfire.dxp' => '.dxp',
		'application/vnd.spotfire.sfs' => '.sfs',
		'application/timestamped-data' => '.tsd',
		'application/vnd.trid.tpt' => '.tpt',
		'application/vnd.triscape.mxs' => '.mxs',
		'text/troff' => '.t',
		'application/vnd.trueapp' => '.tra',
		'application/x-font-ttf' => '.ttf',
		'text/turtle' => '.ttl',
		'application/vnd.umajin' => '.umj',
		'application/vnd.uoml+xml' => '.uoml',
		'application/vnd.unity' => '.unityweb',
		'application/vnd.ufdl' => '.ufd',
		'text/uri-list' => '.uri',
		'application/vnd.uiq.theme' => '.utz',
		'application/x-ustar' => '.ustar',
		'text/x-uuencode' => '.uu',
		'text/x-vcalendar' => '.vcs',
		'text/x-vcard' => '.vcf',
		'application/x-cdlink' => '.vcd',
		'application/vnd.vsf' => '.vsf',
		'model/vrml' => '.wrl',
		'application/vnd.vcx' => '.vcx',
		'model/vnd.mts' => '.mts',
		'model/vnd.vtu' => '.vtu',
		'application/vnd.visionary' => '.vis',
		'video/vnd.vivo' => '.viv',
		'application/ccxml+xml,' => '.ccxml',
		'application/voicexml+xml' => '.vxml',
		'application/x-wais-source' => '.src',
		'application/vnd.wap.wbxml' => '.wbxml',
		'image/vnd.wap.wbmp' => '.wbmp',
		'audio/x-wav' => '.wav',
		'audio/mp3' => '.mp3',
		'application/davmount+xml' => '.davmount',
		'application/x-font-woff' => '.woff',
		'application/wspolicy+xml' => '.wspolicy',
		'image/webp' => '.webp',
		'application/vnd.webturbo' => '.wtb',
		'application/widget' => '.wgt',
		'application/winhlp' => '.hlp',
		'text/vnd.wap.wml' => '.wml',
		'text/vnd.wap.wmlscript' => '.wmls',
		'application/vnd.wap.wmlscriptc' => '.wmlsc',
		'application/vnd.wordperfect' => '.wpd',
		'application/vnd.wt.stf' => '.stf',
		'application/wsdl+xml' => '.wsdl',
		'image/x-xbitmap' => '.xbm',
		'image/x-xpixmap' => '.xpm',
		'image/x-xwindowdump' => '.xwd',
		'application/x-x509-ca-cert' => '.der',
		'application/x-xfig' => '.fig',
		'application/xhtml+xml' => '.xhtml',
		'application/xml' => '.xml',
		'application/xcap-diff+xml' => '.xdf',
		'application/xenc+xml' => '.xenc',
		'application/patch-ops-error+xml' => '.xer',
		'application/resource-lists+xml' => '.rl',
		'application/rls-services+xml' => '.rs',
		'application/resource-lists-diff+xml' => '.rld',
		'application/xslt+xml' => '.xslt',
		'application/xop+xml' => '.xop',
		'application/x-xpinstall' => '.xpi',
		'application/xspf+xml' => '.xspf',
		'application/vnd.mozilla.xul+xml' => '.xul',
		'chemical/x-xyz' => '.xyz',
		'text/yaml' => '.yaml',
		'application/yang' => '.yang',
		'application/yin+xml' => '.yin',
		'application/vnd.zul' => '.zir',
		'application/zip' => '.zip',
		'application/vnd.handheld-entertainment+xml' => '.zmm',
		'application/vnd.zzazz.deck+xml' => '.zaz',
		'image/*' => '.jpg',
		'video/*' => '.mp4',
		'video/mpeg4' => '.mp4',
		'audio/amr' => '.amr'
	);

	/**
	 * Load language constants library.
	 *
	 * return void
	 */
	static public function loadMessages()
	{
		Loc::loadMessages(__FILE__);
	}

	/**
	 * To get the current server address with protocol and port.
	 *
	 * @return string
	 */
	static public function getCurrentServerUrl()
	{
		$url  = '';

		$server = Context::getCurrent()->getServer();
		$request = Context::getCurrent()->getRequest();
		$serverName = $server->getServerName();

		if(!empty($serverName))
		{
			$url  = ($request->isHttps() ? 'https://' :  'http://')  . $serverName .
				(($server->getServerPort() == 80 || ($server->get('HTTPS') && $server->getServerPort() == 443) ) ?  "" : ":" . $server->getServerPort());
		}

		return $url;
	}

	/**
	 * To address the current open page.
	 *
	 * @return string
	 */
	static public function getCurrentUri()
	{
		$server = Context::getCurrent()->getServer();
		$request = Context::getCurrent()->getRequest();

		$uri  = ($request->isHttps() ? 'https://' :  'http://')  . $server->getServerName() .
			(($server->getServerPort() == 80 || ($server->get('HTTPS') && $server->getServerPort() == 443) ) ?  "" : ":" . $server->getServerPort()) .
			$server->getRequestUri();

		return $uri;
	}

	/**
	 * Loads the language file connector.php.
	 */
	static public function loadMessagesConnectorClass()
	{
		Loc::loadMessages(dirname(__DIR__));
	}

	/**
	 * The analogue of the function empty, which 0, 0.0 and "0" is not considered an empty value.
	 *
	 * @param $value
	 * @return bool
	 */
	static public function isEmpty($value)
	{
		if(empty($value))
		{
			if(isset($value) && ($value===0 || $value===0.0 || $value==="0"))
				return false;
			else
				return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns the file name from the passed url
	 *
	 * @param string $url
	 * @return string
	 */
	static public function getNameFile($url)
	{
		$fileName = Path::getName($url);

		if ($fileName != '')
		{
			$pos = UtfSafeString::getLastPosition($fileName, '?');
			if ($pos !== false)
				$fileName = substr($fileName, 0, $pos);
		}

		return $fileName;
	}
}