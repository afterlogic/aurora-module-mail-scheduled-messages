<?php

require_once __DIR__ . "/../../../system/autoload.php";

\Aurora\System\Api::Init(true);
set_time_limit(0);
date_default_timezone_set("UTC");

if (PHP_SAPI !== 'cli')
{
//	exit("Use the console for running this script");
}

$oMailScheduledMessagesModule = \Aurora\Modules\MailScheduledMessages\Module::getInstance();
$oMailModule = \Aurora\Modules\Mail\Module::getInstance();

$iTime = 1613685600; //time();
$aMessagesForSend = $oMailScheduledMessagesModule->Decorator()->GetMessagesForSend($iTime);
foreach ($aMessagesForSend as $aMessageForSend)
{
	$oAccount = $oMailModule->GetAccount($aMessageForSend['AccountId']);

	if ($oMailModule->getMailManager()->directMessageToStream($oAccount,
		function($rResource, $sContentType, $sFileName, $sMimeIndex = '') use ($oAccount, $oMailModule, $oMailScheduledMessagesModule, $aMessageForSend) {
			if (\is_resource($rResource))
			{
				$oMessage = $oMailModule->getMailManager()->getMessage($oAccount, $aMessageForSend['FolderFullName'], (int) $aMessageForSend['MessageUid']);
				if ($oMessage)
				{
					$mSendResult = sendMessage($oAccount, $oMessage, $rResource);
					if ($mSendResult)
					{
						$oMailModule->Decorator()->DeleteMessages($aMessageForSend['AccountId'], $aMessageForSend['FolderFullName'], [$aMessageForSend['MessageUid']]);
						$oMailScheduledMessagesModule->Decorator()->RemoveMessage($aMessageForSend['AccountId'], $aMessageForSend['FolderFullName'], $aMessageForSend['MessageUid']);
					}
				}
			}
	}, $aMessageForSend['FolderFullName'], $aMessageForSend['MessageUid']))
	{

	}
	else
	{
		$oMailScheduledMessagesModule->Decorator()->RemoveMessage($aMessageForSend['AccountId'], $aMessageForSend['FolderFullName'], $aMessageForSend['MessageUid']);
	}
}

function getRcpt($oMessage)
{
	$oResult = \MailSo\Mime\EmailCollection::NewInstance();

	$oResult->MergeWithOtherCollection($oMessage->To());
	$oResult->MergeWithOtherCollection($oMessage->Cc());
	$oResult->MergeWithOtherCollection($oMessage->Bcc());

	return $oResult->Unique();
}

function sendMessage($oAccount, $oMessage, $rMessageStream)
{
	if (!$oAccount || !$oMessage)
	{
		throw new \Aurora\System\Exceptions\InvalidArgumentException();
	}

	$oImapClient =& $oMailModule->getMailManager()->_getImapClient($oAccount);

	$mResult = false;
	if (is_resource($rMessageStream))
	{
		$oRcpt = getRcpt($oMessage);
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

				$oSmtpClient->DataWithStream($rMessageStream);

				$oSmtpClient->LogoutAndDisconnect();
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

			// if (0 < strlen($sSentFolder))
			// {
			// 	try
			// 	{
			// 		if (!$oMessage->Bcc())
			// 		{
			// 			if (is_resource($rMessageStream))
			// 			{
			// 				rewind($rMessageStream);
			// 			}

			// 			$oImapClient->MessageAppendStream(
			// 				$sSentFolder, $rMessageStream, $iMessageStreamSize, array(
			// 					\MailSo\Imap\Enumerations\MessageFlag::SEEN
			// 				));
			// 		}
			// 		else
			// 		{
			// 			$rAppendMessageStream = \MailSo\Base\ResourceRegistry::CreateMemoryResource();

			// 			$iAppendMessageStreamSize = \MailSo\Base\Utils::MultipleStreamWriter(
			// 				$oMessage->ToStream()/*$rMessageStream*/, array($rAppendMessageStream), 8192, true, true, true);

			// 			$oImapClient->MessageAppendStream(
			// 				$sSentFolder, $rAppendMessageStream, $iAppendMessageStreamSize, array(
			// 					\MailSo\Imap\Enumerations\MessageFlag::SEEN
			// 				));

			// 			if (is_resource($rAppendMessageStream))
			// 			{
			// 				@fclose($rAppendMessageStream);
			// 			}
			// 		}
			// 	}
			// 	catch (\Exception $oException)
			// 	{
			// 		throw new \Aurora\Modules\Mail\Exceptions\Exception(
			// 			\Aurora\Modules\Mail\Enums\ErrorCodes::CannotSaveMessageToSentItems,
			// 			$oException,
			// 			$oException->getMessage()
			// 		);
			// 	}

			// 	if (is_resource($rMessageStream))
			// 	{
			// 		@fclose($rMessageStream);
			// 	}
			// }

			$mResult = true;
		}
		else
		{
			throw new \Aurora\Modules\Mail\Exceptions\Exception(\Aurora\Modules\Mail\Enums\ErrorCodes::CannotSendMessageInvalidRecipients);
		}
	}

	return $mResult;
}