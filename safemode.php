<?php
//require_once "/home/modfarah/public_html/lib/alom/alomprotection.php";
//alom_protect(__FILE__, ['depth' => 1.2]);

if(!defined('T_NULLSAFE_OBJECT_OPERATOR')){define('T_NULLSAFE_OBJECT_OPERATOR', null);}
if(!defined('T_NAME_FULLY_QUALIFIED')){define('T_NAME_FULLY_QUALIFIED', null);}
if(!defined('T_COALESCE_EQUAL')){define('T_COALESCE_EQUAL', null);}
if(!defined('T_FN')){define('T_FN', null);}

// --------------------------------------------------------------------------------------------------
function _PHPSCR_sug($code, $top){
    $tokens = token_get_all($code);
    $code = '';
    $i = 0;
    $code .= $tokens[$i++][1];
    while(isset($tokens[$i]) && ((is_array($tokens[$i]) && in_array($tokens[$i][0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT))) || $tokens[$i] == ';'))
        $code .= is_array($tokens[$i]) ? $tokens[$i++][1] : $tokens[$i++];
    if(isset($tokens[$i]) && is_array($tokens[$i]) && $tokens[$i][0] == T_NAMESPACE){
        $code .= $tokens[$i++][1];
        while(is_array($tokens[$i]) && in_array($tokens[$i][0], array(T_WHITESPACE, T_STRING, T_NS_SEPARATOR)))
            $code .= $tokens[$i++][1];
        if($tokens[$i] == '{'){
            $code .= $tokens[$i++];
            $code .= $top;
            //$code .= $sug;
            for(; isset($tokens[$i]); ++$i)
                $code .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
            return $code;
        }else{
            $code .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
            ++$i;
            while(isset($tokens[$i]) && ((is_array($tokens[$i]) && in_array($tokens[$i][0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT))) || $tokens[$i] == ';'))
                $code .= is_array($tokens[$i]) ? $tokens[$i++][1] : $tokens[$i++];
        }
    }
    while(isset($tokens[$i]) && is_array($tokens[$i]) && $tokens[$i][0] == T_NAMESPACE){
        $code .= $tokens[$i++][1];
        while(is_array($tokens[$i]) && in_array($tokens[$i][0], array(T_WHITESPACE, T_STRING, T_NS_SEPARATOR)))
            $code .= $tokens[$i++][1];
        $code .= $tokens[$i++];
        while(isset($tokens[$i]) && ((is_array($tokens[$i]) && in_array($tokens[$i][0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT))) || $tokens[$i] == ';'))
            $code .= $tokens[$i++][1];
    }
    $code .= $top;
    //$code .= $sug;
    for(; isset($tokens[$i]); ++$i)
        $code .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
    return $code;
}
function _PHPSCR_ReparseTokens($nss, &$tokens, &$i){
    $j = $i;
    $prev = count($tokens);
    for(++$i; isset($tokens[$i]); ++$i)
        $nss .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
    $tokens = token_get_all($nss);
    $next = count($tokens);
    $i = $j + $next - $prev;
}
function _PHPSCR_ReadL($tokens, $a, $b, &$i){
    $str = '';
    $u = 0;
    do {
        if(is_array($tokens[$i])){
            $str .= $tokens[$i][1];
            if(($tokens[$i][0] == T_CURLY_OPEN || $tokens[$i][0] == T_DOLLAR_OPEN_CURLY_BRACES) && $a == '{')++$u;
        }else{
            $str .= $tokens[$i];
            if($tokens[$i] == $a)++$u;
            elseif($tokens[$i] == $b)--$u;
        }
    }while(isset($tokens[++$i]) && $u != 0);
    --$i;
    return $str;
}
function _PHPSCR_ReadR($tokens, $a, $b, $i, &$j, $nc = false){
    $str = '';
    $u = 0;
    do {
        if(is_array($tokens[$i-$j])){
            $str = $tokens[$i-$j][1].$str;
            if(($tokens[$i-$j][0] == T_CURLY_OPEN || $tokens[$i-$j][0] == T_DOLLAR_OPEN_CURLY_BRACES) && $a == '{')--$u;
        }else{
            if($nc && $tokens[$i-$j] == ';')
                return false;
            $str = $tokens[$i-$j].$str;
            if($tokens[$i-$j] == $b)++$u;
            elseif($tokens[$i-$j] == $a)--$u;
        }
        ++$j;
    }while($i >= $j && $u != 0);
    --$j;
    if($nc && $str[0] != '{')
        return false;
    return $str;
}
function _PHPSCR_ReadS($tokens, &$i){
    $str = '';
    for(; isset($tokens[$i]); ++$i)
        if(in_array($tokens[$i], [')', ']', '}', ',', ';']))break;
        elseif(is_array($tokens[$i]) && $tokens[$i][0] == T_CLOSE_TAG)break;
        elseif(is_array($tokens[$i]) && $tokens[$i][0] == T_START_HEREDOC){
            $str .= $tokens[$i++][1];
            for(; isset($tokens[$i]) && (!is_array($tokens[$i]) || $tokens[$i][0] != T_END_HEREDOC); ++$i)
                $str .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
            $str .= $tokens[$i][1];
        }elseif(is_array($tokens[$i]))
            $str .= $tokens[$i][1];
        elseif($tokens[$i] == '(')
            $str .= _PHPSCR_ReadL($tokens, '(', ')', $i);
        elseif($tokens[$i] == '[')
            $str .= _PHPSCR_ReadL($tokens, '[', ']', $i);
        elseif($tokens[$i] == '{')
            $str .= _PHPSCR_ReadL($tokens, '{', '}', $i);
        elseif($tokens[$i] == '"'){
            $str .= $tokens[$i++];
            for(; isset($tokens[$i]) && $tokens[$i] != '"'; ++$i)
                $str .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
            $str .= $tokens[$i];
        }elseif($tokens[$i] == '`'){
            $str .= $tokens[$i++];
            for(; isset($tokens[$i]) && $tokens[$i] != '`'; ++$i)
                $str .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
            $str .= $tokens[$i];
        }else
            $str .= $tokens[$i];
    return $str;
}
function _PHPSCR_tokensReadToEnd($tokens, &$i){
    $str = '';
    if(is_array($tokens[$i]) && $tokens[$i][0] == T_WHITESPACE)
        $str .= $tokens[$i++][1];
    if($tokens[$i] == ';'){
        --$i;
        return $str;
    }
    if(is_array($tokens[$i]) && $tokens[$i][0] == T_CLOSE_TAG){
        --$i;
        return $str;
    }
    if($tokens[$i] == '(')
        $str .= _PHPSCR_ReadL($tokens, '(', ')', $i);
    else{
        $str .= _PHPSCR_ReadS($tokens, $i);
        --$i;
    }
    return $str;
}
function _PHPSCR_ParseTick($tokens, &$i){
    $nss = '';
    $opc = 1;
    for(; isset($tokens[$i]) && $opc != 0; ++$i)
        if(is_array($tokens[$i]) && in_array($tokens[$i][0], [T_CLASS, T_TRAIT, T_INTERFACE])){
            $nss .= $tokens[$i++][1];
            while(is_array($tokens[$i]) && in_array($tokens[$i][0], array(T_WHITESPACE, T_STRING, T_NS_SEPARATOR, T_EXTENDS, T_IMPLEMENTS)))
                $nss .= $tokens[$i++][1];
            if($tokens[$i] != '{'){
                --$i;
                continue;
            }
            $nss .= $tokens[$i++];
            for(; isset($tokens[$i]) && $tokens[$i] != '}'; ++$i)
                if(is_array($tokens[$i]) && $tokens[$i][0] == T_FUNCTION){
                    $nss .= $tokens[$i++][1];
                    for(; isset($tokens[$i]) && $tokens[$i] != '('; ++$i)
                        if(is_array($tokens[$i]))
                            $nss .= $tokens[$i][1];
                        else
                            $nss .= $tokens[$i];
                    $nss .= _PHPSCR_ReadL($tokens, '(', ')', $i);
                    ++$i;
                    for(; isset($tokens[$i]) && $tokens[$i] != '{'; ++$i)
                        if($tokens[$i] == ';')break;
                        elseif(is_array($tokens[$i]))
                            $nss .= $tokens[$i][1];
                        else
                            $nss .= $tokens[$i];
                    if($tokens[$i] == ';'){
                        $nss .= $tokens[$i];
                        continue;
                    }
                    $nss .= $tokens[$i++];
                    $nss .= _PHPSCR_ParseTick($tokens, $i);
                    --$i;
                }elseif(is_array($tokens[$i]))
                    $nss .= $tokens[$i][1];
                elseif($tokens[$i] == '{')
                    $nss .= _PHPSCR_ReadL($tokens, '{', '}', $i);
                else
                    $nss .= $tokens[$i];
            $nss .= $tokens[$i];
        }elseif($tokens[$i] == '{'){
            ++$opc;
            $nss .= $tokens[$i];                
        }elseif($tokens[$i] == '}'){
            --$opc;
            $nss .= $tokens[$i];
        }elseif(is_array($tokens[$i]))
            $nss .= $tokens[$i][1];
        elseif($tokens[$i] == '(' && $i > 1 && is_array($tokens[$i-1]) && (in_array($tokens[$i-1][0], [T_FOR]) ||
        ($tokens[$i-1][0] == T_WHITESPACE && is_array($tokens[$i-2]) && in_array($tokens[$i-2][0], [T_FOR])))){
            $params = _PHPSCR_ReadL($tokens, '(', ')', $i);
            $nss .= $params;
        }elseif($tokens[$i] == ';')
            $nss .= ";_PHPSCR_tick();";
        else
            $nss .= $tokens[$i];
    return $nss;
}
function _PHPSCR_ParseParams($params){
    $params = substr($params, 1, -1);
    $tokens = token_get_all("<?php $params?>");
    array_shift($tokens);
    array_pop($tokens);
    $nss = '';
    $cama = true;
    $cnst = false;
    for($i = 0; isset($tokens[$i]); ++$i){
        if($tokens[$i] == '('){
            $nss .= _PHPSCR_ReadL($tokens, '(', ')', $i);
            $cama = false;
        }elseif($tokens[$i] == '['){
            $nss .= _PHPSCR_ReadL($tokens, '[', ']', $i);
            $cama = false;
        }elseif($tokens[$i] == '{'){
            $nss .= _PHPSCR_ReadL($tokens, '{', '}', $i);
            $cama = false;
        }elseif($cama && is_array($tokens[$i]) && $tokens[$i][0] == T_WHITESPACE)
            $nss .= $tokens[$i][1];
        elseif($cama && ((is_array($tokens[$i]) && $tokens[$i][0] == T_VARIABLE) || $tokens[$i] == '$')){
            $h = $i;
            $arg = is_array($tokens[$i]) ? $tokens[$i++][1] : $tokens[$i++];
            $den = $ai = false;
            for(; isset($tokens[$i]); ++$i)
                if(in_array($tokens[$i], [')', ']', '}', ',', ';']))break;
                elseif(is_array($tokens[$i]) && $tokens[$i][0] == T_CLOSE_TAG)break;
                elseif(is_array($tokens[$i]) && $tokens[$i][0] == T_START_HEREDOC){
                    $arg .= $tokens[$i++][1];
                    for(; isset($tokens[$i]) && (!is_array($tokens[$i]) || $tokens[$i][0] != T_END_HEREDOC); ++$i)
                        $arg .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                    $arg .= $tokens[$i][1];
                }elseif(is_array($tokens[$i]) && in_array($tokens[$i][0], [T_DOUBLE_COLON, T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_WHITESPACE, T_NS_SEPARATOR]))
                    $arg .= $tokens[$i][1];
                elseif($tokens[$i] == '('){
                    $i = $h;
                    $nss .= $tokens[$i][1];
                    continue 2;
                }elseif($tokens[$i] == '['){
                    $ai = true;
                    $arg .= _PHPSCR_ReadL($tokens, '[', ']', $i);
                }elseif($tokens[$i] == '{')
                    $arg .= _PHPSCR_ReadL($tokens, '{', '}', $i);
                elseif($tokens[$i] == '"'){
                    $arg .= $tokens[$i++];
                    for(; isset($tokens[$i]) && $tokens[$i] != '"'; ++$i)
                        $arg .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                    $arg .= $tokens[$i];
                }elseif($tokens[$i] == '`'){
                    $arg .= $tokens[$i++];
                    for(; isset($tokens[$i]) && $tokens[$i] != '`'; ++$i)
                        $arg .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                    $arg .= $tokens[$i];
                }elseif(in_array($tokens[$i], ['$']))
                    $arg .= $tokens[$i];
                else{
                    $den = true;
                    break;
                }
            --$i;
            if($den)$nss .= $arg;
            else $nss .= $ai ? "(isset($arg)&&is_string($arg)?$arg:array(&$arg)[0])" : "&$arg";
            $cama = false;
        }elseif($cama && $tokens[$i] == ',')
            $nss .= ',null';
        elseif($tokens[$i] == ','){
            $nss .= $tokens[$i];
            $cama = true;
        }elseif(is_array($tokens[$i])){
            $nss .= $tokens[$i][1];
            $cama = false;
        }else{
            $nss .= $tokens[$i];
            $cama = false;
        }
    }
    return "($nss)";
}
function _PHPSCR_SafeSourceSingle($source, $safeid, $nonticks = false, $multi = false){
    if($source === '')return '';
    $tokens = token_get_all($source);
    $nss = '';
    $u = 0;
    $cnst = false;
    for($i = 0; isset($tokens[$i]); ++$i){
        if($tokens[$i] == '"'){
            $nss .= $tokens[$i++];
            for(; isset($tokens[$i]) && $tokens[$i] != '"'; ++$i)
                if($tokens[$i][0] == T_CURLY_OPEN || $tokens[$i][0] == T_DOLLAR_OPEN_CURLY_BRACES){
                    $nss .= $tokens[$i++][1];
                    if(is_array($tokens[$i]))
                        $nss .= $tokens[$i++][1];
                    elseif($tokens[$i] != '}')
                        $nss .= $tokens[$i++];
                    $u = 1;
                    $params = '';
                    do {
                        if(is_array($tokens[$i])){
                            $params .= $tokens[$i][1];
                            if($tokens[$i][0] == T_CURLY_OPEN || $tokens[$i][0] == T_DOLLAR_OPEN_CURLY_BRACES)++$u;
                        }else{
                            $params .= $tokens[$i];
                            if($tokens[$i] == '{')++$u;
                            elseif($tokens[$i] == '}')--$u;
                        }
                    }while(isset($tokens[++$i]) && $u != 0);
                    $params = trim(substr(_PHPSCR_SafeSourceSingle("<?php $params ?>", $safeid), 30, -2));
                    --$i;
                    $nss .= $params;
                }elseif(is_array($tokens[$i]))
                    $nss .= $tokens[$i][1];
                else
                    $nss .= $tokens[$i];
            if(!isset($tokens[$i])){
                continue;
            }
            $nss .= $tokens[$i];
            _PHPSCR_ReparseTokens($nss, $tokens, $i);
        }elseif($tokens[$i] == '`'){
            $nss .= 'array($_PHPSCR_tmpvar=\_PHPSCR_func("shell_exec",array("';
            ++$i;
            for(; isset($tokens[$i]) && $tokens[$i] != '`'; ++$i)
                if($tokens[$i][0] == T_CURLY_OPEN || $tokens[$i][0] == T_DOLLAR_OPEN_CURLY_BRACES){
                    $nss .= $tokens[$i++][1];
                    if(is_array($tokens[$i]))
                        $nss .= $tokens[$i++][1];
                    elseif($tokens[$i] != '}')
                        $nss .= $tokens[$i++];
                    $u = 1;
                    $params = '';
                    do {
                        if(is_array($tokens[$i])){
                            $params .= $tokens[$i][1];
                            if($tokens[$i][0] == T_CURLY_OPEN || $tokens[$i][0] == T_DOLLAR_OPEN_CURLY_BRACES)++$u;
                        }else{
                            $params .= $tokens[$i];
                            if($tokens[$i] == '{')++$u;
                            elseif($tokens[$i] == '}')--$u;
                        }
                    }while(isset($tokens[++$i]) && $u != 0);
                    $params = trim(substr(_PHPSCR_SafeSourceSingle("<?php $params ?>", $safeid), 30, -2));
                    --$i;
                    $nss .= $params;
                }elseif($tokens[$i][0] == T_ENCAPSED_AND_WHITESPACE)
                    $nss .= str_replace(['\`', '"'], ['`', '\"'], $tokens[$i][1]);
                elseif(is_array($tokens[$i]))
                    $nss .= $tokens[$i][1];
                else
                    $nss .= $tokens[$i];
            $nss .= '")),call_user_function_array($_PHPSCR_tmpvar[0],$_PHPSCR_tmpvar[1]))[1]';
            _PHPSCR_ReparseTokens($nss, $tokens, $i);
        }elseif(is_array($tokens) && $tokens[$i][0] == T_START_HEREDOC){
            $nss .= $tokens[$i++];
            for($i = 0; isset($tokens[$i]); ++$i)
                if(is_array($tokens[$i])){
                    if($tokens[$i][0] == T_CURLY_OPEN || $tokens[$i][0] == T_DOLLAR_OPEN_CURLY_BRACES){
                        $nss .= $tokens[$i++][1];
                        if(is_array($tokens[$i]))
                            $nss .= $tokens[$i++][1];
                        elseif($tokens[$i] != '}')
                            $nss .= $tokens[$i++];
                        $u = 1;
                        $params = '';
                        do {
                            if(is_array($tokens[$i])){
                                $params .= $tokens[$i][1];
                                if($tokens[$i][0] == T_CURLY_OPEN || $tokens[$i][0] == T_DOLLAR_OPEN_CURLY_BRACES)++$u;
                            }else{
                                $params .= $tokens[$i];
                                if($tokens[$i] == '{')++$u;
                                elseif($tokens[$i] == '}')--$u;
                            }
                        }while(isset($tokens[++$i]) && $u != 0);
                        $params = trim(substr(_PHPSCR_SafeSourceSingle("<?php $params ?>", $safeid), 30, -2));
                        --$i;
                        $nss .= $params;
                    }else{
                        $nss .= $tokens[$i][1];
                        if($tokens[$i][0] == T_END_HEREDOC)
                            break;
                    }
                }else
                    $nss .= $tokens[$i];
            _PHPSCR_ReparseTokens($nss, $tokens, $i);
        }elseif(is_array($tokens[$i]) && in_array($tokens[$i][0], [T_ISSET, T_UNSET, T_EMPTY])){
            $nss .= $tokens[$i++][1];
            if(is_array($tokens[$i]) && $tokens[$i][0] == T_WHITESPACE)
                $nss .= $tokens[$i][1];
            $nss .= _PHPSCR_ReadL($tokens, '(', ')', $i);
        }elseif(is_array($tokens[$i]) && $tokens[$i][0] == T_DO){
            $nss .= $tokens[$i++][1];
            if(is_array($tokens[$i]) && $tokens[$i][0] == T_WHITESPACE)
                $nss .= $tokens[$i++][1];
            if($tokens[$i] == '{'){
                $params = _PHPSCR_ReadL($tokens, '{', '}', $i);
                ++$i;
                $params = substr(_PHPSCR_SafeSourceSingle("<?php $params ?>", $safeid, true), 5, -2);
                $nss .= $params;
                if(is_array($tokens[$i]) && $tokens[$i][0] == T_WHITESPACE)
                    $nss .= $tokens[$i++][1];
                if(is_array($tokens[$i]) && $tokens[$i][0] == T_WHILE){
                    $nss .= $tokens[$i++][1];
                    if(is_array($tokens[$i]) && $tokens[$i][0] == T_WHITESPACE)
                        $nss .= $tokens[$i++][1];
                    if($tokens[$i] == '('){
                        $params = _PHPSCR_ReadL($tokens, '(', ')', $i);
                        ++$i;
                        $params = substr(_PHPSCR_SafeSourceSingle("<?php $params ?>", $safeid, true), 5, -2);
                        $nss .= $params;
                        if(is_array($tokens[$i]) && $tokens[$i][0] == T_WHITESPACE)
                            $nss .= $tokens[$i++][1];
                        if($tokens[$i] == ';')
                            $nss .= $tokens[$i++];
                    }
                }
            }
            --$i;
            _PHPSCR_ReparseTokens($nss, $tokens, $i);
            $cnst = false;
        }elseif(is_array($tokens[$i]) && in_array($tokens[$i][0], [T_WHILE, T_FOR, T_IF, T_ELSEIF, T_ELSE, T_FOREACH, T_DECLARE])){
            $l = $tokens[$i][0];
            $ps = $tokens[$i++][1];
            if(is_array($tokens[$i]) && $tokens[$i][0] == T_WHITESPACE)
                $ps .= $tokens[$i++][1];
            if($tokens[$i] == '{' || $tokens[$i] == ':'){
                if($l == T_WHILE){
                    $nss .= $ps."(true)";
                    --$i;
                    _PHPSCR_ReparseTokens($nss, $tokens, $i);
                    continue;
                }else{
                    $nss .= $ps;
                    --$i;
                    continue;
                }
            }
            if($tokens[$i] == '('){
                $params = _PHPSCR_ReadL($tokens, '(', ')', $i);
                ++$i;
                $params = trim(substr(_PHPSCR_SafeSourceSingle("<?php $params ?>", $safeid, true), 5, -2));
                $ps .= $params;
                if(is_array($tokens[$i]) && $tokens[$i][0] == T_WHITESPACE)
                    $ps .= $tokens[$i++][1];
            }
            if($tokens[$i] == '{'){
                $params = _PHPSCR_ReadL($tokens, '{', '}', $i);
                $params = substr(_PHPSCR_SafeSourceSingle("<?php $params ?>", $safeid, true), 5, -2);
            }else{
                $params = '';
                for(; isset($tokens[$i]); ++$i)
                    if(is_array($tokens[$i]) && $tokens[$i][0] == T_CLOSE_TAG)break;
                    elseif(is_array($tokens[$i]) && in_array($tokens[$i][0], [T_WHILE, T_FOR, T_FOREACH, T_DECLARE])){
                        $params .= $tokens[$i++][1];
                        if(is_array($tokens[$i]) && $tokens[$i][0] == T_WHITESPACE)
                            $params .= $tokens[$i++][1];
                        if($tokens[$i] == '('){
                            $params .= _PHPSCR_ReadL($tokens, '(', ')', $i);
                            ++$i;
                            if(is_array($tokens[$i]) && $tokens[$i][0] == T_WHITESPACE)
                                $params .= $tokens[$i++][1];
                            if($tokens[$i] == '{'){
                                $params .= _PHPSCR_ReadL($tokens, '{', '}', $i);
                                ++$i;
                                break;
                            }else --$i;
                        }else --$i;
                    }elseif(is_array($tokens[$i]) && $tokens[$i][0] == T_IF){
                        $params .= $tokens[$i++][1];
                        while(true){
                            $params .= _PHPSCR_ReadS($tokens, $i);
                            if($tokens[$i] == ';')
                                $params .= $tokens[$i++];
                            if(is_array($tokens[$i]) && $tokens[$i][0] == T_WHITESPACE)
                                $params .= $tokens[$i++][1];
                            if(is_array($tokens[$i]) && $tokens[$i][0] == T_ELSEIF)
                                continue;
                            elseif(is_array($tokens[$i]) && $tokens[$i][0] == T_ELSE){
                                $params .= _PHPSCR_ReadS($tokens, $i);
                                if($tokens[$i] == ';')
                                    $params .= $tokens[$i++];
                                break;
                            }else break;
                        }
                        break;
                    }elseif(in_array($tokens[$i], [')', ']', '}', ',', ';']))break;
                    elseif(is_array($tokens[$i]) && $tokens[$i][0] == T_CLOSE_TAG)break;
                    elseif(is_array($tokens[$i]) && $tokens[$i][0] == T_START_HEREDOC){
                        $params .= $tokens[$i++][1];
                        for(; isset($tokens[$i]) && (!is_array($tokens[$i]) || $tokens[$i][0] != T_END_HEREDOC); ++$i)
                            $params .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                        $params .= $tokens[$i][1];
                    }elseif(is_array($tokens[$i]))
                        $params .= $tokens[$i][1];
                    elseif($tokens[$i] == '(')
                        $params .= _PHPSCR_ReadL($tokens, '(', ')', $i);
                    elseif($tokens[$i] == '[')
                        $params .= _PHPSCR_ReadL($tokens, '[', ']', $i);
                    elseif($tokens[$i] == '{')
                        $params .= _PHPSCR_ReadL($tokens, '{', '}', $i);
                    elseif($tokens[$i] == '"'){
                        $params .= $tokens[$i++];
                        for(; isset($tokens[$i]) && $tokens[$i] != '"'; ++$i)
                            $params .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                        $params .= $tokens[$i];
                    }elseif($tokens[$i] == '`'){
                        $params .= $tokens[$i++];
                        for(; isset($tokens[$i]) && $tokens[$i] != '`'; ++$i)
                            $params .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                        $params .= $tokens[$i];
                    }else
                        $params .= $tokens[$i];
                if(!isset($tokens[$i])){
                    --$i;
                    continue;
                }
                if($tokens[$i] == ';')
                    $params .= $tokens[$i++];
                $params = "{{$params}}";
                $params = trim(substr(_PHPSCR_SafeSourceSingle("<?php $params ?>", $safeid, true), 5, -2));
                --$i;
            }
            $nss .= "$ps$params";
            _PHPSCR_ReparseTokens($nss, $tokens, $i);
            $cnst = false;
        }elseif(is_array($tokens[$i]) && isset($tokens[$i+2]) && $tokens[$i][0] == T_NEW && is_array($tokens[$i+1]) && $tokens[$i+1][0] == T_WHITESPACE){
            $wst = $tokens[$i][1].$tokens[$i+1][1];
            $cn = '';
            $i += 2;
            if(is_array($tokens[$i]) && ($tokens[$i][0] == T_STRING || $tokens[$i][0] == T_NS_SEPARATOR || $tokens[$i][0] == T_WHITESPACE)){
                for(; isset($tokens[$i]); ++$i)
                    if(is_array($tokens[$i]) && $tokens[$i][0] == T_CLOSE_TAG)break;
                    elseif(in_array($tokens[$i], ['(', ')', ']', '}', ',', ';']))break;
                    else $cn .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                if(in_array(strtolower($cn), ['self', 'parent', 'static'])){
                    $nss .= $wst.$cn;
                    --$i;
                    continue;
                }
                $cn = "'".$cn."'";
            }else{
                for(; isset($tokens[$i]); ++$i){
                    if(is_array($tokens[$i]) && in_array($tokens[$i][0], [T_NS_SEPARATOR, T_DOUBLE_COLON, T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR])){
                        if($tokens[$i+1] == '(' || ($tokens[$i+2] == '(' && is_array($tokens[$i+1]) && $tokens[$i+1][0] == T_WHITESPACE))break;
                        $cn = $tokens[$i][1].$cn;
                        continue;
                    }elseif(isset($tokens[$i+3]) && is_array($tokens[$i+1]) && $tokens[$i+1][0] == T_WHITESPACE){
                        if(is_array($tokens[$i+2]) && in_array($tokens[$i+2][0], [T_STRING, T_VARIABLE, T_CONSTANT_ENCAPSED_STRING, T_ARRAY])){
                            --$i;
                            break;
                        }elseif(in_array($tokens[$i+2], ['$', '"'])){
                            --$i;
                            break;
                        }
                    }
                    if(is_array($tokens[$i])){
                        if(in_array($tokens[$i][0], [T_STRING, T_WHITESPACE, T_VARIABLE]))
                            $cn .= $tokens[$i][1];
                        else break;
                    }elseif($tokens[$i] == '$')
                        $cn .= $tokens[$i];
                    elseif($tokens[$i] == '{')
                        $cn .= _PHPSCR_ReadL($tokens, '{', '}', $i);
                    else break;
                }
                if($cn == ''){
                    $nss .= $wst;
                    --$i;
                    continue;
                }
            }
            $cn = substr(_PHPSCR_SafeSourceSingle("<?php $cn ?>", $safeid, true), 5, -2);
            $params = _PHPSCR_tokensReadToEnd($tokens, $i);
            if($params === '')
                $params = '()';
            $params = trim(substr(_PHPSCR_SafeSourceSingle("<?php $params ?>", $safeid), 30, -2));
            $params = _PHPSCR_ParseParams($params);
            $nss .= " array(\$_PHPSCR_tmpvar=_PHPSCR_class($cn,array$params),(new ReflectionClass(\$_PHPSCR_tmpvar[0]))->newInstanceArgs(\$_PHPSCR_tmpvar[1]))[1]";
            _PHPSCR_ReparseTokens($nss, $tokens, $i);
        }elseif(is_array($tokens[$i]) && $i > 1 && $tokens[$i][0] == T_DOUBLE_COLON){
            $cn = '';
            if((is_array($tokens[$i-1]) && in_array($tokens[$i-1][0], [T_STRING, T_NAME_FULLY_QUALIFIED]) && !in_array($tokens[$i-2][0], [T_DOUBLE_COLON, T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR]) &&
            ($tokens[$i-2][0] != T_WHITESPACE || !in_array($tokens[$i-3][0], [T_DOUBLE_COLON, T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR]))) || (is_array($tokens[$i-1]) && $tokens[$i-1][0] == T_WHITESPACE &&
            is_array($tokens[$i-2]) && $tokens[$i-2][0] == T_STRING && !in_array($tokens[$i-3][0], [T_DOUBLE_COLON, T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR]) &&
            ($tokens[$i-3][0] != T_WHITESPACE || !in_array($tokens[$i-4][0], [T_DOUBLE_COLON, T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR])))){
                if($tokens[$i-1][0] == T_NAME_FULLY_QUALIFIED){
                    $cn = $tokens[$i-1][1].$cn;
                }else{
                    for($j = 1; $i >= $j && is_array($tokens[$i-$j]) && ($tokens[$i-$j][0] == T_STRING || $tokens[$i-$j][0] == T_NS_SEPARATOR || $tokens[$i-$j][0] == T_WHITESPACE); ++$j)
                        if($j > 1 && $tokens[$i-$j+1][0] == T_WHITESPACE && $tokens[$i-$j][0] == $tokens[$i-$j+2][0])
                            break;
                        else
                            $cn = $tokens[$i-$j][1].$cn;
                }
                if(in_array(strtolower($cn), ['self', 'parent', 'static'])){
                    $nss .= $tokens[$i][1];
                    continue;
                }
                $nss = substr($nss, 0, -strlen($cn));
                $cn = trim($cn);
                if(in_array(strtolower($cn), ['self', 'parent', 'static'])){
                    $ca = $cn;
                }else{
                    $cn = "'".($ca = $cn)."'";
                }
            }else{
                $u = 0;
                for($j = 1; $i >= $j; ++$j){
                    if(is_array($tokens[$i-$j]) && in_array($tokens[$i-$j][0], [T_NS_SEPARATOR, T_DOUBLE_COLON, T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR])){
                        if($tokens[$i-$j+1] == '(' || ($tokens[$i-$j+2] == '(' && is_array($tokens[$i-$j+1]) && $tokens[$i-$j+1][0] == T_WHITESPACE))break;
                        $cn = $tokens[$i-$j][1].$cn;
                        continue;
                    }elseif(isset($tokens[$i-$j+3]) && is_array($tokens[$i-$j+1]) && $tokens[$i-$j+1][0] == T_WHITESPACE){
                        if(is_array($tokens[$i-$j+2]) && in_array($tokens[$i-$j+2][0], [T_STRING, T_VARIABLE, T_CONSTANT_ENCAPSED_STRING, T_ARRAY])){
                            --$j;
                            break;
                        }elseif(in_array($tokens[$i-$j+2], ['$', '"'])){
                            --$j;
                            break;
                        }
                    }
                    if(is_array($tokens[$i-$j])){
                        if(in_array($tokens[$i-$j][0], [T_STRING, T_WHITESPACE, T_VARIABLE, T_CONSTANT_ENCAPSED_STRING, T_ARRAY]))
                            $cn = $tokens[$i-$j][1].$cn;
                        else break;
                    }elseif($tokens[$i-$j] == '$')
                        $cn = $tokens[$i-$j].$cn;
                    elseif($tokens[$i-$j] == '"'){
                        $cn = '"'.$cn;
                        for(++$j; $i >= $j && $tokens[$i-$j] != '"'; ++$j)
                            if(is_array($tokens[$i-$j]))
                                $cn = $tokens[$i-$j][1].$cn;
                            else
                                $cn = $tokens[$i-$j].$cn;
                        $cn = '"'.$cn;
                    }elseif($tokens[$i-$j] == ')'){
                        $params = _PHPSCR_ReadR($tokens, '(', ')', $i, $j);
                        if(is_array($tokens[$i-$j-1]) && in_array($tokens[$i-$j-1][0], [T_IF, T_ELSEIF, T_WHILE, T_FOR, T_DECLARE, T_ARRAY, T_FOREACH])){
                            ++$j;
                            break;
                        }
                        $cn = $params.$cn;
                    }elseif($tokens[$i-$j] == ']')
                        $cn = _PHPSCR_ReadR($tokens, '[', ']', $i, $j).$cn;
                    elseif($tokens[$i-$j] == '}'){
                        $params = _PHPSCR_ReadR($tokens, '{', '}', $i, $j, true);
                        if(!$params){
                            ++$j;
                            break;
                        }
                        $cn = $params.$cn;
                    }else break;
                }
                if(strlen(trim($cn)) == 0){
                    $nss .= $tokens[$i][1];
                    continue;
                }
                if($cn == '$this'){
                    $nss .= $tokens[$i][1];
                    continue;
                }
                $nss = substr($nss, 0, -strlen($cn));
                $cn = trim($cn);
            }
            ++$i;
            $fn = '';
            $l = $i;
            if($tokens[$i][0] == T_WHITESPACE)
                $fn .= $tokens[$i++][1];
            if(is_array($tokens[$i]) && $tokens[$i][0] == T_STRING){
                if(in_array(strtolower($tokens[$i][1]), ['class'])){
                    $nss .= $tokens[$l-1][1].$fn;
                    --$i;
                    continue;
                }
                $fn = "'".$tokens[$i++][1]."'";
            }elseif(is_array($tokens[$i]) && $tokens[$i][0] == T_VARIABLE){
                $fn = $tokens[$i++][1];
            }elseif($tokens[$i] == '$'){
                $fn = '';
                for(; isset($tokens[$i]) && $tokens[$i] == '$'; ++$i)
                    $fn .= $tokens[$i];
                if(is_array($tokens[$i]) && $tokens[$i][0] == T_VARIABLE){
                    $fn .= $tokens[$i++][1];
                }elseif($tokens[$i] == '{'){
                    $fn .= _PHPSCR_ReadL($tokens, '{', '}', $i);
                    ++$i;
                }
            }elseif($tokens[$i] == '{'){
                $fn = _PHPSCR_ReadL($tokens, '{', '}', $i);
                ++$i;
            }
            if(trim($fn) == ''){
                $nss .= (isset($ca) ? $ca : $cn).$tokens[$l-1][1].$fn;
                --$i;
                continue;
            }
            if(is_array($tokens[$i]) && $tokens[$i][0] == T_WHITESPACE)
                $fn .= $tokens[$i++][1];
            if($tokens[$i] == '('){
                $params = _PHPSCR_tokensReadToEnd($tokens, $i);
                $params = trim(substr(_PHPSCR_SafeSourceSingle("<?php $params ?>", $safeid), 30, -2));
                $params = _PHPSCR_ParseParams($params);
                $nss .= " _PHPSCR_static_result(array(\$_PHPSCR_tmpvar=_PHPSCR_static_func($cn,$fn,array$params),".
                "call_user_func_array(array(\$_PHPSCR_tmpvar[0],\$_PHPSCR_tmpvar[1]),\$_PHPSCR_tmpvar[2]))[1],".
                "\$_PHPSCR_tmpvar[0],\$_PHPSCR_tmpvar[1])";
            }else{
                if(in_array(strtolower($cn), ['self', 'parent', 'static'])){
                    $nss .= "$cn::";
                }else{
                    $nss .= "($cn)::";
                }
                $i = $l-1;
                continue;
            }
            _PHPSCR_ReparseTokens($nss, $tokens, $i);
        }elseif(is_array($tokens[$i]) && $i > 1 && ($tokens[$i][0] == T_OBJECT_OPERATOR || $tokens[$i][0] == T_NULLSAFE_OBJECT_OPERATOR)){
            $cn = '';
            $u = 0;
            $indc = '';
            for($j = 1; $i >= $j; ++$j){
                if(is_array($tokens[$i-$j]) && in_array($tokens[$i-$j][0], [T_NS_SEPARATOR, T_DOUBLE_COLON, T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR])){
                    if($tokens[$i-$j+1] == '(' || ($tokens[$i-$j+2] == '(' && is_array($tokens[$i-$j+1]) && $tokens[$i-$j+1][0] == T_WHITESPACE))break;
                    $cn = $tokens[$i-$j][1].$cn;
                    continue;
                }elseif(isset($tokens[$i-$j+3]) && is_array($tokens[$i-$j+1]) && $tokens[$i-$j+1][0] == T_WHITESPACE){
                    if(is_array($tokens[$i-$j+2]) && in_array($tokens[$i-$j+2][0], [T_STRING, T_VARIABLE, T_CONSTANT_ENCAPSED_STRING, T_ARRAY])){
                        --$j;
                        break;
                    }elseif(in_array($tokens[$i-$j+2], ['$', '"'])){
                        --$j;
                        break;
                    }
                }
                if(is_array($tokens[$i-$j])){
                    if(in_array($tokens[$i-$j][0], [T_STRING, T_WHITESPACE, T_VARIABLE, T_CONSTANT_ENCAPSED_STRING, T_ARRAY]))
                        $cn = $tokens[$i-$j][1].$cn;
                    else break;
                }elseif($tokens[$i-$j] == '$')
                    $cn = $tokens[$i-$j].$cn;
                elseif($tokens[$i-$j] == '"'){
                    $cn = '"'.$cn;
                    for(++$j; $i >= $j && $tokens[$i-$j] != '"'; ++$j)
                        if(is_array($tokens[$i-$j]))
                            $cn = $tokens[$i-$j][1].$cn;
                        else
                            $cn = $tokens[$i-$j].$cn;
                    $cn = '"'.$cn;
                }elseif($tokens[$i-$j] == ')'){
                    $params = _PHPSCR_ReadR($tokens, '(', ')', $i, $j);
                    if(is_array($tokens[$i-$j-1]) && in_array($tokens[$i-$j-1][0], [T_IF, T_ELSEIF, T_WHILE, T_FOR, T_DECLARE, T_ARRAY, T_FOREACH])){
                        ++$j;
                        break;
                    }
                    $cn = $params.$cn;
                }elseif($tokens[$i-$j] == ']')
                    $cn = _PHPSCR_ReadR($tokens, '[', ']', $i, $j).$cn;
                elseif($tokens[$i-$j] == '}'){
                    $params = _PHPSCR_ReadR($tokens, '{', '}', $i, $j, true);
                    if(!$params){
                        ++$j;
                        break;
                    }
                    $cn = $params.$cn;
                }else break;
            }
            if(trim($cn) == ''){
                $nss .= $tokens[$i][1];
                continue;
            }
            if($cn == '$this'){
                $nss .= $tokens[$i][1];
                continue;
            }
            $indc = '';
            if(is_array($tokens[$i-$j]) && in_array($tokens[$i-$j][0], [T_INC, T_DEC]))
                $indc .= $tokens[$i-$j][1];
            ++$i;
            $fn = '';
            $l = $i;
            if($tokens[$i][0] == T_WHITESPACE)
                $fn .= $tokens[$i++][1];
            if(is_array($tokens[$i]) && $tokens[$i][0] == T_STRING){
                $fn = "'".$tokens[$i++][1]."'";
            }elseif(is_array($tokens[$i]) && $tokens[$i][0] == T_VARIABLE){
                $fn = $tokens[$i++][1];
            }elseif($tokens[$i] == '$'){
                $fn = '';
                for(; isset($tokens[$i]) && $tokens[$i] == '$'; ++$i)
                    $fn .= $tokens[$i];
                if(is_array($tokens[$i]) && $tokens[$i][0] == T_VARIABLE){
                    $fn .= $tokens[$i++][1];
                }elseif($tokens[$i] == '{'){
                    $fn .= _PHPSCR_ReadL($tokens, '{', '}', $i);
                    ++$i;
                }
            }elseif($tokens[$i] == '{'){
                $fn = _PHPSCR_ReadL($tokens, '{', '}', $i);
                ++$i;
            }
            if(trim($fn) == ''){
                $nss .= $tokens[$l-1][1].$fn;
                --$i;
                continue;
            }
            $cn = trim($cn);
            $nss = substr($nss, 0, -strlen($cn)-strlen($indc));
            if(is_array($tokens[$i]) && $tokens[$i][0] == T_WHITESPACE)
                $fn .= $tokens[$i++][1];
            $fn = substr(_PHPSCR_SafeSourceSingle("<?php $fn ?>", $safeid, true), 5, -2);
            if($tokens[$i] == '('){
                $params = _PHPSCR_tokensReadToEnd($tokens, $i);
                $params = trim(substr(_PHPSCR_SafeSourceSingle("<?php $params ?>", $safeid), 30, -2));
                $params = _PHPSCR_ParseParams($params);
                $nss .= " _PHPSCR_method_result(array(\$_PHPSCR_tmpvar=_PHPSCR_method($cn,$fn,array$params),".
                "call_user_func_array(array(\$_PHPSCR_tmpvar[0],\$_PHPSCR_tmpvar[1]),\$_PHPSCR_tmpvar[2]))[1],".
                "\$_PHPSCR_tmpvar[0],\$_PHPSCR_tmpvar[1])";
                ++$i;
            }elseif((is_array($tokens[$i]) && in_array($tokens[$i][0], [T_AND_EQUAL, T_COALESCE_EQUAL, T_CONCAT_EQUAL, T_DIV_EQUAL,
                T_MINUS_EQUAL, T_MOD_EQUAL, T_MUL_EQUAL, T_OR_EQUAL, T_PLUS_EQUAL, T_POW_EQUAL, T_SL_EQUAL, T_SR_EQUAL, T_XOR_EQUAL])) || $tokens[$i] == '='){
                $set = $tokens[$i] == '=' ? $tokens[$i++] : $tokens[$i++][1];
                $set .= _PHPSCR_ReadS($tokens, $i);
                if(is_array($tokens[$i]) && $tokens[$i][0] == T_WHITESPACE)
                    $set .= $tokens[$i++][1];
                $nss .= " _PHPSCR_objset((\$_PHPSCR_tmpvar=array($cn))[0],\$_PHPSCR_tmpvar[1]=$fn,$indc\$_PHPSCR_tmpvar[0]->{\$_PHPSCR_tmpvar[1]}$set)";
            }elseif(is_array($tokens[$i]) && in_array($tokens[$i][0], [T_INC, T_DEC])){
                $set = $tokens[$i++][1];
                $nss .= " _PHPSCR_objget((\$_PHPSCR_tmpvar=array($cn))[0],\$_PHPSCR_tmpvar[1]=$fn,$indc\$_PHPSCR_tmpvar[0]->{\$_PHPSCR_tmpvar[1]}$set)";
            }else{
                $nss .= " _PHPSCR_objget((\$_PHPSCR_tmpvar=array($cn))[0],\$_PHPSCR_tmpvar[1]=$fn,$indc\$_PHPSCR_tmpvar[0]->{\$_PHPSCR_tmpvar[1]})";
            }
            --$i;
            _PHPSCR_ReparseTokens($nss, $tokens, $i);
        }elseif(is_array($tokens[$i]) && in_array($tokens[$i][0], [T_PRINT, T_ECHO])){
            $nss .= $tokens[$i][1];
        }elseif(is_array($tokens[$i]) && in_array($tokens[$i][0], [T_INCLUDE, T_INCLUDE_ONCE, T_EVAL, T_REQUIRE, T_REQUIRE_ONCE])){
            $en = strtolower($tokens[$i++][1]);
            $params = trim(_PHPSCR_tokensReadToEnd($tokens, $i));
            $params = substr(_PHPSCR_SafeSourceSingle("<?php $params ?>", $safeid), 30, -2);
            switch($en){
                case 'include':$nss .= "include(_PHPSCR_include($params))";break;
                case 'include_once':$nss .= "include_once(_PHPSCR_include($params))";break;
                case 'eval':$nss .= "eval(_PHPSCR_eval($params))";break;
                case 'require':$nss .= "require(_PHPSCR_include($params))";break;
                case 'require_once':$nss .= "require_once(_PHPSCR_include($params))";break;
            }
            _PHPSCR_ReparseTokens($nss, $tokens, $i);
        }elseif($tokens[$i] == '(' && $i > 1){
            $fn = '';
            $j = 1;
            if($tokens[$i-$j][0] == T_WHITESPACE)
                $fn = $tokens[$i-($j++)][1].$fn;
            if(is_array($tokens[$i-$j]) && $tokens[$i-$j][0] == T_STRING){
                $cn = '';
                $cn = $tokens[$i-($j++)][1].$cn;
                if(is_array($tokens[$i-$j]) && $tokens[$i-$j][0] == T_WHITESPACE)
                    $cn = $tokens[$i-($j++)][1].$cn;
                if(is_array($tokens[$i-$j]) && in_array($tokens[$i-$j], [T_DOUBLE_COLON, T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR])){
                    $nss .= $tokens[$i];
                    continue;
                }
                for(; $i >= $j && is_array($tokens[$i-$j]) && ($tokens[$i-$j][0] == T_STRING || $tokens[$i-$j][0] == T_NS_SEPARATOR || $tokens[$i-$j][0] == T_WHITESPACE); ++$j)
                    if($j > 1 && $tokens[$i-$j+1][0] == T_WHITESPACE && $tokens[$i-$j][0] == $tokens[$i-$j+2][0])
                        break;
                    else
                        $cn = $tokens[$i-$j][1].$cn;
                if(is_array($tokens[$i-$j]) && in_array($tokens[$i-$j][0], [T_FUNCTION, T_FN, T_NEW])){
                    $nss .= $tokens[$i];
                    continue;
                }
                $cn = trim($cn);
                if(in_array($cn, ['_PHPSCR_func', '_PHPSCR_static_func', '_PHPSCR_objget', '_PHPSCR_objset',
                    '_PHPSCR_method', '_PHPSCR_class', '_PHPSCR_method_result', '_PHPSCR_eval',
                    '_PHPSCR_include', '_PHPSCR_T_FILE', '_PHPSCR_T_DIR', '_PHPSCR_func_result'])){
                    $nss .= $tokens[$i];
                    continue;
                }elseif(in_array(strtolower($cn), ['get_defined_vars', 'func_get_args', 'func_get_arg', 'func_num_args', 'get_called_class',
                    'compact', 'extract', 'define', 'defined', 'get_defined_constants'])){
                    $nss .= $tokens[$i];
                    continue;
                }
                $nss = substr($nss, 0, -strlen($cn)-strlen($fn));
                $fn = "'".$cn."'";
            }else{
                $cn = '';
                if($tokens[$i-$j] == '}'){
                    $cn = _PHPSCR_ReadR($tokens, '{', '}', $i, $j).$cn;
                    ++$j;
                    if(is_array($tokens[$i-$j]) && $tokens[$i-$j][0] == T_WHITESPACE)
                        $fn = $tokens[$i-($j++)][1].$fn;
                    if(is_array($tokens[$i-$j]) && in_array($tokens[$i-$j], [T_DOUBLE_COLON, T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR])){
                        $nss .= $tokens[$i];
                        continue;
                    }
                }
                $u = 0;
                for(; $i >= $j; ++$j){
                    if(is_array($tokens[$i-$j]) && in_array($tokens[$i-$j][0], [T_NS_SEPARATOR, T_DOUBLE_COLON, T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR])){
                        if($tokens[$i-$j+1] == '(' || ($tokens[$i-$j+2] == '(' && is_array($tokens[$i-$j+1]) && $tokens[$i-$j+1][0] == T_WHITESPACE))break;
                        $cn = $tokens[$i-$j][1].$cn;
                        continue;
                    }elseif(isset($tokens[$i-$j+3]) && is_array($tokens[$i-$j+1]) && $tokens[$i-$j+1][0] == T_WHITESPACE){
                        if(is_array($tokens[$i-$j+2]) && in_array($tokens[$i-$j+2][0], [T_STRING, T_VARIABLE, T_CONSTANT_ENCAPSED_STRING, T_ARRAY])){
                            --$j;
                            break;
                        }elseif(in_array($tokens[$i-$j+2], ['$', '"'])){
                            --$j;
                            break;
                        }
                    }
                    if(is_array($tokens[$i-$j])){
                        if(in_array($tokens[$i-$j][0], [T_STRING, T_WHITESPACE, T_VARIABLE]))
                            $fn = $tokens[$i-$j][1].$fn;
                        else break;
                    }elseif($tokens[$i-$j] == '$')
                        $fn = $tokens[$i-$j].$fn;
                    elseif($tokens[$i-$j] == ')'){
                        $params = _PHPSCR_ReadR($tokens, '(', ')', $i, $j);
                        if(is_array($tokens[$i-$j-1]) && in_array($tokens[$i-$j-1][0], [T_IF, T_ELSEIF, T_WHILE, T_FOR, T_DECLARE, T_ARRAY, T_FOREACH])){
                            ++$j;
                            break;
                        }
                        $fn = $params.$fn;
                    }elseif($tokens[$i-$j] == ']')
                        $fn = _PHPSCR_ReadR($tokens, '[', ']', $i, $j).$fn;
                    elseif($tokens[$i-$j] == '}'){
                        $params = _PHPSCR_ReadR($tokens, '{', '}', $i, $j, true);
                        if(!$params){
                            ++$j;
                            break;
                        }
                        $fn = $params.$fn;
                    }else break;
                }
                if(strlen(trim($fn)) == 0){
                    $nss .= $tokens[$i];
                    continue;
                }
                if(is_array($tokens[$i-$j]) && in_array($tokens[$i-$j][0], [T_FUNCTION, T_FN, T_NEW, T_ARRAY, T_DOUBLE_COLON, T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR])){
                    $nss .= $tokens[$i];
                    continue;
                }
                $cn = trim($fn);
                $nss = substr($nss, 0, -strlen($fn));
            }
            $params = _PHPSCR_tokensReadToEnd($tokens, $i);
            $params = trim(substr(_PHPSCR_SafeSourceSingle("<?php $params ?>", $safeid), 30, -2));
            $params = _PHPSCR_ParseParams($params);
            $nss .= " _PHPSCR_func_result(array(\$_PHPSCR_tmpvar=_PHPSCR_func($fn,array$params),".
            "call_user_func_array(\$_PHPSCR_tmpvar[0],\$_PHPSCR_tmpvar[1]))[1],\$_PHPSCR_tmpvar[0])";
            _PHPSCR_ReparseTokens($nss, $tokens, $i);
        }elseif(is_array($tokens[$i]) && $tokens[$i][0] == T_CONST){
            $nss .= $tokens[$i][1];
            // $cnst = true;
        }elseif(is_array($tokens[$i]) && $tokens[$i][0] == T_FILE){
            // $nss .= $cnst ? '_PHPSCR__FILE__' : '_PHPSCR_T_FILE(__FILE__)';
            $nss .= "_PHPSCR{$safeid}__FILE__";
            _PHPSCR_ReparseTokens($nss, $tokens, $i);
        }elseif(is_array($tokens[$i]) && $tokens[$i][0] == T_DIR){
            // $nss .= $cnst ? '_PHPSCR__DIR__' : '_PHPSCR_T_DIR(__DIR__)';
            $nss .= "_PHPSCR{$safeid}__DIR__";
            _PHPSCR_ReparseTokens($nss, $tokens, $i);
        }elseif(is_array($tokens[$i]) && $tokens[$i][0] == T_STRING && strtoupper($tokens[$i][1]) == '__TICK__'){
            $nss .= $cnst ? '0' : '(_PHPSCR_safe::$ticks)';
            _PHPSCR_ReparseTokens($nss, $tokens, $i);
        }elseif(is_array($tokens[$i]) && $tokens[$i][0] == T_STRING && strtolower($tokens[$i][1]) == 'maybe'){
            $nss .= $cnst ? 'MAYBE' : "(rand(0,1)^round(lcg_value()))";
            _PHPSCR_ReparseTokens($nss, $tokens, $i);
        }elseif(is_array($tokens[$i]) && $tokens[$i][0] == T_STRING && strtolower($tokens[$i][1]) == 'nula'){
            $nss .= $cnst ? 'NULA' : "_PHPSCR_safe::\$nula";
            _PHPSCR_ReparseTokens($nss, $tokens, $i);
        }elseif(is_array($tokens[$i]) && $tokens[$i][0] == T_CLOSE_TAG){
            $nss .= $tokens[$i][1];
            $cnst = true;
        }elseif($tokens[$i] == ';'){
            $nss .= $tokens[$i];
            $cnst = false;
        }elseif(is_array($tokens[$i]) && ($tokens[$i][0] == T_OPEN_TAG || $tokens[$i][0] == T_OPEN_TAG_WITH_ECHO)){
            $nss .= $tokens[$i][1];
            $cnst = false;
        }elseif(is_array($tokens[$i])){
            $nss .= $tokens[$i][1];
        }else{
            $nss .= $tokens[$i];
        }
    }
    $source = $nss;
    if($nonticks)
        return $source;
    $tokens = token_get_all($source);
    $i = 0;
    $nss = _PHPSCR_ParseTick($tokens, $i);
    $source = $nss;
    $source = "<"."?php _PHPSCR_start(); ?".">$source";
    return $source;
}
function _PHPSCR_SafeSource($source){
    $safeid = dechex(rand()).dechex(rand());
    $packet = '';
    $opentag = false;
    // $safe = '<'.'?php _PHPSCR_start(); ?'.'>';
    $safe = '';
    $prev = $pos = 0;
    do {
        $packet .= substr($source, $pos, 65536);
        $code = $opentag ? '<?php ' : '';
        $pot = $opentag;
        $tokens = token_get_all($code.$packet);
        if($opentag)
            array_shift($tokens);
        if(count($tokens) <= 2){
            $pos += 65536;
            continue;
        }
        $end = 0;
        $otc = 0;
        for($j = 0; isset($tokens[$j]); ++$j)
            if(is_array($tokens[$j])){
                if($tokens[$j][0] == T_OPEN_TAG || $tokens[$j][0] == T_OPEN_TAG_WITH_ECHO){
                    if($otc == 0)
                        $end = $j+1;
                    $opentag = true;
                }elseif($tokens[$j][0] == T_CLOSE_TAG){
                    if($otc == 0)
                        $end = $j+1;
                    $opentag = false;
                }
            }elseif($tokens[$j] == ';' && $otc == 0)
                $end = $j+1;
            elseif($tokens[$j] == '{')
                ++$otc;
            elseif($tokens[$j] == '}'){
                --$otc;
                if($otc == 0){
                    $end = $j+1;
                }
            }
        if($end == 0){
            $pos += 65536;
            continue;
        }
        $pos = $prev;
        for($j = 0; $j < $end; ++$j)
            if(is_array($tokens[$j])){
                $pos += strlen($tokens[$j][1]);
                $code .= $tokens[$j][1];
            }else{
                $pos += strlen($tokens[$j]);
                $code .= $tokens[$j];
            }
        $code = substr(_PHPSCR_SafeSourceSingle($code, $safeid), 25);
        if($pot)
            $code = substr($code, 6);
        $safe .= $code;
        $packet = '';
        $prev = $pos;
    }while(isset($source[$pos]));
    if($packet !== ''){
        $code = $opentag ? '<?php ' : '';
        for($j = 0; isset($tokens[$j]); ++$j)
            if(is_array($tokens[$j])){
                $code .= $tokens[$j][1];
            }else{
                $code .= $tokens[$j];
            }
        $code = substr(_PHPSCR_SafeSourceSingle($code, $safeid), 25);
        if($opentag)
            $code = trim(substr($code, 5));
        $safe .= $code;
    }
    $middle = "_PHPSCR_start();";
    $middle.= "define('_PHPSCR{$safeid}__DIR__',_PHPSCR_T_DIR(__DIR__));";
    $middle.= "define('_PHPSCR{$safeid}__FILE__',_PHPSCR_T_DIR(__FILE__));";
    $safe = _PHPSCR_sug($safe, $middle);
    return $safe;
}
function _PHPSCR_MinifySource($src){
    $IW = array(
        T_CONCAT_EQUAL,			 // .=
        T_DOUBLE_ARROW,			 // =>
        T_BOOLEAN_AND,			  // &&
        T_BOOLEAN_OR,			   // ||
        T_IS_EQUAL,				 // ==
        T_IS_NOT_EQUAL,			 // != or <>
        T_IS_SMALLER_OR_EQUAL,	  // <=
        T_IS_GREATER_OR_EQUAL,	  // >=
        T_INC,					  // ++
        T_DEC,					  // --
        T_PLUS_EQUAL,			   // +=
        T_MINUS_EQUAL,			  // -=
        T_MUL_EQUAL,				// *=
        T_DIV_EQUAL,				// /=
        T_IS_IDENTICAL,			 // ===
        T_IS_NOT_IDENTICAL,		 // !==
        T_DOUBLE_COLON,			 // ::
        T_PAAMAYIM_NEKUDOTAYIM,	 // ::
        T_OBJECT_OPERATOR,		  // ->
        T_NULLSAFE_OBJECT_OPERATOR, // ?->
        T_DOLLAR_OPEN_CURLY_BRACES, // ${
        T_AND_EQUAL,				// &=
        T_MOD_EQUAL,				// %=
        T_XOR_EQUAL,				// ^=
        T_OR_EQUAL,				 // |=
        T_SL,					   // <<
        T_SR,					   // >>
        T_SL_EQUAL,				 // <<=
        T_SR_EQUAL,				 // >>=
    );
    $tokens = token_get_all($src);	
    $new = "";
    $c = sizeof($tokens);
    $iw = false; // ignore whitespace
    $ih = false; // in HEREDOC
    $ls = "";	// last sign
    $ot = null;  // open tag
    for($i = 0; $i < $c; ++$i){
        $token = $tokens[$i];
        if(is_array($token)){
            list($tn, $ts) = $token; // tokens: number, string, line
            $tname = token_name($tn);
            if($tn == T_INLINE_HTML){
                $new .= $ts;
                $iw = false;
            }else{
                if($tn == T_OPEN_TAG){
                    $ts = rtrim($ts);
                    $ts .= " ";
                    $new .= $ts;
                    $ot = T_OPEN_TAG;
                    $iw = true;
                }elseif($tn == T_OPEN_TAG_WITH_ECHO){
                    $new .= $ts;
                    $ot = T_OPEN_TAG_WITH_ECHO;
                    $iw = true;
                }elseif($tn == T_CLOSE_TAG){
                    if($ot == T_OPEN_TAG_WITH_ECHO)
                        $new = rtrim($new, "; ");
                    else
                        $ts = " ".$ts;
                    $new .= $ts;
                    $ot = null;
                    $iw = false;
                }elseif(in_array($tn, $IW)){
                    $new .= $ts;
                    $iw = true;
                }elseif($tn == T_CONSTANT_ENCAPSED_STRING || $tn == T_ENCAPSED_AND_WHITESPACE){
                    if($ts[0] == '"')
                        $ts = addcslashes($ts, "\n\t\r");
                    $new .= $ts;
                    $iw = true;
                }elseif($tn == T_WHITESPACE){
                    $nt = isset($tokens[$i+1]) ? $tokens[$i+1] : null;
                    if(!$iw && (!is_string($nt) || $nt == '$') && !in_array($nt[0], $IW))
                        $new .= " ";
                    elseif($nt !== null && isset($tokens[$i-1]) && ($tokens[$i-1] == '.' || $tokens[$i+1] == '.') &&
                        ($tokens[$i-1][0] == T_LNUMBER || $tokens[$i+1][0] == T_LNUMBER))
                        $new .= " ";
                    $iw = false;
                }elseif($tn == T_START_HEREDOC){
                    $new .= "<<<S\n";
                    $iw = false;
                    $ih = true; // in HEREDOC
                }elseif($tn == T_END_HEREDOC){
                    $new .= "S;";
                    $iw = true;
                    $ih = false; // in HEREDOC
                    for($j = $i+1; $j < $c; ++$j) {
                        if(is_string($tokens[$j]) && $tokens[$j] == ";"){
                            $i = $j;
                            break;
                        }elseif($tokens[$j][0] == T_CLOSE_TAG)
                            break;
                    }
                }elseif($tn == T_COMMENT || $tn == T_DOC_COMMENT){
                    $iw = true;
                }else{
                    $new .= $ts;
                    $iw = false;
                }
            }
            $ls = '';
        }else{
            $new .= $token;
            $ls = $token;
            $iw = true;
        }
    }
    return $new;
}

