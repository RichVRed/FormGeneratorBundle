<?php

namespace Codete\FormGeneratorBundle\Annotations;

/**
 * @Annotation
 */
class Embed extends Field
{
    public $view = 'default';

    public $type = 'embed';
}
