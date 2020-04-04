<?
interface IXMPPFactoryHandler
{
	public function ReceiveMessage($senderJId, array $arMessage, CXMPPClient $senderClient);
	public function GetIndex();
}

interface IXMPPFactoryServerHandler
{
	public function ProcessServerMessage(array $arMessage, $clientDomain = "");
	public function GetServerIndex();
}

interface IXMPPFactoryCleanableHandler
{
	public function ClearCaches();
}

abstract class CXMPPFactoryHandler
{
	protected $nameTemplate;

	public function Initialize()
	{
		$this->nameTemplate = CXMPPUtility::GetNameFormat();
	}
}
?>