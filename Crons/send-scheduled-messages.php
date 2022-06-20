<?php

use Aurora\System\EventEmitter;

require_once __DIR__ . "/../../../system/autoload.php";

\Aurora\System\Api::Init(true);
set_time_limit(0);
date_default_timezone_set("UTC");

// if (PHP_SAPI !== 'cli')
// {
	// exit("Use the console for running this script");
// }

$oMailScheduledMessagesModule = \Aurora\Modules\MailScheduledMessages\Module::getInstance();
$oMailModule = \Aurora\Modules\Mail\Module::getInstance();

$iTime = time();

$aMessagesForSend = $oMailScheduledMessagesModule->Decorator()->GetMessagesForSend($iTime);
foreach ($aMessagesForSend as $aMessageForSend)
{
	$mSendResult = false;
	$directMessageToStreamResult = false;
	$oAccount = $oMailModule->GetAccount($aMessageForSend['AccountId']);

	try {
		$directMessageToStreamResult = $oMailModule->getMailManager()->directMessageToStream($oAccount,
			function ($rMessageResourse, $sContentType, $sFileName, $sMimeIndex = '') use ($oAccount, &$mSendResult) {
				if (\is_resource($rMessageResourse))
				{
					
					$mSendResult = sendMessage($oAccount, $rMessageResourse);
					\fclose($rMessageResourse);
				}
		}, $aMessageForSend['FolderFullName'], $aMessageForSend['MessageUid']);
	} catch (\Exception $oEx) {
		\Aurora\System\Api::LogException($oEx);
	}

	if ($directMessageToStreamResult && $mSendResult)
	{
		$oNamespace = \Aurora\Modules\Mail\Module::getInstance()->getMailManager()->getFoldersNamespace($oAccount);
		$sNamespace = $oNamespace ? $oNamespace->GetPersonalNamespace() : '';
		$sSentFolderFullName = $sNamespace . 'Sent';

		$oMailModule->Decorator()->MoveMessages($aMessageForSend['AccountId'], $aMessageForSend['FolderFullName'], $sSentFolderFullName,$aMessageForSend['MessageUid']);
		$oMailScheduledMessagesModule->Decorator()->RemoveMessage($aMessageForSend['AccountId'], $aMessageForSend['FolderFullName'], $aMessageForSend['MessageUid']);
	}
	else
	{
//		$oMailScheduledMessagesModule->Decorator()->RemoveMessage($aMessageForSend['AccountId'], $aMessageForSend['FolderFullName'], $aMessageForSend['MessageUid']);
	}
}

function getRcpt($aHeaders)
{
	$oResult = \MailSo\Mime\EmailCollection::NewInstance();
	if (isset($aHeaders['To']))
	{
		$oResult->MergeWithOtherCollection(
			\MailSo\Mime\EmailCollection::NewInstance(\ltrim($aHeaders['To']))
		);
	}
	if (isset($aHeaders['Cc']))
	{
		$oResult->MergeWithOtherCollection(
			\MailSo\Mime\EmailCollection::NewInstance(\ltrim($aHeaders['Cc']))
		);
	}
	if (isset($aHeaders['Bcc']))
	{
		$oResult->MergeWithOtherCollection(
			\MailSo\Mime\EmailCollection::NewInstance(\ltrim($aHeaders['Bcc']))
		);
	}

	return $oResult->Unique();
}

function getHeaders($rResource)
{
	$sRawHeaders = '';
    while (trim($line = fgets($rResource)) !== '')
    {
        $sRawHeaders .= $line;
    }

    $aHeaders = \explode("\n", \str_replace("\r", '', $sRawHeaders));

    $sName = null;
    $sValue = null;
    $aResult = [];
    foreach ($aHeaders as $sHeadersValue)
    {
        if (0 === strlen($sHeadersValue))
        {
            continue;
        }

        $sFirstChar = \substr($sHeadersValue, 0, 1);
        if ($sFirstChar !== ' ' && $sFirstChar !== "\t" && false === \strpos($sHeadersValue, ':'))
        {
            continue;
        }
        else if (null !== $sName && ($sFirstChar === ' ' || $sFirstChar === "\t"))
        {
            $sValue = \is_null($sValue) ? '' : $sValue;

            if ('?=' === \substr(\rtrim($sHeadersValue), -2))
            {
                $sHeadersValue = \rtrim($sHeadersValue);
            }

            if ('=?' === \substr(\ltrim($sHeadersValue), 0, 2))
            {
                $sHeadersValue = \ltrim($sHeadersValue);
            }

            if ('=?' === \substr($sHeadersValue, 0, 2))
            {
                $sValue .= $sHeadersValue;
            }
            else
            {
                $sValue .= "\n".$sHeadersValue;
            }
        }
        else
        {
            if (null !== $sName)
            {
                $aResult[$sName] = $sValue;

                $sName = null;
                $sValue = null;
            }

            $aHeaderParts = \explode(':', $sHeadersValue, 2);
            $sName = $aHeaderParts[0];
            $sValue = isset($aHeaderParts[1]) ? $aHeaderParts[1] : '';

            if ('?=' === \substr(\rtrim($sValue), -2))
            {
                $sValue = \rtrim($sValue);
            }
        }
    }
	if (null !== $sName)
    {
		$aResult[$sName] = $sValue;
	}

	return $aResult;
}

