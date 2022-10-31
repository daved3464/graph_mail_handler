<?php

namespace Hollow3464\GraphMailHandler;

enum OutlookEndpoints: string
{
    case MAIL  = "/me/messages";
    case MAIL_WITH_FOLDER  = "/me/mailFolders('%s')/messages";

    public function fillEmail(string $email)
    {
        return sprintf(
            $this->value,
            $email
        );
    }

    public function fillEmailFolder(string $email, string $folder)
    {
        return sprintf(
            $this->value,
            $email,
            $folder
        );
    }
}
