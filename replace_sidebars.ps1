$files = @(
    'templates/post/show.html.twig',
    'templates/post/new.html.twig',
    'templates/post/edit.html.twig',
    'templates/dashboard/index.html.twig'
)

foreach ($f in $files) {
    if (Test-Path $f) {
        $content = Get-Content -Path $f -Raw
        $newContent = $content -replace '(?ms)<aside class=\"sidebar\">.*?</aside>', '{% include ''components/sidebar.html.twig'' %}'
        Set-Content -Path $f -Value $newContent -Encoding UTF8
    }
}
