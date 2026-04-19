<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class NoHtml extends Constraint
{
    public string $message = 'no_html.message';
}