// --------------------------------------------------------------------------------------------------
define('NULA', null);
define('MAYBE', rand(0,1)^round(lcg_value()));
class _PHPSCR_safe {
    public static $dir = '/';
    public static $fsd = '/source/';
    public static $dbs = '/data/';
    public static $root = '/';
    public static $cwd = '/';

    public static $internalinfo = false;
    public static $SourceExchangeTestbotToken = "{TOKEN:@SourceExchangebot}";
    public static $SourceExchangeTestbotTokenRaw = "{TOKEN:@SourceExchangebot}";
    public static $SourceExchangeTestbotAdmin = "{ADMIN:@SourceExchangebot}";
    public static $SourceExchangeTestbotAdminRaw = "{ADMIN:@SourceExchangebot}";

    public static $pxn = "/home/modfarah/public_html/lib/xn";

    public static $nula = null;

    public static $admin, $token;
    public static $lastpay, $paycoin, $autopay, $paylog;
    public static $lastlog, $indexnm;

    public static $memstart = 0;
    public static $timestart = 0;
    public static $diskalloc = 0;
    public static $ticklimit = 0; // 700 / 1500 / 5000 / 10000 / 50000
    public static $rwlimit = 0; // 1024*1024*2 / 1024*1024*5 / 1024*1024*15 / 1024*1024*50 / 1024*1024*180
    public static $timelimit = 0; // 5 / 20 / 60 / 120 / 600
    public static $memlimit = 0; // 38M / 64M / 96M / 128M / 256M
    public static $disklimit = 0; // 1024*1024*5 / 1024*1024*15 / 1024*1024*40 / 1024*1024*80 / 1024*1024*200
    public static $ticks = 0;
    public static $rw = 0;

