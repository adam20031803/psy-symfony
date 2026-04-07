<?php
$f = 'c:\symfonyproject\psy-symfony\templates\dashboard\index.html.twig';
$c = file_get_contents($f);
$fixed = mb_convert_encoding($c, 'latin1', 'utf-8');
file_put_contents('c:\symfonyproject\psy-symfony\templates\dashboard\index_fixed.html.twig', $fixed);
