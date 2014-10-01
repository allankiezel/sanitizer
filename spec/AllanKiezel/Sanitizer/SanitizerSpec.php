<?php

namespace spec\AllanKiezel\Sanitizer;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SanitizerSpec extends ObjectBehavior {

    function let()
    {
        $this->beAnInstanceOf('spec\AllanKiezel\Sanitizer\TestSanitizer');
    }

    function it_should_sanitize_data_against_a_set_of_rules()
    {
        $this->sanitize(
            ['slug' => 'SOME-SLUG'],
            ['slug' => 'strtolower']
        )->shouldReturn(['slug' => 'some-slug']);
    }

    function it_should_apply_multiple_sanitizers()
    {
        $this->sanitize(
            ['slug' => '  SOME-SLUG'],
            ['slug' => 'strtolower|trim']
        )->shouldReturn(['slug' => 'some-slug']);
    }

    function it_should_not_try_to_sanitize_the_data_if_theres_no_matching_rule()
    {
        $this->sanitize(
            ['slug' => '  SOME-SLUG'],
            ['name' => 'strtolower|trim']
        )->shouldReturn(['slug' => '  SOME-SLUG']);

        $this->sanitize(
            ['slug' => '  SOME-SLUG'],
            []
        )->shouldReturn(['slug' => '  SOME-SLUG']);
    }

    function it_allows_sanitizers_to_optionally_be_an_array()
    {
        $this->sanitize(
            ['slug' => '  SOME-SLUG'],
            ['slug' => ['strtolower', 'trim']]
        )->shouldReturn(['slug' => 'some-slug']);
    }

    function it_should_fetch_rules_from_a_subclass()
    {
        $this->sanitize(
            ['name' => '  john']
        )->shouldReturn(['name' => 'John']);
    }

    function it_should_allow_custom_sanitizer_methods()
    {
        $this->register('phone', function($value) {
           return str_replace('-', '', $value);
        });

        $this->sanitize(
          ['phone' => '555-555-5555'],
          ['phone' => 'phone']
        )->shouldReturn(['phone' => '5555555555']);
    }

    function it_should_throw_an_exception_when_registering_a_sanitizer_with_a_similar_name()
    {
        $this->register('phone', function($value) {
            return str_replace('-', '', $value);
        });

        $this->shouldThrow('AllanKiezel\Sanitizer\Exceptions\SanitizerAlreadyExistsException')
            ->during('register', ['phone', function($value){}]);
    }

    function it_should_throw_an_exception_when_registering_a_noncallable_sanitizer()
    {
        $this->shouldThrow('AllanKiezel\Sanitizer\Exceptions\SanitizerNotCallableException')
            ->during('register', ['super_sanitizer', 'func']);
    }

    function it_should_throw_an_exception_if_a_sanitizer_doesnt_exist()
    {
        $this->shouldThrow('AllanKiezel\Sanitizer\Exceptions\SanitizerNotFoundException')
            ->during('sanitize', [
                    ['slug' => 'SOME-SLUG'],
                    ['slug' => 'fakeSanitizer']
            ]);
    }

}

class TestSanitizer extends \AllanKiezel\Sanitizer\Sanitizer {

    protected $rules = [
        'name' => 'ucwords|trim'
    ];
}