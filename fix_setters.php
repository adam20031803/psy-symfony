<?php
$files = [
    __DIR__ . '/src/Entity/Habitude.php',
    __DIR__ . '/src/Entity/DailyCheckin.php',
    __DIR__ . '/src/Entity/Store.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Find public function setXxxx(Type $var) and replace with public function setXxxx(?Type $var)
        // We only want to add ? to types: string, int, float, \DateTimeInterface
        // But not to 'bool' because checkbox usually maps to false, and not to nullable properties that already have ?
        
        $content = preg_replace('/public function set([a-zA-Z0-9_]+)\((string|int|float|\\\DateTimeInterface) \$([a-zA-Z0-9_]+)\): static/', 'public function set$1(?$2 $$3): static', $content);
        
        file_put_contents($file, $content);
        echo "Fixed $file\n";
    }
}
