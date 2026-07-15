<?php
// app/Exceptions/TaskAlreadyDoneException.php

namespace App\Exceptions;

use Exception;

class TaskAlreadyDoneException extends Exception
{
    public function __construct(string $message = 'No se puede editar una tarea que ya está completada.')
    {
        parent::__construct($message, 422);
    }
}
