<?php

namespace App\Mail;

class ExceptionReportMail extends GenericMail
{
    public function __construct(array $errorData)
    {
        parent::__construct(
            subjectLine: 'Application Exception Alert',
            viewName: 'emails.templates.exception-report',
            viewData: ['errorData' => $errorData]
        );
    }
}
