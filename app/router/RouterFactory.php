<?php

use Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route,
	Nette\Application\Routers\SimpleRouter;


/**
 * Router factory.
 */
class RouterFactory
{

	/**
	 * @return Nette\Application\IRouter
	 */
	public function createRouter()
	{
		$router = new RouteList();
		$router[] = new Route('index.php', 'Profile:default', Route::ONE_WAY);
		$router[] = new Route('<presenter>/[<lang>/]<action>[/<id>]', 'Profile:default');
		return $router;
	}

}
