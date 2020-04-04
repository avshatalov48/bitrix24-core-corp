<?
IncludeModuleLangFile(__FILE__);

class CXDILFSchemeXML
{
	public static function Request($server, $page, $port, $method, $namespace, $login, $password, $arParams)
	{
		if (!CModule::IncludeModule("webservice"))
			return false;

		global $APPLICATION;
		$client = new CSOAPClient($server, $page, $port);
		$client->setLogin($login);
		$client->setPassword($password);
		$request = new CSOAPRequest($method, $namespace, $arParams);
		$response = $client->send($request);
		if (is_object($response) && $response->isFault())
		{
			if (XDI_XML_ERROR_DEBUG)
				CXDImport::WriteToLog("ERROR: Incorrect webservice response. Raw response: ".$client->getRawResponse(), "RXML");
			return false;
		}
		else
		{
			if (XDI_XML_DEBUG)
				CXDImport::WriteToLog("Successfull webservice response. Raw response: ".$client->getRawResponse(), "RXML");

			if (is_object($response))
				return $response->Value;
			else
				return false;
		}
	}
}
?>