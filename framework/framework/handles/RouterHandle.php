<?php

namespace Framework\Handles;

use Framework\App;
use Framework\Handles\Handle;
use Framework\Exceptions\CoreHttpException;
use ReflectionClass;
use Closure;
use Framework\Router\Job;
use Framework\Router\FrameRouter;

class RouterHandle implements Handle
{
  public function register(App $app)
  {
    (new FrameRouter())->init($app);
  }
}