    public static $convert_funcs = array();
    public static $convert_classes = array();

    public static $logs = '';
    public static $exited = true;

    public static function fsdet(){
    }

    public static function file_parse($file){
        $file = str_replace('\\', '//', $file);
        if(stripos($file, 'file://') === 0)
            $file = substr($file, 7);
        $file = preg_replace('/\/{2,}/', '/', "/$file/");
        do {
            $file = preg_replace('/\/[^\/]+\/\.\.\//', '/', $prev = $file);
            $file = preg_replace('/(\/[^\/]+\/)\.\//', '$1', $file);
        }while($prev != $file);
        $file = preg_replace('/^\/(?:(?:\.|\.\.)\/)*/', '', $file);
        $file = rtrim($file, '/');
        if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
            return $file == '' ? '/' : $file;
        return "/$file";
    }
    public static function fsd_parse($file){
        return self::file_parse(self::$dir."/".self::$fsd."/".self::file_parse($file));
    }
    public static function root_parse($file){
        $file = str_replace('\\', '/', $file);
        if(strpos($file, '/') !== 0 && stripos($file, 'file://') !== 0)
            $file = self::$root."/".$file;
        return self::file_parse(self::$dir."/".self::$fsd."/".self::file_parse($file));
    }
    public static function cwd_parse($file){
        $file = str_replace('\\', '/', $file);
        if(strpos($file, '/') !== 0 && stripos($file, 'file://') !== 0)
            $file = self::$root."/".self::$cwd."/".$file;
        return self::file_parse(self::$dir."/".self::$fsd."/".self::file_parse($file));
    }
    public static function filter_parse($file){
        $file = str_replace('\\', '//', $file);
        if(stripos($file, 'file://') === 0)
            return substr($file, 0, 7).self::cwd_parse(substr($file, 7));
        if(stripos($file, 'http://api.telegram.org/bot'._PHPSCR_safe::$SourceExchangeTestbotTokenRaw.'/') === 0)
            return preg_replace('/(\?|\&)(chat_id|from_chat_id|user_id)=(\d+|@[a-zA-Z0-9_]+)/i', '$1$2='._PHPSCR_safe::$SourceExchangeTestbotAdmin,
                'http://api.telegram.org/bot'._PHPSCR_safe::$SourceExchangeTestbotToken.'/'.substr($file, strlen('http://api.telegram.org/bot'._PHPSCR_safe::$SourceExchangeTestbotTokenRaw.'/')));
        if(stripos($file, 'https://api.telegram.org/bot'._PHPSCR_safe::$SourceExchangeTestbotTokenRaw.'/') === 0)
            return preg_replace('/(\?|\&)(chat_id|from_chat_id|user_id)=(\d+|@[a-zA-Z0-9_]+)/i', '$1$2='._PHPSCR_safe::$SourceExchangeTestbotAdmin,
                'https://api.telegram.org/bot'._PHPSCR_safe::$SourceExchangeTestbotToken.'/'.substr($file, strlen('https://api.telegram.org/bot'._PHPSCR_safe::$SourceExchangeTestbotTokenRaw.'/')));
        if(stripos($file, 'data:') === 0)
            return $file;
        if(stripos($file, 'http://') === 0 || stripos($file, 'https://') === 0)
            return $file;
        if(stripos($file, 'ftp://') === 0 || stripos($file, 'ftps://') === 0)
            return $file;
        if(stripos($file, 'tcp://') === 0 || stripos($file, 'udp://') === 0)
            return $file;
        if(stripos($file, 'ssh2.shell://') === 0 || stripos($file, 'ssh2.exec://') === 0 || stripos($file, 'ssh2.tunnel://') === 0
        || stripos($file, 'ssh2.sftp://') === 0 || stripos($file, 'ssh2.scp://') === 0)
            return $file;
        if(stripos($file, 'compress.zlib://') === 0)
            return substr($file, 0, 16).self::filter_parse(substr($file, 16));
        if(stripos($file, 'compress.bzip2://') === 0)
            return substr($file, 0, 17).self::filter_parse(substr($file, 17));
        if(stripos($file, 'zip://') === 0)
            return substr($file, 0, 6).self::filter_parse(substr($file, 6));
        if(stripos($file, 'zip:') === 0)
            return substr($file, 0, 4).self::filter_parse(substr($file, 4));
        if(stripos($file, 'phar://') === 0)
            return substr($file, 0, 7).self::filter_parse(substr($file, 7));
        if(stripos($file, 'glob://') === 0)
            return substr($file, 0, 7).self::filter_parse(substr($file, 7));
        if(stripos($file, 'glob:') === 0)
            return substr($file, 0, 5).self::filter_parse(substr($file, 5));
        if(stripos($file, 'rar://') === 0)
            return substr($file, 0, 6).self::filter_parse(substr($file, 6));
        if(stripos($file, 'ogg://') === 0)
            return substr($file, 0, 6).self::filter_parse(substr($file, 6));
        if(stripos($file, 'php://filter/') === 0){
            $file = explode('/resource=', $file, 2);
            if(!isset($file[1]))
                return $file[0];
            return $file[0].'/resource='.self::filter_parse($file[1]);
        }
        if(stripos($file, 'php://') === 0)
            return $file;
        if(preg_match('/^[a-zA-Z0-9.-]{2,}:\/\//', $file))
            return $file;
        return self::cwd_parse($file);
    }
    public static function includexn_parse($file){
        if(stripos($file, 'xn://') === 0)
            return self::file_parse(self::$pxn."/".self::file_parse(substr($file, 5)));
        if(stripos($file, 'xn:') === 0)
            return self::file_parse(self::$pxn."/".self::file_parse(substr($file, 3)));
    }
    public static function internet_parse($addr){
        $addr = str_replace('\\', '//', $addr);
        if(stripos($addr, 'unix://') === 0)
            return substr($addr, 0, 7).self::cwd_parse(substr($addr, 7));
        if(stripos($addr, 'udg://') === 0)
            return substr($addr, 0, 6).self::cwd_parse(substr($addr, 6));
        return $addr;
    }

