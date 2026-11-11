<?php
declare(strict_types=1);

namespace Tests\Unit;

use Core\Validator;
use PHPUnit\Framework\TestCase;

/** Form checks used by the guide, method and login forms. */
final class ValidatorTest extends TestCase
{
    public function test_valid_input_passes(): void
    {
        $v = Validator::make(['title' => 'V60 Basics', 'difficulty' => 'easy', 'coffee_g' => '15', 'brew_time' => '180'])
            ->required('title', 'Title')
            ->maxLength('title', 'Title', 150)
            ->inList('difficulty', 'Difficulty', ['easy', 'medium', 'hard'])
            ->number('coffee_g', 'Coffee', 1, 200)
            ->integer('brew_time', 'Brew time', 1, 3600);

        $this->assertFalse($v->fails());
        $this->assertSame([], $v->errors());
    }

    public function test_required_rejects_missing_and_blank_values(): void
    {
        $v = Validator::make(['title' => '   '])
            ->required('title', 'Title')
            ->required('summary', 'Summary');

        $this->assertTrue($v->fails());
        $this->assertSame([
            'title'   => 'Title is required.',
            'summary' => 'Summary is required.',
        ], $v->errors());
    }

    public function test_an_array_where_text_was_expected_counts_as_empty(): void
    {
        // Someone tampering with the form could send title[]=x.
        $v = Validator::make(['title' => ['x']])->required('title', 'Title');
        $this->assertSame(['title' => 'Title is required.'], $v->errors());
    }

    public function test_max_length_counts_characters_not_bytes(): void
    {
        $this->assertFalse(Validator::make(['name' => 'Café'])->maxLength('name', 'Name', 4)->fails());
        $this->assertTrue(Validator::make(['name' => 'Cafés'])->maxLength('name', 'Name', 4)->fails());
    }

    public function test_in_list_is_strict(): void
    {
        $allowed = ['easy', 'medium', 'hard'];
        $this->assertTrue(Validator::make(['d' => 'expert'])->inList('d', 'Difficulty', $allowed)->fails());
        $this->assertTrue(Validator::make(['d' => 'Easy'])->inList('d', 'Difficulty', $allowed)->fails());
        $this->assertFalse(Validator::make(['d' => 'easy'])->inList('d', 'Difficulty', $allowed)->fails());
    }

    public function test_number_range_and_format(): void
    {
        $check = fn (string $value) => Validator::make(['g' => $value])->number('g', 'Coffee', 1, 200)->fails();

        $this->assertFalse($check('16.5'));
        $this->assertFalse($check('1'));
        $this->assertFalse($check('200'));
        $this->assertTrue($check('0.5'));
        $this->assertTrue($check('201'));
        $this->assertTrue($check('abc'));
    }

    public function test_optional_number_can_be_blank(): void
    {
        $this->assertFalse(Validator::make([])->number('temp', 'Temperature', 70, 100, false)->fails());
        $this->assertTrue(Validator::make([])->number('temp', 'Temperature', 70, 100)->fails());
    }

    public function test_integer_rejects_decimals_and_out_of_range(): void
    {
        $check = fn (string $value) => Validator::make(['s' => $value])->integer('s', 'Seconds', 1, 3600)->fails();

        $this->assertFalse($check('45'));
        $this->assertTrue($check('4.5'));
        $this->assertTrue($check('1e2'));
        $this->assertTrue($check('0'));
        $this->assertTrue($check('3601'));
        $this->assertTrue($check('ten'));
    }

    public function test_only_the_first_error_for_a_field_is_kept(): void
    {
        $v = Validator::make(['title' => ''])
            ->required('title', 'Title')
            ->addError('title', 'Another message');

        $this->assertSame(['title' => 'Title is required.'], $v->errors());
    }
}
