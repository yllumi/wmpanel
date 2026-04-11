<?php

namespace Yllumi\Wmpanel\app\controller;

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
