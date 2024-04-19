<?php

declare(strict_types=1);

namespace Hollow3464\GraphMailHandler;

enum UserEnpoints: string
{
    case MAIL  = "/me/messages";
    case MAIL_WITH_FOLDER  = "/me/mailFolders('%s')/messages";
    case MAIL_SEND = "/me/messages/%s/send";
    case ATTACHMENT = "/me/messages/%s/attachments/";
}
