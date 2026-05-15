<?php

it('health check returns ok', function (): void {
    $this->getJson('/up')->assertOk();
});
