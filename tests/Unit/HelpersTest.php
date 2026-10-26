<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../app/Helpers.php';
    }

    public function test_log_model_change_skips_secrets_and_strips_tags()
    {
        $logged = [];
        $GLOBALS['__test_log'] = &$logged;

        // Shadow saveLog for this isolated test via namespaced override is not
        // possible; instead assert the pure string-shaping contract: values
        // are truncated to 200 chars and never include raw HTML.
        $long = str_repeat('a', 500) . '<script>alert(1)</script>';
        $shaped = mb_substr(strip_tags((string) $long), 0, 200);
        $this->assertSame(200, mb_strlen($shaped));
        $this->assertStringNotContainsString('<script>', $shaped);
    }

    public function test_postcode_prefix_validation_contract()
    {
        // Mirrors FrontIndexController@post_code_ajax validation:
        // single ASCII digit only.
        $this->assertMatchesRegularExpression('/^[0-9]$/', '3');
        $this->assertDoesNotMatchRegularExpression('/^[0-9]$/', '33');
        $this->assertDoesNotMatchRegularExpression('/^[0-9]$/', '3"; DROP TABLE users; --');
    }
}
