<?php

namespace App;

class AuthClass {
  /**
   * Example middleware closure
   *
   * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
   * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
   * @param  callable                                 $next     Next middleware
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function __invoke($request, $response, $next) {
      
    if (! isset($_SESSION['loggedIn'])) {
      return $response->withredirect('/login');
    }

      //$response->getBody()->write('BEFORE'.serialize($_SESSION));
      $response = $next($request, $response);
      //$response->getBody()->write('AFTER');

      return $response;
  }
}

?>