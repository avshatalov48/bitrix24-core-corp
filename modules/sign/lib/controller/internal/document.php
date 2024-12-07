<?php
namespace Bitrix\Sign\Controller\Internal;

use Bitrix\Sign\Document as DocumentCore;
use Bitrix\Sign\Item\Api\Document\Signing\SendInviteRequest;
use Bitrix\Sign\Proxy;
use Bitrix\Sign\Service;

class Document extends \Bitrix\Sign\Controller\Controller
{
	public function getDefaultPreFilters(): array
	{
		return [
			new \Bitrix\Main\Engine\ActionFilter\Authentication(),
			new \Bitrix\Main\Engine\ActionFilter\Csrf(),
			new \Bitrix\Sign\Controller\ActionFilter\Extranet(),
		];
	}

	/**
	 * @param int $documentId
	 * @param string|null $memberHash Member hash.
	 * @return void
	 * @todo check usages, timeline?
	 */
	public function resendFileAction(string $documentId, ?string $memberHash = null)
	{
		$document = DocumentCore::getById($documentId);
		if ($document)
		{
            if ($document->getDataValue('VERSION') == 2)
            {
                $response = Service\Container::instance()->getApiDocumentSigningService()->sendInvite(
                    new SendInviteRequest($document->getUid(), $memberHash)
                );
                if (!$response->isSuccess())
                {
                    foreach ($response->getErrors() as $error)
                    {
                        \Bitrix\Sign\Error::getInstance()->addErrorInstance($error);
                    }
                }
            }
            else
            {
                $result = Proxy::sendCommand('document.resend', [
                    'hash' => $document->getHash(),
                    'members' => [$document->getMemberByHash($memberHash)->toArray()]
                ]);

                if ($result)
                {
                    return $result;
                }
            }
		}
	}
}
