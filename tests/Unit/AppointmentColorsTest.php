<?php

namespace Tests\Unit;

use Tests\TestCase;

class AppointmentColorsTest extends TestCase
{
    /** @test */
    public function appointment_color_config_is_available()
    {
        $colors = config('appointment_colors');

        $this->assertIsArray($colors, 'appointment_colors config should return an array');

        $expectedKeys = ['programada', 'confirmada', 'en_curso', 'completada', 'cancelada', 'no_asistio'];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $colors, "Config must contain key: {$key}");
            $this->assertArrayHasKey('bg', $colors[$key], "Key {$key} must have 'bg'");
            $this->assertArrayHasKey('text', $colors[$key], "Key {$key} must have 'text'");
        }
    }
}
