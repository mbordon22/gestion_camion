<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** La raíz lleva al inicio, que pide entrar. */
    public function test_la_raiz_lleva_al_inicio(): void
    {
        $this->get('/')->assertRedirect(route('inicio'));
    }
}
