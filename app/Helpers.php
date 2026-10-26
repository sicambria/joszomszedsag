<?php

use App\Log;

if (!function_exists('logModelChange')) {
  function logModelChange($before, $after, $user_id, $modelName)
  {
    $before = (array) $before;
    $after = (array) $after;
    $diff = array_diff_assoc($before, $after);

    if (count($diff)) {
      foreach ($diff as $key => $value) {
        if ($key == 'password') {
          saveLog($user_id, $modelName.': Jelszót változtatott.');
          continue;
        } else if ($key == 'remember_token') {
          continue;
        }
        // Never store raw user input in logs: strip tags + truncate.
        // Prevents stored-XSS if logs are rendered in admin views.
        $from = isset($before[$key]) ? $before[$key] : '';
        $to = isset($after[$key]) ? $after[$key] : '';
        if (is_array($from) || is_object($from)) {
          $from = json_encode($from);
        }
        if (is_array($to) || is_object($to)) {
          $to = json_encode($to);
        }
        $from = mb_substr(strip_tags((string) $from), 0, 200);
        $to = mb_substr(strip_tags((string) $to), 0, 200);
        saveLog($user_id, $modelName.': "' . $key . '" változtatva "' . $from . '"-ról "' . $to . '"-re');
      }
    }
  }
}

if (!function_exists('saveLog')) {
  function saveLog($user_id, $text)
  {
    Log::saveLog($user_id, $text);
  }
}