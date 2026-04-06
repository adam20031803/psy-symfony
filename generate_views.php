<?php
$entities = ['habitude', 'daily_checkin', 'store'];

foreach ($entities as $e) {
    if (!is_dir(__DIR__ . '/templates/' . $e)) {
        mkdir(__DIR__ . '/templates/' . $e, 0777, true);
    }
    
    // index
    $index = "{% extends 'base.html.twig' %}\n\n{% block title %}{$e} index{% endblock %}\n\n{% block body %}\n    <h1>{$e} index</h1>\n\n    <a href=\"{{ path('app_{$e}_new') }}\">Create new</a>\n    <table class=\"table\">\n        <tbody>\n        {% for item in {$e}" . ($e === 'daily_checkin' ? 's' : 's') . " %}\n            <tr>\n                <td>{{ item.id }}</td>\n                <td>\n                    <a href=\"{{ path('app_{$e}_show', {'id': item.id}) }}\">show</a>\n                    <a href=\"{{ path('app_{$e}_edit', {'id': item.id}) }}\">edit</a>\n                </td>\n            </tr>\n        {% else %}\n            <tr>\n                <td colspan=\"3\">no records found</td>\n            </tr>\n        {% endfor %}\n        </tbody>\n    </table>\n<a href=\"{{ path('app_dashboard') }}\">Back to Dashboard</a>\n{% endblock %}\n";
    file_put_contents(__DIR__ . "/templates/{$e}/index.html.twig", $index);

    // new
    $new = "{% extends 'base.html.twig' %}\n\n{% block title %}New {$e}{% endblock %}\n\n{% block body %}\n    <h1>Create new {$e}</h1>\n\n    {{ include('{$e}/_form.html.twig') }}\n\n    <a href=\"{{ path('app_{$e}_index') }}\">back to list</a>\n{% endblock %}\n";
    file_put_contents(__DIR__ . "/templates/{$e}/new.html.twig", $new);

    // edit
    $edit = "{% extends 'base.html.twig' %}\n\n{% block title %}Edit {$e}{% endblock %}\n\n{% block body %}\n    <h1>Edit {$e}</h1>\n\n    {{ include('{$e}/_form.html.twig', {'button_label': 'Update'}) }}\n\n    <a href=\"{{ path('app_{$e}_index') }}\">back to list</a>\n\n    {{ include('{$e}/_delete_form.html.twig') }}\n{% endblock %}\n";
    file_put_contents(__DIR__ . "/templates/{$e}/edit.html.twig", $edit);

    // show
    $show = "{% extends 'base.html.twig' %}\n\n{% block title %}{$e}{% endblock %}\n\n{% block body %}\n    <h1>{$e}</h1>\n\n    <p>ID: {{ {$e}.id }}</p>\n\n    <a href=\"{{ path('app_{$e}_index') }}\">back to list</a>\n\n    <a href=\"{{ path('app_{$e}_edit', {'id': {$e}.id}) }}\">edit</a>\n\n    {{ include('{$e}/_delete_form.html.twig') }}\n{% endblock %}\n";
    file_put_contents(__DIR__ . "/templates/{$e}/show.html.twig", $show);

    // _form
    $form = "{{ form_start(form) }}\n    {{ form_widget(form) }}\n    <button class=\"btn\">{{ button_label|default('Save') }}</button>\n{{ form_end(form) }}\n";
    file_put_contents(__DIR__ . "/templates/{$e}/_form.html.twig", $form);

    // _delete_form
    $del = "<form method=\"post\" action=\"{{ path('app_{$e}_delete', {'id': {$e}.id}) }}\" onsubmit=\"return confirm('Are you sure you want to delete this item?');\">\n    <input type=\"hidden\" name=\"_token\" value=\"{{ csrf_token('delete' ~ {$e}.id) }}\">\n    <button class=\"btn\">Delete</button>\n</form>\n";
    file_put_contents(__DIR__ . "/templates/{$e}/_delete_form.html.twig", $del);
    
    // Correct variable names for lists
    $indexVarMap = [
        'habitude' => 'habitudes',
        'daily_checkin' => 'daily_checkins',
        'store' => 'stores'
    ];
    
    $indexStr = file_get_contents(__DIR__ . "/templates/{$e}/index.html.twig");
    $indexStr = str_replace("{% for item in {$e}s %}", "{% for item in " . $indexVarMap[$e] . " %}", $indexStr);
    file_put_contents(__DIR__ . "/templates/{$e}/index.html.twig", $indexStr);
}
echo "Done";
