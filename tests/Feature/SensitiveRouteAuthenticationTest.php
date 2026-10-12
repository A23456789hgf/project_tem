<?php

namespace Tests\Feature;

use Tests\TestCase;

class SensitiveRouteAuthenticationTest extends TestCase
{
    public function test_unauthenticated_users_cannot_reach_sensitive_routes_declared_outside_the_auth_group(): void
    {
        $this->delete(route('authorities.bulk.destroy'))->assertRedirectToRoute('login');
        $this->get(route('authorities.import.template'))->assertRedirectToRoute('login');
        $this->get(route('authorities.export'))->assertRedirectToRoute('login');
        $this->delete(route('internal-entities.bulk.destroy'))->assertRedirectToRoute('login');
        $this->post('/switch-scope', ['scope_id' => 1])->assertRedirectToRoute('login');
        $this->post('/log-js-error', ['message' => 'test'])->assertRedirectToRoute('login');
        $this->post(route('projects.duplicate', 1))->assertRedirectToRoute('login');
    }
}
