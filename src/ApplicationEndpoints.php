<?php

declare(strict_types=1);

namespace Hollow3464\GraphMailHandler;

enum ApplicationEndpoints: string
{
    case MAIL  = "/users/%s/messages";
    case MAIL_WITH_FOLDER  = "/users/%s/mailFolders('%s')/messages";
    case MAIL_SEND = "/users/%s/messages/%s/send";
    case ATTACHMENT = "/users/%s/messages/%s/attachments/";
}
