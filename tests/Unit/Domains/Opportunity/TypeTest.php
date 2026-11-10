<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Opportunity;

use App\Domains\Opportunity\Enums\Type;
use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{
    public function test_odc_travel_support_case_value_and_label(): void
    {
        $this->assertSame('odc-travel-support', Type::ODC_TRAVEL_SUPPORT->value);
        $this->assertSame('2027 ODC Travel Support', Type::ODC_TRAVEL_SUPPORT->label());
        $this->assertSame('2027 ODC Travel Support', Type::getLabelByValue('odc-travel-support'));
    }

    public function test_travel_grants_case_is_unchanged(): void
    {
        $this->assertSame('travel-grants', Type::TRAVEL_GRANTS->value);
        $this->assertSame('Travel Grants', Type::TRAVEL_GRANTS->label());
    }

    public function test_only_odc_travel_support_has_reduced_form(): void
    {
        foreach (Type::cases() as $type) {
            $this->assertSame(
                $type === Type::ODC_TRAVEL_SUPPORT,
                $type->hasReducedForm(),
                "Unexpected hasReducedForm() for {$type->value}"
            );
        }
    }

    public function test_options_include_odc_travel_support(): void
    {
        $this->assertContains(
            ['value' => 'odc-travel-support', 'label' => '2027 ODC Travel Support'],
            Type::getOptions()
        );
    }

    public function test_every_case_has_a_label(): void
    {
        foreach (Type::cases() as $type) {
            $this->assertNotSame('', $type->label());
        }
    }
}