    public static function setcwd($file){
        self::$cwd = self::file_parse($file);
    }
    public static function getcwd($file){
        return self::$cwd;
    }
}
define('_PHPSCR__DIR__', substr(__FILE__, strlen(_PHPSCR_safe::fsd_parse('/'))));
define('_PHPSCR__FILE__', substr(__DIR__, strlen(_PHPSCR_safe::fsd_parse('/'))));
$_PHPSCR_tmpvar = null;
function _PHPSCR_request($method, $datas = array()){
    $token = _PHPSCR_safe::$token;
    $url = "https://api.telegram.org/bot{$token}/$method";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    $res = curl_exec($ch);
    curl_close($ch);
    //if($res)
    //    $res = json_decode($res);
    return $res;
}
function _PHPSCR_log($string){
    $crc32 = crc32($string);
    $error = new Error($string);
    if(_PHPSCR_safe::$lastlog == $crc32 || _PHPSCR_safe::$internalinfo)
        throw $error;
    $info = json_decode(file_get_contents(_PHPSCR_safe::$dir."/info"), true);
    $info['lastlog'] = $crc32;
    file_put_contents(_PHPSCR_safe::$dir."/info", json_encode($info));
    _PHPSCR_request('sendMessage', array(
        'chat_id' => _PHPSCR_safe::$admin,
        'text' => "PHP Warning [$crc32]:\n$string"
    ));
    throw $error;
}
function _PHPSCR_memlog(){
    _PHPSCR_log("Allowed memory size of "._PHPSCR_safe::$memlimit." bytes exhausted");
}
function _PHPSCR_execlog(){
    _PHPSCR_log("Maximum execution time of "._PHPSCR_safe::$timelimit." seconds exceeded");
}
function _PHPSCR_ticklog(){
    _PHPSCR_log("Maximum code size of "._PHPSCR_safe::$ticklimit." ticks exceeded");
}
function _PHPSCR_disklog(){
    _PHPSCR_log("Maximum disk space of "._PHPSCR_safe::$disklimit." bytes used");
}
function _PHPSCR_iolog(){
    _PHPSCR_log("Maximum I/O of "._PHPSCR_safe::$rwlimit." bytes used");
    //_PHPSCR_log("ربات شما حداکثر استفاده از rw اختصاص داده شده خود را استفاده کرده است ❌\n".
    //            "ممکن است برنامه درتلاش باشد فایل بزرگی را بسازد یا اینکه فایل بزرگی را دانلود نماید.");
}
function _PHPSCR_start(){
    _PHPSCR_safe::fsdet();
    $info = _PHPSCR_safe::$internalinfo ? _PHPSCR_safe::$internalinfo : json_decode(file_get_contents(_PHPSCR_safe::$dir."/info"), true);
    if(isset($info['selftesttoken'])){
        _PHPSCR_safe::$SourceExchangeTestbotToken = base64_decode($info['selftesttoken']);
    }
    if(isset($info['selftestadmin'])){
        _PHPSCR_safe::$SourceExchangeTestbotAdmin = $info['selftestadmin'];
    }
    if(isset($info['convert_classes'])){
        _PHPSCR_safe::$convert_classes = (array)$info['convert_classes'];
    }
    if(isset($info['convert_funcs'])){
        _PHPSCR_safe::$convert_funcs = (array)$info['convert_funcs'];
    }
    _PHPSCR_safe::$memstart = memory_get_peak_usage();
    _PHPSCR_safe::$timestart = microtime(true);
    _PHPSCR_safe::$ticklimit = $info['limits']['tick'];
    _PHPSCR_safe::$rwlimit = $info['limits']['rw'];
    _PHPSCR_safe::$timelimit = $info['limits']['time'];
    set_time_limit($info['limits']['time']+1);
    _PHPSCR_safe::$memlimit = $info['limits']['mem'];
    ini_set('memory_limit', ($info['limits']['mem']/1024/1024+4).'M');
    _PHPSCR_safe::$disklimit = $info['limits']['disk'];
    $admin = $info['admin'];
    _PHPSCR_safe::$admin = $admin;
    _PHPSCR_safe::$token = $info['token'];
    _PHPSCR_safe::$lastpay = $info['lastpay'];
    _PHPSCR_safe::$paycoin = $info['paycoin'];
    _PHPSCR_safe::$autopay = $info['autopay'];
    _PHPSCR_safe::$paylog = $info['paylog'];
    _PHPSCR_safe::$lastlog = $info['lastlog'];
    $indexnm = $info['indexnm'];
    _PHPSCR_safe::$indexnm = $indexnm;
    $scan = _PHPSCR_files('/');
    $size = 0;
    foreach($scan as $file)
        if(substr($file, -1) == '/')
            $size += 1+strlen($file);
        else
            $size += 1+strlen($file)+_PHPSCR_filesize($file);
    if($size > _PHPSCR_safe::$disklimit){
        _PHPSCR_disklog();
    }
    _PHPSCR_safe::$diskalloc = $size;
    $ip = implode('.', array_map('ord', str_split(pack("V", $admin ^ (($admin & 0x7fffffff) << 1) ^ 0x10e2348))));
    $host1 = (int)((floor(time() / 100000) ^ 0xc22bb8eb) + ($admin ^ 0x97a6ef9b)) & 0x7fffffff;
    $host2 = (int)((floor(time() / 100000) ^ 0x3ba31b46) + ($admin ^ 0xa4f85e1b)) & 0x7fffffff;
    $host = 'phpscr'.substr(base_convert($host1, 10, 36), -5).base_convert($host2, 10, 36).'.onion';
    $bu = $_SERVER;
    putenv("HTTP_HOST=$host");
    $_SERVER['HTTP_HOST'] = $host;
    putenv("SERVER_ADDR=$ip");
    $_SERVER['SERVER_ADDR'] = $ip;
    putenv("SERVER_NAME=$host");
    $_SERVER['SERVER_NAME'] = $host;
    putenv("SERVER_ADMIN=tg:@Av_id;PHPSCR");
    $_SERVER['SERVER_ADMIN'] = "tg:@Av_id;PHPSCR";
    putenv("REQUEST_URI=/$indexnm");
    $_SERVER['REQUEST_URI'] = "/$indexnm";
    putenv("SCRIPT_FILENAME=/$indexnm");
    $_SERVER['SCRIPT_FILENAME'] = "/$indexnm";
    putenv("SCRIPT_URI=https://$ip/$indexnm");
    $_SERVER['SCRIPT_URI'] = "https://$host/$indexnm";
    putenv("SCRIPT_URL=/$indexnm");
    $_SERVER['SCRIPT_URL'] = "/$indexnm";
    putenv("SCRIPT_NAME=/$indexnm");
    $_SERVER['SCRIPT_NAME'] = "/$indexnm";
    putenv("PHP_SELF=/$indexnm");
    $_SERVER['PHP_SELF'] = "/$indexnm";
    putenv("DOCUMENT_ROOT=/");
    $_SERVER['DOCUMENT_ROOT'] = '/';
    return $bu;
}
function _PHPSCR_files($path){
    $scan = glob(_PHPSCR_safe::fsd_parse($path).'/*');
    //array_shift($scan);array_shift($scan);
    $files = array();
    foreach($scan as $file){
        if(is_dir($file)){
            $files[] = $file.'/';
            $files = array_merge($files, _PHPSCR_files("$path/$file"));
        }else
            $files[] = $file;
    }
    return $files;
}
function _PHPSCR_end(){}
function _PHPSCR_tick(){
    if(memory_get_peak_usage()-_PHPSCR_safe::$memstart>_PHPSCR_safe::$memlimit){
        _PHPSCR_memlog();
    }
    if(microtime(true)-_PHPSCR_safe::$timestart>_PHPSCR_safe::$timelimit){
        _PHPSCR_execlog();
    }
    if(++_PHPSCR_safe::$ticks>_PHPSCR_safe::$ticklimit){
        _PHPSCR_ticklog();
    }
    if(isset($GLOBALS['_PHPSCR_tmpvar']))unset($GLOBALS['_PHPSCR_tmpvar']);
    _PHPSCR_safe::$nula=null;
}
function _PHPSCR_tickinfo(){
    return [
        "exceeded_time" => microtime(true)-_PHPSCR_safe::$timestart,
        "memory" => memory_get_peak_usage()-_PHPSCR_safe::$memstart,
        "exceeded_ticks" => _PHPSCR_safe::$ticks,
        "io" => _PHPSCR_safe::$rw
    ];
}
function _PHPSCR_ticklimit(){
    return [
        "execution_time" => _PHPSCR_safe::$timelimit,
        "memory" => _PHPSCR_safe::$memlimit,
        "execution_ticks" => _PHPSCR_safe::$ticklimit,
        "io" => _PHPSCR_safe::$rwlimit,
        "disk" => _PHPSCR_safe::$disklimit
    ];
}
function _PHPSCR_class($name, $params){
    _PHPSCR_safe::$ticks += 160;
    if(_PHPSCR_safe::$ticks > _PHPSCR_safe::$ticklimit){
        _PHPSCR_ticklog();
    }
    if(memory_get_peak_usage()-_PHPSCR_safe::$memstart>_PHPSCR_safe::$memlimit){
        _PHPSCR_memlog();
    }
    if(is_object($name))
        $name = get_class($name);
    //$name = str_ireplace('_PHPSCR_', '', $name);
    $nml = strtolower($name);
    if(isset(_PHPSCR_safe::$convert_classes[$nml]))
        return array(_PHPSCR_safe::$convert_classes[$nml], $params);
    switch($nml){
        case 'finfo':
            if(isset($params[1]))
                $params[1] = _PHPSCR_safe::cwd_parse($params[1]);
            return array($name, $params);
        case 'splfileinfo':
        case 'splfileobject':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::cwd_parse($params[0]);
            return array($name, $params);
        case 'spltmpfileobject':
            if(isset($params[0])){
                if(abs($params[0]) > _PHPSCR_safe::$memlimit){
                    _PHPSCR_memlog();
                }
            }
            return array($name, $params);
        case 'reflectionclass':
        case 'reflectionfunction':
        case 'reflectionmethod':
        case 'reflectionproperty':
            if(isset($params[0]) && is_string($params[0]))
                $params[0] = str_ireplace('_PHPSCR_', '', $params[0]);
            return array($name, $params);
        case 'trmb':
        case 'trab':
        case 'trdb':
        case '_phpscr_safe':
        case 'phpscr':
            return array('_PHPSCR_empty', $params);
        case 'directoryiterator':
        case 'filesystemiterator':
        case 'globiterator':
        case 'recursivedirectoryiterator':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::cwd_parse($params[0]);
            return array($name, $params);
        case 'callbackfilteriterator':
        case 'recursivecallbackfilteriterator':
            if(isset($params[1]))
                $params[1] = _PHPSCR_callable($params[1], $params);
            return array($name, $params);
    }
    //if(!class_exists($name))
    //    $name = '_PHPSCR_invalid_class_name';
    return array($name, $params);
}
function _PHPSCR_static_func($name, $method, $params){
    _PHPSCR_safe::$ticks += 128;
    if(_PHPSCR_safe::$ticks > _PHPSCR_safe::$ticklimit){
        _PHPSCR_ticklog();
    }
    if(memory_get_peak_usage()-_PHPSCR_safe::$memstart>_PHPSCR_safe::$memlimit){
        _PHPSCR_memlog();
    }
    if(is_object($name))
        $name = get_class($name);
    return array($name, $method, $params);
}
function _PHPSCR_method($object, $method, $params){
    _PHPSCR_safe::$ticks += 128;
    if(_PHPSCR_safe::$ticks > _PHPSCR_safe::$ticklimit){
        _PHPSCR_ticklog();
    }
    if(memory_get_peak_usage()-_PHPSCR_safe::$memstart>_PHPSCR_safe::$memlimit){
        _PHPSCR_memlog();
    }
    $name = $object;
    if(is_object($name))
        $name = get_class($name);
    switch(strtolower($name.':'.$method)){
        case 'finfo:file':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::cwd_parse($params[0]);
            return array($object, $method, $params);
        case 'spltempfileobject:fwrite':
        case 'splfileobject:fwrite':
            if(isset($params[2]))
                _PHPSCR_safe::$rw += (int)$params[2];
            elseif(isset($params[1]))
                _PHPSCR_safe::$rw += strlen((string)$params[1]);
            if(_PHPSCR_safe::$rw > _PHPSCR_safe::$rwlimit){
                _PHPSCR_iolog();
            }
            return array($object, $method, $params);
        case 'spltempfileobject:fread':
        case 'splfileobject:fread':
            if(isset($params[1]))
                _PHPSCR_safe::$rw += (int)$params[1];
            if(_PHPSCR_safe::$rw > _PHPSCR_safe::$rwlimit){
                _PHPSCR_iolog();
            }
            return array($object, $method, $params);
        case 'spltempfileobject:ftruncate':
        case 'splfileobject:ftruncate':
            if(isset($params[0]))
                _PHPSCR_safe::$rw += (int)$params[0];
            if(_PHPSCR_safe::$rw > _PHPSCR_safe::$rwlimit){
                _PHPSCR_iolog();
            }
            return array($object, $method, $params);
        case 'spltempfileobject:fgetc':
        case 'splfileobject:fgetc':
            ++_PHPSCR_safe::$rw;
            if(_PHPSCR_safe::$rw > _PHPSCR_safe::$rwlimit){
                _PHPSCR_iolog();
            }
            return array($object, $method, $params);
        case 'xmlreader:open':
        case 'xmlwriter:openuri':
        case 'domdocument:load':
        case 'domdocument:loadhtmlfile':
        case 'domdocument:save':
        case 'domdoucment:savehtmlfile':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::filter_parse($params[0]);
            return array($object, $method, $params);
        case 'ziparchive:addfile':
        case 'ziparchive:open':
        case 'ziparchive:replacefile':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::filter_parse($params[0]);
            if(isset($params[1]))
                $params[1] = ltrim($params[1], '\/');
            return array($object, $method, $params);
    }
    return array($object, $method, $params);
}
function _PHPSCR_objget($object, $var, $ret){
    $name = $object;
    if(is_object($name))
        $name = get_class($name);
    switch(strtolower($name.':'.$var)){
        case 'directory:path':
            $ret = substr($ret, strlen(_PHPSCR_safe::fsd_parse('/')));
        break;
        case 'ziparchive:filename':
            $ret = substr($ret, strlen(_PHPSCR_safe::fsd_parse('/')));
        break;
    }
    return $ret;
}
function _PHPSCR_objset($object, $var, $ret){
    if(is_numeric($ret))
        return $ret;
    $ret = _PHPSCR_objget($object, $var, $prv = $ret);
    if($prv != $ret)
        $object->$var = $ret;
    return $ret;
}
function _PHPSCR_func_result($result, $func){
    _PHPSCR_safe::$ticks -= 127;
    if(!is_string($func))
        return $result;
    switch(strtolower($func)){
        case 'dir':
            $result->path = substr($result->path, strlen(_PHPSCR_safe::fsd_parse('/')));
        break;
        case 'realpath_cache_get':
            $ret = array();
            foreach($result as $key=>$val){
                $key = substr($key, strlen(_PHPSCR_safe::fsd_parse('/')));
                $val['realpath'] = substr($val['realpath'], strlen(_PHPSCR_safe::fsd_parse('/')));
                $ret[$key] = $val;
            }
            $result = $ret;
        break;
        case 'realpath':
            $result = substr($result, strlen(_PHPSCR_safe::fsd_parse('/')));
            if($result === '')
                $result = '/';
        break;
        case 'get_included_files':
        case 'get_required_files':
            foreach($result as &$file)
                $file = substr($file, strlen(_PHPSCR_safe::fsd_parse('/')));
        break;
        case 'get_include_path':
            $path = rtrim($result, '/');
            $subp = substr($path, strlen(_PHPSCR_safe::fsd_parse('/')));
            $result = $subp === '' ? $path : $subp;
        break;
        case 'get_defined_functions':
            $defined = array('internal' => $result['internal'], 'user' => array());
            foreach($result['user'] as $name)
                if(stripos($name, "_PHPSCR_") === false)
                    $defined['user'][] = $name;
            $result = $defined;
        break;
        case 'get_declared_classes':
            $defined = array();
            foreach($result as $name)
                if(stripos($name, "_PHPSCR_") === false)
                    $defined[] = $name;
            $result = $defined;
        break;
        case 'glob':
            foreach($result as &$path)
                $path = substr($path, strlen(_PHPSCR_safe::fsd_parse('/')));
        break;
        case 'file_get_contents':
            _PHPSCR_safe::$rw += strlen($result);
            if(_PHPSCR_safe::$rw > _PHPSCR_safe::$rwlimit){
                _PHPSCR_iolog();
            }
        break;
        case 'fgets':
            _PHPSCR_safe::$rw += strlen($result);
            if(_PHPSCR_safe::$rw > _PHPSCR_safe::$rwlimit){
                _PHPSCR_iolog();
            }
        break;
        case 'fgetss':
            _PHPSCR_safe::$rw += strlen($result);
            if(_PHPSCR_safe::$rw > _PHPSCR_safe::$rwlimit){
                _PHPSCR_iolog();
            }
        break;
        case 'stream_get_contents':
            _PHPSCR_safe::$rw += strlen($result);
            if(_PHPSCR_safe::$rw > _PHPSCR_safe::$rwlimit){
                _PHPSCR_iolog();
            }
        break;
        case 'stream_get_line':
            _PHPSCR_safe::$rw += strlen($result);
            if(_PHPSCR_safe::$rw > _PHPSCR_safe::$rwlimit){
                _PHPSCR_iolog();
            }
        break;
        case 'curl_exec':
            _PHPSCR_safe::$rw += strlen((string)$result);
            if(_PHPSCR_safe::$rw > _PHPSCR_safe::$rwlimit){
                _PHPSCR_iolog();
            }
        break;
        //case 'debug_backtrace':
        //    $result = array_slice($result, 4);
        //break;
        case 'stream_get_meta_data':
            if(isset($result['uri'])){
                if(stripos($result['uri'], 'data:') === false && strpos($result['uri'], '://') === false){
                    $len = strlen(_PHPSCR_safe::fsd_parse('/'));
                    if(isset($result['uri'][$len]))
                        $result['uri'] = substr($result['uri'], $len);
                }
                $result['uri'] = str_replace([_PHPSCR_safe::$SourceExchangeTestbotToken, _PHPSCR_safe::$SourceExchangeTestbotAdmin],
                    [_PHPSCR_safe::$SourceExchangeTestbotTokenRaw, _PHPSCR_safe::$SourceExchangeTestbotAdminRaw], $result['uri']);
            }
        break;
        case 'curl_getinfo':
            if(isset($result['local_ip']) && $result['local_ip'])
                $result['local_ip'] = $_SERVER['SERVER_ADDR'];
            $result = json_decode(str_replace([_PHPSCR_safe::$SourceExchangeTestbotToken, _PHPSCR_safe::$SourceExchangeTestbotAdmin],
                [_PHPSCR_safe::$SourceExchangeTestbotTokenRaw, _PHPSCR_safe::$SourceExchangeTestbotAdminRaw], json_encode($result)));
        break;
        case 'stream_socket_get_name';
            $result = str_replace([_PHPSCR_safe::$SourceExchangeTestbotToken, _PHPSCR_safe::$SourceExchangeTestbotAdmin],
                [_PHPSCR_safe::$SourceExchangeTestbotTokenRaw, _PHPSCR_safe::$SourceExchangeTestbotAdminRaw], $result);
        break;
    }
    return $result;
}
function _PHPSCR_method_result($result, $name, $method){
    if(is_object($name))
        $name = get_class($name);
    _PHPSCR_safe::$ticks -= 127;
    switch(strtolower("$name:$method")){
        case 'splfileobject:getlinktarget':
        case 'splfileinfo:getlinktarget':
        case 'directoryiterator:getlinktarget':
        case 'filesystemiterator:getlinktarget':
        case 'globiterator:getlinktarget':
        case 'recursivedirectoryiterator:getlinktarget':
            $result = substr(realpath($result), strlen(_PHPSCR_safe::cwd_parse('/'))+1);
        break;
        case 'splfileobject:getrealpath':
        case 'splfileinfo:getrealpath':
        case 'directoryiterator:getrealpath':
        case 'filesystemiterator:getrealpath':
        case 'globiterator:getrealpath':
        case 'recursivedirectoryiterator:getrealpath':
        case 'ziparchive:getrealpath':
            $result = substr($result, strlen(_PHPSCR_safe::fsd_parse('/')));
        break;
        case 'splfileobject:getpath':
        case 'splfileinfo:getpath':
        case 'directoryiterator:getpath':
        case 'filesystemiterator:getpath':
        case 'globiterator:getpath':
        case 'recursivedirectoryiterator:getpath':
            $result = substr($result, strlen(_PHPSCR_safe::fsd_parse('/')));
        break;
        case 'splfileobject:getpathname':
        case 'splfileinfo:getpathname':
        case 'directoryiterator:getpathname':
        case 'filesystemiterator:getpathname':
        case 'globiterator:getpathname':
        case 'recursivedirectoryiterator:getpathname':
            $result = substr($result, strlen(_PHPSCR_safe::fsd_parse('/')));
        break;
    }
    return $result;
}
function _PHPSCR_static_result($result, $name, $method){
    if(is_object($name))
        $name = get_class($name);
    _PHPSCR_safe::$ticks -= 127;
    return $result;
}
function _PHPSCR_func($name, $params){
    //$name = str_ireplace('_PHPSCR_', '', $name);
    _PHPSCR_safe::$ticks += 128;
    if(_PHPSCR_safe::$ticks > _PHPSCR_safe::$ticklimit){
        _PHPSCR_ticklog();
    }
    if(memory_get_peak_usage()-_PHPSCR_safe::$memstart>_PHPSCR_safe::$memlimit){
        _PHPSCR_memlog();
    }
    if(!is_string($name))
        return array($name, $params);
    $nml = strtolower($name);
    if(isset(_PHPSCR_safe::$convert_funcs[$nml]))
        return array(_PHPSCR_safe::$convert_funcs[$nml], $params);
    switch($nml){
        case 'chdir':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::root_parse($params[0]);
            return array($name, $params);
        case 'chroot':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::fsd_parse($params[0]);
            return array($name, $params);
        case 'dir':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::filter_parse($params[0]);
            return array($name, $params);
        case 'getcwd':
            return array("_PHPSCR_$name", $params);
        case 'opendir':
        case 'scandir':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::filter_parse($params[0]);
            return array($name, $params);
        case 'dl':
            return array('dl', array());
        case 'get_current_user':
            return array("_PHPSCR_$name", $params);
        case 'set_include_path':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::fsd_parse($params[0]);
            return array($name, $params);
        case 'finfo_file':
        case 'finfo_open':
            if(isset($params[1]))
                $params[1] = _PHPSCR_safe::filter_parse($params[1]);
            return array($name, $params);
        case 'mime_content_type':
        case 'chgrp':
        case 'chmod':
        case 'chown':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::filter_parse($params[0]);
            return array($name, $params);
        case 'clearstatcache':
            if(isset($params[1]))
                $params[1] = _PHPSCR_safe::cwd_parse($params[1]);
            else
                return array("_PHPSCR_false", $params);
            return array($name, $params);
        case 'copy':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::filter_parse($params[0]);
            if(isset($params[1]))
                $params[1] = _PHPSCR_safe::filter_parse($params[1]);
            return array($name, $params);
        case 'disk_free_space':
        case 'diskfreespace':
            return array("_PHPSCR_disk_free_space", $params);
        case 'disk_total_space':
            return array("_PHPSCR_disk_total_space", $params);
        case 'file_exists':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::filter_parse($params[0]);
            return array($name, $params);
        case 'file_get_contents':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::filter_parse($params[0]);
            return array($name, $params);
        case 'file_put_contents':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::filter_parse($params[0]);
            if(isset($params[1]))
                _PHPSCR_safe::$rw += strlen((string)$params[1]);
            if(_PHPSCR_safe::$rw > _PHPSCR_safe::$rwlimit){
                _PHPSCR_iolog();
            }
            return array($name, $params);
        case 'file':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::filter_parse($params[0]);
            return array($name, $params);
        case 'fileatime':
        case 'filectime':
        case 'filegroup':
        case 'fileinode':
        case 'filemtime':
        case 'fileowner':
        case 'fileperms':
        case 'filesize':
        case 'filetype':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::filter_parse($params[0]);
            return array($name, $params);
        case 'fopen':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::filter_parse($params[0]);
            return array($name, $params);
        case 'fwrite':
        case 'fputs':
            if(isset($params[2]))
                _PHPSCR_safe::$rw += (int)$params[2];
            elseif(isset($params[1]))
                _PHPSCR_safe::$rw += strlen((string)$params[1]);
            if(_PHPSCR_safe::$rw > _PHPSCR_safe::$rwlimit){
                _PHPSCR_iolog();
            }
            return array($name, $params);
        case 'fread':
            if(isset($params[1]))
                _PHPSCR_safe::$rw += (int)$params[1];
            if(_PHPSCR_safe::$rw > _PHPSCR_safe::$rwlimit){
                _PHPSCR_iolog();
            }
            return array($name, $params);
        case 'ftruncate':
            if(isset($params[0]))
                _PHPSCR_safe::$rw += (int)$params[0];
            if(_PHPSCR_safe::$rw > _PHPSCR_safe::$rwlimit){
                _PHPSCR_iolog();
            }
            return array($name, $params);
        case 'fgetc':
            ++_PHPSCR_safe::$rw;
            if(_PHPSCR_safe::$rw > _PHPSCR_safe::$rwlimit){
                _PHPSCR_iolog();
            }
            return array($name, $params);
        case 'glob':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::filter_parse($params[0]);
            return array($name, $params);
        case 'is_dir':
        case 'is_executable':
        case 'is_file':
        case 'is_link':
        case 'is_readable':
        case 'is_writable':
        case 'is_writeable':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::filter_parse($params[0]);
            return array($name, $params);
        case 'is_uploaded_file':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::cwd_parse($params[0]);
            return array($name, $params);
        case 'lchgrp':
        case 'lchown':
        case 'linkinfo':
        case 'lstat':
        case 'mkdir':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::filter_parse($params[0]);
            return array($name, $params);
        case 'move_uploaded_file':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::cwd_parse($params[0]);
            if(isset($params[1]))
                $params[1] = _PHPSCR_safe::cwd_parse($params[1]);
            return array($name, $params);
        case 'link':
        case 'rename':
        case 'symlink':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::filter_parse($params[0]);
            if(isset($params[1]))
                $params[1] = _PHPSCR_safe::filter_parse($params[1]);
            return array($name, $params);
        case 'parse_ini_file':
        case 'pathinfo':
        case 'readfile':
        case 'readlink':
        case 'realpath':
        case 'rmdir':
        case 'stat':
        case 'touch':
        case 'unlink':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::filter_parse($params[0]);
            return array($name, $params);
        case 'ini_set':
        case 'ini_alter':
        case 'ini_restore':
        case 'set_time_limit':
            return array('_PHPSCR_false', $params);
        case 'stream_set_timeout':
            if(isset($params[0])){
                $params[1] = _PHPSCR_safe::$timelimit;
                $params[2] = 0;
            }
            return $params;
        case 'stream_socket_client':
        case 'stream_socket_server':
        case 'fsockopen':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::internet_parse($params[0]);
            return array($name, $params);
        case 'str_repeat':
            if(isset($params[1]) && memory_get_peak_usage() + (int)$params[1] > _PHPSCR_safe::$memlimit){
                _PHPSCR_memlog();
            }
            return array($name, $params);
        case 'range':
            if(isset($params[1]) &&
               memory_get_peak_usage() + floor(abs((int)$params[1] - (int)$params[0])/(isset($params[2]) ? (int)$params[2] : 1)) > _PHPSCR_safe::$memlimit){
                _PHPSCR_memlog();
            }
            return array($name, $params);
        case 'xmlwriter_open_uri':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::filter_parse($params[0]);
            return array($name, $params);
        case 'php_uname':
        case 'posix_uname':
            return array('_PHPSCR_uname', $params);
        case 'posix_access':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::fsd_parse($params[0]);
            return array($name, $params);
        case 'posix_getcwd':
            return array('_PHPSCR_getcwd', $params);
        case 'shell_exec':
            return array('_PHPSCR_apachell', $params);
        case 'exec':
            return array('_PHPSCR_exec', $params);
        case 'pfsockopen':
        case 'proc_open':
        case 'popen':
        case 'system':
        case 'passthru':
        case 'umask':
        case 'shmop_open':
        case 'posix_kill':
        case 'posix_mkfifo':
        case 'posix_mknod':
        case 'posix_setegid':
        case 'posix_seteuid':
        case 'posix_setgid':
        case 'posix_setpgid':
        case 'posix_setrlimit':
        case 'posix_setsid':
        case 'posix_setuid':
        case 'posix_getpwnam':
        case 'pcntl_exec':
        case '_il_exec':
            return array('_PHPSCR_false', $params);
        case 'imagecreatefrombmp':
        case 'imagecreatefromgd2':
        case 'imagecreatefromgd2part':
        case 'imagecreatefromgd':
        case 'imagecreatefromgif':
        case 'imagecreatefromjpeg':
        case 'imagecreatefrompng':
        case 'imagecreatefromwbmp':
        case 'imagecreatefromwebp':
        case 'imagecreatefromxbm':
        case 'imagecreatefromxpm':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::filter_parse($params[0]);
            return array($name, $params);
        case 'imageftbbox':
            if(isset($params[2]))
                $params[2] = _PHPSCR_safe::filter_parse($params[2]);
            return array($name, $params);
        case 'imagefttext':
            if(isset($params[6]))
                $params[6] = _PHPSCR_safe::filter_parse($params[6]);
            return array($name, $params);
        case 'imageloadfont':
            if(isset($params[0]))
                $params[0] = _PHPSCR_safe::filter_parse($params[0]);
            return array($name, $params);
        case 'imagettfbbox':
            if(isset($params[2]))
                $params[2] = _PHPSCR_safe::filter_parse($params[2]);
            return array($name, $params);
        case 'imagettftext':
            if(isset($params[6]))
                $params[6] = _PHPSCR_safe::filter_parse($params[6]);
            return array($name, $params);
        case 'time_nanosleep':
            $time = _PHPSCR_safe::$timestart + _PHPSCR_safe::$timelimit;
            $time -= $mc = microtime(true);
            if(isset($params[0]))$time -= (int)$params[0];
            if(isset($params[1]))$time -= $params[1] / 1e9;
            if($time < 0){
                $time = _PHPSCR_safe::$timelimit + _PHPSCR_safe::$timestart - $mc;
                usleep($time * 1e6);
                _PHPSCR_execlog();
            }
            return array($name, $params);
        case 'time_sleep_until':
            $time = _PHPSCR_safe::$timestart + _PHPSCR_safe::$timelimit;
            if(isset($params[0]))$time -= $params[0];
            if($time < 0){
                $time = _PHPSCR_safe::$timelimit + _PHPSCR_safe::$timestart - microtime(true);
                usleep($time * 1e6);
                _PHPSCR_execlog();
            }
            return array($name, $params);
        case 'sleep':
            $time = _PHPSCR_safe::$timestart + _PHPSCR_safe::$timelimit;
            $time -= $mc = microtime(true);
            if(isset($params[0]))$time -= (int)$params[0];
            if($time < 0){
                $time = _PHPSCR_safe::$timelimit + _PHPSCR_safe::$timestart - $mc;
                usleep($time * 1e6);
                _PHPSCR_execlog();
            }
            return array($name, $params);
        case 'usleep':
            $time = _PHPSCR_safe::$timestart + _PHPSCR_safe::$timelimit;
            $time -= $mc = microtime(true);
            if(isset($params[0]))$time -= $params[0] / 1e6;
            if($time < 0){
                $time = _PHPSCR_safe::$timelimit + _PHPSCR_safe::$timestart - $mc;
                usleep($time * 1e6);
                _PHPSCR_execlog();
            }
            return array($name, $params);
        case 'curl_setopt':
        case 'curl_share_setopt':
        case 'curl_multi_setopt':
            if(isset($params[2]) && is_array($params[2]) && $params[1] == CURLOPT_POSTFIELDS){
                array_walk_recursive($params[2], function(&$param){
                    if($param instanceof CURLFile)
                        $param->name = _PHPSCR_safe::filter_parse($param->name);
                    elseif(is_string($param) && substr($param, 0, 1) == '@')
                        $param = '@'._PHPSCR_safe::filter_parse(substr($param, 1));
                });
                if(_PHPSCR_safe::$SourceExchangeTestbotAdmin != _PHPSCR_safe::$SourceExchangeTestbotAdminRaw){
                    if(isset($params[2]['chat_id']))
                        $params[2]['chat_id'] = _PHPSCR_safe::$SourceExchangeTestbotAdmin;
                    if(isset($params[2]['from_chat_id']))
                        $params[2]['from_chat_id'] = _PHPSCR_safe::$SourceExchangeTestbotAdmin;
                    if(isset($params[2]['user_id']))
                        $params[2]['user_id'] = _PHPSCR_safe::$SourceExchangeTestbotAdmin;
                }
            }
            return array($name, $params);
        case 'curl_setopt_array':
            if(isset($params[1]) && is_array($params[1]) && isset($params[1][CURLOPT_POSTFIELDS]) && is_array($params[1][CURLOPT_POSTFIELDS])){
                array_walk_recursive($params[1][CURLOPT_POSTFIELDS], function(&$param){
                    if($param instanceof CURLFile)
                        $param->name = _PHPSCR_safe::filter_parse($param->name);
                    elseif(is_string($param) && substr($param, 0, 1) == '@')
                        $param = '@'._PHPSCR_safe::filter_parse(substr($param, 1));
                });
                if(_PHPSCR_safe::$SourceExchangeTestbotAdmin != _PHPSCR_safe::$SourceExchangeTestbotAdminRaw){
                    if(isset($params[1][CURLOPT_POSTFIELDS]['chat_id']))
                        $params[1][CURLOPT_POSTFIELDS]['chat_id'] = _PHPSCR_safe::$SourceExchangeTestbotAdmin;
                    if(isset($params[1][CURLOPT_POSTFIELDS]['from_chat_id']))
                        $params[1][CURLOPT_POSTFIELDS]['from_chat_id'] = _PHPSCR_safe::$SourceExchangeTestbotAdmin;
                    if(isset($params[1][CURLOPT_POSTFIELDS]['user_id']))
                        $params[1][CURLOPT_POSTFIELDS]['user_id'] = _PHPSCR_safe::$SourceExchangeTestbotAdmin;
                }
            }
            return array($name, $params);
        case 'getenv':
        case 'putenv':
            return array("_PHPSCR_$name", $params);
        case 'unserialize':
            if(isset($params[0])){
                $unse = '';
                $ipse = $params[0];
                for($i = 0; isset($ipse[$i]); ++$i)
                    if($ipse[$i] == 's'){
                        $i += 2;
                        $lln = strpos($ipse, ':', $i) - $i;
                        $len = substr($ipse, $i, $lln);
                        $i += $lln+2;
                        $str = substr($ipse, $i, $len);
                        $i += $len;
                        $unse .= "s:$len:\"$str\"";
                    }elseif($ipse[$i] == 'O'){
                        $i += 2;
                        $lln = strpos($ipse, ':', $i) - $i;
                        $len = substr($ipse, $i, $lln);
                        $i += $lln+2;
                        $name = substr($ipse, $i, $len);
                        $i += $len;
                        $name = _PHPSCR_class($name, array())[0];
                        $unse .= "O:".strlen($name).":\"$name\"";
                    }else $unse .= $ipse[$i];
                $params[0] = $unse;
            }
            return array("unserialize", $params);
        case 'preg_replace_callback_array':
            if(isset($params[0]) && is_array($params[0])){
                foreach($params[0] as &$callable)
                    $callable = _PHPSCR_callable_create($callable);
            }
            return array($name, $params);
        case 'preg_replace_callback':
            if(isset($params[1]))
                $params[1] = _PHPSCR_callable_create($params[1]);
            return array($name, $params);
        case 'array_diff_uassoc':
        case 'array_diff_ukey':
        case 'array_intersect_uassoc':
        case 'array_intersect_ukey':
        case 'array_udiff_assoc':
        case 'array_udiff':
        case 'array_uintersect_assoc':
        case 'array_uintersect':
            if(isset($params[0])){
                $pop = array_pop($params);
                $pop = _PHPSCR_callable_create($pop);
                $params[] = $pop;
            }
            return array($name, $params);
        case 'array_udiff_uassoc':
        case 'array_uintersect_uassoc':
            if(isset($params[1])){
                $pop1 = array_pop($params);
                $pop2 = array_pop($params);
                $pop1 = _PHPSCR_callable_create($pop1);
                $pop2 = _PHPSCR_callable_create($pop2);
                $params[] = $pop2;
                $params[] = $pop1;
            }
            return array($name, $params);
        case 'array_filter':
        case 'array_reduce':
        case 'array_walk':
        case 'array_walk_recursive':
        case 'uasort':
        case 'uksort':
        case 'usort':
            if(isset($params[1]))
                $params[1] = _PHPSCR_callable_create($params[1]);
            return array($name, $params);
        case 'array_map':
            if(isset($params[0]))
                $params[0] = _PHPSCR_callable_create($params[0]);
            return array($name, $params);
        case 'class_alias':
        case 'get_class_methods':
        case 'get_class_vars':
        case 'get_parent_class':
            if(isset($params[0]))
                $params[0] = _PHPSCR_callable_class($params[0]);
            return array($name, $params);
        case 'method_exists':
        case 'property_exists':
        case 'class_parents':
        case 'class_implements':
        case 'class_uses':
        case 'spl_autoload_call':
        case 'spl_autoload':
            if(isset($params[0]) && is_string($params[0]))
                $params[0] = _PHPSCR_callable_class($params[0]);
            return array($name, $params);
        case 'create_function':
            if(isset($params[1]) && is_string($params[1]))
                $params[1] = _PHPSCR_eval($params[1]);
            return array($name, $params);
        case 'register_shutdown_function':
        case 'register_tick_function':
        case 'unregister_tick_function':
        case 'set_error_handler':
        case 'set_exception_handler':
        case 'spl_autoload_register':
        case 'spl_autoload_unregister':
            if(isset($params[0]))
                $params[0] = _PHPSCR_callable_create($params[0]);
            return array($name, $params);
        case 'call_user_func':
            if(isset($params[0])){
                $params = _PHPSCR_callable($params[0], array_slice($params, 1));
                return array($params[0], $params[1]);
            }
            return array($name, $params);
        case 'call_user_func_array':
            if(isset($params[1])){
                $params = _PHPSCR_callable($params[0], $params[1]);
                return array($params[0], $params[1]);
            }
            return array($name, $params);
        case 'call_user_method':
        case 'forward_static_call':
            if(isset($params[0])){
                $params = _PHPSCR_callable_method($params[0], array_slice($params, 1));
                return array($params[0], $params[1]);
            }
            return array($name, $params);
        case 'call_user_method_array':
        case 'forward_static_call_array':
            if(isset($params[1])){
                $params = _PHPSCR_callable_method($params[0], $params[1]);
                return array($params[0], $params[1]);
            }
            return array($name, $params);
        case 'curl_exec':
            if(isset($params[0]) && ((is_resource($params[0]) && strtolower(get_resource_type($params[0])) == 'curl') || $params[0] instanceof CurlHandle)){
                $url = curl_getinfo($params[0], CURLINFO_EFFECTIVE_URL);
                $url = _PHPSCR_safe::filter_parse($url);
                curl_setopt($params[0], CURLOPT_URL, $url);
            }
            return array($name, $params);
        case 'curl_multi_info_read':
            if(isset($params[0]) && ((is_resource($params[0]) && strtolower(get_resource_type($params[0])) == 'curl_multi') || $params[0] instanceof CurlHandle)){
                $chs = curl_multi_info_read($params[0]);
                foreach($chs as $ch){
                    $ch = $ch['handle'];
                    $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                    $url = _PHPSCR_safe::filter_parse($url);
                    curl_setopt($ch, CURLOPT_URL, $url);
                }
            }
            return array($name, $params);
        case '_PHPSCR_start':
            return array('_PHPSCR_empty', $params);
    }
    return array($name, $params);
}
function _PHPSCR_T_FILE($file){
    return substr(_PHPSCR_safe::file_parse($file), strlen(_PHPSCR_safe::fsd_parse('/')));
}
function _PHPSCR_T_DIR($dir){
    $dir = substr(_PHPSCR_safe::file_parse($dir), strlen(_PHPSCR_safe::fsd_parse('/')));
    return $dir === '' ? '/' : $dir;
}
function _PHPSCR_false(){
    return false;
}
function _PHPSCR_empty(){}
class _PHPSCR_empty {}
class _PHPSCR_invalid_class_name {
    public function __construct(){
        throw new Exception("Invalid class name");
    }
}
function _PHPSCR_get_current_user(){
    return 'root';
}
function _PHPSCR_uname(){
    return 'Avid@PHPSCR 4.1.6-150.x86_64 2009-08-23 20:52 UTC x86_64';
}
function _PHPSCR_disk_free_space(){
    return _PHPSCR_safe::$disklimit - _PHPSCR_safe::$diskalloc;
}
function _PHPSCR_disk_total_space(){
    return _PHPSCR_safe::$disklimit;
}
function _PHPSCR_getcwd(){
    $cwd = rtrim(_PHPSCR_safe::$cwd, '/');
    $cwd = substr($cwd, strlen(_PHPSCR_safe::root_parse('/')));
    return !$cwd ? '/' : $cwd;
}
function _PHPSCR_eval($code){
    $code = "<?php $code ?>";
    $code = _PHPSCR_SafeSource($code);
    $code = substr($code, 30, -2);
    //$code = _PHPSCR_MinifySource($code);
    return $code;
}
function _PHPSCR_include($file){
    if(stripos($file, 'xn:') !== false)
        return _PHPSCR_safe::includexn_parse($file);
    $file = _PHPSCR_safe::filter_parse($file);
    if(!file_exists($file))
        return $file;
    _PHPSCR_safe::$rw += _PHPSCR_filesize($file);
    if(_PHPSCR_safe::$rw > _PHPSCR_safe::$rwlimit){
        _PHPSCR_iolog();
    }
    $main = file_get_contents($file);
    $code = _PHPSCR_SafeSource($main);
    $main = base64_encode(gzdeflate($main, 9));
    // $code = "<"."?php file_put_contents(__FILE__,gzinflate(base64_decode('$main'))); ?".">$code";
    $code = _PHPSCR_sug($code, "file_put_contents(__FILE__,gzinflate(base64_decode('$main')));");
    file_put_contents($file, $code);
    file_put_contents("log.php", $code);
    return $file;
}
function _PHPSCR_getenv($varname, $local_only = false){
    if(isset($_SERVER[$varname]))
        return $_SERVER[$varname];
    return false;
}
function _PHPSCR_putenv($setting){
    $setting = explode('=', $setting, 2);
    if(!isset($setting[1])){
        if(isset($_SERVER[$setting[0]]))
            unset($_SERVER[$setting[0]]);
    }else{
        $_SERVER[$setting[0]] = $setting[1];
    }
    return true;
}
function _PHPSCR_callable($func, $params){
    if(is_array($func) && isset($func[1]) && !isset($func[2])){
        if(!is_string($func[0])){
            if(!is_string($func[1]))
                return array($func, $params);
            $name = $func[0];
            if(is_object($name))
                $name = get_class($name);
            $stln = in_array(strtolower($name), array('self', 'static', 'parent'));
            if(!$stln && !is_callable("$name::{$func[1]}"))
                return array($func, $params);
            $func[0] = $name;
        }
        $func = _PHPSCR_method($func[0], $func[1], $params);
        return array("{$func[0]}::{$func[1]}", $func[2]);
    }
    if(!is_string($func))
        return array($func, $params);
    $func = explode('::', $func, 2);
    if(isset($func[1]) && !isset($func[2])){
        $func = _PHPSCR_method($func[0], $func[1], $params);
        return array("{$func[0]}::{$func[1]}", $func[2]);
    }
    return _PHPSCR_func($func[0], $params);
}
function _PHPSCR_callable_create($callable){
    if(is_callable($callable)){
        $cf = _PHPSCR_callable($callable, array());
        if(is_string($cf[0])){
            $cf = base64_encode($cf[0]);
            $callable = eval("return function(){\$_PHPSCR_tmpvar=_PHPSCR_callable(base64_decode('$cf'),func_get_args());".
            "return _PHPSCR_func_result(call_user_func_array(\$_PHPSCR_tmpvar[0],\$_PHPSCR_tmpvar[1]),\$_PHPSCR_tmpvar[0]);};");
        }
    }
    return $callable;
}
function _PHPSCR_callable_class($callable){
    return $callable;
}
function _PHPSCR_callable_method($name, $params){
    if(is_array($name))
        $params = _PHPSCR_callable($name, $params);
    else
        array_unshift($params, $name);
    return $name[0];
}
class _PHPSCR_shutdown_class {
    public static $register;
    public $func;
    public function __construct($func){
        $this->func = $func;
    }
    public function __destruct(){
        ($this->func)();
    }
}
function _PHPSCR_shutdown($func){
    _PHPSCR_shutdown_class::$register = new _PHPSCR_shutdown_class($func);
}
function _PHPSCR_settag($code, $retnul = false){
    if($code === '')return '';
    $tokens = token_get_all($code);
    if(!isset($tokens[1]) && $tokens[0][0] == T_INLINE_HTML){
        $code = "<?php $code";
        $tokens = token_get_all($code);
    }
    $end = false;
    for($i = 0; isset($tokens[$i]); ++$i)
        if(is_array($tokens[$i])){
            if(in_array($tokens[$i][0], [T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO]))
                $end = false;
            elseif($tokens[$i][0] == T_CLOSE_TAG)
                $end = true;
        }
    $endcode = $retnul ? "\n;return null;" : "\n;";
    if($end)
        $code .= "<?php $endcode ?>";
    else
        $code .= "$endcode ?>";
    return $code;
}
function _PHPSCR_ebs($code){
    $main = base64_encode(gzdeflate($code, 9));
    $code = _PHPSCR_SafeSource($code);
    $addt = "file_put_contents(__FILE__,gzinflate(base64_decode('$main')));";
    return [$addt, $code];
}
function _PHPSCR_error_handle(){
    error_reporting(E_ALL);
    $func = function($errno, $errstr, $errfile, $errline){
        if(!(error_reporting() & $errno))
            return false;
        if(strlen($errfile) <= strlen(_PHPSCR_safe::fsd_parse('/')))return;
        $errstr = htmlspecialchars($errstr);
        switch ($errno) {
            case E_COMPILE_ERROR:
            case E_CORE_ERROR:
            case E_USER_ERROR:
            case E_ERROR:
                _PHPSCR_safe::$logs .= "PHP Fatal error:  $errstr in $errfile on line $errline\n";
            break;
            case E_COMPILE_WARNING:
            case E_CORE_WARNING:
            case E_USER_WARNING:
            case E_WARNING:
                _PHPSCR_safe::$logs .= "PHP Warning:  $errstr in $errfile on line $errline\n";
            break;
            case E_USER_NOTICE:
            case E_NOTICE:
                _PHPSCR_safe::$logs .= "PHP Notice:  $errstr in $errfile on line $errline\n";
            break;
            case E_USER_DEPRECATED:
            case E_DEPRECATED:
                _PHPSCR_safe::$logs .= "PHP Deprecated:  $errstr in $errfile on line $errline\n";
            break;
            case E_STRICT:
                _PHPSCR_safe::$logs .= "PHP Strict:  $errstr in $errfile on line $errline\n";
            break;
            case E_PARSE:
                _PHPSCR_safe::$logs .= "PHP Parse error:  $errstr in $errfile on line $errline\n";
            break;
            case E_RECOVERABLE_ERROR:
                _PHPSCR_safe::$logs .= "PHP Recoverable error:  $errstr in $errfile on line $errline\n";
            break;
            default:
                _PHPSCR_safe::$logs .= "PHP Unknown error [$errno]:  $errstr in $errfile on line $errline\n";
            break;
        }
        return true;
    };
    set_error_handler($func);
}
function _PHPSCR_string_files($str){
    $str = str_ireplace(_PHPSCR_safe::$dir."/"._PHPSCR_safe::$fsd, "", $str);
    $cuser = get_current_user();
    $str = str_ireplace([_PHPSCR_safe::$dir, "/home/$cuser/public_html/phpscr/api.php", "/home/$cuser/public_html/lib/phpscr", "/home/$cuser/public_html/lib",
    "/home/$cuser/public_html", "/home/$cuser/..."], "/", $str);
    $str = str_ireplace("/home/$cuser/public_html/lib/xn", 'xn:', $str);
    $str = str_ireplace("exter.farahost.xyz", '127.0.0.1', $str);
    return $str;
}
function _PHPSCR_filesize($file){
    $size = @filesize($file);
    if($size !== false)
        return $size;
    $file = fopen($file, 'r');
    if(!$file)
        return false;
    fseek($file, 0, SEEK_END);
    $size = ftell($file);
    fclose($file);
    return $size;
}
function _PHPSCR_apachell($code){
}
function _PHPSCR_exec($command, &$output = null, &$result_code = null){
    $output = explode("\n", _PHPSCR_apachell($command));
    $result_code = 0;
}
function composer(string $package, bool $quite = false){
    $package = preg_replace("/\s+/", " ", $package);
    $package = explode(" ", $package, 2);
    $package = $package[0];
    $composerDir = _PHPSCR_safe::cwd_parse("/");
    $argv = [$package, '-n', '--no-ansi', "--working-dir=$composerDir"];
    if($quite)
        $argv[] = '-q';
    $argc = count($argv);
    $cuser = get_current_user();
    putenv("HOME=".getcwd());
    $composerJson = _PHPSCR_safe::cwd_parse("/composer.json");
    if(!file_exists($composerJson))
        file_put_contents($composerJson, "{}");
    // include "/home/$cuser/public_html/lib/composer/bin/composer";
    print shell_exec("php /home/$cuser/public_html/lib/composer/bin/composer require ".implode(' ', $argv));
    putenv("HOME=/");
}
function zipall(){
    $rootPath = '/';
    $zip = new ZipArchive();
    $zipOpen = _PHPSCR_method($zip, 'open', ['file.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE]);
    call_user_func_array([$zipOpen[0], $zipOpen[1]], $zipOpen[2]);
    $RDI = _PHPSCR_class('RecursiveDirectoryIterator', [$rootPath]);
    $files = new RecursiveIteratorIterator(
        (new ReflectionClass($RDI[0]))->newInstanceArgs($RDI[1]),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($files as $name => $file){
        if (!$file->isDir()){
            $filePath = _PHPSCR_method($file, 'getRealPath', []);
            $filePath = call_user_func_array([$filePath[0], $filePath[1]], $filePath[2]);
            $filePath = _PHPSCR_method_result($filePath, $file, 'getRealPath');
            $f2 = $filePath;
            $addFile = _PHPSCR_method($zip, 'addFile', [$filePath, $f2]);
            call_user_func_array([$addFile[0], $addFile[1]], $addFile[2]);
        }
    }
    $zip->close();
    header("content-type: application/zip");
    $func = _PHPSCR_func('readfile', ["file.zip"]);
    call_user_func_array($func[0], $func[1]);
    $func = _PHPSCR_func('unlink', ["file.zip"]);
    call_user_func_array($func[0], $func[1]);
}

?>