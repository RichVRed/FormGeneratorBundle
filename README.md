FormGeneratorBundle
===================

[![Build Status](https://travis-ci.org/codete/FormGeneratorBundle.svg?branch=master)](https://travis-ci.org/codete/FormGeneratorBundle)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/8893e0c9-ed68-498e-aa86-63320ac43a62/mini.png)](https://insight.sensiolabs.com/projects/8893e0c9-ed68-498e-aa86-63320ac43a62)

We were extremely bored with writing/generating/keeping-up-to-date
our FormType classes so we wanted to automate the process and limit
required changes only to Entity/Document/Whatever class and get new
form out of the box - this is how FormGenerator was invented.

We use annotations on daily basis so it was natural choice for 
forms' configuration although YAML/XML support is planned.

Basic Usages
------------

Consider a class

``` php
/**
 * @Form\Form(
 *  personal = { "title", "name", "surname", "photo", "active" },
 *  work = { "salary" },
 *  admin = { "id" = { "type" = "number" }, "surname" }
 * )
 */
class Person
{
    public $id;
    
    /**
     * @Form\Display(type="choice", choices = { "mr" = "Mr.", "ms" = "Ms." })
     */
    public $title;
    
    /**
     * @Form\Display(type="text")
     */
    public $name;
    
    /**
     * @Form\Display(type="text")
     */
    public $surname;
    
    /**
     * @Form\Display(type="file")
     */
    public $photo;
    
    /**
     * @Form\Display(type="checkbox")
     */
    public $active;
    
    /**
     * @Form\Display(type="money")
     */
    public $salary;
}
```

Now instead of writing whole ``PersonFormType`` and populating
FormBuilder there we can use instead:

``` php
$person = new Person();
$form = $this->get('form_generator')->createFormBuilder($person)
        ->getForm();
$form->handleRequest($request);
```

Voila! Form for editing all annotated properties is generated for us.
We could even omit ``type=".."`` in annotations if Symfony will be
able to guess field type for us.

**Since Symfony 3.0, if you use a custom form type, you must specify the complete namespace when specifying type**

We have also defined additional form views in ``@Form\Form`` 
annotation so we can add another argument to ``createFormBuilder``

``` php
$form = $this->get('form_generator')->createFormBuilder($person, 'personal')
        ->getForm();
```

And we will get Form with properties specified in annotation. We can 
also add/override fields and their properties like this:

``` php
/**
 * @Form\Form(
 *  work = { "salary" = { "attr" = { "class" = "foo" } } }
 * )
 */
class Person
```

But if you need something more sophisticated than Annotations we 
have prepared few possibilities that can be either added manually
or by tagging your services. For each of them FormGenerator allows 
you to pass any additional informations you want in optional 
``$context`` argument. Both ways allows you to specify `priority`
which defines order of execution (default is `0`, if two or more
services have same priority then first added is executed first).

Embed Forms
-----------

FormGenerator will build also forms for nested models:

 ``` php
/*
* @Form\Embed(
*   class = "Codete\FormGeneratorBundle\Tests\Model\Person",
* )
*/
public $person;
```

This will build form from all nested model properties,
but we can specify which view we want to use:

 ``` php
/*
* @Form\Embed(
*   class = "Codete\FormGeneratorBundle\Tests\Model\Person",
*   view = "work"
* )
*/
public $employee;
```

FormViewProvider
----------------

These are used to provide fields list and/or basic configuration
for Forms and are doing exactly same thing as ``@Form\Form``
annotation.

Tag for service: ``form_generator.view_provider``

FormConfigurationModifier
-------------------------

These can modify any form configuration provided by class
itself or FormViewProviders. Feel free to remove or add more
stuff to your Form or tweak existing configuration

Tag for service: ``form_generator.configuration_modifier``

``` php
class InactivePersonModifier implements FormConfigurationModifierInterface
{
    public function modify($model, $configuration, $context) 
    {
        unset($configuration['salary']);
        return $configuration;
    }

    public function supports($model, $configuration, $context) 
    {
        return $model instanceof Person && $model->active === false;
    }
}
```

FormFieldResolver
-----------------

These are responsible for creating actual field in Form and can
be used for instance to attach Transformers to your fields.

Tag for service: ``form_generator.field_resolver``

``` php
class PersonSalaryResolver implements FormFieldResolverInterface
{
    public function getFormField(FormBuilderInterface $fb, $field, $type, $options, $context) 
    {
        $transformer = new /* ... */;
        return $fb->create($field, $type, $options)
                ->addViewTransformer($transformer);
    }

    public function supports($model, $field, $type, $options, $context) 
    {
        return $model instanceof Person && $field === 'salary';
    }
}
```
