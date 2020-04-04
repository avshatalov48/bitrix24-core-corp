<?
$intranet_default_option = array(
	"search_user_url" => "/company/personal/user/#ID#/",
	"search_file_extensions" => "doc,ppt,xls,pdf",
	"search_file_extension_exe_doc" => "catdoc -a -s cp1251 -d utf-8 \"#FILE_NAME#\"",
	"search_file_extension_exe_ppt" => "catppt -s cp1251 -d utf-8 \"#FILE_NAME#\"",
	"search_file_extension_exe_xls" => "xls2csv -b --- -g 2 -q 1 -s cp1251 -d utf-8 \"#FILE_NAME#\"",
	"search_file_extension_exe_pdf" => "pdftotext \"#FILE_NAME#\" -enc UTF-8 -nopgbrk -",
);
if(strpos($_SERVER["SERVER_SOFTWARE"], "(Win32)") !== false)
{
	$win_root = str_replace("/", "\\", $_SERVER["DOCUMENT_ROOT"]);
	
	if(file_exists($win_root."\\..\\catdoc\\env.exe"))
	{
		$intranet_default_option["search_file_extension_cd_doc"] = $win_root."\\..\\catdoc";
		$intranet_default_option["search_file_extension_exe_doc"] = "\"\"".$win_root."\\..\\catdoc\\env.exe\" HOME=. \"".$win_root."\\..\\catdoc\\catdoc.exe\" -a -s cp1251 -d utf-8 #SHORT_FILE_NAME#\"";
		$intranet_default_option["search_file_extension_cd_ppt"] = $win_root."\\..\\catdoc";
		$intranet_default_option["search_file_extension_exe_ppt"] = "\"\"".$win_root."\\..\\catdoc\\env.exe\" HOME=. \"".$win_root."\\..\\catdoc\\catppt.exe\" -s cp1251 -d utf-8 #SHORT_FILE_NAME#\"";
		$intranet_default_option["search_file_extension_cd_xls"] = $win_root."\\..\\catdoc";
		$intranet_default_option["search_file_extension_exe_xls"] = "\"\"".$win_root."\\..\\catdoc\\env.exe\" HOME=. \"".$win_root."\\..\\catdoc\\xls2csv.exe\" -b --- -g 2 -q 1 -s cp1251 -d utf-8 #SHORT_FILE_NAME#\"";
		$intranet_default_option["search_file_extension_cd_pdf"] = $win_root."\\..\\xpdf";
		$intranet_default_option["search_file_extension_exe_pdf"] = "\"\"".$win_root."\\..\\xpdf\\pdftotext.exe\" \"#FILE_NAME#\" -enc UTF-8 -nopgbrk -\"";
	}
	else
	{
		$intranet_default_option["search_file_extension_cd_doc"] = $win_root."\\..\\catdoc";
		$intranet_default_option["search_file_extension_exe_doc"] = "\"".$win_root."\\..\\catdoc\\catdoc.exe\" -a -s cp1251 -d utf-8 \"#FILE_NAME#\"";
		$intranet_default_option["search_file_extension_cd_ppt"] = $win_root."\\..\\catdoc";
		$intranet_default_option["search_file_extension_exe_ppt"] = "\"".$win_root."\\..\\catdoc\\catppt.exe\" -s cp1251 -d utf-8 \"#FILE_NAME#\"";
		$intranet_default_option["search_file_extension_cd_xls"] = $win_root."\\..\\catdoc";
		$intranet_default_option["search_file_extension_exe_xls"] = "\"".$win_root."\\..\\catdoc\\xls2csv.exe\" -b --- -g 2 -q 1 -s cp1251 -d utf-8 \"#FILE_NAME#\"";
		$intranet_default_option["search_file_extension_cd_pdf"] = $win_root."\\..\\catdoc";
		$intranet_default_option["search_file_extension_exe_pdf"] = "\"".$win_root."\\..\\catdoc\\pdftotext.exe\" \"#FILE_NAME#\" -enc UTF-8 -nopgbrk -";
	}
}
?>
