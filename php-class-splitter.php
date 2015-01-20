<?php
/*
 * Loop usage: find . -maxdepth 1 -name '*.php' -exec php php-class-splitter.php '{}' ';'
*/
$file = $argv[1];
$dest = dirname($file);
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
        if (is_string($token))
            $mainheader = $token;
        else
            $mainheader = $token[1];
    } else {
        if (is_string($token))
            $code .= $token;
        else
            $code .= $token[1];
    }
}
