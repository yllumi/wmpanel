<?php
namespace Yllumi\Wmpanel\app\middleware;

use ReflectionClass;
use ReflectionMethod;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use Yllumi\Wmpanel\attributes\RequirePrivilege;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler) : Response
    {
        $controller = new ReflectionClass($request->controller);
        $noNeedLogin = $controller->getDefaultProperties()['noNeedLogin'] ?? [];

        // ── 1. Auth check ─────────────────────────────────────────
        if (!session('user')) {
            if (!in_array($request->action, $noNeedLogin)) {
                return redirect('/panel/auth/login');
            }
            return $handler($request);
        }

        // ── 2. Privilege check via #[RequirePrivilege] attribute ──
        if ($controller->hasMethod($request->action)) {
            $method = $controller->getMethod($request->action);
            $attrs  = $method->getAttributes(RequirePrivilege::class);

            foreach ($attrs as $attr) {
                /** @var RequirePrivilege $rp */
                $rp = $attr->newInstance();

                if (!isAllow($rp->privilege, $rp->whitelistIds)) {
                    return view('errors/404');
                }
            }
        }

        return $handler($request);
    }
}
