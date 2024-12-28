<?

CCurrencyLang::disableUseHideZero();

if (!empty($_REQUEST['pdf']))
	return include(__DIR__.'/pdf.php');
else
	return include(__DIR__.'/html.php');

CCurrencyLang::enableUseHideZero();

?>