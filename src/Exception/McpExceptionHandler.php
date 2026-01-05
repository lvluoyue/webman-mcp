<?php

namespace Luoyue\WebmanMcp\Exception;

use Mcp\Schema\JsonRpc\Error;
use Throwable;
use Webman\Exception\BusinessException;
use Webman\Exception\ExceptionHandler;
use Webman\Http\Request;
use Webman\Http\Response;

class McpExceptionHandler extends ExceptionHandler
{
    public $dontReport = [
        BusinessException::class,
    ];

    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    public function render(Request $request, Throwable $exception): Response
    {
        $id = $request->input('id', '');
        return json(Error::forInternalError($exception, $id));
    }
}
