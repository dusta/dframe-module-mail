<?php

use Dframe\Router\Response;

set_time_limit(0);
ini_set('max_execution_time', 0);
date_default_timezone_set('Europe/Warsaw');

require_once dirname(__DIR__) . '/../../../vendor/autoload.php';
require_once dirname(__DIR__) . '/../../../web/Config.php';

/**
 * Aonimowa Klasa Crona która sama siebie się wywołuje
 */
return (new class () extends \Dframe\Cron\Task
{

    /**
     * Init function
     *
     * @return array
     */
    public function init()
    {
        if ($this->inLock('mail', [$this->loadModel('Mail/Mail', 'Mail'), 'sendMails'], [])) {
            return Response::renderJSON(['code' => 200, 'message' => 'Cron Complete']);
        }

        return Response::renderJSON(['code' => 403, 'message' => 'Cron in Lock'])->status(403);

    }

}
)->init()->display();