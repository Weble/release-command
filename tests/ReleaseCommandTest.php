<?php

use function Pest\Laravel\artisan;

it('without git it does not run', function () {
    artisan('release')->assertExitCode(1);
});
