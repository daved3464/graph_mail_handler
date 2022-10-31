<?php

namespace Hollow3464\GraphMailHandler;

enum OutlookApplicationEndpoints: string
{
    case MAIL  = "/users/%s/messages";
    case MAIL_WITH_FOLDER  = "/users/%s/mailFolders('%s')/messages";
    case MAIL_SEND = "/users/%s/messages/%s/send";

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

    public function fillEmailSingle(string $email, string $mail_id)
    {
        return sprintf(
            $this->value,
            $email,
            $mail_id
        );
    }
}
