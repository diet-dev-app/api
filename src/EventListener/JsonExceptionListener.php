<?php
namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class JsonExceptionListener
{
    #[AsEventListener(event: 'kernel.exception')]
    public function onKernelException(ExceptionEvent $event)
    {
        $request = $event->getRequest();
        if (0 === strpos($request->headers->get('Content-Type'), 'application/json') ||
            0 === strpos($request->headers->get('Accept'), 'application/json')) {
            $exception = $event->getThrowable();
            $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
            $data = [
                'error' => [
                    'message' => $exception->getMessage(),
                    'code' => $statusCode,
                ]
            ];
            $event->setResponse(new JsonResponse($data, $statusCode));
        }
    }
}
