<?php

namespace Tests\Feature;

use Tests\TestCase;

class AccountOrderingEdgeCaseInterfaceTest extends TestCase
{
    public function test_account_ordering_honors_reduced_motion_and_cleans_animation_styles(): void
    {
        $script = file_get_contents(
            resource_path(
                'js/features/account-ordering.js'
            )
        );

        $this->assertIsString($script);

        $this->assertStringContainsString(
            "window.matchMedia(\n    '(prefers-reduced-motion: reduce)'",
            $script
        );

        $this->assertStringContainsString(
            'const animationTimers = new WeakMap();',
            $script
        );

        $this->assertStringContainsString(
            'function clearMotionStyles(list)',
            $script
        );

        $this->assertStringContainsString(
            'if (REDUCED_MOTION.matches)',
            $script
        );

        $this->assertStringContainsString(
            'function handleReducedMotionChange(',
            $script
        );

        $this->assertStringContainsString(
            "REDUCED_MOTION.addEventListener(\n        'change'",
            $script
        );
    }

    public function test_account_ordering_guards_invalid_dom_and_boundary_moves(): void
    {
        $script = file_get_contents(
            resource_path(
                'js/features/account-ordering.js'
            )
        );

        $this->assertIsString($script);

        $this->assertStringContainsString(
            'function domOrderIsValid(list)',
            $script
        );

        $this->assertStringContainsString(
            'const currentIndex =',
            $script
        );

        $this->assertStringContainsString(
            'cards.indexOf(card)',
            $script
        );

        $this->assertStringContainsString(
            'targetIndex < 0',
            $script
        );

        $this->assertStringContainsString(
            'targetIndex >= cards.length',
            $script
        );

        $this->assertStringContainsString(
            'if (! domOrderIsValid(list))',
            $script
        );

        $this->assertStringContainsString(
            "if (accountId === '')",
            $script
        );

        $this->assertStringContainsString(
            "window.addEventListener(\n    'pageshow'",
            $script
        );
    }
}
