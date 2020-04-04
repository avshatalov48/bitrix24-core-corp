<?
IncludeModuleLangFile(__FILE__);
$arFileTypes[] = array(
	'NAME' => GetMessage('WEBDAV_OPTIONS_FILETYPE_DOCUMENTS'),
	'EXTENSIONS' => '.accda .accdb .accde .accdt .accdu .doc .docm .docx .dot .dotm .dotx .gsa .gta .mda .mdb .mny .mpc .mpp .mpv .mso .msproducer .pcs .pot .potm .potx .ppa .ppam .pps .ppsm .ppsx .ppt .pptm .pptx .pst .pub .rtf .sldx .xla .xlam .xlb .xlc .xld .xlk .xll .xlm .xls .xlsb .xlsm .xlsx .xlt .xltm .xltx .xlv .xlw .xps .xsf .odt .ods .odp .odb .odg .odf'
);
$arFileTypes[] = array(
	'NAME' => GetMessage('WEBDAV_OPTIONS_FILETYPE_IMAGES'),
	'EXTENSIONS' => '.gif .png .jpg .jpeg .bmp .psd .cdr .ai'
);
$arFileTypes[] = array(
	'NAME' => GetMessage('WEBDAV_OPTIONS_FILETYPE_MEDIA'),
	'EXTENSIONS' => '.avi .mp4 .mpeg .flv .mp3 .mp2 .mpg .mkv .qt .vob .ogg .wav'
);

foreach ($arFileTypes as $iIndex => $arValue)
{
	$arFileTypes[$iIndex]['ID'] = substr(md5($arFileTypes[$iIndex]['EXTENSIONS']),0,8);
}

$webdav_default_option = array(
	"office_extensions" =>
		".accda .accdb .accde .accdt .accdu .doc .docm .docx .dot .dotm ".
		".dotx .gsa .gta .mda .mdb .mny .mpc .mpp .mpv .mso .msproducer .pcs ".
		".pot .potm .potx .ppa .ppam .pps .ppsm .ppsx .ppt .pptm .pptx .pst .pub ".
		".rtf .sldx .xla .xlam .xlb .xlc .xld .xlk .xll .xlm .xls .xlsb .xlsm .xlsx ".
		".xlt .xltm .xltx .xlv .xlw .xps .xsf .odt .ods .odp .odb .odg .odf",
	"hide_system_files" => "Y",
	"webdav_log" => "N",
	"webdav_socnet" => "Y",
	"webdav_allow_ext_doc_services_global" => "Y",
	"webdav_allow_ext_doc_services_local" => "Y",
	"webdav_allow_autoconnect_share_group_folder" => "Y",
	"file_types" => serialize($arFileTypes),
	"bp_history_size" => 50,
	"bp_history_glue" => "Y",
	"bp_history_glue_period" => 300
);
?>
