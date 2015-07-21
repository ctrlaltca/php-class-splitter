#!/usr/bin/env php
<?php
/*
 * Loop usage: find . -maxdepth 1 -name '*.php' -exec php php-class-splitter.php '{}' ';'
 */
function help($args)
{
    echo <<< EOM

php $args[0] FILE DEST

Splits a file containing multiple PHP classes up into multiple files with
one class per file. Overwrites any existing files in the destination path
with the same name, useful for handling redundant class definitions
across multiple files. Requires the tokenizer extension.

* FILE - path to a single PHP file containing multiple class definitions
* DEST - path to a directory to contain the new class files

Use -p instead of DEST to create the generated files in the same directory
as the original file.

EOM;
}

if ($argc != 3) {
    help($argv);
    die;
}
$file = null;
$dest = null;

if ($argv[1] == '-p' && $argv[2] == '-p') {
    help($argv);
    die;
}
if ($argv[1] != '-p' && $argv[2] != '-p') {
    $file = $argv[1];
    $dest = rtrim($argv[2], '/');
}
if ($argv[1] != '-p' && $argv[2] == '-p') {
    $file = $argv[1];
    $dest = dirname($file);
}
if ($argv[1] == '-p' && $argv[2] != '-p') {
    $file = $argv[2];
    $dest = dirname($file);
}

if (!file_exists($file)) {
    die("Input file $file does not exist." . PHP_EOL);
}
if (!file_exists($dest)) {
    die("$dest does not exist." . PHP_EOL);
}
if (!is_dir($dest)) {
    die("$dest is not a directory." . PHP_EOL);
}

echo $file." => ".$dest . PHP_EOL;
$tokens = token_get_all(file_get_contents($file));
$mainheader=null;
$buffer = false;
$code='';
while ($token = next($tokens)) {

    if ($token[0] == T_CLASS || $token[0] == T_INTERFACE) {
        $buffer = true;
        $name = null;
        $braces = 1;
        do {
            $code .= is_string($token) ? $token : $token[1];
            if (is_array($token)
                && $token[0] == T_STRING
                && empty($name)) {
                $name = ucfirst($token[1]);
            }
        } while (!(is_string($token) && $token == '{') && $token = next($tokens));
    } elseif ($buffer) {
        if (is_string($token)) {
            $code .= $token;
            if ($token == '{') {
                $braces++;
            } elseif ($token == '}') {
                $braces--;
                if ($braces == 0) {
                    $buffer = false;
                    $file = $dest . '/' . $name . '.php';
                    $code = '<?php' . PHP_EOL . $mainheader . $code;
                    file_put_contents($file, $code);
                    $code='';
                }
            }
        } else {
            $code .= $token[1];
        }
    } elseif ($mainheader === null && ($token[0] == T_COMMENT || $token[0] == T_DOC_COMMENT)) {
        if (is_string($token)) {
            $mainheader = $token;
        } else {
            $mainheader = $token[1];
        }
    } else {
        if (is_string($token)) {
            $code .= $token;
        } else {
            $code .= $token[1];
        }
    }
}