function sendMessage($oAccount, $rStream)
{
	if (!$oAccount || !$rStream)
	{
		throw new \Aurora\System\Exceptions\InvalidArgumentException();
	}

	$rMessageStream = \MailSo\Base\ResourceRegistry::CreateMemoryResource();

	$iMessageStreamSize = \MailSo\Base\Utils::MultipleStreamWriter(
		$rStream, array($rMessageStream), 8192, true, true, true);

	$oMailModule = \Aurora\Modules\Mail\Module::getInstance();
	$oImapClient =& $oMailModule->getMailManager()->_getImapClient($oAccount);

	$mResult = false;
	if (is_resource($rMessageStream))
	{
		$aHeaders = getHeaders($rMessageStream);
		$oRcpt = getRcpt($aHeaders);

		if ($oRcpt && 0 < $oRcpt->Count())
		{
			$oServer = null;
			try
			{
				$oSettings =& \Aurora\System\Api::GetSettings();
				$iConnectTimeOut = $oSettings->GetValue('SocketConnectTimeoutSeconds', 5);
				$iSocketTimeOut = $oSettings->GetValue('SocketGetTimeoutSeconds', 5);
				$bVerifySsl = !!$oSettings->GetValue('SocketVerifySsl', false);

				$oSmtpClient = \MailSo\Smtp\SmtpClient::NewInstance();
				$oSmtpClient->SetTimeOuts($iConnectTimeOut, $iSocketTimeOut);

				$oLogger = $oImapClient->Logger();
				if ($oLogger)
				{
					$oSmtpClient->SetLogger($oLogger);
				}

				$oServer = $oAccount->getServer();
				$iSecure = \MailSo\Net\Enumerations\ConnectionSecurityType::AUTO_DETECT;
				if ($oServer->OutgoingUseSsl)
				{
					$iSecure = \MailSo\Net\Enumerations\ConnectionSecurityType::SSL;
				}

				$sEhlo = \MailSo\Smtp\SmtpClient::EhloHelper();

				$oSmtpClient->Connect($oServer->OutgoingServer, $oServer->OutgoingPort, $sEhlo, $iSecure, $bVerifySsl);

				if ($oServer->SmtpAuthType === \Aurora\Modules\Mail\Enums\SmtpAuthType::UseUserCredentials)
				{
					$oSmtpClient->Login($oAccount->IncomingLogin, $oAccount->getPassword());
				}
				else if ($oServer->SmtpAuthType === \Aurora\Modules\Mail\Enums\SmtpAuthType::UseSpecifiedCredentials)
				{
					$oSmtpClient->Login($oServer->SmtpLogin, $oServer->SmtpPassword);
				}

				$oSmtpClient->MailFrom($oAccount->Email);

				$aRcpt =& $oRcpt->GetAsArray();

				foreach ($aRcpt as /* @var $oEmail \MailSo\Mime\Email */ $oEmail)
				{
					$sRcptEmail = $oEmail->GetEmail();
					$oSmtpClient->Rcpt($sRcptEmail);
				}

				$aEmails = array();
				$oRcpt->ForeachList(function ($oEmail) use (&$aEmails) {
					$aEmails[strtolower($oEmail->GetEmail())] = trim($oEmail->GetDisplayName());
				});

				if (\is_array($aEmails))
				{
					$aArgs = ['IdUser' => $oAccount->IdUser, 'Emails' => $aEmails];
					EventEmitter::getInstance()->emit('Mail', 'AfterUseEmails', $aArgs);
				}

				\rewind($rMessageStream);
				$oSmtpClient->DataWithStream($rMessageStream);

				$oSmtpClient->LogoutAndDisconnect();
				\fclose($rMessageStream);
			}
			catch (\MailSo\Net\Exceptions\ConnectionException $oException)
			{
				throw new \Aurora\Modules\Mail\Exceptions\Exception(
					\Aurora\Modules\Mail\Enums\ErrorCodes::CannotConnectToMailServer,
					$oException,
					$oException->getMessage()
				);
			}
			catch (\MailSo\Smtp\Exceptions\LoginException $oException)
			{
				throw new \Aurora\Modules\Mail\Exceptions\Exception(
					\Aurora\Modules\Mail\Enums\ErrorCodes::CannotLoginCredentialsIncorrect,
					$oException,
					$oException->getMessage()
				);
			}
			catch (\MailSo\Smtp\Exceptions\NegativeResponseException $oException)
			{
				throw new \Aurora\Modules\Mail\Exceptions\Exception(
					\Aurora\Modules\Mail\Enums\ErrorCodes::CannotSendMessage,
					$oException,
					$oException->getMessage()
				);
			}
			catch (\MailSo\Smtp\Exceptions\MailboxUnavailableException $oException)
			{
				$iErrorCode = ($oServer && $oServer->SmtpAuthType === \Aurora\Modules\Mail\Enums\SmtpAuthType::UseUserCredentials)
					? \Aurora\Modules\Mail\Enums\ErrorCodes::CannotSendMessageToRecipients
					: \Aurora\Modules\Mail\Enums\ErrorCodes::CannotSendMessageToExternalRecipients;
				throw new \Aurora\Modules\Mail\Exceptions\Exception(
					$iErrorCode,
					$oException,
					$oException->getMessage()
				);
			}

			$mResult = true;
		}
		else
		{
			throw new \Aurora\Modules\Mail\Exceptions\Exception(\Aurora\Modules\Mail\Enums\ErrorCodes::CannotSendMessageInvalidRecipients);
		}
	}

	return $mResult;
}