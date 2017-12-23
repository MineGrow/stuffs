<?php

namespace Framework\Router;

use Framework\Router\FrameRouter;

Interface RouterInterface
{
  public function route(FrameRouter $entrance);
}