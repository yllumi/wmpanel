<?php

namespace Yllumi\Wmpanel\app\controller;

use support\annotation\Middleware;
use Yllumi\Wmpanel\app\middleware\PanelAuthMiddleware;

#[Middleware(PanelAuthMiddleware::class)]
class AdminController
{
    /**
     * Methods that don't require login.
     * The AuthMiddleware reads this property via reflection.
     */
    protected $noNeedLogin = [];

    protected $data = [
        'page_title' => '',
        'module' => '',
        'submodule' => '',
    ];
}
