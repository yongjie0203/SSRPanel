<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use ReflectionException;
use Mail;
use App\Mail\freeMail;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Report or log an exception.
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  Exception $exception
     *
     * @throws Exception
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception               $exception
     *
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        $bcc = ["admin@syyai.com"];
        try {
            $mail = new freeMail(null);
            $mail -> subject = "网站异常通知";
            $mail -> content = "<html><div> ". "请求导致异常的地址：" . $request->fullUrl() . "，请求IP：" . getClientIp(). "，异常信息：". $exception-> getMessage() . "，异常追踪：". $exception->getTraceAsString() . " </div></html>";
            Mail::bcc($bcc) -> queue($mail);
        } catch (\Exception $e) {
            \Log::info("异常请求，发送邮件异常：" . $request->fullUrl() . "，请求IP：" . getClientIp().'异常信息：' .$e-> getMessage() . "，异常追踪：". $e->getTraceAsString() );
            \Log::info("异常请求，原始异常：" . $request->fullUrl() . "，请求IP：" . getClientIp().'异常信息：' .$exception-> getMessage() . "，异常追踪：". $exception->getTraceAsString() );
        }
        
        if (config('app.debug')) {
            \Log::info("请求导致异常的地址：" . $request->fullUrl() . "，请求IP：" . getClientIp());
                      
            return parent::render($request, $exception);
        }
                
        // 捕获身份校验异常
        if ($exception instanceof AuthenticationException) {
            if ($request->ajax()) {
                return response()->json(['status' => 'fail', 'data' => '', 'message' => 'Unauthorized']);
            } else {
                return response()->view('error.404');
            }
        }

        // 捕获CSRF异常
        if ($exception instanceof TokenMismatchException) {
            if ($request->ajax()) {
                return response()->json(['status' => 'fail', 'data' => '', 'message' => trans('404.csrf_title')]);
            } else {
                return response()->view('error.csrf');
            }
        }

        // 捕获反射异常
        if ($exception instanceof ReflectionException) {
            if ($request->ajax()) {
                return response()->json(['status' => 'fail', 'data' => '', 'message' => 'System Error']);
            } else {
                return response()->view('error.404');
            }
        }

        return response()->view('error.404');
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request                 $request
     * @param  \Illuminate\Auth\AuthenticationException $exception
     *
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest(route('login'));
    }
}
