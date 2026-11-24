<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Opportunity;

use App\Domains\Opportunity\Requests\OpportunityPostRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class OpportunityPostRequestTest extends TestCase
{
    private function normalizeUrl(string $raw): string
    {
        $request = new OpportunityPostRequest;
        $request->merge(['url' => $raw]);

        $reflection = new \ReflectionMethod($request, 'prepareForValidation');
        $reflection->invoke($request);

        return (string) $request->input('url');
    }

    public function test_schemeless_host_gets_https_prepended(): void
    {
        $this->assertSame('https://www.google.com', $this->normalizeUrl('www.google.com'));
    }

    public function test_schemeless_path_gets_https_prepended(): void
    {
        $this->assertSame('https://example.com/apply?x=1', $this->normalizeUrl('example.com/apply?x=1'));
    }

    public function test_uppercase_http_scheme_is_lowercased(): void
    {
        $this->assertSame('http://Example.com/X', $this->normalizeUrl('HTTP://Example.com/X'));
    }

    public function test_preserves_https_scheme(): void
    {
        $this->assertSame('https://example.com', $this->normalizeUrl('https://example.com'));
    }

    public function test_protocol_relative_gets_https_scheme(): void
    {
        $this->assertSame('https://cdn.example.com/a', $this->normalizeUrl('//cdn.example.com/a'));
    }

    public function test_whitespace_is_trimmed_and_https_prepended(): void
    {
        $this->assertSame('https://www.x.com', $this->normalizeUrl('  www.x.com  '));
    }

    public function test_javascript_scheme_is_left_as_is(): void
    {
        $this->assertSame('javascript:alert(1)', $this->normalizeUrl('javascript:alert(1)'));
    }

    public function test_data_scheme_is_left_as_is(): void
    {
        $this->assertSame('data:text/html,x', $this->normalizeUrl('data:text/html,x'));
    }

    public function test_idempotent_https_url(): void
    {
        $first = $this->normalizeUrl('https://x.com');
        $second = $this->normalizeUrl($first);

        $this->assertSame('https://x.com', $first);
        $this->assertSame('https://x.com', $second);
    }

    public function test_empty_string_is_left_unchanged(): void
    {
        $this->assertSame('', $this->normalizeUrl(''));
    }

    public function test_rule_rejects_javascript_scheme_after_normalization(): void
    {
        $validator = Validator::make(
            ['url' => 'javascript:alert(1)'],
            ['url' => ['required', 'string', 'max:2048', 'url:http,https']]
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('url', $validator->errors()->toArray());
    }

    public function test_rule_rejects_data_scheme_after_normalization(): void
    {
        $validator = Validator::make(
            ['url' => 'data:text/html,x'],
            ['url' => ['required', 'string', 'max:2048', 'url:http,https']]
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('url', $validator->errors()->toArray());
    }

    public function test_rule_rejects_ftp_scheme_after_normalization(): void
    {
        $validator = Validator::make(
            ['url' => 'ftp://example.com/file'],
            ['url' => ['required', 'string', 'max:2048', 'url:http,https']]
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('url', $validator->errors()->toArray());
    }

    public function test_rule_accepts_normalized_http_and_https(): void
    {
        $httpValidator = Validator::make(
            ['url' => 'http://example.com'],
            ['url' => ['required', 'string', 'max:2048', 'url:http,https']]
        );

        $httpsValidator = Validator::make(
            ['url' => 'https://example.com'],
            ['url' => ['required', 'string', 'max:2048', 'url:http,https']]
        );

        $this->assertFalse($httpValidator->fails());
        $this->assertFalse($httpsValidator->fails());
    }
}
