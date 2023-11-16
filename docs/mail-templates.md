---
title: Mail Templates
category: Services
---

# Mail Templates

To allow for easy-to-manage and easy-to-modify messages, we use Mail Templates. These templates are defined in the `resources/markdown/mail-templates` directory and describe their subject, parameters and label using the Frontmatter in the documents.

## Architecture

The architecture of the MailTemplates is fairly simple:

1. An `App\Models\Content\MailTemplate` model to hold the template, subject, and parameters
2. An `App\Mail\MailTemplateMessage` to render the template to a mail message.
4. An `App\Services\FrontmatterService` to parse headers in the template files.
3. A `Database\Seeder\Content\MailTemplateSeeder`  (called by default) to convert Markdown template files to `MailTemplate` models

That's all there's to it. A Nova resource and read-only policy is present to allow admins to view the templates, but no editing is allowed.

## Defining templates

To define a template, create a Markdown file (`.md`) in the `resources/markdown/mail-templates` folder using some sensible name, and add some markdown. A template file without any Frontmatter will use the relative filename as slug (`test/example-mail.md` will become `test-example-mail`) and the filename as a
subject (`test/example-mail.md` will become `Example Mail`).

Then, you can just use Markdown formatting to write your message template. The next time you run `php artisan db:seed`, the templates will be added (or updated).

> *Warning*
> When you remove a mail template file, or change it's label, the original template is **deleted** when seeding.

### Adding additional metadata

You can use Frontmatter to define additional metadata. These consist of three properties:

- `label` - The label to use, instead of the slugged relative path
- `subject` - The subject to use, instead of the Title-ified filename
- `parameters` - The parameters you wish to replace (see below).

None of these are required, but no other properties are allowed. Adding additional properties will cause the seeder to crash.

### Using parameter replacement

When making mails, you usually have some content you wish to replace by relevant data (like a user's name or a date). This can be easily
achieved using the `parameters` property. All parameter names must be in snake_case.

```markdown
---
label: example
parameters:
    first_name: The user's first name
    name: The user's full name
    email: The user's e-mail address
---

Dear {first_name},

This is an example message.

Cheers,

Admin.

```

As you can see, defined parameters need not be in the body of the message. Whenver a user overrides this template with their own, they can use all the 
parameters defined in the original template.

After you've defined the parameters, you must also supply them when constructing a `MailTemplateMessage`. **All parameters in the template must be present in the $parameters array in the constructor**. They don't have to contain a value, but they must be in the list.

```php
Mail::send(new MailTemplateMessage(
    MailTemplate::findByLabel('example'), 
    [
        'first_name' => Auth::user()->first_name,
        'name' => Auth::user()->name,
        'email' => Auth::user()->email,
    ]
));
```

Furthermore, only parameters defined in the template will be replaced when rendering the email. In the above example, a `{last_name}` placeholder in the body would be left as-is in the resulting message.

## Design choices

Some design choices explained.

### Frontmatter and the `FrontmatterService`

Of course, a Packagist package for Frontmatter exists (see `spatie/yaml-frontmatter`), but this would bring in yet another dependency whilst we're cutting down on most of them. Writing one myself wasn't too hard, so I've chosen to do that instead.

### Binding parameters and throwing errors

Binding parameters and having them throw errors is also intentional behaviour. It requires users to write decent code with proper parameters
and allows users significant freedom when overriding code.
