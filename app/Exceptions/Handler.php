<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use App\Helper\JsonApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        $exception = $this->prepareException($exception);
        return $this->prepareJsonApiErrorResponse($request, $exception);
        return parent::render($request, $exception);
    }

    /**
     * Prepare a JSON response for the given exception.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function prepareJsonApiErrorResponse($request, Throwable $e)
    {
        if ($e instanceof FatalThrowableError && config('app.debug')) {
           $this->responseData =  ['error' => true, 'code' => 500, 'message' => 'Fatal Error Something went wrong.', $e->getMessage() . ' in ' . $e->getFile() . ', line no ' . $e->getLine(), 'data' => []];

        } elseif ($e instanceof FatalThrowableError) {
            $this->responseData =  ['error' => true, 'code' => 500, 'message' => 'Whoops, looks like something went wrong.', 'error_message' => '', 'data' => []];

        } elseif ($e instanceof NotFoundHttpException) {
            $this->responseData =  ['error' => true, 'code' => 404, 'message' => 'End Point Not Found.','error_message' => '', 'data' => []];

        } elseif ($e instanceof MethodNotAllowedHttpException) {
            $this->responseData = ['error' => true, 'code' => 405, 'message' => 'Method Not Allowed.', 'error_message' => '', 'data' => []];

        } elseif ($e instanceof AuthenticationException) {
            $this->responseData =  ['error' => true, 'code' => 401, 'message' => ucwords('Access token invalid or expired'),'error_message' => '', 'data' => []];

        } elseif ($e instanceof ValidationException) {
            return $this->convertValidationExceptionToResponse($e, $request);
        } else {
            $this->responseData = ['error' => true,'code' =>  500, 'message' =>  'Something went wrong.', $e->getMessage() . ' in ' . $e->getFile() . ', line no ' . $e->getLine(), 'data' => []];
        }
        return $this->createNewJsonResponse($this->responseData, $e);
    }

    /**
     * Create a response object from the given validation exception.
     *
     * @param  \Illuminate\Validation\ValidationException  $e
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function convertValidationExceptionToResponse(ValidationException $e, $request)
    {
        $messageData = 'Validation errors';
        $validation_errors = $e->errors();
        $this->responseData =  ['error' => true, 'code' => $e->status, 'message' => $messageData, 'error_message' =>  $validation_errors, 'data' =>  []];
        return response()->json($this->responseData, $e->status);
    }


    protected function createNewJsonResponse($responseData, $e)
    {
        return new JsonResponse(
            $responseData,
            $responseData['code'],
            $this->isHttpException($e) ? $e->getHeaders() : [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }
}