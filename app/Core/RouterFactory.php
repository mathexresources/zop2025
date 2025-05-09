<?php

declare(strict_types=1);

namespace App\Core;

use Nette;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
	use Nette\StaticClass;

	/**
	 * Creates the main application router with defined routes.
	 */
    public static function createRouter(): RouteList
    {
        $router = new RouteList;

        // 1. Homepage route (root URL)
        $router->addRoute('', 'Dashboard:default');

        // 2. Specific user profile route with optional username
        $router->addRoute('user/profile[/<username>]', 'User:profile');

        // 3. Inventory routes with filters
        $router->addRoute('inventory[/<filter>]', 'Dashboard:inventory');

        // 4. Other generic fallback routes (optional)
        $router->addRoute('<presenter>/<action>[/<id>]', 'Dashboard:default');

        return $router;
    }

}
