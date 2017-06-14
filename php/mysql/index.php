<?php
  ini_set('display_errors', 0);
  require_once './utilities.php';
  // TODO offer a GraphQL endpoint

  $accredited = false;
  $shouldLog = false;
  $httpStatus = 500;
  $result = [];
  try {
    if (($_USE_SESSIONS) && (is_session_started() === FALSE )) session_start();
    // Check for valid request and presence of credentials
    $data = [];
    if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {

        // Fetch data json object
        $rawBody = file_get_contents('php://input');
        // if json object valid
        $data = json_decode($rawBody ?: '', true);
    }

    $data += ['query' => null, 'credentials' => null,'interface' => $_INTERFACE_REST, 'variables' => null];
    if ($data['query'] === null) {
        // $data = $_POST;
        throw new Exception('Empty or invalid query', 1);
    }

    // Connect to the db
    $bdd = connect_to_db();

    // Look for credentials
    $userId = null;
    $credentials = $data['credentials'];
    if ($data['query'] === $_QUERY_LOGIN) {
      if (!is_array($credentials)) {
        throw new Exception("Invalid credentials - A", 1);
      } elseif ((array_key_exists("logKey", $credentials))&&(array_key_exists("userId", $credentials))) {
        // try to login with a logKey -- only check if accredited - return credential if so
        if (is_accredited($bdd, ['userId' => $credentials['userId'], 'logKey' => $credentials['logKey']])) {
          $result['credentials'] = ['userId' => $credentials['userId'], 'logKey' => $credentials['logKey']];
          $result['newUser'] = false;
        }
      } else {
        $logged = login($bdd, $data['credentials']);
        if (is_array($logged)) {
          $result['credentials'] = $logged;
          $result['newUser'] = false;
        } elseif ($_SHOULD_CREATE_USERS_ON_LOGIN) {
          // login failure - try to create new user
          $logged = signup($bdd, $data['credentials']);
          if (is_array($logged)) {
            $result['credentials'] = $logged;
            $result['newUser'] = true;
          }
        }
      }
      if (!array_key_exists('credentials', $result)) {
        throw new Exception("Invalid credentials - B", 1);
      }
    } elseif ($data['query'] === $_QUERY_SIGNUP)  {
      // create new user
      $logged = signup($bdd, $data['credentials']);
      if (is_array($logged)) {
        $result['credentials'] = $logged;
        $result['newUser'] = true;
      }
    } else {
      if (isset($credentials['userId']) && isset($credentials['logKey'])) {
        $userId = $credentials['userId'];
        $logKey = $credentials['logKey'];
      } elseif ($_USE_SESSIONS && isset($_SESSION['userId']) && isset($_SESSION['logKey'])) {
        $userId = $credentials['userId'];
        $logKey = $credentials['logKey'];
      }else {
        $shouldLog = true;
        throw new Exception("is_accredited: no valid credentials passed", 1);
      }

      // TODO Should we use an OAuth library.. ?
      // http://bshaffer.github.io/oauth2-server-php-docs/cookbook/
      $accredited = is_accredited($bdd, ['userId' => $userId, 'logKey' => $logKey]);

      if (!$accredited) {
        $shouldLog = true;
        throw new Exception('Not accredited', 1);
      }

      $query = $data['query'];
      $variables = $data['variables'];

      $interface = $data['interface'];
      if ($data['interface'] === $_INTERFACE_REST) {

        // Add endpoint
        if ($data['query'] == 'add') {
          if (($variables === null) || (!isset($variables['table'])) || (!isset($variables['rows']))) {
            throw new Exception("Invalid data", 1);
          }
          $rows = $variables['rows'];
          if (!is_array($rows)) {
            throw new Exception("Invalid rows", 1);
          }

          $table = $variables['table'];
          if (!is_string($table)) {
            throw new Exception("Invalid table " .json_encode($table), 1);
          }

          $rowsAdded = add_rows($bdd, $table, $rows);
          $result = ['status' => 'OK', 'Rows added' => json_encode($rowsAdded)];
        }

        // Checkpoints endpoint
        if ($query === "getCheckpoint") {
          $checkpoint = get_checkpoint($bdd, $userId);
          $result = ['status' => 'OK'] + $checkpoint;
        }

        if ($query === "setCheckpoints") {
          // treat this as a normal row hanled by add row
        }

        if ($query === 'get') {
          // general endpoint to select information from db
          // limited to the userId
          // is it usefull ? ..probably not

        }
      }

    }

    $httpStatus = 200;
  } catch (Exception $e) {
    $result['message'] = $e.message;
    $httpStatus = 500;

    $result['shouldLog'] = $shouldLog;
  }


  header('Content-Type: application/json', true, $httpStatus);
  echo json_encode($result);
